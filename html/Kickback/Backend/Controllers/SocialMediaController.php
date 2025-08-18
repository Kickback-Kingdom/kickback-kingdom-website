<?php
declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use Kickback\Backend\Config\ServiceCredentials;

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

    public static function assignVerifiedRole(string $discordUserId) : void
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
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
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
}
