<?php
require(__DIR__ . "/../../engine/engine.php");
require_once(\Kickback\SCRIPT_ROOT . "/Kickback/Backend/Controllers/SecretSantaController.php");

use Kickback\Backend\Controllers\SecretSantaController;

OnlyPOST();

$containsResp = POSTContainsFields('event_ctime', 'event_crand', 'name');
if (!$containsResp->success) {
    return $containsResp;
}

$eventCtime = ValidateCTime($_POST['event_ctime']);
$eventCrand = intval($_POST['event_crand']);
$groupName = Validate($_POST['name']);
$existingGroupCtime = isset($_POST['exclusion_group_ctime']) ? ValidateCTime($_POST['exclusion_group_ctime']) : null;
$existingGroupCrand = isset($_POST['exclusion_group_crand']) ? intval($_POST['exclusion_group_crand']) : null;

return SecretSantaController::upsertExclusionGroup($eventCtime, $eventCrand, $groupName, $existingGroupCtime, $existingGroupCrand);
?>
