<?php

require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

require_once(\Kickback\SCRIPT_ROOT . "/api/v1/engine/engine.php");

OnlyPOST();

$containsFieldsResp = POSTContainsFields("directory","name","desc","imgBase64","sessionToken");
if (!$containsFieldsResp->Success)
    return $containsFieldsResp;

$kk_service_key = \Kickback\Config\ServiceCredentials::get("kk_service_key");

$directory = Validate($_POST["directory"]);
$imgBase64 = Validate($_POST["imgBase64"]);
$name = Validate($_POST["name"]);
$desc = Validate($_POST["desc"]);
$sessionToken = Validate($_POST["sessionToken"]);

$loginResp = GetLoginSession($kk_service_key, $sessionToken);
if (!$loginResp->Success)
{
    return $loginResp;
}
return UploadMediaImage($directory, $name, $desc, $imgBase64);
?>
