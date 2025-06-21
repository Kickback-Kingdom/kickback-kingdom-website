<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Accessories;

use Kickback\Common\Database\SQL\SQL_Row;
use Kickback\Common\Database\SQL\Accessories\SQL_Accessor;
//use Kickback\Common\Database\SQL\Accessories\Internal\SQL_ColumnAccessorTrait;

// * \@phpstan-import-type kksql_any_supported_type from SQL_Row
/**
* @template T
* @extends \ArrayAccess<int|string,T>
*/
interface SQL_ColumnAccessor extends \ArrayAccess, SQL_Accessor
{
    // /** @use SQL_ColumnAccessorTrait<kksql_any_supported_type> */
    //use SQL_ColumnAccessorTrait;

    /** @param int|string $offset */
    public function offsetExists(mixed $offset) : bool;

    /**
    * @param int|string $offset
    * @return T
    */
    public function offsetGet(mixed $offset) : mixed;

    /**
    * @param int|string|null   $offset
    * @param T                 $value
    * @return never
    */
    public function offsetSet(mixed $offset, mixed $value) : void;

    /**
    * @param int|string $offset
    * @return never
    */
    public function offsetUnset(mixed $offset) : void;
}
?>
