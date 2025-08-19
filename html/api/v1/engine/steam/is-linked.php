<?php
require(__DIR__.'/../../engine/engine.php');

use Kickback\Backend\Controllers\SocialMediaController;
use Kickback\Backend\Models\Response;

OnlyGET();

$steamId = $_GET['id'] ?? null;
if (!$steamId) {
    return new Response(false, 'Missing Steam user ID', null);
}

return SocialMediaController::isSteamLinked($steamId);
?>
