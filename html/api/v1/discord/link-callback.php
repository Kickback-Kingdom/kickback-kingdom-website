<?php
require(__DIR__.'/../engine/engine.php');

use Kickback\Backend\Config\ServiceCredentials;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Controllers\SocialMediaController;
use Kickback\Services\Session;

Session::ensureSessionStarted();

if (!isset($_GET['state']) || !isset($_SESSION['discord_oauth_state']) || $_GET['state'] !== $_SESSION['discord_oauth_state']) {
    (new Response(false, 'Invalid state token', null))->Exit();
}

if (!isset($_GET['code'])) {
    (new Response(false, 'Missing code', null))->Exit();
}

$code = $_GET['code'];
$clientId = ServiceCredentials::get_discord_oauth_client_id();
$clientSecret = ServiceCredentials::get_discord_oauth_client_secret();
$redirectUri = ServiceCredentials::get_discord_redirect_uri();

if (!$clientId || !$clientSecret || !$redirectUri) {
    (new Response(false, 'Discord OAuth not configured', null))->Exit();
}

$tokenCh = curl_init('https://discord.com/api/oauth2/token');
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
    (new Response(false, 'Failed to contact Discord', null))->Exit();
}
$tokenData = json_decode($tokenResponse, true);
curl_close($tokenCh);

if (!isset($tokenData['access_token'])) {
    (new Response(false, 'Failed to retrieve access token', $tokenData))->Exit();
}

$accessToken = $tokenData['access_token'];
$userCh = curl_init('https://discord.com/api/users/@me');
curl_setopt($userCh, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $accessToken,
    'Content-Type: application/json',
]);
curl_setopt($userCh, CURLOPT_RETURNTRANSFER, true);
$userResponse = curl_exec($userCh);
if ($userResponse === false) {
    curl_close($userCh);
    (new Response(false, 'Failed to fetch user profile', null))->Exit();
}
$userData = json_decode($userResponse, true);
curl_close($userCh);

if (!isset($userData['id'], $userData['username'])) {
    (new Response(false, 'Invalid user data from Discord', $userData))->Exit();
}

$account = Session::getCurrentAccount();
if (is_null($account)) {
    (new Response(false, 'User not logged in', null))->Exit();
}

$discordId = $userData['id'];
$discordUsername = $userData['username'];

$stmt = $GLOBALS['conn']->prepare('UPDATE account SET DiscordUserId = ?, DiscordUsername = ? WHERE Email = ?');
if ($stmt) {
    $stmt->bind_param('sss', $discordId, $discordUsername, $account->email);
    $stmt->execute();
    $stmt->close();
}

SocialMediaController::assignVerifiedRole($discordId);

unset($_SESSION['discord_oauth_state']);

(new Response(true, 'Discord account linked', null))->Return();
?>
