<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

use Kickback\Common\Exceptions\UnexpectedNullException;

use Kickback\Backend\Controllers\BracketController;
use Kickback\Backend\Controllers\TournamentController;

use Kickback\Backend\Views\vTournamentBracket;
use Kickback\Backend\Views\vTournamentBracketRound;
use Kickback\Backend\Views\vTournamentBracketRoundMatch;
use Kickback\Backend\Views\vTournamentBracketRoundMatchSet;
use Kickback\Backend\Views\vTournamentTeam;
use Kickback\Backend\Views\vTournamentResult;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vQuestApplicant;

class vTournament extends vRecordId
{
    private bool $hasBracket_;

    /** @var ?array<vTournamentTeam> */
    private ?array $competitors_ = null;

    private ?string $champion_ = null;

    /** @var ?array<vBracketInfo> */
    private ?array $bracketInfoArray_ = null;

    
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
        $tournamentResults = TournamentController::queryTournamentResults($this);

        $this->competitors_ = [];

        foreach ($tournamentResults as $tournamentResult)
        {
            $teamName = $tournamentResult->teamName;

            // Initialize the team if not already present
            if (!isset($this->competitors_[$teamName])) {
                $this->competitors_[$teamName] = new vTournamentTeam($teamName, $tournamentResult->champion);
            }

            // Add the player to the team
            $this->competitors_[$teamName]->addPlayer($tournamentResult->account);

            // Set the champion if applicable
            if ($tournamentResult->champion) {
                $this->champion_ = $teamName;
            }
        }

        $this->populateBrackets();
    }

    /**
    * @phpstan-assert-if-true !null $this->champion_
    */
    public function concluded() : bool {
        return !is_null($this->champion_);
    }

    public function hasBracket(bool ...$newValue) : bool {
        if ( count($newValue) === 1 ) {
            $this->hasBracket_ = $newValue[0];
        }
        return $this->hasBracket_;
    }

    /** @return array<vTournamentTeam> */
    public function competitors() : array
    {
        if ( is_null($this->competitors_) ) {
            throw new UnexpectedNullException();
        } else {
            return $this->competitors_;
        }
    }

    public function champion() : vTournamentTeam
    {
        if ( is_null($this->champion_) ) {
            throw new UnexpectedNullException();
        }

        // Note: Use the `competitors` accessor and not the private field,
        // because this gives us a null-check that PHPStan can use to verify type-safety
        // (and it catches null values earlier in execution if that ever happens here).
        return $this->competitors()[$this->champion_];
    }

    /**
    * @return array<vBracketInfo>
    */
    public function bracketInfoArray() : array
    {
        if ( is_null($this->bracketInfoArray_) ) {
            throw new UnexpectedNullException();
        }
        return $this->bracketInfoArray_;
    }

    public function populateBrackets() : void
    {
        $brackets = [];
        $this->bracketInfoArray_ = TournamentController::queryTournamentBracketInfos($this);

        foreach ($this->bracketInfoArray_ as $bracketInfo)
        {
            $bracketIndex = $bracketInfo->gameMatch->bracket - 1;

            if ($bracketIndex >= 0)
            {
                if (!array_key_exists($bracketIndex, $brackets)) {
                    $brackets[$bracketIndex] = new vTournamentBracket($bracketInfo->gameMatch->bracket);
                }

                $bracket = $brackets[$bracketIndex];

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

    /**
    * @param array<vQuestApplicant> $questApplicants
    * @return array{
    *     array<vBracketTeam>,
    *     array<vBracketMatch>,
    *     array<array<array<array{}|array{int, int, vBracketMatch}>>>,
    *     array<array{?string, ?string}>
    * }
    * // Names of things in the returned shaped array:
    * // array{
    * //     teams:                array<vBracketTeam>,
    * //     matchArray:           array<vBracketMatch>,
    * //     startPlacementArray:  array<array<array<array{}|array{scoreA: int,  scoreB: int,  match: vBracketMatch}>>>,
    * //     pairs:                array<array{betterPlayer: ?string,  worsePlayer: ?string}>
    * // }
    */
    public function calculateBracketRenderData($questApplicants) : array {
        return BracketController::calculateBracketRenderData($questApplicants, $this->bracketInfoArray());
    }
}



?>
