<?php

declare(strict_types=1);

namespace Kickback\Backend\Views;

class vTournamentBracketRound
{
    public int $round;
    public array $matches;

    
    
    function __construct(int $round)
    {
        $this->round = $round;
        $this->matches = [];
    }
}

?>