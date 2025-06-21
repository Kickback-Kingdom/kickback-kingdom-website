<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Accessories;

use Kickback\Common\Database\SQL\Accessories\SQL_ColumnAccessorDateTime;
use Kickback\Common\Database\SQL\Accessories\SQL_ColumnAccessorInt;
use Kickback\Common\Database\SQL\Accessories\SQL_NullableColumnAccessorDateTime;
use Kickback\Common\Database\SQL\Accessories\SQL_NullableColumnAccessorInt;

use Kickback\Common\Database\SQL\Accessories\SQL_MetaAccessorEnumClass;

interface SQL_MetaAccessorUnitOfTime extends SQL_Accessor
{
    public function DateTime(int|string $column_number_or_name = null)   : SQL_ColumnAccessorDateTime|\DateTime;
    public function timestamp(int|string $column_number_or_name = null)  : SQL_ColumnAccessorInt|int;
    public function nDateTime(int|string $column_number_or_name = null)  : SQL_NullableColumnAccessorDateTime|\DateTime|null;
    public function ntimestamp(int|string $column_number_or_name = null) : SQL_NullableColumnAccessorInt|int|null;

    /**
    * The precision of the Unix timestamp whenever a timestamp-to-DateTime
    * or DateTime-to-timestamp conversion is involved in accessing the column.
    *
    * Explanation:
    *
    * SQL columns don't necessarily contain any information about the units
    * stored in them, so the precision must be provided by methods like
    * `secs()`, `msecs()`, `usecs()`, `hnsecs()`, or `nsecs()`, as are
    * available on an `SQL_Row`.
    *
    * So if `usecs()` is chosen (microseconds), then the timestamp's integer
    * is assumed to be "the number of microseconds since Unix epoch".
    *
    * If `hnsecs()` is chosen (hectonanoseconds), then the timestamp's integer
    * is assumed to be "the number of hectonanoseconds since Unix epoch".
    *
    * This `precision` property is a way to expose that internal state:
    * * `secs()`   (seconds) implies a precision of 0
    * * `msecs()`  (milliseconds) implies a precision of 3
    * * `usecs()`  (microseconds) implies a precision of 6
    * * `hnsecs()` (hectonanoseconds) implies a precision of 7
    * * `nsecs()`  (nanoseconds) implies a precision of 9
    */
    public function precision() : int;

    /**
    * @see \Kickback\Common\Database\SQL\SQL_Row::enum_class
    */
    public function enum_class(string $class_fqn = null)  : SQL_MetaAccessorEnumClass|int;
}
?>
