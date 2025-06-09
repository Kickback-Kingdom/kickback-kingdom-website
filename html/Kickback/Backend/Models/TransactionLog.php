<?php

declare(strict_types=1);

namespace Kickback\Backend\Models;

use \Kickback\Backend\Views\vRecordId;

use \Kickback\Backend\Models\ForeignRecordId;
use \Kickback\Backend\Models\RecordId;

class TransactionLog extends RecordId
{
    public string $description;
    public string $jsonString;
    public ForeignRecordId $accountId;

    public function __construct(
        string $description,
        string $jsonString, 
        vRecordId $accountId)
    {
        parent::__construct();

        $this->description = $description;
        $this->jsonString = $jsonString;
        $this->accountId = new ForeignRecordId($accountId->ctime, $accountId->crand);
    }
}

?>