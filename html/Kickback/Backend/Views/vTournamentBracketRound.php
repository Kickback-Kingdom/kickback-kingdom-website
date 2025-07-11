<?php

declare(strict_types=1);

namespace Kickback\Backend\Views;

class vTournamentBracketRound
{
    public int $round;

    /** @var array<vTournamentBracketRoundMatch> */
    public array $matches;
    
    
    function __construct(int $round)
    {
        $this->round = $round;
        $this->matches = [];
    }
}

?>
