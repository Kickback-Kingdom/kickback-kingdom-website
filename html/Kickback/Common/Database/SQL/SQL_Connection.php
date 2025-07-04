<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL;

use Kickback\Common\Database\SQL\Drivers\DriverID;

use Kickback\Common\Database\SQL\SQL_Statement;
use Kickback\Common\Database\SQL\SQL_ConnectionDetails;

/**
* @see SQL_ConnectionDetails
*
* @template DRIVER_ID of DriverID::*
* @extends SQL_ConnectionDetails<DRIVER_ID>
*/
interface SQL_Connection extends SQL_ConnectionDetails
{
    /**
    * @param     ?SQL_Statement<DRIVER_ID> $statement
    * @param-out SQL_Statement<DRIVER_ID>  $statement
    */
    public function prepare(?SQL_Statement &$statement, string $query) : void;
}

?>
