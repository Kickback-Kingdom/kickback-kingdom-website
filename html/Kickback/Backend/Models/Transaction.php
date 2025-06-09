<?php

declare(strict_types = 1);

namespace Kickback\Backend\Models;

use \Kickback\Backend\Views\vRecordId;
use \Kickback\Backend\Views\vPrice;

class Transaction extends RecordId
{
    public int $amount;
    public ForeignRecordId $currencyItem;
    public bool $payed;
    public bool $complete;
    public bool $void;
    public ForeignRecordId $accountId;

    public function __construct(
        int $amount, 
        vRecordId $currencyItem, 
        vRecordId $accountId, 
        bool $payed = false, 
        bool $complete = false,
        bool $void = false,
    )
    {
        parent::__construct();

        $this->amount = $amount;
        $this->currencyItem = $currencyItem->getForeignRecordId();
        $this->accountId = new ForeignRecordId($accountId->ctime, $accountId->crand);
        $this->payed = $payed;
        $this->complete = $complete;
        $this->void = $void;
    }
}



?>