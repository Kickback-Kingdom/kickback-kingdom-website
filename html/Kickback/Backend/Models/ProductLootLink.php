<?php

declare(strict_types = 1);

namespace Kickback\Backend\Models;

use Kickback\Backend\Views\vRecordId;

class ProductLootLink extends RecordId
{
    public ForeignRecordId $productId;
    public ForeignRecordId $lootId;
    
    public bool $removed;
    public int $quantity;

    public function __construct(bool $removed = false, int $quantity = 1, ?vRecordId $productId = null, ?vRecordId $lootId = null)
    {
        parent::__construct();

        $this->removed = $removed;
        $this->quantity = $quantity;

        if(!is_null($productId)) $this->productId = $productId->getForeignRecordId();
        if(!is_null($lootId)) $this->lootId = $lootId->getForeignRecordId();
    }
}

?>