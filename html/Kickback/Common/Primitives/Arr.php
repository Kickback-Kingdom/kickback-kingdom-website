<?php
declare(strict_types=1);

namespace Kickback\Common\Primitives;

use Kickback\Common\Exceptions\Reporting\Report;
use Kickback\Common\Meta\Location;
use Kickback\Common\Traits\StaticClassTrait;
use Kickback\Common\Traits\ClassOfConstantIntegersTrait;

/**
* Extended functionality for the `array` type.
*
* This class originally existed to silence the PHPStan error about `empty` being not allowed.
*/
final class Arr
{
    use \Kickback\Common\Traits\StaticClassTrait;

    /**
    * A type-safe alternative to the `empty` builtin.
    *
    * This can be used to make PHPStan stop complaining about
    * `empty($some_array)` being "not allowed" and telling us
    * to "use a more strict comparison".
    *
    * @param array<mixed>|array<string,mixed> $x
    */
    public static function empty(?array $x) : bool
    {
        return !isset($x) || (0 === count($x));
    }

    /**
    * @param array<mixed>|array<string,mixed> $var
    */
    public static function is_longer_than(?array $var, int $minLength) : bool
    {
        return !is_null($var) && count($var) >= $minLength;
    }

    /**
    * Future directions: support associative arrays.
    * Thanks to: https://stackoverflow.com/a/7858985
    * @param     mixed[]  $arr
    * @param-out scalar[] $arr
    */
    public static function flatten_in_place(array &$arr) : void
    {
        $idx = 0;
        while($idx < count($arr)) // process stack until done
        {
            $value = $arr[$idx];

            // Scalars: leave them as they are, and advance to next element.
            if (!is_array($value)) {
                $idx++;
                continue;
            }

            // Remove empty arrays.
            if (0 === count($value)) {
                array_splice($arr, $idx, 1);
                continue;
            }

            // Move values from nested array to parent array.
            array_splice($arr, $idx, 1, $value);
        }
    }

    /**
    * Future directions: support associative arrays.
    * @param  mixed[] $arr
    * @return scalar[]
    */
    public static function flatten(array $arr) : array
    {
        // "copy" the array.
        $flat = $arr;

        // flatten
        self::flatten_in_place($flat);

        // done
        return $flat;
    }

