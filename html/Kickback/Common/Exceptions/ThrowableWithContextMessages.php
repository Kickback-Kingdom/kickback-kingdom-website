<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions;

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
    * @see say_after_message
    *
    * @param      string|\Closure():string          $msg
    *
    * @phpstan-impure
    * @throws void
    */
    public function say_before_message(string|\Closure $msg) : void;

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
    *
    * @param      string|\Closure():string          $msg
    *
    * @phpstan-impure
    * @throws void
    */
    public function say_after_message(string|\Closure $msg) : void;
}
?>
