<?php
require(__DIR__.'/../../engine/engine.php');

use Kickback\Backend\Controllers\SteamController;
use Kickback\Common\Version;

OnlyGET();

$resp = SteamController::completeLink($_GET);

if ($resp->success) {
    header('Location: '.Version::urlBetaPrefix().'/account-settings.php');
    exit;
}

$msg = urlencode($resp->message);
header('Location: '.Version::urlBetaPrefix().'/account-settings.php?steam_error=' . $msg);
exit;
?>
