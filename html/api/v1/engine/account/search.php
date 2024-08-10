<?php

require_once(__DIR__."/../../engine/engine.php");

use Kickback\Controllers\AccountController;

OnlyPOST();


$containsFieldsResp = POSTContainsFields("searchTerm","page","itemsPerPage");//,"sessionToken"

if (!$containsFieldsResp->success)
return $containsFieldsResp;

$searchTerm = Validate($_POST["searchTerm"]);
//$sessionToken = Validate($_POST["sessionToken"]);
$page = Validate($_POST["page"]);
$itemsPerPage = Validate($_POST["itemsPerPage"]);

$filters = [];
if (isset($_POST['filters']) && is_array($_POST['filters'])) {
    foreach ($_POST['filters'] as $key => $value) {
        $filters[$key] = Validate($value);
    }
}

assert(is_string($searchTerm));
assert(is_string($page) || is_int($page));
assert(is_string($itemsPerPage) || is_int($itemsPerPage));

if (is_string($page) && !ctype_digit($page)) {
    return (new Kickback\Models\Response(false, "'page' parameter must be integer, but was not. Got '$page' instead.", null));
}

if (is_string($itemsPerPage) && !ctype_digit($itemsPerPage)) {
    return (new Kickback\Models\Response(false, "'itemsPerPage' parameter must be integer, but was not. Got '$itemsPerPage' instead.", null));
}

return AccountController::SearchForAccount($searchTerm, intval($page), intval($itemsPerPage), $filters);
?>
