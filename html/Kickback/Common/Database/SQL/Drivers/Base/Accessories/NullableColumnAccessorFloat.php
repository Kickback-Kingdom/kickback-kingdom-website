<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Drivers\Base\Accessories;

use Kickback\Common\Database\SQL\Internal\SQL_ColumnAccessorSet;

use Kickback\Common\Database\SQL\Accessories\SQL_NullableColumnAccessorFloat;

use Kickback\Common\Database\SQL\Drivers\Base\Accessories\ColumnAccessorCommon;
use Kickback\Common\Database\SQL\Drivers\Base\Accessories\ColumnAccessorFloat;

/**
* @phpstan-import-type kksql_any_supported_type from SQL_ColumnAccessorSet
*
* @extends ColumnAccessorCommon<?float>
* @implements \ArrayAccess<int|string,?float>
*/
final class NullableColumnAccessorFloat extends ColumnAccessorCommon implements \ArrayAccess, SQL_NullableColumnAccessorFloat
{
    /**
    * @param int|string $offset
    * @return ?float
    */
    public function offsetGet(mixed $offset) : mixed
    {
        $result = $this->accessor_->offsetGet($offset);
        if ( isset($result) ) {
            return ColumnAccessorFloat::process_incoming_value($offset, $result);
        } else {
            return null;
        }
    }
}
?>
