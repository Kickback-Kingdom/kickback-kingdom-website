<?php

$urlPrefixBeta = "";
if ( array_key_exists("KICKBACK_IS_BETA",$_SERVER) && $_SERVER["KICKBACK_IS_BETA"] ) {
    $urlPrefixBeta = "/beta";
}
$GLOBALS["urlPrefixBeta"] = $urlPrefixBeta;

use \Kickback\Common\Version;
use \Kickback\Services\Session;
use \Kickback\Views\Response;

$showPopUpError = false;
$showPopUpSuccess = false;
$PopUpTitle = "";
$PopUpMessage = "";

$hasError = false;
$hasSuccess = false;
$successMessage = "";
$errorMessage = "";


$activeAccountInfoResp = Session::getSessionInformation();
if (!$activeAccountInfoResp->success)
{
    $showPopUpError = true;
    $PopUpTitle = "Session Error!";
    $PopUpMessage = $activeAccountInfoResp->message;
}

$activeAccountInfo = $activeAccountInfoResp->data;

if (!Session::isLoggedIn())
{
    Version::$show_version_popup = false;
}

require(\Kickback\SCRIPT_ROOT . "/php-components/base-page-form-handler.php"); 

?>
