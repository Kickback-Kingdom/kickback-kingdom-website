<?php

declare(strict_types=1);

namespace Kickback\Backend\Models;

use \Kickback\Backend\Views\vRecordId;
use \Kickback\Backend\Views\vPrice;

use \Kickback\Backend\Controllers\MediaController;

class Product extends RecordId
{
    public string $name;
    public string $locator;
    public ForeignRecordId $itemId;
    public ?ForeignRecordId $currencyItem;
    public vPrice $price;
    public string $description;
    public ForeignRecordId $storeId;

    function __construct(
        string $productName, 
        string $locator, 
        vRecordId $itemId,
        ?vRecordId $currencyItem, 
        vPrice $price, 
        string $description, 
        vRecordId $storeId
        )
    {
        parent::__construct();

        $this->name = $productName;
        $this->locator = $locator;
        $this->currencyItem = is_null($currencyItem) ? null : new ForeignRecordId($currencyItem->ctime, $currencyItem->crand);
        $this->price = $price;
        $this->description = $description;
        $this->storeId = $storeId->getForeignRecordId();
        $this->itemId = $itemId->getForeignRecordId();
    }
}

?>