<?php
declare(strict_types = 1);

namespace Kickback\Common\Database;

// Ideally, fetch_field() would return an instance of a class that's defined
// somewhere in PHP's built-in/standard library of classes/functions/etc.
//
// But it doesn't. It just returns `object`.
//
// So if we want to be able to pass this around between functions with type
// safety, then we need to declare our own version of that object, with all
// the same fields in it. Here it is:
//
/**
* See the \mysqli_result->fetch_field() method for the reference documentation
* used to implement this class.
*/
final class DatabaseFieldInfo
{
	/** @var string $name       The name of the column */
	public string  $name = "";

	/** @var string $orgname    Original column name if an alias was specified */
	public string  $orgname = "";

	/** @var string $table      The name of the table this field belongs to (if not calculated) */
	public string  $table = "";

	/** @var string $orgtable   Original table name if an alias was specified */
	public string  $orgtable = "";

	/** @var string $db         The name of the database */
	public string  $db = "";

	// Looks like a deprecated field, so let's not write any code that uses it ;)
	/* @var int $max_length    The maximum width of the field for the result set. As of PHP 8.1, this value is always 0. */
	//public int  $max_length;

	/**
	* The width of the field in bytes.
	*
	* For string columns, the length value varies on the connection character set. For example, if the character set is latin1, a single-byte character set, the length value for a SELECT 'abc' query is 3. If the character set is utf8mb4, a multibyte character set in which characters take up to 4 bytes, the length value is 12.
	*
	* @var int    $length
	*/
	public int     $length = -1;

	/** @var int    $charsetnr  The character set number for the field. */
	public int     $charsetnr = -1;

	/** @var int    $flags      An integer representing the bit-flags for the field. */
	public int     $flags = -1;

	/** @var int    $type       The data type used for this field */
	public int     $type = -1;

	/** @var int    $decimals   The number of decimals for numeric fields, and the fractional seconds precision for temporal fields. */
	public int     $decimals = 0;

	/** @var int    $index      (Customization) The number of times `\mysqli_result->fetch_field()` was called before the call that returned this field info. (e.g. it's 0-based index) */
	public int     $index = -1;

	/**
	* This is the method used to populate a `DatabaseFieldInfo` object with data.
	*
	* This should get called automatically when populating a `DatabaseRow`,
	* so it is unlikely that there is any reason to call this from code outside
	* of the `DatabaseRow` class.
	*
	* @param  object  $field_info   Pass the return value of \mysqli_result->fetch_field() into this parameter.
	*/
	public function init_from_mysqli_field_info(object $field_info, int $index) : void
	{
		$this->name      = $field_info->name;       // @phpstan-ignore property.notFound
		$this->orgname   = $field_info->orgname;    // @phpstan-ignore property.notFound
		$this->table     = $field_info->table;      // @phpstan-ignore property.notFound
		$this->orgtable  = $field_info->orgtable;   // @phpstan-ignore property.notFound
		$this->db        = $field_info->db;         // @phpstan-ignore property.notFound
		$this->length    = $field_info->length;     // @phpstan-ignore property.notFound
		$this->charsetnr = $field_info->charsetnr;  // @phpstan-ignore property.notFound
		$this->flags     = $field_info->flags;      // @phpstan-ignore property.notFound
		$this->type      = $field_info->type;       // @phpstan-ignore property.notFound
		$this->decimals  = $field_info->decimals;   // @phpstan-ignore property.notFound
		$this->index     = $index;
	}

	/**
	* Put the DatabaseFieldInfo into an uninitialized state.
	*
	* This is called by DatabaseRow to clear field info objects without
	* having to unset/deallocate them. This cuts down on memory allocations
	* in situations where more than one row is read from a query.
	*/
	public function clear() : void
	{
		$this->name      = "";
		$this->orgname   = "";
		$this->table     = "";
		$this->orgtable  = "";
		$this->db        = "";
		$this->length    = -1;
		$this->charsetnr = -1;
		$this->flags     = -1;
		$this->type      = -1;
		$this->decimals  =  0;
		$this->index     = -1;
	}

	public function is_valid() : bool
	{
		return (0 <= $this->index);
	}
}
?>
