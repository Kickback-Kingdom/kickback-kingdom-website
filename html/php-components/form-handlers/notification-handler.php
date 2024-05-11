<?php


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

?>