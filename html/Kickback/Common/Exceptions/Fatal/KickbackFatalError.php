<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions\Fatal;

use Kickback\Common\Exceptions\IKickbackThrowable;
use Kickback\Common\Exceptions\KickbackThrowable;
use Kickback\Common\Exceptions\Fatal\KickbackFatalErrorTrait;

/**
* All Kickback Kingdom _fatal_ throwables should (indirectly) implement this.
*
* Fatal errors are unrecoverable errors.
*
* In other words: they are used whenever the cause of the error invalidates
* potentially all program state, thus making it wise to crash immediately
* instead of continuing to operate in an "anything might happen"
* situation.
*
* Note that a key word in the above definition is "potentially".
* In a _production environment_, the callee that is deciding whether
* to "crash or throw" are allowing the caller to decide if invalid
* program state from a certain error will _actually_ lead to
* invalid program state or not.
*
* This can matter in a security context:
* * If bugged code just leads to a loss of a feature,
*     then we may _actaully_ want to continue and salvage
*     the user-experience of our working functionality.
* * If bugged code leads to truly unknown consequences,
*     ESPECIALLY if it's within validation code, permission checks,
*     or code that handles system resources (network IO, databases,
*     filesystem access, etc), then it is potentially exploitable
*     by attackers to escalate access to systems that they should
*     not be able to access. In this latter case, we DO NOT
*     want to continue execution, even in (especially in)
*     a production environment.
*
* This class is useful for quickly defining new types of fatal errors,
* especially new broad classifications of errors.
* (Though, if you're not defining a broad class of errors,
* do try to extend something more specific.)
*
* Example usage:
*   <code>
*   <?php
*   declare(strict_types=1);
*   namespace ((your namespace here));
*
*   use Kickback\Common\Exceptions\Fatal\IKickbackFatalError;
*   use Kickback\Common\Exceptions\Fatal\KickbackFatalError;
*
*   interface ITestError extends IKickbackFatalError {}
*   class TestError extends KickbackFatalError implements ITestError {}
*   ?>
*   </code>
*
* When defining an error, and if it's appropriate to inherit from an
* error already available
* in {@link https://www.php.net/manual/en/reserved.exceptions.php PHP's pre-defined exceptions}
* or in {@link https://www.php.net/manual/en/spl.exceptions.php SPL's exceptions},
* then the recommendation is to have the new error class extend from
* the PHP (or 3rd party) class, then have the interface extend from
* IKickbackFatalError. KickbackFatalErrorTrait can be used to simplify
* implementation of commonly needed methods.
*
* Example usage, defining new exceptions:
*   <code>
*   <?php
*   declare(strict_types=1);
*   namespace ((your namespace here));
*
*   use Kickback\Common\Exceptions\Fatal\IKickbackFatalError;
*   use Kickback\Common\Exceptions\Fatal\KickbackFatalError;
*   use Kickback\Common\Exceptions\Fatal\KickbackFatalErrorTrait;
*
*   interface ITestError extends IKickbackFatalError {}
*
*   class TestError extends KickbackFatalError implements ITestError {
*       use KickbackFatalErrorTrait;
*   }
*   ?>
*   </code>
*
* All KickbackFatalErrors (or at least ones using `KickbackFatalErrorTrait`)
* will, in addition to the interface constraints, have these methods:
* * KickbackFatalError::throw_or_crash(...)  or KickbackFatalError->throw_or_crash()
* * KickbackFatalError::crash_or_throw(...)  or KickbackFatalError->crash_or_throw()  (just an alias of throw_or_catch)
* * KickbackFatalError::crash(...)           or KickbackFatalError->crash()
*
* They may be called as either static methods or as object methods.
*
* If they are called as static methods, then they accept arguments,
* and the arguments will be passed to the constructor of the given
* KickbackFatalError derivative. After that, it will call the
* corresponding object method on the newly constructed object.
*
* Example usage, throwing and catching exceptions:
*   <code>use Kickback\Common\Exceptions\Fatal\INotImplementedError;
*   use Kickback\Common\Exceptions\Fatal\NotImplementedError;
*
*   function foo() : void
*   {
*           $err = new NotImplementedError("Throwing from `foo` using `\$err->throw_or_crash();`");
*           $err->throw_or_crash();
*   }
*
*   function bar() : void
*   {
*           NotImplementedError::throw_or_crash("Throwing from `bar` using `NotImplementedError::throw_or_crash(...);`");
*   }
*
*   try
*   {
*       foo();
*   }
*   catch (INotImplementedError $e)
*   {
*       echo "Yay, we caught the FIRST NotImplementedError!\n";
*       echo "NotImplementedError message:\n";
*       echo "\n";
*       echo $e->__toString();
*   }
*
*   echo "\n\n";
*
*   try
*   {
*       bar();
*   }
*   catch (INotImplementedError $e)
*   {
*       echo "Yay, we caught the SECOND NotImplementedError!\n";
*       echo "NotImplementedError message:\n";
*       echo "\n";
*       echo $e->__toString();
*   }
*
*   echo "\n\n";
*   </code>
*
* @method  throw_or_crash()
* @method  crash_or_throw()
* @method  crash()
*
* @see \Kickback\Common\Exceptions\KickbackException
*/
interface IKickbackFatalError extends IKickbackThrowable
{
    /**
    * Should fatal errors should crash or throw?
    *
    * Fatal errors are represented by exceptions/throwables inheriting from
    * \Kickback\Common\Exceptions\Fatal\IKickbackFatalError or
    * \Kickback\Common\Exceptions\Fatal\KickbackFatalError
    *
    * @see throw_or_crash
    */
    public static function should_crash_on_fatal_errors() : bool;

