<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Drivers\Base\Accessories;

use Kickback\Common\Exceptions\NotImplementedException;
use Kickback\Common\Exceptions\UnexpectedNullException;

use Kickback\Common\Database\SQL\Internal\SQL_ColumnAccessorSet;

use Kickback\Common\Database\SQL\Accessories\SQL_ColumnAccessor;
use Kickback\Common\Database\SQL\Accessories\SQL_ColumnAccessorBool;

/**
* @phpstan-import-type kksql_any_supported_type from SQL_ColumnAccessorSet
*
* @template T
*/
abstract class ColumnAccessorCommon
{
    /**
    * @param SQL_ColumnAccessor<mixed> $accessor_
    */
    public function __construct(
        protected SQL_ColumnAccessor  $accessor_
    ) {}

    /**
    * @phpstan-assert !null $value
    */
    protected static function enforce_not_null(
        int|string $column_number_or_name,
        mixed      $value // Assumed to originate from underlying SQL driver.
    ) : void
    {
        if ( isset($value) ) {
            return;
        }

        throw new UnexpectedNullException(
            "Error when reading from column `$column_number_or_name`: ".
            ' Unexpected `null` value.'
        );
    }

    protected static function conversion_not_implemented(
        int|string $column_number_or_name,
        mixed      $value_from, // Assumed to originate from underlying SQL driver.
        string     $type_to    // Assumed to be the destination of the access.
    ) : NotImplementedException
    {
        $type_from = gettype($value_from);
        return new NotImplementedException(
            "Error when reading from column `$column_number_or_name`: ".
            "Conversions from type `$type_from` to `$type_to` are not currently implemented."
        );
    }

    protected static function conversion_unsupported(
        int|string $column_number_or_name,
        string     $type_from, // Assumed to originate from underlying SQL driver.
        string     $type_to    // Assumed to be the destination of the access.
    ) : \TypeError
    {
        return new \TypeError(
            "Error when reading from column `$column_number_or_name`: ".
            "Destination is `$type_to`, but read a `$type_from` value from the SQL driver. ".
            "This conversion (`$type_from` to `$type_to`) is not supported."
        );
    }

    /** @param int|string $offset */
    public final function offsetExists(mixed $offset) : bool { return $this->accessor_->offsetExists($offset); }

    /**
    * @param int|string|null   $offset
    * @param T                 $value
    * @return never
    */
    public final function offsetSet(mixed $offset, mixed $value) : void { throw new \BadMethodCallException(); }

    /**
    * @param int|string $offset
    * @return never
    */
    public final function offsetUnset(mixed $offset) : void { throw new \BadMethodCallException(); }
}
?>
