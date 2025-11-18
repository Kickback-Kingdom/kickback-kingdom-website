<?php

declare(strict_types = 1);

namespace Kickback\Backend\Views;

use DateTime;

class vProductReservation extends vRecordId
{
    public ?vRecordId $cartId;
    public ?vRecordId $productId;
    public int $quantity;
    public ?DateTime $expiryTime;
    public ?DateTime $closeTime;

    public function __construct(
        string $ctime = '',
        int $crand = -1,
        ?vRecordId $cartId = null, 
        ?vRecordId $productId = null, 
        int $quantity = 0, 
        ?DateTime $expiryTime = null, 
        ?DateTime $closeTime = null
        )
    {
        parent::__construct($ctime, $crand);

        $this->cartId = $cartId;
        $this->productId =  $productId;

        $this->quantity = $quantity;

        $this->expiryTime = $expiryTime;
        $this->closeTime = $closeTime;
    }

}

?>