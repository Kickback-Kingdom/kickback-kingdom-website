<?php
require_once(__DIR__ . "/../../engine/engine.php");

use Kickback\Backend\Controllers\AccountController;
use Kickback\Backend\Controllers\QuestController;
use Kickback\Backend\Config\ServiceCredentials;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Models\Response;

OnlyPOST();

$containsFieldsResp = POSTContainsFields("questId", "sessionToken");
if (!$containsFieldsResp->success) {
    return $containsFieldsResp;
}

$questId = Validate($_POST["questId"]);
if (is_string($questId) && !ctype_digit($questId)) {
    return new Response(false, "'questId' parameter must be integer, but was not.", null);
}
$sessionToken = Validate($_POST["sessionToken"]);

$kk_service_key = ServiceCredentials::get("kk_service_key");
$loginResp = AccountController::getAccountBySession($kk_service_key, $sessionToken);
if (!$loginResp->success) {
    return $loginResp;
}
$account = $loginResp->data;

$questResp = QuestController::queryQuestByIdAsResponse(new vRecordId('', intval($questId)));
if (!$questResp->success) {
    return $questResp;
}
$quest = $questResp->data;

if ($quest->host1->crand !== $account->crand && (!isset($quest->host2) || $quest->host2->crand !== $account->crand)) {
    return new Response(false, "Only the quest owner can view reviews.", null);
}

return QuestController::queryQuestReviewDetailsAsResponse($quest);
?>
