<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vItem;

class vItemStack
{
    public vRecordId $ownerId;
    public vItem $item;
    public int $amount;
    public vRecordId $nextLootId;
}

?>