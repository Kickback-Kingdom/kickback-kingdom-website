<?php

declare(strict_types=1);

namespace Kickback\Backend\Models;

use \Kickback\Backend\Views\vRecordId;


class Cart extends RecordId
{  
    public bool $checkedOut;
    public ForeignRecordId $accountId;
    public ForeignRecordId $storeId;

    public function __construct(vRecordId $accountId, vRecordId $storeId, bool $checkedOut = false)
    {
        parent::__construct();

        $this->checkedOut = $checkedOut;
        $this->storeId = new ForeignRecordId($storeId->ctime, $storeId->crand);
        $this->accountId = new ForeignRecordId($accountId->ctime, $accountId->crand);
    }

}

?>