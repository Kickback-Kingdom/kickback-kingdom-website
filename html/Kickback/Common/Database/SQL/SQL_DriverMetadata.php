<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL;

use Kickback\Common\Database\SQL\Drivers\DriverID;

// * @template DriverT of SQL_DriverMetadata
/**
* @template DRIVER_ID of DriverID::*
*/
interface SQL_DriverMetadata
{
    // /**
    // * @return DriverID::*
    // */
    /** @return DRIVER_ID */
    public function driver_id() : int;
}

?>
