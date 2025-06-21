<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Drivers\Base\Accessories;

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

use Kickback\Common\Database\SQL\Accessories\SQL_MetaAccessorEnumClass;
use Kickback\Common\Database\SQL\Accessories\SQL_MetaAccessorUnitOfTime;

use Kickback\Common\Database\SQL\Drivers\Base\ColumnAccessorSetTrait;
use Kickback\Common\Database\SQL\Drivers\Base\RawColumnAccessorSetInterface;

// NOTE: This should be a `final` class, but putting `final` makes PHPStan fail before performing analysis.
// We can switch it over to `final` once all of those methods are implemented.
final class MetaAccessorEnumClass implements SQL_MetaAccessorEnumClass
{
    use ColumnAccessorSetTrait;

    // This field is used by the `ColumnAccessorSetTrait` trait.
    // (Note: If you need to override the column accessor set, you only need
    // to do so in `BaseRow`. The property here is populated from the return
    // value of `BaseRow`'s `column_accessor_set()` function, so modifying
    // that one will cause this one to also return a different value.)
    private function column_accessor_set() : RawColumnAccessorSetInterface {
        return $this->column_accessor_set_;
    }

    public function __construct(
        private  RawColumnAccessorSetInterface  $column_accessor_set_
    )
    {}

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
