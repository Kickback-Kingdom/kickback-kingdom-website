<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Accessories;

use Kickback\Common\Database\SQL\Accessories\SQL_ColumnAccessor;

/**
* @extends \ArrayAccess<int|string,bool>
* @extends SQL_ColumnAccessor<bool>
*/
interface SQL_ColumnAccessorBool extends \ArrayAccess, SQL_ColumnAccessor
{
}
?>