    private static function unittest_flatten() : void
    {
        echo("  ".__FUNCTION__."()\n");

        // Identity properties.
        assert(self::flatten([]) === []);
        assert(self::flatten([1]) === [1]);
        assert(self::flatten([1,2]) === [1,2]);
        assert(self::flatten([1,2,3]) === [1,2,3]);
        assert(self::flatten([1,2,3,4]) === [1,2,3,4]);

        // Index preservation
        assert(self::flatten([2,1]) === [2,1]);
        assert(self::flatten([3,2,1]) === [3,2,1]);
        assert(self::flatten([4,3,2,1]) === [4,3,2,1]);

        // Element preservation
        assert(self::flatten([1,1]) === [1,1]);
        assert(self::flatten([1,1,1]) === [1,1,1]);
        assert(self::flatten([1,1,1,1]) === [1,1,1,1]);

        // Nominal cases, 2D
        assert(self::flatten([[1]]) === [1]);
        assert(self::flatten([1,[2]]) === [1,2]);
        assert(self::flatten([[1],2]) === [1,2]);
        assert(self::flatten([[1,2]]) === [1,2]);
        assert(self::flatten([[1],[2]]) === [1,2]);

        assert(self::flatten([1,2,[3]]) === [1,2,3]);
        assert(self::flatten([1,[2],3]) === [1,2,3]);
        assert(self::flatten([[1],2,3]) === [1,2,3]);
        assert(self::flatten([1,[2,3]]) === [1,2,3]);
        assert(self::flatten([[1,2],3]) === [1,2,3]);
        assert(self::flatten([[1,2,3]]) === [1,2,3]);
        assert(self::flatten([1,[2],[3]]) === [1,2,3]);
        assert(self::flatten([[1],[2],3]) === [1,2,3]);
        assert(self::flatten([[1],2,[3]]) === [1,2,3]);
        assert(self::flatten([[1],[2,3]]) === [1,2,3]);
        assert(self::flatten([[1,2],[3]]) === [1,2,3]);

        assert(self::flatten([1, 2, 3, [4]]) === [1, 2, 3, 4]);
        assert(self::flatten([1, 2, [3], 4]) === [1, 2, 3, 4]);
        assert(self::flatten([1, [2], 3, 4]) === [1, 2, 3, 4]);
        assert(self::flatten([[1], 2, 3, 4]) === [1, 2, 3, 4]);
        assert(self::flatten([1, 2, [3, 4]]) === [1, 2, 3, 4]);
        assert(self::flatten([1, [2, 3], 4]) === [1, 2, 3, 4]);
        assert(self::flatten([[1, 2], 3, 4]) === [1, 2, 3, 4]);
        assert(self::flatten([1, [2, 3, 4]]) === [1, 2, 3, 4]);
        assert(self::flatten([[1, 2, 3], 4]) === [1, 2, 3, 4]);
        assert(self::flatten([[1, 2, 3, 4]]) === [1, 2, 3, 4]);
        assert(self::flatten([[1, 2], 3, [4]]) === [1, 2, 3, 4]);
        assert(self::flatten([[1, 2], [3], 4]) === [1, 2, 3, 4]);
        assert(self::flatten([1, [2, 3], [4]]) === [1, 2, 3, 4]);
        assert(self::flatten([[1], [2, 3], 4]) === [1, 2, 3, 4]);
        assert(self::flatten([1, [2], [3, 4]]) === [1, 2, 3, 4]);
        assert(self::flatten([[1], 2, [3, 4]]) === [1, 2, 3, 4]);
        assert(self::flatten([[1, 2, 3], [4]]) === [1, 2, 3, 4]);
        assert(self::flatten([[1, 2], [3, 4]]) === [1, 2, 3, 4]);
        assert(self::flatten([[1], [2, 3, 4]]) === [1, 2, 3, 4]);
        assert(self::flatten([[1], [2], [3], 4]) === [1, 2, 3, 4]);
        assert(self::flatten([[1], [2], 3, [4]]) === [1, 2, 3, 4]);
        assert(self::flatten([[1], 2, [3], [4]]) === [1, 2, 3, 4]);
        assert(self::flatten([1, [2], [3], [4]]) === [1, 2, 3, 4]);
        assert(self::flatten([[1], [2], [3, 4]]) === [1, 2, 3, 4]);
        assert(self::flatten([[1], [2, 3], [4]]) === [1, 2, 3, 4]);
        assert(self::flatten([[1, 2], [3], [4]]) === [1, 2, 3, 4]);
        assert(self::flatten([[1], [2], [3], [4]]) === [1, 2, 3, 4]);

        // Nominal cases, 3D
        assert(self::flatten([[[1]]]) === [1]);
        assert(self::flatten([[1,[2]]]) === [1,2]);
        assert(self::flatten([[[1],2]]) === [1,2]);
        assert(self::flatten([[[1,2]]]) === [1,2]);
        assert(self::flatten([1,[[2]]]) === [1,2]);
        assert(self::flatten([[[1]],2]) === [1,2]);
        assert(self::flatten([[1],[[2]]]) === [1,2]);
        assert(self::flatten([[[1]],[2]]) === [1,2]);
        assert(self::flatten([[[1]],[[2]]]) === [1,2]);

        assert(self::flatten([[[1,2,3]]]) === [1,2,3]);
        assert(self::flatten([[1, 2, 3, [4]]]) === [1, 2, 3, 4]);
        assert(self::flatten([[[1], 2, 3, 4]]) === [1, 2, 3, 4]);
        assert(self::flatten([[1, 2, [3, 4]]]) === [1, 2, 3, 4]);
        assert(self::flatten([[1, [2, 3], 4]]) === [1, 2, 3, 4]);
        assert(self::flatten([[[1, 2], 3, 4]]) === [1, 2, 3, 4]);
        assert(self::flatten([1, [2, 3, [4]]]) === [1, 2, 3, 4]);
        assert(self::flatten([[[1], 2, 3], 4]) === [1, 2, 3, 4]);
        assert(self::flatten([1, 2, [3, [4]]]) === [1, 2, 3, 4]);
        assert(self::flatten([1, 2, [[3], 4]]) === [1, 2, 3, 4]);
        assert(self::flatten([1, [2, [3]], 4]) === [1, 2, 3, 4]);
        assert(self::flatten([1, [[2], 3], 4]) === [1, 2, 3, 4]);
        assert(self::flatten([[1, [2]], 3, 4]) === [1, 2, 3, 4]);
        assert(self::flatten([[[1], 2], 3, 4]) === [1, 2, 3, 4]);
        assert(self::flatten([1, [2, [3], 4]]) === [1, 2, 3, 4]);
        assert(self::flatten([[1, [2], 3], 4]) === [1, 2, 3, 4]);
        assert(self::flatten([1, [[2], 3, 4]]) === [1, 2, 3, 4]);
        assert(self::flatten([[1, 2, [3]], 4]) === [1, 2, 3, 4]);
        assert(self::flatten([[[1, 2], [3, 4]]]) === [1, 2, 3, 4]);
        assert(self::flatten([[[1, 2], 3, [4]]]) === [1, 2, 3, 4]);
        assert(self::flatten([[[1, 2], [3], 4]]) === [1, 2, 3, 4]);
        assert(self::flatten([[1, [2, 3], [4]]]) === [1, 2, 3, 4]);
        assert(self::flatten([[[1], [2, 3], 4]]) === [1, 2, 3, 4]);
        assert(self::flatten([[1, [2], [3, 4]]]) === [1, 2, 3, 4]);
        assert(self::flatten([[[1], 2, [3, 4]]]) === [1, 2, 3, 4]);
        assert(self::flatten([[[1, 2], [3]], 4]) === [1, 2, 3, 4]);
        assert(self::flatten([1, [[2], [3, 4]]]) === [1, 2, 3, 4]);
        assert(self::flatten([[1, 2], [3, [4]]]) === [1, 2, 3, 4]);
        assert(self::flatten([[1, 2], [[3], 4]]) === [1, 2, 3, 4]);
        assert(self::flatten([[1, [2]], [3, 4]]) === [1, 2, 3, 4]);
        assert(self::flatten([[[1], 2], [3, 4]]) === [1, 2, 3, 4]);
        assert(self::flatten([[1, [2]], [3, [4]]]) === [1, 2, 3, 4]);
        assert(self::flatten([[1, [2]], [[3], 4]]) === [1, 2, 3, 4]);
        assert(self::flatten([[[1], 2], [3, [4]]]) === [1, 2, 3, 4]);
        assert(self::flatten([[[1], 2], [[3], 4]]) === [1, 2, 3, 4]);
        assert(self::flatten([[1], [2], [3], [[4]]]) === [1, 2, 3, 4]);
        assert(self::flatten([[1], [2], [[3]], [4]]) === [1, 2, 3, 4]);
        assert(self::flatten([[1], [[2]], [3], [4]]) === [1, 2, 3, 4]);
        assert(self::flatten([[[1]], [2], [3], [4]]) === [1, 2, 3, 4]);
        assert(self::flatten([[1], [2], [[3], [4]]]) === [1, 2, 3, 4]);
        assert(self::flatten([[1], [[2], [3]], [4]]) === [1, 2, 3, 4]);
        assert(self::flatten([[[1], [2]], [3], [4]]) === [1, 2, 3, 4]);
        assert(self::flatten([[1], [[2], [3], [4]]]) === [1, 2, 3, 4]);
        assert(self::flatten([[[1], [2], [3]], [4]]) === [1, 2, 3, 4]);
        assert(self::flatten([[1], [2], [[3]], [[4]]]) === [1, 2, 3, 4]);
        assert(self::flatten([[1], [[2]], [[3]], [4]]) === [1, 2, 3, 4]);
        assert(self::flatten([[[1]], [[2]], [3], [4]]) === [1, 2, 3, 4]);
        assert(self::flatten([[[1], [2]], [[3], [4]]]) === [1, 2, 3, 4]);
        assert(self::flatten([[1], [[2]], [3], [[4]]]) === [1, 2, 3, 4]);
        assert(self::flatten([[[1]], [2], [[3]], [4]]) === [1, 2, 3, 4]);
        assert(self::flatten([[[1]], [[2]], [[3], [4]]]) === [1, 2, 3, 4]);
        assert(self::flatten([[[1]], [[2], [3]], [[4]]]) === [1, 2, 3, 4]);
        assert(self::flatten([[[1], [2]], [[3]], [[4]]]) === [1, 2, 3, 4]);
        assert(self::flatten([[[1]], [[2]], [[3]], [[4]]]) === [1, 2, 3, 4]);

        // Higher dimensions
        assert(self::flatten([[[[[[1]]]]]]) === [1]);
        assert(self::flatten([[[[[[1,2]]]]]]) === [1,2]);
        assert(self::flatten([[[[[[1]]]]],2]) === [1,2]);
        assert(self::flatten([1,[[[[[2]]]]]]) === [1,2]);
        assert(self::flatten([[[[[[1]]]]],[[[[[2]]]]]]) === [1, 2]);

        // Handling empty arrays.
        assert(self::flatten([[]]) === []);
        assert(self::flatten([[],[]]) === []);
        assert(self::flatten([[],[],[]]) === []);
        assert(self::flatten([[[]]]) === []);
        assert(self::flatten([[[],[]]]) === []);
        assert(self::flatten([[[],[]],[]]) === []);
        assert(self::flatten([[],[[],[]]]) === []);
        assert(self::flatten([[[],[]],[[],[]]]) === []);
        assert(self::flatten([[[[[[]]]]]]) === []);

        // Mixed empty+not arrays.
        assert(self::flatten([1, []]) === [1]);
        assert(self::flatten([[], 1]) === [1]);
        assert(self::flatten([1, 2, []]) === [1, 2]);
        assert(self::flatten([1, [], 2]) === [1, 2]);
        assert(self::flatten([[], 1, 2]) === [1, 2]);
        assert(self::flatten([[], [], 1]) === [1]);
        assert(self::flatten([[], 1, []]) === [1]);
        assert(self::flatten([1, [], []]) === [1]);
        assert(self::flatten([[], [1, 2]]) === [1, 2]);
        assert(self::flatten([[1, 2], []]) === [1, 2]);
        assert(self::flatten([[[], []], 1]) === [1]);
        assert(self::flatten([1, [[], []]]) === [1]);
        assert(self::flatten([[], [1, []]]) === [1]);
        assert(self::flatten([[], [[], 1]]) === [1]);
        assert(self::flatten([[1, 2], []]) === [1, 2]); // @phpstan-ignore function.alreadyNarrowedType
        assert(self::flatten([[1, []], 2]) === [1, 2]);
        assert(self::flatten([[[], 1], 2]) === [1, 2]);
        assert(self::flatten([1, [2, []]]) === [1, 2]);
        assert(self::flatten([1, [[], 2]]) === [1, 2]);
        assert(self::flatten([[], [1, 2]]) === [1, 2]); // @phpstan-ignore function.alreadyNarrowedType
        assert(self::flatten([[1, 2], []]) === [1, 2]); // @phpstan-ignore function.alreadyNarrowedType
        assert(self::flatten([[1, []], 2]) === [1, 2]); // @phpstan-ignore function.alreadyNarrowedType
        assert(self::flatten([[[], 1], 2]) === [1, 2]); // @phpstan-ignore function.alreadyNarrowedType
        assert(self::flatten([[1, 2, []]]) === [1, 2]);
        assert(self::flatten([[1, [], 2]]) === [1, 2]);
        assert(self::flatten([[[], 1, 2]]) === [1, 2]);
        // Why does PHPStan pick up some of those, but not the rest? iunno. ¯\_(ツ)_/¯

        assert(self::flatten([[1, 2, 3, []]]) === [1, 2, 3]);
        assert(self::flatten([[[], 1, 2, 3]]) === [1, 2, 3]);
        assert(self::flatten([1, [2, 3, []]]) === [1, 2, 3]);
        assert(self::flatten([[[], 1, 2], 3]) === [1, 2, 3]);
        assert(self::flatten([1, 2, [3, []]]) === [1, 2, 3]);
        assert(self::flatten([1, 2, [[], 3]]) === [1, 2, 3]);
        assert(self::flatten([1, [2, []], 3]) === [1, 2, 3]);
        assert(self::flatten([1, [[], 2], 3]) === [1, 2, 3]);
        assert(self::flatten([[1, []], 2, 3]) === [1, 2, 3]);
        assert(self::flatten([[[], 1], 2, 3]) === [1, 2, 3]);
        assert(self::flatten([1, [2, [], 3]]) === [1, 2, 3]);
        assert(self::flatten([[1, [], 2], 3]) === [1, 2, 3]);
        assert(self::flatten([1, [[], 2, 3]]) === [1, 2, 3]);
        assert(self::flatten([[1, 2, []], 3]) === [1, 2, 3]);

        assert(self::flatten([[[[[[]]]]],1]) === [1]);
        assert(self::flatten([1,[[[[[]]]]]]) === [1]);
        assert(self::flatten([[[[[[1,[]]]]]]]) === [1]);
        assert(self::flatten([[[[[[[],1]]]]]]) === [1]);
        assert(self::flatten([[[[[[]]]]],1,[]]) === [1]);
        assert(self::flatten([[],1,[[[[[]]]]]]) === [1]);
        assert(self::flatten([[[[[[1]]]]],[[[[[]]]]]]) === [1]);
        assert(self::flatten([[[[[[]]]]],[[[[[1]]]]]]) === [1]);
        assert(self::flatten([[[[[[1]]]]],2,[]]) === [1,2]);
        assert(self::flatten([[],1,[[[[[2]]]]]]) === [1,2]);
        assert(self::flatten([[[[[[]]]]],1,[2]]) === [1,2]);
        assert(self::flatten([[1],2,[[[[[]]]]]]) === [1,2]);
        assert(self::flatten([[[[[[1]]]]],[],[2]]) === [1,2]);
        assert(self::flatten([[[[[[1]]]]],[2],[]]) === [1,2]);
        assert(self::flatten([[],[1],[[[[[2]]]]]]) === [1,2]);
        assert(self::flatten([[1],[],[[[[[2]]]]]]) === [1,2]);
    }

