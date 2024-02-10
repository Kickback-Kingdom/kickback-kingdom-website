<?php

require_once(__DIR__."/../../engine/engine.php");

OnlyPOST();

$containsFieldsResp = POSTContainsFields("directory","searchTerm","page","itemsPerPage","sessionToken");
if (!$containsFieldsResp->Success)
    return $containsFieldsResp;

require_once($_SERVER['DOCUMENT_ROOT']."/service-credentials-ini.php");
$kk_credentials = LoadServiceCredentialsOnce();
$kk_service_key = $kk_credentials["kk_service_key"];
unset($kk_credentials);

$directory = Validate($_POST["directory"]);
$searchTerm = Validate($_POST["searchTerm"]);
$sessionToken = Validate($_POST["sessionToken"]);
$page = Validate($_POST["page"]);
$itemsPerPage = Validate($_POST["itemsPerPage"]);

$loginResp = GetLoginSession($kk_service_key, $sessionToken);
if (!$loginResp->Success)
{
    return $loginResp;
}
return SearchForMedia($directory, $searchTerm, $page, $itemsPerPage);
?>
