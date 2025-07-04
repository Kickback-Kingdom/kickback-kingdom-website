<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL;

use Kickback\Common\Attributes\KickbackGetter;

use Kickback\Common\Database\SQL\Drivers\DriverID;

use Kickback\Common\Database\SQL\SQL_DriverMetadata;

use Kickback\Common\Database\SQL\SQL_ConnectionDetails;
use Kickback\Common\Database\SQL\SQL_StatementDetails;
use Kickback\Common\Database\SQL\SQL_ResultDetails;

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
use Kickback\Common\Database\SQL\Accessories\SQL_MetaAccessorEnumClass;

/**
* This interface represents a single row returned from an SQL query.
*
* It provides type-safe access to the row contents, thus ensuring that
* analysis tools (such as PHPStan) will be able to determine exactly
* what kind of data the calling code expects when querying databases.
*
* Examples:
* ```
* // Using conventional array notation.
* $row = $results->fetch();
* $player->name            = $row->string['name'];
* $player->strength        = $row->int['strength'];
* $player->dexterity       = $row->int['dexterity'];
* $player->intelligence    = $row->int['intelligence'];
* $player->location->x     = $row->int['location$x'];
* $player->location->y     = $row->int['location$y'];
* $player->last_login_time = $row->datetime['last_login'];
*
* // Using field notation.
* // This works whenever the result's SQL field name is also a valid
* // PHP identifier. Otherwise, array notation must be used.
* $row = $results->fetch();
* $player->name            = $row->string->name;
* $player->strength        = $row->int->strength;
* $player->dexterity       = $row->int->dexterity;
* $player->intelligence    = $row->int->intelligence;
* $player->location->x     = $row->int['location$x'];
* $player->location->y     = $row->int['location$y'];
* $player->last_login_time = $row->datetime->last_login;
* ```
*
* When reading from a column/cell, a type conversion may occur
* if the SQL driver does not deliver a value that is exactly the same
* (PHP) type as the type requested from the `SQL_Row` object.
*
* The rules for such type conversions are as follows:
* * For reading `bool` from a string columns:
*     The strings '', '0', and 'false' will convert to boolean `false`,
*     while the strings '1' (and other non-zero integers) and 'true'
*     will convert to boolean `true`.
*     Other strings will result in a \DomainException being thrown.
* * For reading `bool` from integer or decimal columns:
*     The value 0 will be `false`, and any non-zero values will be `true`.
* * For reading `float` from string columns:
*     Whitespace (ASCII) is stripped from both ends of the string, then
*     a conversion will be attempted using
*     `filter_var($column, FILTER_VALIDATE_FLOAT)`
*     If conversion fails, a \DomainException will be thrown.
* * For reading `int` from string columns:
*     Whitespace (ASCII) is stripped from both ends of the string, then
*     a conversion will be attempted using
*     `filter_var($column, FILTER_VALIDATE_INT)`.
*     If conversion fails, a \DomainException will be thrown.
* * For reading `string` from any other type of column:
*     It will be stringized using string interpolation or equivalent,
*     i.e. `return "$column"`. For all scalar types, this will be guaranteed
*     to succeed. DateTime objects, if provided by the SQL driver (unlikely,
*     but technically possible), will be formatted like so:
*     `YYYY-MM-DD hh:mm:ss +0000`
* * For reading `int` from a datetime column:
*     The `int` accessor does not provide this feature directly;
*     but a unit-of-time accessor allows integer output with its `timestamp` accessor.
*     If precision/units aren't provided, then a \BadMethodCallException will be thrown.
*     If precision/units are provided (ex: `$row->hnsecs->timestamp['my_datetime']`),
*     then the datetime will be converted to a unix timestamp with
*     the given precision (in the example, it'd be hectonanoseconds since Unix Epoch).
*     If the database column metadata or value does not contain timezone information,
*     then it is assumed to be in the UTC timezone.
* * For reading `datetime` from an integer or decimal column:
*     If precision/units aren't provided, then a \BadMethodCallException will be thrown.
*     If precision/units are provided (ex: `$row->usecs->DateTime['my_timestamp']`),
*     then the source column will be assumed to be a unix timestamp with the
*     given precision (in the example, it'd be microseconds since Unix Epoch)
*     and that will be converted into a `DateTime` object.
*     The resulting `DateTime` object will be in the UTC timezone.
*     NOTE: PHP \DateTime objects have only `microsecond` resolution.
*     Any precision beyond a microsecond level will be truncated
*     during a conversion to a \DateTime object. If preservation of data
*     is desired, then it is recommended to use an `int` internally
*     (on 64-bit systems) to represent timestamps/datetimes, and only
*     convert it to something else when displaying approximate datetime
*     to the user.
*
* These conversions are invalid, and will result in a \TypeError being thrown:
* * `DateTime` to `bool`
* * `DateTime` to `float`
* * `float` to `bool`
* * `float` to `DateTime`
* * `bool` to `float`
* * `bool` to `DateTime`
* * `int` to `DateTime`
*
* A \TypeError will only be thrown if the conversion fails.
*
* @phpstan-import-type kksql_any_supported_type   from SQL_ColumnAccessorSet
* @phpstan-import-type all_column_accessor_names  from SQL_ColumnAccessorSet
*
* @phpstan-type unit_of_time_names    'secs'|'msecs'|'hnsecs'|'usecs'|'nsecs'
* @phpstan-type validator_names       'enum_class'
* @phpstan-type kksql_all_stem_names  all_column_accessor_names|unit_of_time_names|validator_names
*
* @template DRIVER_ID of DriverID::*
* @extends SQL_DriverMetadata<DRIVER_ID>
*/
interface SQL_Row extends SQL_ColumnAccessorSet, SQL_DriverMetadata
{
    /* NOTE: All of the (definitions for) accessor methods,
    * ex: `int()`, `string()`, `bool()`, etc,
    * are provided by extending the `SQL_ColumnAccessorSet` interface.
    */

