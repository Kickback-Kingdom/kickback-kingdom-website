<?php
declare(strict_types=1);

use Kickback\Common\Utility\FormToken;

use Kickback\Backend\Models\Lobby;
use Kickback\Backend\Controllers\LobbyController;
use Kickback\Backend\Controllers\LobbyChallengeController;
use Kickback\Services\Session;
use Kickback\Backend\Models\ForeignRecordId;
use Kickback\Backend\Views\vRecordId;


if (isset($_POST["submit_match_report"])) {
    // Validate and use form token
    $tokenResponse = FormToken::useFormToken();

    if ($tokenResponse->success) {
        $lobbyId = new vRecordId($_POST["lobby_ctime"], (int)$_POST["lobby_crand"]);
        $challengeId = new vRecordId($_POST["challenge_ctime"], (int)$_POST["challenge_crand"]);
        $accountId = Session::getCurrentAccount()->getForeignRecordId();

        try {
            // Validate and sanitize inputs
            $matchResult = htmlspecialchars($_POST["match_result"] ?? '', ENT_QUOTES, 'UTF-8');
            $winningTeam = htmlspecialchars($_POST["winning_team"] ?? '', ENT_QUOTES, 'UTF-8');
            $didWin = $matchResult === 'win';
            $voteVoid = isset($_POST["vote_void"]) && $_POST["vote_void"] === '1';

            // Insert challenge result
            $insertResultResp = LobbyChallengeController::insertChallengeResult(
                $challengeId,
                $accountId,
                $winningTeam,
                $didWin,
                $voteVoid
            );

            if (!$insertResultResp->success) {
                throw new Exception($insertResultResp->message ?: "Failed to save match result.");
            }

            // Handle additional player details
            if (isset($_POST["players"]) && is_array($_POST["players"])) {
                foreach ($_POST["players"] as $teamName => $playerIds) {
                    foreach ($playerIds as $playerId) {
                        $reportedPlayerRecordId = new vRecordId('', (int)$playerId);

                        // Extract and sanitize character and picked_random values
                        $character = !empty($_POST["player_characters"][$teamName][$playerId])
                            ? htmlspecialchars($_POST["player_characters"][$teamName][$playerId], ENT_QUOTES, 'UTF-8')
                            : null;

                        $pickedRandom = isset($_POST["player_random"][$teamName][$playerId]) && $_POST["player_random"][$teamName][$playerId] !== ''
                            ? ($_POST["player_random"][$teamName][$playerId] === '1')
                            : null; // Set to null if the user selected "Not Sure"

                        // Insert challenge result details
                        $insertDetailsResp = LobbyChallengeController::insertChallengeResultDetails(
                            $challengeId,
                            $accountId,
                            $reportedPlayerRecordId,
                            $teamName ?: null,
                            $character,
                            $pickedRandom
                        );

                        if (!$insertDetailsResp->success) {
                            throw new Exception($insertDetailsResp->message ?: "Failed to save match result details.");
                        }
                    }
                }
            }

            // Check if all players have submitted their results
            $finalResp = LobbyChallengeController::hasEveryoneSubmitted($challengeId);
            if ($finalResp->success && $finalResp->data) {
                // Process final results if everyone has submitted
                $processFinalResp = LobbyChallengeController::processFinalResults($challengeId);
                if (!$processFinalResp->success) {
                    throw new Exception($processFinalResp->message ?: "Failed to process final results.");
                }
            }

            // Success response
            $showPopUpSuccess = true;
            $PopUpTitle = "Match Report";
            $PopUpMessage = "Match report submitted successfully!";
        } catch (Exception $e) {
            // Handle any exceptions
            $hasError = true;
            $errorMessage = $e->getMessage() ?: "An error occurred while processing your match report.";
        }
    } else {
        // Handle form token validation failure
        $hasError = true;
        $errorMessage = $tokenResponse->message ?: "Invalid form token. Please refresh the page and try again.";
    }
}



