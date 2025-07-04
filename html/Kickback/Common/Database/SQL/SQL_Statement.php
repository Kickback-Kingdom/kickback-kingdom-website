<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL;

use Kickback\Common\Database\SQL\Drivers\DriverID;

use Kickback\Common\Database\SQL\SQL_Result;
use Kickback\Common\Database\SQL\SQL_StatementDetails;

/**
* @see SQL_StatementDetails
*
* @template DRIVER_ID of DriverID::*
* @extends SQL_StatementDetails<DRIVER_ID>
*/
interface SQL_Statement extends SQL_StatementDetails
{

}

?>
