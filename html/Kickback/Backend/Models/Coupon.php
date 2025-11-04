<?php

declare(strict_types = 1);

namespace Kickback\Backend\Models;

use DateTime;
use Exception;
use InvalidArgumentException;

class Coupon extends RecordId
{
    public ?string $code = null;
    public ?string $description = null;
    public ?int $requiredQuantityOfProduct = null;
    public ?ForeignRecordId $productId = null;
    public ?int $timesUsed = null;
    public ?int $maxTimesUsed = null;
    public ?int $maxTimesUsedPerAccount = null;
    public ?DateTime $expiryTime = null;
    public ?bool $removed = null;

    public array $priceComponents = [];

    public function __construct()
    {
        parent::__construct();
    }

}


?>