<?php
require(__DIR__.'/../../engine/engine.php');

use Kickback\Backend\Controllers\SteamController;

OnlyGET();

return SteamController::startLink();
?>
