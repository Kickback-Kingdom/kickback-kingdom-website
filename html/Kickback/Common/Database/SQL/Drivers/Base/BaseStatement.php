<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Drivers\Base;

use Kickback\Common\Attributes\KickbackGetter;

use Kickback\Common\Database\SQL\Drivers\DriverID;
use Kickback\Common\Database\SQL\Drivers\Base\BaseStatementDetails;
use Kickback\Common\Database\SQL\Drivers\Base\BaseResult;

/**
* @see \Kickback\Common\Database\SQL\SQL_StatementDetails
*
* @template DRIVER_ID of DriverID::*
* @extends BaseStatementDetails<DRIVER_ID>
*/
abstract class BaseStatement extends BaseStatementDetails
{
    /**
    * @param BaseConnectionDetails<DRIVER_ID> $connection
    */
    public function __construct(BaseConnectionDetails $connection) {
        parent::__construct($connection);
    }
}

?>
