<?php

require_once(($_SERVER["DOCUMENT_ROOT"] ?: (__DIR__ . "/../../../..")) . "/Kickback/init.php");

require_once(\Kickback\SCRIPT_ROOT . "/api/v1/engine/engine.php");

use Kickback\Backend\Controllers\AccountController;
use Kickback\Backend\Controllers\LichCardController;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vLichCard;
use Kickback\Backend\Models\Response;

OnlyPOST();

$containsFieldsResp = POSTContainsFields("lichCardData", "sessionToken");
if (!$containsFieldsResp->success)
    return $containsFieldsResp;

$kk_service_key = \Kickback\Backend\Config\ServiceCredentials::get("kk_service_key");

$thisLichCardData = $_POST["lichCardData"];
$sessionToken = Validate($_POST["sessionToken"]);


// Validate session token and account
$loginResp = AccountController::getAccountBySession($kk_service_key, $sessionToken);
if (!$loginResp->success) {
    return $loginResp;
}

$account = $loginResp->data;

if (!$account->isServantOfTheLich)
{
    return new Response(false, "You are not a servant of the lich! Begone!");
}

$data  = json_decode($thisLichCardData, false, 512, JSON_THROW_ON_ERROR);
$thisLichCardData = new vLichCard();
$thisLichCardData->hydrate($data);


$model = $thisLichCardData->toModel();

$saveCardResponse = LichCardController::saveLichCard($model);

return $saveCardResponse;

?>
