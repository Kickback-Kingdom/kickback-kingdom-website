<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Drivers\Base\Accessories;

use Kickback\Common\Database\SQL\Internal\SQL_ColumnAccessorSet;

use Kickback\Common\Database\SQL\Accessories\SQL_ColumnAccessorFloat;

use Kickback\Common\Database\SQL\Drivers\Base\Accessories\ColumnAccessorCommon;


/**
* @phpstan-import-type kksql_any_supported_type from SQL_ColumnAccessorSet
*
* @extends ColumnAccessorCommon<float>
* @implements \ArrayAccess<int|string,float>
*/
final class ColumnAccessorFloat extends ColumnAccessorCommon implements \ArrayAccess, SQL_ColumnAccessorFloat
{
    /**
    * @param int|string $offset
    * @return float
    */
    public function offsetGet(mixed $offset) : mixed
    {
        $result = $this->accessor_->offsetGet($offset);
        self::enforce_not_null($offset, $result);
        return self::process_incoming_value($offset, $result);
    }

    public static function process_incoming_value(int|string $column_number_or_name, mixed $value) : float
    {
        if ( is_float($value) ) {
            return $value;
        } else
        if ( is_string($value) )
        {
            $as_float = filter_var(trim($value), FILTER_VALIDATE_FLOAT);
            if ( $as_float !== false ) {
                return $as_float;
            } else {
                throw new \DomainException(
                    "Error when reading from column `$column_number_or_name`: ".
                    "Could not parse string `$value` into a floating point value. "
                );
            }
        }
        else
        if ( is_int($value) ) {
            return floatval($value);
        } else
        if ( $value instanceof \DateTime ) {
            throw self::conversion_unsupported($column_number_or_name,'DateTime','float');
        } else
        if ( is_bool($value) ) {
            throw self::conversion_unsupported($column_number_or_name,'bool','float');
        } else
        {
            throw self::conversion_not_implemented($column_number_or_name, $value, 'float');
        }
    }
}
?>
