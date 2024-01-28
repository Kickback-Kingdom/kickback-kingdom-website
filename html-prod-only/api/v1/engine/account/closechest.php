<?php

require_once(__DIR__."/../../engine/engine.php");

OnlyPOST();


$containsFieldsResp = POSTContainsFields("chestId","accountId","sessionToken");

if (!$containsFieldsResp->Success)
return $containsFieldsResp;

$chestId = Validate($_POST["chestId"]);
$accountId = Validate($_POST["accountId"]);
$sessionToken = Validate($_POST["sessionToken"]);

$loginResp = GetLoginSession($GLOBALS["kkservice"], $sessionToken);
if (!$loginResp->Success)
{
    return $loginResp;
}
return CloseChest($chestId, $accountId);
?>