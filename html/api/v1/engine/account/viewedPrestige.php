<?php

require_once(($_SERVER["DOCUMENT_ROOT"] ?: (__DIR__ . "/../../../..")) . "/Kickback/init.php");
require_once(\Kickback\SCRIPT_ROOT . "/api/v1/engine/engine.php");

use Kickback\Backend\Controllers\AccountController;
use Kickback\Backend\Controllers\PrestigeController;
use Kickback\Backend\Views\vRecordId;

OnlyPOST();

// Validate input fields
$containsFieldsResp = POSTContainsFields("prestigeId", "accountId", "sessionToken");
if (!$containsFieldsResp->success)
    return $containsFieldsResp;

$kk_service_key = \Kickback\Backend\Config\ServiceCredentials::get("kk_service_key");

$prestigeId = Validate($_POST["prestigeId"]);
$accountId = Validate($_POST["accountId"]);
$sessionToken = Validate($_POST["sessionToken"]);

$prestigeId = new vRecordId('', $prestigeId);
$accountId = new vRecordId('', $accountId);

// Verify session
$loginResp = AccountController::getAccountBySession($kk_service_key, $sessionToken);
if (!$loginResp->success) {
    return $loginResp;
}


// Call the function to mark as viewed
return PrestigeController::markPrestigeAsViewed($prestigeId, $accountId);
?>
