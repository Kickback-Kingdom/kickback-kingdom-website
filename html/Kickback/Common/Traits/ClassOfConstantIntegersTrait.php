<?php
declare(strict_types=1);

namespace Kickback\Common\Traits;

use Kickback\Common\Attributes\KickbackGetter;
use Kickback\Common\Primitives\Int_;
use Kickback\Common\Traits\StaticClassTrait;

// Future directions:
// * ClassOfConstantsTrait that uses PHPStan template to implement subset of methods valid for `mixed` types.
// * ClassOfConstantStringsTrait uses ClassOfConstantsTrait<string>
// * Reimplement ClassOfConstantIntegersTrait as using ClassOfConstantsTrait<int> but adding things like `max()` and `min()` methods.
//
// /**
// * @template TClassName of class-string
// */
trait ClassOfConstantIntegersTrait
{
    // This function comes from the advice of "Panni" in the `getConstants` comments section:
    // https://www.php.net/manual/en/reflectionclass.getconstants.php#123353
    //
    // The advantage of this method is that it should handle inheritance correctly.
    /**
    * Returns an array mapping constant names to constant values.
    *
    * @return array<string, self::*>
    */
    private static function generate_all_array_() : array
    {
        // "static::class" here does the magic
        $reflectionClass = new \ReflectionClass(static::class);

        // We have to ignore PHPStan's error:
        // ```
        // Method Kickback\Common\Traits\ClassOfConstantIntegers::generate_all_array_()
        //     should return array<string, 1|2|3|4|5|7|8|9> but returns array<string, mixed>.
        // ```
        // Because PHPStan is not aware that `$reflectionClass->getConstants()`
        // is guaranteed (by definition) to return exactly that kind of array.
        /** @phpstan-ignore return.type */
        return $reflectionClass->getConstants();
    }

    /**
    * Returns an array mapping constant names to constant values.
    *
    * @return array<string, self::*>
    */
    #[KickbackGetter]
    public static function all() : array
    {
        static $all = null;
        if (!isset($all)) {
            $all = self::generate_all_array_();
            assert(0 < count($all));
        }
        return $all;
    }

    /**
    * Returns the number of constants in the class.
    */
    #[KickbackGetter]
    public static function count() : int
    {
        return \count(self::all());
    }

    /**
    * Returns an array of all constant values, without name information.
    *
    * The values will be sorted according to how they appear lexically
    * within the class definition.
    *
    * @return array<self::*>
    */
    #[KickbackGetter]
    public static function values() : array
    {
        static $values = null;
        if (!isset($values)) {
            $values = array_values(self::all());
        }
        return $values;
    }

    /**
    * @param     ?string $name
    * @param-out string  $name
    * @return    self::*
    */
    public static function min_pair(?string &$name) : int
    {
        static $min_name  = null;
        static $min_value = \PHP_INT_MAX;
        if ($min_value === \PHP_INT_MAX) {
            $all = self::all();
            $idx = Int_::first_min_pair($all, $min_name, $min_value);
            assert(0 <= $idx && $idx < count($all));
        }
        $name = $min_name;
        return $min_value;
    }

    /**
    * Returns the value of the lowest constant.
    * @return    self::*
    */
    #[KickbackGetter]
    public static function min() : int
    {
        $min_value = self::min_pair($min_name);
        return $min_value;
    }

    #[KickbackGetter]
    public static function name_of_minimum_constant() : string
    {
        $min_value = self::min_pair($min_name);
        return $min_name;
    }

    /**
    * @param     ?string $name
    * @param-out string  $name
    * @return    self::*
    */
    public static function max_pair(?string &$name) : int
    {
        static $max_name  = null;
        static $max_value = \PHP_INT_MIN;
        if ($max_value === \PHP_INT_MIN) {
            $all = self::all();
            $idx = Int_::first_max_pair($all, $max_name, $max_value);
            assert(0 <= $idx && $idx < count($all));
        }
        $name = $max_name;
        return $max_value;
    }


