<?php
require_once(__DIR__ . "/../../engine/engine.php");

use Kickback\Backend\Controllers\AccountController;
use Kickback\Backend\Controllers\QuestController;
use Kickback\Backend\Config\ServiceCredentials;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Models\Response;

OnlyPOST();

$contains = POSTContainsFields("sessionToken");
if (!$contains->success) {
    return $contains;
}

$sessionToken = Validate($_POST["sessionToken"]);

$kk_service_key = ServiceCredentials::get("kk_service_key");
$loginResp = AccountController::getAccountBySession($kk_service_key, $sessionToken);
if (!$loginResp->success) {
    return $loginResp;
}
$account = $loginResp->data;

return QuestController::getParticipationByDate(new vRecordId('', $account->crand));
?>
