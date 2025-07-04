<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL;

use Kickback\Common\Attributes\KickbackGetter;

use Kickback\Common\Database\SQL\Drivers\DriverID;

use Kickback\Common\Database\SQL\SQL_DriverMetadata;

use Kickback\Common\Database\SQL\SQL_ConnectionDetails;
use Kickback\Common\Database\SQL\SQL_Result;

/**
* @template DRIVER_ID of DriverID::*
* @extends SQL_DriverMetadata<DRIVER_ID>
*/
interface SQL_StatementDetails extends SQL_DriverMetadata
{
    /**
    * Returns the underlying `mysqli` object, but ONLY if this statement
    * object is actually backed by a `mysqli` driver.
    *
    * If this statement is provided by a different driver, then the
    * variable passed as $underlying_statement will be `null` after the
    * call to this function.
    *
    * @throws void
    *
    * @param ?\mysqli_stmt $underlying_statement
    *
    * @phpstan-assert-if-true  \mysqli_stmt $underlying_statement
    * @phpstan-assert-if-false null         $underlying_statement
    */
    public function to_mysqli(?\mysqli_stmt &$underlying_statement) : bool;

    public function close() : bool;

    public function dispose() : void;
    public function disposed() : bool;

}

?>
