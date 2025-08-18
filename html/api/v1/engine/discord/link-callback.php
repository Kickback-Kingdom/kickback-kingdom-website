<?php
require(__DIR__.'/../../engine/engine.php');

use Kickback\Backend\Controllers\SocialMediaController;
use Kickback\Backend\Models\Response;

OnlyGET();

$code = $_GET['code'] ?? null;
$state = $_GET['state'] ?? null;
if (!$code || !$state) {
    return new Response(false, 'Missing code or state', null);
}

return SocialMediaController::completeDiscordLink($code, $state);
?>
