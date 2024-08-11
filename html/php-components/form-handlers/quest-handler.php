<?php
use Kickback\Common\Utility\FormToken;

use Kickback\Views\vRecordId;
use Kickback\Controllers\QuestController;

if (isset($_POST["edit-quest-images-submit"])) 
{
    $tokenResponse = FormToken::useFormToken();
    
    if ($tokenResponse->success) {
        $questId = new vRecordId('', $_POST["edit-quest-id"]);
        $desktopId =  new vRecordId('', $_POST["edit-quest-images-desktop-banner-id"]);
        $mobileId =  new vRecordId('', $_POST["edit-quest-images-mobile-banner-id"]);
        $iconId =  new vRecordId('', $_POST["edit-quest-images-icon-id"]);
        $response = QuestController::updateQuestImages($questId, $desktopId, $mobileId, $iconId);

        if ($response->success) {
            $showPopUpSuccess = true;
            $PopUpTitle = "Updated Quest";
            $PopUpMessage= "Your quest images have been updated successfully.";
        } else {
            $showPopUpError = true;
            $PopUpTitle = "Error";
            $PopUpMessage = $response->message." -> ".json_encode($response->data);
        }
    }
    else {
        $hasError = true;
        $errorMessage = $tokenResponse->message;
    }
}


if (isset($_POST["edit-quest-line-images-submit"])) 
{
    $tokenResponse = FormToken::useFormToken();
    
    if ($tokenResponse->success) {
        $questId = new vRecordId('', $_POST["edit-quest-line-id"]);
        $desktopId = new vRecordId('', $_POST["edit-quest-line-images-desktop-banner-id"]);
        $mobileId = new vRecordId('', $_POST["edit-quest-line-images-mobile-banner-id"]);
        $iconId = new vRecordId('', $_POST["edit-quest-line-images-icon-id"]);
        $response = QuestLineController::updateQuestLineImages($questId, $desktopId, $mobileId, $iconId);

        if ($response->success) {
            $showPopUpSuccess = true;
            $PopUpTitle = "Updated Quest Line";
            $PopUpMessage= "Your quest line images have been updated successfully.";
        } else {
            $showPopUpError = true;
            $PopUpTitle = "Error";
            $PopUpMessage = $response->message." -> ".json_encode($response->data);
        }
    }
    else {
        $hasError = true;
        $errorMessage = $tokenResponse->message;
    }
}

if (isset($_POST["edit-quest-options-submit"]))
{

    $tokenResponse = FormToken::useFormToken();
    
    if ($tokenResponse->success) {
        $response = QuestController::updateQuestOptions($_POST);
        if ($response->success) {
            $showPopUpSuccess = true;
            $PopUpTitle = "Updated Quest";
            $PopUpMessage= "Your quest options have been updated successfully. ".json_encode($_POST). "<br/>". json_encode($response->data);

            
            if ($response->data->locatorChanged)
            {
                Redirect("q/".$response->data->locator);
            }
        } else {
            $showPopUpError = true;
            $PopUpTitle = "Error";
            $PopUpMessage = $response->message." -> ".json_encode($response->data);
        }
    }
    else {
        $hasError = true;
        $errorMessage = $tokenResponse->message;
    }
}

if (isset($_POST["edit-quest-rewards-submit"]))
{
    
    $tokenResponse = FormToken::useFormToken();
    
    if ($tokenResponse->success) {
        $quest_id = new vRecordId('', $_POST["edit-quest-id"]);
        $shouldHaveStandardRewards = isset($_POST["edit-quest-rewards-has-standard"]);
        $rewardResp = null;
        if ($shouldHaveStandardRewards) {
            $rewardResp = QuestController::setupStandardParticipationRewards($quest_id);
        }
        else {
            $rewardResp = QuestController::removeStandardParticipationRewards($quest_id);

        }
        if (!$rewardResp->success) {
            $showPopUpError = true;
            $PopUpTitle = "Error";
            $PopUpMessage = $rewardResp->message." -> ".json_encode($rewardResp->data);
        }
    }
    else
    {
        $hasError = true;
        $errorMessage = $tokenResponse->message;

    }
}

