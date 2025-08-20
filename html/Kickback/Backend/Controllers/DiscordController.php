<?php
declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use Kickback\Backend\Config\ServiceCredentials;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vAccount;
use Kickback\Common\Version;
use Kickback\Services\Database;
use Kickback\Services\Session;

class DiscordController
{
    use CurlHelper;

    /**
     * Perform a Discord API request with common cURL configuration.
     *
     * @param string               $method  HTTP method to use.
     * @param string               $url     Request URL.
     * @param array<string,mixed>|string|null $payload Optional payload to send. Arrays
     *                                                will be JSON-encoded while strings
     *                                                are sent as-is.
     * @param array<int,string>    $headers Additional headers.
     *
     * @return array{status:int, body:string|false, error:?string}
     */
    private static function apiRequest(string $method, string $url, array|string|null $payload = null, array $headers = []) : array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return ['status' => 0, 'body' => false, 'error' => 'failed to init'];
        }

        if ($payload !== null) {
            if (is_array($payload)) {
                $json = json_encode($payload);
                if ($json === false) {
                    curl_close($ch);
                    return ['status' => 0, 'body' => false, 'error' => 'json_encode failed'];
                }
                curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
                $headers[] = 'Content-Type: application/json';
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            }
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

    public static function sendWebhook(mixed $msg) : bool
    {
        $apiUrl = ServiceCredentials::get_discord_api_url();
        $apiKey = ServiceCredentials::get_discord_api_key();
        if (!$apiUrl || !$apiKey) {
            error_log('Discord webhook not configured');
            return false;
        }

        // Ex: $webhookURL = "https://discord.com/api/webhooks/<some_number>/<api_key>"
        $webhookURL = $apiUrl . '/' . $apiKey;

        $result = self::apiRequest('POST', $webhookURL, ['content' => $msg]);
        if ($result['body'] === false) {
            error_log('Discord webhook error: ' . ($result['error'] ?? 'unknown'));
            return false;
        }

        return true;
    }

    private static function sendChannelMessage(string $channelId, string $message) : void
    {
        $botToken = ServiceCredentials::get_discord_bot_token();
        if (!$botToken) {
            return;
        }
        $url = 'https://discord.com/api/channels/' . urlencode($channelId) . '/messages';
        self::apiRequest('POST', $url, ['content' => $message], [
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
        $memberResp = self::apiRequest('GET', $memberUrl, null, [
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
        $botResp = self::apiRequest('GET', $botMemberUrl, null, [
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
        $rolesResp = self::apiRequest('GET', $rolesUrl, null, [
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
        $result = self::apiRequest('PUT', $roleUrl, null, [
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
                $error = self::errorMessage((int)$decoded['code'])
                    . ' (' . $decoded['code'] . ')';
            }
            error_log('assignVerifiedRole HTTP status ' . $status . ' error: ' . $error . ' response: ' . $result['body']);
            return new Response(false, 'Discord API error: ' . $error);
        }
        return new Response(true, 'verified role assigned');
    }

    private static function errorMessage(int $code) : string
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
        self::apiRequest('DELETE', $roleUrl, null, [
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
        self::apiRequest('PUT', $everyoneUrl, [
            'type'  => 0,
            'allow' => '0',
            'deny'  => (string)$sendMessages,
        ], [
            'Authorization: Bot ' . $botToken,
        ]);

        // Allow send messages for verified role
        $roleUrl = 'https://discord.com/api/channels/' . urlencode($channelId)
            . '/permissions/' . urlencode($roleId);
        self::apiRequest('PUT', $roleUrl, [
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
    private static function redirectUri() : ?string
    {
        $base = ServiceCredentials::get_discord_redirect_uri();
        if (!$base) {
            $host = $_SERVER['HTTP_HOST'] ?? null;
            if (!$host) {
                return null;
            }

            $scheme = 'http';
            $forwarded = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
            $forwarded = explode(',', (string)$forwarded)[0];
            if (
                (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || strcasecmp($forwarded, 'https') === 0
            ) {
                $scheme = 'https';
            }

            $base = $scheme . '://' . $host;
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
    public static function startLink() : Response
    {
        Session::ensureSessionStarted();
        if (!Session::isLoggedIn()) {
            return new Response(false, 'User not logged in', null);
        }

        $clientId    = ServiceCredentials::get_discord_oauth_client_id();
        $redirectUri = self::redirectUri();
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
     * Exchange an OAuth code for an access token.
     */
    private static function fetchAccessToken(string $code) : Response
    {
        $clientId     = ServiceCredentials::get_discord_oauth_client_id();
        $clientSecret = ServiceCredentials::get_discord_oauth_client_secret();
        $redirectUri  = self::redirectUri();
        if (!$clientId || !$clientSecret || !$redirectUri) {
            return new Response(false, 'Discord OAuth not configured', null);
        }

        $payload = http_build_query([
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $redirectUri,
        ]);

        $result = self::apiRequest(
            'POST',
            'https://discord.com/api/oauth2/token',
            $payload,
            ['Content-Type: application/x-www-form-urlencoded']
        );
        if ($result['body'] === false) {
            return new Response(false, 'Failed to contact Discord', null);
        }
        $data = json_decode($result['body'], true);
        if (!isset($data['access_token'])) {
            return new Response(false, 'Failed to retrieve access token', $data);
        }

        return new Response(true, 'access token', ['access_token' => $data['access_token']]);
    }

    /**
     * Fetch the Discord user profile for the given access token.
     */
    private static function fetchUser(string $accessToken) : Response
    {
        $result = self::apiRequest('GET', 'https://discord.com/api/users/@me', null, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ]);
        if ($result['body'] === false) {
            return new Response(false, 'Failed to fetch user profile', null);
        }
        $data = json_decode($result['body'], true);
        if (!isset($data['id'], $data['username'])) {
            return new Response(false, 'Invalid user data from Discord', $data);
        }
        return new Response(true, 'user profile', $data);
    }

    /**
     * Join the configured guild, if possible.
     */
    private static function joinGuild(string $discordId, string $accessToken) : Response
    {
        $guildId  = ServiceCredentials::get_discord_guild_id();
        $botToken = ServiceCredentials::get_discord_bot_token();
        if (!$guildId || !$botToken) {
            return new Response(true, 'guild join skipped', null);
        }

        $memberUrl = 'https://discord.com/api/guilds/' . urlencode($guildId)
            . '/members/' . urlencode($discordId);
        $checkResp = self::apiRequest('GET', $memberUrl, null, [
            'Authorization: Bot ' . $botToken,
        ]);
        if ($checkResp['body'] === false) {
            return new Response(false, 'cURL error during guild membership check: ' . ($checkResp['error'] ?? 'unknown'));
        }

        $status = $checkResp['status'];
        if ($status === 404) {
            $joinResp = self::apiRequest('PUT', $memberUrl, ['access_token' => $accessToken], [
                'Authorization: Bot ' . $botToken,
            ]);
            if ($joinResp['body'] === false) {
                error_log('Failed to join guild: ' . ($joinResp['error'] ?? 'unknown'));
                return new Response(false, 'cURL error during guild join: ' . ($joinResp['error'] ?? 'unknown'));
            }
            if ($joinResp['status'] >= 400) {
                error_log('Failed to join guild: status ' . $joinResp['status'] . ' response: ' . $joinResp['body']);
                return new Response(false, 'Discord API returned status ' . $joinResp['status'] . ' when joining guild');
            }
        } elseif ($status >= 400 && $status !== 200 && $status !== 204) {
            return new Response(false, 'Discord API returned status ' . $status . ' when checking guild membership');
        }

        return new Response(true, 'guild joined', null);
    }

    /**
     * Update the current account with Discord details and assign verified role.
     */
    private static function updateAccount(vAccount $account, string $discordId, string $discordUsername) : Response
    {
        $conn = Database::getConnection();
        $stmt = $conn->prepare('UPDATE account SET DiscordUserId = ?, DiscordUsername = ? WHERE Email = ?');
        if (!$stmt) {
            return new Response(false, 'Failed to prepare statement', null);
        }
        $stmt->bind_param('sss', $discordId, $discordUsername, $account->email);
        $stmt->execute();
        $stmt->close();

        $account->discordUserId  = $discordId;
        $account->discordUsername = $discordUsername;
        Session::setSessionData('vAccount', $account);

        $roleResp = self::assignVerifiedRole($discordId);
        if (!$roleResp->success) {
            return new Response(false, $roleResp->message, null);
        }

        return new Response(true, 'account updated', null);
    }

    /**
     * Notify the configured channel about a new link
     */
    private static function notifyLink(string $discordUserId, ?string $discordUsername = null) : void
    {
        $channelId = ServiceCredentials::get_discord_link_channel_id();
        if ($channelId) {
            $mention = "<@{$discordUserId}>";
            $message = FlavorTextController::getDiscordLinkFlavorText($mention);
            //self::sendChannelMessage($channelId, $message);
            self::sendWebhook($message);
        }
    }
    /**
     * Notify the configured channel about a unlink
     */
    private static function notifyUnlink(string $discordUserId, ?string $discordUsername = null) : void
    {
        $channelId = ServiceCredentials::get_discord_link_channel_id();
        if ($channelId) {
            $mention = "<@{$discordUserId}>";
            $message = FlavorTextController::getDiscordUnlinkFlavorText($mention);
            //self::sendChannelMessage($channelId, $message);
            self::sendWebhook($message);
        }
    }
    /**
     * Complete the Discord OAuth linking process.
     */
    public static function completeLink(string $code, string $state) : Response
    {
        Session::ensureSessionStarted();
        if (!Session::readCurrentAccountInto($account)) {
            return new Response(false, 'User not logged in', null);
        }

        $expectedState = Session::sessionDataString('discord_oauth_state');
        if (!$expectedState || $expectedState !== $state) {
            $sessionId = Session::getCurrentSessionId();
            $username  = $account->username ?? 'unknown';
            $msg = 'completeDiscordLink invalid state token: session='
                . ($sessionId ?? 'none')
                . ' user=' . $username
                . ' expected=' . ($expectedState ?? 'none')
                . ' received=' . $state;
            error_log($msg);
            Session::removeSessionData('discord_oauth_state');
            return new Response(false, 'Invalid state token; please restart the Discord link process.', null);
        }

        // Consume the state token to prevent re-use before any network operations.
        Session::removeSessionData('discord_oauth_state');

        $tokenResp = self::fetchAccessToken($code);
        if (!$tokenResp->success) {
            return $tokenResp;
        }
        $accessToken = $tokenResp->data['access_token'];

        $userResp = self::fetchUser($accessToken);
        if (!$userResp->success) {
            return $userResp;
        }
        $discordId       = $userResp->data['id'];
        $discordUsername = $userResp->data['username'];

        $guildResp  = self::joinGuild($discordId, $accessToken);
        $updateResp = self::updateAccount($account, $discordId, $discordUsername);

        $errors = [];
        if (!$guildResp->success && $guildResp->message !== '') {
            $errors[] = $guildResp->message;
        }
        if (!$updateResp->success) {
            $errors[] = $updateResp->message;
        }
        if ($errors) {
            $msg = 'Discord account linked, but ' . implode(' and ', $errors);
            return new Response(false, $msg, null);
        }

        // Defer notification until after the response is sent.
        $discordUserId = $account->discordUserId;
        $discordUsername = $account->discordUsername;
        register_shutdown_function(fn() => self::notifyLink($discordUserId, $discordUsername));

        return new Response(true, 'Discord account linked', null);
    }

    /**
     * Check if a Discord account is already linked.
     */
    public static function isLinked(string $discordId) : Response
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
    public static function unlinkAccount(vAccount $account) : Response
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

        
        // Defer notification until after the response is sent.
        $discordUserId = $account->discordUserId;
        $discordUsername = $account->discordUsername;
        register_shutdown_function(fn() => self::notifyUnlink($discordUserId, $discordUsername));

        $account->discordUserId = null;
        $account->discordUsername = null;
        Session::setSessionData('vAccount', $account);

        return new Response(true, 'Discord account unlinked', null);
    }
}