    // TODO: I haven't been able to figure out how to prototype these
    // without also breaking the `__call` and `__callStatic` dispatch
    // in the process. Which is too bad, because it makes it really
    // difficult to document these quite handy methods.

    // /**
    // * Thrown or halt unconditionally, depending on system|host context.
    // *
    // * This is shorthand for
    // * ```
    // *   if ( self::should_crash_on_fatal_errors() ) {
    // *       $fatal_error->crash();
    // *   } else {
    // *       throw $fatal_error;
    // *   }
    // * ```
    // *
    // * The error message will be displayed or logged in either case.
    // *
    // * Note that any code emitting/raising a security-relevant fatal error
    // * should NOT call this, but instead just call `crash`. But only if
    // * it's _for-sure_ a security-relevant issue; don't unconditionally
    // * crash if the implications might NOT be security-relevant, and the
    // * caller can more appropriately make the determination.
    // *
    // * @see should_crash_on_fatal_errors
    // * @see crash
    // *
    // * @return never
    // */
    // public static function throw_or_crash(mixed ...$args) : void;
    //
    // /**
    // * Alias of throw_or_crash.
    // *
    // * @see throw_or_crash
    // * @see should_crash_on_fatal_errors
    // * @see crash
    // *
    // * @return never
    // */
    // public static function crash_or_throw(mixed ...$args) : void;
    //
    // /**
    // * Halt unconditionally, after displaying or logging the error message.
    // *
    // * @return never
    // */
    // public static function crash(mixed ...$args) : void;
}

/**
* Concrete counterpart of \Kickback\Common\Exceptions\KickbackException.
*
* Along with KickbackException, this is useful for quickly defining
* new types of exceptions, especially new broad classifications of
* exceptions. (Though, if you're not defining a broad class of exceptions,
* do try to extend something more specific.)
*
* Example usage:
*   <code>
*   <?php
*   declare(strict_types=1);
*   namespace ((your namespace here));
*
*   use Kickback\Common\Exceptions\Fatal\IKickbackFatalError;
*   use Kickback\Common\Exceptions\Fatal\KickbackFatalError;
*
*   interface ITestError extends IKickbackFatalError {}
*   class TestError extends KickbackFatalError implements ITestError {}
*   ?>
*   </code>
*
* @see IKickbackFatalError
* @see \Kickback\Common\Exceptions\KickbackException
*/
abstract class KickbackFatalError extends KickbackThrowable implements IKickbackFatalError
{
    use KickbackFatalErrorTrait;
}
?>
