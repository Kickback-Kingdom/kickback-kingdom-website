<?php



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
    }

    $info->chestsJSON = $chestsJSON;
    $info->notifications = $notifications;
    $info->notificationsJSON = $notificationsJSON;
    return $info;
}
$showPopUpError = false;
$showPopUpSuccess = false;
$PopUpTitle = "";
$PopUpMessage = "";

//submit forms
if (isset($_POST["submit-quest-review"])) {
    $hostRating = $_POST["quest-review-host"];
    $questRating = $_POST["quest-review-quest"];
    $comment = $_POST["quest-review-comment"];
    $quest_id = $_POST["quest-review-quest-id"];

    if (empty($hostRating) || empty($questRating)) {
        $showPopUpError = true;
        $PopUpTitle = "Validation Error";
        $PopUpMessage = "Failed to collect rewards. Both host and quest ratings must be filled out.";
    } else {
        
        $reviewResp = SubmitFeedbackAndCollectRewards($_SESSION["account"]["Id"],$quest_id,$hostRating,$questRating,$comment);
        if (!$reviewResp->Success)
        {
            $showPopUpError = true;
            $PopUpTitle = "Error";
            $PopUpMessage = $reviewResp->Message;

        }
    }
}

if (isset($_POST["submit-notifications-thanks-for-hosting"]))
{
    $quest_id = $_POST["quest-notifications-thanks-for-hosting-quest-id"];

    
    $reviewResp = SubmitFeedbackAndCollectRewards($_SESSION["account"]["Id"],$quest_id,null,null,null);

    if (!$reviewResp->Success)
    {
        $showPopUpError = true;
        $PopUpTitle = "Error";
        $PopUpMessage = $reviewResp->Message;

    }
}

if (isset($_POST["submit-equipment"]))
{
    
    //$showPopUpSuccess = true;
    //$PopUpTitle = "Recieved Data";
    //$PopUpMessage = json_encode($_POST);

    
    $response = UpsertAccountEquipment($_POST);

    if ($response->Success)
    {
        
        $hasSuccess = true;
        $successMessage= "Updated equipment successfully.";
    }
}

if (isset($_POST["save-content"])) {
    
    /*$showPopUpSuccess = true;
    $PopUpTitle = "Recieved Data";
    $PopUpMessage = json_encode($_POST);*/

    $response = UpdateContentDataByID($_POST);

    
    if ($response->Success)
    {
        
        $hasSuccess = true;
        $successMessage= "Updated content successfully.";
    }
    
}

if (isset($_POST["submit-blog-post-publish"]))
{
    $blog_post_id = $_POST["blog-post-id"];

    $response = PublishBlogPost($blog_post_id);

    // Handle the response
    if ($response->Success) {
        $showPopUpSuccess = true;
        $PopUpTitle = "Updated Blog Post";
        $PopUpMessage= "Your blog post has been published successfully.";
    } else {
        $showPopUpError = true;
        $PopUpTitle = "Error";
        $PopUpMessage = $response->Message." -> ".json_encode($response->Data);
    }
}

if (isset($_POST["edit-quest-images-submit"])) 
{
    $questId = $_POST["edit-quest-id"];
    $desktopId = $_POST["edit-quest-images-desktop-banner-id"];
    $mobileId = $_POST["edit-quest-images-mobile-banner-id"];
    $iconId = $_POST["edit-quest-images-icon-id"];
    $response = UpdateQuestImages($questId, $desktopId, $mobileId, $iconId);

    if ($response->Success) {
        $showPopUpSuccess = true;
        $PopUpTitle = "Updated Quest";
        $PopUpMessage= "Your quest images have been updated successfully.";
    } else {
        $showPopUpError = true;
        $PopUpTitle = "Error";
        $PopUpMessage = $response->Message." -> ".json_encode($response->Data);
    }
}

if (isset($_POST["edit-quest-options-submit"]))
{

    $response = UpdateQuestOptions($_POST);
    if ($response->Success) {
        $showPopUpSuccess = true;
        $PopUpTitle = "Updated Quest";
        $PopUpMessage= "Your quest options have been updated successfully. ".json_encode($_POST);
    } else {
        $showPopUpError = true;
        $PopUpTitle = "Error";
        $PopUpMessage = $response->Message." -> ".json_encode($response->Data);
    }
}

if (IsAdmin())
{
    
    if (isset($_POST["process-purchase"]))
    {
        $pid = $_POST["purchase_id"];
        $purchaseToProcess = PullMerchantGuildPurchaseInformation($pid);
        $currentStatementTiedToPTP = BuildStatement($purchaseToProcess['account_id'], $purchaseToProcess['execution_date'], false);
        $preProcessData = PreProcessPurchase($purchaseToProcess, $currentStatementTiedToPTP);


        $sharesToBeGiven = $preProcessData["shareCertificatesToBeGivien"];

        //for each share to be given
        //$giveLootResp = GiveMerchantGuildShare($purchaseToProcess['account_id'],$purchaseToProcess['execution_date']);
        $processResp = ProcessMerchantSharePurchase($pid, $sharesToBeGiven);

        if ($processResp->Success)
        {

            $showPopUpSuccess = true;
            $PopUpMessage = $processResp->Message;
            $PopUpTitle = "Purchase Processed Successfully";
        }
        else{
            $showPopUpError = true;
            $PopUpMessage = $processResp->Message;
            $PopUpTitle = "Purchase Processed Failed";

        }


        unset($purchaseToProcess);
        unset($currentStatementTiedToPTP);
        unset($preProcessData);
    }

    if (isset($_POST["process-statements"]))
    {
        $statement_date = $_POST["statement-date"];
        ProcessMonthlyStatements($statement_date);
        $showPopUpSuccess = true;
        $PopUpMessage = "Go check the db";
        $PopUpTitle = "Statement Processed Successfully";
    }
}



$activeAccountInfo = GetLoggedInAccountInformation();

$chestsJSON = $activeAccountInfo->chestsJSON;


$urlPrefixBeta = "";
if ( $_SERVER["KICKBACK_IS_BETA"] ) {
    $urlPrefixBeta = "/beta";
}


if (isset($_POST["submitBlogOptions"])) {
    
    
    /*$showPopUpSuccess = true;
    $PopUpTitle = "Recieved Data";
    $PopUpMessage = json_encode($_POST);*/

    $title = $_POST["blogPostOptionsTitle"];
    $locator = $_POST["blogPostOptionsLocator"];
    $desc = $_POST["blogPostOptionsDesc"];
    $imageId = $_POST["blogPostOptionsIcon"];
    $postIdToUpdate = $_POST["blogPostId"]; // You'll need a way to determine which post to update.

    $response = UpdateBlogPost($postIdToUpdate, $title, $locator, $desc, $imageId);

    // Handle the response
    if ($response->Success) {
        $showPopUpSuccess = true;
        $PopUpTitle = "Updated Blog Post";
        $PopUpMessage= "Your changes have been saved successfully.";
        $newURL = $urlPrefixBeta.$response->Data;
        header('Location: '.$newURL);
    } else {
        $showPopUpError = true;
        $PopUpTitle = "Error";
        $PopUpMessage = $response->Message." -> ".json_encode($response->Data);
    }
}


?>
