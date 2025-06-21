<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Drivers\Base\Accessories;

use Kickback\Common\Database\SQL\Internal\SQL_ColumnAccessorSet;

use Kickback\Common\Database\SQL\Accessories\SQL_ColumnAccessorBool;

use Kickback\Common\Database\SQL\Drivers\Base\Accessories\ColumnAccessorCommon;


/**
* @phpstan-import-type kksql_any_supported_type from SQL_ColumnAccessorSet
*
* @extends ColumnAccessorCommon<bool>
* @implements \ArrayAccess<int|string,bool>
*/
final class ColumnAccessorBool extends ColumnAccessorCommon implements \ArrayAccess, SQL_ColumnAccessorBool
{
    /**
    * @param int|string $offset
    * @return bool
    */
    public function offsetGet(mixed $offset) : mixed
    {
        $result = $this->accessor_->offsetGet($offset);
        self::enforce_not_null($offset, $result);
        return self::process_incoming_value($offset, $result);
    }

    public static function process_incoming_value(int|string $column_number_or_name, mixed $value) : bool
    {
        if ( is_bool($value) ) {
            return $value;
        } else
        if ( is_string($value) )
        {
            $value = trim($value);
            if (strlen($value) === 0
            || '0' === $value
            ||  0 === strcasecmp($value, 'false')
            ||  0 === strcasecmp($value, 'no')
            ) {
                return false;
            }

            $as_int = filter_var($value, FILTER_VALIDATE_INT);
            if (($as_int !== false && $as_int !== 0)
            || 0 === strcasecmp($value, 'true')
            || 0 === strcasecmp($value, 'yes')
            ) {
                return true;
            }

            throw new \DomainException(
                "Error when reading from column `$column_number_or_name`: ".
                "Attempt to convert string `$value` into a `bool` (true|false) value. ".
                "The only strings allowed are '', '0', '1' (and other non-zero integers), 'false', 'true', 'no', and 'yes'.");
        }
        else
        if ( is_int($value) ) {
            return boolval($value);
        } else
        if ( $value instanceof \DateTime ) {
            throw self::conversion_unsupported($column_number_or_name,'DateTime','bool');
        } else
        if ( is_float($value) ) {
            throw self::conversion_unsupported($column_number_or_name,'float','bool');
        } else
        {
            throw self::conversion_not_implemented($column_number_or_name, $value, 'bool');
        }
    }
}
?>
