<?php
require(__DIR__.'/../../engine/engine.php');

use Kickback\Backend\Controllers\SocialMediaController;

OnlyGET();

$resp = SocialMediaController::completeSteamLink($_GET);

if ($resp->success) {
    header('Location: /account-settings.php');
    exit;
}

$msg = urlencode($resp->message);
header('Location: /account-settings.php?steam_error=' . $msg);
exit;
?>
