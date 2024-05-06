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
    $tokenResponse = UseFormToken();

    if ($tokenResponse->Success) {

        $response = UpdateContentDataByID($_POST);

        
        if ($response->Success)
        {
            
            $hasSuccess = true;
            $successMessage= "Updated content successfully.";
        }
    } else {
        $hasError = true;
        $errorMessage = $tokenResponse->Message;
    }
}

if (isset($_POST["submit-blog-post-publish"]))
{
    $tokenResponse = UseFormToken();
    
    if ($tokenResponse->Success) {
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
    else {
        $hasError = true;
        $errorMessage = $tokenResponse->Message;
    }
}

if (isset($_POST["edit-quest-images-submit"])) 
{
    $tokenResponse = UseFormToken();
    
    if ($tokenResponse->Success) {
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
    else {
        $hasError = true;
        $errorMessage = $tokenResponse->Message;
    }
}


if (isset($_POST["edit-quest-line-images-submit"])) 
{
    $tokenResponse = UseFormToken();
    
    if ($tokenResponse->Success) {
        $questId = $_POST["edit-quest-line-id"];
        $desktopId = $_POST["edit-quest-line-images-desktop-banner-id"];
        $mobileId = $_POST["edit-quest-line-images-mobile-banner-id"];
        $iconId = $_POST["edit-quest-line-images-icon-id"];
        $response = UpdateQuestLineImages($questId, $desktopId, $mobileId, $iconId);

        if ($response->Success) {
            $showPopUpSuccess = true;
            $PopUpTitle = "Updated Quest Line";
            $PopUpMessage= "Your quest line images have been updated successfully.";
        } else {
            $showPopUpError = true;
            $PopUpTitle = "Error";
            $PopUpMessage = $response->Message." -> ".json_encode($response->Data);
        }
    }
    else {
        $hasError = true;
        $errorMessage = $tokenResponse->Message;
    }
}

if (isset($_POST["edit-quest-options-submit"]))
{

    $tokenResponse = UseFormToken();
    
    if ($tokenResponse->Success) {
        $response = UpdateQuestOptions($_POST);
        if ($response->Success) {
            $showPopUpSuccess = true;
            $PopUpTitle = "Updated Quest";
            $PopUpMessage= "Your quest options have been updated successfully. ".json_encode($_POST). "<br/>". json_encode($response->Data);

            
            if ($response->Data->locatorChanged)
            {
                Redirect("q/".$response->Data->locator);
            }
        } else {
            $showPopUpError = true;
            $PopUpTitle = "Error";
            $PopUpMessage = $response->Message." -> ".json_encode($response->Data);
        }
    }
    else {
        $hasError = true;
        $errorMessage = $tokenResponse->Message;
    }
}

if (isset($_POST["edit-quest-rewards-submit"]))
{
    
    $tokenResponse = UseFormToken();
    
    if ($tokenResponse->Success) {
        $quest_id = $_POST["edit-quest-id"];
        $shouldHaveStandardRewards = isset($_POST["edit-quest-rewards-has-standard"]);
        $rewardResp = null;
        if ($shouldHaveStandardRewards) {
            $rewardResp = SetupStandardParticipationRewards($quest_id);
        }
        else {
            $rewardResp = RemoveStandardParticipationRewards($quest_id);

        }
        if (!$rewardResp->Success) {
            $showPopUpError = true;
            $PopUpTitle = "Error";
            $PopUpMessage = $rewardResp->Message." -> ".json_encode($rewardResp->Data);
        }
    }
    else
    {
        $hasError = true;
        $errorMessage = $tokenResponse->Message;

    }
}

if (isset($_POST["edit-quest-line-options-submit"]))
{

    $tokenResponse = UseFormToken();
    
    if ($tokenResponse->Success) {
        $response = UpdateQuestLineOptions($_POST);
        if ($response->Success) {
            $showPopUpSuccess = true;
            $PopUpTitle = "Submitted Quest Line";
            $PopUpMessage= "Your quest line details have been updated successfully.";
            
            if ($response->Data->locatorChanged)
            {
                Redirect("quest-line/".$response->Data->locator);
            }
        } else {
            $showPopUpError = true;
            $PopUpTitle = "Error";
            $PopUpMessage = $response->Message." -> ".json_encode($response->Data);
        }
    }
    else {
        $hasError = true;
        $errorMessage = $tokenResponse->Message;
    }
}


if (isset($_POST["submit-quest-publish"]))
{
    
    $tokenResponse = UseFormToken();
    
    if ($tokenResponse->Success) {
        $response = SubmitQuestForReview($_POST);
        if ($response->Success) {
            $showPopUpSuccess = true;
            $PopUpTitle = "Published Quest";
            $PopUpMessage= $response->Message;
        } else {
            $showPopUpError = true;
            $PopUpTitle = "Error";
            $PopUpMessage = $response->Message." -> ".json_encode($response->Data);
        }
    }
    else {
        $hasError = true;
        $errorMessage = $tokenResponse->Message;
    }
}

if (isset($_POST["submit-quest-line-publish"]))
{
    
    $tokenResponse = UseFormToken();
    
    if ($tokenResponse->Success) {
        $response = SubmitQuestLineForReview($_POST);
        if ($response->Success) {
            $showPopUpSuccess = true;
            $PopUpTitle = "Updated Quest Line";
            $PopUpMessage= $response->Message;
        } else {
            $showPopUpError = true;
            $PopUpTitle = "Error";
            $PopUpMessage = $response->Message." -> ".json_encode($response->Data);
        }
    }
    else {
        $hasError = true;
        $errorMessage = $tokenResponse->Message;
    }
}

if (isset($_POST["quest-line-approve-submit"]))
{
    
    $tokenResponse = UseFormToken();
    
    if ($tokenResponse->Success) {
        $response = ApproveQuestLineReview($_POST);
        if ($response->Success) {
            $showPopUpSuccess = true;
            $PopUpTitle = "Approved Quest Line";
            $PopUpMessage= $response->Message;
        } else {
            $showPopUpError = true;
            $PopUpTitle = "Error";
            $PopUpMessage = $response->Message." -> ".json_encode($response->Data);
        }
    }
    else {
        $hasError = true;
        $errorMessage = $tokenResponse->Message;
    }
}

if (isset($_POST["quest-line-reject-submit"]))
{
    
    $tokenResponse = UseFormToken();
    
    if ($tokenResponse->Success) {
        
        $response = RejectQuestLineReview($_POST);
        if ($response->Success) {
            $showPopUpSuccess = true;
            $PopUpTitle = "Rejected Quest Line";
            $PopUpMessage= $response->Message;
        } else {
            $showPopUpError = true;
            $PopUpTitle = "Error";
            $PopUpMessage = $response->Message." -> ".json_encode($response->Data);
        }
    }
    else {
        $hasError = true;
        $errorMessage = $tokenResponse->Message;
    }
}


if (isset($_POST["quest-approve-submit"]))
{
    
    $tokenResponse = UseFormToken();
    
    if ($tokenResponse->Success) {
        $response = ApproveQuestReview($_POST);
        if ($response->Success) {
            $showPopUpSuccess = true;
            $PopUpTitle = "Approved Quest";
            $PopUpMessage= $response->Message;
        } else {
            $showPopUpError = true;
            $PopUpTitle = "Error";
            $PopUpMessage = $response->Message." -> ".json_encode($response->Data);
        }
    }
    else {
        $hasError = true;
        $errorMessage = $tokenResponse->Message;
    }
}

if (isset($_POST["quest-reject-submit"]))
{
    
    $tokenResponse = UseFormToken();
    
    if ($tokenResponse->Success) {
        
        $response = RejectQuestReview($_POST);
        if ($response->Success) {
            $showPopUpSuccess = true;
            $PopUpTitle = "Rejected Quest";
            $PopUpMessage= $response->Message;
        } else {
            $showPopUpError = true;
            $PopUpTitle = "Error";
            $PopUpMessage = $response->Message." -> ".json_encode($response->Data);
        }
    }
    else {
        $hasError = true;
        $errorMessage = $tokenResponse->Message;
    }
}

if (IsAdmin())
{
    
    if (isset($_POST["process-purchase"]))
    {
        $tokenResponse = UseFormToken();
    
        if ($tokenResponse->Success) {

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
        else 
        {
            $hasError = true;
            $errorMessage = $tokenResponse->Message;
        }
    }

    if (isset($_POST["process-statements"]))
    {
        
        $tokenResponse = UseFormToken();

        if ($tokenResponse->Success) {
            $statement_date = $_POST["statement-date"];
            $processResp = ProcessMonthlyStatements($statement_date);
            if ($processResp->Success)
            {
                $showPopUpSuccess = true;
                $PopUpMessage = $processResp->Message;
                $PopUpTitle = "Statement Processed Successfully";

            }
            else{
                
            $showPopUpError = true;
            $PopUpMessage = $processResp->Message;
            $PopUpTitle = "Purchase Processed Failed";
            }
        }
        else 
        {
            $hasError = true;
            $errorMessage = $tokenResponse->Message;
        }
    }
}



$activeAccountInfo = GetLoggedInAccountInformation();

$chestsJSON = $activeAccountInfo->chestsJSON;



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
