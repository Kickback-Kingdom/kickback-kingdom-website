<?php


if (isset($_POST["edit-quest-images-submit"])) 
{
    $tokenResponse = Kickback\Utilities\FormToken::useFormToken();
    
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
    $tokenResponse = Kickback\Utilities\FormToken::useFormToken();
    
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

    $tokenResponse = Kickback\Utilities\FormToken::useFormToken();
    
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
    
    $tokenResponse = Kickback\Utilities\FormToken::useFormToken();
    
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

    $tokenResponse = Kickback\Utilities\FormToken::useFormToken();
    
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
    
    $tokenResponse = Kickback\Utilities\FormToken::useFormToken();
    
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
    
    $tokenResponse = Kickback\Utilities\FormToken::useFormToken();
    
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
    
    $tokenResponse = Kickback\Utilities\FormToken::useFormToken();
    
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
    
    $tokenResponse = Kickback\Utilities\FormToken::useFormToken();
    
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
    
    $tokenResponse = Kickback\Utilities\FormToken::useFormToken();
    
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
    
    $tokenResponse = Kickback\Utilities\FormToken::useFormToken();
    
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

?>