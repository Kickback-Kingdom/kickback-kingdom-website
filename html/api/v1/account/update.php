<?php

require(__DIR__."/../engine/engine.php");

OnlyPOST();

ValidateBody("firstName","lastName","email","username");

$firstName = Validate($_POST["firstName"]);
$lastName = Validate($_POST["lastName"]);
$email = Validate($_POST["email"]);
$username = Validate($_POST["username"]);

RequireData($firstName, "First Name");
RequireData($lastName, "Last Name");
RequireData($email, "Email");
RequireData($username, "Username");


$id = GetAuthenticatedAccountId();

$sql = "update account set Email = '$email', FirstName = '$firstName', LastName = '$lastName', Username = '$username' WHERE Id = '$id'";

mysqli_query($GLOBALS["conn"],$sql);

$affectedRows = mysqli_affected_rows($GLOBALS["conn"]);

if ($affectedRows > 0)
{
    return (new Kickback\Backend\Models\Response(true, "Account updated successfully",null));
}
else
{
    return (new Kickback\Backend\Models\Response(false, "Account not found or failed to update with error: ".GetSQLError(),null));
}
?>