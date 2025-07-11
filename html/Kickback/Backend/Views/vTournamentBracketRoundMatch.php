<?php

declare(strict_types=1);

namespace Kickback\Backend\Views;

class vTournamentBracketRoundMatch
{
    public int $match;

    /** @var array<vTournamentBracketRoundMatchSet> */
    public array $sets;

    
    function __construct(int $match)
    {
        $this->match = $match;
        $this->sets = [];
    }
}

?>
