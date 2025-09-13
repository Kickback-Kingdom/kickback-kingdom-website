<?php
require_once(__DIR__ . "/../../engine/engine.php");

use Kickback\Backend\Controllers\NotificationController;
use Kickback\Backend\Views\vQuest;
use Kickback\Backend\Models\Response;

OnlyPOST();

$containsFieldsResp = POSTContainsFields("questId");
if (!$containsFieldsResp->success) {
    return $containsFieldsResp;
}

$questId = Validate($_POST["questId"]);
if (is_string($questId) && !ctype_digit($questId)) {
    return new Response(false, "'questId' parameter must be integer, but was not.", null);
}

$quest = new vQuest('', intval($questId));

return NotificationController::queryQuestReviewDetailsAsResponse($quest);
?>
