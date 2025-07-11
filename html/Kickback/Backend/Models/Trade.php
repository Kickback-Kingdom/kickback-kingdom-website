<?php

declare(strict_types = 1);

namespace Kickback\Backend\Models;

use Kickback\Backend\Views\vRecordId;

class Trade extends RecordId
{
    public ForeignRecordId $fromAccountId;
    public ForeignRecordId $toAccountId;
    public ForeignRecordId $lootId;
    
    public function __construct(
        vRecordId $fromAccountId,
        vRecordId $toAccountId,
        vRecordId $lootId
    )
    {
        parent::__construct();

        $this->fromAccountId = $fromAccountId->getForeignRecordId();
        $this->toAccountId = $toAccountId->getForeignRecordId();
        $this->lootId = $lootId->getForeignRecordId();
    }
}


?>