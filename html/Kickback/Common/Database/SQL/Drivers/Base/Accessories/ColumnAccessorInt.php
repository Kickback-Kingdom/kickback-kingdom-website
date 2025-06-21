<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Drivers\Base\Accessories;

use Kickback\Common\Database\SQL\Internal\SQL_ColumnAccessorSet;

use Kickback\Common\Database\SQL\Accessories\SQL_ColumnAccessorInt;

use Kickback\Common\Database\SQL\Drivers\Base\Accessories\ColumnAccessorCommon;


/**
* @extends ColumnAccessorCommon<int>
* @implements \ArrayAccess<int|string,int>
*/
final class ColumnAccessorInt extends ColumnAccessorCommon implements \ArrayAccess, SQL_ColumnAccessorInt
{
    /**
    * @param int|string $offset
    * @return int
    */
    public function offsetGet(mixed $offset) : mixed
    {
        $result = $this->accessor_->offsetGet($offset);
        self::enforce_not_null($offset, $result);
        return self::process_incoming_value($offset, $result);
    }

    public static function process_incoming_value(int|string $column_number_or_name, mixed $value) : int
    {
        if ( is_int($value) ) {
            return $value;
        } else
        if ( is_string($value) )
        {
            $as_int = filter_var(trim($value), FILTER_VALIDATE_INT);
            if ( $as_int !== false ) {
                return $as_int;
            } else {
                throw new \DomainException(
                    "Error when reading from column `$column_number_or_name`: ".
                    "Could not parse string `$value` into an integer. "
                );
            }
        }
        else
        if ( is_float($value) || is_bool($value) ) {
            return intval($value);
        } else
        if ( $value instanceof \DateTime ) {
            throw self::conversion_unsupported($column_number_or_name,'DateTime','int');
        } else
        {
            throw self::conversion_not_implemented($column_number_or_name, $value, 'int');
        }
    }
}
?>
