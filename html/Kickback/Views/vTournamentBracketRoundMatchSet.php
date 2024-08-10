<?php
declare(strict_types=1);


namespace Kickback\Views;



class vTournamentBracketRoundMatchSet {

    public int $set;

    function __construct(int $set)
    {
        $this->set = $set;
    }
}


?>