<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Accessories;

use Kickback\Common\Database\SQL\Accessories\SQL_ColumnAccessor;

/**
* @extends \ArrayAccess<int|string,?\DateTime>
* @extends SQL_ColumnAccessor<?\DateTime>
*/
interface SQL_NullableColumnAccessorDateTime extends \ArrayAccess, SQL_ColumnAccessor
{
    //public function secs(int|string $column_number_or_name = null)       : SQL_NullableColumnAccessorDateTime|int|null;
    //public function msecs(int|string $column_number_or_name = null)      : SQL_NullableColumnAccessorDateTime|int|null;
    //public function usecs(int|string $column_number_or_name = null)      : SQL_NullableColumnAccessorDateTime|int|null;
    //public function hnsecs(int|string $column_number_or_name = null)     : SQL_NullableColumnAccessorDateTime|int|null;
    //public function nsecs(int|string $column_number_or_name = null)      : SQL_NullableColumnAccessorDateTime|int|null;
    //
    ///** @see SQL_ColumnAccessorDateTime::precision */
    //public function precision() : int;

    /*
    * The `__get` and `__isset` magic methods implement properties that correspond
    * to the methods `secs()`, `msecs()`, `hnsecs()`, and `usecs()`.
    *
    * This allows us to write more concise access chains. For example,
    * `$row->int()->hnsecs()['foo']` can instead be written as `$row->int->hnsecs['foo']`.
    *
    * Note that setting and unsetting are not allowed. See
    * `Kickback\\Common\\Database\\SQL\\SQL_Row::__get` for details.
    *
    * @param 'secs'|'msecs'|'hnsecs'|'usecs'|'enum_class' $name
    *
    * @return ($name is 'secs'       ? SQL_NullableColumnAccessorDateTime :
    *          $name is 'msecs'      ? SQL_NullableColumnAccessorDateTime :
    *          $name is 'usecs'      ? SQL_NullableColumnAccessorDateTime :
    *          $name is 'hnsecs'     ? SQL_NullableColumnAccessorDateTime :
    *          $name is 'nsecs'      ? SQL_NullableColumnAccessorDateTime :
    *          never)
    */
    //public function __get(string $name): mixed;
}
?>
