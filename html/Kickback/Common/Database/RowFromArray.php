<?php
declare(strict_types=1);

namespace Kickback\Common\Database;

use Kickback\Common\Database\RowInterface;
use Kickback\Common\Exceptions\UnexpectedNullException;

// /**
// * @method bool    bool(string $column_name)
// * @method bool    bool(string $column_name, bool $new_value)
// * @method int     int(string $column_name)
// * @method int     int(string $column_name, int $new_value)
// * @method string  str(string $column_name)
// * @method string  str(string $column_name, string $new_value)
// * @method string  string(string $column_name)
// * @method string  string(string $column_name, string $new_value)
// * @method float   float(string $column_name)
// * @method float   float(string $column_name, float $new_value)
// */
class RowFromArray implements RowInterface
{
    /** @var array<?mixed> */
    private array $contents_;

    /**
    * @param array<string,?mixed> $contents
    */
    function __construct(array $contents)
    {
        $this->contents_ = $contents;
    }

    /**
    * @param array<?mixed> $args
    */
    private function prevent_passing_three_or_more_args(array $args) : void
    {
        if ( count($args) > 1 ) {
            $c = count($args);
            throw new \BadMethodCallException(
                "Too many arguments ($c) passed to column accessor. ".
                'Caller must pass 1 argument when reading a value {column_name}, '.
                'or pass 2 arguments when writing a value {column_name, new_value}.'
            );
        }
    }

    private function access(string $column_name) : mixed
    {
        if ( !array_key_exists($column_name, $this->contents_) ) {
            throw new \OutOfBoundsException();
        }

        $value = $this->contents_[$column_name];
        if ( is_null($value) ) {
            throw new UnexpectedNullException();
        }

        return $value;
    }

    private function access_nullable(string $column_name) : mixed
    {
        if ( !array_key_exists($column_name, $this->contents_) ) {
            throw new \OutOfBoundsException();
        }

        return $this->contents_[$column_name];
    }

    // =====================================================================
    // -------------- non-nullable column accessors ------------------------

    public function bool(string $column_name, bool ...$newValue) : bool
    {
        $this->prevent_passing_three_or_more_args($newValue);
        if ( count($newValue) === 1 ) {
            $this->contents_[$column_name] = $newValue[0];
            return $newValue[0];
        }

        $value = $this->access($column_name);
        if ( !is_bool($value) ) {
            throw new \TypeError();
        }

        return $value;
    }

    public function int(string $column_name, int ...$newValue) : int
    {
        $this->prevent_passing_three_or_more_args($newValue);
        if ( count($newValue) === 1 ) {
            $this->contents_[$column_name] = $newValue[0];
            return $newValue[0];
        }

        $value = $this->access($column_name);
        if ( !is_int($value) ) {
            throw new \TypeError();
        }

        return $value;
    }

    public function str(string $column_name, string ...$newValue) : string
    {
        $this->prevent_passing_three_or_more_args($newValue);
        if ( count($newValue) === 1 ) {
            $this->contents_[$column_name] = $newValue[0];
            return $newValue[0];
        }

        $value = $this->access($column_name);
        if ( !is_string($value) ) {
            throw new \TypeError();
        }

        return $value;
    }

    public function string(string $column_name, string ...$newValue) : string
    {
        $this->prevent_passing_three_or_more_args($newValue);
        if ( count($newValue) === 0 ) {
            return $this->str($column_name);
        } else {
            return $this->str($column_name, $newValue[0]);
        }
    }

    public function float(string $column_name, float ...$newValue) : float
    {
        $this->prevent_passing_three_or_more_args($newValue);
        if ( count($newValue) === 1 ) {
            $this->contents_[$column_name] = $newValue[0];
            return $newValue[0];
        }

        $value = $this->access($column_name);
        if ( !is_float($value) ) {
            throw new \TypeError();
        }

        return $value;
    }

    // =====================================================================
    // ---------------- nullable column accessors --------------------------

    public function nbool(string $column_name, ?bool ...$newValue) : ?bool
    {
        $this->prevent_passing_three_or_more_args($newValue);
        if ( count($newValue) === 1 ) {
            $this->contents_[$column_name] = $newValue[0];
            return $newValue[0];
        }

        $value = $this->access_nullable($column_name);
        if ( !is_null($value) && !is_bool($value) ) {
            throw new \TypeError();
        }

        return $value;
    }

    public function nint(string $column_name, ?int ...$newValue) : ?int
    {
        $this->prevent_passing_three_or_more_args($newValue);
        if ( count($newValue) === 1 ) {
            $this->contents_[$column_name] = $newValue[0];
            return $newValue[0];
        }

        $value = $this->access_nullable($column_name);
        if ( !is_null($value) && !is_int($value) ) {
            throw new \TypeError();
        }

        return $value;
    }

    public function nstr(string $column_name, ?string ...$newValue) : ?string
    {
        $this->prevent_passing_three_or_more_args($newValue);
        if ( count($newValue) === 1 ) {
            $this->contents_[$column_name] = $newValue[0];
            return $newValue[0];
        }

        $value = $this->access_nullable($column_name);
        if ( !is_null($value) && !is_string($value) ) {
            throw new \TypeError();
        }

        return $value;
    }

    public function nstring(string $column_name, ?string ...$newValue) : ?string
    {
        $this->prevent_passing_three_or_more_args($newValue);
        if ( count($newValue) === 0 ) {
            return $this->nstr($column_name);
        } else {
            return $this->nstr($column_name, $newValue[0]);
        }
    }

    public function nfloat(string $column_name, ?float ...$newValue) : ?float
    {
        $this->prevent_passing_three_or_more_args($newValue);
        if ( count($newValue) === 1 ) {
            $this->contents_[$column_name] = $newValue[0];
            return $newValue[0];
        }

        $value = $this->access_nullable($column_name);
        if ( !is_null($value) && !is_float($value) ) {
            throw new \TypeError();
        }

        return $value;
    }
}

?>
