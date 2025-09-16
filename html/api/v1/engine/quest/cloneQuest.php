<?php
require_once(__DIR__ . '/../../engine/engine.php');

use Kickback\Backend\Config\ServiceCredentials;
use Kickback\Backend\Controllers\AccountController;
use Kickback\Backend\Controllers\QuestController;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vRecordId;

OnlyPOST();

$contains = POSTContainsFields('sessionToken', 'questId');
if (!$contains->success) {
    return $contains;
}

$sessionToken = Validate($_POST['sessionToken']);
$questIdRaw = $_POST['questId'];

if (!is_numeric($questIdRaw)) {
    return new Response(false, 'Invalid quest selected.', null);
}

$questId = (int)$questIdRaw;
if ($questId <= 0) {
    return new Response(false, 'Invalid quest selected.', null);
}

$kk_service_key = ServiceCredentials::get('kk_service_key');
$loginResp = AccountController::getAccountBySession($kk_service_key, $sessionToken);
if (!$loginResp->success) {
    return $loginResp;
}

return QuestController::cloneQuest(new vRecordId('', $questId));
?>