if (isset($_POST["edit-quest-line-options-submit"]))
{

    $tokenResponse = FormToken::useFormToken();
    
    if ($tokenResponse->success) {
        $response = QuestLineController::updateQuestLineOptions($_POST);
        if ($response->success) {
            $showPopUpSuccess = true;
            $PopUpTitle = "Submitted Quest Line";
            $PopUpMessage= "Your quest line details have been updated successfully.";
            
            if ($response->data->locatorChanged)
            {
                Redirect("quest-line/".$response->data->locator);
            }
        } else {
            $showPopUpError = true;
            $PopUpTitle = "Error";
            $PopUpMessage = $response->message." -> ".json_encode($response->data);
        }
    }
    else {
        $hasError = true;
        $errorMessage = $tokenResponse->message;
    }
}


if (isset($_POST["submit-quest-publish"]))
{
    
    $tokenResponse = FormToken::useFormToken();
    
    if ($tokenResponse->success) {
        $questId = new vRecordId('', (int) $_POST["quest-id"]);
        $response = QuestController::submitQuestForReview($questId);
        if ($response->success) {
            $showPopUpSuccess = true;
            $PopUpTitle = "Published Quest";
            $PopUpMessage= $response->message;
        } else {
            $showPopUpError = true;
            $PopUpTitle = "Error";
            $PopUpMessage = $response->message." -> ".json_encode($response->data);
        }
    }
    else {
        $hasError = true;
        $errorMessage = $tokenResponse->message;
    }
}

if (isset($_POST["submit-quest-line-publish"]))
{
    
    $tokenResponse = FormToken::useFormToken();
    
    if ($tokenResponse->success) {
        $response = QuestLineController::submitQuestLineForReview($_POST);
        if ($response->success) {
            $showPopUpSuccess = true;
            $PopUpTitle = "Updated Quest Line";
            $PopUpMessage= $response->message;
        } else {
            $showPopUpError = true;
            $PopUpTitle = "Error";
            $PopUpMessage = $response->message." -> ".json_encode($response->data);
        }
    }
    else {
        $hasError = true;
        $errorMessage = $tokenResponse->message;
    }
}

if (isset($_POST["quest-line-approve-submit"]))
{
    
    $tokenResponse = FormToken::useFormToken();
    
    if ($tokenResponse->success) {
        $response = QuestLineController::approveQuestLineReview($_POST);
        if ($response->success) {
            $showPopUpSuccess = true;
            $PopUpTitle = "Approved Quest Line";
            $PopUpMessage= $response->message;
        } else {
            $showPopUpError = true;
            $PopUpTitle = "Error";
            $PopUpMessage = $response->message." -> ".json_encode($response->data);
        }
    }
    else {
        $hasError = true;
        $errorMessage = $tokenResponse->message;
    }
}

if (isset($_POST["quest-line-reject-submit"]))
{
    
    $tokenResponse = FormToken::useFormToken();
    
    if ($tokenResponse->success) {
        
        $response = QuestLineController::rejectQuestLineReview($_POST);
        if ($response->success) {
            $showPopUpSuccess = true;
            $PopUpTitle = "Rejected Quest Line";
            $PopUpMessage= $response->message;
        } else {
            $showPopUpError = true;
            $PopUpTitle = "Error";
            $PopUpMessage = $response->message." -> ".json_encode($response->data);
        }
    }
    else {
        $hasError = true;
        $errorMessage = $tokenResponse->message;
    }
}


if (isset($_POST["quest-approve-submit"]))
{
    
    $tokenResponse = FormToken::useFormToken();
    
    if ($tokenResponse->success) {
        $questId = new vRecordId('', (int) $_POST["quest-id"]);
        $response = QuestController::approveQuestReviewById($questId);
        if ($response->success) {
            $showPopUpSuccess = true;
            $PopUpTitle = "Approved Quest";
            $PopUpMessage= $response->message;
        } else {
            $showPopUpError = true;
            $PopUpTitle = "Error";
            $PopUpMessage = $response->message." -> ".json_encode($response->data);
        }
    }
    else {
        $hasError = true;
        $errorMessage = $tokenResponse->message;
    }
}

if (isset($_POST["quest-reject-submit"]))
{
    
    $tokenResponse = FormToken::useFormToken();
    
    if ($tokenResponse->success) {
        
        $questId = new vRecordId('', (int) $_POST["quest-id"]);
        $response = QuestController::rejectQuestReviewById($questId);
        if ($response->success) {
            $showPopUpSuccess = true;
            $PopUpTitle = "Rejected Quest";
            $PopUpMessage= $response->message;
        } else {
            $showPopUpError = true;
            $PopUpTitle = "Error";
            $PopUpMessage = $response->message." -> ".json_encode($response->data);
        }
    }
    else {
        $hasError = true;
        $errorMessage = $tokenResponse->message;
    }
}

?>
