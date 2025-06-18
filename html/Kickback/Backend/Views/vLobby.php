<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vGame;
use Kickback\Services\Session;

class vLobby extends vRecordId
{

    public vGame $game;
    public vAccount $host;
    public vLobbyChallenge $challenge;
    public bool $passwordProtected;
    public string $name;
    public vReviewStatus $reviewStatus;

    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }

    function getLobbyURL() : string {
        return '/lobby.php?l='.$this->toURLEncodedEncrypted();
    }

    function getJoinButtonElement() : string {
        return '<a class="btn btn-primary float-end mx-1" href="'.$this->getLobbyURL().'"><i class="fa-solid fa-play"></i></a>';
    }

    function getAcceptButtonElement() : string {
        if (Session::isLoggedIn())
        {
            return '<button class="btn bg-ranked-1 btn-sm" onclick="OpenAcceptLobby()">Join / Accept Terms</button>';
        }
        else{
            return '<a class="btn bg-ranked-1 btn-sm" href="/login.php?redirect='.urlencode($this->getLobbyURL()).'">Join / Accept Terms</a>';
        }
    }
    
    public function isHost() : bool {
        if (Session::isLoggedIn())
        {
            return (Session::getCurrentAccount()->crand == $this->host->crand);
        }
        else{
            return false;
        }
    }

    function hostCanStart() : bool {
        $hostIsReady = false;
        if ($this->isHost())
        {
            $hostIsReady = $this->challenge->ready;
        }

        return ($this->challenge->getPlayerCount() > 1) && $hostIsReady;
    }

    function hostCanClose() : bool {
        
        $hostIsReady = false;
        if ($this->isHost())
        {
            $hostIsReady = $this->challenge->ready;
        }

        if ($hostIsReady)
        {
            return ($this->challenge->playersReady == 1);
        }
        else
        {
            return ($this->challenge->playersReady > 1);
        }
    }

    public function canEditRules(): bool {
        return $this->isHost() && !$this->reviewStatus->isPublished();
    }
    
    public function canPublishChallenge(): bool {
        return $this->isHost() && !$this->reviewStatus->isPublished();
    }
    
    public function canStartChallenge(): bool {
        return $this->isHost() && $this->hostCanStart() && !$this->challenge->started;
    }
    
    public function canLeave(): bool {
        return !$this->isHost() && !$this->challenge->ready;
    }
    
    public function canReadyUp(): bool {
        return !$this->challenge->ready &&
            (($this->isHost() && $this->challenge->playerCount > 1) || !$this->isHost());
    }

    public function canSelectCharacter(): bool {
        // Early return if the game does not allow character selection
        if (!$this->game->allowsCharacterSelection) {
            return false;
        }
    
        // Check if the challenge is not ready and the review status is published
        return !$this->challenge->ready && $this->reviewStatus->isPublished();
    }
    
    

    public function getLobbyStatus(): array {
        if ($this->reviewStatus->published == false) {
            return ["message" => "Please select a game mode and rules...", "class" => "bg-danger text-bg-danger"];
        }
        if ($this->challenge->getPlayerCount() > 1) {
            if ($this->challenge->started) {
                return ["message" => "Ranked Challenge in progress...", "class" => "bg-warning text-bg-warning"];
            } else {
                if ($this->challenge->allPlayersReady) {
                    return ["message" => "Waiting for host to start the challenge...", "class" => "bg-info text-bg-info"];
                } else {
                    if ($this->challenge->ready) {
                        return ["message" => "Waiting for other challengers to ready up... (".$this->challenge->playersReady."/".$this->challenge->playerCount.")", "class" => "bg-secondary text-bg-secondary"];
                    } else {
                        return ["message" => "Waiting for you to ready up...", "class" => "bg-primary text-bg-primary"];
                    }
                }
            }
        } else {
            return ["message" => "Waiting for challengers...", "class" => "bg-success text-bg-success"];
        }
    }
    
}



?>