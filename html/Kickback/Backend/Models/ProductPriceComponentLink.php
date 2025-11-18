<?php

declare(strict_types = 1);

namespace Kickback\Backend\Models;

class ProductPriceComponentLink extends recordId
{
    public ?ForeignRecordId $productId;
    public ?ForeignRecordId $priceId;

    public function __construct()
    {
        parent::__construct();
    }
}

?>