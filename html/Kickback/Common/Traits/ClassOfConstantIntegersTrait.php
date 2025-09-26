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
    * @param      ?array<string, self::*>  $names_to_values
    * @param-out  array<string, self::*>   $names_to_values
    * @return     array<string, self::*>
    */
    private static function
        ClassOfConstantIntegersTrait__generate_names_to_values_array_from_reflection(?array &$names_to_values) : array
    {
        // "static::class" here does the magic
        $reflectionClass = new \ReflectionClass(static::class);

        $names_to_values = $reflectionClass->getConstants();

        // We have to ignore PHPStan's error:
        // ```
        // Method Kickback\Common\Traits\ClassOfConstantIntegers::
        //   ClassOfConstantIntegersTrait__generate_names_to_values_array_from_reflection()
        //     should return array<string, 1|2|3|4|5|7|8|9> but returns array<string, mixed>.
        // ```
        // Because PHPStan is not aware that `$reflectionClass->getConstants()`
        // is guaranteed (by definition) to return exactly that kind of array.
        /** @phpstan-ignore return.type */
        return $names_to_values;
    }

    /**
    * Returns an array mapping constant names to constant values.
    *
    * @return array<string, self::*>
    */
    #[KickbackGetter]
    public static function names_to_values() : array
    {
        static $names_to_values = null;
        if (isset($names_to_values)) {
            return $names_to_values;
        }
        self::ClassOfConstantIntegersTrait__generate_names_to_values_array_from_reflection($names_to_values);
        assert(0 < count($names_to_values));
        return $names_to_values;
    }

    /**
    * Returns the number of constants in the class.
    */
    #[KickbackGetter]
    public static function count() : int
    {
        return \count(self::names_to_values());
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
        if (isset($values)) {
            return $values;
        }
        $values = \array_values(self::names_to_values());
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
            $n2v = self::names_to_values();
            $idx = Int_::first_min_pair($n2v, $min_name, $min_value);
            assert(0 <= $idx && $idx < count($n2v));
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
            $n2v = self::names_to_values();
            $idx = Int_::first_max_pair($n2v, $max_name, $max_value);
            assert(0 <= $idx && $idx < count($n2v));
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
    * @param      array<string, self::*>   $names_to_values
    * @param      ?array<self::*, string>  $values_to_names
    * @param-out  array<self::*, string>   $values_to_names
    * @return     array<self::*, string>
    * @throws void
    */
    private static function ClassOfConstantIntegersTrait__generate_name_lookup(array $names_to_values,  ?array &$values_to_names) : array
    {
        $values_to_names = [];
        foreach($names_to_values as $name => $value)
        {
            if (\array_key_exists($value, $values_to_names)) {
                // Deduplicate by preferring the constants
                // that appear first in the class declaration.
                continue;
            }
            $values_to_names[$value] = $name;
        }
        return $values_to_names;
    }

    /**
    * @return array<self::*, string>
    * @throws void
    */
    #[KickbackGetter]
    public static function values_to_names() : array
    {
        static $values_to_names = null;
        if (isset($values_to_names)) {
            return $values_to_names;
        }
        // Generate it as needed:
        return self::ClassOfConstantIntegersTrait__generate_name_lookup(
            self::names_to_values(), $values_to_names);
    }

    /**
    * Non-throwing alternative to self::name_of
    *
    * If `$value` isn't in the set of constants, than the value placed
    * into `$name` will be `\strval($value)`.
    *
    * @param     self::*  $value
    * @param     ?string  $name
    * @param-out string   $name
    * @return bool  `false` if `$value` is not in the list of constants.
    * @throws void
    */
    public static function put_name_into(int $value, ?string &$name) : bool
    {
        $values_to_names = self::values_to_names();
        if (\array_key_exists($value, $values_to_names)) {
            $name = $values_to_names[$value];
            return true;
        }

        // Invalid value.
        $name = \strval($value);
        return false;
    }

    /**
    * Name of the constant with the given integer value
    *
    * Throws an \UnexpectedValueException
    * if `$value` isn't in the set of constants.
    *
    * @param  self::*  $value
    */
    public static function name_of(int $value) : string
    {
        if ( self::put_name_into($value, $res) ) {
            return $res;
        }

        // Invalid value.
        $classname = __CLASS__;
        throw new \UnexpectedValueException("ERROR: Value $res is not defined in $classname");
    }

    /**
    * Non-throwing version of self::name_of
    *
    * If `$value` isn't in the set of constants, than the returned value
    * will be `\strval($value)`.
    *
    * This can make it difficult to detect if there was an error while
    * retrieving the name. An alternative that provides non-throwing
    * behavior and structured failure info is `put_name_into`.
    *
    * @param  self::*  $value
    * @throws void
    */
    public static function nt_name_of(int $value) : string
    {
        self::put_name_into($value, $res);
        return $res;
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

    private static function unittest_names_to_values() : void
    {
        $names_to_values = self::names_to_values();

        /** @phpstan-ignore isset.variable, function.alreadyNarrowedType */
        assert(isset($names_to_values));

        // We'll keep the `array_key_exists` tests separate from the tests
        // that actually access the elements.
        // That way, if a unittest fails, it'll be a little bit more clear
        // what exactly went wrong.
        // (e.g. "constant didn't exist" vs "constant has incorrect contents");

        assert(array_key_exists('TEST_CONST_01', $names_to_values));
        assert(array_key_exists('TEST_CONST_02', $names_to_values));
        assert(array_key_exists('TEST_CONST_03', $names_to_values));
        assert(array_key_exists('TEST_CONST_04', $names_to_values));
        assert(array_key_exists('TEST_CONST_05', $names_to_values));
        assert(array_key_exists('TEST_CONST_07', $names_to_values));
        assert(array_key_exists('TEST_CONST_09', $names_to_values));
        assert(array_key_exists('TEST_CONST_08', $names_to_values));
        assert(array_key_exists('TEST_CONST_10', $names_to_values));
        assert(array_key_exists('TEST_CONST_N1', $names_to_values));
        assert(array_key_exists('TEST_CONST_00', $names_to_values));

        assert($names_to_values['TEST_CONST_01'] ===  1);
        assert($names_to_values['TEST_CONST_02'] ===  2);
        assert($names_to_values['TEST_CONST_03'] ===  3);
        assert($names_to_values['TEST_CONST_04'] ===  4);
        assert($names_to_values['TEST_CONST_05'] ===  5);
        assert($names_to_values['TEST_CONST_07'] ===  7);
        assert($names_to_values['TEST_CONST_09'] ===  9);
        assert($names_to_values['TEST_CONST_08'] ===  8);
        assert($names_to_values['TEST_CONST_10'] ===  3);
        assert($names_to_values['TEST_CONST_N1'] === -1);
        assert($names_to_values['TEST_CONST_00'] ===  0);

        assert(11 === \count($names_to_values));

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
        assert(\count(self::names_to_values()) === self::count());

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

    private static function unittest_name_of() : void
    {
        assert(self::name_of(self::TEST_CONST_01) === 'TEST_CONST_01');
        assert(self::name_of(self::TEST_CONST_02) === 'TEST_CONST_02');
        assert(self::name_of(self::TEST_CONST_03) === 'TEST_CONST_03');
        assert(self::name_of(self::TEST_CONST_04) === 'TEST_CONST_04');
        assert(self::name_of(self::TEST_CONST_05) === 'TEST_CONST_05');
        assert(self::name_of(self::TEST_CONST_07) === 'TEST_CONST_07');
        assert(self::name_of(self::TEST_CONST_09) === 'TEST_CONST_09');
        assert(self::name_of(self::TEST_CONST_08) === 'TEST_CONST_08');
        assert(self::name_of(self::TEST_CONST_10) === 'TEST_CONST_03'); // Tricky!
        assert(self::name_of(self::TEST_CONST_N1) === 'TEST_CONST_N1');
        assert(self::name_of(self::TEST_CONST_00) === 'TEST_CONST_00');

        // Out of bounds variables should throw.
        $threw = false;
        $min = self::min();
        /** @phpstan-ignore argument.type */
        try { assert(self::name_of($min - 1) !== 'TEST_CONST_N2'); }
        catch (\UnexpectedValueException $e) { $threw = true; }
        assert($threw);

        $threw = false;
        $max = self::max();
        /** @phpstan-ignore argument.type */
        try { assert(self::name_of($max + 1) !== 'TEST_CONST_10_or_11'); }
        catch (\UnexpectedValueException $e) { $threw = true; }
        assert($threw);

        // We'll make sure that it still throws if the invalid value
        // is in a "hole" in the range of constants.
        $threw = false;
        /** @phpstan-ignore argument.type */
        try { assert(self::name_of(6) !== '6'); }
        catch (\UnexpectedValueException $e) { $threw = true; }
        assert($threw);

        // Boundary value testing.
        $threw = false;
        /** @phpstan-ignore argument.type */
        try { assert(self::name_of(\PHP_INT_MIN) !== '-9223372036854775808'); }
        catch (\UnexpectedValueException $e) { $threw = true; }
        assert($threw);

        $threw = false;
        /** @phpstan-ignore argument.type */
        try { assert(self::name_of(\PHP_INT_MAX) !== '9223372036854775807'); }
        catch (\UnexpectedValueException $e) { $threw = true; }
        assert($threw);

        echo("  ".__FUNCTION__."()\n");
    }

    public static function unittests() : void
    {
        $class_fqn = self::class;
        echo("Running `$class_fqn::unittests()`\n");

        self::unittest_names_to_values();
        self::unittest_count();
        self::unittest_min_pair();
        self::unittest_max_pair();
        self::unittest_name_of();

        echo("  ... passed.\n\n");
    }
}

?>
