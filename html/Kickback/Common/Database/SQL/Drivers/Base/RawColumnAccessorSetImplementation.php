<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Drivers\Base;

use Kickback\Common\Database\SQL\Internal\SQL_ColumnAccessorSet;

use Kickback\Common\Database\SQL\SQL_Row;

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

//use Kickback\Common\Database\SQL\Accessories\SQL_MetaAccessorUnitOfTime;

use Kickback\Common\Database\SQL\Drivers\Base\Accessories\ColumnAccessorBool;
use Kickback\Common\Database\SQL\Drivers\Base\Accessories\ColumnAccessorDateTime;
use Kickback\Common\Database\SQL\Drivers\Base\Accessories\ColumnAccessorFloat;
use Kickback\Common\Database\SQL\Drivers\Base\Accessories\ColumnAccessorInt;
use Kickback\Common\Database\SQL\Drivers\Base\Accessories\ColumnAccessorString;
use Kickback\Common\Database\SQL\Drivers\Base\Accessories\NullableColumnAccessorBool;
use Kickback\Common\Database\SQL\Drivers\Base\Accessories\NullableColumnAccessorDateTime;
use Kickback\Common\Database\SQL\Drivers\Base\Accessories\NullableColumnAccessorFloat;
use Kickback\Common\Database\SQL\Drivers\Base\Accessories\NullableColumnAccessorInt;
use Kickback\Common\Database\SQL\Drivers\Base\Accessories\NullableColumnAccessorString;

//use Kickback\Common\Database\SQL\Drivers\Base\Accessories\MetaAccessorUnitOfTime;

use Kickback\Common\Database\SQL\Drivers\Base\Misc;
use Kickback\Common\Database\SQL\Drivers\Base\RawColumnAccessorSetInterface;

/**
* @phpstan-import-type kksql_any_supported_type from SQL_ColumnAccessorSet
* @phpstan-import-type kksql_all_stem_names     from SQL_Row
*/
final class RawColumnAccessorSetImplementation implements RawColumnAccessorSetInterface
{
    /**
    * @param SQL_ColumnAccessor<mixed> $mixed_
    */
    public function __construct(
        private  SQL_ColumnAccessor  $mixed_
    )
    {}

    private ?SQL_ColumnAccessorBool               $bool_       = null;
    private ?SQL_ColumnAccessorDateTime           $DateTime_   = null;
    private ?SQL_ColumnAccessorFloat              $float_      = null;
    private ?SQL_ColumnAccessorInt                $int_        = null;
    private ?SQL_ColumnAccessorString             $string_     = null;
    private ?SQL_NullableColumnAccessorBool       $nbool_      = null;
    private ?SQL_NullableColumnAccessorDateTime   $nDateTime_  = null;
    private ?SQL_NullableColumnAccessorFloat      $nfloat_     = null;
    private ?SQL_NullableColumnAccessorInt        $nint_       = null;
    private ?SQL_NullableColumnAccessorString     $nstring_    = null;

    public function bool()      : SQL_ColumnAccessorBool               { return Misc::accessor_property_impl($this->bool_,      fn() => new ColumnAccessorBool($this->mixed_)); }
    public function DateTime()  : SQL_ColumnAccessorDateTime           { return Misc::accessor_property_impl($this->DateTime_,  fn() => new ColumnAccessorDateTime($this->mixed_)); }
    public function float()     : SQL_ColumnAccessorFloat              { return Misc::accessor_property_impl($this->float_,     fn() => new ColumnAccessorFloat($this->mixed_)); }
    public function int()       : SQL_ColumnAccessorInt                { return Misc::accessor_property_impl($this->int_,       fn() => new ColumnAccessorInt($this->mixed_)); }
    public function string()    : SQL_ColumnAccessorString             { return Misc::accessor_property_impl($this->string_,    fn() => new ColumnAccessorString($this->mixed_)); }
    public function nbool()     : SQL_NullableColumnAccessorBool       { return Misc::accessor_property_impl($this->nbool_,     fn() => new NullableColumnAccessorBool($this->mixed_)); }
    public function nDateTime() : SQL_NullableColumnAccessorDateTime   { return Misc::accessor_property_impl($this->nDateTime_, fn() => new NullableColumnAccessorDateTime($this->mixed_)); }
    public function nfloat()    : SQL_NullableColumnAccessorFloat      { return Misc::accessor_property_impl($this->nfloat_,    fn() => new NullableColumnAccessorFloat($this->mixed_)); }
    public function nint()      : SQL_NullableColumnAccessorInt        { return Misc::accessor_property_impl($this->nint_,      fn() => new NullableColumnAccessorInt($this->mixed_)); }
    public function nstring()   : SQL_NullableColumnAccessorString     { return Misc::accessor_property_impl($this->nstring_,   fn() => new NullableColumnAccessorString($this->mixed_)); }

}

?>
