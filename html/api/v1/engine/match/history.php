<?php

require_once(__DIR__."/../../engine/engine.php");

use Kickback\Backend\Controllers\ChallengeHistoryController;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vRecordId;
OnlyPOST();


$containsFieldsResp = POSTContainsFields("gameId","page","itemsPerPage");//,"sessionToken"

if (!$containsFieldsResp->success)
return $containsFieldsResp;

$gameId = (int)Validate($_POST["gameId"]);
//$sessionToken = Validate($_POST["sessionToken"]);
$page = Validate($_POST["page"]);
$itemsPerPage = Validate($_POST["itemsPerPage"]);

$filters = [];
if (isset($_POST['filters']) && is_array($_POST['filters'])) {
    foreach ($_POST['filters'] as $key => $value) {
        $filters[$key] = Validate($value);
    }
}

assert(is_int($gameId));
assert(is_string($page) || is_int($page));
assert(is_string($itemsPerPage) || is_int($itemsPerPage));

if (is_string($gameId) && !ctype_digit($gameId)) {
    return (new Response(false, "'gameId' parameter must be integer, but was not. Got '$gameId' instead.", null));
}

if (is_string($page) && !ctype_digit($page)) {
    return (new Response(false, "'page' parameter must be integer, but was not. Got '$page' instead.", null));
}

if (is_string($itemsPerPage) && !ctype_digit($itemsPerPage)) {
    return (new Response(false, "'itemsPerPage' parameter must be integer, but was not. Got '$itemsPerPage' instead.", null));
}

return ChallengeHistoryController::getMatchHistory(new vRecordId('', $gameId), intval($page), intval($itemsPerPage));

?>
