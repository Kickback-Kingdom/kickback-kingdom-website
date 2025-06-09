<?php

declare(strict_types = 1);

namespace Kickback\Backend\Views;

use \Kickback\Backend\Models\ForeignRecordId;

class vTransaction extends vRecordId
{
    public vPrice $price;
    public string $firstName;
    public string $lastName;
    public bool $payed;
    public bool $complete;
    public bool $void;
    public ForeignRecordId $accountId;

    public function __construct(
        string $ctime, 
        int $crand, 
        vPrice $price, 
        string $firstName, 
        string $lastName, 
        bool $payed, 
        bool $complete, 
        bool $void,
        vRecordId $accountId)
    {
        parent::__construct($ctime, $crand);
        $this->price = $price;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->payed = $payed;
        $this->complete = $complete;
        $this->void = $void;
        $this->accountId = new ForeignRecordId($accountId->ctime, $accountId->crand);
    }
}

?>