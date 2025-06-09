<?php

declare(strict_type = 1);

namespace Kickback\Backend\Views;

use Kickback\Backend\Models\ForeignRecordId;

class vTrade extends vRecordId
{
    public ForeignRecordId $fromAccountId;
    public ForeignRecordId $toAccountId;
    public ForeignRecordId $lootId;

    public function __construct(
        string $ctime,
        int $crand,
        vRecordId $fromAccountId,
        vRecordId $toAccountId,
        vRecordId $lootId
    )
    {
        parent::__construct($ctime, $crand);

        $this->fromAccountId = $fromAccountId->getForeignRecordId();
        $this->toAccountId = $toAccountId->getForeignRecordId();
        $this->lootId = $lootId->getForeignRecordId();
    }
}


?>