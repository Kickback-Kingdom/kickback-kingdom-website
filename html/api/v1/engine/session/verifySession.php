<?php
require_once(__DIR__."/../../engine/engine.php");
use Kickback\Services\Session;
use Kickback\Controllers\AccountController;
if (IsPOST())
{
    $containsFieldsResp = POSTContainsFields("sessionToken","serviceKey");

    if (!$containsFieldsResp->success)
    return $containsFieldsResp;

    $sessionToken = Validate($_POST["sessionToken"]);
    $serviceKey = Validate($_POST["serviceKey"]);
}
elseif(IsGET())
{

    $containsFieldsResp = SESSIONContainsFields("sessionToken","serviceKey");
    if (!$containsFieldsResp->success)
    return $containsFieldsResp;

    $sessionToken = Validate($_SESSION["sessionToken"]);
    $serviceKey = Validate($_SESSION["serviceKey"]);
}

$containsDataResp = ContainsData($sessionToken, "Session Token");
if (!$containsDataResp->success)
return $containsDataResp;

$containsDataResp = ContainsData($serviceKey, "Service Key");
if (!$containsDataResp->success)
return $containsDataResp;


$session = AccountController::getAccountBySession($serviceKey, $sessionToken);


if (!$session->success) {
    Session::setSessionData('vAccount', null);
} else {
    Session::setSessionData('vAccount', $session->data);
}


if (Kickback\Services\Session::isAdmin()) {
    if (isset($_GET['delegateAccess'])) {
        $delegateResp = GetAccountById($_GET['delegateAccess']);
        if ($delegateResp->success) {
            Session::setSessionData('delegate_account', $delegateResp->data);
            Session::setSessionData('account_using_delegate_access', Session::getSessionData('vAccount'));
            Session::setSessionData('vAccount', $delegateResp->data);
        }
    }
}


if (Session::isDelegatingAccess()) {
    Session::setSessionData('vAccount', Session::getSessionData('delegate_account'));
}

if (isset($_GET['exitDelegate'])) {
    Session::setSessionData('vAccount', Session::getSessionData('account_using_delegate_access'));
    Session::removeSessionData('account_using_delegate_access');
    Session::removeSessionData('delegate_account');
}

return $session;
?>