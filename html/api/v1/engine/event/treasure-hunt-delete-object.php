<?php
require(__DIR__ . "/../../engine/engine.php");

OnlyPOST();

use Kickback\Backend\Controllers\AccountController;
use Kickback\Backend\Controllers\TreasureHuntController;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vTreasureHuntObject;
use Kickback\Backend\Models\Response;


$containsFieldsResp = POSTContainsFields("sessionToken", "item_crand", "item_ctime");
if (!$containsFieldsResp->success)
    return $containsFieldsResp;


$kk_service_key = \Kickback\Backend\Config\ServiceCredentials::get("kk_service_key");

$sessionToken = Validate($_POST["sessionToken"]);
$itemCrand = (int) Validate($_POST["item_crand"]);
$itemCtime = Validate($_POST["item_ctime"]);

// Validate session token and account
$loginResp = AccountController::getAccountBySession($kk_service_key, $sessionToken);
if (!$loginResp->success) {
    return $loginResp;
}

// Delete the treasure hunt object
$deleteResp = TreasureHuntController::deleteObject(new vRecordId($itemCtime, $itemCrand));
return $deleteResp;
?>