    /**
    * @throws void
    * @return SQL_ConnectionDetails<DRIVER_ID>
    */
    #[KickbackGetter]
    public function connection() : SQL_ConnectionDetails;

    /**
    * @throws void
    * @return SQL_StatementDetails<DRIVER_ID>
    */
    #[KickbackGetter]
    public function statement() : SQL_StatementDetails;

    /**
    * @throws void
    * @return SQL_ResultDetails<DRIVER_ID>
    */
    #[KickbackGetter]
    public function result() : SQL_ResultDetails;

    // Time-unit accessors that precede either int (timestamp) or DateTime accessors
    // as a way to provide scale/precision information to integer-valued
    // Unix timestamps on either side of the data transfer.
    /* TODO
    public function secs()      : SQL_MetaAccessorUnitOfTime;
    public function msecs()     : SQL_MetaAccessorUnitOfTime;
    public function usecs()     : SQL_MetaAccessorUnitOfTime;
    public function hnsecs()    : SQL_MetaAccessorUnitOfTime;
    public function nsecs()     : SQL_MetaAccessorUnitOfTime;
    */

    /**
    * This yields an accessor, similar to `$row->int()`, but validates the
    * incoming SQL data against possibilities that are listed as constants
    * in a PHP class (an "enum class").
    *
    * An "enum class" is just a PHP class whose sole purpose is to define
    * a list of constants (an enumeration). Typically such classes
    * can't (shouldn't be) instantiated, as they are simply a place for constants.
    *
    * Here is an example of what such a class might look like:
    * ```
    * final class MyEnum
    * {
    *     use \Kickback\Common\StaticClassTrait;
    *
    *     public const int FOO = 0;
    *     public const int BAR = 1;
    *     public const int BAZ = 2;
    *     public const int XYZ = 42;
    * }
    * ```
    *
    * It is suggested to use the `get_class` function to acquire the
    * Fully Qualified Name that the accessor needs in order to validate
    * the value returned from the column.
    *
    * Examples:
    * ```
    * $a = $row->enum_class(get_class(MyEnum))->int['column_name'];
    * $b = $row->enum_class[get_class(MyEnum)]->int['column_name'];
    * assert(is_int($a) && is_int($b) && is_int($c));
    * assert($a === $b && $b === $c);
    * ```
    *
    * More detailed notes:
    * The `::class` operator can potentially work as well, though it has some
    * quirks. It doesn't match letter casing to actual class name. It expands at
    * "compile" time before autoloading, so it's just the concatenation of
    * the namespace declared at the top of the file and the class name to the
    * left of the `::class` operator. Meanwhile, `get_class` will return the
    * FQN after it is autoloaded and fully resolved, and the capitalization will
    * exactly match how the class and its namespace are defined.
    *
    * @param class-string $class_fqn
    */
    public function enum_class(string $class_fqn)  : SQL_MetaAccessorEnumClass;

    /**
    * Avoid using this unless it is really necessary. This function has poorer
    * type-safety than accessing data directly from the `SQL_Row` object,
    * and this function requires an unnecessary memory allocation.
    *
    * This function converts the row into a PHP numeric and associative array.
    * In other words: the resulting array is indexed both by column number
    * and by column name.
    *
    * This is important in at least these 2 situations:
    * * When compatibility with other SQL APIs or abstraction layers is needed.
    * * When the row's contents must be stored past the current iteration of the result set.
    *     (e.g. before calling `$results->next()`)
    *
    * @return array<int|string,kksql_any_supported_type|null>
    */
    public function to_array() : array;

    /**
    * Removes all data from the `SQL_Row` object without deallocating memory.
    */
    public function clear() : void;

    /**
    * The `__get` and `__isset` magic methods implement properties that correspond
    * to the methods `bool()`, `DateTime()`, `float()`, `int()`, and `string()`,
    * as well as their nullable counterparts `nbool()`, `nDateTime()`,
    * `nfloat()`, `nint()`, and `nstring()`.
    *
    * Additional accessor-stems permitted:
    * * Unit-of-time specifiers, such as `secs()`, `msecs()`,
    *     `usecs()`, `hnsecs()`, and `nsecs()`.
    * * The validator `enum_class(...)`
    *
    * This allows us to write more concise access chains. For example,
    * `$row->int()['foo']` can instead be written as `$row->int['foo']`.
    *
    * Note that setting and unsetting are not allowed. This would be against
    * the intent of the `SQL_Row` interface: it is a mechanism for delivering
    * SQL data to the caller, for an SQL query that has already executed.
    * There is nowhere for data to go if it is set here by the caller.
    * This interface is also not intended as a way to persist state that
    * originates outside of SQL; there are better ways to do that, and using
    * this interface for such things would likely become a source of bugs.
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
    public function __get(string $name): mixed;

    /**
    * @see __get
    *
    * @param kksql_all_stem_names $name
    */
    public function __isset(string $name): bool;

    //public function __call(string $name, array $arguments): mixed

    public function __toString() : string;

    public function dispose() : void;
    public function disposed() : bool;
}

?>