    /**
    * @param      array<mixed>|\ArrayAccess<mixed>  $to_validate
    * @param      string|int                        $key
    * @param      null|array<string>|(\Closure(int):string) $int_key_to_name
    * @param      ?(Arr__IndexType::*)              $index_type
    * @param-out  Arr__IndexType::*                 $index_type
    */
    private static function lookup_index_name(
        array|\ArrayAccess    $to_validate,
        string|int            $key,
        array|\Closure|null   $int_key_to_name,
        ?int                  &$index_type = null
    ) : string
    {
        if ( \is_string($key) ) {
            $index_type = Arr__IndexType::NAME;
            return $key;
        } else
        if ( isset($int_key_to_name) ) {
            if (is_array($int_key_to_name)) {
                $index_type = Arr__IndexType::NAME;
                return $int_key_to_name[$key];
            } else {
                assert($int_key_to_name instanceof \Closure);
                $index_type = Arr__IndexType::NAME;
                return $int_key_to_name($key);
            }
        } else {
            $index_type = Arr__IndexType::INDEX;
            return \strval($key);
        }
    }

        // TODO: Delete this comment when git commit or confident
        // The last argument is the "stack depth" for `enforce` to use when
        // figuring out the caller's file/func/line. By default it is 0,
        // which would be this very line below the comment. But that isn't
        // helpful for _our_ caller.
        // So it's a value of 2 because there are two layers here:
        // * One layer within the `Arr` class:
        //     * We entered through `validate_key_exists` or `validate_is_string`
        //         (or anything else that needs to validate key existence),
        //         so whichever it is, that will contribute 1 stack frame.
        // * One layer external to `Arr`:
        //     * The code, that called whichever above function was chosen,
        //         (`validate_key_exists, `validate_is_string`, etc)
        //         is the _actual_ caller's file/func/line, so
        //         that will also contribute 1 stack frame.

