<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Accessories;

use Kickback\Common\Database\SQL\Accessories\SQL_ColumnAccessor;

/**
* @extends \ArrayAccess<int|string,?string>
* @extends SQL_ColumnAccessor<?string>
*/
interface SQL_NullableColumnAccessorString extends \ArrayAccess, SQL_ColumnAccessor
{
}
?>
