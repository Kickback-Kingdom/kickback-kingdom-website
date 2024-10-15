<?php

use \Kickback\Common\Version;
use \Kickback\Services\Session;
use \Kickback\Backend\Views\Response;


$showPopUpError = false;
$showPopUpSuccess = false;
$PopUpTitle = "";
$PopUpMessage = "";

$hasError = false;
$hasSuccess = false;
$successMessage = "";
$errorMessage = "";

require(\Kickback\SCRIPT_ROOT . "/php-components/base-page-form-handler.php"); 

$activeAccountInfoResp = Session::getSessionInformation();
if (!$activeAccountInfoResp->success)
{
    $showPopUpError = true;
    $PopUpTitle = "Session Error!";
    $PopUpMessage = $activeAccountInfoResp->message;
}

$activeAccountInfo = $activeAccountInfoResp->data;

/*if (!Session::isLoggedIn())
{
    Version::$show_version_popup = false;
}*/
Version::$show_version_popup = false;


?>
