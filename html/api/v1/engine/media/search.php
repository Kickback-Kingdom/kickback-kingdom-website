<?php

require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

require_once(\Kickback\SCRIPT_ROOT . "/api/v1/engine/engine.php");

OnlyPOST();

$containsFieldsResp = POSTContainsFields("directory","searchTerm","page","itemsPerPage","sessionToken");
if (!$containsFieldsResp->Success)
    return $containsFieldsResp;

$kk_service_key = \Kickback\Config\ServiceCredentials::get("kk_service_key");

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
