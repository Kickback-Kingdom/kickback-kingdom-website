<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions;

use Kickback\Common\Exceptions\DebugBacktraceAliasTypes;

/**
* @phpstan-import-type  kkdebug_frame_a               from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
* @phpstan-import-type  kkdebug_backtrace_a           from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
* @phpstan-import-type  kkdebug_frame_paranoid_a      from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
* @phpstan-import-type  kkdebug_backtrace_paranoid_a  from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
*/
interface ThrowableWithAssignableFields
{
    /**
    * Cause this throwable's `message_prefix` field to be calculated (once).
    *
    * This should be called whenever the Throwable/Exception's fields/state
    * changes in a way that would require `__toString()` to print multiple
    * messages or a message with multiple lines.
    *
    * If the `message_prefix` has already been calculated, this will do nothing.
    *
    * (This is not a(n) (in)validation routine because the only thing that can
    * invalidate the _contents_ of the prefix are changes to the file path
    * or line number, and those are already handled internally within
    * the trait.)
    *
    * Here are two known examples of when this is required:
    * * The `message()` field changed and is now a multi-line message.
    * * The `say_before_message` or `say_after_message` methods
    *     from `ThrowableContextMessageHandlingTrait` were called.
    *     Now there are multiple messages to print, which will require
    *     the main message to have a file+line prefix to distinguish
    *     it from the other messages.
    *
    * @phpstan-impure
    * @throws void
    */
    public function populate_message_prefix() : void;

    /**
    * Strictly pure (does not modify the Throwable/Exception object) version of the `message` property-method.
    *
    * Use this when the calling code doesn't need to set the `message` field,
    * but DOES need to be statically varifiable as not mutating the
    * Throwable/Exception object.
    *
    * @phpstan-pure
    * @throws void
    */
    public function message_pure() : string;

    /**
    * Invoked instead of `getMessage()` when `__toString()` is called.
    *
    * It is impossible to change the contents of `getMessage()` because
    * it is marked `final` (and seems tied to the Zend implementation).
    *
    * However, we CAN change what ends up in `__toString()`, since
    * `__toString()` can be overridden (and we do so anyways to implement
    * context message handling, for `say_before` and `say_after`).
    *
    * Thus, the `message()` property-function will replace `getMessage()`
    * for purposes of `__toString()` output. This gives us a way to
    * change the exception's main message after it has been constructed.
    *
    * The caveat is that there may be other places besides `__toString()`
    * where `getMessage()` is used. This property-function will be unable
    * to replace the `getMessage()` contents in those cases.
    *
    * If a message is never assigned by this method, then it will default
    * to returning the result of `getMessage()`.
    *
    * The provided `$msg` does not need to be a string, but can also be
    * a closure that returns a string. This essentially allows the string
    * to be calculated after the `message($msg)` setter has already returned.
    * Usually this would be called in `__toString()`.
    *
    * If `$msg` is passed as a string-returning-closure, then this method
    * provides no guarantees about whether it will be memoized or not.
    * It may be called any number of times during other method calls on
    * the Throwable object. Calling `__toString()` will cause `$msg` to
    * be called _at least_ once.
    *
    * It is possible to change the source location for the message
    * at the same time as changing the message. The location parameters
    * are defined by the `Uniform Transitive Source Location Parameters`
    * convention which is currently documented in the class-level documentation
    * of the `\Kickback\Common\Meta\Location` class.
    *
    * If the source location parameters are not provided, then the location
    * associated with the message will not be changed.
    *
    * @see \Kickback\Common\Meta\Location
    *
    * @param  string|(\Closure():string)|null   $msg
    * @param ?string                            $in_file
    * @param ?string                            $in_function
    * @param int                                $at_line
    * @param ?int<0,max>                        $at_trace_depth
    * @param ?kkdebug_backtrace_paranoid_a      $trace
    *
    * @throws void
    */
    public function message(
        string|\Closure|null   $msg = null,
        ?string                $in_file = null,
        ?string                $in_function = null,
        int                    $at_line = \PHP_INT_MIN,
        ?int                   $at_trace_depth = null,
        ?array                 $trace = null
    ) : string;

    /**
    * Invoked instead of `getFile()` when `__toString()` is called.
    *
    * @phpstan-pure
    * @throws void
    */
    public function file() : string;

    /**
    * The (fully qualified) name of the function that threw the exception.
    *
    * This can be modified with location-setting methods such as
    * `set_location`, `calculate_location_from`, and `message`.
    *
    * By default, this is derived from the first stack frame
    * in the trace returned by `getTrace()`.
    *
    * @phpstan-pure
    * @throws void
    */
    public function func() : string;

    /**
    * Invoked instead of `getLine()` when `__toString()` is called.
    *
    * @phpstan-pure
    * @throws void
    */
    public function line() : int;

    /**
    * Explicitly set the file, function, and line location for the exception.
    *
    * As of this writing, the `$function` information isn't used by the
    * exception object itself (e.g. it won't get printed if the exception
    * is thrown and not caught), but it does make the information available
    * via the `function()` getter.
    *
    * @param string                             $in_file
    * @param string                             $in_function
    * @param int<0,max>                         $at_line
    *
    * @phpstan-impure
    * @throws void
    */
    public function set_location(
        string    $in_file,
        string    $in_function,
        int       $at_line
    ) : void;

    /**
    * Sets the exception's location based on either the current backtrace, or explicit values.
    *
    * These parameters are defined by the `Uniform Transitive Source Location Parameters`
    * convention which is currently documented in the class-level documentation
    * of the `\Kickback\Common\Meta\Location` class.
    *
    * @see \Kickback\Common\Meta\Location
    *
    * @param ?string                            $in_file
    * @param ?string                            $in_function
    * @param int                                $at_line
    * @param int<0,max>                         $at_trace_depth
    * @param ?kkdebug_backtrace_paranoid_a      $trace
    *
    * @phpstan-impure
    * @throws void
    */
    public function calculate_location_from(
        ?string               $in_file = null,
        ?string               $in_function = null,
        int                   $at_line = \PHP_INT_MIN,
        int                   $at_trace_depth = 0,
        ?array                $trace = null
    ) : void;
}
?>
