<?php
declare(strict_types=1);

namespace Kickback\Common\Meta;
use Kickback\Common\Primitives\Meta;
use Kickback\Common\Traits\StaticClassTrait;
use Kickback\Common\Traits\ClassOfConstantIntegersTrait;

/**
* The values were taken from the IS_* definitions in the Zend engine source code:
* https://github.com/php/php-src/blob/master/Zend/zend_types.h
*/
final class ZType
{
    use StaticClassTrait;
    use ClassOfConstantIntegersTrait;

    /**
    * Type of undefined variables.
    *
    * Note that ZType::of(...) will never return this value, because
    * function parameters are always defined. So it is difficult to
    * actually measure them using... functions.
    */
    public const _UNDEF        =  0;

    public const _NULL         =  1;
    public const _FALSE        =  2;
    public const _TRUE         =  3;
    public const _INT          =  4; // IS_LONG in Zend
    public const _FLOAT        =  5; // IS_DOUBLE in Zend
    public const _STRING       =  6;
    public const _ARRAY        =  7;
    public const _OBJECT       =  8;
    public const _RESOURCE     =  9;
    public const _REFERENCE    = 10;
    public const _CONSTANT_AST = 11; // "constant expression", but I don't think we can identify this.

    /**
    * Type code for things that can be called like functions.
    *
    * In principle this is also valid for certain strings, arrays, and objects,
    * with the constraint that it is possible to call them.
    *
    * Unfortunately that is pretty ambiguous, so the `ZType::of` method is
    * going to return the more specific types for this, and not `ZType::_CALLABLE`.
    *
    * The one exception is instances of the \Closure class:
    * That case is pretty unambiguous, so we'll class that as `ZType::_CALLABLE`.
    */
    public const _CALLABLE     = 12;

    /**
    * Type code for things that can be the subject of foreach loops.
    *
    * In principle this is also valid for arrays,
    * generators (ambiguity: generators are also callables),
    * and objects that implement the \Traversable interface.
    *
    * Unfortunately that is pretty ambiguous, so the `ZType::of` method is
    * going to return the more specific types for this, and not `ZType::_ITERABLE`.
    *
    * The one exception is instances of the \Traversable interface:
    * That case is pretty unambiguous, so we'll class that as `ZType::_ITERABLE`.
    */
    public const _ITERABLE     = 13;

    public const _VOID         = 14;
    public const _STATIC       = 15;
    public const _MIXED        = 16;
    public const _NEVER        = 17;

    // There are more constants in zend_types.h, but they seem
    // to be internals and other things that aren't very relevant
    // to type management within PHP itself. It'd be more of
    // a burden to include them (e.g. more possibilities for
    // invalid values that can throw exceptions and stuff).

    // If adding more constants, make sure that E_NOT_A_ZTYPE
    // has the highest index (max index of other constants, then add 1).
    /**
    * Error-handling catch-all for non-ZType integers.
    *
    * This does not represent a PHP type, nor anything from zend_types.h;
    * it only exists as a placeholder that's helpful in some error-handling contexts.
    */
    public const E_NOT_A_ZTYPE = 18;

