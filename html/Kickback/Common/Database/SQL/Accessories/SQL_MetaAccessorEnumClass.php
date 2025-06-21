<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Accessories;

use Kickback\Common\Database\SQL\Internal\SQL_ColumnAccessorSet;

use Kickback\Common\Database\SQL\Accessories\SQL_ColumnAccessorBool;
use Kickback\Common\Database\SQL\Accessories\SQL_ColumnAccessorDateTime;
use Kickback\Common\Database\SQL\Accessories\SQL_ColumnAccessorFloat;
use Kickback\Common\Database\SQL\Accessories\SQL_ColumnAccessorInt;
use Kickback\Common\Database\SQL\Accessories\SQL_ColumnAccessorString;
use Kickback\Common\Database\SQL\Accessories\SQL_NullableColumnAccessorBool;
use Kickback\Common\Database\SQL\Accessories\SQL_NullableColumnAccessorDateTime;
use Kickback\Common\Database\SQL\Accessories\SQL_NullableColumnAccessorFloat;
use Kickback\Common\Database\SQL\Accessories\SQL_NullableColumnAccessorInt;
use Kickback\Common\Database\SQL\Accessories\SQL_NullableColumnAccessorString;

use Kickback\Common\Database\SQL\Accessories\SQL_MetaAccessorUnitOfTime;

interface SQL_MetaAccessorEnumClass extends SQL_Accessor, SQL_ColumnAccessorSet
{
    public function bool(int|string $column_number_or_name = null)      : SQL_ColumnAccessorBool|bool;
    public function DateTime(int|string $column_number_or_name = null)  : SQL_ColumnAccessorDateTime|\DateTime;
    public function float(int|string $column_number_or_name = null)     : SQL_ColumnAccessorFloat|float;
    public function int(int|string $column_number_or_name = null)       : SQL_ColumnAccessorInt|int;
    public function str(int|string $column_number_or_name = null)       : SQL_ColumnAccessorString|string;
    public function string(int|string $column_number_or_name = null)    : SQL_ColumnAccessorString|string;
    public function nbool(int|string $column_number_or_name = null)     : SQL_NullableColumnAccessorBool|bool|null;
    public function nDateTime(int|string $column_number_or_name = null) : SQL_NullableColumnAccessorDateTime|\DateTime|null;
    public function nfloat(int|string $column_number_or_name = null)    : SQL_NullableColumnAccessorFloat|float|null;
    public function nint(int|string $column_number_or_name = null)      : SQL_NullableColumnAccessorInt|int|null;
    public function nstr(int|string $column_number_or_name = null)      : SQL_NullableColumnAccessorString|string|null;
    public function nstring(int|string $column_number_or_name = null)   : SQL_NullableColumnAccessorString|string|null;

    // TODO
    // // Time-unit accessors that precede either int (timestamp) or DateTime accessors
    // // as a way to provide scale/precision information to integer-valued
    // // Unix timestamps on either side of the data transfer.
    // public function secs()      : SQL_MetaAccessorUnitOfTime;
    // public function msecs()     : SQL_MetaAccessorUnitOfTime;
    // public function usecs()     : SQL_MetaAccessorUnitOfTime;
    // public function hnsecs()    : SQL_MetaAccessorUnitOfTime;
    // public function nsecs()     : SQL_MetaAccessorUnitOfTime;
}
?>
