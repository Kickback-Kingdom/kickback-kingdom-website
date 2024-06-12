<?php
declare(strict_types=1);

namespace Kickback\Views;

use Kickback\Views\vRecordId;
use Kickback\Views\vMedia;
use Kickback\Views\vQuest;
use Kickback\Views\vAccount;
use Kickback\Models\ItemType;
use Kickback\Models\ItemRarity;

class vItem extends vRecordId
{
    public string $name;
    public string $description;
    public vMedia $iconSmall;
    public vMedia $iconBig;
    public ?vAccount $nominatedBy = null;
    public ItemType $type;
    public ItemRarity $rarity;
    public vDateTime $dateCreated;

    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }
}



?>