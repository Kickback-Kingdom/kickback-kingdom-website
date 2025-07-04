<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Drivers\Base;

use Kickback\Common\Attributes\KickbackGetter;

use Kickback\Common\Database\SQL\Drivers\DriverID;
use Kickback\Common\Database\SQL\Drivers\Base\BaseDriverMetadataTrait;
use Kickback\Common\Database\SQL\SQL_Connection;

/**
* @see \Kickback\Common\Database\SQL\SQL_ConnectionDetails
*
* @template DRIVER_ID of DriverID::*
* @implements SQL_Connection<DRIVER_ID>
*/
abstract class BaseConnectionDetails implements SQL_Connection
{
    /** @use BaseDriverMetadataTrait<DRIVER_ID> */
    use BaseDriverMetadataTrait;

    public function __construct() {
        $this->driver_id_value = $this->driver_id_definition();
    }

    // TODO: Delete
    // // Optimization: allow other classes to read $driver_id_value directly,
    // // as this could theoretically reduce dynamic dispatch overhead from
    // // having to do a vtable lookup on `::driver_id()`.
    // /**  @var DriverID::*  **/
    // public readonly int $driver_id_value;
    // /**  @return DriverID::*  **/
    // public final function driver_id() : int { return $this->driver_id_value; }
    // /**  @return DriverID::*  **/
    // protected abstract function driver_id_definition() : int;
    //
    // public function __construct() {
    //     $this->driver_id_value = $this->driver_id_definition();
    // }

    /**
    * @see Kickback\Common\Database\SQL\SQL_ConnectionDetails::to_mysqli
    *
    * @throws void
    *
    * @param ?\mysqli $underlying_connection
    *
    * @phpstan-assert-if-true  \mysqli $underlying_connection
    * @phpstan-assert-if-false null    $underlying_connection
    */
    public function to_mysqli(?\mysqli &$underlying_connection) : bool {
        $underlying_connection = null;
        return false;
    }

    private bool  $disposed_ = false;
    public function dispose() : void
    {
        $this->disposed_ = true;
        /** TODO: dispose/detach and nullify any statement references "owned" by this. */
    }

    public function disposed() : bool
    {
        return $this->disposed_;
    }
}

?>
