<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Drivers\Base;

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

//use Kickback\Common\Database\SQL\Accessories\SQL_MetaAccessorUnitOfTime;

interface RawColumnAccessorSetInterface
{
    public function bool()      : SQL_ColumnAccessorBool;
    public function DateTime()  : SQL_ColumnAccessorDateTime;
    public function float()     : SQL_ColumnAccessorFloat;
    public function int()       : SQL_ColumnAccessorInt;
    public function string()    : SQL_ColumnAccessorString;
    public function nbool()     : SQL_NullableColumnAccessorBool;
    public function nDateTime() : SQL_NullableColumnAccessorDateTime;
    public function nfloat()    : SQL_NullableColumnAccessorFloat;
    public function nint()      : SQL_NullableColumnAccessorInt;
    public function nstring()   : SQL_NullableColumnAccessorString;

    // These will probably be moved to a different accessor set,
    // as they do not yield values directly, and do not have the
    // same interface as the "column" accessors.
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
