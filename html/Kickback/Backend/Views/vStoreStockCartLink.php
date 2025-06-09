<?php

declare(strict_types=1);

namespace Kickback\Backend\Views;

use \Kickback\Backend\Models\ForeignRecordId;

class vStoreStockCartLink extends vRecordId
{
    public string $ctime;
    public int $crand;
    public string $name;
    public string $description;
    public vPrice $productPrice;
    public vPrice $stockPrice;
    public vPrice $price;
    public ?string $currencyItemName;
    public bool $checkedOut;
    public bool $removed;
    public ForeignRecordId $cartId;
    public ForeignRecordId $productId;
    public ?ForeignRecordId $couponId;
    public ?ForeignRecordId $transactionId;
    public ?ForeignRecordId $currencyItemId;
    public ?ForeignRecordId $productCurrencyItemId;
    public string $smallImagePath;
    public string $largeImagePath;

    public function __construct(
        string $ctime,
        int $crand,
        string $name,
        string $description,
        vPrice $productPrice,
        vPrice $stockPrice,
        vPrice $price,
        ?string $currencyItemName,
        bool $checkedOut,
        bool $removed,
        vRecordId $cartId,
        vRecordId $productId,
        ?vRecordId $couponId,
        ?vRecordId $transactionId,
        ?vRecordId $currencyItemId,
        ?vRecordId $productCurrencyItemId,
        string $smallImagePath,
        string $largeImagePath
    ) {
        parent::__construct($ctime, $crand);

        $this->name = $name;
        $this->description = $description;
        $this->productPrice = $productPrice;
        $this->stockPrice = $stockPrice;
        $this->price = $price;
        $this->checkedOut = $checkedOut;
        $this->removed = $removed;
        $this->cartId = $cartId->getForeignRecordId();
        $this->productId = $productId->getForeignRecordId();
        $this->currencyItemName = is_null($currencyItemName) ? null : $currencyItemName;
        $this->couponId = is_null($couponId) ? null : $couponId->getForeignRecordId();
        $this->transactionId = is_null($transactionId) ? null : $transactionId->getForeignRecordId();
        $this->currencyItemId = is_null($currencyItemId) ? null : $currencyItemId->getForeignRecordId();
        $this->productCurrencyItemId = is_null($productCurrencyItemId) ? null : $productCurrencyItemId->getForeignRecordId();
        $this->smallImagePath = $smallImagePath;
        $this->largeImagePath = $largeImagePath;
    }
}


?>