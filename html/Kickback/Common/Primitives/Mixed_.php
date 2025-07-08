<?php
declare(strict_types=1);

namespace Kickback\Common\Primitives;

use Kickback\Common\Primitives\Meta;

/**
* Extended functionality for the `mixed` type.
*/
final class Mixed_
{
    use \Kickback\Common\Traits\StaticClassTrait;

    /**
    * @template TComparable
    *
    * @param      array<array-key, ?TComparable>  $arr
    *
    * @param      ?int          $first_max_index   The lowest 0-based position in the array where the match appears.
    * @param-out  (
    *   $arr is non-empty-array<array-key, null>         ? ?int :
    *   $arr is non-empty-array<array-key, ?TComparable> ? int  : ?int
    *                        )  $first_max_index
    *
    * @param      ?array-key    $first_max_key
    * @param-out  (
    *   $arr is non-empty-array<array-key, null>         ? ?array-key :
    *   $arr is non-empty-array<array-key, ?TComparable> ? array-key  : ?array-key
    *                        )  $first_max_key
    *
    * @param      ?int          $last_max_index    The highest 0-based position in the array where the match appears.
    * @param-out  (
    *   $arr is non-empty-array<array-key, null>         ? ?int :
    *   $arr is non-empty-array<array-key, ?TComparable> ? int  : ?int
    *                        )  $last_max_index
    *
    * @param      ?array-key    $last_max_key
    * @param-out  (
    *   $arr is non-empty-array<array-key, null>         ? ?array-key :
    *   $arr is non-empty-array<array-key, ?TComparable> ? array-key  : ?array-key
    *                        )  $last_max_key
    *
    * @param      ?TComparable  $max_value
    * @param-out  (
    *   $arr is non-empty-array<array-key, null>         ? ?TComparable :
    *   $arr is non-empty-array<array-key, ?TComparable> ? TComparable  : ?TComparable
    *                        )  $max_value
    *
    * @param callable(TComparable,TComparable): int  $cmp
    *
    * @return (
    *   $arr is non-empty-array<array-key, null>         ? false :
    *   $arr is non-empty-array<array-key, ?TComparable> ? true  : false)
    */
    public static function max_pair_implementation(
        array $arr,
        ?int &$first_max_index, int|string|null &$first_max_key,
        ?int &$last_max_index,  int|string|null &$last_max_key,
        mixed &$max_value,
        callable $cmp,
        bool $actually_look_for_min = false) : bool
    {
        if (count($arr) === 0) {
            return false;
        }

        if ( \array_is_list($arr) ) {
            // Thanks to this post for providing a potentially less memory intense
            // way to call the `debug_backtrace` function:
            // https://stackoverflow.com/a/28602181
            $caller_func_name = Meta::outermost_caller_name_within_class();
            throw new \ValueError("Array argument for `$caller_func_name` must be an associative array; this array is a `list` or `sequential` array.");
        }

        $use_callable = true;
        if ( is_string($cmp) && (0 === strcmp($cmp,'\min')) ) {
            $actually_look_for_min = true;
            $use_callable = false;
        } else
        if ( is_string($cmp) && (0 === strcmp($cmp,'\max')) ) {
            $actually_look_for_min = false;
            $use_callable = false;
        }

        $wip_first_max_index = 0;
        $wip_first_max_key   = '';
        $wip_last_max_index  = 0;
        $wip_last_max_key    = '';
        $wip_max_value       = null;

        $index = 0;
        foreach($arr as $key => $value)
        {
            if (!isset($value)) {
                $index++;
                continue;
            }

            // First non-null value in the array.
            // Assign initial values.
            if (!isset($wip_max_value)) {
                $wip_first_max_index = $index;
                $wip_first_max_key   = $key;
                $wip_last_max_index  = $index;
                $wip_last_max_key    = $key;
                $wip_max_value       = $value;
                $index++;
                continue;
            }

            $way = 0;
            if ( $use_callable ) {
                $way = $cmp($wip_max_value, $value);
                //echo '$cmp('.strval($wip_max_value).', '.strval($value).') -> '.strval($way)."\n";
            } else {
                // Tempted to do
                // `$way = ($wip_max_value - $value);`
                // BUT: that wouldn't work for non-numeric comparables.
                if ( $wip_max_value < $value ) {
                    $way = -1; // out-of-order
                } else
                if ( $wip_max_value > $value ) {
                    $way = 1; // already-in-order
                } else {
                    $way = 0;
                }
            }

            if ($actually_look_for_min) {
                $way = -$way;
            }

            if ( $way < 0 ) {
                $wip_first_max_index = $index;
                $wip_first_max_key   = $key;
                $wip_max_value       = $value;
            }

            if ( $way <= 0 ) {
                $wip_last_max_index = $index;
                $wip_last_max_key   = $key;
            }

            $index++;
        }

        // Detect arrays that are full of `null` values.
        if (!isset($wip_max_value)) {
            return false;
        }

        // We stored these in temporaries so that the final results are
        // assigned as "atomically" as possible. That is, if the caller were
        // to execute this function asynchronously, it would only see them
        // get assigned once, instead of potentially almost O(n) times.
        $first_max_index = $wip_first_max_index;
        $first_max_key   = $wip_first_max_key;
        $last_max_index  = $wip_last_max_index;
        $last_max_key    = $wip_last_max_key;
        $max_value       = $wip_max_value;
        return true;
    }

