<?php
declare(strict_types = 1);

namespace Kickback\Common\Database;

use Kickback\Common\Database\DatabaseRowIntegerAccess;
use Kickback\Common\Database\DatabaseRowIterator;
use Kickback\Common\Database\DatabaseFieldInfo;

// TODO:
// * Provide class-level documentation with examples.
// * DatabaseFieldInfo iterator.
// * DatabaseFieldInfo getter/accessors.

/**
* @implements \IteratorAggregate<string,mixed>
* @implements \ArrayAccess<string,mixed>
*/
class DatabaseRow implements \IteratorAggregate, \ArrayAccess, \Countable, DatabaseRowIntegerAccess
{
	// =========================================================================
	// Internal state
	// -------------------------------------------------------------------------

	private int $valid_field_count = 0;

	/**
	* @var  array<string,int>  $field_indices
	*/
	private array $field_indices = [];

	/**
	* @var  array<int,DatabaseFieldInfo>  $field_infos
	*/
	private array $field_infos = [];

	/**
	* @var  array<int,mixed>  $field_values
	*/
	private array $field_values = [];


	// =========================================================================
	// Constructor(s)
	// -------------------------------------------------------------------------

	/**
	* Constructs an object that represents one row of results from a `mysqli` SQL query.
	*
	* Using this class is preferable to using the array returned from `\mysqli_result->fetch_row()`
	* and related methods, because this class will prevent subsequent code from
	* accessing columns that were not fetched in the query.
	*
	* Additionally, this provides a way to use the type system to indicate that
	* a DatabaseRow is being passed/returned/stored instead of an `array` object
	* of unknown type. This allows _some_ type safety to be enforced by linting
	* tools like PHPStan. (It will still be impossible to verify field accesses
	* on the database row using static analysis, but it'll at least be possible
	* to detect when unrelated arrays are passed into places where database rows
	* are expected, or to detect when database rows are passed into places
	* where unrelated arrays are expected.)
	*
	* Note that calling `new DatabaseRow($mysqli_rows)` is semantically equivalent
	* to this sequence of operations:
	* ```
	* $row = new DatabaseRow();
	* $row->init_from_next_mysqli_result($mysqli_rows);
	* ```
	*
	* To avoid unnecessary memory allocations:
	* When iterating over multiple rows from a `\mysqli_result` object,
	* and when those results do NOT need to be stored past the lifespan
	* of the previous row, then just use `new DatabaseRow()` to construct
	* the DatabaseRow object once, then use `->init_from_next_mysqli_result(...)`
	* on each row to populate the DatabaseRow object with the contents of
	* the next row.
	*/
	public function __construct(?\mysqli_result $db_rows = null)
	{
		if ( !is_null($db_rows) ) {
			$this->init_from_next_mysqli_result($db_rows);
		}
	}

	/**
	* @param   \mysqli_result  $db_rows
	* @return  bool
	* @throws  \UnexpectedValueException If there is an error reading the next row from `$db_rows`.
	* @throws  \InvalidArgumentException If two or more columns have the same name (e.g. this \mysqli_result is from an invalid query).
	*/
	public final function init_from_next_mysqli_result(\mysqli_result $db_rows) : bool
	{
		// Populate field metadata.
		$this->init_all_field_infos_from_mysqli_result($db_rows);

		// Error handling.
		$values = $db_rows->fetch_array(MYSQLI_NUM);
		if ( $values === false ) {
			throw new \UnexpectedValueException("Could not retrieve row from \mysqli_result object due to unknown error(s).");
		} else
		if ( is_null($values) ) {
			return false;
		}

		// Populate field values.
		$n_columns = $db_rows->field_count;
		for($i = 0; $i < $n_columns; $i++)
		{
			if ( !$this->field_infos[$i]->is_valid() ) {
				$this->field_values[$i] = null;
				continue;
			}

			$this->field_values[$i] = $values[$i];
			$this->valid_field_count++;
		}

		// Contract testing.
		assert(count($this->field_infos) === count($this->field_values));

		// Done.
		return true;
	}

	// Possible future function?
	/*
	* @param   \mysqli_result  $db_rows
	* @return  void
	*/
	/*
	private function init_from_next_mysqli_result(\mysqli_result $db_rows) : void
	{
		$this->init_all_field_infos_from_mysqli_result($db_rows);

		// BEWARE: Untested attempt to retrieve a row from a \mysqli_result
		// without causing it to advance to the next row.
		$iter = $db_rows->getIterator();
		$this->items = $iter->current();
	}
	*/

