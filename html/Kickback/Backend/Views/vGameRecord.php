<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vDateTime;
use Kickback\Backend\Views\vGame;

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