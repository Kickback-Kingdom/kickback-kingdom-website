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

assert(is_string($searchTerm));
assert(is_string($page) || is_int($page));
assert(is_string($itemsPerPage) || is_int($itemsPerPage));

if (is_string($page) && !ctype_digit($page)) {
    return (new APIResponse(false, "'page' parameter must be integer, but was not. Got '$page' instead.", null));
}

if (is_string($itemsPerPage) && !ctype_digit($itemsPerPage)) {
    return (new APIResponse(false, "'itemsPerPage' parameter must be integer, but was not. Got '$itemsPerPage' instead.", null));
}

return SearchForAccount($searchTerm, intval($page), intval($itemsPerPage));
?>
