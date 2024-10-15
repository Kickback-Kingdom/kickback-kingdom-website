<?php
declare(strict_types=1);

use Kickback\Backend\Controllers\QuestController;
use Kickback\Backend\Views\vRecordId;
use Kickback\Services\Session;

if (isset($_POST["submit-quest-review"])) {
    $hostRating = (int)$_POST["quest-review-host"];
    $questRating = (int)$_POST["quest-review-quest"];
    $comment = $_POST["quest-review-comment"];
    $quest_id = new vRecordId('', (int)$_POST["quest-review-quest-id"]);

    if (empty($hostRating) || empty($questRating)) {
        $showPopUpError = true;
        $PopUpTitle = "Validation Error";
        $PopUpMessage = "Failed to collect rewards. Both host and quest ratings must be filled out.";
    } else {
        
    $reviewResp = QuestController::SubmitFeedbackAndCollectRewards(Session::getCurrentAccount(),$quest_id,$hostRating,$questRating,$comment);
        if (!$reviewResp->success)
        {
            $showPopUpError = true;
            $PopUpTitle = "Error";
            $PopUpMessage = $reviewResp->message;

        }
    }
}

if (isset($_POST["submit-notifications-thanks-for-hosting"]))
{
    $quest_id = new vRecordId('', (int)$_POST["quest-notifications-thanks-for-hosting-quest-id"]);

    
    $reviewResp = QuestController::SubmitFeedbackAndCollectRewards(Session::getCurrentAccount(),$quest_id,null,null,null);

    if (!$reviewResp->success)
    {
        $showPopUpError = true;
        $PopUpTitle = "Error";
        $PopUpMessage = $reviewResp->message;

    }
}

?>