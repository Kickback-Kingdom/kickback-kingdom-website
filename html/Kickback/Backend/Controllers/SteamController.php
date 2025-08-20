<?php
declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use Kickback\Backend\Config\ServiceCredentials;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vAccount;
use Kickback\Services\Database;
use Kickback\Services\Session;

class SteamController
{
    use CurlHelper;

    /**
     * Start the Steam OpenID linking process.
     *
     * @return Response Contains the Steam OpenID URL on success.
     */
    public static function startLink() : Response
    {
        Session::ensureSessionStarted();
        if (!Session::isLoggedIn()) {
            return new Response(false, 'User not logged in', null);
        }

        $redirectUri = ServiceCredentials::get_steam_redirect_uri();
        if (!$redirectUri) {
            return new Response(false, 'Steam OAuth not configured', null);
        }

        $state = bin2hex(random_bytes(16));
        Session::setSessionData('steam_oauth_state', $state);

        $realm = parse_url($redirectUri, PHP_URL_SCHEME)
            . '://' . parse_url($redirectUri, PHP_URL_HOST);
        $params = [
            'openid.ns'       => 'http://specs.openid.net/auth/2.0',
            'openid.mode'     => 'checkid_setup',
            'openid.return_to'=> $redirectUri . '?state=' . urlencode($state),
            'openid.realm'    => $realm,
            'openid.identity' => 'http://specs.openid.net/auth/2.0/identifier_select',
            'openid.claimed_id' => 'http://specs.openid.net/auth/2.0/identifier_select',
        ];

        $authUrl = 'https://steamcommunity.com/openid/login?' . http_build_query($params);

        return new Response(true, 'Steam OAuth URL', ['url' => $authUrl]);
    }

    /**
     * Complete the Steam OpenID linking process.
     *
     * @param array<string,string> $query The OpenID response query parameters.
     */
    public static function completeLink(array $query) : Response
    {
        Session::ensureSessionStarted();
        if (!Session::readCurrentAccountInto($account)) {
            return new Response(false, 'User not logged in', null);
        }

        $expectedState = Session::sessionDataString('steam_oauth_state');
        $state = $query['state'] ?? null;
        if (!$expectedState || !$state || $expectedState !== $state) {
            return new Response(false, 'Invalid state token', null);
        }

        $openidParams = [];
        foreach ($query as $key => $value) {
            if (str_starts_with($key, 'openid_')) {
                $openidParams[str_replace('_', '.', $key)] = $value;
            }
        }
        $openidParams['openid.mode'] = 'check_authentication';

        $verifyCh = curl_init('https://steamcommunity.com/openid/login');
        if ($verifyCh === false) {
            return new Response(false, 'Failed to contact Steam', null);
        }
        curl_setopt($verifyCh, CURLOPT_POST, true);
        curl_setopt($verifyCh, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($verifyCh, CURLOPT_POSTFIELDS, http_build_query($openidParams));
        self::applyCaBundle($verifyCh);
        $verifyResp = curl_exec($verifyCh);
        if ($verifyResp === false) {
            curl_close($verifyCh);
            return new Response(false, 'Failed to contact Steam', null);
        }
        curl_close($verifyCh);
        if (stripos($verifyResp, 'is_valid:true') === false) {
            return new Response(false, 'Invalid authentication response', null);
        }

        if (!isset($query['openid_claimed_id'])
            || !preg_match('/^https:\/\/steamcommunity\.com\/openid\/id\/(\d{17})$/', $query['openid_claimed_id'], $m)) {
            return new Response(false, 'Invalid claimed ID', null);
        }
        $steamId = $m[1];

        $steamName = null;
        $apiKey = ServiceCredentials::get_steam_web_api_key();
        if ($apiKey) {
            $profileUrl = 'https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v2/?key='
                . urlencode($apiKey) . '&steamids=' . urlencode($steamId);
            $profileCh = curl_init($profileUrl);
            if ($profileCh !== false) {
                curl_setopt($profileCh, CURLOPT_RETURNTRANSFER, true);
                self::applyCaBundle($profileCh);
                $profileResp = curl_exec($profileCh);
                if ($profileResp !== false) {
                    $profileData = json_decode($profileResp, true);
                    if (isset($profileData['response']['players'][0]['personaname'])) {
                        $steamName = $profileData['response']['players'][0]['personaname'];
                    }
                }
                curl_close($profileCh);
            }
        }
        if ($steamName === null) {
            $steamName = $steamId;
        }

        $conn = Database::getConnection();
        $stmt = $conn->prepare('UPDATE account SET SteamUserId = ?, SteamUsername = ? WHERE Email = ?');
        if (!$stmt) {
            return new Response(false, 'Failed to prepare statement', null);
        }
        $stmt->bind_param('sss', $steamId, $steamName, $account->email);
        $stmt->execute();
        $stmt->close();

        $account->steamUserId = $steamId;
        $account->steamUsername = $steamName;
        Session::setSessionData('vAccount', $account);
        Session::setSessionData('steam_oauth_state', null);

        return new Response(true, 'Steam account linked', null);
    }

    /**
     * Check if a Steam account is already linked.
     */
    public static function isLinked(string $steamId) : Response
    {
        Session::ensureSessionStarted();
        $conn = Database::getConnection();
        $stmt = $conn->prepare('SELECT 1 FROM account WHERE SteamUserId = ? LIMIT 1');
        if (!$stmt) {
            return new Response(false, 'Failed to prepare statement', null);
        }
        $stmt->bind_param('s', $steamId);
        $stmt->execute();
        $result = $stmt->get_result();
        $linked = $result && $result->num_rows > 0;
        $stmt->close();

        return new Response(true, 'Steam link status', ['linked' => $linked]);
    }

    /**
     * Unlink the given account's Steam profile.
     */
    public static function unlink(vAccount $account) : Response
    {
        Session::ensureSessionStarted();
        $current = Session::requireSteamLinked();
        if ($current->crand !== $account->crand) {
            return new Response(false, 'User not logged in', null);
        }

        $conn = Database::getConnection();
        $stmt = $conn->prepare('UPDATE account SET SteamUserId = NULL, SteamUsername = NULL WHERE Email = ?');
        if (!$stmt) {
            return new Response(false, 'Failed to prepare statement', null);
        }
        $stmt->bind_param('s', $account->email);
        $stmt->execute();
        $stmt->close();

        $account->steamUserId = null;
        $account->steamUsername = null;
        Session::setSessionData('vAccount', $account);

        return new Response(true, 'Steam account unlinked', null);
    }
}
