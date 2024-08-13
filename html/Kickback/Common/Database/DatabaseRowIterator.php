<?php
declare(strict_types = 1);

namespace Kickback\Common\Database;

use Kickback\Common\Database\DatabaseRowIntegerAccess;

/**
* @implements \Iterator<string,mixed>
*/
final class DatabaseRowIterator implements \Iterator
{
	public function __construct(
		private DatabaseRowIntegerAccess  $row,
		private int                       $index = 0
	) {}

	/**
	* @return  mixed  Returns the value of the field that is at the iterator's current position.
	*/
	public function current(): mixed
	{
		return $this->row->value_of_field_at_position($this->index);
	}

	/**
	* @return  string  Returns the name of the field that is at the iterator's current position.
	*/
	public function key(): mixed {
		return $this->row->name_of_field_at_position($this->index);
	}

	public function next(): void
	{
		// We do all incrementation on a separate variable, just to make it
		// impossible for the iterator to end up in an invalid state
		// where the index is "between" valid possibilities.
		$i = $this->index;
		$i++;

		// This incrementation operation must skip over any missing fields
		// that have within-bounds positions/indices, because if we don't,
		// then `$this->valid()` will become false and the caller could
		// end termination prematurely, instead of simply skipping
		// the nonexistant fields.
		$this->skip_missing_fields($i);

		// Done.
		$this->index = $i;
	}

	public function rewind(): void {
		// Logical considerations are similar to that of `$this->next()`.
		$i = 0;
		$this->skip_missing_fields($i);
		$this->index = $i;
	}

	public function valid(): bool {
		// We check for being less than the column-positions-end-length,
		// because `$this->next()` should have skipped over any "holes"
		// in the row. This behavior is important, because we don't want
		// to attempt to load a field that's in-bounds but didn't have
		// any contents given to it by `\mysqli_result->fetch_*()`:
		// that kind of "invalid" would terminate iteration early,
		// instead of skipping over the missing field.
		$result = ($this->index <= $this->row->max_column_index());

		// Because `this->next()` skips any missing fields (holes), then
		// we should never encounter a situation where the above logic
		// has a result that's different from the `field_exists_at_position`
		// logic.
		assert($result === $this->row->field_exists_at_position($this->index));

		// Done.
		return $result;
	}

	private function skip_missing_fields(int &$i): void
	{
		$max = $this->row->max_column_index();
		while( ($i <= $max) && !$this->row->field_exists_at_position($i) )
		{
			$i++;
		}
	}
}
?>
