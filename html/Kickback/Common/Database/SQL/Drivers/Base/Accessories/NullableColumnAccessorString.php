<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Drivers\Base\Accessories;

use Kickback\Common\Database\SQL\Internal\SQL_ColumnAccessorSet;

use Kickback\Common\Database\SQL\Accessories\SQL_NullableColumnAccessorString;

use Kickback\Common\Database\SQL\Drivers\Base\Accessories\ColumnAccessorCommon;
use Kickback\Common\Database\SQL\Drivers\Base\Accessories\ColumnAccessorString;

/**
* @extends ColumnAccessorCommon<?string>
* @implements \ArrayAccess<int|string,?string>
*/
final class NullableColumnAccessorString extends ColumnAccessorCommon implements \ArrayAccess, SQL_NullableColumnAccessorString
{
    /**
    * @param int|string $offset
    * @return ?string
    */
    public function offsetGet(mixed $offset) : mixed
    {
        $result = $this->accessor_->offsetGet($offset);
        if ( isset($result) ) {
            return ColumnAccessorString::process_incoming_value($offset, $result);
        } else {
            return null;
        }
    }
}
?>
