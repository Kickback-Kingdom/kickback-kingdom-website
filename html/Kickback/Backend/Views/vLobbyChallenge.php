<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Models\ForeignRecordId;
use Kickback\Backend\Models\PlayStyle;
use Kickback\Backend\Controllers\LobbyChallengeController;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vChallengePlayer;
use Kickback\Services\Session;
use Kickback\Common\Primitives\Arr;

class vLobbyChallenge extends vRecordId
{

    public string $gamemode = "Custom Rules";
    public string $rules;
    public PlayStyle $style;
    public ForeignRecordId $lobbyId;
    /** @var array<vChallengePlayer> */
    public array $players;
    public bool $hasJoined;
    public int $playerCount;
    public bool $allPlayersReady;
    public bool $ready;
    public int $playersReady;
    public bool $started;

    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }

    function getPlayerCount() : int {
        return $this->playerCount;

        if ($this->players == null)
        {
            return 0;
        }

        return count($this->players);
    }

    function downloadPlayers() : Response {
        $resp = LobbyChallengeController::getPlayers($this);
        if ($resp->success) {
            $this->players = $resp->data; // Assign to the class property
        } else {
            $this->players = []; // Ensure players is initialized to an empty array if the fetch fails
        }
        return $resp;
    }

    public function getHighestRankedPlayer(): ?vChallengePlayer
    {
        if (Arr::empty($this->players)) {
            return null; // Return null if the array is empty
        }

        $highestRankedPlayer = $this->players[0];

        foreach ($this->players as $player) {
            if ($player->elo > $highestRankedPlayer->elo) {
                $highestRankedPlayer = $player;
            }
        }

        return $highestRankedPlayer;
    }

    /**
    * @return array<string,array<vChallengePlayer>>
    */
    public function getGroupedPlayers(): array {
        $grouped = [];

        foreach ($this->players as $player) {
            $grouped[$player->teamName][] = $player;
        }

        return $grouped;
    }

    public function getMyPlayer() : ?vChallengePlayer
    {
        if (!Session::isLoggedIn()) {
            return null;
        }

        $currentAccount = Session::getCurrentAccount();
        if (!isset($currentAccount)) {
            return null;
        }

        $myId = $currentAccount->crand;

        foreach ($this->players as $player)
        {
            if ($player->account->crand == $myId) {
                return $player;
            }
        }

        return null;
    }
}



?>
