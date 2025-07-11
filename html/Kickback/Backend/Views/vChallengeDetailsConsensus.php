<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

class vChallengeDetailsConsensus
{
    // Global consensus
    public ?string $winningTeam;          // Consensus winning team
    public float $winningTeamPercentage;  // Percentage agreement on the winning team

    public float $voteVoidPercentage;     // Percentage of players voting to void the challenge

    // Player-specific consensus
    /** @var vPlayerConsensusDetails[] */
    public array $playerConsensusDetails; // Consensus details for each player

    public bool $iVoted;
    public bool $allVoted;

    public function __construct(
        ?string $winningTeam,
        float $winningTeamPercentage,
        float $voteVoidPercentage,
        array $playerConsensusDetails,
        bool $iVoted,
        bool $allVoted
    ) {
        $this->winningTeam = $winningTeam;
        $this->winningTeamPercentage = $winningTeamPercentage;
        $this->voteVoidPercentage = $voteVoidPercentage;
        $this->playerConsensusDetails = $playerConsensusDetails;
        $this->iVoted = $iVoted;
        $this->allVoted = $allVoted;
    }

    /**
     * Determines if there is consensus for the winning team.
     */
    public function hasWinningTeamConsensus(): bool
    {
        return $this->winningTeam !== null && $this->winningTeamPercentage > 0;
    }

    /**
     * Determines if there is a significant vote to void the challenge.
     */
    public function hasVoteVoidConsensus(): bool
    {
        return $this->voteVoidPercentage > 0;
    }

    /**
     * Helper to display the winning team consensus as a string.
     */
    public function getWinningTeamConsensusText(): string
    {
        return $this->hasWinningTeamConsensus() 
            ? "Winning Team: {$this->winningTeam} ({$this->winningTeamPercentage}%)"
            : '';
    }

    /**
     * Helper to display the vote void consensus as a string.
     */
    public function getVoteVoidConsensusText(): string
    {
        return $this->hasVoteVoidConsensus() 
            ? "Vote Void: {$this->voteVoidPercentage}%"
            : '';
    }

    /**
     * Retrieves consensus details for a specific player.
     */
    public function getPlayerConsensus(int $playerId): ?vPlayerConsensusDetails
    {
        return $this->playerConsensusDetails[$playerId] ?? null;
    }

    /**
     * Filters only players with a valid consensus.
     * @return vPlayerConsensusDetails[]
     */
    public function getPlayersWithConsensus(): array
    {
        return array_filter($this->playerConsensusDetails, function ($player) {
            return $player->teamNameHasConsensus() || $player->characterHasConsensus() || $player->pickedRandomHasConsensus();
        });
    }
}
