<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

use Kickback\Backend\Views\vAccount;

class vRaffle extends vRecordId
{
    public ?vAccount $winner = null;
}



?>