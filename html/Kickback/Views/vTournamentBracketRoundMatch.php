<?php

declare(strict_types=1);

namespace Kickback\Views;

class vTournamentBracketRoundMatch
{
    public int $match;
    public array $sets;

    
    function __construct(int $match)
    {
        $this->match = $match;
        $this->sets = [];
    }
}

?>