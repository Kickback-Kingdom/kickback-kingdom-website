<?php
declare(strict_types=1);

namespace Kickback\Common\Meta;

use Kickback\Common\Traits\StaticClassTrait;

/**
* Handles source-code (line) location information, e.g. file+function+line info.
*
* ## Uniform Transitive Source Location Parameters ##
*
* All methods in the Kickback Framework that deal with source-code lines should,
* when possible, allow the caller to provide that information. Such methods
* should accept that information using a certain sequence of parameters, which
* are hereby known as Uniform Transitive Source Location Parameters (UTSLP).
*
* It is named for these properties:
* * Uniform: It can be used everywhere because it handles almost every use-case.
* * Transitive: Functions can forward the information to other functions without
*       any loss of information or expressiveness.
* * Source Location: This is specifically for referring to file + function + line
*       locations within text files (usually source code, specifically).
* * Parameters: This convention is defined primarily in terms of how
*       method parameters are defined and arranged.
*
*
* ### UTSLP - Parameter list pattern ###
*
* This parameter pattern should go at the end of methods, and is defined like so:
* ```
* /**
* * (at)param ?string                           $in_file
* * (at)param ?string                           $in_function
* * (at)param int                               $at_line
* * (at)param int&lt;0,max&gt;                        $at_trace_depth
* * (at)param ?kkdebug_backtrace_paranoid_a     $trace
* * /
* public function my_method(
*     ...
*     non-location parameters
*     ...
*     ?string   $in_file = null,
*     ?string   $in_function = null,
*     int       $at_line = \PHP_INT_MIN,
*     int       $at_trace_depth = 0,
*     ?array    $trace = null
* ) : my_method_return_type
* { ... }
* ```
*
* This gives maximum flexibility to the caller in determining what appears
* in any exceptions, audits, or other reports about source code activities:
* * The caller may omit all such arguments, and the callee will use \debug_backtrace to get location information.
* * The caller may explicitly provide file+function+line information. (This is especially useful during testing.)
* * The caller may adjust the stacktrace depth using `$at_trace_depth`, which
*     allows the caller to accept location information information from their
*     own caller and then forward that information to a called function/method.
* * The caller may provide their own backtrace using the `$trace` parameter,
*     which, if known, can avoid duplicate calls to \debug_backtrace.
*
*
* ### UTSLP - Parameter definitions ###
*
* The `$in_file`, `$in_function`, and `$at_line` parameters provide exactly
* the information as their names suggest. Details:
* * A line number (`$at_line`) of `\PHP_INT_MIN` indicates that the caller
*      is not passing line information.
* * If the file is not known, use '{unknown file}'.
* * If the function is not known, use '{unknown function}'.
* * A line number of `0` indicates that the line number is not known or not relevant.
* * These fields override the correlating element that would come from the backtrace.
* * If all 3 are provided, then the backtrace is not used.
* * Otherwise, the backtrace will provide any fields that are not provided explicitly.
*
* The `$at_trace_depth` parameter determines which "frame" of the backtrace to
* retrieve file+function+line information from.
*
* The `$trace` parameter allows the caller to provide an explicit backtrace.
* The called function/method shall not call `\debug_backtrace` if `$trace`
* is provided.
*
* Details:
* * `$at_trace_depth = 0` indicates the frame of the caller, which is `$trace[2]`.
* * `$at_trace_depth = 1` indicates the frame of the caller's caller, which is `$trace[3]`.
* * `$at_trace_depth = 2` indicates the frame of the caller's caller's caller, which is `$trace[4]`.
* * ... and so on.
* * It is an assertable error to provide a trace depth that would select a frame
*      that does not exist in the `$trace` parameter. (But only if `$trace` is
*      provided; that is, `$trace` isn't null.)
* * If `$trace` is `null`, then the called function/method is responsible for
*      acquiring a backtrace. (It may also forward this responsibility to another
*      function or method.)
*
*
* ### UTSLP - Forwarding location information ###
*
* When forwarding a caller's UTSLP information,
* there is a simplicity-vs-efficiency tradeoff.
*
* The simpler way to do it looks like so:
* ```
* public function my_method(
*     string    $foo,
*     string    $bar,
*     string    $baz,
*     ?string   $in_file = null,
*     ?string   $in_function = null,
*     int       $at_line = \PHP_INT_MIN,
*     int       $at_trace_depth = 0,
*     ?array    $trace = null
* ) : my_method_return_type
* {
*     return self:my_other_method(
*         $foo, $bar, $baz,
*         $in_file, $in_function, $at_line, $at_trace_depth+1, $trace);
* }
* ```
*
* Notice the adding of `1` to the `$at_trace_depth`: this represents the
* additional stack frame for the `my_method` call in the backtrace. This
* causes `my_other_method` to show the file+function+line that called
* `my_method`, instead of just _always_ returning the same line within
* `my_method`, the latter of which is typically not very helpful.
*
* The more efficient way to do this is like so:
* ```
* public function my_method(
*     string    $foo,
*     string    $bar,
*     string    $baz,
*     ?string   $in_file = null,
*     ?string   $in_function = null,
*     int       $at_line = \PHP_INT_MIN,
*     int       $at_trace_depth = 0,
*     ?array    $trace = null
* ) : my_method_return_type
* {
*     if (Location::need_backtrace(
*         $in_file, $in_function, $at_line, $at_trace_depth, $trace))
*     {
*         $at_trace_depth = 2 + $at_trace_depth;
*         $trace = \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $at_trace_depth);
*     }
*     Location::process_info($in_file, $in_function, $at_line, $at_trace_depth, $trace);
*
*     return self:my_other_method(
*         $foo, $bar, $baz,
*         $in_file, $in_function, $at_line);
* }
* ```
*
* This pattern is efficient because it limits the number of frames that
* `\debug_backtrace` is required to return, regardless of the additional
* call stack depth that occurs within `self:my_other_method`.
*
* We add `2` to the `$at_trace_depth` parameter because it makes the
* trace large enough to include the caller's stack frame. In the case
* where there is no forwarding of information, then `$at_trace_depth`
* will start as `0` and end up requesting 2 frames. The first frame
* will be this function, the `my_method` function. The 2nd frame will
* be the caller's function, along with their file and the line at which
* `my_method` was called.
*
* The call to `Location::process_info` will reify any backtrace information
* and perform all of the overriding logic for explicit file+function+line info.
*
* After the call to `Location::process_info`, the `$in_file`, `$in_function`,
* and `$at_line` arguments will be set (non-null, non-zero). Thus, it is
* no longer required to pass `$at_trace_depth` or `$trace` arguments into
* `self:my_other_method`, because the file+function+line information is
* already explicitly provided at this point.
*
* There is another notable optimization in the above boilerplate, which
* is the `DEBUG_BACKTRACE_IGNORE_ARGS` flag passed to `\debug_backtrace`.
* This avoids allocating additional arrays/memory for argument metadata,
* and avoids requiring `\debug_backtrace` to walk that information.
*
*
* ### UTSLP - Receiving location information ###
*
* Receiving location information has exactly the same process as the
* "efficient" way to forward location information, with the only difference
* being that the file+function+line arguments are consumed instead of being
* forwarded to another function.
*
* It looks like this:
* ```
* public function my_method(
*     string    $foo,
*     string    $bar,
*     string    $baz,
*     ?string   $in_file = null,
*     ?string   $in_function = null,
*     int       $at_line = \PHP_INT_MIN,
*     int       $at_trace_depth = 0,
*     ?array    $trace = null
* ) : void
* {
*     if (Location::need_backtrace(
*         $in_file, $in_function, $at_line, $at_trace_depth, $trace))
*     {
*         $at_trace_depth = 2 + $at_trace_depth;
*         $trace = \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $at_trace_depth);
*     }
*     Location::process_info($in_file, $in_function, $at_line, $at_trace_depth, $trace);
*
*     $exc = new KickbackException(
*         "The thingie happened:\\n".
*         "foo = '$foo'\\n".
*         "bar = '$bar'\\n".
*         "baz = '$baz'\\n");
*
*     $exc->set_location($in_file, $in_function, $at_line);
*     throw $exc;
* }
* ```
*
*
* ### UTSLP - Optional source location information ###
*
* It is possible to specify that _all_ source location is optional.
*
* This is done by marking the `$at_trace_depth` parameter as nullable
* and giving it a `null` default value instead of a `0` default.
*
* The called function can avoid undesired modification by calling
* `Location::is_set` to determine if the caller is requesting
* a change of source location information.
*
* This is useful whenever a function or method needs to be able to receive
* updated location information, but _also_ needs to be able to avoid
* modifying source location information if the caller doesn't want to
* change it.
*
* This is _usually_ not necessary, but one example of it is the
* `\Kickback\Common\Exceptions\ThrowableWithAssignableFields::message`
* method. This method should not change the exception's source location
* information unless the caller explicitly designates that information.
*
* ```
* /**
* * (at)param ?string                           $in_file
* * (at)param ?string                           $in_function
* * (at)param int                               $at_line
* * (at)param ?int&lt;0,max&gt;                       $at_trace_depth
* * (at)param ?kkdebug_backtrace_paranoid_a     $trace
* * /
* public function my_method(
*     ...
*     non-location parameters
*     ...
*     ?string   $in_file = null,
*     ?string   $in_function = null,
*     int       $at_line = \PHP_INT_MIN,
*     ?int      $at_trace_depth = null,
*     ?array    $trace = null
* ) : void
* {
*     if (!Location::is_set(
*         $in_file, $in_function, $at_line, $at_trace_depth, $trace))
*     {
*         // Caller does not wish to modify this object's location info.
*         return;
*     }
*
*     if (!isset($at_trace_depth)) {
*         $at_trace_depth = 0;
*     }
*
*     if (Location::need_backtrace(
*         $in_file, $in_function, $at_line, $at_trace_depth, $trace))
*     {
*         $at_trace_depth = 2 + $at_trace_depth;
*         $trace = \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $at_trace_depth);
*     }
*     Location::process_info($in_file, $in_function, $at_line, $at_trace_depth, $trace);
*
*     $this->path = $in_file;
*     $this->func = $in_function;
*     $this->line = $at_line;
* }
* ```
*
*
* @phpstan-import-type  kkdebug_frame_a               from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
* @phpstan-import-type  kkdebug_backtrace_a           from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
* @phpstan-import-type  kkdebug_frame_paranoid_a      from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
* @phpstan-import-type  kkdebug_backtrace_paranoid_a  from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
*
* @internal
*/
final class Location
{
    use StaticClassTrait;

