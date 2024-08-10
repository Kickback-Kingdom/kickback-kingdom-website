<?php

require_once(($_SERVER["DOCUMENT_ROOT"] ?: (__DIR__ . "/../../../..")) . "/Kickback/init.php");

require_once(\Kickback\SCRIPT_ROOT . "/api/v1/engine/engine.php");

use Kickback\Controllers\AccountController;
use Kickback\Controllers\LootController;
use Kickback\Views\vRecordId;

OnlyPOST();

$containsFieldsResp = POSTContainsFields("chestId","accountId","sessionToken");
if (!$containsFieldsResp->success)
    return $containsFieldsResp;

$kk_service_key = \Kickback\Backend\Config\ServiceCredentials::get("kk_service_key");

$chestId = Validate($_POST["chestId"]);
$accountId = Validate($_POST["accountId"]);
$sessionToken = Validate($_POST["sessionToken"]);
$chestId = new vRecordId('', $chestId);
$accountId = new vRecordId('', $accountId);
$loginResp = AccountController::getAccountBySession($kk_service_key, $sessionToken);
if (!$loginResp->success)
{
    return $loginResp;
}
return LootController::closeChest($chestId, $accountId);
?>
