<?php
declare(strict_types=1);

use Kickback\Common\Utility\FormToken;

use Kickback\Backend\Models\Lobby;
use Kickback\Backend\Controllers\LobbyController;
use Kickback\Services\Session;

if (isset($_POST["host-lobby-submit"])) {
    
    
    $tokenResponse = FormToken::useFormToken();

    if ($tokenResponse->success) {

        $lobbyName = $_POST["host-lobby-name"];
        $password = $_POST["host-lobby-password"];
        $gameId = new ForeignRecordId('', $_POST["host-lobby-game"]);
        
        $lobby = new Lobby($gameId, Session::getCurrentAccountId(), $lobbyName); 
        
        

        $lobbyResp = LobbyController::Host($lobby, $password);
        

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
