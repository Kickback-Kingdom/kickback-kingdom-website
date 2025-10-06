<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vItem;
use Kickback\Backend\Views\vDateTime;

class vLoot extends vRecordId
{
    public bool $opened;
    public vRecordId $ownerId;
    public vItem $item;
    public ?vQuest $quest = null;
    public vDateTime $dateObtained;
    public ?vLoot $containerLoot;
    public string $nickname;
    public string $description;
    public int $quantity;
    
    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }
}



?>