    /**
    * Acquire the ZType for a given variable.
    *
    * Note that ZTypes can be somewhat ambiguous, so precise comparisons
    * should be avoided:
    * ```
    * $my_callable = 'my_function';
    * if ( ZType::of($my_callable) === ZType::_CALLABLE ) {
    *     // This will never execute.
    * }
    * ```
    *
    * A better way to compare ZTypes is with
    * the ZType::matches method, like so:
    * ```
    * $my_callable = 'my_function';
    * if ( ZType::matches(ZType::_CALLABLE, $my_callable) ) {
    *     // This executes.
    * }
    * ```
    *
    * The ZType::of method is nonetheless useful for storing metadata
    * about types and values in an efficient way (integers instead of strings).
    *
    * @return  self::*
    */
    public static function of(mixed $val) : int
    {
        // I suspect there is no way to tell if we should return
        // Type::_REFERENCE. Because if we passed the argument by ref,
        // then it is a reference always. If we pass by value, then
        // it is a value always. (TODO: untested, but that's how the logic
        // stacks so far.)
        // Ergo, we may as well just pass by-value since this decision
        // will allow literals to be used with the function in addition
        // to variables and stuff.
        // Note: Use `isset` instead of `is_null` because `is_null` can print warnings!
        if (!isset($val)) {
            return self::_NULL;
            // // NOTE: self::_UNDEF doesn't work.
            // // See this assertion in the unittest:
            // //   `assert(ZType::of(@$foo) === self::_NULL);`
            // // We tried this originally:
            // //   `assert(ZType::of(@$foo) === self::_UNDEF);`
            // // but with the below code enabled.
            // // It didn't work: AssertionError.
            // if (!\array_key_exists('val',\get_defined_vars())) {
            //     return self::_UNDEF;
            // } else {
            //     return self::_NULL;
            // }
        }

        switch(true)
        {
            // Check for bools first, just to avoid any ambiguity.
            case $val === true:    return self::_TRUE;
            case $val === false:   return self::_FALSE;

            // The rest are vaguely in most-likely-to-be-encountered order.
            // Though we notably skip `object` so that the instanceof
            // has a chance to catch some notable classes first.
            case \is_int($val):                 return self::_INT;
            case \is_string($val):              return self::_STRING;
            case \is_array($val):               return self::_ARRAY;
            case \is_float($val):               return self::_FLOAT;
            case \is_resource($val):            return self::_RESOURCE;
            case $val instanceof \Closure:      return self::_CALLABLE;
            case $val instanceof \Traversable:  return self::_ITERABLE;
        }

        if ( \is_object($val) ) {
            return self::_OBJECT;
        }

        $valstr = \get_debug_type($val);
        throw new \UnexpectedValueException("Could not determine ZType constant for type $valstr.");
    }

    private static function unittest_of() : void
    {
        echo("  ".__FUNCTION__."()\n");

        $obj = new \stdClass();

        assert(ZType::of(@$foo) === self::_NULL); // @phpstan-ignore  variable.undefined
        assert(ZType::of(null)  === self::_NULL);
        assert(ZType::of(true)  === self::_TRUE);
        assert(ZType::of(false) === self::_FALSE);
        assert(ZType::of(1)     === self::_INT);
        assert(ZType::of(0.1)   === self::_FLOAT);
        assert(ZType::of('a')   === self::_STRING);
        assert(ZType::of(['a']) === self::_ARRAY);
        assert(ZType::of($obj)  === self::_OBJECT);

        assert(ZType::of(0)     === self::_INT);
        assert(ZType::of(0.0)   === self::_FLOAT);
        assert(ZType::of('')    === self::_STRING);
        assert(ZType::of([])    === self::_ARRAY);

        // Distinctness, but inability to overlap
        assert(ZType::of(__METHOD__) === self::_STRING);   // @phpstan-ignore  function.alreadyNarrowedType
        assert(ZType::of(__METHOD__) !== self::_CALLABLE); // @phpstan-ignore  function.alreadyNarrowedType
        assert(ZType::of(['a'])      !== self::_ITERABLE); // @phpstan-ignore  function.alreadyNarrowedType
    }


    /**
    * @param      ?array<self::*,callable(mixed,self::*):bool>  $jumptable
    * @param-out  array<self::*,callable(mixed,self::*):bool>   $jumptable
    * @return     array<self::*,callable(mixed,self::*):bool>
    */
    private static function populate_match_making_array(?array &$jumptable) : array
    {
        $ztype_class_shortname = Meta::shortname(static::class);

        // $caller_shortqn is the "short qualified name" of the caller's method.
        // So if the caller is Kickback\Common\Meta\ZType::matches(...)
        // Then
        //   $class_shortname === ZType::
        //   $func_shortname  === matches
        //   $caller_shortqn  === ZType::matches
        $caller_name_fn = function(
            ?string &$class_shortname,
            ?string &$func_shortname,
            ?string &$caller_shortqn
        ) : void
        {
            // Stack frames:
            // $trace[0] is the $caller_name_fn closure.
            // $trace[1] is the $caller_shortqn_func closure.
            // $trace[2] is the $jumptable element closure.
            // $trace[3] is the caller of of the $jumptable element.
            //
            // So, we only grabbed 4 frames from the stack because that's
            // all we'll need, AND we'll know which function is attempting to
            // determine a match using the array.
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,4);
            $frame = $trace[3];

            $class_shortname = '';
            $func_shortname = $frame['function'];
            $caller_shortqn = $func_shortname;
            if ( array_key_exists('class',$frame) ) {
                $class_shortname = Meta::shortname($frame['class']) . '::';
                $caller_shortqn = $class_shortname . $func_shortname;
            }
        };

