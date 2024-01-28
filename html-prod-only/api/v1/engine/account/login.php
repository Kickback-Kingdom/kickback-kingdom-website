<?php
require(__DIR__."/../engine.php");

OnlyPOST();

$containsFieldsResp = POSTContainsFields("email","pwd","serviceKey");

if (!$containsFieldsResp->Success)
return $containsFieldsResp;

$email = Validate($_POST["email"]);
$pwd = Validate($_POST["pwd"]);
$serviceKey = Validate($_POST["serviceKey"]);

$containsDataResp = ContainsData($email, "Email");
if (!$containsDataResp->Success)
return $containsDataResp;

$containsDataResp = ContainsData($pwd, "Password");
if (!$containsDataResp->Success)
return $containsDataResp;

$containsDataResp = ContainsData($serviceKey, "Service Key");
if (!$containsDataResp->Success)
return $containsDataResp;

return Login($serviceKey,$email,$pwd);
?>