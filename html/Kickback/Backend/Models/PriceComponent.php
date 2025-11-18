<?php

declare(strict_types = 1);

namespace Kickback\Backend\Models;

use Kickback\Backend\Models\Enums\CurrencyCode;
use Kickback\Backend\Views\vRecordId;

class PriceComponent extends RecordId
{
    public int $amount;
    public ?CurrencyCode $currencyCode;
    public ?vRecordId $itemId;

    public function __construct(
        int $amount = 0, 
        ?CurrencyCode $currencyCode = null, 
        ?vRecordId $itemId = null)
    {
        parent::__construct();

        $this->amount = $amount;
        $this->currencyCode = $currencyCode;
        $this->itemId = $itemId;
    }
}

?>