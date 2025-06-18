<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

class vPlayerConsensusDetails
{
    public int $playerId;                // ID of the reported player
    public ?string $teamName;            // Consensus team name for the player
    public float $teamNamePercentage;    // Percentage agreement on the team name

    public ?string $character;           // Consensus character for the player
    public float $characterPercentage;   // Percentage agreement on the character

    public ?bool $pickedRandom;          // Consensus on whether the player picked randomly
    public float $pickedRandomPercentage; // Percentage agreement on picked random

    public function __construct(
        int $playerId,
        ?string $teamName,
        float $teamNamePercentage,
        ?string $character,
        float $characterPercentage,
        ?bool $pickedRandom,
        float $pickedRandomPercentage
    ) {
        $this->playerId = $playerId;
        $this->teamName = $teamName;
        $this->teamNamePercentage = $teamNamePercentage;
        $this->character = $character;
        $this->characterPercentage = $characterPercentage;
        $this->pickedRandom = $pickedRandom;
        $this->pickedRandomPercentage = $pickedRandomPercentage;
    }

    /**
     * Determines if the picked random value has consensus.
     */
    public function pickedRandomHasConsensus(): bool
    {
        return $this->pickedRandom !== null && $this->pickedRandomPercentage > 0;
    }

    /**
     * Determines if the team name has consensus.
     */
    public function teamNameHasConsensus(): bool
    {
        return $this->teamName !== null && $this->teamNamePercentage > 0;
    }

    /**
     * Determines if the character has consensus.
     */
    public function characterHasConsensus(): bool
    {
        return $this->character !== null && $this->characterPercentage > 0;
    }

    /**
     * Helper to display the picked random consensus as a string.
     */
    public function getPickedRandomConsensusText(): string
    {
        if ($this->pickedRandomHasConsensus()) {
            $value = $this->pickedRandom ? 'Yes' : 'No';
            return "{$value} ({$this->pickedRandomPercentage}%)";
        }
        return '';
    }

    /**
     * Helper to display the team name consensus as a string.
     */
    public function getTeamNameConsensusText(): string
    {
        return $this->teamNameHasConsensus() ? "{$this->teamName} ({$this->teamNamePercentage}%)" : '';
    }

    /**
     * Helper to display the character consensus as a string.
     */
    public function getCharacterConsensusText(): string
    {
        return $this->characterHasConsensus() ? "{$this->character} ({$this->characterPercentage}%)" : '';
    }
}
