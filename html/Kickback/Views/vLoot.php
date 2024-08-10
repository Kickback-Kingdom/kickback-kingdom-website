<?php
declare(strict_types=1);

namespace Kickback\Views;

use Kickback\Views\vRecordId;
use Kickback\Views\vItem;
use Kickback\Views\vDateTime;

class vLoot extends vRecordId
{

    public vRecordId $ownerId;
    public vItem $item;
    public ?vQuest $quest = null;
    public vDateTime $dateObtained;
    
    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }
}



?>