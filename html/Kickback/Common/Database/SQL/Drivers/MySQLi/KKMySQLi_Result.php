<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Drivers\MySQLi;

use Kickback\Common\Attributes\KickbackGetter;

use Kickback\Common\Database\SQL\Drivers\DriverID;

use Kickback\Common\Database\SQL\Drivers\MySQLi\KKMySQLi_StatementDetails;
use Kickback\Common\Database\SQL\Drivers\MySQLi\KKMySQLi_ResultDetails;
use Kickback\Common\Database\SQL\Drivers\MySQLi\KKMySQLi_Row;

use Kickback\Common\Database\SQL\Accessories\SQL_BindingMap;
use Kickback\Common\Database\SQL\SQL_Result;
use Kickback\Common\Database\SQL\SQL_Row;

/**
* @see \Kickback\Common\Database\SQL\SQL_ResultDetails
*
* TODO: DElete
* \@extends KKMySQLi_ResultDetails<KKMySQLi_Result,KKMySQLi_Row>
*
* @phpstan-type DRIVER_ID  DriverID::MySQLi
*/
final class KKMySQLi_Result extends KKMySQLi_ResultDetails
{
    /**
    * This must be implemented by the SQL driver wrapper or implementation.
    *
    * It is typically called the first time `BaseResult->current()` is called,
    * and is then reused in subsequent calls to the `current()` method within
    * the same result-set.
    *
    * @return KKMySQLi_Row
    */
    protected function construct_iterator_row() : KKMySQLi_Row
    {
        // TODO
        return new KKMySQLi_Row($this);
    }

    /**
    * @param KKMySQLi_StatementDetails $statement
    */
    public function __construct(KKMySQLi_StatementDetails $statement, \mysqli_result $result)
    {
        parent::__construct($statement, $result);
    }

    // Honestly not even sure if these are going to exist in this class
    // or be implemented this way in the final draft.
    /**
    * @param SQL_BindingMap|class-string ...$options
    */
    public function fetch_next_into_required_fields(object &$object_to_populate, SQL_BindingMap|string ...$options) : void { return; /* TODO */ }
    public function fetch_current_into_required_fields(object &$object_to_populate, SQL_BindingMap|string ...$options) : void { return; /* TODO */ }

    /**
    * @param SQL_BindingMap|class-string ...$options
    *
    * @return int  0 if ALL optional fields were populated, or a positive integer equal to the number of optional fields that could not be filled.
    */
    public function fetch_next_into_optional_fields(object &$object_to_populate, SQL_BindingMap|string ...$options) : int { return 42; /* TODO */ }

    /**
    * @param SQL_Row<DRIVER_ID> $row_to_populate
    *
    * @return bool  `true` if the row is populated; `false` if there is no current row (because all rows have already been fetched)
    */
    public function fetch_next_into_row(SQL_Row &$row_to_populate) : bool { return false; /* TODO */ }
}

?>
