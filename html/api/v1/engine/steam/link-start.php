<?php
require(__DIR__.'/../../engine/engine.php');

use Kickback\Backend\Controllers\SocialMediaController;

OnlyGET();

return SocialMediaController::startSteamLink();
?>
