<?php
declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use Kickback\Backend\Config\ServiceCredentials;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vRecordId;
use Kickback\Common\Version;
use Kickback\Services\Database;
use Kickback\Services\Session;

class SocialMediaController
{

    private static function applyCaBundle($ch) : void
    {
        foreach ([
            ini_get('curl.cainfo'),
            ini_get('openssl.cafile'),
            '/etc/ssl/certs/ca-certificates.crt',
            '/etc/pki/ca-trust/extracted/pem/tls-ca-bundle.pem',
            'C:/xampp/apache/bin/curl-ca-bundle.crt',
        ] as $path) {
            if (is_string($path) && $path !== '' && is_readable($path)) {
                curl_setopt($ch, CURLOPT_CAINFO, $path);
                break;
            }
        }
    }

    /**
     * Perform a Discord API request with common cURL configuration.
     *
     * @param string               $method  HTTP method to use.
     * @param string               $url     Request URL.
     * @param array<string,mixed>|null $payload Optional JSON payload to send.
     * @param array<int,string>    $headers Additional headers.
     *
     * @return array{status:int, body:string|false, error:?string}
     */
    private static function discordApiRequest(string $method, string $url, ?array $payload = null, array $headers = []) : array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return ['status' => 0, 'body' => false, 'error' => 'failed to init'];
        }

        if ($payload !== null) {
            $json = json_encode($payload);
            if ($json === false) {
                curl_close($ch);
                return ['status' => 0, 'body' => false, 'error' => 'json_encode failed'];
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            $headers[] = 'Content-Type: application/json';
        }

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($headers !== []) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        self::applyCaBundle($ch);

        $body  = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = null;
        if ($body === false) {
            $error = curl_error($ch);
        }
        curl_close($ch);

        return ['status' => $status, 'body' => $body, 'error' => $error];
    }

    public static function DiscordWebHook(mixed $msg) : void
    {
        $kk_credentials = ServiceCredentials::instance();

        // Ex: $webhookURL = "https://discord.com/api/webhooks/<some_number>/<api_key>"
        $webhookURL = $kk_credentials["discord_api_url"] . '/' . $kk_credentials["discord_api_key"];

        $result = self::discordApiRequest('POST', $webhookURL, ['content' => $msg]);
        if ($result['body'] === false) {
            echo 'Error:' . ($result['error'] ?? 'unknown');
        }
    }

    private static function sendChannelMessage(string $channelId, string $message) : void
    {
        $botToken = ServiceCredentials::get_discord_bot_token();
        if (!$botToken) {
            return;
        }
        $url = 'https://discord.com/api/channels/' . urlencode($channelId) . '/messages';
        self::discordApiRequest('POST', $url, ['content' => $message], [
            'Authorization: Bot ' . $botToken,
        ]);
    }

    public static function assignVerifiedRole(string $discordUserId) : Response
    {
        $guildId  = ServiceCredentials::get_discord_guild_id();
        $botToken = ServiceCredentials::get_discord_bot_token();
        $roleId   = ServiceCredentials::get_discord_verified_role_id();
        if (!$guildId || !$botToken || !$roleId) {
            return new Response(false, 'missing configuration');
        }
        $memberUrl = 'https://discord.com/api/guilds/' . urlencode($guildId)
            . '/members/' . urlencode($discordUserId);
        $memberResp = self::discordApiRequest('GET', $memberUrl, null, [
            'Authorization: Bot ' . $botToken,
        ]);
        if ($memberResp['body'] === false) {
            error_log('assignVerifiedRole member fetch failed: ' . ($memberResp['error'] ?? 'unknown'));
            return new Response(false, 'failed to fetch member data');
        }
        if ($memberResp['status'] !== 200) {
            error_log('assignVerifiedRole member fetch HTTP ' . $memberResp['status'] . ' response: ' . $memberResp['body']);
            return new Response(false, 'member fetch HTTP ' . $memberResp['status']);
        }
        $memberData = json_decode($memberResp['body'], true);
        if (!is_array($memberData)) {
            error_log('assignVerifiedRole invalid member data: ' . $memberResp['body']);
            return new Response(false, 'invalid member data');
        }
        if (!empty($memberData['pending'])) {
            error_log('assignVerifiedRole member is pending');
            return new Response(false, 'member pending in guild');
        }

        $botMemberUrl = 'https://discord.com/api/guilds/' . urlencode($guildId)
            . '/members/@me';
        $botRoles = [];
        $botResp = self::discordApiRequest('GET', $botMemberUrl, null, [
            'Authorization: Bot ' . $botToken,
        ]);
        if ($botResp['body'] !== false && $botResp['status'] === 200) {
            $botData = json_decode($botResp['body'], true);
            if (is_array($botData) && isset($botData['roles']) && is_array($botData['roles'])) {
                $botRoles = $botData['roles'];
            }
        }

        $rolesUrl = 'https://discord.com/api/guilds/' . urlencode($guildId) . '/roles';
        $verifiedRolePos = null;
        $verifiedRoleManaged = null;
        $botHighestPos = null;
        $rolesResp = self::discordApiRequest('GET', $rolesUrl, null, [
            'Authorization: Bot ' . $botToken,
        ]);
        if ($rolesResp['body'] !== false && $rolesResp['status'] === 200) {
            $roles = json_decode($rolesResp['body'], true);
            if (is_array($roles)) {
                foreach ($roles as $role) {
                    if (!isset($role['id'], $role['position'])) {
                        continue;
                    }
                    if ($role['id'] === $roleId) {
                        $verifiedRolePos     = $role['position'];
                        $verifiedRoleManaged = !empty($role['managed']);
                    }
                    if (in_array($role['id'], $botRoles, true)) {
                        if ($botHighestPos === null || $role['position'] > $botHighestPos) {
                            $botHighestPos = $role['position'];
                        }
                    }
                }
            }
        }

        error_log('assignVerifiedRole hierarchy botHighest=' . ($botHighestPos ?? 'unknown')
            . ' verifiedRolePos=' . ($verifiedRolePos ?? 'unknown')
            . ' managed=' . ($verifiedRoleManaged ? 'yes' : 'no'));

        if ($verifiedRoleManaged) {
            error_log('assignVerifiedRole verified role is managed');
            return new Response(false, 'verified role is managed');
        }

        if ($verifiedRolePos !== null && $botHighestPos !== null && $botHighestPos <= $verifiedRolePos) {
            error_log('assignVerifiedRole bot role hierarchy insufficient');
            return new Response(false, 'bot role hierarchy insufficient');
        }

        $roleUrl = 'https://discord.com/api/guilds/' . urlencode($guildId)
            . '/members/' . urlencode($discordUserId)
            . '/roles/' . urlencode($roleId);
        $result = self::discordApiRequest('PUT', $roleUrl, null, [
            'Authorization: Bot ' . $botToken,
            'Content-Length: 0',
        ]);
        if ($result['body'] === false) {
            error_log('assignVerifiedRole curl_exec failed: ' . ($result['error'] ?? 'unknown'));
            return new Response(false, 'failed to assign verified role');
        }
        $status = $result['status'];
        if ($status < 200 || $status >= 300) {
            $error = $result['body'];
            $decoded = json_decode($result['body'], true);
            if (is_array($decoded) && isset($decoded['code'])) {
                $error = self::discordErrorMessage((int)$decoded['code'])
                    . ' (' . $decoded['code'] . ')';
            }
            error_log('assignVerifiedRole HTTP status ' . $status . ' error: ' . $error . ' response: ' . $result['body']);
            return new Response(false, 'Discord API error: ' . $error);
        }
        return new Response(true, 'verified role assigned');
    }

    private static function discordErrorMessage(int $code) : string
    {
        $map = [
            10004 => 'Unknown guild',
            10007 => 'Unknown member',
            10011 => 'Unknown role',
            50001 => 'Missing access',
            50013 => 'Missing permissions',
            50035 => 'Invalid form body',
        ];
        return $map[$code] ?? 'Unknown error';
    }

    public static function removeVerifiedRole(string $discordUserId) : void
    {
        $guildId  = ServiceCredentials::get_discord_guild_id();
        $botToken = ServiceCredentials::get_discord_bot_token();
        $roleId   = ServiceCredentials::get_discord_verified_role_id();
        if (!$guildId || !$botToken || !$roleId) {
            return;
        }

        $roleUrl = 'https://discord.com/api/guilds/' . urlencode($guildId)
            . '/members/' . urlencode($discordUserId)
            . '/roles/' . urlencode($roleId);
        self::discordApiRequest('DELETE', $roleUrl, null, [
            'Authorization: Bot ' . $botToken,
            'Content-Length: 0',
        ]);
    }

    public static function restrictChannelToVerified(string $channelId) : void
    {
        $botToken = ServiceCredentials::get_discord_bot_token();
        $roleId   = ServiceCredentials::get_discord_verified_role_id();
        $guildId  = ServiceCredentials::get_discord_guild_id();
        if (!$botToken || !$roleId || !$guildId) {
            return;
        }

        $sendMessages = 1 << 11; // SEND_MESSAGES permission bit

        // Deny send messages for @everyone (guild id)
        $everyoneUrl = 'https://discord.com/api/channels/' . urlencode($channelId)
            . '/permissions/' . urlencode($guildId);
        self::discordApiRequest('PUT', $everyoneUrl, [
            'type'  => 0,
            'allow' => '0',
            'deny'  => (string)$sendMessages,
        ], [
            'Authorization: Bot ' . $botToken,
        ]);

        // Allow send messages for verified role
        $roleUrl = 'https://discord.com/api/channels/' . urlencode($channelId)
            . '/permissions/' . urlencode($roleId);
        self::discordApiRequest('PUT', $roleUrl, [
            'type'  => 0,
            'allow' => (string)$sendMessages,
            'deny'  => '0',
        ], [
            'Authorization: Bot ' . $botToken,
        ]);
    }

    /**
     * Build the Discord OAuth redirect callback URL from the configured base.
     */
    private static function discordRedirectUri() : ?string
    {
        $base = ServiceCredentials::get_discord_redirect_uri();
        if (!$base) {
            $host = $_SERVER['HTTP_HOST'] ?? null;
            if (!$host) {
                return null;
            }
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $base   = $scheme . '://' . $host;
        }
        $base = rtrim($base, '/');
        $beta = Version::urlBetaPrefix();
        if ($beta !== '' && !str_ends_with($base, $beta)) {
            $base .= $beta;
        }
        return $base . '/api/v1/discord/link-callback.php';
    }

    /**
     * Start the Discord OAuth linking process.
     *
     * @return Response Contains the Discord authorization URL on success.
     */
    public static function startDiscordLink() : Response
    {
        Session::ensureSessionStarted();
        if (!Session::isLoggedIn()) {
            return new Response(false, 'User not logged in', null);
        }

        $clientId    = ServiceCredentials::get_discord_oauth_client_id();
        $redirectUri = self::discordRedirectUri();
        if (!$clientId || !$redirectUri) {
            return new Response(false, 'Discord OAuth not configured', null);
        }

        $state = bin2hex(random_bytes(16));
        Session::setSessionData('discord_oauth_state', $state);
        $scope = urlencode('identify guilds.join');

        $authUrl = 'https://discord.com/api/oauth2/authorize'
            . '?client_id=' . urlencode($clientId)
            . '&redirect_uri=' . urlencode($redirectUri)
            . '&response_type=code'
            . '&scope=' . $scope
            . '&state=' . urlencode($state);

        return new Response(true, 'Discord OAuth URL', ['url' => $authUrl]);
    }

    /**
     * Complete the Discord OAuth linking process.
     */
    public static function completeDiscordLink(string $code, string $state) : Response
    {
        Session::ensureSessionStarted();
        if (!Session::readCurrentAccountInto($account)) {
            return new Response(false, 'User not logged in', null);
        }

        $firstLink = empty($account->discordUserId);

        $expectedState = Session::sessionDataString('discord_oauth_state');
        if (!$expectedState || $expectedState !== $state) {
            $sessionId = Session::getCurrentSessionId();
            $username = $account->username ?? 'unknown';
            $msg = 'completeDiscordLink invalid state token: session='
                . ($sessionId ?? 'none')
                . ' user=' . $username
                . ' expected=' . ($expectedState ?? 'none')
                . ' received=' . $state;
            error_log($msg);
            // State token is single-use to prevent replay attacks.
            Session::removeSessionData('discord_oauth_state');
            return new Response(false, 'Invalid state token; please restart the Discord link process.', null);
        }

        $clientId     = ServiceCredentials::get_discord_oauth_client_id();
        $clientSecret = ServiceCredentials::get_discord_oauth_client_secret();
        $redirectUri  = self::discordRedirectUri();
        if (!$clientId || !$clientSecret || !$redirectUri) {
            return new Response(false, 'Discord OAuth not configured', null);
        }

        $tokenCh = curl_init('https://discord.com/api/oauth2/token');
        if ($tokenCh === false) {
            return new Response(false, 'Failed to contact Discord', null);
        }
        curl_setopt($tokenCh, CURLOPT_POST, true);
        curl_setopt($tokenCh, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($tokenCh, CURLOPT_POSTFIELDS, http_build_query([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUri,
        ]));
        $tokenResponse = curl_exec($tokenCh);
        if ($tokenResponse === false) {
            curl_close($tokenCh);
            return new Response(false, 'Failed to contact Discord', null);
        }
        $tokenData = json_decode($tokenResponse, true);
        curl_close($tokenCh);
        if (!isset($tokenData['access_token'])) {
            return new Response(false, 'Failed to retrieve access token', $tokenData);
        }
        $accessToken = $tokenData['access_token'];

        $userCh = curl_init('https://discord.com/api/users/@me');
        if ($userCh === false) {
            return new Response(false, 'Failed to fetch user profile', null);
        }
        curl_setopt($userCh, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ]);
        curl_setopt($userCh, CURLOPT_RETURNTRANSFER, true);
        $userResponse = curl_exec($userCh);
        if ($userResponse === false) {
            curl_close($userCh);
            return new Response(false, 'Failed to fetch user profile', null);
        }
        $userData = json_decode($userResponse, true);
        curl_close($userCh);
        if (!isset($userData['id'], $userData['username'])) {
            return new Response(false, 'Invalid user data from Discord', $userData);
        }

        $discordId       = $userData['id'];
        $discordUsername = $userData['username'];

        $guildJoinError = null;
        $guildId  = ServiceCredentials::get_discord_guild_id();
        $botToken = ServiceCredentials::get_discord_bot_token();
        if ($guildId && $botToken) {
            $memberUrl = 'https://discord.com/api/guilds/' . urlencode($guildId)
                . '/members/' . urlencode($discordId);
            $checkResp = self::discordApiRequest('GET', $memberUrl, null, [
                'Authorization: Bot ' . $botToken,
            ]);
            if ($checkResp['body'] === false) {
                $guildJoinError = 'cURL error during guild membership check: ' . ($checkResp['error'] ?? 'unknown');
            } else {
                $checkStatus = $checkResp['status'];
                if ($checkStatus === 404) {
                    $joinResp = self::discordApiRequest('PUT', $memberUrl, [
                        'access_token' => $accessToken,
                    ], [
                        'Authorization: Bot ' . $botToken,
                    ]);
                    $joinStatus = $joinResp['status'];
                    if ($joinResp['body'] === false) {
                        $guildJoinError = 'cURL error during guild join: ' . ($joinResp['error'] ?? 'unknown');
                        error_log('Failed to join guild: ' . ($joinResp['error'] ?? 'unknown'));
                    } elseif ($joinStatus >= 400) {
                        $guildJoinError = 'Discord API returned status ' . $joinStatus . ' when joining guild';
                        error_log('Failed to join guild: status ' . $joinStatus . ' response: ' . $joinResp['body']);
                    }
                } elseif ($checkStatus >= 400 && $checkStatus !== 200 && $checkStatus !== 204) {
                    $guildJoinError = 'Discord API returned status ' . $checkStatus . ' when checking guild membership';
                }
            }
        }

        $conn = Database::getConnection();
        $stmt = $conn->prepare('UPDATE account SET DiscordUserId = ?, DiscordUsername = ? WHERE Email = ?');
        if (!$stmt) {
            return new Response(false, 'Failed to prepare statement', null);
        }
        $stmt->bind_param('sss', $discordId, $discordUsername, $account->email);
        $stmt->execute();
        $stmt->close();

        $account->discordUserId = $discordId;
        $account->discordUsername = $discordUsername;
        Session::setSessionData('vAccount', $account);

        $roleResponse = self::assignVerifiedRole($discordId);
        Session::setSessionData('discord_oauth_state', null);

        $errors = [];
        if ($guildJoinError !== null) {
            $errors[] = $guildJoinError ?: 'failed to join Discord guild';
        }
        if (!$roleResponse->success) {
            $errors[] = $roleResponse->message;
        }
        if ($errors) {
            $msg = 'Discord account linked, but ' . implode(' and ', $errors);
            return new Response(false, $msg, null);
        }

        if ($firstLink) {
            $channelId = ServiceCredentials::get_discord_link_channel_id();
            if ($channelId) {
                $mention = "<@{$account->discordUserId}>";
                $message = FlavorTextController::getDiscordLinkFlavorText($mention);
                //self::sendChannelMessage($channelId, $message);
                //self::DiscordWebHook($message);
            }
        }
        

        return new Response(true, 'Discord account linked', null);
    }

    /**
     * Check if a Discord account is already linked.
     */
    public static function isDiscordLinked(string $discordId) : Response
    {
        Session::ensureSessionStarted();
        $conn = Database::getConnection();
        $stmt = $conn->prepare('SELECT 1 FROM account WHERE DiscordUserId = ? LIMIT 1');
        if (!$stmt) {
            return new Response(false, 'Failed to prepare statement', null);
        }
        $stmt->bind_param('s', $discordId);
        $stmt->execute();
        $result = $stmt->get_result();
        $linked = $result && $result->num_rows > 0;
        $stmt->close();

        return new Response(true, 'Discord link status', ['linked' => $linked]);
    }

    /**
     * Unlink the given account's Discord profile.
     */
    public static function unlinkDiscordAccount(vAccount $account) : Response
    {
        Session::ensureSessionStarted();
        if (!Session::readCurrentAccountInto($current) || $current->crand !== $account->crand) {
            return new Response(false, 'User not logged in', null);
        }

        if (empty($account->discordUserId)) {
            return new Response(false, 'No Discord account linked', null);
        }

        self::removeVerifiedRole($account->discordUserId);

        $conn = Database::getConnection();
        $stmt = $conn->prepare('UPDATE account SET DiscordUserId = NULL, DiscordUsername = NULL WHERE Email = ?');
        if (!$stmt) {
            return new Response(false, 'Failed to prepare statement', null);
        }
        $stmt->bind_param('s', $account->email);
        $stmt->execute();
        $stmt->close();

        $channelId = ServiceCredentials::get_discord_link_channel_id();
        if ($channelId) {
            $mention = "<@{$account->discordUserId}>";
            $message = FlavorTextController::getDiscordUnlinkFlavorText($mention);
            //self::sendChannelMessage($channelId, $message);
            //self::DiscordWebHook($message);
        }

        $account->discordUserId = null;
        $account->discordUsername = null;
        Session::setSessionData('vAccount', $account);

        return new Response(true, 'Discord account unlinked', null);
    }

    /**
     * Start the Steam OpenID linking process.
     *
     * @return Response Contains the Steam OpenID URL on success.
     */
    public static function startSteamLink() : Response
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
    public static function completeSteamLink(array $query) : Response
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
    public static function isSteamLinked(string $steamId) : Response
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
    public static function unlinkSteamAccount(vAccount $account) : Response
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
