<?php
require(__DIR__ . "/../../engine/engine.php");
require_once(\Kickback\SCRIPT_ROOT . "/Kickback/Backend/Controllers/SecretSantaController.php");

use Kickback\Backend\Controllers\SecretSantaController;

OnlyPOST();

$containsResp = POSTContainsFields('event_ctime', 'event_crand');
if (!$containsResp->success) {
    return $containsResp;
}

$eventCtime = ValidateCTime($_POST['event_ctime']);
$eventCrand = intval($_POST['event_crand']);

return SecretSantaController::emailAssignments($eventCtime, $eventCrand);
?>
