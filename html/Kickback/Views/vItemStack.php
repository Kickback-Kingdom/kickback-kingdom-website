<?php
declare(strict_types=1);

namespace Kickback\Views;

use Kickback\Views\vRecordId;
use Kickback\Views\vItem;

class vItemStack
{
    public vRecordId $ownerId;
    public vItem $item;
    public int $amount;
    public vRecordId $nextLootId;
}

?>