    /**
    * Find first and last pairs with maximum value in an associative array.
    *
    * This function takes an associative array and returns both
    * the lowest-indexed and highest-indexed pairs whose values are
    * the maximum value in the array.
    *
    * In most situations, these pairs will be the same value. But if the array
    * has more than one instance of the maximum value, they will differ.
    *
    * The "lowest-indexed" pair in the array is the _first_ match
    * that `foreach` would encounter while iterating the array.
    *
    * The "highest-indexed" pair in the array is the _last_ match
    * that `foreach` would encounter while iterating the array.
    *
    * This function uses a comparison callback, the `$cmp` parameter,
    * to allow the caller to "define" a natural ordering for any collection
    * of objects/types.
    *
    * This table demonstrates the relationship between ordering and the `$cmp` function:
    * ```
    * ===================================================================
    * | if this is true | then this is true as well  |  and so is this  |
    * |-----------------|----------------------------|------------------|
    * |     a < b       |      $cmp(a,b) < 0         |  $cmp(b,a) > 0   |
    * |     a <= b      |      $cmp(a,b) <= 0        |  $cmp(b,a) >= 0  |
    * |     a > b       |      $cmp(a,b) > 0         |  $cmp(b,a) < 0   |
    * |     a >= b      |      $cmp(a,b) >= 0        |  $cmp(b,a) <= 0  |
    * |_________________|____________________________|__________________|
    * ```
    *
    * If a value is `null`, then its pair will be ignored.
    *
    * If the `$arr` argument has no elements, then none of the referenced
    * parameters will be altered, and the return value will be `false`.
    *
    * If the `$arr` argument has only pairs of `null` values, then none of the
    * referenced parameters will be altered, and the return value will be `false`.
    *
    * If the `$arr` argument is not an associative array, then a \ValueError
    * will be thrown.
    *
    * @template TComparable
    *
    * @param      array<array-key, ?TComparable>  $arr
    *
    * @param      ?int          $first_max_index   The lowest 0-based position in the array where the match appears.
    * @param-out  (
    *   $arr is non-empty-array<array-key, null>         ? ?int :
    *   $arr is non-empty-array<array-key, ?TComparable> ? int  : ?int
    *                        )  $first_max_index
    *
    * @param      ?array-key    $first_max_key
    * @param-out  (
    *   $arr is non-empty-array<array-key, null>         ? ?array-key :
    *   $arr is non-empty-array<array-key, ?TComparable> ? array-key  : ?array-key
    *                        )  $first_max_key
    *
    * @param      ?int          $last_max_index    The highest 0-based position in the array where the match appears.
    * @param-out  (
    *   $arr is non-empty-array<array-key, null>         ? ?int :
    *   $arr is non-empty-array<array-key, ?TComparable> ? int  : ?int
    *                        )  $last_max_index
    *
    * @param      ?array-key    $last_max_key
    * @param-out  (
    *   $arr is non-empty-array<array-key, null>         ? ?array-key :
    *   $arr is non-empty-array<array-key, ?TComparable> ? array-key  : ?array-key
    *                        )  $last_max_key
    *
    * @param      ?TComparable  $max_value
    * @param-out  (
    *   $arr is non-empty-array<array-key, null>         ? ?TComparable :
    *   $arr is non-empty-array<array-key, ?TComparable> ? TComparable  : ?TComparable
    *                        )  $max_value
    *
    * @param callable(TComparable,TComparable): int  $cmp   Returns
    *     -1 if left-side is precedes (ex: "less-than") the right-side,
    *     0 if equal, and
    *     1 if left-side is "greater-than" the right-side.
    *
    * @return (
    *   $arr is non-empty-array<array-key, null>         ? false :
    *   $arr is non-empty-array<array-key, ?TComparable> ? true  : false)
    */
    public static function max_pair_generalized(
        array $arr,
        ?int &$first_max_index, int|string|null &$first_max_key,
        ?int &$last_max_index,  int|string|null &$last_max_key,
        mixed &$max_value,
        callable $cmp) : bool
    {
        return self::max_pair_implementation($arr,
            $first_max_index, $first_max_key,
            $last_max_index,  $last_max_key,
            $max_value,
            $cmp,false);
    }

