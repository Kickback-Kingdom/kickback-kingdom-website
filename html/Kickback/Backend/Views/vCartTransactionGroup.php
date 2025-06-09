<?php

declare(strict_types=1);

namespace Kickback\Backend\Views;

use \Kickback\Backend\Models\ForeignRecordId;

class vCartTransactionGroup extends vRecordId
{
    public bool $payed;
    public bool $completed;
    public bool $void;
    public ForeignRecordId $cartId;

    public function __construct(
        string $ctime,
        int $crand,
        vRecordid $cartId, 
        bool $payed = false, 
        bool $completed = false,
        bool $void = false
        )
    {
        parent::__construct($ctime, $crand);

        $this->payed = $payed;
        $this->completed = $completed;
        $this->void = $void;
        $this->cartId = $cartId->getForeignRecordId();
    }
}

?>