<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Drivers\Base;

use Kickback\Common\Attributes\KickbackGetter;
use Kickback\Common\Exceptions\UnsupportedOperationException;

use Kickback\Common\Database\SQL\Drivers\DriverID;
use Kickback\Common\Database\SQL\Drivers\Base\BaseStatementDetails;
use Kickback\Common\Database\SQL\Drivers\Base\BaseResultDetails;
use Kickback\Common\Database\SQL\Drivers\Base\BaseRow;

use Kickback\Common\Database\SQL\Accessories\SQL_BindingMap;

use Kickback\Common\Database\SQL\Drivers\Base\Accessories\BindingMap;

/*
TODO: implement this stuff
TODO: explain accessors
*/

/**
* @see \Kickback\Common\Database\SQL\SQL_ResultDetails
*
* TODO: Delete
* \@template Driver_ResultT of BaseResultDetails
* \@template-contravariant Driver_RowT of BaseRow
* \@extends BaseResultDetails<Driver_ResultT,Driver_RowT>
* @template DRIVER_ID of DriverID::*
* @extends BaseResultDetails<DRIVER_ID>
*/
abstract class BaseResult extends BaseResultDetails
{
    /**
    * This must be implemented by the SQL driver wrapper or implementation.
    *
    * It is typically called the first time `BaseResult->current()` is called,
    * and is then reused in subsequent calls to the `current()` method within
    * the same result-set.
    *
    * @return BaseRow<DRIVER_ID>
    */
    protected abstract function construct_iterator_row() : BaseRow;

    //private const     ITERATOR_FULL = 1;
    //private const     ITERATOR_PARTIAL = 2;
    //private const     ITERATOR_EMPTY = 3;


    private bool      $empty = false;

    /** @var ?BaseRow<DRIVER_ID> */
    private ?BaseRow  $iterator_row = null;

    /**
    * @return BaseRow<DRIVER_ID>
    */
    #[KickbackGetter]
    private function &iterator_row() : BaseRow
    {
        if (!isset($this->iterator_row)) {
            $this->iterator_row = $this->construct_iterator_row();
        }
        return $this->iterator_row;
    }

    /**
    * @param BaseStatementDetails<DRIVER_ID> $statement
    */
    public function __construct(BaseStatementDetails $statement) {
        parent::__construct($statement);
    }

    /**
    * @see Kickback\Common\Database\SQL\SQL_Result::to_mysqli
    *
    * @throws void
    *
    * @param ?\mysqli_result $underlying_results_object
    *
    * @phpstan-assert-if-true  \mysqli_result $underlying_results_object
    * @phpstan-assert-if-false null           $underlying_results_object
    */
    public function to_mysqli(?\mysqli_result &$underlying_results_object) : bool {
        $underlying_results_object = null;
        return false;
    }

/*
    TODO: How do I preserve the atomicity guarantee without copying everything
    every time a row is read?
    // Right now we would need to do one of these things:
    // * Read into a row-buffer to catch any conversion acceptions, then safely
    //     copy those validated values into the object.
    // * Save the objects current state into a row-buffer, then populate the
    //     object directly until success or failure. If failure, then use the
    //     saved-state to revert the object to its original condition, THEN
    //     throw the exception.
    // The first route would be more desirable I think, as it is more inherently
    // thread-safe (not completely, but better) or just less "racey".
    // The only way we can't do that is if we can't completely validate
    // the fields without writing them into the object.
    // Either way, it requires that darned row-buffer to temporarily hold things.
    //
    // Hybrid approach:
    // * Note that:
    //     * Type-matching validation can be performed just once at bind-collation-time. (or is it? how do we know what types the SQL driver will return?)
    //     * Not all type-conversions throw exceptions. Some are "safe". Or rather, they are "nothrow".
    // * Plan:
    //     * Throw immediately if there are any impossible type conversions.
    //     * Group all assignments into "nothrow" and "maythrow".
    //     * Create a row-buffer for all of the "maythrow" values.
    //     * Fetch "maythrow" values into the row-buffer, throwing an exception if there are any errors. (Destination object is still untouched at this point.)
    //     * Interleave the copying of "maythrow" values into the destination object and the fetching of "nothrow" values directly into the destination object.
    // Hmmm, I think that might be a really good approach! There will probably be a lot of "nothrow" conversions actually (e.g. string->string and int->int and such.)
    // BUT.
    // Is it possible to know if an SQL column is NOT-NULL? Because if not, then
    // any non-nullable accessor will need to be buffered in case the SQL server
    // hands a `null` value to the non-null accessor! (In other words,
    // converting a maybe-null value into a non-null value is a type-conversion
    // that is ALWAYS "maythrow" and NEVER "nothrow".)
    // * For `mysqli`: YES  (Requires interpreting bit-flags: https://www.php.net/manual/en/mysqli-result.fetch-fields.php )
    // * For `pgsql`: MAYBE (We can use `pg_field_is_null` on a per-row/per-field basis to test for NULL without fetching: https://www.php.net/manual/en/function.pg-field-is-null.php )
    // * For `sqlite`: NO, BUT it has no other way to read rows besides returning an array! https://www.php.net/manual/en/class.sqlite3result.php
    // * For `mssql`: YES (https://www.php.net/manual/en/function.sqlsrv-field-metadata.php)
    // * For `ibmdb2`: NO? (Requires a separate metadata query I think: https://www.php.net/manual/en/function.db2-columns.php)
    // * For `oracle`: MAYBE (Seems to feature an `oci_field_is_null` function like PostgreSQL: https://www.php.net/manual/en/function.oci-field-is-null.php )
    // So... there seems to be enough possibility there that it might be worth it.
    // The places that don't support it at all are either on the more obscure side (IBM DB2) or are not really scalable anyways (SQLite3).
    // And in those situations, we just use a buffer row. Oh well.
    //
    // Now then... can we actually get SQL server metadata about TYPE information?
    // Like, so we can know ahead-of-time (see above) whether there will be impossible type conversions? And identify nothrow-vs-maythrow conversions?
    // * For `pgsql`: YES (Available when Result object is available: https://www.php.net/manual/en/function.pg-field-type.php)
    // * For `mysqli`: ------ WAIT
    // Rather than using SQL metadata, why not just load the FIRST row into an associative array,
    // then use the PHP type data from that?
    // Presumably, the rest of the values in the result-set will have the same types as the first,
    // so we only have to do this once. (Though it does create an extra allocation per-result-set, so that's not great, but probably acceptable.)
    // Caveat: if a value in the first row is `null`, then we don't know its proper type. Darn. (Some columns have a lot of NULL values, too.)
    //
    TODO: Type-conversion architecture:
    // * Centralized place for platform-independent type conversions.
    // * Function to call that tells us if a given from->to conversion is possible at all. (e.g. int->DateTime would return `true` and DateTime->bool would return `false`)
    // * Function to call that tells us if a given from->to conversion is nothrow or maythrow.
*/
    // protected abstract function impl_fetch_into_object(object &$object_to_populate, SQL_BindingMap) : int;
    //
    // /**
    // * @param SQL_BindingMap|class-string ...$options
    // */
    // public final function fetch_into_required_fields(object &$object_to_populate, SQL_BindingMap|string ...$options) : void;
    //
    // /**
    // * @param SQL_BindingMap|class-string ...$options
    // *
    // * @return 0 if ALL optional fields were populated, or a positive integer equal to the number of optional fields that could not be filled.
    // */
    // public final function fetch_into_optional_fields(object &$object_to_populate, SQL_BindingMap|string ...$options) : int;
    //
    // public final function fetch_into_row(SQL_Row &$row_to_populate) : bool;

