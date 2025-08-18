<?php
require(__DIR__.'/../engine/engine.php');

use Kickback\Backend\Config\ServiceCredentials;
use Kickback\Services\Session;

Session::ensureSessionStarted();

$clientId = ServiceCredentials::get_discord_oauth_client_id();
$redirectUri = ServiceCredentials::get_discord_redirect_uri();

if (!$clientId || !$redirectUri) {
    exit('Discord OAuth not configured.');
}

$state = bin2hex(random_bytes(16));
$_SESSION['discord_oauth_state'] = $state;
$scope = urlencode('identify');

$authUrl = 'https://discord.com/api/oauth2/authorize'
    . '?client_id=' . urlencode($clientId)
    . '&redirect_uri=' . urlencode($redirectUri)
    . '&response_type=code'
    . '&scope=' . $scope
    . '&state=' . urlencode($state);

header('Location: ' . $authUrl);
exit();
?>
