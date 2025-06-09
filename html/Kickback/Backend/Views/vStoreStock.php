<?php

declare(strict_types=1);

namespace Kickback\Backend\Views;

use \Kickback\Backend\Models\ForeignRecordId;
use \Kickback\Backend\Models\StoreStock;

use \Kickback\Backend\Views\vRecordId;

class vStoreStock extends vRecordId
{
    public string $name;
    public bool $removed;
    public string $locator;
    public ?string $currencyName;
    public vPrice $price;
    public vPrice $productPrice;
    public string $description;
    public string $storeName;
    public ForeignRecordId $productId;
    public ForeignRecordId $productCurrencyItemId;
    public ForeignRecordId $storeId;
    public ForeignRecordId $currencyItemId;
    public string $smallImagePath;
    public string $largeImagePath;

    public function __construct(
        string $ctime,
        int $crand,
        string $name,
        vPrice $price,
        vPrice $productPrice,
        bool $removed,
        string $locator,
        ?string $currencyName,
        string $description,
        string $storeName,
        vRecordId $productId,
        vRecordId $productCurrencyItemId,
        vRecordId $storeId,
        vRecordId $currencyItemId,
        string $smallImagePath,
        string $largeImagePath
    ) {
        parent::__construct($ctime, $crand);

        $this->name = $name;
        $this->price = new vPrice($price->smallCurrencyUnit, $currencyItemId);
        $this->productPrice = new vPrice($productPrice->smallCurrencyUnit, $productCurrencyItemId);
        $this->removed = $removed;
        $this->locator = $locator;
        $this->currencyName = $currencyName;
        $this->description = $description;
        $this->storeName = $storeName;
        $this->productId = $productId->getForeignRecordId();
        $this->productCurrencyItemId = $productCurrencyItemId->getForeignRecordId();
        $this->storeId = $storeId->getForeignRecordId();
        $this->currencyItemId = $currencyItemId->getForeignRecordId();
        $this->smallImagePath = $smallImagePath;
        $this->largeImagePath = $largeImagePath;
    }

    public function toStoreStock() : StoreStock
    {
        $storeStock = new StoreStock(
            $this->price,
            $this->productId,
            $this->storeId,
            $this->removed
        );

        $storeStock->ctime = $this->ctime;
        $storeStock->crand = $this->crand;

        return $storeStock;
    }
}


?>