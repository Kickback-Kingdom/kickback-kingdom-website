<?php

declare(strict_types=1);

namespace Kickback\Backend\Views;
use Kickback\Backend\Models\ForeignRecordId;

class vCart extends vRecordId
{
    public bool $checkedOut;
    public ForeignRecordId $storeId;
    public ForeignRecordId $accountId;

    function __construct(
        string $ctime, 
        int $crand, 
        bool $checkedOut, 
        vRecordId $store, 
        vRecordId $account
        )
    {
        parent::__construct($ctime, $crand);
        $this->checkedOut = $checkedOut;
        $this->storeId = new ForeignRecordId($store->ctime, $store->crand);
        $this->accountId = new ForeignRecordId($account->ctime, $account->crand);
    }
}

?>