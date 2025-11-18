<?php

declare(strict_types = 1);

namespace Kickback\Backend\Models;

class CouponAccountUse extends RecordId
{
    public ?ForeignRecordId $couponId;
    public ?ForeignRecordId $accountId;
    public int $timesUsed;

    public function __construct()
    {
        parent::__construct();

        $timesUsed = 0;
    }

}


?>