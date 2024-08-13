<?php
declare(strict_types = 1);

namespace Kickback\Common\Database;

// This interface mostly exists to break a circular dependency and allow
// the DatabaseRowIterator to track its index's meaning against the
// DatabaseRow that it spans.
interface DatabaseRowIntegerAccess
{
	public function max_column_index() : int;
	public function field_exists_at_position(int $pos) : bool;
	public function value_of_field_at_position(int $pos) : mixed;
	public function name_of_field_at_position(int $pos) : string;
}

?>
