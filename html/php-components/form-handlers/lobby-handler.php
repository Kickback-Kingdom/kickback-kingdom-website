<?php

use Kickback\Utilities\FormToken;
use Kickback\Models\Lobby;
use Kickback\Controllers\LobbyController;

if (isset($_POST["hostLobbySubmit"])) {
    
    
    $tokenResponse = FormToken::useFormToken();

    if ($tokenResponse->success) {

        $lobby = new Lobby(GetCurrentAccountId(), $_POST["hostLobbyName"]); 
        
        

        $lobbyResp = LobbyController::Host($lobby, $_POST["hostLobbyPassword"]);
        

        if ($lobbyResp->success)
        {

            $showPopUpSuccess = true;
            $PopUpTitle = "Create Lobby";
            $PopUpMessage = "Lobby created successfully!";
        }
        else
        {

            $showPopUpError = true;
            $PopUpTitle = "Create Lobby";
            $PopUpMessage = $lobbyResp->message;
        }
        
    } else {
        $hasError = true;
        $errorMessage = $tokenResponse->message;
    }
}

?>