<?php
use Kickback\Backend\Controllers\ContentController;
if (isset($_POST["save-content"])) {
    
    /*$showPopUpSuccess = true;
    $PopUpTitle = "Recieved Data";
    $PopUpMessage = json_encode($_POST);*/
    $tokenResponse = Kickback\Common\Utility\FormToken::useFormToken();

    if ($tokenResponse->success)
    {
        $response = ContentController::update_content_data_from_http_post($_POST);

        if ($response->success)
        {
            $hasSuccess = true;
            $successMessage= "Updated content successfully.";
        }
        else
        {
            $hasError = true;
            $errorMessage = $response->message;
        }
    }
    else
    {
        $hasError = true;
        $errorMessage = $tokenResponse->message;
    }
}

?>
