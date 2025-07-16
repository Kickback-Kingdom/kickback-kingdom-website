<?php
declare(strict_types=1);

namespace Kickback\Common\Primitives;

use Kickback\Common\Exceptions\IKickbackThrowable;
use Kickback\Common\Primitives\CategoriesOfCallables;

/**
* Miscellaneous functions that do useful things with PHP metadata (ex: Reflection API).
*
* @phpstan-import-type  kkdebug_frame_paranoid_a      from IKickbackThrowable
* @phpstan-import-type  kkdebug_backtrace_paranoid_a  from IKickbackThrowable
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

        echo("  ".__FUNCTION__."()\n");
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
                assert(is_string($callable));
                return $callable;
            case CategoriesOfCallables::FUNCTION_AS_STRING:
                assert(is_string($callable));
                return $callable;
            case CategoriesOfCallables::METHOD_AS_ARRAY:
                assert(is_array($callable));
                return get_class($callable[0])  . '->' . $callable[1];
            case CategoriesOfCallables::STATIC_AS_ARRAY:
                assert(is_array($callable));
                return $callable[0]  . '::' . $callable[1];
            case CategoriesOfCallables::CLOSURE_INSTANCE:
                assert($callable instanceof \Closure);
                // TODO: At least cache the ReflectionFunction, if not others too.
                $reflectionClosure = new \ReflectionFunction($callable);
                $func_name = $reflectionClosure->getName();
                $class_refl = $reflectionClosure->getClosureScopeClass();
                if ( !isset($class_refl) ) {
                    return $func_name;
                }
                $class_fqn = $class_refl->getName();
                if ( $reflectionClosure->isStatic() ) {
                    return $class_fqn . '::' . $func_name;
                } else {
                    return $class_fqn . '->' . $func_name;
                }
            case CategoriesOfCallables::INVOKABLE_OBJECT:
                assert(is_object($callable));
                return get_class($callable);
            default:
                //assert($category === CategoriesOfCallables::UNKNOWN);
                throw new \UnexpectedValueException(
                    'Could not generate unique name for unknown callback.');
        }
    }

    public static function unittests() : void
    {
        $class_fqn = self::class;
        echo("Running `$class_fqn::unittests()`\n");

        self::unittest_outermost_caller_name_within_class();

        echo("  ... passed.\n\n");
    }
}

?>
