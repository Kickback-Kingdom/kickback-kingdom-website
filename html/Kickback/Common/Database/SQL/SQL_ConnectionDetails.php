<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL;

use Kickback\Common\Database\SQL\Drivers\DriverID;

use Kickback\Common\Database\SQL\SQL_DriverMetadata;

/**
* @template DRIVER_ID of DriverID::*
* @extends SQL_DriverMetadata<DRIVER_ID>
*/
interface SQL_ConnectionDetails extends SQL_DriverMetadata
{
    /**
    * Returns the underlying `mysqli` object, but ONLY if this connection
    * object is actually backed by a `mysqli` driver.
    *
    * If this connection is provided by a different driver, then the
    * variable passed as $underlying_connection will be `null` after the
    * call to this function.
    *
    * @throws void
    *
    * @param ?\mysqli $underlying_connection
    *
    * @phpstan-assert-if-true  \mysqli $underlying_connection
    * @phpstan-assert-if-false null    $underlying_connection
    */
    public function to_mysqli(?\mysqli &$underlying_connection) : bool;

    public function close() : bool;

    //public function mysqli_autocommit(bool $enable): bool;
    //public function mysqli_begin_transaction(int $flags = 0, ?string $name = null): bool;
    //public function mysqli_change_user(string $username, #[\SensitiveParameter] string $password, ?string $database): bool;
    //public function mysqli_character_set_name(): string;
    //public function mysqli_close() : bool;
    //public function mysqli_commit(int $flags = 0, ?string $name = null): bool;
    //public function mysqli_rollback(int $flags = 0, ?string $name = null): bool;

    public function dispose() : void;
    public function disposed() : bool;
}

?>
