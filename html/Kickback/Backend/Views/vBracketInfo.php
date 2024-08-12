<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

use Kickback\Backend\Views\vGameRecord;
use Kickback\Backend\Views\vGameMatch;
use Kickback\Backend\Views\vAccount;

class vBracketInfo
{
    public vGameRecord $gameRecord;
    public vGameMatch $gameMatch;
    public vAccount $account;
}

?>