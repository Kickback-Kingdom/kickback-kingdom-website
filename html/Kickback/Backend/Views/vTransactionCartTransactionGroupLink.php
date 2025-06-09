<?php

declare(strict_types=1);

namespace Kickback\Backend\Views;

use \Kickback\Backend\Models\ForeignRecordId;

class vTransactionCartTransactionGroupLink extends vRecordId
{
    public ForeignRecordId $transactionId;
    public ForeignRecordId $cartTransactionGroup;

    public function __construct(
        string $ctime, 
        int $crand,
        vRecordId $transactionId, 
        vRecordId $cartTransactionGroup
        )
    {
        parent::__construct($ctime, $crand);

        $this->transactionId = $transactionId->getForeignRecordId();
        $this->cartTransactionGroup = $cartTransactionGroup->getForeignRecordId();
    }
}

?>