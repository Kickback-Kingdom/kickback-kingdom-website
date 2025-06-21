<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL;

interface TypeStrictness
{
    /**
    * When reading from a column/cell, the SQL datatype will be ignored
    * and a conversion will be attempted using some simple rules.
    *
    * The rules are as follows:
    * * For reading `bool` from a string columns:
    *     The strings '', '0', and 'false' will convert to boolean `false`,
    *     while the strings '1' and 'true' will convert to boolean `true`.
    *     Other strings will result in a \TypeError being thrown.
    * * For reading `bool` from integer or decimal columns:
    *     The value 0 will be `false`, and any non-zero values will be `true`.
    * * For reading `float` from string columns:
    *     Whitespace (ASCII) is stripped from both ends of the string, then
    *     a conversion will be attempted using
    *     `filter_var($column,
    * * For reading `int` from string columns:
    *     Whitespace (ASCII) is stripped from both ends of the string, then
    *     a conversion will be attempted using
    *     `filter_var($column, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE)`.
    *     If conversion fails, a \TypeError will be thrown.
    * * For reading `string` from any other type of column:
    *     It will be stringized using string interpolation or equivalent,
    *     i.e. `return "$column"`. For all scalar types, this will be guaranteed
    *     to succeed.
    * * For reading `int` from a datetime column:
    *     If precision/units aren't provided, then a \TypeError will be thrown.
    *     If precision/units are provided (ex: `$row->int->hnsecs['my_datetime']`),
    *     then the datetime will be converted to a unix timestamp with
    *     the given precision (in the example, it'd be hectonanoseconds since Unix Epoch).
    *     If the database column metadata or value does not contain timezone information,
    *     then it is assumed to be in the UTC timezone.
    * * For reading `datetime` from an integer or decimal column:
    *     If precision/units aren't provided, then a \TypeError will be thrown.
    *     If precision/units are provided (ex: `$row->datetime->hnsecs['my_timestamp']`),
    *     then source column will be assumed to be a unix timestamp with the
    *     given precision (in the example, it'd be hectonanoseconds since Unix Epoch)
    *     and that will be converted into a `DateTime` object.
    *     The resulting `DateTime` object will be in the UTC timezone.
    *
    * A \TypeError will only be thrown if the conversion fails.
    */
    public const SIMPLE_CONVERSIONS = 0;

    /**
    * When reading from a column/cell, the requested return type and
    * the source SQL datatype of that column/cell must be compatible.
    *
    * If they aren't, then a \TypeError will be thrown.
    */
    public const EXACT              = 1;


    /**
    * When reading from a column/cell, the SQL datatype will be ignored
    * and a conversion will be attempted using PHP's implicit type conversion
    * rules.
    *
    * A \TypeError will only be thrown if the PHP type conversion fails.
    */
    public const LOOSEST            = 2;
}

?>
