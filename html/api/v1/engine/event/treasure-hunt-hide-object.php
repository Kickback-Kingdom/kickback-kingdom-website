<?php
require(__DIR__ . "/../../engine/engine.php");

OnlyPOST();

use Kickback\Backend\Controllers\AccountController;
use Kickback\Backend\Controllers\TreasureHuntController;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vTreasureHuntObject;
use Kickback\Backend\Models\Response;


$containsFieldsResp = POSTContainsFields("sessionToken", "hunt_locator", "item_crand", "media_id", "one_time_only", "page_url", "x_percentage", "y_percentage");
if (!$containsFieldsResp->success)
    return $containsFieldsResp;


$kk_service_key = \Kickback\Backend\Config\ServiceCredentials::get("kk_service_key");

$sessionToken = Validate($_POST["sessionToken"]);
$huntLocator = Validate($_POST["hunt_locator"]);
$itemCrand = (int) Validate($_POST["item_crand"]);
$mediaCrand = (int) Validate($_POST["media_id"]);
$one_time_only = isset($_POST["one_time_only"]) && $_POST["one_time_only"] === "true";
$oneTimeInt = $one_time_only ? 1 : 0;
$pageUrl = Validate($_POST["page_url"]);
$xPercent = (float) Validate($_POST["x_percentage"]);
$yPercent = (float) Validate($_POST["y_percentage"]);

if ($itemCrand <= 0 || $mediaCrand <= 0 || $xPercent < 0 || $xPercent > 100 || $yPercent < 0 || $yPercent > 100) {
    return new Response(false, "Invalid input values.");
}


// Validate session token and account
$loginResp = AccountController::getAccountBySession($kk_service_key, $sessionToken);
if (!$loginResp->success) {
    return $loginResp;
}


$eventResp = TreasureHuntController::getEventByLocator($huntLocator);
if (!$eventResp->success) return $eventResp;

$event = $eventResp->data;

$hideResp = TreasureHuntController::hideTreasureObject(
    new vRecordId($event->ctime, $event->crand),
    new vRecordId('', $itemCrand),
    $pageUrl,
    $xPercent,
    $yPercent,
    new vRecordId('', $mediaCrand),
    $oneTimeInt
);

return $hideResp;
?>