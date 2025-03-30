<?php

require_once(($_SERVER["DOCUMENT_ROOT"] ?: (__DIR__ . "/../../../..")) . "/Kickback/init.php");

require_once(\Kickback\SCRIPT_ROOT . "/api/v1/engine/engine.php");

use Kickback\Backend\Controllers\AccountController;
use Kickback\Backend\Controllers\LootController;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vLichCard;
use Kickback\Backend\Models\Response;

OnlyPOST();

$containsFieldsResp = POSTContainsFields("sessionToken", "lootId");
if (!$containsFieldsResp->success)
    return $containsFieldsResp;

//$kk_service_key = \Kickback\Backend\Config\ServiceCredentials::get("kk_service_key");

//$sessionToken = Validate($_POST["sessionToken"]);

$lootId = Validate($_POST["lootId"]);


// Validate session token and account
//$loginResp = AccountController::getAccountBySession($kk_service_key, $sessionToken);
//if (!$loginResp->success) {
//    return $loginResp;
//}



// Fetch container contents
return LootController::getLichCardLootByContainer(new vRecordId('', $lootId));
?>