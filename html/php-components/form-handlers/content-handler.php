<?php
use Kickback\Controllers\ContentController;
if (isset($_POST["save-content"])) {
    
    /*$showPopUpSuccess = true;
    $PopUpTitle = "Recieved Data";
    $PopUpMessage = json_encode($_POST);*/
    $tokenResponse = Kickback\Utilities\FormToken::useFormToken();

    if ($tokenResponse->success) {
    //if (true) {

        $response = ContentController::updateContentDataByID($_POST);

        
        if ($response->success)
        {
            
            $hasSuccess = true;
            $successMessage= "Updated content successfully.";
        }
    } else {
        $hasError = true;
        $errorMessage = $tokenResponse->message;
    }
}

?>