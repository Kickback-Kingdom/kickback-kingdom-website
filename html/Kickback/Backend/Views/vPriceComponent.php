<?php

declare(strict_types = 1);

namespace Kickback\Backend\Views;

use Kickback\Backend\Models\Enums\CurrencyCode;
use Kickback\Backend\Models\Item;

class vPriceComponent extends vRecordId
{
    public int $amount;
    public? vItem $item;
    public? CurrencyCode $currencyCode;

    public function __construct(
        string $ctime = '', 
        int $crand = 0, 
        int $amount = 0, 
        ?vItem $item = null, 
        ?CurrencyCode $currencyCode = null
        )
    {
        parent::__construct($ctime, $crand);

        $this->amount = $amount;
        $this->item = $item;
        $this->currencyCode = $currencyCode;
    }
}

?>