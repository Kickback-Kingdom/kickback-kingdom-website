<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Drivers\Base\Accessories;

use Kickback\Common\Database\SQL\Internal\SQL_ColumnAccessorSet;

use Kickback\Common\Database\SQL\Accessories\SQL_NullableColumnAccessorDateTime;

use Kickback\Common\Database\SQL\Drivers\Base\Accessories\ColumnAccessorCommon;
use Kickback\Common\Database\SQL\Drivers\Base\Accessories\ColumnAccessorDateTime;

/**
* @phpstan-import-type kksql_any_supported_type from SQL_ColumnAccessorSet
*
* @extends ColumnAccessorCommon<?\DateTime>
* @implements \ArrayAccess<int|string,?\DateTime>
*/
final class NullableColumnAccessorDateTime extends ColumnAccessorCommon implements \ArrayAccess, SQL_NullableColumnAccessorDateTime
{
    public ?int $precision_ = null;

    public function precision() : ?int {
        return $this->precision_;
    }

    // TODO: Timezones?
    /**
    * @param int|string $offset
    * @return ?\DateTime
    */
    public function offsetGet(mixed $offset) : mixed
    {
        $source = $this->accessor_->offsetGet($offset);

        $result = null;
        $e = null;
        try {
            if ( isset($source) ) {
                $result = ColumnAccessorDateTime::process_incoming_value($offset, $source, $this->precision_);
            }
        } catch (\Exception $exc) {
            $e = $exc;
        }

        // Try to prevent data from bleeding from one column to another.
        // THIS IS CRITICAL and must run regardless of whether the conversion succeeded or not.
        // (So don't throw any exceptions before this point.)
        $this->precision_ = null;

        if ( !isset($e) ) {
            return $result;
        } else {
            throw $e;
        }
    }
}
?>
