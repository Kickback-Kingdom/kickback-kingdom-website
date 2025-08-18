<?php
require(__DIR__.'/../engine/engine.php');

use Kickback\Backend\Config\ServiceCredentials;
use Kickback\Backend\Models\Response;
use Kickback\Services\Session;

Session::ensureSessionStarted();

$account = Session::getCurrentAccount();
if (is_null($account)) {
    (new Response(false, 'User not logged in', null))->Exit();
}

if (!$account->discordUserId) {
    (new Response(false, 'No Discord account linked', null))->Exit();
}

// Remove role if possible
$guildId = ServiceCredentials::get_discord_guild_id();
$botToken = ServiceCredentials::get_discord_bot_token();
$roleId = ServiceCredentials::get('discord_verified_role_id');

if ($guildId && $botToken && $roleId) {
    $roleUrl = 'https://discord.com/api/guilds/' . urlencode($guildId) . '/members/' . urlencode($account->discordUserId) . '/roles/' . urlencode($roleId);
    $ch = curl_init($roleUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bot ' . $botToken,
        'Content-Length: 0',
    ]);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}

$stmt = $GLOBALS['conn']->prepare('UPDATE account SET DiscordUserId = NULL, DiscordUsername = NULL WHERE Email = ?');
if ($stmt) {
    $stmt->bind_param('s', $account->email);
    $stmt->execute();
    $stmt->close();
}

(new Response(true, 'Discord account unlinked', null))->Return();
?>
