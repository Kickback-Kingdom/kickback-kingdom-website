<?php

$resp = require(__DIR__."/../engine/account/register.php");

$resp->Return();

/*
require(__DIR__."/../engine/engine.php");

OnlyPOST();

//ValidateBody("firstName","lastName","email","pwd","pwd_confirm","i_agree_to_the_terms","username");

$firstName = Validate($_POST["firstName"]);
$lastName = Validate($_POST["lastName"]);
$username = Validate($_POST["username"]);
$refUsername = Validate($_POST["refUsername"]);
$email = Validate($_POST["email"]);
$pwd = Validate($_POST["pwd"]);
$pwd_confirm = Validate($_POST["pwd_confirm"]);
$agreeterms = isset($_POST["i_agree_to_the_terms"]);

RequireData($firstName, "First Name");
RequireData($lastName, "Last Name");
RequireData($email, "Email");
RequireData($pwd, "Password");
RequireData($pwd_confirm, "Password Confirmation");
RequireData($username, "Username");
RequireData($refUsername, "Referrel's Username");


return RegisterAccount($firstName, $lastName, $pwd, $pwd_confirm, $username, $refUsername, $email, $agreeterms);

/*if (!$agreeterms)
{
    return (new APIResponse(false, "Please agree to the terms.", null));
}

if ($pwd != $pwd_confirm)
{
    return (new APIResponse(false, "Passwords do not match.", null));
}

$sql = "SELECT * FROM account WHERE Email = '$email'";

$result = mysqli_query($GLOBALS["conn"],$sql);

$num_rows = mysqli_num_rows($result);
$passwordHash = password_hash($pwd, PASSWORD_DEFAULT);
if ($num_rows === 0)
{
    $sql = "INSERT INTO account (Email, Password, FirstName, LastName, Username) VALUES ('$email', '$passwordHash', '$firstName', '$lastName','$username')";
    $result = mysqli_query($GLOBALS["conn"],$sql);
    if ($result === TRUE) {
        return (new APIResponse(true, "Account created successfully",null));
        } else {
        return (new APIResponse(false, "Failed to register account with error: ".GetSQLError(), null));
        }
}
else{
    return (new APIResponse(false, "Account already exists. Please register with a different email.", null));
}
*/


?>