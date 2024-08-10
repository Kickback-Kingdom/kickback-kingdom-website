<?php
declare(strict_types=1);

namespace Kickback\Views;

class vTournamentTeam
{
    public string $name;
    public array $players;
    public ?bool $champion;
    public int $seed;

    /**
     * Constructor to initialize the team name and players array.
     *
     * @param string $name The name of the team.
     */
    public function __construct(string $name, ?bool $champion)
    {
        $this->name = $name;
        $this->players = [];
        $this->champion = $champion;
    }

    /**
     * Adds a player to the team.
     *
     * @param vAccount $account The account of the player to add.
     * @return void
     */
    public function addPlayer(vAccount $account): void
    {
        $this->players[] = $account;
    }

    public function getProfilePictureURL() : string {
        return $this->players[0]->getProfilePictureURL();
    }
}
?>
