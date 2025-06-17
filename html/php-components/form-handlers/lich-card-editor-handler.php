<?php
declare(strict_types=1);

use Kickback\Common\Utility\FormToken;

use Kickback\Services\Session;
use Kickback\Backend\Models\ForeignRecordId;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vLichCard;
use Kickback\Backend\Controllers\LichCardController;


if (isset($_POST["submit_lich_card_edit"])) {
    // Validate and use form token
    $tokenResponse = FormToken::useFormToken();

    if ($tokenResponse->success) {

        $json = $_POST["lichCardData"];
        $data  = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
        $thisLichCardData = new vLichCard();
        $thisLichCardData->hydrate($data);
        

        $model = $thisLichCardData->toModel();

        $saveCardResponse = LichCardController::saveLichCard($model);

        if ($saveCardResponse->success) {
            
            $showPopUpSuccess = true;
            $PopUpTitle = "Saved Lich Card!";
            $PopUpMessage = $saveCardResponse->message;
        } else {

            
            $showPopUpError = true;
            $PopUpTitle = "Faile to save Lich Card!";
            $PopUpMessage = $saveCardResponse->message;
        }


    } else {
        // Handle form token validation failure
        $showPopUpError = true;
        $PopUpTitle = "Failed!";
        $PopUpMessage = $tokenResponse->message ?: "Invalid form token. Please refresh the page and try again.";
    }

}

?>