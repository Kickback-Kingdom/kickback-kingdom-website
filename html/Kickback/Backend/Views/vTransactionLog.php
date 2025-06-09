<?php

declare(strict_types=1);

namespace Kickback\Backend\Views;

use \Kickback\Backend\Views\vRecordId;

use \Kickback\Backend\Models\ForeignRecordId;

class vTransactionLog extends vRecordId
{
    public string $accountFirstName;
    public string $accountLastName;
    public string $username;
    public string $description;
    public string $jsonString;
    public ForeignRecordId $accountId;

    public function __construct(
        string $accountFirstName, 
        string $accountLastName, 
        string $username,
        string $description, 
        string $jsonString,
        vRecordId $accountId
        )
    {
        $this->accountFirstName = $accountFirstName;
        $this->accountLastName = $accountLastName;
        $this->username = $username;
        $this->description = $description;
        $this->jsonString = $jsonString;

        $this->accountId = new ForeignRecordId($accountId->ctime, $accountId->crand);
    }

}

?>