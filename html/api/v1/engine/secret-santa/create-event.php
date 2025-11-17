<?php
require(__DIR__ . "/../../engine/engine.php");
require_once(\Kickback\SCRIPT_ROOT . "/Kickback/Backend/Controllers/SecretSantaController.php");

use Kickback\Backend\Controllers\SecretSantaController;

OnlyPOST();

$containsResp = POSTContainsFields('name', 'signup_deadline', 'gift_deadline');
if (!$containsResp->success) {
    return $containsResp;
}

$name = Validate($_POST['name']);
$description = isset($_POST['description']) ? Validate($_POST['description']) : null;
$signupDeadline = Validate($_POST['signup_deadline']);
$giftDeadline = Validate($_POST['gift_deadline']);

return SecretSantaController::createEvent($name, $description, $signupDeadline, $giftDeadline);
?>