        $caller_shortqn_func = function() use($caller_name_fn) : string
        {
            $caller_name_fn($class_shortname, $func_shortname, $caller_shortqn);
            return $caller_shortqn;
        };

        // // @param  self::*  $ztype
        // $ztype_str = function(int $ztype):string
        // {
        //     if ( $ztype < self::count() ) {
        //         return self::name_of($ztype);
        //     } else {
        //         return \strval($ztype);
        //     }
        // };
        $interpolate = function(string $arg) : string {
            return $arg;
        };

        $jt = &$jumptable;

        $jt[self::_UNDEF        ] = (fn($val,$ztype) => (!isset($val) && !array_key_exists('val',get_defined_vars())));
        $jt[self::_NULL         ] = (fn($val,$ztype) => (!isset($val) &&  array_key_exists('val',get_defined_vars())));
        $jt[self::_FALSE        ] = (fn($val,$ztype) => \is_bool($val));
        $jt[self::_TRUE         ] = $jt[self::_FALSE];
        $jt[self::_INT          ] = (fn($val,$ztype) => \is_int($val));
        $jt[self::_FLOAT        ] = (fn($val,$ztype) => \is_float($val));
        $jt[self::_STRING       ] = (fn($val,$ztype) => \is_string($val));
        $jt[self::_ARRAY        ] = (fn($val,$ztype) => \is_array($val));
        $jt[self::_OBJECT       ] = (fn($val,$ztype) => \is_object($val));
        $jt[self::_RESOURCE     ] = (fn($val,$ztype) => \is_resource($val));

        $jt[self::_REFERENCE    ] = (fn($val,$ztype) =>
            throw new \UnexpectedValueException(
                "The {$caller_shortqn_func()} function is unable to determine ".
                "if an argument is a reference or not; can't determine ".
                "if it's a $ztype_class_shortname::".self::nt_name_of($ztype).'.'));

        $jt[self::_CONSTANT_AST ] = (fn($val,$ztype) =>
            throw new \UnexpectedValueException(
                "The {$caller_shortqn_func()} function is unable to determine ".
                "if an argument is a constant expression or not; can't determine ".
                "if it's a $ztype_class_shortname::".self::nt_name_of($ztype).'.'));

        $jt[self::_CALLABLE     ] = (fn($val,$ztype) => \is_callable($val));
        $jt[self::_ITERABLE     ] = (fn($val,$ztype) => \is_iterable($val));

        $jt[self::_VOID         ] = (fn($val,$ztype) =>
            throw new \UnexpectedValueException(
                "The {$caller_shortqn_func()} function is unable to ".
                "accept `void` as an argument. The constant ".
                $ztype_class_shortname.'::'.self::nt_name_of($ztype).
                ' will never match anything.'));

        $jt[self::_STATIC       ] = (fn($val,$ztype) =>
            throw new \UnexpectedValueException(
                "The {$caller_shortqn_func()} function is unable to ".
                "accept `static` as an argument. The constant ".
                $ztype_class_shortname.'::'.self::nt_name_of($ztype).
                ' will never match anything.'));

        $jt[self::_MIXED        ] = (fn($val,$ztype) => true );
        $jt[self::_NEVER        ] = (fn($val,$ztype) => false);
        $jt[self::E_NOT_A_ZTYPE ] = (fn($val,$ztype) =>
            throw new \UnexpectedValueException(
                "Could not determine if $ztype_class_shortname::".
                self::nt_name_of($ztype).' matches type '.\get_debug_type($val).'.')
            );

