<?php

declare(strict_types = 1);

namespace Kickback\Backend\Views;

use DateTime;
use Kickback\Backend\Models\ForeignRecordId;

class vLootReservation extends vRecordId
{

    public ?ForeignRecordId $lootId;
    public int $quantity;
    public ?DateTime $expiryTime;
    public ?DateTime $closeTime;

    public function __construct(
        string $ctime = '',
        int $crand = -1,
        ?vRecordId $lootId = null, 
        ?int $quantity = 1, 
        ?DateTime $expiryTime = null, 
        ?DateTime $closeTime = null
        )
    {
        parent::__construct($ctime, $crand);

        $this->lootId = is_null($lootId) ?  null : $lootId->getForeignRecordId();

        $this->quantity = is_null($quantity) ? 1 : $quantity;

        $this->expiryTime = $expiryTime;
        $this->closeTime = $closeTime;
    }

}

?>