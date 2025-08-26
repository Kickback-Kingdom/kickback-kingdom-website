<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions;

/**
* @phpstan-import-type  kkdebug_frame_a               from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
* @phpstan-import-type  kkdebug_backtrace_a           from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
* @phpstan-import-type  kkdebug_frame_paranoid_a      from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
* @phpstan-import-type  kkdebug_backtrace_paranoid_a  from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
*/
interface ThrowableWithContextMessages
{
    /**
    * Cause uncaught throwable to emit `$msg` before `getMessage()`.
    *
    * More specifically: this affects the output of `__toString()`,
    * which is what PHP calls when emitting error messages due to
    * uncaught exceptions.
    *
    * This is useful for providing context to more specific errors
    * that are emitted by called functions.
    *
    * Example:
    * ```
    *   // Testing framework logic for scanning a project for tests.
    *   // Here we open a directory that may contain .PHP files to scan.
    *   try {
    *       $dir = Directory::open($directory_path);
    *   } catch(\IKickbackException $e) {
    *       $class_shortname = str_replace(__NAMESPACE__ . '\\', '', __CLASS__);
    *
    *       $e->say_before_message(fn() =>
    *           "$class_shortname attempted to scan for .PHP files ".
    *           'containing tests, but encountered an error:');
    *
    *       throw $e;
    *   }
    * ```
    *
    * In this example, the `Directory::open` function can throw a number
    * of exceptions (usually `IO_IException`-type exceptions, but we are
    * catching even more if possible). `Directory::open` can emit an
    * accurate message about the exact nature of what went wrong
    * _with the filesystem_, but it has no idea _why_ we wanted to open
    * a directory in the first place.
    *
    * Meanwhile, the caller knows that we're opening a directory because
    * we are scanning for tests. But, the caller does NOT know exactly
    * what went wrong with the filesystem operation(s).
    *
    * Ideally, an error message should provide BOTH sets of information:
    * the higher-level "what/why", and the lower-level "how/where".
    *
    * The `say_before_message` and `say_after_message` methods provide a way
    * to do exactly that: place high level and low level information together
    * in an error message.
    *
    * The `__toString()` method will print the file basename and line number
    * for each method. It will not print the full file path, nor will it
    * print the function name. This is for the sake of brevity, as the
    * additional information would make it difficult to read the exception
    * messages.
    *
    * If explicitly providing a file and/or line number, then the
    * `$in_function` parameter is required. Although it isn't printed,
    * it is still used to determine the correct order in which to
    * print the "before" messages:
    * * Messages from the same function will print as they appear
    *     in the function: top to bottom.
    * * Messages from different functions will be printed in "stack"
    *     order: each call to `say_before_message` treats any previous
    *     calls to `say_before_message` as part of the "message",
    *     so earlier calls will print closer to the "main" message.
    *
    * The location parameters are defined by the `Uniform Transitive Source Location Parameters`
    * convention which is currently documented in the class-level documentation
    * of the `\Kickback\Common\Meta\Location` class.
    *
    * @see say_after_message
    * @see \Kickback\Common\Meta\Location
    *
    * @param  string|\Closure():string          $msg
    * @param ?string                            $in_file
    * @param  ($in_file is string ? string : (?string)
    *         )                                 $in_function
    * @param int                                $at_line
    * @param int<0,max>                         $at_trace_depth
    * @param ?kkdebug_backtrace_paranoid_a      $trace
    *
    * @phpstan-impure
    * @throws void
    */
    public function say_before_message(
        string|\Closure   $msg,
        ?string           $in_file = null,
        ?string           $in_function = null,
        int               $at_line = \PHP_INT_MIN,
        int               $at_trace_depth = 0,
        ?array            $trace = null) : void;

    /**
    * Cause uncaught exception to emit `$msg` after `getMessage()`.
    *
    * More specifically: this affects the output of `__toString()`,
    * which is what PHP calls when emitting error messages due to
    * uncaught exceptions.
    *
    * This is useful for providing context to more specific errors
    * that are emitted by called functions.
    *
    * @see say_before_message
    *
    * Even though this will print after `getMessage()`,
    * it will still print _before_ the backtrace portion
    * of the error message (e.g. `getTraceAsString()`).
    * (At least, it will with PHP's default exception handler,
    * or any exception handler that does not modify that
    * behavior.)
    *
    * Although this method has an `$in_function` parameter similar
    * to the one in `say_before_message`, it isn't actually used
    * for anything in this case. ("After" messages will naturally print
    * in the same order regardless of whether they were from the same
    * function or not.) However, to mirror the signature of
    * `say_after_message`, it is still present and also has
    * the same argument-passing semantics as in `say_before_message`.
    * (That is: it will be required or optional whenever the
    * corresponding `say_before_message` is required or optional,
    * respectively.)
    *
    * @param  string|\Closure():string          $msg
    * @param ?string                            $in_file
    * @param  ($in_file is string ? string : (?string)
    *         )                                 $in_function
    * @param int                                $at_line
    * @param int<0,max>                         $at_trace_depth
    * @param ?kkdebug_backtrace_paranoid_a      $trace
    *
    * @phpstan-impure
    * @throws void
    */
    public function say_after_message(
        string|\Closure   $msg,
        ?string           $in_file = null,
        ?string           $in_function = null,
        int               $at_line = \PHP_INT_MIN,
        int               $at_trace_depth = 0,
        ?array            $trace = null) : void;

    /**
    * Whether or not either of `say_before_message` or `say_after_message` have been called.
    *
    * @phpstan-pure
    * @throws void
    */
    public function have_context_messages() : bool;

    // These methods are helpful for other abstractions to be able to
    // use the `say_after`/`say_before` feature programmatically.
    // One example is in `Kickback\Common\Exceptions\Reporting\Report`.

    /**
    * The number of messages that have been set with `say_before`.
    *
    * @phpstan-pure
    * @throws void
    */
    public function say_before_message_count() : int;

    /**
    * The number of messages that have been set with `say_after`.
    *
    * @phpstan-pure
    * @throws void
    */
    public function say_after_message_count() : int;

    /**
    * The number of messages that have been set with either `say_before` or `say_after`.
    *
    * @phpstan-pure
    * @throws void
    */
    public function context_message_count() : int;
}
?>