if (isset($_POST["player-settings-submit"])) {
    // Validate and use form token
    $tokenResponse = FormToken::useFormToken();

    if ($tokenResponse->success) {
        // Validate and sanitize inputs
        $lobbyId = new vRecordId($_POST["lobby_ctime"], (int)$_POST["lobby_crand"]);
        $challengeId = new vRecordId($_POST["challenge_ctime"], (int)$_POST["challenge_crand"]);
        $random_character = isset($_POST['player-settings-character-random']) && $_POST['player-settings-character-random'] === 'on';
        $selected_character = htmlspecialchars($_POST['player-settings-character'] ?? '', ENT_QUOTES, 'UTF-8');
        $custom_character = htmlspecialchars($_POST['player-settings-character-custom'] ?? '', ENT_QUOTES, 'UTF-8');

         // Determine character setting
         $characterToSave = null;
         if ($random_character) {
             $characterToSave = 'random';
         } elseif (!empty($custom_character)) {
             $characterToSave = $custom_character;
         } elseif (!empty($selected_character)) {
             $characterToSave = $selected_character;
         } else {
             // Handle case where no character is selected
             $hasError = true;
             $errorMessage = "You must select or enter a character.";
         }

         if (!$hasError) {
            // Bind parameters to the query
            if ($random_character)
                $characterToSave = '';
             // Save character settings (pseudo code for saving to database or session)
             $saveCharacterResp = LobbyChallengeController::saveCharacterSettings($challengeId, Session::getCurrentAccount()->getForeignRecordId(), $characterToSave, $random_character);

             if ($saveCharacterResp->success) {
                 // Success response
                 $showPopUpSuccess = true;
                 $PopUpTitle = "Player Settings";
                 $PopUpMessage = "Character settings saved successfully!";
             } else {
                 // Handle save failure
                 $showPopUpError = true;
                 $PopUpTitle = "Player Settings";
                 $PopUpMessage = $saveCharacterResp->message ?: "Failed to save character settings. Please try again.";
             }
         }
    } else {
        // Handle form token validation failure
        $hasError = true;
        $errorMessage = $tokenResponse->message ?: "Invalid form token. Please refresh the page and try again.";
    }
}


if (isset($_POST["start-challenge-submit"])) {
    $tokenResponse = FormToken::useFormToken();

    if ($tokenResponse->success) {
        $lobbyId = new vRecordId($_POST["lobby_ctime"], (int)$_POST["lobby_crand"]);
        $challengeId = new vRecordId($_POST["challenge_ctime"], (int)$_POST["challenge_crand"]);

        // Trigger the start of the challenge
        $startChallengeResp = LobbyChallengeController::startChallenge($lobbyId, $challengeId);

        if ($startChallengeResp->success) {
            // Redirect to the challenge page or display a success message
            $showPopUpSuccess = true;
            $PopUpTitle = "Start Challenge";
            $PopUpMessage = "Challenge has been successfully started!";
        } else {
            // Display error if the challenge could not be started
            $showPopUpError = true;
            $PopUpTitle = "Start Challenge";
            $PopUpMessage = $startChallengeResp->message;
        }
    } else {
        // Handle form token validation failure
        $hasError = true;
        $errorMessage = $tokenResponse->message;
    }
}

