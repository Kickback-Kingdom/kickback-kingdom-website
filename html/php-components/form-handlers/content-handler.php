<?php

if (isset($_POST["save-content"])) {
    
    /*$showPopUpSuccess = true;
    $PopUpTitle = "Recieved Data";
    $PopUpMessage = json_encode($_POST);*/
    $tokenResponse = Kickback\Utilities\FormToken::useFormToken();

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

?>