    /**
    * Used to determine if the given parameters are usable as location info.
    *
    * If this returns `true`, the caller should check for `$at_trace_depth`
    * to be `null` and then set it to `0` if that's the case. This handles
    * the likely situation where the caller passed some explicit
    * file + function + line information, but didn't pass any trace (depth)
    * information. In such a case, the parameters are actually usable, but
    * the `$at_trace_depth` does need to be adapted into a non-nullable
    * value to avoid problems in downstream calculations.
    *
    * @param      ?string                        $in_file
    * @param      ?string                        $in_function
    * @param      int                            $at_line
    * @param      ?int<0,max>                    $at_trace_depth
    * @param      ?kkdebug_backtrace_paranoid_a  $trace
    *
    * @phpstan-assert-if-false  =null  $at_trace_depth
    * @phpstan-assert-if-false  =null  $in_file
    * @phpstan-assert-if-false  =null  $in_function
    * @phpstan-assert-if-false  =0     $at_line
    *
    * @phpstan-pure
    * @throws void
    */
    public static function is_set(
        ?string               $in_file,
        ?string               $in_function,
        int                   $at_line,
        ?int                  $at_trace_depth,
        ?array                $trace
    ) : bool
    {
        return (isset($at_trace_depth) || isset($trace)
            || isset($in_file) || isset($in_function) || $at_line !== \PHP_INT_MIN);
    }

