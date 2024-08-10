<?php
require(__DIR__."/../engine.php");

OnlyPOST();

$containsFieldsResp = POSTContainsFields("email","pwd","serviceKey");

if (!$containsFieldsResp->success)
return $containsFieldsResp;

$email = Validate($_POST["email"]);
$pwd = Validate($_POST["pwd"]);
$serviceKey = Validate($_POST["serviceKey"]);

$containsDataResp = ContainsData($email, "Email");
if (!$containsDataResp->success)
return $containsDataResp;

$containsDataResp = ContainsData($pwd, "Password");
if (!$containsDataResp->success)
return $containsDataResp;

$containsDataResp = ContainsData($serviceKey, "Service Key");
if (!$containsDataResp->success)
return $containsDataResp;

return Kickback\Services\Session::Login($serviceKey,$email,$pwd);
?>