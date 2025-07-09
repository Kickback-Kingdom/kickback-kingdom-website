<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Models\PlayStyle;
use Kickback\Backend\Controllers\AccountController;
use Kickback\Backend\Models\Response;

class vChallengePlayer extends vRecordId
{
    public vAccount $account;
    public vDateTime $joinedAt;
    public string $teamName;
    public string $character;
    public bool $pickedRandom;
    public bool $win;
    public bool $ready;
    public bool $left;

    public int $elo;
    public bool $isRanked;
    public int $totalMatches;
    public int $totalWins;
    public int $totalLosses;
    public float $winRate;
    public int $minRankedMatches;
    public string $gameName;
    public string $gameLocator;
    public int $rank;

    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }

    function getRank() {
        if ($this->isRanked)
        {
            return "$this->rank";
        }
        else{
            return "unranked";
        }
    }

    function isRanked1() {
        return $this->rank === 1;
    }

static function getTeamPowerLevel(array $players) {
        
        // Filter out only ready players
        $readyPlayers = array_filter($players, fn($player) => $player->ready);

        if (!empty($readyPlayers)) {
            // Calculate the sum and average of ELO for ready players
            $sumElo = array_sum(array_map(fn($player) => $player->elo, $readyPlayers));
            $avgElo = $sumElo / count($readyPlayers);

            // Combine the sum and average for the team power level
            $teamPowerLevel = round(($sumElo + $avgElo)/2, 2);
        } else {
            $teamPowerLevel = 0; // If no players are ready, the power level is 0
        }

        return $teamPowerLevel;
    }

    function getLeagueDetails() {
        // Define the leagues with their ELO ranges and flavor text
        $leagues = [
            ["name" => "Hatchling", "max" => 1599, "flavor" => "The first step into the skies."],
            ["name" => "Wind Rider", "max" => 1799, "flavor" => "Gliding with the flow of the winds."],
            ["name" => "Branch Breaker", "max" => 1999, "flavor" => "Rising above the treetops."],
            ["name" => "Mountain Peak", "max" => 2499, "flavor" => "Conquering the heights of the earth."],
            ["name" => "Sky Breaker", "max" => 2999, "flavor" => "Piercing the clouds to claim the open skies."],
            ["name" => "Storm Piercer", "max" => 3999, "flavor" => "Challenging the fury of the storm."],
            ["name" => "Twilight", "max" => 4499, "flavor" => "Where moonlight meets the edge of the sky."],
            ["name" => "Legends of Kicsi", "max" => PHP_INT_MAX, "flavor" => "The final flight to the divine moon."],
        ];

        // Loop through leagues to find the correct range for the given ELO
        foreach ($leagues as $league) {
            if ($this->elo <= $league["max"]) {
                return [
                    "name" => $league["name"],
                    "flavor" => $league["flavor"],
                ];
            }
        }

        // Default return if no league matches (should not happen)
        return [
            "name" => "Unranked",
            "flavor" => "No rank yet assigned to this adventurer.",
        ];
    }
    
    public function getRankElement(): string
    {
        // Unranked case
        if (!$this->isRanked) {
            $matchesRemaining = max(0, $this->minRankedMatches - $this->totalMatches);
            return '<span class="badge unranked position-absolute start-0 top-0" 
                         data-bs-toggle="tooltip" 
                         data-bs-placement="left" 
                         data-bs-title="Unranked: ' . $matchesRemaining . ' matches remaining" style="border-radius: 4px 0px var(--bs-border-radius) 0px;">' .
                   $this->totalMatches . ' / ' . $this->minRankedMatches . '
                   </span>';
        }
    
        // Ranked case
        $rankClass = $this->rank === 1 ? 'bg-ranked-1' : 'ranked';
        $rankClass = $this->ready ? 'bg-success' : 'bg-danger';
        return '<span class="badge position-absolute start-0 top-0 ' . $rankClass . '" 
                     data-bs-toggle="tooltip" 
                     data-bs-placement="left" 
                     data-bs-title="Ranked #' . $this->rank . ' Kingdom Wide" style="border-radius: 0px 0px var(--bs-border-radius) 0px;">
                     #' . $this->rank . '
                </span>';
    }
    

    public function getEloElement(): string
    {
        // Elo badge class
        $eloClass = $this->ready ? 'bg-success' : 'bg-danger';
    
        // Return the Elo badge
        return '<span class="badge position-absolute top-0 end-0 ' . $eloClass . '" 
                     data-bs-toggle="tooltip" 
                     data-bs-placement="left" 
                     data-bs-title="Elo Rating: ' . $this->elo . '" style="border-radius: 0px 0px 0px var(--bs-border-radius);">
                    ' . $this->elo . '
                </span>';
    }
}



?>