    // TODO: I'm not sure if the validate_* functions are actually "impure".
    // They modify the $errors parameter (reference param).
    // But otherwise, if they the same inputs are passed, the same output
    // will be given. (The true/false depends only on the condition of
    // the validation, and nothing else.)
    /**
    * Validate that `$to_validate[$key]` exists.
    *
    * @param array<mixed>|\ArrayAccess<mixed>  $to_validate
    * @param string|int                        $key
    * @param Report|string                     $errors
    * @param ($key is int ? null|array<string>|(\Closure(int):string) : null
    *        )                                 $int_key_to_name
    * @param ?string                           $in_file
    * @param ?string                           $in_function
    * @param int                               $at_line
    * @param int<0,max>                        $at_trace_depth
    * @param ?kkdebug_backtrace_paranoid_a     $trace
    *
    * @phpstan-impure
    * @throws void
    */
    public static function validate_key_exists(
        array|\ArrayAccess    $to_validate,
        string|int            $key,
        Report|string         &$errors,
        array|\Closure|null   $int_key_to_name = null,
        ?string               $in_file = null,
        ?string               $in_function = null,
        int                   $at_line = \PHP_INT_MIN,
        int                   $at_trace_depth = 0,
        ?array                $trace = null
    ) : bool
    {
        if ( \array_key_exists($key, $to_validate) ) {
            return true;
        }

        if (Location::need_backtrace(
            $in_file, $in_function, $at_line, $at_trace_depth, $trace))
        {
            // Capture desired frame from \debug_backtrace, if it is needed.
            // If `$at_trace_depth === 0`, this is the depth of the caller of this function.
            // Optimization: Capture backtrace as close to caller as possible (minimizes number of frames to allocate)
            // Optimization: Use `DEBUG_BACKTRACE_IGNORE_ARGS` to avoid allocating arrays for argument metadata.
            $at_trace_depth = 2 + $at_trace_depth;
            $trace = \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $at_trace_depth);
        }
        Location::process_info($in_file, $in_function, $at_line, $at_trace_depth, $trace);

