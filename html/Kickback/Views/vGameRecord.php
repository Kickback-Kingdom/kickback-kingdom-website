<?php
declare(strict_types=1);

namespace Kickback\Views;

use Kickback\Views\vRecordId;
use Kickback\Views\vAccount;
use Kickback\Views\vDateTime;
use Kickback\Views\vGame;

class vGameRecord extends vRecordId
{
    public vGame $game;
    public vAccount $account;
    public bool $won;
    public string $teamName;
    public vDateTime $date;

    public function getScore() : int {
        return ($this->won ? 1 : 0);
    }
    
    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }
}

?>