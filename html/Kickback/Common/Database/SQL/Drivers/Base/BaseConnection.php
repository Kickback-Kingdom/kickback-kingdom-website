<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Drivers\Base;

use Kickback\Common\Attributes\KickbackGetter;

use Kickback\Common\Database\SQL\Drivers\DriverID;
use Kickback\Common\Database\SQL\Drivers\Base\BaseConnectionDetails;
use Kickback\Common\Database\SQL\Drivers\Base\BaseStatement;
use Kickback\Common\Database\SQL\SQL_Statement;

/**
* @see \Kickback\Common\Database\SQL\SQL_ConnectionDetails
*
* @template DRIVER_ID of DriverID::*
* @extends BaseConnectionDetails<DRIVER_ID>
*/
abstract class BaseConnection extends BaseConnectionDetails
{
    public function __construct() {
        parent::__construct();
    }

    /**
    * @param     ?SQL_Statement<DRIVER_ID> $statement
    * @param-out BaseStatement<DRIVER_ID>  $statement
    */
    public function prepare(?SQL_Statement &$statement, string $query) : void
    {
        // TODO!
        return;
    }
}

?>
