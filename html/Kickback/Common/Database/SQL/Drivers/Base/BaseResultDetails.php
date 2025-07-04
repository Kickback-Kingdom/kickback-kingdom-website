<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Drivers\Base;

use Kickback\Common\Attributes\KickbackGetter;

use Kickback\Common\Database\SQL\Drivers\DriverID;
use Kickback\Common\Database\SQL\Drivers\Base\BaseDriverMetadataTrait;
use Kickback\Common\Database\SQL\Drivers\Base\BaseConnectionDetails;
use Kickback\Common\Database\SQL\Drivers\Base\BaseStatementDetails;
use Kickback\Common\Database\SQL\SQL_Result;
use Kickback\Common\Database\SQL\SQL_Row;

use Kickback\Common\Database\SQL\Accessories\SQL_BindingMap;

use Kickback\Common\Database\SQL\Drivers\Base\Accessories\BindingMap;

/*
TODO: implement this stuff
TODO: explain accessors
*/

/**
* @see \Kickback\Common\Database\SQL\SQL_ResultDetails
*
* @template DRIVER_ID of DriverID::*
* @implements SQL_Result<DRIVER_ID>
*/
abstract class BaseResultDetails implements SQL_Result
{
    /** @use BaseDriverMetadataTrait<DRIVER_ID> */
    use BaseDriverMetadataTrait;

    /** @var ?BaseStatementDetails<DRIVER_ID> */
    private ?BaseStatementDetails $statement_;

    /**
    * @throws void
    * @return BaseConnectionDetails<DRIVER_ID>
    */
    #[KickbackGetter]
    public function connection() : BaseConnectionDetails {
        return $this->statement()->connection();
    }

    /**
    * @throws void
    * @return BaseStatementDetails<DRIVER_ID>
    */
    #[KickbackGetter]
    public function statement() : BaseStatementDetails {
        assert(!$this->disposed());
        return $this->statement_;
    }

    // Not all drivers keep track of the number of rows that have been
    // returned from their `fetch` methods. `mysqli`, for instances, has
    // the `$mysqli_result->num_rows` property which may return 0 (repeatedly?)
    // for unbuffered result sets (until all rows have been fetched from the server).
    private int       $num_rows_fetched_ = 0;

    /** @throws void */
    #[KickbackGetter]
    public final function num_rows_fetched() : int {
        return $this->num_rows_fetched_;
    }

    // This can't be merged with the above getter because of the differing
    // protection levels. It shouldn't be possible to set this from
    // outside of this class and its descendants, but reading it is OK.
    /** @throws void */
    protected final function set_num_rows_fetched(int $new_value) : int {
        $this->num_rows_fetched_ = $new_value;
        return $this->num_rows_fetched_;
    }

    /**
    * @param BaseStatementDetails<DRIVER_ID> $statement
    */
    public function __construct(BaseStatementDetails $statement)
    {
        // This assignment is required for `BaseDriverMetadataTrait` to work.
        $this->driver_id_value = $this->driver_id_definition();

        $this->statement_ = $statement;
    }

    /**
    * @see Kickback\Common\Database\SQL\SQL_ResultDetails::to_mysqli
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
    TODO: Implement the cache-on-fetch logic for binding maps.
    (This could be helpful for automatic/attribute-based object population too,
    as those operations could be cached as ... you guessed it ... binding maps!
    This would then allow the "fetcher" to just walk the column indices to
    retrieve the column values (instead of doing an associative lookup for
    each one).)
*/
    /**
    * @see Kickback\Common\Database\SQL\SQL_Result::create_bindmap
    *
    * @param      ?SQL_BindingMap  $map_to_populate
    * @param-out  SQL_BindingMap   $map_to_populate
    *
    * @param  class-string  $fqn_of_class_to_bind
    */
    public final function create_bindmap(?SQL_BindingMap &$map_to_populate, string $fqn_of_class_to_bind) : SQL_BindingMap
    {
        if (!isset($map_to_populate)) {
            $map_to_populate = new BindingMap($fqn_of_class_to_bind);
        }
        return $map_to_populate;
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

    private bool  $disposed_ = false;
    public function dispose() : void
    {
        $this->disposed_ = true;
        $this->statement_ = null;
    }

    /**
    * @phpstan-assert-if-true  null                  $this->statement_
    * @phpstan-assert-if-false BaseStatementDetails  $this->statement_
    */
    public function disposed() : bool
    {
        return $this->disposed_;
    }
}

?>
