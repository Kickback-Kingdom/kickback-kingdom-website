<?php
declare(strict_types=1);

namespace Kickback\Common\Primitives;

use Kickback\Common\Exceptions\IKickbackThrowable;
use Kickback\Common\Primitives\CategoriesOfCallables;
use Kickback\Common\Unittesting\AssertException;

/**
* Miscellaneous functions that do useful things with PHP metadata (ex: Reflection API).
*
* @phpstan-import-type  kkdebug_frame_paranoid_a      from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
* @phpstan-import-type  kkdebug_backtrace_paranoid_a  from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
*/
final class Meta
{
    use \Kickback\Common\Traits\StaticClassTrait;

    /**
    * @param kkdebug_backtrace_paranoid_a $trace
    */
    private static function handle_outermost_caller_name_corner_case(array $trace, int $frame_number) : string
    {
        $frame = $trace[$frame_number];
        if (array_key_exists('function',$frame)
        &&  0 !== strcmp($frame['function'], 'include')
        &&  0 !== strcmp($frame['function'], 'require')) {
            return $frame['function'];
        } else
        if (array_key_exists('file',$frame)) {
            $file = $frame['file'];
            if (array_key_exists('line',$frame)) {
                $line = $frame['line'];
                return "{top-level script: $file:$line}";
            } else {
                return "{top-level script: $file}";
            }
        } else {
            return '{unknown caller}';
        }
    }

    // This function is factored out so that we can specify, in phpstan,
    // the array type returned by the `\debug_backtrace` function.
    /**
    * @param kkdebug_backtrace_paranoid_a           $trace
    * @param class-string|array<class-string>|null  $home_class_fqns
    */
    private static function outermost_caller_name_within_class_impl(array $trace, string|array|null $home_class_fqns, ?string &$caller_class_name = null) : string
    {
        $caller_class_name = null;
        $frame_count = count($trace);
        if ($frame_count <= 1) {
            return '{top-level script}';
        }

        if (!isset($home_class_fqns)) {
            if (array_key_exists('class',$trace[1])) {
                $home_class_fqns = [$trace[1]['class']];
            } else {
                return self::handle_outermost_caller_name_corner_case($trace, 1);
            }
        }

        if (!is_array($home_class_fqns)) {
            $home_class_fqns = [$home_class_fqns];
        }

        // Initial scan for home class.
        $start_at = \PHP_INT_MAX;
        for($frame_num = 1; $frame_num < $frame_count; $frame_num++)
        {
            $frame = $trace[$frame_num];
            if (!array_key_exists('class', $frame)) {
                continue; // Not even in a class.
            }
            if (in_array($frame['class'], $home_class_fqns, true)) {
                $start_at = $frame_num;
                break;
            }
        }

        // Failed to find class. Just return the caller's function name.
        // TODO: Is it appropriate to throw an exception here? Hmmmmm...
        if ( $start_at === \PHP_INT_MAX ) {
            return self::handle_outermost_caller_name_corner_case($trace, 1);
        }

        // Scan for "end" of home class.
        $end_at = \PHP_INT_MAX;
        for($frame_num = $start_at+1; $frame_num < $frame_count; $frame_num++)
        {
            $frame = $trace[$frame_num];

            if (array_key_exists('function', $frame)) {
                $func_name = $frame['function'];
            } else {
                $func_name = '';
            }

            if (!array_key_exists('class', $frame)
            ||  !in_array($frame['class'], $home_class_fqns, true)
            ||  str_starts_with($func_name, 'unittest_'))
            {
                $end_at = $frame_num-1;
                break;
            }
        }
        if ($end_at === \PHP_INT_MAX) {
            $end_at = $frame_count-1;
        }

        // Back away from the edge if we're hovering over
        // a vague '{closure}' frame.
        while ($start_at < $end_at
            && (!array_key_exists('function',$trace[$end_at])
                || 0 === strcmp($trace[$end_at]['function'], '{closure}'))
        ) {
            $end_at--;
        }

        $end_frame = $trace[$end_at];
        if ( array_key_exists('function',$end_frame) ) {
            if ( array_key_exists('class',$end_frame) ) {
                $caller_class_name = $end_frame['class'];
            }
            return $end_frame['function'];
        } else {
            return self::handle_outermost_caller_name_corner_case($trace, $end_at);
        }
    }