    private static function unittest_is_set() : void
    {
        echo("  ".__FUNCTION__."()\n");

        // "no line" value; for brevity.
        $noln = \PHP_INT_MIN;

        // value indicating that we have a line number.
        $line = 0;

        // Simple non-null backtrace.
        $trc = [['function' => '']];

        assert(!self::is_set(null,null,$noln,null,null));
        assert( self::is_set(null,null,$noln,null,$trc));
        assert( self::is_set(null,null,$noln,1234,null));
        assert( self::is_set(null,null,$noln,1234,$trc));
        assert( self::is_set(null,null,$line,null,null));
        assert( self::is_set(null,null,$line,null,$trc));
        assert( self::is_set(null,null,$line,1234,null));
        assert( self::is_set(null,null,$line,1234,$trc));
        assert( self::is_set(null,'fn',$noln,null,null));
        assert( self::is_set(null,'fn',$noln,null,$trc));
        assert( self::is_set(null,'fn',$noln,1234,null));
        assert( self::is_set(null,'fn',$noln,1234,$trc));
        assert( self::is_set(null,'fn',$line,null,null));
        assert( self::is_set(null,'fn',$line,null,$trc));
        assert( self::is_set(null,'fn',$line,1234,null));
        assert( self::is_set(null,'fn',$line,1234,$trc));
        assert( self::is_set('aa',null,$noln,null,null));
        assert( self::is_set('aa',null,$noln,null,$trc));
        assert( self::is_set('aa',null,$noln,1234,null));
        assert( self::is_set('aa',null,$noln,1234,$trc));
        assert( self::is_set('aa',null,$line,null,null));
        assert( self::is_set('aa',null,$line,null,$trc));
        assert( self::is_set('aa',null,$line,1234,null));
        assert( self::is_set('aa',null,$line,1234,$trc));
        assert( self::is_set('aa','fn',$noln,null,null));
        assert( self::is_set('aa','fn',$noln,null,$trc));
        assert( self::is_set('aa','fn',$noln,1234,null));
        assert( self::is_set('aa','fn',$noln,1234,$trc));
        assert( self::is_set('aa','fn',$line,null,null));
        assert( self::is_set('aa','fn',$line,null,$trc));
        assert( self::is_set('aa','fn',$line,1234,null));
        assert( self::is_set('aa','fn',$line,1234,$trc));
    }

