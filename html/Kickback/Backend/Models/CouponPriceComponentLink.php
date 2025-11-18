<?php

declare(strict_types = 1);

namespace Kickback\Backend\Models;

class CouponPriceComponentLink extends RecordId
{
    public ?ForeignRecordId $couponId;
    public ?ForeignRecordId $priceComponentId;

    public function __construct()
    {
        parent::__construct();
    }

}


?>