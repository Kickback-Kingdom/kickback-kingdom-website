<?php
declare(strict_types=1);

namespace Kickback\Views;

use Kickback\Views\vAccount;

class vRaffle extends vRecordId
{
    public ?vAccount $winner = null;
}



?>