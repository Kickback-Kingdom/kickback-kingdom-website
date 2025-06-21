<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Drivers\Base;

use Kickback\Common\Database\SQL\Internal\SQL_ColumnAccessorSet;

use Kickback\Common\Database\SQL\SQL_Row;

use Kickback\Common\Database\SQL\Accessories\SQL_Accessor;
use Kickback\Common\Database\SQL\Accessories\SQL_ColumnAccessor;

/**
* @phpstan-import-type kksql_any_supported_type from SQL_ColumnAccessorSet
* @phpstan-import-type kksql_all_stem_names     from SQL_Row
*/
final class Misc
{
    use \Kickback\Common\StaticClassTrait;

    /**
    * @template TAccessor of SQL_Accessor
    * @param ?TAccessor $field
    * @param callable():TAccessor $instantiate
    * @return TAccessor
    */
    public static function accessor_property_impl(?SQL_Accessor &$field, callable $instantiate) : SQL_Accessor
    {
        if ( !isset($field) ) {
            $field = $instantiate();
        }
        return $field;
    }

    /**
    * @template TPrimitive of kksql_any_supported_type|null
    * @template TAccessor of SQL_ColumnAccessor<TPrimitive>
    * @param int|string|null $column_number_or_name
    * @param TAccessor $accessor
    * @return ($column_number_or_name is null ? TAccessor : TPrimitive)
    */
    public static function column_accessor_impl(int|string|null $column_number_or_name, SQL_ColumnAccessor $accessor) : mixed
    {
        if ( !isset($column_number_or_name) ) {
            return $accessor;
        } else {
            return $accessor[$column_number_or_name];
        }
    }
}

?>
