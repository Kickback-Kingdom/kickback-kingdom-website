<?php
require(__DIR__.'/../../engine/engine.php');

use Kickback\Backend\Controllers\DiscordController;

OnlyGET();

return DiscordController::startLink();
?>
