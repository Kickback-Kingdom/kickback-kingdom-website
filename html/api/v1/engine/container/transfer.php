<?php
require(__DIR__ . "/../../engine/engine.php");

OnlyPOST();

use Kickback\Backend\Controllers\LootController;
use Kickback\Backend\Controllers\AccountController;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Models\Response;


$containsFieldsResp = POSTContainsFields("sessionToken", "itemLootId", "toContainerLootId");
if (!$containsFieldsResp->success)
    return $containsFieldsResp;


$kk_service_key = \Kickback\Backend\Config\ServiceCredentials::get("kk_service_key");

$sessionToken = Validate($_POST["sessionToken"]);
$itemLootId = Validate($_POST["itemLootId"]);
$toContainerLootId = Validate($_POST["toContainerLootId"]);


// Validate session token and account
$loginResp = AccountController::getAccountBySession($kk_service_key, $sessionToken);
if (!$loginResp->success) {
    return $loginResp;
}

// Fetch container contents
return LootController::transferLootIntoContainer(new vRecordId('', $itemLootId), new vRecordId('', $toContainerLootId));
?>