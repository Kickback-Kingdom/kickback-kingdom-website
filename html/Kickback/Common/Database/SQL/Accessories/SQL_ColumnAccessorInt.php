<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Accessories;

use Kickback\Common\Database\SQL\Accessories\SQL_ColumnAccessor;

/**
* @extends \ArrayAccess<int|string,int>
* @extends SQL_ColumnAccessor<int>
*/
interface SQL_ColumnAccessorInt extends \ArrayAccess, SQL_ColumnAccessor
{
}
?>
