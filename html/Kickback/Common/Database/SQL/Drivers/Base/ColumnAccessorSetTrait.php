<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Drivers\Base;

use Kickback\Common\Database\SQL\Internal\SQL_ColumnAccessorSet;

use Kickback\Common\Database\SQL\Accessories\SQL_Accessor;
use Kickback\Common\Database\SQL\Accessories\SQL_ColumnAccessor;

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
use Kickback\Common\Database\SQL\Accessories\SQL_MetaAccessorEnumClass;

use Kickback\Common\Database\SQL\SQL_TypeStrictness;

trait ColumnAccessorSetTrait
{
    // Note: $cnon = $column_number_or_name
    // (It is abbreviated here to reduce some of the horizontal scrolling.)
    /** @return ($cnon is null ? SQL_ColumnAccessorBool             : bool      ) */ public function bool(int|string $cnon = null)      : SQL_ColumnAccessorBool|bool                        { return Misc::column_accessor_impl($cnon, $this->column_accessor_set()->bool()); }
    /** @return ($cnon is null ? SQL_ColumnAccessorDateTime         : \DateTime ) */ public function DateTime(int|string $cnon = null)  : SQL_ColumnAccessorDateTime|\DateTime               { return Misc::column_accessor_impl($cnon, $this->column_accessor_set()->DateTime()); }
    /** @return ($cnon is null ? SQL_ColumnAccessorFloat            : float     ) */ public function float(int|string $cnon = null)     : SQL_ColumnAccessorFloat|float                      { return Misc::column_accessor_impl($cnon, $this->column_accessor_set()->float()); }
    /** @return ($cnon is null ? SQL_ColumnAccessorInt              : int       ) */ public function int(int|string $cnon = null)       : SQL_ColumnAccessorInt|int                          { return Misc::column_accessor_impl($cnon, $this->column_accessor_set()->int()); }
    /** @return ($cnon is null ? SQL_ColumnAccessorString           : string    ) */ public function str(int|string $cnon = null)       : SQL_ColumnAccessorString|string                    { return Misc::column_accessor_impl($cnon, $this->column_accessor_set()->string()); }
    /** @return ($cnon is null ? SQL_ColumnAccessorString           : string    ) */ public function string(int|string $cnon = null)    : SQL_ColumnAccessorString|string                    { return Misc::column_accessor_impl($cnon, $this->column_accessor_set()->string()); }
    /** @return ($cnon is null ? SQL_NullableColumnAccessorBool     : ?bool     ) */ public function nbool(int|string $cnon = null)     : SQL_NullableColumnAccessorBool|bool|null           { return Misc::column_accessor_impl($cnon, $this->column_accessor_set()->nbool()); }
    /** @return ($cnon is null ? SQL_NullableColumnAccessorDateTime : ?\DateTime) */ public function nDateTime(int|string $cnon = null) : SQL_NullableColumnAccessorDateTime|\DateTime|null  { return Misc::column_accessor_impl($cnon, $this->column_accessor_set()->nDateTime()); }
    /** @return ($cnon is null ? SQL_NullableColumnAccessorFloat    : ?float    ) */ public function nfloat(int|string $cnon = null)    : SQL_NullableColumnAccessorFloat|float|null         { return Misc::column_accessor_impl($cnon, $this->column_accessor_set()->nfloat()); }
    /** @return ($cnon is null ? SQL_NullableColumnAccessorInt      : ?int      ) */ public function nint(int|string $cnon = null)      : SQL_NullableColumnAccessorInt|int|null             { return Misc::column_accessor_impl($cnon, $this->column_accessor_set()->nint()); }
    /** @return ($cnon is null ? SQL_NullableColumnAccessorString   : ?string   ) */ public function nstr(int|string $cnon = null)      : SQL_NullableColumnAccessorString|string|null       { return Misc::column_accessor_impl($cnon, $this->column_accessor_set()->nstring()); }
    /** @return ($cnon is null ? SQL_NullableColumnAccessorString   : ?string   ) */ public function nstring(int|string $cnon = null)   : SQL_NullableColumnAccessorString|string|null       { return Misc::column_accessor_impl($cnon, $this->column_accessor_set()->nstring()); }

    //// Time-unit accessors that precede either int (timestamp) or DateTime accessors
    //// as a way to provide scale/precision information to integer-valued
    //// Unix timestamps on either side of the data transfer.
    //public function secs()      : SQL_ColumnAccessorUnitOfTime;
    //public function msecs()     : SQL_ColumnAccessorUnitOfTime;
    //public function usecs()     : SQL_ColumnAccessorUnitOfTime;
    //public function hnsecs()    : SQL_ColumnAccessorUnitOfTime;
    //public function nsecs()     : SQL_ColumnAccessorUnitOfTime;
}

?>
