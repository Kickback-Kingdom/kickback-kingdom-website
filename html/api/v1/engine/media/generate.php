<?php

require_once(($_SERVER["DOCUMENT_ROOT"] ?: (__DIR__ . "/../../../..")) . "/Kickback/init.php");

require_once(\Kickback\SCRIPT_ROOT . "/api/v1/engine/engine.php");
use Kickback\Backend\Controllers\MediaController;
use Kickback\Backend\Controllers\AccountController;
use Kickback\Backend\Config\ServiceCredentials;
use Kickback\Backend\Models\Response;

OnlyPOST();

$containsFieldsResp = POSTContainsFields("prompt","directory","name","desc","sessionToken","size","model");
if (!$containsFieldsResp->success)
    return $containsFieldsResp;

$kk_service_key = ServiceCredentials::get("kk_service_key");

$prompt = Validate($_POST["prompt"]);
$directory = Validate($_POST["directory"]);
$name = Validate($_POST["name"]);
$desc = Validate($_POST["desc"]);
$sessionToken = Validate($_POST["sessionToken"]);
$size = Validate($_POST["size"]);
$model = Validate($_POST["model"]);

$loginResp = AccountController::getAccountBySession($kk_service_key, $sessionToken);
if (!$loginResp->success)
{
    return $loginResp;
}

if (!$loginResp->data->isAdmin)
{
    return new Response(false, "This action is admin-only.");
}

return MediaController::GenerateImage($prompt, $directory, $name, $desc, $size, $model);
?>
