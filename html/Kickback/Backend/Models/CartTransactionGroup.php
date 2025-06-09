<?php

namespace Kickback\Backend\Models;

use \Kickback\Backend\Views\vRecordId;

class CartTransactionGroup extends recordId
{
    public bool $payed;
    public bool $completed;
    public bool $void;
    public ForeignRecordId $cartId;

    public function __construct(
        vRecordId $cartId,
        bool $payed = false,
        bool $completed = false,
        bool $void = false
    )
    {
        parent::__construct();

        $this->payed = $payed;
        $this->completed = $completed;
        $this->void = $void;
        $this->cartId = $cartId->getForeignRecordId();
    }
}

?>