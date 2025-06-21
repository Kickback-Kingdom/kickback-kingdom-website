<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Accessories;

use Kickback\Common\Database\SQL\Accessories\SQL_ColumnAccessor;

/**
* @extends \ArrayAccess<int|string,?float>
* @extends SQL_ColumnAccessor<?float>
*/
interface SQL_NullableColumnAccessorFloat extends \ArrayAccess, SQL_ColumnAccessor
{
}
?>
