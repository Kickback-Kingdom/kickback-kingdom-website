<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Drivers\Base\Accessories;

use Kickback\Common\Database\SQL\Internal\SQL_ColumnAccessorSet;

use Kickback\Common\Database\SQL\Accessories\SQL_NullableColumnAccessorInt;

use Kickback\Common\Database\SQL\Drivers\Base\Accessories\ColumnAccessorCommon;
use Kickback\Common\Database\SQL\Drivers\Base\Accessories\ColumnAccessorInt;

/**
* @extends ColumnAccessorCommon<?int>
* @implements \ArrayAccess<int|string,?int>
*/
final class NullableColumnAccessorInt extends ColumnAccessorCommon implements \ArrayAccess, SQL_NullableColumnAccessorInt
{
    /**
    * @param int|string $offset
    * @return ?int
    */
    public function offsetGet(mixed $offset) : mixed
    {
        $result = $this->accessor_->offsetGet($offset);
        if ( isset($result) ) {
            return ColumnAccessorInt::process_incoming_value($offset, $result);
        } else {
            return null;
        }
    }
}
?>
