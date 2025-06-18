<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

class vTournamentBracket
{
    public int $bracket;

    /** @var array<vTournamentBracketRound> */
    public array $rounds;

    
    function __construct(int $bracket)
    {
        $this->bracket = $bracket;
        $this->rounds = [];
    }
}
?>
