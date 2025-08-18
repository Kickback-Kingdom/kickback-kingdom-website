<?php
declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use Kickback\Backend\Config\ServiceCredentials;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vAccount;
use Kickback\Services\Database;
use Kickback\Services\Session;

class SocialMediaController
{

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
        curl_setopt($ch, CURLOPT_CAINFO, "/etc/pki/ca-trust/extracted/pem/tls-ca-bundle.pem");

        // Execute the cURL session
        $result = curl_exec($ch);
    
        // Check for errors
        if (0 < curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
    
        // Close the cURL session
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
        curl_setopt($memberCh, CURLOPT_CAINFO, '/etc/pki/ca-trust/extracted/pem/tls-ca-bundle.pem');
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
            curl_setopt($botCh, CURLOPT_CAINFO, '/etc/pki/ca-trust/extracted/pem/tls-ca-bundle.pem');
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
            curl_setopt($rolesCh, CURLOPT_CAINFO, '/etc/pki/ca-trust/extracted/pem/tls-ca-bundle.pem');
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
        curl_setopt($ch, CURLOPT_CAINFO, '/etc/pki/ca-trust/extracted/pem/tls-ca-bundle.pem');
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

        $guildJoinFailed = false;
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
                curl_setopt($checkCh, CURLOPT_CAINFO, '/etc/pki/ca-trust/extracted/pem/tls-ca-bundle.pem');
                curl_exec($checkCh);
                $checkStatus = curl_getinfo($checkCh, CURLINFO_HTTP_CODE);
                curl_close($checkCh);
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
                            curl_setopt($joinCh, CURLOPT_CAINFO, '/etc/pki/ca-trust/extracted/pem/tls-ca-bundle.pem');
                            $joinResp = curl_exec($joinCh);
                            if ($joinResp === false || curl_getinfo($joinCh, CURLINFO_HTTP_CODE) >= 400) {
                                error_log('Failed to join guild: ' . curl_error($joinCh) . ' response: ' . $joinResp);
                                $guildJoinFailed = true;
                            }
                            curl_close($joinCh);
                        } else {
                            $guildJoinFailed = true;
                        }
                    } else {
                        $guildJoinFailed = true;
                    }
                } elseif ($checkStatus >= 400 && $checkStatus !== 200 && $checkStatus !== 204) {
                    $guildJoinFailed = true;
                }
            } else {
                $guildJoinFailed = true;
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
        if ($guildJoinFailed) {
            $errors[] = 'failed to join Discord guild';
        }
        if (!$roleResponse->success) {
            $errors[] = $roleResponse->message;
        }
        if ($errors) {
            $msg = 'Discord account linked, but ' . implode(' and ', $errors);
            return new Response(false, $msg, null);
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

        $account->discordUserId = null;
        $account->discordUsername = null;
        Session::setSessionData('vAccount', $account);

        return new Response(true, 'Discord account unlinked', null);
    }
}
