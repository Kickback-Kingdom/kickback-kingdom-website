<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Drivers\Base;

use Kickback\Common\Database\SQL\Drivers\DriverID;
use Kickback\Common\Database\SQL\SQL_DriverMetadata;

/**
* @template DRIVER_ID of DriverID::*
* @extends SQL_DriverMetadata<DRIVER_ID>
*/
trait BaseDriverMetadataTrait
{
    // Optimization: allow other classes to read $driver_id_value directly,
    // as this could theoretically reduce dynamic dispatch overhead from
    // having to do a vtable lookup on `::driver_id()`.
    /**  @var DRIVER_ID  **/
    public readonly int $driver_id_value;
    /**  @return DRIVER_ID  **/
    public final function driver_id() : int { return $this->driver_id_value; }
    /**  @return DRIVER_ID  **/
    protected abstract function driver_id_definition() : int;

    // Ideally, we'd do this:
    // private function setup_driver_metadata_trait() : void {
    //     $this->driver_id_value = $this->driver_id_definition();
    // }
    //
    // Unfortunately, this leads to PHPStan `property.readOnlyAssignNotInConstructor`
    // errors because the `$this->driver_id_value` variable MUST be assigned
    // within the constructor, not just in a function called from the constructor.
}

?>