    private static function unittest_max_pair_generalized() : void
    {
        $arr = ['a' => 'apple', 'b' => 'banana', 'c' => 'zebra', 'd' => 'banana'];
        $cmp = fn($a, $b) => strcmp($a, $b);

        $first = null; $first_key = null;
        $last  = null; $last_key = null;
        $max   = null;

        $ok = self::max_pair_generalized($arr, $first, $first_key, $last, $last_key, $max, $cmp);
        assert($ok === true); /** @phpstan-ignore function.alreadyNarrowedType */
        assert($first === 2 && $first_key === 'c');
        assert($last === 2 && $last_key === 'c');
        assert($max === 'zebra');

        echo("  ".__FUNCTION__."()\n");
    }

    /**
    * @see self::max_pair_generalized
    *
    * @template TComparable
    *
    * @param      array<array-key, ?TComparable>  $arr
    *
    * @param      ?int          $first_min_index   The lowest 0-based position in the array where the match appears.
    * @param-out  (
    *   $arr is non-empty-array<array-key, null>         ? ?int :
    *   $arr is non-empty-array<array-key, ?TComparable> ? int  : ?int
    *                        )  $first_min_index
    *
    * @param      ?array-key    $first_min_key
    * @param-out  (
    *   $arr is non-empty-array<array-key, null>         ? ?array-key :
    *   $arr is non-empty-array<array-key, ?TComparable> ? array-key  : ?array-key
    *                        )  $first_min_key
    *
    * @param      ?int          $last_min_index    The highest 0-based position in the array where the match appears.
    * @param-out  (
    *   $arr is non-empty-array<array-key, null>         ? ?int :
    *   $arr is non-empty-array<array-key, ?TComparable> ? int  : ?int
    *                        )  $last_min_index
    *
    * @param      ?array-key    $last_min_key
    * @param-out  (
    *   $arr is non-empty-array<array-key, null>         ? ?array-key :
    *   $arr is non-empty-array<array-key, ?TComparable> ? array-key  : ?array-key
    *                        )  $last_min_key
    *
    * @param      ?TComparable  $min_value
    * @param-out  (
    *   $arr is non-empty-array<array-key, null>         ? ?TComparable :
    *   $arr is non-empty-array<array-key, ?TComparable> ? TComparable  : ?TComparable
    *                        )  $min_value
    *
    * @param callable(TComparable,TComparable): int  $cmp   Returns
    *     -1 if left-side is precedes (ex: "less-than") the right-side,
    *     0 if equal, and
    *     1 if left-side is "greater-than" the right-side.
    *
    * @return (
    *   $arr is non-empty-array<array-key, null>         ? false :
    *   $arr is non-empty-array<array-key, ?TComparable> ? true  : false)
    */
    public static function min_pair_generalized(
        array $arr,
        ?int &$first_min_index, int|string|null &$first_min_key,
        ?int &$last_min_index,  int|string|null &$last_min_key,
        mixed &$min_value,
        callable $cmp) : bool
    {
        return self::max_pair_implementation($arr,
            $first_min_index, $first_min_key,
            $last_min_index,  $last_min_key,
            $min_value,
            $cmp,true);
    }

