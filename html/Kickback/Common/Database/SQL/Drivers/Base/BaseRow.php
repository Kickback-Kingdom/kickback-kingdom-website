<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Drivers\Base;

use Kickback\Common\Attributes\KickbackGetter;

use Kickback\Common\Database\SQL\Drivers\DriverID;
use Kickback\Common\Database\SQL\Drivers\Base\BaseDriverMetadataTrait;
use Kickback\Common\Database\SQL\Drivers\Base\BaseConnectionDetails;
use Kickback\Common\Database\SQL\Drivers\Base\BaseStatementDetails;
use Kickback\Common\Database\SQL\Drivers\Base\BaseResultDetails;
use Kickback\Common\Database\SQL\SQL_Row;

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

use Kickback\Common\Database\SQL\Drivers\Base\ColumnAccessorSetTrait;
use Kickback\Common\Database\SQL\Drivers\Base\Misc;
use Kickback\Common\Database\SQL\Drivers\Base\RawColumnAccessorSetImplementation;
use Kickback\Common\Database\SQL\Drivers\Base\RawColumnAccessorSetInterface;

use Kickback\Common\Database\SQL\Drivers\Base\Accessories\MetaAccessorEnumClass;

use Kickback\Common\Exceptions\NotImplementedMethodException;

/**
* @see \Kickback\Common\Database\SQL\SQL_Row
*
* @phpstan-import-type kksql_any_supported_type from SQL_ColumnAccessorSet
* @phpstan-import-type kksql_all_stem_names     from SQL_Row
*
* @template DRIVER_ID of DriverID::*
* @implements SQL_Row<DRIVER_ID>
*/
abstract class BaseRow implements SQL_Row
{
    /** @use BaseDriverMetadataTrait<DRIVER_ID> */
    use BaseDriverMetadataTrait;

    use ColumnAccessorSetTrait;

    // This field (specifically, its getter below)
    // is used by the `ColumnAccessorSetTrait` trait.
    private RawColumnAccessorSetImplementation $column_accessor_set_;

    /**
    * This getter allows the column accessors to be overridden
    * by an implementer if such a thing is ever required.
    */
    protected function column_accessor_set() : RawColumnAccessorSetInterface {
        return $this->column_accessor_set_;
    }

    /**
    * @param BaseResultDetails<DRIVER_ID> $result
    * @param SQL_ColumnAccessor<mixed>    $column_accessor_mixed
    */
    public function __construct(BaseResultDetails $result,  SQL_ColumnAccessor  $column_accessor_mixed)
    {
        $this->driver_id_value = $this->driver_id_definition();
        $this->result_ = $result;
        $this->column_accessor_set_ = new RawColumnAccessorSetImplementation($column_accessor_mixed);
    }

    // TODO: Delete
    // // Optimization: allow other classes to read $driver_id_value directly,
    // // as this could theoretically reduce dynamic dispatch overhead from
    // // having to do a vtable lookup on `::driver_id()`.
    // /**  @var DriverID::*  **/
    // public readonly int $driver_id_value;
    // /**  @return DriverID::*  **/
    // public final function driver_id() : int { return $this->driver_id_value; }
    // /**  @return DriverID::*  **/
    // protected abstract function driver_id_definition() : int;
    //
    // /**
    // * @var ($this->disposed_ ? BaseResultDetails : null)
    // */

    /** @var ?BaseResultDetails<DRIVER_ID> $result_ */
    private ?BaseResultDetails $result_;

    /**
    * @throws void
    * @return BaseConnectionDetails<DRIVER_ID>
    */
    #[KickbackGetter]
    public function connection() : BaseConnectionDetails {
        return $this->result()->connection();
    }

    /**
    * @throws void
    * @return BaseStatementDetails<DRIVER_ID>
    */
    #[KickbackGetter]
    public function statement() : BaseStatementDetails {
        return $this->result()->statement();
    }

    /**
    * @throws void
    * @return BaseResultDetails<DRIVER_ID>
    */
    #[KickbackGetter]
    public function result() : BaseResultDetails {
        assert(!$this->disposed());
        return $this->result_;
    }

    private ?MetaAccessorEnumClass $accessor_EnumClass_ = null;
    protected function accessor_EnumClass() : SQL_MetaAccessorEnumClass {
        return Misc::accessor_property_impl($this->accessor_EnumClass_, fn() => new MetaAccessorEnumClass($this->column_accessor_set()));
    }

