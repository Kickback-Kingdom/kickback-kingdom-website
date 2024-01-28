<?php
require(__DIR__."/../../engine/engine.php");

OnlyGET();

$id = $_GET["id"];
$sessionToken = $_GET['sessionToken'];
$serviceKey = $_GET['serviceKey'];


$session = GetLoginSession($serviceKey, $sessionToken);
if ($session->Success)
{

    return GetAccountById($id);
}
else{

    return (new APIResponse(false, "Please provide a valid session.", null));
}

?>