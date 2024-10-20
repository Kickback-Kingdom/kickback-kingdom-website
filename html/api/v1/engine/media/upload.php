<?php

require_once(($_SERVER["DOCUMENT_ROOT"] ?: (__DIR__ . "/../../../..")) . "/Kickback/init.php");

require_once(\Kickback\SCRIPT_ROOT . "/api/v1/engine/engine.php");
use Kickback\Backend\Controllers\MediaController;
use Kickback\Services\Session;
use Kickback\Backend\Config\ServiceCredentials;
use Kickback\Backend\Controllers\AccountController;

OnlyPOST();

$containsFieldsResp = POSTContainsFields("directory","name","desc","imgBase64","sessionToken");
if (!$containsFieldsResp->success)
    return $containsFieldsResp;

$kk_service_key = ServiceCredentials::get("kk_service_key");

$directory = Validate($_POST["directory"]);
$imgBase64 = Validate($_POST["imgBase64"]);
$name = Validate($_POST["name"]);
$desc = Validate($_POST["desc"]);
$sessionToken = Validate($_POST["sessionToken"]);

$loginResp = AccountController::getAccountBySession($kk_service_key, $sessionToken);
if (!$loginResp->success)
{
    return $loginResp;
}
return MediaController::UploadMediaImage($directory, $name, $desc, $imgBase64);
?>
