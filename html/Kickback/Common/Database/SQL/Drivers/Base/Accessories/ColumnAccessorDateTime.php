<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Drivers\Base\Accessories;

use Kickback\Common\Exceptions\NotImplementedException;

use Kickback\Common\Database\SQL\Internal\SQL_ColumnAccessorSet;

use Kickback\Common\Database\SQL\Accessories\SQL_ColumnAccessorDateTime;

use Kickback\Common\Database\SQL\Drivers\Base\Accessories\ColumnAccessorCommon;


/**
* @phpstan-import-type kksql_any_supported_type from SQL_ColumnAccessorSet
*
* @extends ColumnAccessorCommon<\DateTime>
* @implements \ArrayAccess<int|string,\DateTime>
*/
final class ColumnAccessorDateTime extends ColumnAccessorCommon implements \ArrayAccess, SQL_ColumnAccessorDateTime
{
    public ?int $precision_ = null;

    public function precision() : ?int {
        return $this->precision_;
    }

    // TODO: Timezones?
    /**
    * @param int|string $offset
    * @return \DateTime
    */
    public function offsetGet(mixed $offset) : mixed
    {
        $source = $this->accessor_->offsetGet($offset);

        $e = null;
        try {
            self::enforce_not_null($offset, $source);
            $result = self::process_incoming_value($offset, $source, $this->precision_);
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

    public static function process_incoming_value(
        int|string $column_number_or_name, mixed $value, ?int $precision) : \DateTime
    {
        if ( $value instanceof \DateTime ) {
            return $value;
        } else
        if ( is_string($value) ) {
            // TODO: Conversion from date strings is probably BORKED
            // TODO: DateTime expects some special syntax, which SQL columns are unlikely to have!
            return new \DateTime(trim($value));
        } else
        if ( is_int($value) )
        {
            $provide_error_message_context =
            function(string $emsg) use($column_number_or_name) {
                return "Error when reading from column `$column_number_or_name`: ".$emsg;
            };
            return self::convert_timestamp_to_datetime($value, $precision, $provide_error_message_context);
        }
        else
        if ( is_bool($value) ) {
            throw self::conversion_unsupported($column_number_or_name, 'bool', 'DateTime');
        } else
        if ( is_float($value) ) {
            throw self::conversion_unsupported($column_number_or_name, 'float', 'DateTime');
        } else
        {
            throw self::conversion_not_implemented($column_number_or_name, $value, 'DateTime');
        }
    }

    // TODO: Unittesting
    // (Also this thing with the callable is kinda jank.)
    /**
    * @param callable(string):string $provide_error_message_context
    */
    private static function convert_timestamp_to_datetime(int $timestamp, ?int $precision, callable $provide_error_message_context) : \DateTime
    {
        if ( !isset($precision) ) {
            $emsg = $provide_error_message_context(
                'Timestamp units (secs, msecs, usecs, hnsecs, nsecs) '.
                'must be provided when reading a DateTime from a timestamp '.
                'stored in SQL as an integer or decimal.'
            );
            throw new \BadMethodCallException($emsg);
        }
        else if ( $precision > 0 )
        {
            if ( $precision === 6 ) {
                $timestamp_usecs = $timestamp;
            }
            else if ( $precision > 6 ) {
                $exponent = $precision - 6;
                $timestamp_usecs = intdiv($timestamp, pow(10,$exponent));
            } else /* $precision < 6 */ { // This case is mostly for `msecs`, otherwise probably pretty rare.
                $exponent = 6 - $precision;
                $timestamp_usecs = $timestamp * pow(10,$exponent);
            }
            $secs = intdiv($timestamp_usecs, 1000000);
            $usecs = $timestamp_usecs % 1000000;
            $datestr = sprintf('@%d.%06d', $secs, $usecs);
            return new \DateTime($datestr);
        }
        else if ( $precision === 0 ) {
            return new \DateTime('@'.strval($timestamp));
        } else /* $precision < 0 */ {
            $emsg = $provide_error_message_context('Timestamps with negative precision are currently unsupported.');
            throw new NotImplementedException($emsg);
        }
    }
}
?>
