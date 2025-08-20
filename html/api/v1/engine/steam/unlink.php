<?php
require(__DIR__.'/../../engine/engine.php');

use Kickback\Backend\Controllers\SteamController;
use Kickback\Services\Session;

OnlyPOST();

$account = Session::requireSteamLinked();
return SteamController::unlink($account);
?>
