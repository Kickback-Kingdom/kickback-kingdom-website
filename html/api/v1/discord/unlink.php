<?php
require(__DIR__.'/../engine/engine.php');

use Kickback\Backend\Models\Response;
use Kickback\Backend\Controllers\SocialMediaController;
use Kickback\Services\Session;

Session::ensureSessionStarted();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    (new Response(false, 'Invalid request method', null))->Exit();
}

$account = Session::requireDiscordLinked();

// Remove role if possible
SocialMediaController::removeVerifiedRole($account->discordUserId);

$stmt = $GLOBALS['conn']->prepare('UPDATE account SET DiscordUserId = NULL, DiscordUsername = NULL WHERE Email = ?');
if ($stmt) {
    $stmt->bind_param('s', $account->email);
    $stmt->execute();
    $stmt->close();
}

(new Response(true, 'Discord account unlinked', null))->Return();
?>