    /**
    * @template TComparable
    *
    * @param      array<array-key, ?TComparable>  $arr
    *
    * @param      ?int          $first_min_index   The lowest 0-based position in the array where the minimum appears.
    * @param-out  (
    *   $arr is non-empty-array<array-key, null>         ? ?int :
    *   $arr is non-empty-array<array-key, ?TComparable> ? int  : ?int
    *                        )  $first_min_index
    *
    * @param      ?array-key    $first_min_key
    * @param-out  (
    *   $arr is non-empty-array<array-key, null>         ? ?array-key :
    *   $arr is non-empty-array<array-key, ?TComparable> ? array-key  : ?array-key
    *                        )  $first_min_key
    *
    * @param      ?int          $last_min_index    The highest 0-based position in the array where the minimum appears.
    * @param-out  (
    *   $arr is non-empty-array<array-key, null>         ? ?int :
    *   $arr is non-empty-array<array-key, ?TComparable> ? int  : ?int
    *                        )  $last_min_index
    *
    * @param      ?array-key    $last_min_key
    * @param-out  (
    *   $arr is non-empty-array<array-key, null>         ? ?array-key :
    *   $arr is non-empty-array<array-key, ?TComparable> ? array-key  : ?array-key
    *                        )  $last_min_key
    *
    * @param      ?TComparable  $min_value
    * @param-out  (
    *   $arr is non-empty-array<array-key, null>         ? ?TComparable :
    *   $arr is non-empty-array<array-key, ?TComparable> ? TComparable  : ?TComparable
    *                        )  $min_value
    *
    * @return (
    *   $arr is non-empty-array<array-key, null>         ? false :
    *   $arr is non-empty-array<array-key, ?TComparable> ? true  : false)
    */
    public static function min_pair(
        array $arr,
        ?int &$first_min_index, int|string|null &$first_min_key,
        ?int &$last_min_index,  int|string|null &$last_min_key,
        mixed &$min_value) : bool
    {
        return self::max_pair_implementation($arr,
            $first_min_index, $first_min_key,
            $last_min_index,  $last_min_key,
            $min_value,
            '\min',true);
    }

    private static function unittest_min_pair() : void
    {
        $nullify_params = function() use(&$first, &$first_key, &$last, &$last_key, &$min) : void
        {
            $first = null; $first_key = null;
            $last  = null; $last_key = null;
            $min   = null;
        };

        $populate_params = function() use(&$first, &$first_key, &$last, &$last_key, &$min) : void
        {
            $first = 13; $first_key = 'abc';
            $last =  27; $last_key  = 'xyz';
            $min = 58;
        };

        $check_params_unchanged = function(int $line) use(&$first, &$first_key, &$last, &$last_key, &$min) : void
        {
            $line_  = strval($line);
            $min_   = strval($min);
            $first_ = strval($first);
            $last_  = strval($last);
            $exc_string = "Called from line $line_;  first = `$first`;  first_key = `$first_key`;  last = `$last`;  last_key = `$last_key`;  min = `$min`";
            assert($min   === 58, $exc_string);
            assert($first === 13, $exc_string);
            assert($last  === 27, $exc_string);
            assert($first_key === 'abc', $exc_string);
            assert($last_key  === 'xyz', $exc_string);
        };

        // Normal case: repeated minimum value
        $nullify_params();
        $arr = ['a' => 7, 'b' => 3, 'c' => 5, 'd' => 3];

        $ok = self::min_pair($arr, $first, $first_key, $last, $last_key, $min);
        assert($ok === true); /** @phpstan-ignore function.alreadyNarrowedType */
        assert($first === 1 && $first_key === 'b');
        assert($last === 3 && $last_key === 'd');
        assert($min === 3);

        // Edge case: one non-null element
        $nullify_params();
        $arr = ['x' => null, 'y' => 2];
        $ok = self::min_pair($arr, $first, $first_key, $last, $last_key, $min);
        assert($ok === true);
        assert($first === 1 && $first_key === 'y');
        assert($last === 1 && $last_key === 'y');
        assert($min === 2);

        // Edge case: empty array
        $populate_params();
        $arr = [];
        $ok = self::min_pair($arr, $first, $first_key, $last, $last_key, $min);
        assert($ok === false); /** @phpstan-ignore function.alreadyNarrowedType */
        $check_params_unchanged(__LINE__);

        // Edge case: all nulls
        $populate_params();
        $arr = ['a' => null, 'b' => null];
        $ok = self::min_pair($arr, $first, $first_key, $last, $last_key, $min);
        assert($ok === false); /** @phpstan-ignore function.alreadyNarrowedType */
        $check_params_unchanged(__LINE__);

        // Exception: list (not associative)
        $populate_params();
        $caught = false;
        try {
            self::min_pair([1, 2, 3], $first, $first_key, $last, $last_key, $min);
        } catch (\ValueError $e) {
            $caught = true;
        }
        assert($caught === true);

        $check_params_unchanged(__LINE__);

        echo("  ".__FUNCTION__."()\n");
    }

