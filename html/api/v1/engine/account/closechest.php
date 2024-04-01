<?php

require_once(($_SERVER["DOCUMENT_ROOT"] ?: (__DIR__ . "/../../../..")) . "/Kickback/init.php");

require_once(\Kickback\SCRIPT_ROOT . "/api/v1/engine/engine.php");

OnlyPOST();

$containsFieldsResp = POSTContainsFields("chestId","accountId","sessionToken");
if (!$containsFieldsResp->Success)
    return $containsFieldsResp;

$kk_service_key = \Kickback\Config\ServiceCredentials::get("kk_service_key");

$chestId = Validate($_POST["chestId"]);
$accountId = Validate($_POST["accountId"]);
$sessionToken = Validate($_POST["sessionToken"]);

$loginResp = GetLoginSession($kk_service_key, $sessionToken);
if (!$loginResp->Success)
{
    return $loginResp;
}
return CloseChest($chestId, $accountId);
?>
