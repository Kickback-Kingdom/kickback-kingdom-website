<?php
declare(strict_types=1);

namespace Kickback\Backend\Models;

class ShipmentManifestItem extends RecordId
{
    public string $tracking_number;

    function __construct(string $trackingNumber)
    {
        parent::__construct();
        
        $this->tracking_number = $trackingNumber;
    }
}



?>