<?php
declare(strict_types=1);

namespace Kickback\Views;

class vTournamentBracket
{
    public int $bracket;
    public array $rounds;

    
    function __construct(int $bracket)
    {
        $this->bracket = $bracket;
        $this->rounds = [];
    }
}
?>