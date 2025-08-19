<?php
declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use Kickback\Backend\Config\ServiceCredentials;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vRecordId;
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

    public static function DiscordWebHook(mixed $msg) : void
    {
        $kk_credentials = ServiceCredentials::instance();
    
        // Ex: $webhookURL = "https://discord.com/api/webhooks/<some_number>/<api_key>"
        $webhookURL = $kk_credentials["discord_api_url"] . '/' . $kk_credentials["discord_api_key"];
    
        $message = $msg;
    
        $jsonData = json_encode(array("content" => $message));
        if ($jsonData === false) {
            echo 'Error: `json_encode` failed to encode message in `DiscordWebHook` function.';
            echo "Input message: $jsonData";
            return;
        }
    
        // Initialize a cURL session
        $ch = curl_init($webhookURL);
        if ($ch === false) {
            echo 'Error: `curl_init` returned `false`.';
            return;
        }
    
        // Set the options for the cURL session
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData)
        ));
        self::applyCaBundle($ch);

        // Execute the cURL session
        $result = curl_exec($ch);
    
        // Check for errors
        if (0 < curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
    
        // Close the cURL session
        curl_close($ch);
    }

    private static function sendChannelMessage(string $channelId, string $message) : void
    {
        $botToken = ServiceCredentials::get_discord_bot_token();
        if (!$botToken) {
            return;
        }
        $payload = json_encode(['content' => $message]);
        if ($payload === false) {
            return;
        }
        $url = 'https://discord.com/api/channels/' . urlencode($channelId) . '/messages';
        $ch = curl_init($url);
        if ($ch === false) {
            return;
        }
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bot ' . $botToken,
        ]);
        self::applyCaBundle($ch);
        curl_exec($ch);
        curl_close($ch);
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
        $memberCh = curl_init($memberUrl);
        if ($memberCh === false) {
            return new Response(false, 'failed to initialize member fetch');
        }
        curl_setopt($memberCh, CURLOPT_HTTPHEADER, [
            'Authorization: Bot ' . $botToken,
        ]);
        curl_setopt($memberCh, CURLOPT_RETURNTRANSFER, true);
        self::applyCaBundle($memberCh);
        $memberResp = curl_exec($memberCh);
        if ($memberResp === false) {
            error_log('assignVerifiedRole member fetch failed: ' . curl_error($memberCh));
            curl_close($memberCh);
            return new Response(false, 'failed to fetch member data');
        }
        $memberStatus = curl_getinfo($memberCh, CURLINFO_HTTP_CODE);
        curl_close($memberCh);
        if ($memberStatus !== 200) {
            error_log('assignVerifiedRole member fetch HTTP ' . $memberStatus . ' response: ' . $memberResp);
            return new Response(false, 'member fetch HTTP ' . $memberStatus);
        }
        $memberData = json_decode($memberResp, true);
        if (!is_array($memberData)) {
            error_log('assignVerifiedRole invalid member data: ' . $memberResp);
            return new Response(false, 'invalid member data');
        }
        if (!empty($memberData['pending'])) {
            error_log('assignVerifiedRole member is pending');
            return new Response(false, 'member pending in guild');
        }

        $botMemberUrl = 'https://discord.com/api/guilds/' . urlencode($guildId)
            . '/members/@me';
        $botRoles = [];
        $botCh = curl_init($botMemberUrl);
        if ($botCh !== false) {
            curl_setopt($botCh, CURLOPT_HTTPHEADER, [
                'Authorization: Bot ' . $botToken,
            ]);
            curl_setopt($botCh, CURLOPT_RETURNTRANSFER, true);
            self::applyCaBundle($botCh);
            $botResp = curl_exec($botCh);
            if ($botResp !== false && curl_getinfo($botCh, CURLINFO_HTTP_CODE) === 200) {
                $botData = json_decode($botResp, true);
                if (is_array($botData) && isset($botData['roles']) && is_array($botData['roles'])) {
                    $botRoles = $botData['roles'];
                }
            }
            curl_close($botCh);
        }

        $rolesUrl = 'https://discord.com/api/guilds/' . urlencode($guildId) . '/roles';
        $rolesCh = curl_init($rolesUrl);
        $verifiedRolePos = null;
        $verifiedRoleManaged = null;
        $botHighestPos = null;
        if ($rolesCh !== false) {
            curl_setopt($rolesCh, CURLOPT_HTTPHEADER, [
                'Authorization: Bot ' . $botToken,
            ]);
            curl_setopt($rolesCh, CURLOPT_RETURNTRANSFER, true);
            self::applyCaBundle($rolesCh);
            $rolesResp = curl_exec($rolesCh);
            if ($rolesResp !== false && curl_getinfo($rolesCh, CURLINFO_HTTP_CODE) === 200) {
                $roles = json_decode($rolesResp, true);
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
            curl_close($rolesCh);
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
        $ch = curl_init($roleUrl);
        if ($ch === false) {
            return new Response(false, 'failed to initialize role assignment');
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bot ' . $botToken,
            'Content-Length: 0',
        ]);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        self::applyCaBundle($ch);
        $result = curl_exec($ch);
        if ($result === false) {
            error_log('assignVerifiedRole curl_exec failed: ' . curl_error($ch));
            curl_close($ch);
            return new Response(false, 'failed to assign verified role');
        }
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($status < 200 || $status >= 300) {
            $error = $result;
            $decoded = json_decode($result, true);
            if (is_array($decoded) && isset($decoded['code'])) {
                $error = self::discordErrorMessage((int)$decoded['code'])
                    . ' (' . $decoded['code'] . ')';
            }
            error_log('assignVerifiedRole HTTP status ' . $status . ' error: ' . $error . ' response: ' . $result);
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
        $ch = curl_init($roleUrl);
        if ($ch === false) {
            return;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bot ' . $botToken,
            'Content-Length: 0',
        ]);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
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
        $denyPayload = json_encode([
            'type'  => 0,
            'allow' => '0',
            'deny'  => (string)$sendMessages,
        ]);
        if ($denyPayload !== false) {
            $ch = curl_init($everyoneUrl);
            if ($ch !== false) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bot ' . $botToken,
                    'Content-Type: application/json',
                ]);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $denyPayload);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_exec($ch);
                curl_close($ch);
            }
        }

        // Allow send messages for verified role
        $roleUrl = 'https://discord.com/api/channels/' . urlencode($channelId)
            . '/permissions/' . urlencode($roleId);
        $allowPayload = json_encode([
            'type'  => 0,
            'allow' => (string)$sendMessages,
            'deny'  => '0',
        ]);
        if ($allowPayload !== false) {
            $ch = curl_init($roleUrl);
            if ($ch !== false) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bot ' . $botToken,
                    'Content-Type: application/json',
                ]);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $allowPayload);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_exec($ch);
                curl_close($ch);
            }
        }
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
        $redirectUri = ServiceCredentials::get_discord_redirect_uri();
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
            return new Response(false, 'Invalid state token', null);
        }

        $clientId     = ServiceCredentials::get_discord_oauth_client_id();
        $clientSecret = ServiceCredentials::get_discord_oauth_client_secret();
        $redirectUri  = ServiceCredentials::get_discord_redirect_uri();
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
            $checkCh = curl_init($memberUrl);
            if ($checkCh !== false) {
                curl_setopt($checkCh, CURLOPT_HTTPHEADER, [
                    'Authorization: Bot ' . $botToken,
                ]);
                curl_setopt($checkCh, CURLOPT_RETURNTRANSFER, true);
                self::applyCaBundle($checkCh);
                $checkResp = curl_exec($checkCh);
                if ($checkResp === false) {
                    $guildJoinError = 'cURL error during guild membership check: ' . curl_error($checkCh);
                } else {
                    $checkStatus = curl_getinfo($checkCh, CURLINFO_HTTP_CODE);
                    if ($checkStatus === 404) {
                        $joinPayload = json_encode(['access_token' => $accessToken]);
                        if ($joinPayload !== false) {
                            $joinCh = curl_init($memberUrl);
                            if ($joinCh !== false) {
                                curl_setopt($joinCh, CURLOPT_HTTPHEADER, [
                                    'Authorization: Bot ' . $botToken,
                                    'Content-Type: application/json',
                                ]);
                                curl_setopt($joinCh, CURLOPT_CUSTOMREQUEST, 'PUT');
                                curl_setopt($joinCh, CURLOPT_POSTFIELDS, $joinPayload);
                                curl_setopt($joinCh, CURLOPT_RETURNTRANSFER, true);
                                self::applyCaBundle($joinCh);
                                $joinResp = curl_exec($joinCh);
                                $joinStatus = curl_getinfo($joinCh, CURLINFO_HTTP_CODE);
                                if ($joinResp === false) {
                                    $guildJoinError = 'cURL error during guild join: ' . curl_error($joinCh);
                                    error_log('Failed to join guild: ' . curl_error($joinCh));
                                } elseif ($joinStatus >= 400) {
                                    $guildJoinError = 'Discord API returned status ' . $joinStatus . ' when joining guild';
                                    error_log('Failed to join guild: status ' . $joinStatus . ' response: ' . $joinResp);
                                }
                                curl_close($joinCh);
                            } else {
                                $guildJoinError = 'Failed to initialize guild join request';
                            }
                        } else {
                            $guildJoinError = 'Failed to encode guild join payload';
                        }
                    } elseif ($checkStatus >= 400 && $checkStatus !== 200 && $checkStatus !== 204) {
                        $guildJoinError = 'Discord API returned status ' . $checkStatus . ' when checking guild membership';
                    }
                }
                curl_close($checkCh);
            } else {
                $guildJoinError = 'Failed to initialize guild membership check';
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
