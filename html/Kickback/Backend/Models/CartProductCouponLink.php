<?php

declare(strict_types = 1);

namespace Kickback\Backend\Models;

class CartProductCouponLink extends RecordId
{
    public ?ForeignRecordId $couponId;
    public ?ForeignRecordId $cartProductId;

    public function __construct()
    {
        parent::__construct();
    }

}


?>