<?php

declare(strict_types = 1);

namespace Kickback\Backend\Views;

use Kickback\Backend\Views\vRecordId;

class vProductLootLink extends vRecordId
{
    public vRecordId $productId;
    public vRecordId $lootId;
    
    public bool $removed;
    public int $quantity;

    public function __construct(string $ctime = '', int $crand = -1, bool $removed = false, int $quantity = 1, ?vRecordId $productId = null, ?vRecordId $lootId = null)
    {
        parent::__construct($ctime, $crand);

        $this->removed = $removed;
        $this->quantity = $quantity;

        if(!is_null($productId)) $this->productId = $productId->getForeignRecordId();
        if(!is_null($lootId)) $this->lootId = $lootId->getForeignRecordId();
    }
}

?>