<?php

declare(strict_types = 1);

namespace Kickback\Backend\Models;

use \Kickback\Backend\Views\vRecordId;

class TransactionCartTransactionGroupLink extends RecordId
{
    public ForeignRecordId $transactionId;
    public ForeignRecordId $cartTransactionGroupId;

    public function __construct(
        vRecordId $transactionId, 
        vRecordId $cartTransactionGroupId)
    {
        parent::__construct();

        $this->transactionId = $transactionId->getForeignRecordId();
        $this->cartTransactionGroupId = $cartTransactionGroupId->getForeignRecordId();
    }
}


?>