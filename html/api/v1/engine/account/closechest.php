<?php

require_once(__DIR__."/../../engine/engine.php");

OnlyPOST();

$containsFieldsResp = POSTContainsFields("chestId","accountId","sessionToken");
if (!$containsFieldsResp->Success)
    return $containsFieldsResp;

require_once($_SERVER['DOCUMENT_ROOT']."/service-credentials-ini.php");
$kk_credentials = LoadServiceCredentialsOnce();
$kk_service_key = $kk_credentials["kk_service_key"];
unset($kk_credentials);

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
