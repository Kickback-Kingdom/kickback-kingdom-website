<?php

require_once(\Kickback\SCRIPT_ROOT . "/Kickback/version.php");

$urlPrefixBeta = "";
if ( array_key_exists("KICKBACK_IS_BETA",$_SERVER) && $_SERVER["KICKBACK_IS_BETA"] ) {
    $urlPrefixBeta = "/beta";
}
$GLOBALS["urlPrefixBeta"] = $urlPrefixBeta;


function GetLoggedInAccountInformation()
{
    $info = new stdClass();
    if (IsLoggedIn())
    {
        $_SESSION["account"] = GetAccountById($_SESSION["account"]["Id"])->Data;
        $chestsResp = GetMyChests($_SESSION["account"]["Id"]);
        $chests = $chestsResp->Data;
        
        $notifications = GetAccountNotifications($_SESSION["account"]["Id"])->Data;

        $chestsJSON = json_encode($chests);
        $notificationsJSON = json_encode($notifications);

    }
    else{
        $chestsJSON = "[]";
        $notificationsJSON = "[]";
        $notifications = [];
        $chests = [];
    }

    $info->chestsJSON = $chestsJSON;
    $info->chests = $chests;
    $info->notifications = $notifications;
    $info->notificationsJSON = $notificationsJSON;
    $info->delayUpdateAfterChests = count($chests) > 0;
    return $info;
}

$showPopUpError = false;
$showPopUpSuccess = false;
$PopUpTitle = "";
$PopUpMessage = "";

$hasError = false;
$hasSuccess = false;
$successMessage = "";
$errorMessage = "";


$activeAccountInfo = GetLoggedInAccountInformation();

$chestsJSON = $activeAccountInfo->chestsJSON;

if (!IsLoggedIn())
{
    $_globalDoNotShowNewVersionPopup = true;
}

require(\Kickback\SCRIPT_ROOT . "/php-components/base-page-form-handler.php"); 

?>
