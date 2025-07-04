<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Drivers\MySQLi;

use Kickback\Common\Database\SQL\Drivers\DriverID;

use Kickback\Common\Database\SQL\Drivers\MySQLi\KKMySQLi_StatementDetails;
use Kickback\Common\Database\SQL\Drivers\MySQLi\KKMySQLi_Result;

/**
* @see \Kickback\Common\Database\SQL\SQL_StatementDetails
*
* @phpstan-type DRIVER_ID  DriverID::MySQLi
*/
final class KKMySQLi_Statement extends KKMySQLi_StatementDetails
{
    public function __construct(KKMySQLi_ConnectionDetails $connection, \mysqli_stmt $mysqli_stmt) {
        parent::__construct($connection, $mysqli_stmt);
    }
}

?>
