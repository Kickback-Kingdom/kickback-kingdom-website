<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Drivers\Base\Accessories;

use Kickback\Common\Database\SQL\Internal\SQL_ColumnAccessorSet;

use Kickback\Common\Database\SQL\Accessories\SQL_NullableColumnAccessorBool;

use Kickback\Common\Database\SQL\Drivers\Base\Accessories\ColumnAccessorCommon;
use Kickback\Common\Database\SQL\Drivers\Base\Accessories\ColumnAccessorBool;

/**
* @phpstan-import-type kksql_any_supported_type from SQL_ColumnAccessorSet
*
* @extends ColumnAccessorCommon<?bool>
* @implements \ArrayAccess<int|string,?bool>
*/
final class NullableColumnAccessorBool extends ColumnAccessorCommon implements \ArrayAccess, SQL_NullableColumnAccessorBool
{
    /**
    * @param int|string $offset
    * @return ?bool
    */
    public function offsetGet(mixed $offset) : mixed
    {
        $result = $this->accessor_->offsetGet($offset);
        if ( isset($result) ) {
            return ColumnAccessorBool::process_incoming_value($offset, $result);
        } else {
            return null;
        }
    }
}
?>
