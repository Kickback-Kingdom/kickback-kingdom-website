<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vItem;
use Kickback\Backend\Views\vLoot;
use Kickback\Common\Primitives\Str;

class vItemStack
{
    public vRecordId $ownerId;
    public vItem $item;
    public int $amount;
    public ?vRecordId $nextLootId;
    public vRecordId $itemLootId;
    public ?vRecordId $containerLootId;
    public bool $isContainer;
    public string $nickname;
    public string $description;

    /** @var array<vLoot> */
    public ?array $lootStack = null;

    public function GetName() : string {
        if (Str::empty($this->nickname))
        {
            return $this->item->name;
        }
        return $this->nickname;
    }
}

?>