    /**
    * Returns the value of the highest constant.
    * @return    self::*
    */
    #[KickbackGetter]
    public static function max() : int
    {
        $max_value = self::max_pair($max_name);
        return $max_value;
    }

    #[KickbackGetter]
    public static function name_of_maximum_constant() : string
    {
        $max_value = self::max_pair($max_name);
        return $max_name;
    }

    // TODO: Overrides for the ClassOfConstantIndicesTrait and the ClassOfIntegerFlagsTrait
    // ClassOfConstantIndicesTrait -> generate lookup array, use array lookup!
    // ClassOfIntegerFlagsTrait -> decompose into log2 values, then use array lookup!
    /**
    * Returns an array mapping constant values to constant names.
    *
    * @return array<self::*, string>
    */
    private static function generate_name_lookup_() : array
    {
        $lookup = [];
        $all = self::all();
        foreach($all as $name => $value)
        {
            if (array_key_exists($value, $lookup)) {
                // Deduplicate by preferring the constants
                // that appear first in the class declaration.
                continue;
            }
            $lookup[$value] = $name;
        }
        return $lookup;
    }

    /**
    * @param  self::*  $value
    */
    public static function stringize(int $value) : string
    {
        static $lookup = null;
        if (!isset($lookup)) {
            $lookup = self::generate_name_lookup_();
        }

        if (array_key_exists($value, $lookup)) {
            return $lookup[$value];
        } else {
            $vstr = strval($value);
            $classname = __CLASS__;
            throw new \ValueError("ERROR: Value $vstr is not defined in $classname");
        }
    }
}

/** @internal */
final class ClassOfConstantIntegers
{
    use StaticClassTrait;
    use ClassOfConstantIntegersTrait;

    private const TEST_CONST_01 =  1;
    private const TEST_CONST_02 =  2;
    private const TEST_CONST_03 =  3;
    private const TEST_CONST_04 =  4;
    private const TEST_CONST_05 =  5;
    private const TEST_CONST_07 =  7; // Mind the gap! ^^
    private const TEST_CONST_09 =  9;
    private const TEST_CONST_08 =  8; // Sometimes things are backwards.
    private const TEST_CONST_10 =  3; // Sometimes there are duplicates. ¯\_(ツ)_/¯
    private const TEST_CONST_N1 = -1; // Make sure we can handle negatives...
    private const TEST_CONST_00 =  0; // ...and zero.

    private static function unittest_all_array() : void
    {
        $all = self::all();

        /** @phpstan-ignore isset.variable, function.alreadyNarrowedType */
        assert(isset($all));

        // We'll keep the `array_key_exists` tests separate from the tests
        // that actually access the elements.
        // That way, if a unittest fails, it'll be a little bit more clear
        // what exactly went wrong.
        // (e.g. "constant didn't exist" vs "constant has incorrect contents");

        assert(array_key_exists('TEST_CONST_01', $all));
        assert(array_key_exists('TEST_CONST_02', $all));
        assert(array_key_exists('TEST_CONST_03', $all));
        assert(array_key_exists('TEST_CONST_04', $all));
        assert(array_key_exists('TEST_CONST_05', $all));
        assert(array_key_exists('TEST_CONST_07', $all));
        assert(array_key_exists('TEST_CONST_09', $all));
        assert(array_key_exists('TEST_CONST_08', $all));
        assert(array_key_exists('TEST_CONST_10', $all));
        assert(array_key_exists('TEST_CONST_N1', $all));
        assert(array_key_exists('TEST_CONST_00', $all));

        assert($all['TEST_CONST_01'] ===  1);
        assert($all['TEST_CONST_02'] ===  2);
        assert($all['TEST_CONST_03'] ===  3);
        assert($all['TEST_CONST_04'] ===  4);
        assert($all['TEST_CONST_05'] ===  5);
        assert($all['TEST_CONST_07'] ===  7);
        assert($all['TEST_CONST_09'] ===  9);
        assert($all['TEST_CONST_08'] ===  8);
        assert($all['TEST_CONST_10'] ===  3);
        assert($all['TEST_CONST_N1'] === -1);
        assert($all['TEST_CONST_00'] ===  0);

        assert(11 === \count($all));

        echo("  ".__FUNCTION__."()\n");
    }