    /**
    * Find a pair with the minimum value in an associative array.
    *
    * This function takes an associative array and returns the
    * pair whose value is the lowest (minimum) value in the array.
    *
    * If there is more than one instance of the minimum value in the array,
    * then the output will be the one with the lowest-indexed pair in the array
    * (e.g. the _first_ one `foreach` would encounter).
    *
    * The values may be any "comparable" type.
    * (See: https://www.php.net/manual/en/function.min.php)
    *
    * If a value is `null`, then its pair will be ignored.
    *
    * If the `$arr` parameter has no elements, then `$min_key` and `$min_value`
    * will not be altered, and the return value will be `\PHP_INT_MAX` (an invalid index).
    *
    * If the `$arr` argument has only pairs of `null` values, then none of the
    * referenced parameters will be altered, and the return value will be
    * `\PHP_INT_MAX` (an invalid index).
    *
    * If the `$arr` argument is not an associative array, then a \ValueError
    * will be thrown.
    *
    * @template TComparable
    *
    * @param      array<array-key, ?TComparable>  $arr
    * @param      ?array-key                      $min_key
    * @phpstan-param-out (
    *   $arr is non-empty-array<array-key, null>         ? ?array-key :
    *   $arr is non-empty-array<array-key, ?TComparable> ? array-key  : ?array-key)  $min_key
    *
    * @return (
    *   $arr is non-empty-array<array-key, null>         ? \PHP_INT_MAX :
    *   $arr is non-empty-array<array-key, ?TComparable> ? int          : \PHP_INT_MAX
    * )  The 0-based position in the array where the minimum occurs,
    *    or \PHP_INT_MAX if the array is empty or filled with `null` values.
    */
    public static function first_min_pair(array $arr, int|string|null &$min_key,  mixed &$min_value) : int
    {
        if (self::min_pair($arr, $first_min_index, $min_key, $last_min_index, $last_min_key, $min_value)) {
            assert(isset($first_min_index));
            return $first_min_index;
        } else {
            return \PHP_INT_MAX;
        }
    }


    private static function unittest_first_min_pair() : void
    {
        // Normal case
        $arr = ['x' => null, 'y' => 5, 'z' => 1, 'a' => 1, 'b' => 10];
        $key = null;
        $val = null;

        $idx = self::first_min_pair($arr, $key, $val);
        assert($idx === 2);
        assert($key === 'z');
        assert($val === 1);

        // Empty array
        $arr = [];
        $key = null; $val = null;
        $idx = self::first_min_pair($arr, $key, $val);
        assert($idx === \PHP_INT_MAX);
        assert(!isset($key));
        assert(!isset($val));

        // All nulls
        $arr = ['a' => null];
        $idx = self::first_min_pair($arr, $key, $val);
        assert($idx === \PHP_INT_MAX);

        echo("  ".__FUNCTION__."()\n");
    }

