<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL;

use Kickback\Common\Database\SQL\Drivers\DriverID;

/**
* @template DRIVER_ID of DriverID::*
*/
interface SQL_DriverMetadata
{
    /** @return DRIVER_ID */
    public function driver_id() : int;
}

?>
