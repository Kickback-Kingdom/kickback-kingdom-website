<?php
declare(strict_types=1);

namespace Kickback\Backend\Models;

class Item extends RecordId
{
    public ItemType $type;
    public ItemRarity $rarity;

    public ForeignRecordId $mediaLarge; // media_id_large
    public ForeignRecordId $mediaSmall; // media_id_small
    public ?ForeignRecordId $mediaBack; // media_id_back

    public string $desc;
    public ?string $name = null;

    public ?ForeignRecordId $nominatedBy = null;
    public ?ForeignRecordId $collection = null;

    public bool $equipable;
    public ?ItemEquipmentSlot $equipmentSlot = null;

    public bool $redeemable;
    public bool $useable;

    public bool $isContainer;
    public int $containerSize; // -1 = infinite
    public bool $fungible;

    public ?ItemCategory $containerItemCategory = null;
    public ?ItemCategory $itemCategory = null;

    public function __construct()
    {
        parent::__construct();

        $this->type = ItemType::Standard;
        $this->rarity = ItemRarity::Common;

        $this->mediaLarge = new ForeignRecordId();
        $this->mediaSmall = new ForeignRecordId();

        $this->desc = '';
        $this->equipable = false;
        $this->redeemable = false;
        $this->useable = false;
        $this->isContainer = false;
        $this->containerSize = -1;
    }
}
