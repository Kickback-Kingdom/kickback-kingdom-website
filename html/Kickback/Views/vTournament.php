<?php
declare(strict_types=1);

namespace Kickback\Views;

use Kickback\Controllers\TournamentController;

use Kickback\Views\vTournamentBracket;
use Kickback\Views\vTournamentBracketRound;
use Kickback\Views\vTournamentBracketRoundMatch;
use Kickback\Views\vTournamentBracketRoundMatchSet;
use Kickback\Views\vTournamentTeam;
use Kickback\Views\vTournamentResult;
use Kickback\Views\vAccount;
use Kickback\Controllers\BracketController;

class vTournament extends vRecordId
{
    public bool $hasBracket;
    public ?array $competitors = null;
    public ?string $champion = null;

    public ?array $brackets = null;
    public ?array $bracketInfoArray = null;

    
    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }

      /**
     * Populates tournament details.
     *
     * @return void
     */
    public function populate(): void
    {
        $championResp = TournamentController::getTournamentResults($this);
        if (!$championResp->success) {
            throw new Exception($championResp->message);
        }

        $tournamentResults = $championResp->data;

        $this->competitors = [];

        foreach ($tournamentResults as $tournamentResult) {
            $teamName = $tournamentResult->teamName;

            // Initialize the team if not already present
            if (!isset($this->competitors[$teamName])) {
                $this->competitors[$teamName] = new vTournamentTeam($teamName, $tournamentResult->champion);
            }

            // Add the player to the team
            $this->competitors[$teamName]->addPlayer($tournamentResult->account);

            // Set the champion if applicable
            if ($tournamentResult->champion) {
                $this->champion = $teamName;
            }
        }

        $this->populateBrackets();
    }

    public function concluded() : bool {
        return !is_null($this->champion);
    }

    public function getChampion() : vTournamentTeam {
        return $this->competitors[$this->champion];
    }

    public function populateBrackets() : void {
        $this->brackets = [];

        $bracketInfoArrayResp = TournamentController::getTournamentBracketInfo($this);
        $this->bracketInfoArray = $bracketInfoArrayResp->data;

        foreach ($this->bracketInfoArray as $bracketInfo) {
            
            $bracketIndex = $bracketInfo->gameMatch->bracket - 1;

            if ($bracketIndex >= 0)
            {
                if (!array_key_exists($bracketIndex, $this->brackets)) {
                    $this->brackets[$bracketIndex] = new vTournamentBracket($bracketInfo->gameMatch->bracket);
                }

                $bracket = $this->brackets[$bracketIndex];

                $roundIndex = $bracketInfo->gameMatch->round - 1;

                if (!array_key_exists($roundIndex, $bracket->rounds)) {
                    $bracket->rounds[$roundIndex] = new vTournamentBracketRound($bracketInfo->gameMatch->round);
                }

                $round = $bracket->rounds[$roundIndex];

                $matchIndex = $bracketInfo->gameMatch->match - 1;

                if (!array_key_exists($matchIndex, $round->matches)) {
                    $round->matches[$matchIndex] = new vTournamentBracketRoundMatch($bracketInfo->gameMatch->match);
                }

                $match = $round->matches[$matchIndex];

                $setIndex = $bracketInfo->gameMatch->set - 1;

                if (!array_key_exists($setIndex, $match->sets)) {
                    $match->sets[$setIndex] = new vTournamentBracketRoundMatchSet($bracketInfo->gameMatch->set);
                }

                $set = $match->sets[$setIndex];
            }
        }
    }

    public function getBracketRenderData($questApplicants) : array {
        return BracketController::getBracketRenderData($questApplicants, $this->bracketInfoArray);
    }
}



?>