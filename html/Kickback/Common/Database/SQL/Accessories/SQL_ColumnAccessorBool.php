<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Accessories;

use Kickback\Common\Database\SQL\Accessories\SQL_ColumnAccessor;

/**
* @extends \ArrayAccess<int|string,bool>
* @extends SQL_ColumnAccessor<bool>
*/
interface SQL_ColumnAccessorBool extends \ArrayAccess, SQL_ColumnAccessor
{
    // /** @param int|string $offset */
    // public function offsetExists(mixed $offset) : bool;
    //
    // /**
    // * @param int|string $offset
    // * @return bool
    // */
    // public function offsetGet(mixed $offset) : mixed;
    //
    // /**
    // * @param int|string|null $offset
    // * @param bool            $value
    // * @return never
    // */
    // public function offsetSet(mixed $offset, mixed $value) : never;
    //
    // /**
    // * @param int|string $offset
    // * @return never
    // */
    // public function offsetUnset(mixed $offset) : never;
}
?>
