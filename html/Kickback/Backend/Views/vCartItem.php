<?php

declare(strict_types = 1);

namespace Kickback\Backend\Views;

class vCartItem extends vRecordId
{
    public bool $removed;
    public bool $checkedOut;    
    public vProduct $product;
    public vCart $cart;
    public ?vCoupon $coupon = null;
    public ?vRecordId $couponGroupAssignmentId;

    public $price;

    public function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }
}

?>