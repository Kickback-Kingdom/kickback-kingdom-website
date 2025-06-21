<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Accessories;

use Kickback\Common\Database\SQL\Accessories\SQL_ColumnAccessor;

/**
* @extends \ArrayAccess<int|string,\DateTime>
* @extends SQL_ColumnAccessor<\DateTime>
*/
interface SQL_ColumnAccessorDateTime extends \ArrayAccess, SQL_ColumnAccessor
{
    /*
    public function secs(int|string $column_number_or_name = null)       : SQL_ColumnAccessorDateTime|\DateTime;
    public function msecs(int|string $column_number_or_name = null)      : SQL_ColumnAccessorDateTime|\DateTime;
    public function usecs(int|string $column_number_or_name = null)      : SQL_ColumnAccessorDateTime|\DateTime;
    public function hnsecs(int|string $column_number_or_name = null)     : SQL_ColumnAccessorDateTime|\DateTime;
    public function nsecs(int|string $column_number_or_name = null)      : SQL_ColumnAccessorDateTime|\DateTime;
    */

    /*
    * The precision of the Unix timestamp (in SQL) being converted into a PHP DateTime object.
    *
    * Explanation:
    *
    * If this DateTime is being read from an SQL integer-typed or decimal-typed
    * column, then that integer (or decimal) will be assumed to be a Unix timestamp
    * that can be converted to a DateTime object.
    *
    * SQL columns don't necessarily contain any information about the units
    * stored in them, so the precision must be provided by methods like
    * `secs()`, `msecs()`, `usecs()`, `hnsecs()`, or `nsecs()`.
    *
    * So if `usecs()` is chosen (microseconds), then the integer in the SQL
    * column is assumed to be "the number of microseconds since Unix epoch".
    *
    * If `hnsecs()` is chosen (hectonanoseconds), then the integer in the SQL
    * column is assumed to be "the number of hectonanoseconds since Unix epoch".
    *
    * This `precision` property is a way to expose that internal state:
    * * `secs()`   (seconds) implies a precision of 0
    * * `msecs()`  (milliseconds) implies a precision of 3
    * * `usecs()`  (microseconds) implies a precision of 6
    * * `hnsecs()` (hectonanoseconds) implies a precision of 7
    * * `nsecs()`  (nanoseconds) implies a precision of 9
    *
    * @return ?int  An integer as described above, or `null` if the accessor chain did not assign one.
    */
    public function precision() : ?int;

    // /** @param int|string $offset */
    // public function offsetExists(mixed $offset) : bool;
    //
    // /**
    // * @param int|string $offset
    // * @return \DateTime
    // */
    // public function offsetGet(mixed $offset) : mixed;
    //
    // /**
    // * @param int|string|null $offset
    // * @param \DateTime       $value
    // * @return never
    // */
    // public function offsetSet(mixed $offset, mixed $value) : never;
    //
    // /**
    // * @param int|string $offset
    // * @return never
    // */
    // public function offsetUnset(mixed $offset) : never;

    /*
    * The `__get` and `__isset` magic methods implement properties that correspond
    * to the methods `secs()`, `msecs()`, `usecs()`, `hnsecs()`, and `nsecs()`.
    *
    * This allows us to write more concise access chains. For example,
    * `$row->int()->hnsecs()['foo']` can instead be written as `$row->int->hnsecs['foo']`.
    *
    * Note that setting and unsetting are not allowed. See
    * `Kickback\\Common\\Database\\SQL\\SQL_Row::__get` for details.
    *
    * @param 'secs'|'msecs'|'hnsecs'|'usecs'|'enum_class' $name
    *
    * @return ($name is 'secs'       ? SQL_ColumnAccessorDateTime :
    *          $name is 'msecs'      ? SQL_ColumnAccessorDateTime :
    *          $name is 'usecs'      ? SQL_ColumnAccessorDateTime :
    *          $name is 'hnsecs'     ? SQL_ColumnAccessorDateTime :
    *          $name is 'nsecs'      ? SQL_ColumnAccessorDateTime :
    *          never)
    */
    //public function __get(string $name): mixed;
}
?>