    // We intentionally don't implement this here.
    // We want anyone writing a driver-integration layer to at least check
    // for this function in the driver's documentation before they decide
    // that their wrapper should just do nothing.
    // (Although not all drivers support this, it's still pretty common,
    // and the very popular MySQL and PostgreSQL drivers DO have this.
    // So it's worth checking.)
    //public function free_memory() : void;
    //
    // Ditto.
    //public function free_memory_supported() : bool;

    /**
    * @return BaseRow<DRIVER_ID>
    */
    public final function current() : BaseRow
    {
        return $this->iterator_row();
    }

    public final function key() : int
    {
        return $this->num_rows_fetched();
    }

    public final function next() : void
    {
        if ( $this->empty ) {
            throw new \OutOfBoundsException('Called `SQL_Result::next()` when there are no rows left in the result-set.');
        }

        $row = $this->iterator_row();
        if ( !$this->fetch_next_into_row($row) ) {
            $this->empty = true;
        }
        /*
        if ( $this->fetch_next_into_row($row) ) {
            $this->iterator_state = self::ITERATOR_PARTIAL;
        } else {
            $this->iterator_state = self::ITERATOR_EMPTY;
        }
        */
    }

    public final function rewind() : void
    {
        // NOTE:
        // ```
        // This is the _first_ method called when starting a foreach loop.
        // It will _not_ be executed _after_ foreach loops.
        // ```
        // Source: https://www.php.net/manual/en/iterator.rewind.php
        // (E.g., it's from the PHP documentation/manual.)
        //
        // Thus, despite the method's name, we need to allow it to be called
        // once at the very beginning of iteration. This one call will mark
        // the point in time that the caller entered a `foreach` loop with
        // the current `BaseResult` object. Unless we need to hook some other
        // action into that event, then we can just do nothing.
        //
        // After that, however, we don't allow `rewind` to be called.
        // We simply don't know if the underlying database driver can
        // rewind a result-set. And even if it could, it'd be inconsistent
        // with the behavior of drivers that can't. So for the sake of
        // a uniform interface, this method functions as the lowest-common-denominator
        // and simply doesn't allow any _actual_ rewinding.
        //
        if ( 0 !== $this->num_rows_fetched() ) {
            throw new UnsupportedOperationException('`SQL_Result` objects do not support rewinding. They only support forward-iteration.');
        }
        /*
        if ( $this->iterator_state === self::ITERATOR_FULL ) {
            $this->iterator_state = self::ITERATOR_PARTIAL;
        } else {
            throw new UnsupportedOperationException('SQL_Result objects do not support rewinding. They only support forward-iteration.');
        }
        */
    }

    public final function valid() : bool {
        return !$this->empty;
        /*return  ($this->iterator_state === self::ITERATOR_FULL)
             || ($this->iterator_state === self::ITERATOR_PARTIAL);
        */
    }

    // public function count() : int;
}

?>
