<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL;

use Kickback\Common\Attributes\KickbackGetter;

use Kickback\Common\Database\SQL\Drivers\DriverID;

use Kickback\Common\Database\SQL\SQL_DriverMetadata;

use Kickback\Common\Database\SQL\SQL_ConnectionDetails;
use Kickback\Common\Database\SQL\SQL_StatementDetails;

use Kickback\Common\Database\SQL\Accessories\SQL_BindingMap;

/*
TODO: implement this stuff
TODO: explain accessors
*/

/**
* This interface represents a list of rows (SQL_Row) returned by an SQL query.
*
* If a query returns multiple results (multiple lists of rows), as would
* likely be the case for queries that contain multiple SQL statements,
* then this only represents a single result-set. The other results can
* be obtained by requesting subsequent results from the `SQL_Statement`
* object that yielded the current `SQL_Result` instance. (TODO: Is this consistent with common driver layouts?)
*
* Keep in mind that the list of rows might not exist locally as physical
* data, because the rows might be lazily retrieved from the SQL server
* or buffered in some way. Since the retrieved data will probably be
* shuffled into other PHP variables/data anyways, lazy/buffered loading
* can avoid having the same information being stored in memory twice.
* Currently, the exact mechanics of this are likely to be implemented
* by the SQL driver that is used.
*
* ```
* // Reading rows directly into their destination, using a binding map.
* $map = $results->create_bindmap(get_class(ExamplePlayerClass))
*     ->string  ('name',         'name')
*     ->int     ('strength',     'strength')
*     ->int     ('dexterity',    'dexterity')
*     ->int     ('intelligence', 'intelligence')
*     ->int     ('location$x',   fn(&$p, $x) => ($p->location->x = $x))
*     ->int     ('location$y',   fn(&$p, $y) => ($p->location->y = $y))
*     ->datetime('last_login',   'last_login_time');
*
* $result->fetch_into_with_map($player, $map);
*
* // Reading rows directly into their destination.
* //
* // This version uses a simplified notation that is possible
* // when the destination object and source SQL row have fields with
* // identical names.
* //
* // In this case, not all fields and columns match, so an exception
* // is thrown.
* //
* assert_throws($results->fetch_into_all_fields($player));
* (TODO: Equivalent to? `$results->fetch_into_required_fields($player, SQL_FieldAny::class)`)
*
* // Reading rows directly into select columns in a destination.
* //
* // If we don't need ALL fields in the object to be filled, then
* // we can specify that only fields with a specific PHP Attribute
* // shall be populated with data from the row.
* //
* // All fields with the given PHP attribute must successfully be loaded
* // from the SQL query, or an exception will be thrown.
* // This allows for errors to be detected if column names
* // and class field names become desynchronized.
* //
* // In this example, the values in the row can fill all fields that have the
* // `ExamplePlayerRequiredField` attribute present. The other columns would
* // be ignored.
* $player->location->x = null;
* $player->location->y = null;
* $player->last_login_time = null;
*
* $results->fetch_into_required_fields($player, ExamplePlayerRequiredField::class));
*
* assert(!isset($player->location->x));
* assert(!isset($player->location->y));
* assert(!isset($player->last_login_time));
*
* // Reading rows directly into their destination: mixture of methods.
* //
* // The `fetch_into_required_fields` method can also accept a binding map,
* // thus allowing the binding map to handle fields that couldn't be loaded
* // automatically.
* //
* // In this example, the entire results row is
* // populated into the `$player` object.
* //
* $results->create_bindmap($map, get_class(ExamplePlayerClass))
*     ->int('location$x', fn(&$p, $x) => ($p->location->x = $x))
*     ->int('location$y', fn(&$p, $y) => ($p->location->y = $y))
*     ->datetime('last_login_time',  'last_login');
*
* $results->fetch_into_required_fields($player, $map, ExamplePlayerRequiredField::class));
*
* // Reading rows directly into their destination, without safeties.
* //
* // Like the other "fetch_into" methods, this will read columns into
* // fields on the given object whenever the column names match field
* // or accessor names on the class.
* //
* // This version will ignore mismatches:
* // * Fields that do not have a corresponding column in the result row.
* // * Columns that do not have corresponding fields with the same name.
* //
* $player->location->x = null;
* $player->location->y = null;
* $player->last_login_time = null;
*
* $results->fetch_into_matching_fields($player);
* (TODO: This might be better as `$results->fetch_into_optional_fields($player, SQL_FieldAny::class)`)
*
* assert(!isset($player->location->x));
* assert(!isset($player->location->y));
* assert(!isset($player->last_login_time));
*
* ```
*
* @template DRIVER_ID of DriverID::*
* @extends SQL_DriverMetadata<DRIVER_ID>
*/
interface SQL_ResultDetails extends SQL_DriverMetadata
{
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
/*
    TODO: Do we support/implement \ArrayAccess?
    // Which (how many) SQL drivers support integer-based row access?
    // (I suspect that even if they do, it will be very technical and squirrely
    // as it will involve issuing instructions to the driver to explicitly
    // copy all results into memory ahead of time.)
*/
    /**
    * Returns the underlying `mysqli_result` object, but ONLY if this results
    * object is actually backed by a `mysqli` driver.
    *
    * If these results are provided by a different driver, then the
    * variable passed as $underlying_results_object will be `null` after the
    * call to this function.
    *
    * @throws void
    *
    * @param ?\mysqli_result $underlying_results_object
    *
    * @phpstan-assert-if-true  \mysqli_result $underlying_results_object
    * @phpstan-assert-if-false null           $underlying_results_object
    */
    public function to_mysqli(?\mysqli_result &$underlying_results_object) : bool;