    // TODO: innermost_caller_name_outside_class
    /**
    * Uses `debug_backtrace` to determine what function/method was called
    * when the class was first entered.
    *
    * The process, if `$home_class_fqns` is not passed:
    * * Call `debug_backtrace`
    * * Start at index 1 in the backtrace. (Index 0 is just `outermost_caller_name_within_class`, which we ignore.)
    * * Note the class name at index 1.
    * * Find the next index with a different class name, call it `$outside_index`
    * * Return the (unqualified) function name at the `$outside_index - 1`.
    *
    * The process, if `$home_class_fqns` is passed:
    * * Call `debug_backtrace`
    * * Scan (starting at index 1) until the index where the function's class's name equals `(string)$home_class_fqns` or is in `(array)$home_class_fqns`.
    * * Find the next index with a different class name (that is NOT in `(array)`$home_class_fqns`), call it `$outside_index`
    * * Return the (unqualified) function name at the `$outside_index - 1`.
    *
    * Notable exception to the above: anything where the function name begins
    * with the prefix `unittest_` is also considered to be "outside",
    * even if it is within the "home" class.
    *
    * The use-case for this method is when an implementation function wants
    * to print the name of the function that the caller would recognize.
    * This will work as long as the whole implementation stack exists within
    * a single class. If it doesn't, then a different approach is required.
    *
    * If this is called from a class or location that is outside of the
    * "interface" or "API" class because it was called by that class, then
    * passing that interfacing class's name into the `$home_class_fqns` parameter
    * will allow the scanner to "skip" to that class.
    *
    * Caveats:
    *
    * This should not be called from within callbacks that are declared within
    * the "home"/target class, or from within functions indirectly (or directly)
    * called from such callbacks. The exception to this is if the callback
    * is created and used within the same class, and all intervening
    * function calls are also within that same class, in which case
    * the scanner will still successfully find the "edge" of the class's
    * callstack.
    *
    * @param class-string|array<class-string>|null  $home_class_fqns
    * @param ?class-string                          $caller_class_name
    */
    public static function outermost_caller_name_within_class(string|array|null $home_class_fqns = null, ?string &$caller_class_name = null) : string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        return self::outermost_caller_name_within_class_impl($trace, $home_class_fqns, $caller_class_name);
    }

    private static function unittest_outermost_caller_name_within_class() : void
    {
        echo("  ".__FUNCTION__."()\n");

        $foo = function() : string {
            return self::outermost_caller_name_within_class();
        };
        // We expect this:
        //assert(0 === strcmp($foo(), 'foo'));

        // But we get this:
        $namespace = __NAMESPACE__;
        assert(0 === strcmp($foo(), "$namespace\{closure}"));
        // There doesn't seem to be any way to get the desired behavior though,
        // because there is no frame for `foo` in the backtrace; it is
        // replaced with `'{closure}'`.
        // So for now, this is just defined to be the correct behavior. (Darn.)

        assert(0 === strcmp(self::test_function_for_outermost_caller_unittest_01(),
            'test_function_for_outermost_caller_unittest_01'));

        assert(0 === strcmp(self::test_function_for_outermost_caller_unittest_02(),
            'test_function_for_outermost_caller_unittest_02'));
    }

    private static function test_function_for_outermost_caller_unittest_01() : string
    {
        return self::outermost_caller_name_within_class();
    }

    private static function test_function_for_outermost_caller_unittest_02() : string
    {
        return self::test_function_for_outermost_caller_unittest_01();
    }

    public static function callable_to_unique_name(callable $callable) : string
    {
        // Thanks goes to StackOverflow poster `Bigdot` for enumerating the
        // possible `callable` types and how one might stringize them:
        // https://stackoverflow.com/a/68113840
        //
        // Thanks goes to StackOverflow poster `Shizzen83` for describing
        // how to acquire more information from Closure objects:
        // https://stackoverflow.com/a/62722371
        //
        $category = CategoriesOfCallables::from_callable($callable);

        switch ($category)
        {
            case CategoriesOfCallables::STATIC_AS_STRING:
                assert(\is_string($callable));
                return $callable;
            case CategoriesOfCallables::FUNCTION_AS_STRING:
                assert(\is_string($callable));
                return $callable;
            case CategoriesOfCallables::METHOD_AS_ARRAY:
                assert(\is_array($callable));
                return get_class($callable[0])  . '->' . $callable[1];
            case CategoriesOfCallables::STATIC_AS_ARRAY:
                assert(\is_array($callable));
                return $callable[0]  . '::' . $callable[1];

            case CategoriesOfCallables::CLOSURE_INSTANCE:
                assert($callable instanceof \Closure);
                // TODO: At least cache the ReflectionFunction, if not others too. (But how?)
                $reflectionClosure = new \ReflectionFunction($callable);

                $unique_str = $reflectionClosure->getFileName() ?: '';

                $sline = $reflectionClosure->getStartLine();
                $eline = $reflectionClosure->getEndLine();

                if ( 0 < \strlen($unique_str)
                &&  ($sline !== false || $eline !== false) ) {
                    $unique_str .= '@';
                }

                if ( $sline !== false && $eline !== false ) {
                    $unique_str .= \strval($sline) . '-' . \strval($eline);
                } else
                if ( $sline !== false || $eline !== false ) {
                    $unique_str .= ($sline !== false) ? \strval($sline) : '';
                    $unique_str .= ($eline !== false) ? \strval($eline) : '';
                }

                if ( 0 < \strlen($unique_str) ) {
                    $unique_str .= ':';
                }

                $scope_class  = $reflectionClosure->getClosureScopeClass();
                $called_class = $reflectionClosure->getClosureCalledClass();
                $closure_this = $reflectionClosure->getClosureThis();

                $scope_class_str  = isset($scope_class)  ? $scope_class->getName() : null;
                $called_class_str = isset($called_class) ? $called_class->getName() : null;
                $closure_this_str = isset($closure_this) ? \get_class($closure_this) : null;

                // Remove duplicate names; they would make things display like
                //   `/project/My/Class@15-27: {\My\Class, \My\Class, \My\Class}::foo`
                // which is nonsense. By removing dupes, we'd instead get this:
                //   `/project/My/Class@15-27: \My\Class::foo`
                if ( $scope_class_str  === $called_class_str ) { $called_class_str = null; }
                if ( $scope_class_str  === $closure_this_str ) { $closure_this_str = null; }
                if ( $called_class_str === $closure_this_str ) { $closure_this_str = null; }

                $class_count = 0;
                if(isset($scope_class))  { $class_count++; }
                if(isset($called_class)) { $class_count++; }
                if(isset($closure_this)) { $class_count++; }

                if ( 1 < $class_count ) {
                    $unique_str .= '{';
                }

                $i = 0;
                $append_class_name =
                function(string $type,  string|false|null $name)
                    use(&$i, &$unique_str) : void
                {
                    if ( !isset($name) || $name === false ) {
                        return;
                    }
                    if ( $i !== 0 ) {
                        $unique_str .= ', ';
                    }
                    $unique_str .= "$type=$name";
                    $i++;
                };

                $append_class_name('scope_class',  $scope_class_str);
                $append_class_name('called_class', $called_class_str);
                $append_class_name('closure_this', $closure_this_str);

                if ( 1 < $class_count ) {
                    $unique_str .= '}';
                }

                $func_name = $reflectionClosure->getName();
                if ( 0 === $class_count ) {
                    $unique_str .= $func_name;
                    return $unique_str;
                }

                if ( $reflectionClosure->isStatic() ) {
                    return $unique_str . '::' . $func_name;
                } else {
                    return $unique_str . '->' . $func_name;
                }

            case CategoriesOfCallables::INVOKABLE_OBJECT:
                assert(\is_object($callable));
                return \get_class($callable);
            default:
                //assert($category === CategoriesOfCallables::UNKNOWN);
                throw new \UnexpectedValueException(
                    'Could not generate unique name for unknown callback.');
        }
    }

    /**
    * Returns the shortname for a class/interface/enum.
    *
    * For example:
    * ```
    * assert(Meta::shortname('Foo\\Bar\\MyClass') === 'MyClass');
    * ```
    *
    * @param object|class-string $obj
    */
    public static function shortname(object|string $obj) : string
    {
        if ( \is_string($obj) ) {
            $class_fqn = $obj;
        } else {
            $class_fqn = \get_class($obj);
        }

        $shortname_with_leading_slash = \strrchr($class_fqn, '\\');
        if ( $shortname_with_leading_slash !== false ) {
            return \substr($shortname_with_leading_slash, 1);
        }

        // strrchar returns `false` if `$needle` is not found.
        // e.g. there are no path separators
        // e.g.e.g. the whole string is the shortname
        return $class_fqn;
    }

    public static function subtest_shortname(IKickbackThrowable $iface) : void
    {
        assert(self::shortname($iface)  === 'AssertException');
    }

    private static function unittest_shortname() : void
    {
        echo("  ".__FUNCTION__."()\n");

        assert(self::shortname('Foo\\Bar\\MyClass') === 'MyClass'); // @phpstan-ignore  argument.type
        assert(self::shortname('Kickback\\Common\\Primitives\\Meta') === 'Meta');

        $exc = new AssertException();
        assert(self::shortname($exc) === 'AssertException');

        self::subtest_shortname($exc);
    }

    /**
    * Generates a string mask of locations where it is valid to check for (numerically-identified) subclasses.
    *
    * This is mostly used in the autoloader, and probably
    * won't be relevant for most code.
    *
    * This function DOES allow the autoloader logic to be subjected
    * to unittesting, which is very useful in its own way.
    */
    public static function generate_numbered_subclass_mask(string $class_unqual_name): string
    {
        return \Kickback\InitializationScripts\autoloader_generate_numbered_subclass_mask($class_unqual_name);
    }

    private static function unittest_generate_numbered_subclass_mask() : void
    {
        echo("  ".__FUNCTION__."()\n");

        assert(self::generate_numbered_subclass_mask('')              === '');
        assert(self::generate_numbered_subclass_mask('a')             === '1');
        assert(self::generate_numbered_subclass_mask('A')             === '1');
        assert(self::generate_numbered_subclass_mask('_')             === '0');

        assert(self::generate_numbered_subclass_mask('FooBar')        === '000001');
        assert(self::generate_numbered_subclass_mask('FooBar_FooBar') === '0000010000001');
        assert(self::generate_numbered_subclass_mask('FooBar_fooBar') === '0000010111001');
        assert(self::generate_numbered_subclass_mask('fooBar')        === '000001');
        assert(self::generate_numbered_subclass_mask('fooBar_FooBar') === '0000010000001');
        assert(self::generate_numbered_subclass_mask('fooBar_fooBar') === '0000010111001');
        assert(self::generate_numbered_subclass_mask('FooBar_x')      === '00000101');
        assert(self::generate_numbered_subclass_mask('FooBar_0')      === '00000101');
        assert(self::generate_numbered_subclass_mask('FooBar_Baz')    === '0000010001');
        assert(self::generate_numbered_subclass_mask('FooBar__x')     === '000001001');
        assert(self::generate_numbered_subclass_mask('FooBar__0')     === '000001001');
        assert(self::generate_numbered_subclass_mask('FooBar_x0')     === '000001011');
        assert(self::generate_numbered_subclass_mask('FooBar__x0')    === '0000010011');

        assert(self::generate_numbered_subclass_mask('MyClass_0x0y')  === '000000101111');
        assert(self::generate_numbered_subclass_mask('MyClass_0x0')   === '00000010111');
        assert(self::generate_numbered_subclass_mask('MyClass_0x')    === '0000001011');
        assert(self::generate_numbered_subclass_mask('MyClass_0')     === '000000101');
        assert(self::generate_numbered_subclass_mask('MyClass_')      === '00000010');
        assert(self::generate_numbered_subclass_mask('MyClass')       === '0000001');

        assert(self::generate_numbered_subclass_mask('MyClass_a2bC5')        === '0000001011101');
        assert(self::generate_numbered_subclass_mask('MyClass_a2bC5x')       === '00000010111001');
        assert(self::generate_numbered_subclass_mask('MyClass_5t9SomeThing') === '00000010111000000001');
        assert(self::generate_numbered_subclass_mask('_p67q9')               === '011111');

        // These are invalid for numbered subclasses, but they still have
        // a mask. (The autoloader just wouldn't call the function on
        // these identifiers.)
        assert(self::generate_numbered_subclass_mask('FooBar_X')      === '00000101');
        assert(self::generate_numbered_subclass_mask('FooBar__X')     === '000001001');
        assert(self::generate_numbered_subclass_mask('FooBar_')       === '0000010');
        assert(self::generate_numbered_subclass_mask('FooBar__')      === '00000100');
        assert(self::generate_numbered_subclass_mask('FooBar__Baz')   === '00000100001');
        assert(self::generate_numbered_subclass_mask('FooBar_Baz_')   === '00000100010');
        assert(self::generate_numbered_subclass_mask('FooBar_Baz__')  === '000001000100');
        assert(self::generate_numbered_subclass_mask('FooBar__Baz_')  === '000001000010');
        assert(self::generate_numbered_subclass_mask('FooBar__Baz__') === '0000010000100');
    }

    /**
    * Does the autoloader's "eponymous interface" transform to the given class shortname.
    *
    * This is mostly used in the autoloader, and probably
    * won't be relevant for most code.
    *
    * This function DOES allow the autoloader logic to be subjected
    * to unittesting, which is very useful in its own way.
    *
    * @return int  `true` if `$class_unqual_name` was modified (because interface notation was found), `false` otherwise.
    */
    public static function eponymous_interfaces_transform(string &$class_unqual_name, int $start_at = 0) : int
    {
        return \Kickback\InitializationScripts\autoloader_eponymous_interfaces_transform($class_unqual_name, $start_at);
    }

    private static function unittest_eponymous_interfaces_transform() : void
    {
        echo("  ".__FUNCTION__."()\n");

        $transform = function(string $name_example, string &$output_name, int $start_at = 0) : bool {
            $tmp = $name_example;
            $idx = self::eponymous_interfaces_transform($tmp, $start_at);
            if ( $idx < \strlen($name_example) ) {
                $output_name = $tmp;
                return true;
            }
            return false;
        };

        $output = '';
        assert(!$transform('',$output));
        assert(!$transform('A',$output));
        assert(!$transform('MyClass',    $output));
        assert(!$transform('My_Class',   $output));
        assert(!$transform('My__Class',  $output)); // The autoloader will look at `My.php`, but that's a _different_ feature.
        assert(!$transform('FooIBar',    $output));
        assert(!$transform('FooBarI',    $output)); // This might be allowed in the future?
        assert(!$transform('IfooBar',    $output)); // 'I' must be followed by a capital letter...
        assert(!$transform('IntWhacker', $output)); // ... and this is why. I is a normal letter that can just be the start of a word/name.
        assert($transform('IA',          $output)); assert($output === 'A');
        assert($transform('IMyClass',    $output)); assert($output === 'MyClass');
        assert($transform('IFooBar',     $output)); assert($output === 'FooBar');
        assert($transform('IIntWhacker', $output)); assert($output === 'IntWhacker'); // Things starting with 'I' can have an interface if another 'I' is added.
        assert($transform('My_IClass',   $output)); assert($output === 'My_Class');
        assert($transform('My__IClass',  $output)); assert($output === 'My__Class');
        assert($transform('My_ISilly_IClass',   $output)); assert($output === 'My_Silly_IClass');
        assert($transform('My__ISilly_IClass',  $output)); assert($output === 'My__Silly_IClass');
        assert($transform('My_ISilly__IClass',  $output)); assert($output === 'My_Silly__IClass');
        assert($transform('My__ISilly__IClass', $output)); assert($output === 'My__Silly__IClass');
        assert($transform('My_Isilly_IClass',   $output)); assert($output === 'My_Isilly_Class'); // 'I' must be followed by a capital letter; so it scans right.
        assert(!$transform('My_Isilly_Iclass',  $output)); // None of the 'I''s are followed by a word.
        assert(!$transform('My_Int_Interpolation', $output)); // Clearer example of why the capital-letter-rule exists.

        // Regretably, this follows the rules.
        // (Perhaps these possibilities should be excluded using autoloader logic?)
        assert($transform('My_I_Class',$output)); assert($output === 'My__Class');

        // Underscore notation: slightly more advanced.
        assert(!$transform('_',$output));
        assert(!$transform('_I',$output));
        assert(!$transform('__I',$output));
        assert($transform('_IA', $output)); assert($output === '_A');
        assert($transform('__IA',$output)); assert($output === '__A');
        assert($transform('I_',  $output)); assert($output === '_');

        // Failure case during initial usage
        assert($transform('IO_IException', $output, 0)); assert($output === 'O_IException');
        assert($transform('IO_IException', $output, 1)); assert($output === 'IO_Exception');
    }


    public static function unittests() : void
    {
        $class_fqn = self::class;
        echo("Running `$class_fqn::unittests()`\n");

        self::unittest_outermost_caller_name_within_class();
        self::unittest_shortname();
        self::unittest_generate_numbered_subclass_mask();
        self::unittest_eponymous_interfaces_transform();

        echo("  ... passed.\n\n");
    }
}

?>
