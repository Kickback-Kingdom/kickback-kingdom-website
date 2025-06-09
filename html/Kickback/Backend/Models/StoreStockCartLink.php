<?php

declare(strict_types = 1);

namespace Kickback\Backend\Models;

use \Kickback\Backend\Models\ForeignRecordId;

use \Kickback\Backend\Views\vPrice;
use \Kickback\Backend\Views\vRecordId;

class StoreStockCartLink extends RecordId
{
    public vPrice $price;
    public bool $checkedOut;
    public bool $removed;
    public ForeignRecordId $storeStockId;
    public ForeignRecordId $cartId;
    public ?ForeignRecordId $currencyItemId;
    public ?ForeignRecordId $couponId;
    public ?ForeignRecordId $transactionId;

    public function __construct(
        vPrice $price, 
        bool $checkedOut, 
        bool $removed, 
        vRecordId $storeStockId,
        vRecordId $cartId,
        ?vRecordId $currencyItemId = null,
        ?vRecordId $couponId = null,
        ?vRecordId $transactionId = null
        )
    {
        parent::__construct();

        $this->price = $price;
        $this->checkedOut = $checkedOut;
        $this->removed = $removed;
        $this->storeStockId = $storeStockId->getForeignRecordId();
        $this->cartId = $cartId->getForeignRecordId();
        $this->currencyItemId = is_null($currencyItemId) ? null : $currencyItemId->getForeignRecordId();
        $this->couponId = is_null($couponId) ? null : $couponId->getForeignRecordId();
        $this->transactionId = is_null($transactionId) ? null : $transactionId->getForeignRecordId();
    }

}

?>