    /**
    * Returns the number of rows already fetched.
    *
    * Returns the number of rows that have already been fetched from
    * this result-set.
    *
    * In this case, `fetched` refers to a call to any method that advances
    * the result-set's row-cursor.
    *
    * @throws void
    */
    #[KickbackGetter]
    public function num_rows_fetched() : int;

    /**
    * This method allows the creation of bindings that directly place SQL results
    * into the fields of an instance of the class specified by the `$fqn_of_class_to_bind`
    * parameter.
    *
    * The caller must always pass a PHP variable (of type `SQL_BindingMap` or unset)
    * into the `$map_to_populate`.
    *
    * The `$map_to_populate` allows for an optimization: If the `SQL_BindingMap`
    * passed into `create_bindmap` is already set and has been used in at least
    * one `fetch_into*` operation, then the binding calculations will be
    * skipped (e.g. every chained mapping call will just return the binding map
    * without doing anything). And if the caller stores their `SQL_BindingMap`
    * object in a static variable, it becomes possible to avoid repeating
    * binding calculations for the duration of the HTTP request or PHP shell
    * instance (the "cycle").
    *
    * Building a binding map entails array creation with incremental inserts,
    * which would require the PHP interpreter to perform some amount of
    * heap activity (memory allocations). In well-optimized code, such
    * things may be worth avoiding.
    *
    * Storing `$map_to_populate` in the $_SESSION array would allow
    * the binding to be cached/memoized even longer, though this is unlikely
    * to be worth the additional burden in lifecycle management. (Currently untested.)
    *
    * @param      ?SQL_BindingMap  $map_to_populate
    * @param-out  SQL_BindingMap   $map_to_populate
    *
    * @param  class-string  $fqn_of_class_to_bind
    *     The Fully Qualified Name (FQN) of the class whose fields we are
    *     binding SQL columns to.
    */
    public function create_bindmap(?SQL_BindingMap &$map_to_populate, string $fqn_of_class_to_bind) : SQL_BindingMap;

    /**
    * Frees the memory allocated by the SQL driver for this result-set.
    *
    * Frees the memory allocated by the underlying SQL driver for the result-set
    * held by this `SQL_Result` object, if the underlying SQL driver supports
    * it.
    *
    * If the underlying driver does not support this operation, then this
    * will do nothing.
    *
    * It is usually unnecessary to call this method, but it could be helpful
    * if memory leaks are traced to `SQL_Result` objects holding on to
    * result sets longer than is necessary.
    *
    * This method does not throw exceptions.
    *
    * This doesn't deallocate the memory occupied by the `SQL_Result` object
    * itself, or any objects owned by it. All it does is call the underlying
    * SQL driver's corresponding `*_free_result` function-or-method.
    * As of this writing (2025-07-01, or PHP 8.3ish), PHP seems to use
    * automatic-reference-counting as a memory management strategy,
    * so the `SQL_Result` and underlying objects will likely be
    * reclaimed once no references to them exist anymore.
    *
    * Drivers known to implement this method:
    * * [MySQLi](https://www.php.net/manual/en/mysqli-result.free.php) (awaiting integration)
    * * [PostgreSQL](https://www.php.net/manual/en/function.pg-free-result.php) (awaiting integration)
    * * [SQLite3](https://www.php.net/manual/en/sqlite3result.finalize.php) (likely match but no guarantee; will integrate if SQLite3 integration happens)
    * * [IBM DB2](https://www.php.net/manual/en/function.db2-free-result.php) (will integrate if IBM DB2 integration happens)
    *
    * Drivers that probably do not have this method:
    * * [PDO](https://www.php.net/manual/en/book.pdo.php) - Seems to lack memory resource management functions entirely.
    * * [Microsoft SQL](https://www.php.net/manual/en/book.sqlsrv.php) - It has `sqlsrv_free_stmt`, but apparently no result-level version.
    * * [Oracle](https://www.php.net/manual/en/book.oci8.php) - It has `oci_free_statement`, but apparently no result-level version. It has other `free` functions, but so far none seem to fit.
    *
    * @throws void
    */
    public function free_memory() : void;

    /**
    * Tests if the `free_memory()` method is implemented in this SQL driver.
    *
    * @return bool  `true` if `free_memory()` is implemented; `false` if it isn't and will do nothing instead.
    *
    * @throws void
    */
    public function free_memory_supported() : bool;

    // NOTE: The SQL_Result object can't be Countable because the mysqli
    //     driver doesn't provide a reliable way to retreive the number of
    //     rows _remaining_ in the result set (or the number of rows that
    //     were originally generated by the execution of the query/statement).
    //     Notably, `$mysqli_result->num_rows` may return 0 (repeatedly?)
    //     for unbuffered result sets (until all rows have been fetched from
    //     the server). And we can't make accurate "guesses" with
    //     `$mysqli->affected_rows` because that is only applicable
    //     to INSERT, UPDATE, REPLACE, or DELETE queries.
    // Sources:
    //   https://www.php.net/manual/en/mysqli-stmt.num-rows.php
    //   https://www.php.net/manual/en/mysqli.affected-rows.php
    //public function count() : int;

    public function dispose() : void;
    public function disposed() : bool;
}

?>
