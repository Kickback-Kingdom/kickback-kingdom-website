<?php

declare(strict_types = 1);

namespace Kickback\Backend\Models;

use Kickback\Backend\Models\ForeignRecordId;

use Kickback\Backend\Views\vRecordId;
use \Kickback\Backend\Views\vPrice;

class StoreStock extends RecordId
{
    public vPrice $price;
    public ForeignRecordId $productId;
    public ForeignRecordId $storeId;
    public bool $removed;

    public function __construct(
        vPrice $price,
        vRecordId $productId, 
        vRecordId $storeId, 
        bool $removed = false)
    {
        parent::__construct();

        $this->price = $price;
        $this->productId = $productId->getForeignRecordId();
        $this->storeId = $storeId->getForeignRecordId();
        $this->removed = $removed;
    }
}

?>