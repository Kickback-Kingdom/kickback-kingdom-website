<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Accessories;

// * \@phpstan-import-type kksql_any_supported_type from \Kickback\Common\Database\SQL\SQL_Row

/**
* @extends \ArrayAccess<int|string,mixed>
*/
interface InternalUntypedColumnGetterInterface extends \ArrayAccess
{
    // /** @param int|string $offset */
    // public function offsetExists(mixed $offset) : bool;
    //
    // /**
    // * @param int|string $offset
    // * @return kksql_any_supported_type
    // */
    // public function offsetGet(mixed $offset) : mixed;
    //
    // /**
    // * @param int|string|null           $offset
    // * @param kksql_any_supported_type  $value
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
