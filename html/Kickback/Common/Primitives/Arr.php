<?php
declare(strict_types=1);

namespace Kickback\Common\Primitives;

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

        echo("  ".__FUNCTION__."()\n");
    }

    public static function unittests() : void
    {
        $class_fqn = self::class;
        echo("Running `$class_fqn::unittests()`\n");

        self::unittest_flatten();

        echo("  ... passed.\n\n");
    }
}
?>
