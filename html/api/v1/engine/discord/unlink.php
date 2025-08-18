<?php
require(__DIR__.'/../../engine/engine.php');

use Kickback\Backend\Controllers\SocialMediaController;
use Kickback\Services\Session;

OnlyPOST();

$account = Session::requireDiscordLinked();
return SocialMediaController::unlinkDiscordAccount($account);
?>