        assert(\count($jt) === self::count());
        return $jt;
    }

    /**
    * @return     array<self::*,callable(mixed,self::*):bool>
    */
    private static function match_making_jump_table() : array
    {
        static $jumptable = null;
        if (isset($jumptable)) {
            return $jumptable;
        }
        return self::populate_match_making_array($jumptable);
    }

    /**
    * Determines if a value is within the range of types covered by a ZType.
    *
    * This treats all booleans as interchangeably, so
    * ```
    * assert(ZType::matches(ZType::_TRUE, false));
    * assert(ZType::matches(ZType::_FALSE, true));
    * ```
    *
    * Some ZTypes can match multiple values, and some values can match
    * multiple ZTypes. This is especially true with things like
    * callables and iterables.
    *
    * Notably, ZType::_MIXED will match everything,
    * and ZType::_NEVER will not match anything.
    *
    * @param  self::*  $ztype
    */
    public static function matches(int $ztype, mixed $val) : bool
    {
        $jumptable = self::match_making_jump_table();
        if ( array_key_exists($ztype, $jumptable) ) {
            return $jumptable[$ztype]($val,$ztype);
        }
        return $jumptable[self::E_NOT_A_ZTYPE]($val,$ztype);

        // TODO: Delete the below commented-out code.
        // $valstr = null;
        // $class_shortname = null;
        // $method_name = null;
        // $populate_debug_info =
        // function() use(&$valstr, &$class_shortname, &$method_name, $val) : void {
        //     $valstr = \get_debug_type($val);
        //     $class_shortname = Meta::shortname(self::class);
        //     $method_name     = __FUNCTION__;
        // };

        // switch($ztype)
        // {
        //     // Note: Use `isset` instead of `is_null` because `is_null` can print warnings!
        //     case self::_UNDEF: return !isset($val) && !array_key_exists('val',get_defined_vars());
        //     case self::_NULL:  return !isset($val) &&  array_key_exists('val',get_defined_vars());
        //     case self::_TRUE:
        //     case self::_FALSE:    return is_bool($val);
        //     case self::_INT:      return is_int($val);
        //     case self::_FLOAT:    return is_float($val);
        //     case self::_STRING:   return is_string($val);
        //     case self::_ARRAY:    return is_array($val);
        //     case self::_OBJECT:   return is_object($val);
        //     case self::_RESOURCE: return is_resource($val);
        //
        //     case self::_REFERENCE:
        //         $populate_debug_info();
        //         throw new \UnexpectedValueException(
        //             "The $class_shortname::$method_name function is unable to ".
        //             "determine if an argument is a reference or not; ".
        //             "can't determine if it's a $class_shortname::_REFERENCE.");
        //
        //     case self::_CONSTANT_AST:
        //         $populate_debug_info();
        //         throw new \UnexpectedValueException(
        //             "The $class_shortname::$method_name function is unable to ".
        //             "determine if an argument is a constant expression or not; ".
        //             "can't determine if it's a $class_shortname::_CONSTANT_AST.");
        //
        //     case self::_CALLABLE: return is_callable($val);
        //     case self::_ITERABLE: return is_iterable($val);
        //     case self::_VOID:
        //         $populate_debug_info();
        //         throw new \UnexpectedValueException(
        //             "The $class_shortname::$method_name function is unable to ".
        //             "accept `void` as an argument. This constant will never ".
        //             "match anything.");
        //
        //     case self::_MIXED:    return true;
        //     case self::_NEVER:    return false;
        // }

        // // Nothing matched.
        // $populate_debug_info();
        // $ztypestr = self::stringize($ztype);
        // throw new \UnexpectedValueException("Could not determine if $class_shortname::$ztypestr matches type $valstr.");
    }

    private static function unittest_matches() : void
    {
        echo("  ".__FUNCTION__."()\n");

        // Boolean tautologies
        assert(self::matches(self::_TRUE,  true));
        assert(self::matches(self::_FALSE, false));

        // Boolean interchangeability
        assert(self::matches(self::_TRUE,  false));
        assert(self::matches(self::_FALSE, true));

        // Various types that should work
        assert(self::matches(self::_NULL,  null));
        assert(self::matches(self::_INT, 57));
        assert(self::matches(self::_FLOAT, 98.6));
        assert(self::matches(self::_STRING, 'a string'));
        assert(self::matches(self::_ARRAY, ['an array element', 'another']));

        // Make sure 0/empty types still work.
        $empty_obj = new \stdClass();
        assert(self::matches(self::_INT, 0));
        assert(self::matches(self::_FLOAT, 0.0));
        assert(self::matches(self::_STRING, ''));
        assert(self::matches(self::_ARRAY, []));
        assert(self::matches(self::_OBJECT, $empty_obj));

        assert(!self::matches(self::_NULL,  0));
        assert(!self::matches(self::_NULL,  0.0));
        assert(!self::matches(self::_NULL,  ''));
        assert(!self::matches(self::_NULL,  []));
        assert(!self::matches(self::_NULL,  $empty_obj));

        assert(!self::matches(self::_FALSE,  0));
        assert(!self::matches(self::_FALSE,  0.0));
        assert(!self::matches(self::_FALSE,  ''));
        assert(!self::matches(self::_FALSE,  []));
        assert(!self::matches(self::_FALSE,  $empty_obj));

        // Overlap
        $method_str = __METHOD__;
        assert(self::matches(self::_STRING,   $method_str));
        assert(self::matches(self::_CALLABLE, $method_str));

        $method_arr = [__CLASS__, __FUNCTION__];
        assert(self::matches(self::_ARRAY,    $method_arr));
        assert(self::matches(self::_CALLABLE, $method_arr));

        assert(self::matches(self::_ARRAY,    ['a']));
        assert(self::matches(self::_ITERABLE, ['a']));

        // Mixed type = EVERYTHING
        assert(self::matches(self::_MIXED, null));
        assert(self::matches(self::_MIXED, true));
        assert(self::matches(self::_MIXED, false));
        assert(self::matches(self::_MIXED, 0));
        assert(self::matches(self::_MIXED, 57));
        assert(self::matches(self::_MIXED, 0.0));
        assert(self::matches(self::_MIXED, 98.6));
        assert(self::matches(self::_MIXED, ''));
        assert(self::matches(self::_MIXED, 'a string'));
        assert(self::matches(self::_MIXED, []));
        assert(self::matches(self::_MIXED, ['an array element', 'another']));
        assert(self::matches(self::_MIXED, $empty_obj));

        // Never type = NOTHING
        assert(!self::matches(self::_NEVER, null));
        assert(!self::matches(self::_NEVER, true));
        assert(!self::matches(self::_NEVER, false));
        assert(!self::matches(self::_NEVER, 0));
        assert(!self::matches(self::_NEVER, 57));
        assert(!self::matches(self::_NEVER, 0.0));
        assert(!self::matches(self::_NEVER, 98.6));
        assert(!self::matches(self::_NEVER, ''));
        assert(!self::matches(self::_NEVER, 'a string'));
        assert(!self::matches(self::_NEVER, []));
        assert(!self::matches(self::_NEVER, ['an array element', 'another']));
        assert(!self::matches(self::_NEVER, $empty_obj));

        // --- Error handling ---

        // Emulate what happens if ZType::matches(...) is called with an invalid ZType ID.
        $threw = false;
        try {
            assert(self::matches(self::E_NOT_A_ZTYPE, null));
        } catch ( \UnexpectedValueException $e ) {
            $threw = true;
            //echo "{$e->__toString()}\n";
        }
        assert($threw);

        // We can never match the _REFERENCE ZType due to PHP argument passing semantics.
        $threw = false;
        try {
            $arr = [1,2,3];
            $ref_arr = &$arr;
            assert(self::matches(self::_REFERENCE, $ref_arr));
        } catch ( \UnexpectedValueException $e ) {
            $threw = true;
            //echo "{$e->__toString()}\n";
        }
        assert($threw);

        // Constant expressions can't be identified due to PHP argument passing semantics.
        $threw = false;
        try {
            assert(self::matches(self::_CONSTANT_AST, 1+1));
        } catch ( \UnexpectedValueException $e ) {
            $threw = true;
            //echo "{$e->__toString()}\n";
        }
        assert($threw);

        // Void types don't exist _by definition_.
        $threw = false;
        try {
            assert(self::matches(self::_VOID, null));
        } catch ( \UnexpectedValueException $e ) {
            $threw = true;
            //echo "{$e->__toString()}\n";
        }
        assert($threw);

        // What even is a "static" type?
        // The zend_types.h file never explained...
        $threw = false;
        try {
            assert(self::matches(self::_STATIC, null));
        } catch ( \UnexpectedValueException $e ) {
            $threw = true;
            //echo "{$e->__toString()}\n";
        }
        assert($threw);
    }

    public static function unittests() : void
    {
        $class_fqn = self::class;
        echo("Running `$class_fqn::unittests()`\n");

        self::unittest_of();
        self::unittest_matches();

        echo("  ... passed.\n\n");
    }
}
?>
