<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vMedia;
use Kickback\Backend\Views\vQuest;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Models\ItemType;
use Kickback\Backend\Models\ItemRarity;
use Kickback\Backend\Models\ItemEquipmentSlot;

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
    public bool $equipable;
    public ?ItemEquipmentSlot $equipmentSlot = null;

    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }

    public function isWritOfPassage() : bool {
        return $this->crand == 14;
    }
}



?>