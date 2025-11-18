<?php

declare(strict_types = 1);

namespace Kickback\Backend\Models;

use DateTime;
use Kickback\Backend\Views\vRecordId;

class LootReservation extends recordId
{

    public ?ForeignRecordId $lootId;
    public int $quantity;
    public ?DateTime $expiryTime;
    public ?DateTime $closeTime;

    public function __construct(
        ?vRecordId $lootId = null, 
        ?int $quantity = null, 
        ?DateTime $expiryTime = null, 
        ?DateTime $closeTime = null
        )
    {
        parent::__construct();

        $this->lootId = is_null($lootId) ?  null : $lootId->getForeignRecordId();

        $this->quantity = is_null($quantity) ? 1 : $quantity;

        $this->expiryTime = $expiryTime;
        $this->closeTime = $closeTime;
    }

}

?>