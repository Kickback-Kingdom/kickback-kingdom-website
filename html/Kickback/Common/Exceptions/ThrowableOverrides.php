<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions;

use Kickback\Common\Exceptions\Internal\DefaultMethods;
use Kickback\Common\Exceptions\ThrowableWithAssignableFields;

/**
* Overrides for non-inheritable methods of the \Throwable class.
*
* @see \Kickback\Common\Exceptions\ThrowableWithAssignableFields
*/
final class ThrowableOverrides
{
    use \Kickback\Common\Traits\StaticClassTrait;

    /**
    * Returns `$e->message()` if available, otherwise `$e->getMessage()`.
    *
    * This function is helpful when working with exceptions or throwables
    * that could potentially be outside of the KickbackThrowable hierarchy.
    *
    * PHP doesn't allow `getMessage()` to be overridden, so whenever
    * the `message()` value of a KickbackThrowable is altered, the
    * two will no longer agree.
    *
    * Typically, the `message()` value will be the most up-to-date
    * and/or correct, so we want to use it. However, it is not always
    * available, so the next logical course of action is to attempt
    * to use `message()`, but fail over to `getMessage()` if the former
    * wans't available.
    */
    public static function message(\Throwable $e) : string
    {
        if ( $e instanceof ThrowableWithAssignableFields ) {
            return $e->message_pure();
        } else {
            return $e->getMessage();
        }
    }

    /**
    * Returns `$e->code()` if available, otherwise `$e->getCode()`.
    *
    * @see self::message
    */
    public static function code(\Throwable $e) : int
    {
        if ( $e instanceof ThrowableWithAssignableFields ) {
            return $e->code();
        } else {
            return $e->getCode();
        }
    }

    /**
    * Returns `$e->file()` if available, otherwise `$e->getFile()`.
    *
    * @see self::message
    */
    public static function file(\Throwable $e) : string
    {
        if ( $e instanceof ThrowableWithAssignableFields ) {
            return $e->file();
        } else {
            return $e->getFile();
        }
    }

    /**
    * Returns `$e->func()` if available, otherwise it will attempt to
    * retrieve the function name from the contents of `$e->getTrace()`.
    *
    * @see self::message
    */
    public static function func(\Throwable $e) : string
    {
        if ( $e instanceof ThrowableWithAssignableFields ) {
            return $e->func();
        } else {
            $trace = $e->getTrace();
            if ( 0 === \count($trace) ) {
                return DefaultMethods::UNKNOWN_FUNCTION_NAME;
            }

            // $e->getTrace() is likely to return a frame that is
            // the caller's caller's frame instead of the caller's frame.
            // If that happens, we catch it here, and admit that we Just Don't Know.
            $frame = $trace[0];
            if (\array_key_exists('file',$frame) && $frame['file'] !== $e->getFile()) {
                return DefaultMethods::UNKNOWN_FUNCTION_NAME;
            } else
            if (\array_key_exists('line',$frame) && $frame['line'] !== $e->getLine()) {
                return DefaultMethods::UNKNOWN_FUNCTION_NAME;
            }

            // If we found the correct frame, we'll use it!
            return DefaultMethods::getFunc($e, $trace);
        }
    }

    /**
    * Returns `$e->line()` if available, otherwise `$e->getLine()`.
    *
    * @see self::message
    */
    public static function line(\Throwable $e) : int
    {
        if ( $e instanceof ThrowableWithAssignableFields ) {
            return $e->line();
        } else {
            return $e->getLine();
        }
    }
}
?>
