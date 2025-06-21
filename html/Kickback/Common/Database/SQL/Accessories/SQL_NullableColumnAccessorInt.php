<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Accessories;

use Kickback\Common\Database\SQL\Accessories\SQL_ColumnAccessor;

/**
* @extends \ArrayAccess<int|string,?int>
* @extends SQL_ColumnAccessor<?int>
*/
interface SQL_NullableColumnAccessorInt extends \ArrayAccess, SQL_ColumnAccessor
{
    //public function secs(int|string $column_number_or_name = null)       : SQL_NullableColumnAccessorTimestamp|int|null;
    //public function msecs(int|string $column_number_or_name = null)      : SQL_NullableColumnAccessorTimestamp|int|null;
    //public function usecs(int|string $column_number_or_name = null)      : SQL_NullableColumnAccessorTimestamp|int|null;
    //public function hnsecs(int|string $column_number_or_name = null)     : SQL_NullableColumnAccessorTimestamp|int|null;
    //public function nsecs(int|string $column_number_or_name = null)      : SQL_NullableColumnAccessorTimestamp|int|null;
    //
    ///** @see SQL_ColumnAccessorInt::enum_class */
    //public function enum_class(string $class_fqn = null, string $column_name = null)  : SQL_NullableColumnAccessorEnumClass|int|null;
    //
    ///** @see SQL_ColumnAccessorInt::precision */
    //public function precision() : int;

    // /** @param int|string $offset */
    // public function offsetExists(mixed $offset) : bool;
    //
    // /**
    // * @param int|string $offset
    // * @return ?int
    // */
    // public function offsetGet(mixed $offset) : mixed;
    //
    // /**
    // * @param int|string|null $offset
    // * @param ?int            $value
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
    * @return ($name is 'secs'       ? SQL_NullableColumnAccessorTimestamp :
    *          $name is 'msecs'      ? SQL_NullableColumnAccessorTimestamp :
    *          $name is 'usecs'      ? SQL_NullableColumnAccessorTimestamp :
    *          $name is 'hnsecs'     ? SQL_NullableColumnAccessorTimestamp :
    *          $name is 'nsecs'      ? SQL_NullableColumnAccessorTimestamp :
    *          $name is 'enum_class' ? SQL_NullableColumnAccessorEnumClass :
    *          never)
    */
    //public function __get(string $name): mixed;
}
?>
