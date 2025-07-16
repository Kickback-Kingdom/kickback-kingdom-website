<?php
require(__DIR__."/../../engine/engine.php");

use Kickback\Backend\Views\vRecordId;
OnlyGET();

$id = $_GET["id"];
$sessionToken = $_GET['sessionToken'];
$serviceKey = $_GET['serviceKey'];


$session = Kickback\Services\Session::GetLoginSession($serviceKey, $sessionToken);
if ($session->success)
{

    $accountId = new vRecordId('', $_GET['delegateAccess']);
    return AccountController::getAccountById($id);
}
else{

    return (new Kickback\Backend\Models\Response(false, "Please provide a valid session.", null));
}

?>
