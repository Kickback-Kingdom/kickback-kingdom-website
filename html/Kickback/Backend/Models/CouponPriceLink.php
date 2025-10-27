<?php

declare(strict_types = 1);

namespace Kickback\Backend\Models;

class CouponPriceLink extends RecordId
{
    public ?ForeignRecordId $couponId;
    public ?ForeignRecordId $priceId;

    public function __construct()
    {
        parent::__construct();
    }

}


?>