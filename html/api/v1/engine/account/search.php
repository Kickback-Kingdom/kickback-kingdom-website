<?php

require_once(__DIR__."/../../engine/engine.php");

OnlyPOST();


$containsFieldsResp = POSTContainsFields("searchTerm","page","itemsPerPage");//,"sessionToken"

if (!$containsFieldsResp->Success)
return $containsFieldsResp;

$searchTerm = Validate($_POST["searchTerm"]);
//$sessionToken = Validate($_POST["sessionToken"]);
$page = Validate($_POST["page"]);
$itemsPerPage = Validate($_POST["itemsPerPage"]);

return SearchForAccount($searchTerm, $page, $itemsPerPage);
?>
