<?php

declare(strict_types = 1);

namespace Kickback\Backend\Views;

class vCartProductCouponLink extends vRecordId
{
    public ?vRecordId $couponId;
    public ?vRecordId $cartProductId;
    public ?vRecordId $couponAssignmentGroupId;

    public function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }

}


?>