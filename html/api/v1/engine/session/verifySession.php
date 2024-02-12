<?php
require_once(__DIR__."/../../engine/engine.php");

if (IsPOST())
{
    $containsFieldsResp = POSTContainsFields("sessionToken","serviceKey");

    if (!$containsFieldsResp->Success)
    return $containsFieldsResp;

    $sessionToken = Validate($_POST["sessionToken"]);
    $serviceKey = Validate($_POST["serviceKey"]);
}
elseif(IsGET())
{

    $containsFieldsResp = SESSIONContainsFields("sessionToken","serviceKey");
    if (!$containsFieldsResp->Success)
    return $containsFieldsResp;

    $sessionToken = Validate($_SESSION["sessionToken"]);
    $serviceKey = Validate($_SESSION["serviceKey"]);
}

$containsDataResp = ContainsData($sessionToken, "Session Token");
if (!$containsDataResp->Success)
return $containsDataResp;

$containsDataResp = ContainsData($serviceKey, "Service Key");
if (!$containsDataResp->Success)
return $containsDataResp;


$session = GetLoginSession($serviceKey, $sessionToken);

if (!$session->Success)
{
    $GLOBALS['account'] = null;
    $_SESSION['account'] = null;
}
else{
    
    $GLOBALS['account'] = $session->Data;
    $_SESSION['account'] = $session->Data;
}


if (IsAdmin())
{
    if (isset($_GET['delegateAccess'])) 
    {
        $delegateResp = GetAccountById($_GET['delegateAccess']);
        if ($delegateResp->Success)
        {
            $_SESSION['delegate_account'] = $delegateResp->Data;
            $_SESSION['account_using_delegate_access'] = $_SESSION['account'];
            $_SESSION['account'] = $delegateResp->Data;
        }
    }
}

if (IsDelegatingAccess())
{
    $_SESSION['account'] = $_SESSION['delegate_account'];
}



if (isset($_GET['exitDelegate']))
{
    $_SESSION['account'] = $_SESSION['account_using_delegate_access'];
    unset($_SESSION['account_using_delegate_access']);
    unset($_SESSION['delegate_account']);
}

return $session;
?>