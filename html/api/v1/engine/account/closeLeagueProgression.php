<?php

require_once(($_SERVER["DOCUMENT_ROOT"] ?: (__DIR__ . "/../../../..")) . "/Kickback/init.php");

require_once(\Kickback\SCRIPT_ROOT . "/api/v1/engine/engine.php");

use Kickback\Backend\Controllers\AccountController;
use Kickback\Backend\Views\vRecordId;

OnlyPOST();

$containsFieldsResp = POSTContainsFields("accountId", "gameId", "sessionToken");
if (!$containsFieldsResp->success)
    return $containsFieldsResp;

$kk_service_key = \Kickback\Backend\Config\ServiceCredentials::get("kk_service_key");

$accountId = Validate($_POST["accountId"]);
$gameId = Validate($_POST["gameId"]);
$sessionToken = Validate($_POST["sessionToken"]);

// Convert IDs into vRecordId objects
$accountId = new vRecordId('', $accountId);
$gameId = new vRecordId('', $gameId);

// Validate session token and account
$loginResp = AccountController::getAccountBySession($kk_service_key, $sessionToken);
if (!$loginResp->success) {
    return $loginResp;
}

// Perform the ELO update
$response = AccountController::updateLastEloSeenForGame($accountId, $gameId);
return $response;

?>
