<?php
require(__DIR__."/../../engine/engine.php");

OnlyGET();

$id = $_GET["id"];
$sessionToken = $_GET['sessionToken'];
$serviceKey = $_GET['serviceKey'];


$session = Kickback\Services\Session::GetLoginSession($serviceKey, $sessionToken);
if ($session->success)
{

    return GetAccountById($id);
}
else{

    return (new Kickback\Models\Response(false, "Please provide a valid session.", null));
}

?>