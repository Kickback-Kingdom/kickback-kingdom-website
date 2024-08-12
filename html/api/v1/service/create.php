<?php
require("../../../engine/engine.php");

OnlyPOST();

ValidateBody("email","pwd","serviceKey");

$email = Validate($_POST["email"]);
$pwd = Validate($_POST["pwd"]);
$serviceKey = Validate($_POST["serviceKey"]);

RequireData($email, "Email");
RequireData($pwd, "Password");
RequireData($serviceKey, "Service Key");


$sql = "SELECT * FROM account WHERE Email = '$email' and Banned = 0";

$result = mysqli_query($GLOBALS["conn"],$sql);

$num_rows = mysqli_num_rows($result);
if ($num_rows === 0)
{
    return (new Kickback\Backend\Models\Response(false, "Email or Password are incorrect", null));
}
else
{
    $row = mysqli_fetch_assoc($result);

    if (password_verify($pwd, $row["Password"]))
    {
        session_regenerate_id(true);
        $_SESSION["account_id"] = $row["Id"];
        if (RegisterLoginSession())
        {
            $sessionId = session_id();
            $accountId = GetAuthenticatedAccountId();

            $sql = "SELECT * FROM account_sessions WHERE session_id = '$sessionId' and account_id = $accountId";
            $result = mysqli_query($GLOBALS["conn"],$sql);
            $num_rows = mysqli_num_rows($result);
            if ($num_rows > 0)
            {

                $row = mysqli_fetch_assoc($result);
                return (new Kickback\Backend\Models\Response(false, "Welcome to Kickback Kingdom!", $row));
            }
            else{

                return (new Kickback\Backend\Models\Response(false, "Failed to login", null));
            }
        }
        else
        {
            return (new Kickback\Backend\Models\Response(false, "Failed to login", null));
        }
    }
    else
    {
        return (new Kickback\Backend\Models\Response(false, "Email or Password are incorrect2", array($pwd, $row["Password"])));
    }

}

?>