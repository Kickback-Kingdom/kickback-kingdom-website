<?php

require_once(__DIR__."/../../engine/engine.php");

OnlyPOST();


$containsFieldsResp = POSTContainsFields("directory","searchTerm","page","itemsPerPage","sessionToken");

if (!$containsFieldsResp->Success)
return $containsFieldsResp;

$directory = Validate($_POST["directory"]);
$searchTerm = Validate($_POST["searchTerm"]);
$sessionToken = Validate($_POST["sessionToken"]);
$page = Validate($_POST["page"]);
$itemsPerPage = Validate($_POST["itemsPerPage"]);

$loginResp = GetLoginSession($GLOBALS["kkservice"], $sessionToken);
if (!$loginResp->Success)
{
    return $loginResp;
}
return SearchForMedia($directory, $searchTerm, $page, $itemsPerPage);
?>