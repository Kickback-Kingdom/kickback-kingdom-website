<?php

require_once(__DIR__."/../../engine/engine.php");

OnlyPOST();


$containsFieldsResp = POSTContainsFields("directory","name","desc","imgBase64","sessionToken");

if (!$containsFieldsResp->Success)
return $containsFieldsResp;

$directory = Validate($_POST["directory"]);
$imgBase64 = Validate($_POST["imgBase64"]);
$name = Validate($_POST["name"]);
$desc = Validate($_POST["desc"]);
$sessionToken = Validate($_POST["sessionToken"]);

$loginResp = GetLoginSession($GLOBALS["kkservice"], $sessionToken);
if (!$loginResp->Success)
{
    return $loginResp;
}
return UploadMediaImage($directory, $name, $desc, $imgBase64);
?>