    /**
    * Find a pair with the minimum value in an associative array.
    *
    * This function takes an associative array and returns the
    * pair whose value is the lowest (minimum) value in the array.
    *
    * If there is more than one instance of the minimum value in the array,
    * then the output will be the one with the highest-indexed pair in the array
    * (e.g. the _last_ one `foreach` would encounter).
    *
    * The other details of this function are the same as the `first_min_pair`
    * function.
    *
    * @see Mixed_::first_min_pair
    *
    * @template TComparable
    *
    * @param      array<array-key, ?TComparable>  $arr
    * @param      ?array-key                      $min_key
    * @phpstan-param-out (
    *   $arr is non-empty-array<array-key, null>         ? ?array-key :
    *   $arr is non-empty-array<array-key, ?TComparable> ? array-key  : ?array-key)  $min_key
    *
    * @return (
    *   $arr is non-empty-array<array-key, null>         ? \PHP_INT_MAX :
    *   $arr is non-empty-array<array-key, ?TComparable> ? int          : \PHP_INT_MAX
    * )  The 0-based position in the array where the minimum occurs,
    *    or \PHP_INT_MAX if the array is empty or filled with `null` values.
    */
    public static function last_min_pair(array $arr, int|string|null &$min_key,  mixed &$min_value) : int
    {
        if (self::min_pair($arr, $first_min_index, $first_min_key, $last_min_index, $min_key, $min_value)) {
            assert(isset($last_min_index));
            return $last_min_index;
        } else {
            return \PHP_INT_MAX;
        }
    }

    private static function unittest_last_min_pair() : void
    {
        // Normal case
        $arr = ['p' => 8, 'q' => 3, 'r' => null, 's' => 3, 't' => 9];
        $key = null;
        $val = null;

        $idx = self::last_min_pair($arr, $key, $val);
        assert($idx === 3);
        assert($key === 's');
        assert($val === 3);

        // Single pair
        $arr = ['only' => 42];
        $key = null;
        $val = null;
        $idx = self::last_min_pair($arr, $key, $val);
        assert($idx === 0);
        assert($key === 'only');
        assert($val === 42);

        echo("  ".__FUNCTION__."()\n");
    }

    /**
    * @template TComparable
    *
    * @param      array<array-key, ?TComparable>  $arr
    *
    * @param      ?int          $first_max_index   The lowest 0-based position in the array where the maximum appears.
    * @param-out  (
    *   $arr is non-empty-array<array-key, null>         ? ?int :
    *   $arr is non-empty-array<array-key, ?TComparable> ? int  : ?int
    *                        )  $first_max_index
    *
    * @param      ?array-key    $first_max_key
    * @param-out  (
    *   $arr is non-empty-array<array-key, null>         ? ?array-key :
    *   $arr is non-empty-array<array-key, ?TComparable> ? array-key  : ?array-key
    *                        )  $first_max_key
    *
    * @param      ?int          $last_max_index    The highest 0-based position in the array where the maximum appears.
    * @param-out  (
    *   $arr is non-empty-array<array-key, null>         ? ?int :
    *   $arr is non-empty-array<array-key, ?TComparable> ? int  : ?int
    *                        )  $last_max_index
    *
    * @param      ?array-key    $last_max_key
    * @param-out  (
    *   $arr is non-empty-array<array-key, null>         ? ?array-key :
    *   $arr is non-empty-array<array-key, ?TComparable> ? array-key  : ?array-key
    *                        )  $last_max_key
    *
    * @param      ?TComparable  $max_value
    * @param-out  (
    *   $arr is non-empty-array<array-key, null>         ? ?TComparable :
    *   $arr is non-empty-array<array-key, ?TComparable> ? TComparable  : ?TComparable
    *                        )  $max_value
    *
    * @return (
    *   $arr is non-empty-array<array-key, null>         ? false :
    *   $arr is non-empty-array<array-key, ?TComparable> ? true  : false)
    */
    public static function max_pair(
        array $arr,
        ?int &$first_max_index, int|string|null &$first_max_key,
        ?int &$last_max_index,  int|string|null &$last_max_key,
        mixed &$max_value) : bool
    {
        return self::max_pair_implementation($arr,
            $first_max_index, $first_max_key,
            $last_max_index,  $last_max_key,
            $max_value,
            '\max',false);
    }