	private function clear_all_field_infos() : void
	{
		$len = count($this->field_infos);
		for($i = 0; $i < $len; $i++) {
			$this->field_infos[$i]->clear();
		}
	}

	/**
	* @throws \InvalidArgumentException  If two or more columns have the same name (e.g. this \mysqli_result is from an invalid query).
	*/
	private function init_all_field_infos_from_mysqli_result(\mysqli_result $db_rows) : void
	{
		// Prevent any stale state from persisting in the field info array.
		$this->clear_all_field_infos();

		// Calculate lengths
		$n_columns = $db_rows->field_count;
		$prev_len = count($this->field_infos);

		// Abort if there are any signs that we don't have any rows left in the \mysqli_result.
		if ( ($db_rows->num_rows === 0) || ($n_columns === 0) ) {
			return;
		} else {
			$mysqli_field_info = $db_rows->fetch_field_direct(0);
			if ( $mysqli_field_info === false ) {
				return;
			}
		}

		// Allocate new DatabaseFieldInfo objects whenever necessary.
		if ( $prev_len < $n_columns ) {
			for($i = $prev_len; $i < $n_columns; $i++) {
				$field_infos = new DatabaseFieldInfo();
			}
		}

		// Populate the DatabaseFieldInfo objects with the info from the \mysqli_result.
		for($i = 0; $i < $n_columns; $i++)
		{
			// Get the next field information element from our \mysqli_result object.
			$mysqli_field_info = $db_rows->fetch_field_direct($i);
			if ( $mysqli_field_info === false ) {
				// fetch_field_direct() returns "false if no field information for specified index is available."
				continue;
			}

			// Populate this field's `DatabaseFieldInfo` object.
			$field_infos = $this->field_infos[$i];
			$field_infos->init_from_mysqli_field_info($mysqli_field_info, $i);
			$field_name = $field_infos->name;

			// Check for duplicate field names.
			if ( array_key_exists($field_name, $this->field_indices) ) {
				$other_index = $this->field_indices[$field_name];
				if ( $other_index < $i ) {
					$this->clear_all_field_infos();
					throw new \InvalidArgumentException(
						"Query returned two fields with the same name (`$field_name`), ".
						"one at position `".strval($other_index)."`, and the other at `".strval($i)."`.");
				}
			}

			// Update our class's arrays.
			$this->field_infos[$i] = $field_infos;
			$this->field_indices[$field_name] = $i;
		}
	}

	// =========================================================================
	// Dynamic properties implementation
	// -------------------------------------------------------------------------

	/**
	* @param   string  $field_name
	* @return  mixed
	* @throws  \OutOfBoundsException  If there is no field with the given name in the Database row.
	*/
	public function __get(string $field_name) : mixed
	{
		return $this->offsetGet($field_name);
	}

	/**
	* @param   string $field_name
	* @return  void
	* @throws  \OutOfBoundsException  If there is no field with the given name in the Database row.
	*/
	public function __set(string $field_name, mixed $value) : void
	{
		$this->offsetSet($field_name,$value);
	}

	// =========================================================================
	// \IteratorAggregate implementation
	// -------------------------------------------------------------------------

	/**
	* @return  \Iterator<string,mixed>
	*/
	public function getIterator() : \Iterator
	{
		return new DatabaseRowIterator($this);
	}

	// =========================================================================
	// \ArrayAccess implementation
	// -------------------------------------------------------------------------

	private function index_is_in_positive_bounds(int $index) : bool
	{
		return (0 <= $index) && ($index < count($this->field_infos));
	}

	private function is_prebounded_index_valid(int $index) : bool
	{
		if ( !$this->field_infos[$index]->is_valid() ) {
			return false;
		}
		assert(array_key_exists($index, $this->field_values));
		return true;
	}

	private function validate_field_and_lookup_index(string $field_name, int &$index) : bool
	{
		if ( !array_key_exists($field_name, $this->field_indices) ) {
			return false;
		}
		$index = $this->field_indices[$field_name];
		return $this->is_prebounded_index_valid($index);
	}

	/**
	* @param   string  $field_name
	* @return  bool
	*/
	public final function offsetExists(mixed $field_name): bool
	{
		$index_to_discard = 0;
		return $this->validate_field_and_lookup_index($field_name, $index_to_discard);
	}

	/**
	* @param   string  $field_name
	* @return  mixed
	* @throws  \OutOfBoundsException  If there is no field with the given name in the Database row.
	*/
	public final function offsetGet(mixed $field_name): mixed
	{
		$i = 0;
		if (!$this->validate_field_and_lookup_index($field_name, $i)) {
			throw new \OutOfBoundsException("Attempt to read non-existant field: `".$field_name."`");
		}
		return $this->field_values[$i];
	}

