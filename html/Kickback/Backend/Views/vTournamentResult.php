<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

use Kickback\Backend\Views\vAccount;

class vTournamentResult
{
    public string $teamName;
    public bool $teamCaptain;
    public bool $champion;
    public vAccount $account;
    public int $setsPlayed;
    
    public function __construct(string $teamName, bool $teamCaptain, bool $champion, vAccount $account, int $setsPlayed)
    {
        $this->teamName = $teamName;
        $this->teamCaptain = $teamCaptain;
        $this->champion = $champion;
        $this->account = $account;
        $this->setsPlayed = $setsPlayed;
    }
}
?>
