<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Drivers\MySQLi;

use Kickback\Common\Database\SQL\Drivers\DriverID;
use Kickback\Common\Database\SQL\Drivers\Base\BaseDriverMetadataTrait;

/**
* @phpstan-type DRIVER_ID  DriverID::MySQLi
* @extends BaseDriverMetadataTrait<DRIVER_ID>
*/
trait KKMySQLi_DriverMetadataTrait
{
    /**  @return DRIVER_ID  **/
    protected final function driver_id_definition() : int {
        return DriverID::MySQLi;
    }
}

?>
