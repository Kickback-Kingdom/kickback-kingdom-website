<?php

declare(strict_types = 1);

namespace Kickback\Backend\Views;

use DateTime;
use Exception;
use InvalidArgumentException;

class vCoupon extends vRecordId
{
    public ?string $code = null;
    public ?string $description = null;
    public ?int $requiredQuantityOfProduct = null;
    public ?vRecordId $productId = null;
    public ?int $timesUsed = null;
    public ?int $maxTimesUsed = null;
    public ?int $maxTimesUsedPerAccount = null;
    public ?DateTime $expiryTime = null;
    public ?bool $removed = null;

    public array $priceComponents;

    public function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);


    }

}


?>