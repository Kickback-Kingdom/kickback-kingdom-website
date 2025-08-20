<?php
require(__DIR__.'/../../engine/engine.php');

use Kickback\Backend\Controllers\DiscordController;
use Kickback\Services\Session;

OnlyPOST();

$account = Session::requireDiscordLinked();
return DiscordController::unlinkAccount($account);
?>
