<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL;

use Kickback\Common\Database\SQL\Drivers\DriverID;

use Kickback\Common\Database\SQL\SQL_ResultDetails;
use Kickback\Common\Database\SQL\SQL_Row;
use Kickback\Common\Database\SQL\Accessories\SQL_BindingMap;

/**
* @see SQL_ResultDetails
*
* @template DRIVER_ID of DriverID::*
* @extends SQL_ResultDetails<DRIVER_ID>
* @extends \Iterator<int,SQL_Row>
*/
interface SQL_Result extends SQL_ResultDetails, \Iterator
{
    /*
    TODO: Separate these into `fetch_next` and `fetch_current` varieties?
    // (Though this might _guarantee_ higher memory use per-fetch, because
    // we'd be required to store an entire row-buffer to guarantee that
    // fetch_current would always work.)
*/

    /**
    * Fetch the next row from the result set, immediately placing it into the given object.
    *
    * Fetches one row of data from the result set and places it into the given
    * object (`$object_to_populate`).
    *
    * Unlike `fetch_into_optional_fields`, this method does not return anything.
    * This is due to the fact that this method either succeeds or fails,
    * and there is no possibility of partially populating the object.
    *
    * As above, the action of this method can be considered atomic:
    * if this method fails in any way and throws an exception, then
    * `$object_to_populate` will be untouched.
    *
    * This method may throw exceptions for a number of reasons:
    * type mismatch, type conversion failure, missing field, and possibly others.
    *
    * @param SQL_BindingMap|class-string ...$options
    */
    public function fetch_next_into_required_fields(object &$object_to_populate, SQL_BindingMap|string ...$options) : void;
    public function fetch_current_into_required_fields(object &$object_to_populate, SQL_BindingMap|string ...$options) : void;

    /**
    * Fetch the next row from the result set, immediately placing it into the given object.
    *
    * Fetches one row of data from the result set and places it into the given
    * object (`$object_to_populate`).
    *
    * Unlike `fetch_into_required_fields`, this method has a return value.
    * This is due to the fact that partial completion is possible with this
    * method, but not with `fetch_into_required_fields`.
    *
    * Although this method has one less reason to throw an exception than does
    * `fetch_into_required_fields`, this method may still throw an exception for
    * other reasons, such as type mismatch or type conversion failure.
    *
    * @param SQL_BindingMap|class-string ...$options
    *
    * @return int  0 if ALL optional fields were populated, or a positive integer equal to the number of optional fields that could not be filled.
    */
    public function fetch_next_into_optional_fields(object &$object_to_populate, SQL_BindingMap|string ...$options) : int;

    /**
    * Fetch the next row of the result set.
    *
    * Fetches one row of data from the result set and places it into the given
    * `SQL_Row` object (`$row_to_populate`).
    *
    * Each subsequent call to this function will provide the next row
    * within the result set and return `true`.
    *
    * When the result set is exhausted, it will call `$row_to_populate->clear()`
    * and then return `false`.
    *
    * The signature of this method allows the `$row_to_populate` object
    * (and all of the resources it owns) to be reused every time the
    * `fetch_into_row` function is called. This avoids requiring the
    * interpreter to make unnecessary memory allocations and deallocations
    * (though it might perform unnecessary memory heap activity anyways).
    *
    * The trade-off for the above optimization advantage is that the
    * any references to the contents of `$row_to_populate` will become
    * invalid after a subsequent call to `fetch_into_row`. The intent of
    * this design is that the caller shall copy all data that they need
    * out of the `SQL_Row` object (`$row_to_populate`) before calling
    * `fetch_into_row` again.
    *
    * @param SQL_Row<DRIVER_ID> $row_to_populate
    *
    * @return bool  `true` if the row is populated; `false` if there is no current row (because all rows have already been fetched)
    */
    public function fetch_next_into_row(SQL_Row &$row_to_populate) : bool;


    // NOTE: We implement `\Iterator` instead of `\IteratorAggregate`
    // because the `getIterator()` method allows more than one iterator
    // to be returned from the `SQL_Row` object. This could be confusing
    // to the caller: there might only be ONE cursor for iterating over
    // the SQL query results, yet the caller may expect EACH returned iterator
    // to represent a separate cursor. This introduces an aliasing bug.
    // Meanwhile, by implementing `\Iterator`, the `SQL_Result` object
    // is clearly only a single iterator, and it is generally clear in
    // PHP code that basic assignments like `$resultB = $resultA;` do
    // not clone the object, but simply result in two references to the
    // same object (e.g. the same iterator, in this case).

    /**
    * @return SQL_Row<DRIVER_ID>
    */
    public function current() : SQL_Row;
    public function key() : int;
    public function next() : void;
    public function rewind() : void;
    public function valid() : bool;
}

?>
