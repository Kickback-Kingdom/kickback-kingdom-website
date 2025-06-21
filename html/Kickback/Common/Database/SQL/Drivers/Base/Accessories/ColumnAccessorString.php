<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Drivers\Base\Accessories;

use Kickback\Common\Database\SQL\Internal\SQL_ColumnAccessorSet;

use Kickback\Common\Database\SQL\Accessories\SQL_ColumnAccessorString;

use Kickback\Common\Database\SQL\Drivers\Base\Accessories\ColumnAccessorCommon;


/**
* @extends ColumnAccessorCommon<string>
* @implements \ArrayAccess<int|string,string>
*/
final class ColumnAccessorString extends ColumnAccessorCommon implements \ArrayAccess, SQL_ColumnAccessorString
{
    /**
    * @param int|string $offset
    * @return string
    */
    public function offsetGet(mixed $offset) : mixed
    {
        $result = $this->accessor_->offsetGet($offset);
        self::enforce_not_null($offset, $result);
        return self::process_incoming_value($offset, $result);
    }

    public static function process_incoming_value(int|string $column_number_or_name, mixed $value) : string
    {
        if ( is_string($value) ) {
            return $value;
        } else
        if ( $value instanceof \DateTime ) {
            // Ex: 2000-01-01 00:00:00 +0000
            return $value->format('Y-m-d H:i:s O');
        } else
        if ( is_bool($value) || is_int($value) || is_float($value) ) {
            return strval($value);
        } else
        {
            throw self::conversion_not_implemented($column_number_or_name, $value, 'string');
        }
    }
}
?>