    private static function unittest_max_pair() : void
    {
        // Normal case: repeated max
        $arr = ['a' => 3, 'b' => 10, 'c' => 10, 'd' => 1];
        $first = null; $first_key = null;
        $last  = null; $last_key = null;
        $max   = null;

        $ok = self::max_pair($arr, $first, $first_key, $last, $last_key, $max);
        assert($ok === true); /** @phpstan-ignore function.alreadyNarrowedType */
        assert($first === 1 && $first_key === 'b');
        assert($last === 2 && $last_key === 'c');
        assert($max === 10);

        // List detection (should throw)
        $caught = false;
        try {
            self::max_pair([1, 2, 3], $first, $first_key, $last, $last_key, $max);
        } catch (\ValueError $e) {
            $caught = true;
        }
        assert($caught === true);

        echo("  ".__FUNCTION__."()\n");
    }

    /**
    * @template TComparable
    *
    * @param      array<array-key, ?TComparable>  $arr
    * @param      ?array-key                      $max_key
    * @phpstan-param-out (
    *   $arr is non-empty-array<array-key, null>         ? ?array-key :
    *   $arr is non-empty-array<array-key, ?TComparable> ? array-key  : ?array-key)  $max_key
    *
    * @return (
    *   $arr is non-empty-array<array-key, null>         ? \PHP_INT_MAX :
    *   $arr is non-empty-array<array-key, ?TComparable> ? int          : \PHP_INT_MAX
    * )  The 0-based position in the array where the maximum occurs,
    *    or \PHP_INT_MAX if the array is empty or filled with `null` values.
    */
    public static function first_max_pair(array $arr, int|string|null &$max_key,  mixed &$max_value) : int
    {
        if (self::max_pair($arr, $first_max_index, $max_key, $last_max_index, $last_max_key, $max_value)) {
            assert(isset($first_max_index));
            return $first_max_index;
        } else {
            return \PHP_INT_MAX;
        }
    }

    private static function unittest_first_max_pair() : void
    {
        // Normal case
        $arr = ['a' => 1, 'b' => 3, 'c' => 3, 'd' => 2];
        $key = null;
        $val = null;

        $idx = self::first_max_pair($arr, $key, $val);
        assert($idx === 1);
        assert($key === 'b');
        assert($val === 3);

        // All-null values
        $arr = ['a' => null, 'b' => null];
        $key = null; $val = null;
        $idx = self::first_max_pair($arr, $key, $val);
        assert($idx === \PHP_INT_MAX);

        echo("  ".__FUNCTION__."()\n");
    }

    /**
    * @template TComparable
    *
    * @param      array<array-key, ?TComparable>  $arr
    * @param      ?array-key                      $max_key
    * @phpstan-param-out (
    *   $arr is non-empty-array<array-key, null>         ? ?array-key :
    *   $arr is non-empty-array<array-key, ?TComparable> ? array-key  : ?array-key)  $max_key
    *
    * @return (
    *   $arr is non-empty-array<array-key, null>         ? \PHP_INT_MAX :
    *   $arr is non-empty-array<array-key, ?TComparable> ? int          : \PHP_INT_MAX
    * )  The 0-based position in the array where the maximum occurs,
    *    or \PHP_INT_MAX if the array is empty or filled with `null` values.
    */
    public static function last_max_pair(array $arr, int|string|null &$max_key,  mixed &$max_value) : int
    {
        if (self::max_pair($arr, $first_max_index, $first_max_key, $last_max_index, $max_key, $max_value)) {
            assert(isset($last_max_index));
            return $last_max_index;
        } else {
            return \PHP_INT_MAX;
        }
    }

    private static function unittest_last_max_pair() : void
    {
        // Normal case
        $arr = ['x' => 2, 'y' => 8, 'z' => 8, 'w' => 4];
        $key = null;
        $val = null;

        $idx = self::last_max_pair($arr, $key, $val);
        assert($idx === 2);
        assert($key === 'z');
        assert($val === 8);

        // Single element
        $arr = ['solo' => 5];
        $idx = self::last_max_pair($arr, $key, $val);
        assert($idx === 0);
        assert($key === 'solo');
        assert($val === 5);

        echo("  ".__FUNCTION__."()\n");
    }

