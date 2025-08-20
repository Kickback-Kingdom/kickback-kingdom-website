<?php
require(__DIR__.'/../../engine/engine.php');

use Kickback\Backend\Controllers\DiscordController;
use Kickback\Backend\Models\Response;

OnlyGET();

$discordId = $_GET['id'] ?? null;
if (!$discordId) {
    return new Response(false, 'Missing Discord user ID', null);
}

return DiscordController::isLinked($discordId);
?>
