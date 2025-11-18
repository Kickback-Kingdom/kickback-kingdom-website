<?php

declare(strict_types = 1);

namespace Kickback\Backend\Models;

use DateTime;
use Kickback\Backend\Views\vRecordId;

class ProductReservation extends recordId
{

    public ?ForeignRecordId $cartId;
    public ?ForeignRecordId $productId;
    public int $quantity;
    public ?DateTime $expiryTime;
    public ?DateTime $closeTime;

    public function __construct(
        ?vRecordId $cartId = null, 
        ?vRecordId $productId = null, 
        ?int $quantity = null, 
        ?DateTime $expiryTime = null, 
        ?DateTime $closeTime = null
        )
    {
        parent::__construct();

        $this->cartId = is_null($cartId) ?  null : $cartId->getForeignRecordId();
        $this->productId = is_null($productId) ?  null : $productId->getForeignRecordId();

        $this->quantity = is_null($quantity) ? 1 : $quantity;

        $this->expiryTime = $expiryTime;
        $this->closeTime = $closeTime;
    }

}

?>