    public static function unittests() : void
    {
        $class_fqn = self::class;
        echo("Running `$class_fqn::unittests()`\n");

        self::unittest_min_pair();
        self::unittest_first_min_pair();
        self::unittest_last_min_pair();

        self::unittest_max_pair();
        self::unittest_first_max_pair();
        self::unittest_last_max_pair();

        self::unittest_max_pair_generalized();

        echo("  ... passed.\n\n");
    }
}
?>

<?php












































//
// class Foo
// {
//     /** @return 'X' */
//     public static function ecks() : string
//     {
//         return 'X';
//     }
//
//     /** @return 'Y' */
//     public static function wye() : string
//     {
//         return 'Y';
//     }
//
//     /**
//     * A concatenation of the results of `ecks` and `wye`.
//     * @return 'XY'
//     */
//     public static function ecks() : string
//     {
//         return self::ecks() . self::wye();
//     }
// }
//
//     /**
//     * Generic function that takes an associative array and returns the
//     * pair whose value is the highest value in the array.
//     *
//     * If there is more than one value, it will be the lowest-indexed
//     * pair in the array (e.g. the first one `foreach` would encounter).
//     *
//     * The values may be any "comparable" type.
//     * (See: https://www.php.net/manual/en/function.min.php)
//     *
//     * If a value is `null`, it will be ignored.
//     *
//     * If the `$arr` parameter has no elements, then `$min_key` and `$min_value`
//     * will not be altered, and the return value will be `null`.
//     *
//     * @param array<array-key, mixed>
//     *
//     * @return int  The 0-based position in the array where the minimum occurs.
//     */
//     public static function max_pair(array $arr, string &$min_key, mixed &$min_value) : mixed
//     {
//         if (count($arr)) {
//             return null;
//         }
//
//         $wip_min_index = 0;
//         $wip_min_key   = array_key_first($arr);
//         $wip_min_value = $arr[$wip_min_key];
//         $index = 0;
//         foreach($arr as $key => $value)
//         {
//             if (!isset($value)) {
//                 $index++;
//                 continue;
//             }
//
//             if ( $value < $wip_min_value ) {
//                 $wip_min_index = $index;
//                 $wip_min_key   = $key;
//                 $wip_min_value = $value;
//             }
//             $index++;
//         }
//
//         // We stored these in temporaries so that the final results are
//         // assigned as "atomically" as possible. That is, if the caller were
//         // to execute this function asynchronously, it would only see them
//         // get assigned once, instead of potentially almost O(n) times.
//         $min_key   = $wip_min_key;
//         $min_value = $wip_min_value;
//         return $wip_min_index;
//     }
//
//     /**
//     * Generic function that takes an associative array and returns the
//     * pair whose value is the lowest value in the array.
//     *
//     * If there is more than one value, it will be the lowest-indexed
//     * pair in the array (e.g. the first one `foreach` would encounter).
//     *
//     * The values must be of `int` or `float` types.
//     * (Future directions: any comparable?
//     * See: https://www.php.net/manual/en/function.min.php
//     * Problem: )
//     *
//     * If a value is `null`, it will be ignored.
//     *
//     * If the `$arr` parameter has no elements, then `$min_key` and `$min_value`
//     * will not be altered, and \PHP_INT_MAX will be returned.
//     *
//     * @param array<array-key, mixed>
//     *
//     * @return int  The 0-based position in the array where the minimum occurs.
//     */
//     public static function min_pair_(array $arr, string &$min_key, int &$min_value) : int
//     {
//         if (count($arr)) {
//             return \PHP_INT_MAX;
//         }
//
//         $key = array_key_first($arr);
//         $wip_min_value = $arr[$key0];
//         $index = 0;
//         foreach($arr as $key => $value)
//         {
//             if (!isset($value)) {
//                 $index++;
//                 continue;
//             }
//
//
//             $index++;
//         }
//     }
?>