    private static function unittest_count() : void
    {
        // At the time of writing, this is a bit tautological.
        // But it would matter if the implementation ever changes.
        // (E.g. current version allocates memory, but
        // if there were a way to avoid the memory allocation,
        // then that version could slightly improve overall
        // system performance.)
        assert(\count(self::all()) === self::count());

        echo("  ".__FUNCTION__."()\n");
    }

    private static function unittest_min_pair() : void
    {
        assert(self::min_pair($name_of_min) === -1);
        assert($name_of_min === 'TEST_CONST_N1');
        assert(self::min() === -1);
        assert(self::name_of_minimum_constant() === 'TEST_CONST_N1');

        echo("  ".__FUNCTION__."()\n");
    }

    private static function unittest_max_pair() : void
    {
        assert(self::max_pair($name_of_max) === 9);
        assert($name_of_max === 'TEST_CONST_09');
        assert(self::max() === 9);
        assert(self::name_of_maximum_constant() === 'TEST_CONST_09');

        echo("  ".__FUNCTION__."()\n");
    }

    private static function unittest_stringize() : void
    {
        assert(self::stringize(self::TEST_CONST_01) === 'TEST_CONST_01');
        assert(self::stringize(self::TEST_CONST_02) === 'TEST_CONST_02');
        assert(self::stringize(self::TEST_CONST_03) === 'TEST_CONST_03');
        assert(self::stringize(self::TEST_CONST_04) === 'TEST_CONST_04');
        assert(self::stringize(self::TEST_CONST_05) === 'TEST_CONST_05');
        assert(self::stringize(self::TEST_CONST_07) === 'TEST_CONST_07');
        assert(self::stringize(self::TEST_CONST_09) === 'TEST_CONST_09');
        assert(self::stringize(self::TEST_CONST_08) === 'TEST_CONST_08');
        assert(self::stringize(self::TEST_CONST_10) === 'TEST_CONST_03'); // Tricky!
        assert(self::stringize(self::TEST_CONST_N1) === 'TEST_CONST_N1');
        assert(self::stringize(self::TEST_CONST_00) === 'TEST_CONST_00');

        // Out of bounds variables should throw.
        $threw = false;
        $min = self::min();
        /** @phpstan-ignore argument.type */
        try { assert(self::stringize($min - 1) !== 'TEST_CONST_N2'); }
        catch (\ValueError $e) { $threw = true; }
        assert($threw);

        $threw = false;
        $max = self::max();
        /** @phpstan-ignore argument.type */
        try { assert(self::stringize($max + 1) !== 'TEST_CONST_10_or_11'); }
        catch (\ValueError $e) { $threw = true; }
        assert($threw);

        // We'll make sure that it still throws if the invalid value
        // is in a "hole" in the range of constants.
        $threw = false;
        /** @phpstan-ignore argument.type */
        try { assert(self::stringize(6) !== '6'); }
        catch (\ValueError $e) { $threw = true; }
        assert($threw);

        // Boundary value testing.
        $threw = false;
        /** @phpstan-ignore argument.type */
        try { assert(self::stringize(\PHP_INT_MIN) !== '-9223372036854775808'); }
        catch (\ValueError $e) { $threw = true; }
        assert($threw);

        $threw = false;
        /** @phpstan-ignore argument.type */
        try { assert(self::stringize(\PHP_INT_MAX) !== '9223372036854775807'); }
        catch (\ValueError $e) { $threw = true; }
        assert($threw);

        echo("  ".__FUNCTION__."()\n");
    }

    public static function unittests() : void
    {
        $class_fqn = self::class;
        echo("Running `$class_fqn::unittests()`\n");

        self::unittest_all_array();
        self::unittest_count();
        self::unittest_min_pair();
        self::unittest_max_pair();
        self::unittest_stringize();

        echo("  ... passed.\n\n");
    }
}

?>
