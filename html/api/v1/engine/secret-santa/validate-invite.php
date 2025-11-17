<?php
require(__DIR__ . "/../../engine/engine.php");
require_once(\Kickback\SCRIPT_ROOT . "/Kickback/Backend/Controllers/SecretSantaController.php");

use Kickback\Backend\Controllers\SecretSantaController;
use Kickback\Backend\Models\Response;

OnlyGET();

if (!isset($_GET['invite_token'])) {
    return new Response(false, 'Missing invite token.', null);
}

$inviteToken = Validate($_GET['invite_token']);

return SecretSantaController::validateInvite($inviteToken);
?>