    /**
    * @param      ?string                        $in_file
    * @param      ?string                        $in_function
    * @param      int                            $at_line
    * @param      int<0,max>                     $at_trace_depth
    * @param      ?kkdebug_backtrace_paranoid_a  $trace
    *
    * @phpstan-pure
    * @throws void
    */
    public static function need_backtrace(
        ?string               $in_file,
        ?string               $in_function,
        int                   $at_line,
        int                   $at_trace_depth,
        ?array                $trace
    ) : bool
    {
        return !(isset($trace)
            || (isset($in_file) && isset($in_function) && $at_line !== \PHP_INT_MIN));
    }

    private static function unittest_need_backtrace() : void
    {
        echo("  ".__FUNCTION__."()\n");

        // "no line" value; for brevity.
        $noln = \PHP_INT_MIN;

        // value indicating that we have a line number.
        $line = 0;

        // Simple non-null backtrace.
        $trc = [['function' => '']];

        assert( self::need_backtrace(null,null,$noln,0,null));
        assert(!self::need_backtrace(null,null,$noln,0,$trc));
        assert( self::need_backtrace(null,null,$line,0,null));
        assert(!self::need_backtrace(null,null,$line,0,$trc));
        assert( self::need_backtrace(null,'fn',$noln,0,null));
        assert(!self::need_backtrace(null,'fn',$noln,0,$trc));
        assert( self::need_backtrace(null,'fn',$line,0,null));
        assert(!self::need_backtrace(null,'fn',$line,0,$trc));
        assert( self::need_backtrace('aa',null,$noln,0,null));
        assert(!self::need_backtrace('aa',null,$noln,0,$trc));
        assert( self::need_backtrace('aa',null,$line,0,null));
        assert(!self::need_backtrace('aa',null,$line,0,$trc));
        assert( self::need_backtrace('aa','fn',$noln,0,null));
        assert(!self::need_backtrace('aa','fn',$noln,0,$trc));
        assert(!self::need_backtrace('aa','fn',$line,0,null));
        assert(!self::need_backtrace('aa','fn',$line,0,$trc));
    }

    /**
    * @param      ?string                        $in_file
    * @param-out  string                         $in_file
    * @param      ?string                        $in_function
    * @param-out  string                         $in_function
    * @param      int                            $at_line
    * @param-out  int<0,max>                     $at_line
    * @param      int<0,max>                     $at_trace_depth
    * @param      ?kkdebug_backtrace_paranoid_a  $trace
    *
    * @throws void
    */
    public static function process_info(
        ?string               &$in_file,
        ?string               &$in_function,
        int                   &$at_line,
        int                   $at_trace_depth,
        ?array                $trace
    ) : void
    {
        // NOTE: This must be kept no-throw and no-dependency,
        // as it is used in Kickback\Common\Exceptions.
        // This code may be executed within an error or exception handler
        // (which is why throwing is a really bad idea), and exceptions
        // tend to be a common dependency of most things, so depending
        // on other code/classes has a high likelyhood of introducing
        // circular dependencies.

        if (!isset($trace)) {
            // Caller provided file+function+line information
            // explicitly, so there's no need for a stack trace.
            assert(isset($in_file));
            assert(isset($in_function));
            assert($at_line !== \PHP_INT_MIN);
            return;
        }

        // Process results of \debug_backtrace, if it was needed.
        assert($at_trace_depth <= \count($trace));
        $frame = $trace[\count($trace)-1];

        if (!isset($in_file)) {
            // Caller wishes to override function and/or line with
            // something specific, but determine the file dynamically.
            $in_file = \array_key_exists('file', $frame)
                ? $frame['file']
                : '{unknown file}';
        }

        if (!isset($in_function)) {
            // Caller wishes to override file and/or line with
            // something specific, but determine the function dynamically.
            $in_function = \array_key_exists('function',$frame)
                ? $frame['function']
                : '{unknown function}';
        }

        if ( $at_line === \PHP_INT_MIN ) {
            // Caller wishes to override file and/or function with
            // something specific, but determine the line dynamically.
            $at_line = \array_key_exists('line', $frame)
                ? $frame['line']
                : 0;
        }
    }

    public static function unittests() : void
    {
        $class_fqn = self::class;
        echo("Running `$class_fqn::unittests()`\n");

        self::unittest_is_set();
        self::unittest_need_backtrace();

        echo("  ... passed.\n\n");
    }
}
?>
