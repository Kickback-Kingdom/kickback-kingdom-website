<?php

declare(strict_types=1);

namespace Kickback\Backend\Views;

use \Kickback\Backend\Models\ForeignRecordId;
use \Kickback\Backend\Views\vMedia;
use \Kickback\Backend\Controllers\ItemController;


class vProduct extends vRecordId
{
    public string $name;
    public string $locator;
    public string $currency_item_name;
    public vPrice $price;
    public string $description;
    public vItem $currency_item;
    public vMedia $ref_small_image_path;
    public vMedia $ref_large_image_path;
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
        ?string $ref_small_image_path,
        ?string $ref_large_image_path,
        vRecordId $storeId
        )
    {
        parent::__construct($ctime, $crand);

        $this->name = $name;
        $this->locator = $locator;
        $this->currency_item_name = is_null($currency_item_name) ? "ADA" : $currency_item_name;
        $this->price = $price;
        $this->description = $description;

        $currencyItemResp = ItemController::getItemById($currency_item);

        if ($currencyItemResp->success)
            $this->currency_item = $currencyItemResp->data;

        if ($ref_small_image_path != null)
        {
            $this->ref_small_image_path = new vMedia();
            $this->ref_small_image_path->setMediaPath($ref_small_image_path);
        }
        else{

            $this->ref_small_image_path = vMedia::defaultIcon();
        }

        if ($ref_small_image_path != null)
        {
            $this->ref_large_image_path = new vMedia();
            $this->ref_large_image_path->setMediaPath($ref_large_image_path);
        }
        else {
            
            $this->ref_large_image_path = vMedia::defaultIcon();
        }

        $this->storeId = $storeId->getForeignRecordId();
    }
}


?>