<?php

declare(strict_types = 1);

namespace Kickback\Backend\Views;

class vCouponAccountUse extends vRecordId
{
    public ?vCoupon $coupon;
    public ?vRecordId $accountId;

    public ?int $remainingUses;
    public ?int $accountTimesUsed;

    public function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }

}


?>