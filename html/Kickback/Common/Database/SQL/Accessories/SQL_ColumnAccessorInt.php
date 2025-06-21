<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Accessories;

use Kickback\Common\Database\SQL\Accessories\SQL_ColumnAccessor;

/**
* @extends \ArrayAccess<int|string,int>
* @extends SQL_ColumnAccessor<int>
*/
interface SQL_ColumnAccessorInt extends \ArrayAccess, SQL_ColumnAccessor
{
    /*
    * The precision of the Unix timestamp resulting from the conversion of an SQL DateTime.
    *
    * Explanation:
    *
    * If this integer is being read from a DateTime-typed SQL column,
    * then the resulting integer will be assumed to represent a Unix timestamp
    * that can be converted from the SQL DateTime value.
    *
    * SQL columns don't necessarily contain any information about the units
    * stored in them, so the precision must be provided by methods like
    * `secs()`, `msecs()`, `usecs()`, `hnsecs()`, or `nsecs()`, as are
    * available on an `SQL_ColumnAccessorInt` or `SQL_NullableColumnAccessorInt`.
    *
    * So if `usecs()` is chosen (microseconds), then the resulting integer
    * is assumed to be "the number of microseconds since Unix epoch".
    *
    * If `hnsecs()` is chosen (hectonanoseconds), then the resulting integer
    * is assumed to be "the number of hectonanoseconds since Unix epoch".
    *
    * This `precision` property is a way to expose that internal state:
    * * `secs()`   (seconds) implies a precision of 0
    * * `msecs()`  (milliseconds) implies a precision of 3
    * * `usecs()`  (microseconds) implies a precision of 6
    * * `hnsecs()` (hectonanoseconds) implies a precision of 7
    * * `nsecs()`  (nanoseconds) implies a precision of 9
    */
    //public function precision() : int;

    // /** @param int|string $offset */
    // public function offsetExists(mixed $offset) : bool;
    //
    // /**
    // * @param int|string $offset
    // * @return int
    // */
    // public function offsetGet(mixed $offset) : mixed;
    //
    // /**
    // * @param int|string|null $offset
    // * @param int             $value
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
    * @return ($name is 'secs'       ? SQL_MetaAccessorUnitOfTime :
    *          $name is 'msecs'      ? SQL_MetaAccessorUnitOfTime :
    *          $name is 'usecs'      ? SQL_MetaAccessorUnitOfTime :
    *          $name is 'hnsecs'     ? SQL_MetaAccessorUnitOfTime :
    *          $name is 'nsecs'      ? SQL_MetaAccessorUnitOfTime :
    *          $name is 'enum_class' ? SQL_MetaAccessorEnumClass  :
    *          never)
    */
    //public function __get(string $name): mixed;
}
?>