        $name = self::lookup_index_name($to_validate, $key, $int_key_to_name, $index_type);
        switch($index_type) {
            case Arr__IndexType::NAME:  $msg = "Required field '$name' not found."; break;
            case Arr__IndexType::INDEX: $msg = "Required value at index '$name' not found."; break;
            default:
                $noun_for_index = Arr__IndexType::name_of($index_type);
                $msg = "Required $noun_for_index '$name' not found.";
                assert(false); // Should trigger if there's ever another index type, for some reason.
        }
        Report::enforce($errors, false, $msg, $in_file, $in_function, $at_line);
        return false;
    }

    private static function unittest_validate_key_exists() : void
    {
        echo("  ".__FUNCTION__."()\n");

        $input = [
            'a' => 'x',
            'b' => '',
            'c' => 5,
            'd' => null
        ];

        $report = Report::blank();
        assert($report->count() === 0);

        assert(self::validate_key_exists($input, 'a', $report));
        assert(self::validate_key_exists($input, 'b', $report));
        assert(self::validate_key_exists($input, 'c', $report));
        assert(self::validate_key_exists($input, 'd', $report));
        assert($report->count() === 0);

        assert(!self::validate_key_exists($input, 'foo', $report));
        assert($report->count() === 1);

        assert(!self::validate_key_exists($input, '', $report));
        assert($report->count() === 2);
    }

    /**
    * @param array<mixed>|\ArrayAccess<mixed>  $to_validate
    * @param string|int                        $key
    * @param Report|string                     $errors
    * @param null|array<string>|(\Closure(int):string) $int_key_to_name
    * @param ?string                           $in_file
    * @param ?string                           $in_function
    * @param int                               $at_line
    * @param int<0,max>                        $at_trace_depth
    * @param ?kkdebug_backtrace_paranoid_a     $trace
    *
    * @phpstan-impure
    * @throws voidd
    */
    private static function type_validation_error(
        array|\ArrayAccess    $to_validate,
        string|int            $key,
        Report|string         &$errors,
        array|\Closure|null   $int_key_to_name,
        string                $expected_type,
        ?string               $in_file,
        ?string               $in_function,
        int                   $at_line = \PHP_INT_MIN,
        int                   $at_trace_depth,
        ?array                $trace
    ) : void
    {
        if (Location::need_backtrace(
            $in_file, $in_function, $at_line, $at_trace_depth, $trace))
        {
            // Capture desired frame from \debug_backtrace, if it is needed.
            // If `$at_trace_depth === 0`, this is the depth of the caller of this function.
            // Optimization: Capture backtrace as close to caller as possible (minimizes number of frames to allocate)
            // Optimization: Use `DEBUG_BACKTRACE_IGNORE_ARGS` to avoid allocating arrays for argument metadata.
            $at_trace_depth = 2 + $at_trace_depth;
            $trace = \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $at_trace_depth);
        }
        Location::process_info($in_file, $in_function, $at_line, $at_trace_depth, $trace);

        $value = $to_validate[$key];
        $name = self::lookup_index_name($to_validate, $key, $int_key_to_name, $index_type);
        $got_type = \get_debug_type($value);
        switch($index_type) {
            case Arr__IndexType::NAME:  $noun_for_index = 'Field'; break;
            case Arr__IndexType::INDEX: $noun_for_index = 'Index'; break;
            default:
                $noun_for_index = Arr__IndexType::name_of($index_type);
                assert(false); // Should trigger if there's ever another index type, for some reason.
        }
        Report::enforce($errors, false,
            "$noun_for_index '$name' must have type `$expected_type`, but `$got_type` was given.",
            $in_file, $in_function, $at_line);
    }

    /**
    * Validate that `$to_validate[$key]` is a `string` type.
    *
    * This will also call `validate_key_exists` before testing the type.
    *
    * In the case of validating multiple array fields, it is recommended
    * to validate existance for all fields first, THEN validate types.
    * Example:
    * ```
    * $input  = MyExampleClass::receive_input_array();
    * $report = Report::blank();
    *
    * // Validate existence in first pass.
    * $Arr::validate_key_exists($input, 'foo', $report);
    * $Arr::validate_key_exists($input, 'bar', $report);
    * $Arr::validate_key_exists($input, 'baz', $report);
    *
    * // Validate typing in second pass.
    * $Arr::validate_is_string($input, 'foo', $report);
    * $Arr::validate_is_string($input, 'bar', $report);
    * $Arr::validate_is_string($input, 'baz', $report);
    * ```
    *
    * Validation in multiple passes, as above, will cause similar validation
    * failures to be grouped together in the error report, which may improve
    * readability.
    *
    * If the given key does not exist, then this will return `false`, but
    * it will not add anything to the `$errors` list. (This behavior
    * is important for multi-pass validation: if the key already failed
    * an existence validation, we wouldn't want to add the error again
    * and end up with unnecessarily redundant error messages.)
    *
    * @param array<mixed>|\ArrayAccess<mixed>  $to_validate
    * @param string|int                        $key
    * @param Report|string                     $errors
    * @param ($key is int ? null|array<string>|(\Closure(int):string) : null
    *        )                                 $int_key_to_name
    * @param ?string                           $in_file
    * @param ?string                           $in_function
    * @param int                               $at_line
    * @param int<0,max>                        $at_trace_depth
    * @param ?kkdebug_backtrace_paranoid_a     $trace
    *
    * @phpstan-impure
    * @throws void
    */
    public static function validate_is_string(
        array|\ArrayAccess    $to_validate,
        string|int            $key,
        Report|string         &$errors,
        array|\Closure|null   $int_key_to_name = null,
        ?string               $in_file = null,
        ?string               $in_function = null,
        int                   $at_line = \PHP_INT_MIN,
        int                   $at_trace_depth = 0,
        ?array                $trace = null
    ) : bool
    {
        // Only check this on fields that exist.
        if ( !\array_key_exists($key, $to_validate) ) {
            return false;
        }

        // Now check for stringiness
        $value = $to_validate[$key];
        if ( is_string($value) ) {
            return true;
        }

        // It wasn't a string. Error handling time.
        self::type_validation_error(
            $to_validate, $key, $errors, $int_key_to_name, 'string',
            $in_file, $in_function, $at_line, $at_trace_depth+1, $trace);
        return false;
    }

    private static function unittest_validate_is_string() : void
    {
        echo("  ".__FUNCTION__."()\n");

        $input = [
            'a' => 'x',
            'b' => '',
            'c' => 5,
            'd' => null
        ];

        $report = Report::blank();
        assert($report->count() === 0);

        assert(self::validate_is_string($input, 'a', $report));
        assert(self::validate_is_string($input, 'b', $report));
        assert($report->count() === 0);

        assert(!self::validate_is_string($input, 'foo', $report));
        assert($report->count() === 0);

        assert(!self::validate_is_string($input, '', $report));
        assert($report->count() === 0);

        assert(!self::validate_is_string($input, 'c', $report));
        assert($report->count() === 1);

        assert(!self::validate_is_string($input, 'd', $report));
        assert($report->count() === 2);
    }

    /**
    * Validate that `$to_validate[$key]` is an `int` type.
    *
    * Semantics are otherwise identical to `validate_is_string`.
    *
    * @see Arr::validate_is_string
    *
    * @param array<mixed>|\ArrayAccess<mixed>  $to_validate
    * @param string|int                        $key
    * @param Report|string                     $errors
    * @param ($key is int ? null|array<string>|(\Closure(int):string) : null
    *        )                                 $int_key_to_name
    * @param ?string                           $in_file
    * @param ?string                           $in_function
    * @param int                               $at_line
    * @param int<0,max>                        $at_trace_depth
    * @param ?kkdebug_backtrace_paranoid_a     $trace
    *
    * @phpstan-impure
    * @throws void
    */
    public static function validate_is_int(
        array|\ArrayAccess    $to_validate,
        string|int            $key,
        Report|string         &$errors,
        array|\Closure|null   $int_key_to_name = null,
        ?string               $in_file = null,
        ?string               $in_function = null,
        int                   $at_line = \PHP_INT_MIN,
        int                   $at_trace_depth = 0,
        ?array                $trace = null
    ) : bool
    {
        // Only check this on fields that exist.
        if ( !\array_key_exists($key, $to_validate) ) {
            return false;
        }

        // Now check for integer-type-having
        $value = $to_validate[$key];
        if ( is_int($value) ) {
            return true;
        }

        // It wasn't an integer. Error handling time.
        self::type_validation_error(
            $to_validate, $key, $errors, $int_key_to_name, 'int',
            $in_file, $in_function, $at_line, $at_trace_depth+1, $trace);
        return false;
    }

    /**
    * Validate that `$to_validate[$key]` is an `array` type.
    *
    * Semantics are otherwise identical to `validate_is_string`.
    *
    * @see Arr::validate_is_string
    *
    * @param array<mixed>|\ArrayAccess<mixed>  $to_validate
    * @param string|int                        $key
    * @param Report|string                     $errors
    * @param ($key is int ? null|array<string>|(\Closure(int):string) : null
    *        )                                 $int_key_to_name
    * @param ?string                           $in_file
    * @param ?string                           $in_function
    * @param int                               $at_line
    * @param int<0,max>                        $at_trace_depth
    * @param ?kkdebug_backtrace_paranoid_a     $trace
    *
    * @phpstan-impure
    * @throws void
    */
    public static function validate_is_array(
        array|\ArrayAccess    $to_validate,
        string|int            $key,
        Report|string         &$errors,
        array|\Closure|null   $int_key_to_name = null,
        ?string               $in_file = null,
        ?string               $in_function = null,
        int                   $at_line = \PHP_INT_MIN,
        int                   $at_trace_depth = 0,
        ?array                $trace = null
    ) : bool
    {
        // Only check this on fields that exist.
        if ( !\array_key_exists($key, $to_validate) ) {
            return false;
        }

        // Now check for array-ish-ness
        $value = $to_validate[$key];
        if ( is_array($value) ) {
            return true;
        }

        // It wasn't an array. Error handling time.
        self::type_validation_error(
            $to_validate, $key, $errors, $int_key_to_name, 'array',
            $in_file, $in_function, $at_line, $at_trace_depth+1, $trace);
        return false;
    }

    /**
    * Validate that the given field|value is the given type.
    *
    * The value will have its type stringized using \get_debug_type,
    * which will be compared against the `$expected_type` parameter
    * to determine if the validation passes or not.
    *
    * This is useful if the type being validated is too specific
    * for the other validation routines to cover (ex: class names).
    *
    * For built-in types, it is recommended to use a more specific
    * validator instead, ex: `validate_is_int` for integer fields.
    *
    * Semantics are otherwise identical to `validate_is_string`.
    *
    * @see Arr::validate_is_string
    *
    * @param array<mixed>|\ArrayAccess<mixed>  $to_validate
    * @param string|int                        $key
    * @param Report|string                     $errors
    * @param string                            $expected_type
    * @param ($key is int ? null|array<string>|(\Closure(int):string) : null
    *        )                                 $int_key_to_name
    * @param ?string                           $in_file
    * @param ?string                           $in_function
    * @param int                               $at_line
    * @param int<0,max>                        $at_trace_depth
    * @param ?kkdebug_backtrace_paranoid_a     $trace
    *
    * @phpstan-impure
    * @throws void
    */
    public static function validate_is_type(
        array|\ArrayAccess    $to_validate,
        string|int            $key,
        Report|string         &$errors,
        string                $expected_type,
        array|\Closure|null   $int_key_to_name = null,
        ?string               $in_file = null,
        ?string               $in_function = null,
        int                   $at_line = \PHP_INT_MIN,
        int                   $at_trace_depth = 0,
        ?array                $trace = null
    ) : bool
    {
        // Only check this on fields that exist.
        if ( !\array_key_exists($key, $to_validate) ) {
            return false;
        }

        // Now check for type agreement
        $value = $to_validate[$key];
        $typename = \get_debug_type($value);
        if ( $typename === $expected_type ) {
            return true;
        }

        // Types did not agree. Error handling time.
        self::type_validation_error(
            $to_validate, $key, $errors, $int_key_to_name, $expected_type,
            $in_file, $in_function, $at_line, $at_trace_depth+1, $trace);
        return false;
    }

    private static function unittest_validate_is_type() : void
    {
        echo("  ".__FUNCTION__."()\n");

        $input = [
            'a' => 'x',
            'b' => 2,
            'c' => 3.5,
            'd' => true,
            'e' => null
        ];

        $report = Report::blank();
        assert($report->count() === 0);

        assert(self::validate_is_type($input, 'a', $report, 'string'));
        assert(self::validate_is_type($input, 'b', $report, 'int'));
        assert(self::validate_is_type($input, 'c', $report, 'float'));
        assert(self::validate_is_type($input, 'd', $report, 'bool'));
        assert(self::validate_is_type($input, 'e', $report, 'null'));
        assert($report->count() === 0);

        assert(!self::validate_is_type($input, 'foo', $report, 'string'));
        assert($report->count() === 0);

        assert(!self::validate_is_type($input, '', $report, 'string'));
        assert($report->count() === 0);

        assert(!self::validate_is_type($input, 'a', $report, 'int'));
        assert($report->count() === 1);

        assert(!self::validate_is_type($input, 'b', $report, 'string'));
        assert(!self::validate_is_type($input, 'c', $report, 'int'));
        assert(!self::validate_is_type($input, 'd', $report, 'int'));
        assert(!self::validate_is_type($input, 'e', $report, 'int'));
        assert($report->count() === 5);
    }

    public static function unittests() : void
    {
        $class_fqn = self::class;
        echo("Running `$class_fqn::unittests()`\n");

        self::unittest_flatten();
        self::unittest_validate_key_exists();
        self::unittest_validate_is_string();
        self::unittest_validate_is_type();

        echo("  ... passed.\n\n");
    }
}

final class Arr__IndexType
{
    use StaticClassTrait;
    use ClassOfConstantIntegersTrait;

    public const NAME  = 0;
    public const INDEX = 1;
}
?>
