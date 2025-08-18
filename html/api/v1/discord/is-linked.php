<?php
require(__DIR__.'/../engine/engine.php');

use Kickback\Backend\Models\Response;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    (new Response(false, 'Invalid request method', null))->Exit();
}

$discordId = $_GET['id'] ?? null;
if (!$discordId) {
    (new Response(false, 'Missing Discord user ID', null))->Exit();
}

$stmt = $GLOBALS['conn']->prepare('SELECT 1 FROM account WHERE DiscordUserId = ? LIMIT 1');
if (!$stmt) {
    (new Response(false, 'Failed to prepare statement', null))->Exit();
}

$stmt->bind_param('s', $discordId);
$stmt->execute();
$result = $stmt->get_result();
$linked = $result && $result->num_rows > 0;
$stmt->close();

(new Response(true, 'Discord link status', ['linked' => $linked]))->Return();
?>
