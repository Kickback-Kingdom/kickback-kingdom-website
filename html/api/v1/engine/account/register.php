<?php

require_once(__DIR__."/../../engine/engine.php");

OnlyPOST();



$containsFieldsResp = POSTContainsFields("firstName","lastName","pwd","pwd_confirm","i_agree_to_the_terms","username","email","passage_quest","passage_id");

if (!$containsFieldsResp->Success)
return $containsFieldsResp;

$firstName = Validate($_POST["firstName"]);
$lastName = Validate($_POST["lastName"]);
$pwd = Validate($_POST["pwd"]);

$pwd_confirm = Validate($_POST["pwd_confirm"]);
$i_agree_to_the_terms = isset($_POST["i_agree_to_the_terms"]);
$username = Validate($_POST["username"]);

$passage_quest = Validate($_POST["passage_quest"]);
$passage_id = Validate($_POST["passage_id"]);

//$refUsername = Validate($_POST["refUsername"]);
$email = Validate($_POST["email"]);

return RegisterAccount($firstName, $lastName, $pwd, $pwd_confirm, $username, $email, $i_agree_to_the_terms, $passage_quest, $passage_id);

?>