if (isset($_POST["host-lobby-submit"])) {
    
    
    $tokenResponse = FormToken::useFormToken();

    if ($tokenResponse->success) {

        $lobbyName = $_POST["host-lobby-name"];
        $password = $_POST["host-lobby-password"];
        $gameId = new ForeignRecordId('', (int)$_POST["host-lobby-game"]);
        
        $lobby = new Lobby($gameId, Session::getCurrentAccount()->getForeignRecordId(), $lobbyName); 
        
        $lobbyResp = LobbyController::host($lobby, $password);
        
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

if (isset($_POST["manage-lobby-submit"])) {
    
    $tokenResponse = FormToken::useFormToken();

    if ($tokenResponse->success) {
        $gameMode = $_POST["manage-lobby-gamemode"];
        $customRules = $_POST["manage-lobby-rules"];

        $lobbyId = new vRecordId($_POST["lobby_ctime"], (int)$_POST["lobby_crand"]);
        $challengeId = new vRecordId($_POST["challenge_ctime"], (int)$_POST["challenge_crand"]);

        $editLobbyChallengeResp = LobbyChallengeController::edit($lobbyId, $challengeId, $gameMode, $customRules);
    } else {
        $hasError = true;
        $errorMessage = $tokenResponse->message;
    }
}

if (isset($_POST["close-lobby-submit"])) {
    
    $tokenResponse = FormToken::useFormToken();

    if ($tokenResponse->success) {


        $lobbyId = new vRecordId($_POST["lobby_ctime"], (int)$_POST["lobby_crand"]);
        $challengeId = new vRecordId($_POST["challenge_ctime"], (int)$_POST["challenge_crand"]);

        $editLobbyChallengeResp = LobbyController::close($lobbyId);
        if ($editLobbyChallengeResp->success)
        {
            Session::redirect("/challenges.php");
        }

    } else {
        $hasError = true;
        $errorMessage = $tokenResponse->message;
    }
}

if (isset($_POST["accept-lobby-submit"])) {
    
    $tokenResponse = FormToken::useFormToken();

    if ($tokenResponse->success) {
        $lobbyId = new vRecordId($_POST["lobby_ctime"], (int)$_POST["lobby_crand"]);
        $challengeId = new vRecordId($_POST["challenge_ctime"], (int)$_POST["challenge_crand"]);
        $challenger = Session::getCurrentAccount();
        $insertChallengerResp = LobbyChallengeController::insertChallenger($challengeId, $challenger);


    } else {
        $hasError = true;
        $errorMessage = $tokenResponse->message;
    }
}

if (isset($_POST["publish-lobby-submit"])) {
    
    $tokenResponse = FormToken::useFormToken();

    if ($tokenResponse->success) {

        $lobbyId = new vRecordId($_POST["lobby_ctime"], (int)$_POST["lobby_crand"]);
        $challengeId = new vRecordId($_POST["challenge_ctime"], (int)$_POST["challenge_crand"]);

        $insertChallengerResp = LobbyController::publish($lobbyId);


    } else {
        $hasError = true;
        $errorMessage = $tokenResponse->message;
    }
}

if (isset($_POST["leave-lobby-submit"])) {

    $tokenResponse = FormToken::useFormToken();

    if ($tokenResponse->success) {

        $lobbyId = new vRecordId($_POST["lobby_ctime"], (int)$_POST["lobby_crand"]);
        $challengeId = new vRecordId($_POST["challenge_ctime"], (int)$_POST["challenge_crand"]);

        $leaveResp = LobbyChallengeController::leave($challengeId);
        if ($leaveResp->success)
        {

        }
        else{
            
            $hasError = true;
            $errorMessage = $leaveResp->message;
        }

    } else {
        $hasError = true;
        $errorMessage = $tokenResponse->message;
    }
}

if (isset($_POST["ready-up-submit"])) {
    $tokenResponse = FormToken::useFormToken();

    if ($tokenResponse->success) {

        $lobbyId = new vRecordId($_POST["lobby_ctime"], (int)$_POST["lobby_crand"]);
        $challengeId = new vRecordId($_POST["challenge_ctime"], (int)$_POST["challenge_crand"]);

        $leaveResp = LobbyChallengeController::readyUp($challengeId);
        if ($leaveResp->success)
        {

        }
        else{
            
            $hasError = true;
            $errorMessage = $leaveResp->message;
        }

    } else {
        $hasError = true;
        $errorMessage = $tokenResponse->message;
    }
}
?>
