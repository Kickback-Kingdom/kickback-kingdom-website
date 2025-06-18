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
use Kickback\Backend\Models\ItemCategory;

class vItem extends vRecordId
{
    public string $name;
    public string $description;
    public vMedia $iconSmall;
    public vMedia $iconBig;
    public vMedia $iconBack;
    public ?vAccount $nominatedBy = null;
    public ItemType $type;
    public ItemRarity $rarity;
    public vDateTime $dateCreated;
    public bool $equipable;
    public ?ItemEquipmentSlot $equipmentSlot = null;

    public bool $redeemable;
    public bool $useable;

    public bool $isContainer;
    public int $containerSize; // -1 = infinite
    public ?ItemCategory $containerItemCategory = null;
    public ?ItemCategory $itemCategory = null;

    /** @var array<vLichCard> */
    public array $auxData;

    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
        
        $this->name = '';
        $this->description = '';
        $this->iconSmall = vMedia::defaultIcon();
        $this->iconBig = vMedia::defaultIcon();
        $this->type = ItemType::Standard;
        $this->rarity = ItemRarity::Common;
        $this->dateCreated = new vDateTime();
        $this->equipable = false;
        $this->redeemable = false;
        $this->useable = false;
        $this->isContainer = false;
        $this->containerSize = -1;
        $this->auxData = [];
    }

    public function isWritOfPassage() : bool {
        return $this->crand == 14;
    }
}



?>
