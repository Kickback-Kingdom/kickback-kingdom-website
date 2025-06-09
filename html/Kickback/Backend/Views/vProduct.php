<?php

declare(strict_types=1);

namespace Kickback\Backend\Views;

use \Kickback\Backend\Models\ForeignRecordId;


class vProduct extends vRecordId
{
    public string $name;
    public string $locator;
    public string $currency_item_name;
    public vPrice $price;
    public string $description;
    public ForeignRecordId $currency_item;
    public string $ref_small_image_path;
    public string $ref_large_image_path;
    public ForeignRecordId $storeId;

    function __construct(
        string $ctime, 
        int $crand, 
        string $name, 
        string $locator, 
        ?string $currency_item_name, 
        vPrice $price, 
        string $description, 
        ?vRecordId $currency_item,
        string $ref_small_image_path,
        string $ref_large_image_path,
        vRecordId $storeId
        )
    {
        parent::__construct($ctime, $crand);

        $this->name = $name;
        $this->locator = $locator;
        $this->currency_item_name = is_null($currency_item_name) ? "ADA" : $currency_item_name;
        $this->price = $price;
        $this->description = $description;
        $this->currency_item = new ForeignRecordId($currency_item->ctime, $currency_item->crand);
        $this->ref_small_image_path = $ref_small_image_path;
        $this->ref_large_image_path = $ref_large_image_path;
        $this->storeId = $storeId->getForeignRecordId();
    }
}


?>