    /**
    * @see \Kickback\Common\Database\SQL\SQL_Row::enum_class
    * @param class-string $class_fqn
    */
    public function enum_class(string $class_fqn)  : SQL_MetaAccessorEnumClass {
        // TODO: When this gets executed, it should pass the `$class_fqn`
        //   data into the MetaAccessorEnumClass object, which should then
        //   pass that data onto the column accessor, which should then
        //   use that data during value conversion to do enum-based validation.
        return $this->accessor_EnumClass();
    }

    // /**
    // * @see \Kickback\Common\Database\SQL\SQL_Row::to_array
    // * @return array<int|string,kksql_any_supported_type>
    // */
    // public function to_array() : array {
    //     return []; // TODO!
    // }

    /**
    * @see \Kickback\Common\Database\SQL\SQL_Row::__get
    *
    * @param kksql_all_stem_names $name
    *
    * @return ($name is 'int'        ? SQL_ColumnAccessorInt              :
    *          $name is 'str'        ? SQL_ColumnAccessorString           :
    *          $name is 'string'     ? SQL_ColumnAccessorString           :
    *          $name is 'nint'       ? SQL_NullableColumnAccessorInt      :
    *          $name is 'nstr'       ? SQL_NullableColumnAccessorString   :
    *          $name is 'nstring'    ? SQL_NullableColumnAccessorString   :
    *          $name is 'DateTime'   ? SQL_ColumnAccessorDateTime         :
    *          $name is 'nDateTime'  ? SQL_NullableColumnAccessorDateTime :
    *          $name is 'bool'       ? SQL_ColumnAccessorBool             :
    *          $name is 'nbool'      ? SQL_NullableColumnAccessorBool     :
    *          $name is 'float'      ? SQL_ColumnAccessorFloat            :
    *          $name is 'nfloat'     ? SQL_NullableColumnAccessorFloat    :
    *          $name is 'secs'       ? SQL_MetaAccessorUnitOfTime         :
    *          $name is 'msecs'      ? SQL_MetaAccessorUnitOfTime         :
    *          $name is 'usecs'      ? SQL_MetaAccessorUnitOfTime         :
    *          $name is 'hnsecs'     ? SQL_MetaAccessorUnitOfTime         :
    *          $name is 'nsecs'      ? SQL_MetaAccessorUnitOfTime         :
    *          $name is 'enum_class' ? SQL_MetaAccessorEnumClass          :
    *          never)
    */
    public function __get(string $name): mixed
    {
        switch($name)
        {
            case 'int'        : return $this->int();
            case 'str'        : return $this->str();
            case 'string'     : return $this->string();
            case 'nint'       : return $this->nint();
            case 'nstr'       : return $this->nstr();
            case 'nstring'    : return $this->nstring();
            case 'DateTime'   : return $this->DateTime();
            case 'nDateTime'  : return $this->nDateTime();
            case 'bool'       : return $this->bool();
            case 'nbool'      : return $this->nbool();
            case 'float'      : return $this->float();
            case 'nfloat'     : return $this->nfloat();
            case 'secs'       : throw new NotImplementedMethodException();
            case 'msecs'      : throw new NotImplementedMethodException();
            case 'usecs'      : throw new NotImplementedMethodException();
            case 'hnsecs'     : throw new NotImplementedMethodException();
            case 'nsecs'      : throw new NotImplementedMethodException();
            case 'enum_class' : throw new NotImplementedMethodException(); // TODO: Interface that requires ArrayAccess (to get the fqn) to acquire the MetaAccessorEnumClass.
            default: throw new \BadMethodCallException();
        }
    }

    /**
    * @see __get
    *
    * @param kksql_all_stem_names $name
    */
    public function __isset(string $name): bool
    {
        switch($name)
        {
            case 'int'        : return true;
            case 'str'        : return true;
            case 'string'     : return true;
            case 'nint'       : return true;
            case 'nstr'       : return true;
            case 'nstring'    : return true;
            case 'DateTime'   : return true;
            case 'nDateTime'  : return true;
            case 'bool'       : return true;
            case 'nbool'      : return true;
            case 'float'      : return true;
            case 'nfloat'     : return true;
            case 'secs'       : return false;
            case 'msecs'      : return false;
            case 'usecs'      : return false;
            case 'hnsecs'     : return false;
            case 'nsecs'      : return false;
            case 'enum_class' : return false;
            default: throw new \BadMethodCallException();
        }
    }

    //public function __call(string $column_name, array $arguments): mixed

    //public function __toString() : string;

    private bool  $disposed_ = false;
    public function dispose() : void
    {
        $this->disposed_ = true;
        $this->result_ = null;
    }

    /**
    * @phpstan-assert-if-true  null               $this->result_
    * @phpstan-assert-if-false BaseResultDetails  $this->result_
    */
    public function disposed() : bool
    {
        return $this->disposed_;
    }
}

?>
