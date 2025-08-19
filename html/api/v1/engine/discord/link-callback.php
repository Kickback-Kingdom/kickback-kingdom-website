<?php
require(__DIR__.'/../../engine/engine.php');

use Kickback\Backend\Controllers\SocialMediaController;
use Kickback\Backend\Models\Response;
use Kickback\Common\Version;

OnlyGET();

$code  = $_GET['code']  ?? null;
$state = $_GET['state'] ?? null;

if (!$code || !$state) {
    $resp = new Response(false, 'Missing code or state', null);
} else {
    $resp = SocialMediaController::completeDiscordLink($code, $state);
}

if ($resp->success) {
    header('Location: '.Version::urlBetaPrefix().'/account-settings.php');
    exit;
}

$msg = urlencode($resp->message);
header('Location: '.Version::urlBetaPrefix().'/account-settings.php?discord_error=' . $msg);
exit;
?>