	/**
	* @param   string  $field_name
	* @param   mixed   $value
	* @return  void
	* @throws  \OutOfBoundsException      If there is no field with the given name in the Database row.
	* @throws  \InvalidArgumentException  If `$value` does not have the same type as the existing value. (e.g. it is the wrong type for this field|column.)
	*/ // @phpstan-ignore method.childParameterType
	public final function offsetSet(mixed $field_name, mixed $value): void
	{
		$i = 0;
		if (!$this->validate_field_and_lookup_index($field_name, $i)) {
			throw new \OutOfBoundsException("Attempt to write to non-existant field: `".$field_name."`");
		}
		assert(is_int($i));
		$old_type = gettype($this->field_values[$i]);
		$new_type = gettype($value);
		if ($old_type !== $new_type) {
			throw new \InvalidArgumentException(
				"Attempted to assign value of incorrect type to field `$field_name`. "
				."The new value's type is `$new_type`, and the field's type is `$old_type`.");
		}
		$this->field_values[$i] = $value;
	}

	// TODO: Should `offsetUnset` no-op if the field doesn't exist?
	// That could potentially catch fewer errors, but this is... debatable.
	// And making it a no-op would make it idempotent.
	// But if the bounds-checking DOES catch errors... that matters more than idempotency.
	// Hmmmmm.

	/**
	* @param   string  $field_name
	* @return  void
	* @throws  \OutOfBoundsException  If there is no field with the given name in the Database row.
	*/
	public final function offsetUnset(mixed $field_name): void
	{
		$i = 0;
		if (!$this->validate_field_and_lookup_index($field_name, $i)) {
			throw new \OutOfBoundsException("Attempt to unset non-existant field: `".$field_name."`");
		}
		$this->field_infos[$i]->clear();
		unset($this->field_values[$i]);
		unset($this->field_indices[$field_name]);
	}

	// =========================================================================
	// \Countable implementation
	// -------------------------------------------------------------------------

	/**
	* @return   int  The number of fields/columns in the Database Row.
	*/
	public final function count() : int
	{
		return $this->valid_field_count;
	}

	// =========================================================================
	// DatabaseRowIntegerAccess implementation
	// -------------------------------------------------------------------------

	public final function max_column_index() : int
	{
		return count($this->field_infos) - 1;
	}

	public final function field_exists_at_position(int $pos) : bool
	{
		if ( $this->index_is_in_positive_bounds($pos) ) {
			return $this->is_prebounded_index_valid($pos);
		} else {
			return false;
		}
	}

	/**
	* @return  int  The index into this class's field arrays after from-the-end indices have been resolved.
	*/
	private function enforce_valid_positional_access(int $pos) : int
	{
		$i = $pos;
		$len = count($this->field_infos);

		// Allow access from end of "array".
		if ( $i < 0 ) {
			$i += $len;
		}

		// If it's still out of bounds, then throw an exception with an
		// appropriate error message.
		if ( !$this->index_is_in_positive_bounds($i) )
		{
			throw new \OutOfBoundsException(
				get_class($this).": The field position ".strval($pos)." is out of bounds. "
				."It must be between ".strval(-$len)." (inclusive) and ".strval($len)." (exclusive).");
		}

		// And just in case there are "holes" in the row...
		// (hopefully this never happens?)
		if ( !$this->field_exists_at_position($i) ) {
			throw new \OutOfBoundsException(
				get_class($this).": No field exists at position ".strval($pos).". "
				."The position is within bounds, but there was no field with that "
				."position/index given in the original \mysqli_result row.");
		}

		return $i;
	}

	public final function value_of_field_at_position(int $pos) : mixed
	{
		$i = self::enforce_valid_positional_access($pos);
		return $this->field_values[$i];
	}

	public final function name_of_field_at_position(int $pos) : string
	{
		$i = self::enforce_valid_positional_access($pos);
		return $this->field_infos[$i]->name;
	}

	// =========================================================================
	// Other methods
	// -------------------------------------------------------------------------

	/**
	* @return  array<string,mixed>
	*/
	public final function toArray() : array
	{
		$result = [];
		$len = count($this->field_infos);
		for($i = 0; $i < $len; $i++) {
			$info = $this->field_infos[$i];
			if ( !$info->is_valid() ) {
				continue;
			}
			$result[$info->name] = $this->field_values[$i];
			assert(array_key_exists($info->name, $this->field_indices));
			assert($this->field_indices[$info->name] === $i);
		}
		return $result;
	}
}
?>
