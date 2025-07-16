<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions;

// TODO: Beneath the "highly summarized advice for using exception classes and interfaces"
//   explain rationale for the interface-class parallel.
//   (E.g.: because interfaces have multiple-inheritance,
//   which allows us to use interfaces like tags,
//   while still being able to instantiate concrete instances.)
/**
* All Kickback Kingdom exceptions/errors should (indirectly) implement this.
*
* It allows for very concise definitions of new exception/throwable types.
*
* This establishes a more detailed exception hierarchy than what is available
* in {@link https://www.php.net/manual/en/reserved.exceptions.php PHP's pre-defined exceptions}
* or in {@link https://www.php.net/manual/en/spl.exceptions.php SPL's exceptions}.
*
* Here is the highly summarized advice for using exception classes and interfaces
* in code that uses the Kickback Kingdom framework (and possibly in PHP in general):
* * Always `catch` exception/error _interfaces_
* * Always `throw` exception/error _classes_ (you'll have to, because you can't `new` an interface)
* * When defining new exceptions/errors, always create both an interface _and_ a class for it
* * The Kickback autoloader has a feature for interfaces: it will look for IMyClass in MyClass.php
* * Optionally (but recommendedly) use the autoloader feature to define
*     `interface IMyException` and `class MyException` both in `MyException.php`
* * Attach relevant documentation to the "interface" version
* * Have the documentation of the "implementation" (class) version
*     just point to the interface (using the PHPDoc "at see" notation)
* * Non-fatal throwables are called "exceptions"
* * Fatal throwables are called "errors"
*
* This interface also makes it possible for `catch` clauses to distinguish between
* PHP-native (or 3rd party) exceptions and Kickback-specific exceptions/errors.
*
* When attempting the above distinction, always catch `IKickbackThrowable` instead of
* `KickbackThrowable__impl` when writing a catch-all for Kickback-specific
* errors and exceptions.
*
* The above advice is given because Kickback Kingdom exceptions and errors
* will ALWAYS implement IKickbackThrowable, but they may or may not extend
* KickbackThrowable__impl. This is because, if there is a more specific
* {@link https://www.php.net/manual/en/reserved.exceptions.php PHP pre-defined exception}
* or {@link https://www.php.net/manual/en/spl.exceptions.php SPL exception},
* then it is more appropriate to extend those. Thus, it is not always possible
* to place the concrete exception/error/throwable classes in ones'
* inheritance graph; but it IS always possible to place IKickbackThrowable in ones'
* inheritance graph.
*
* Note that whenever you are defining a new exception or error, you should
* probably NOT implement this directly (or extend KickbackThrowable directly)
* but instead implement IKickbackException or Fatal\IKickbackFatalError (or extend
* KickbackException or Fatal\KickbackFatalError when possible).
* If possible, implement/extends the most specific Exception/Error that
* represents the broader category of whatever Exception/Error
* is being thrown/reported/emitted.
*
* Credit:
* This interface was taken from a comment written by `ask at nilpo dot com`
* (ask@nilpo.com) on the PHP Exceptions documentation page:
* https://www.php.net/manual/en/language.exceptions.php#91159
*
* @see IKickbackException
*
* @phpstan-type  kkdebug_frame_paranoid_a  array{
*       function? : string,
*       line?     : int,
*       file?     : string,
*       class?    : class-string,
*       type?     : '->'|'::',
*       args?     : array<array-key, mixed>,
*       object?   : object
*   }
*
* @phpstan-type  kkdebug_frame_a  array{
*       function  : string,
*       line?     : int,
*       file?     : string,
*       class?    : class-string,
*       type?     : '->'|'::',
*       args?     : array<array-key, mixed>,
*       object?   : object
*   }
*
* @phpstan-type  kkdebug_backtrace_paranoid_a  array<int, kkdebug_frame_paranoid_a>
* @phpstan-type  kkdebug_backtrace_a           array<int, kkdebug_frame_a>
*/
interface IKickbackThrowable extends \Throwable
{
    // NOTE: getCode() is documented as having this signature:
    //   final public function getCode() : int
    // What we actually find in PHP's source code is this:
    //   /** @return int */
    //   final public function getCode() {} // TODO add proper type (i.e. int|string)
    // Source: https://github.com/php/php-src/blob/378b015360db45286b53b86181faf38ec504bc3c/Zend/zend_exceptions.stub.php#L52
    //
    // The former would have us declare our signature this way:
    //   public function getCode() : int
    // (Interface-ness demands that we drop the 'final', but in the case, the return type is what's important.)
    // But if we put THAT into our interface, we get this error:
    //   PHP Fatal error:  Declaration of KickbackThrowable::getCode() must be compatible with Kickback\Common\Exceptions\IKickbackThrowable::getCode(): int in /var/www/localhost/kickback-kingdom-website/html/Kickback/Common/Exceptions/KickbackThrowable.php on line 0
    //
    // We need to drop the `: int` from the end of the declaration to get rid
    // of the error, because that's what makes our declaration compatible with
    // the ACTUAL contents of the Zend source code.
    //
    // Ergo, we end up with this:
    //   /** @return int */
    //   public function getCode()
    // Type-safety problems aside, this is going to be brittle, because whenever
    // PHP devs fix their code and add the `: int` on the end of their definition,
    // it will break our code. But there's no way around it, because we MUST
    // define it the brittle way, just to get it to compile. Darn.
    // -- Chad Joan  2024-05-01

    /* Protected methods inherited from Throwable class */
    public function getMessage() : string;
    public function getPrevious() : ?\Throwable;
    /** @return int */
    public function getCode();           // User-defined Exception code
    public function getFile() : string;  // Source filename
    public function getLine() : int;     // Source line
    /** @return kkdebug_backtrace_a */
    public function getTrace() : array;
    public function getTraceAsString() : string;           // Formated string of trace

    /* Overrideable methods inherited from Throwable class */
    public function __toString() : string;                 // formated string for display
    public function __construct(?string $message = null, int $code = 0, ?\Throwable $previous = null);
}



/**
* Concrete counterpart of \Kickback\Common\Exceptions\IKickbackThrowable.
*
* Kickback Kingdom exceptions and errors should indirectly
* extend this class whenever there isn't a more appropriate exception type
* available in {@link https://www.php.net/manual/en/reserved.exceptions.php PHP's pre-defined exceptions}
* or in {@link https://www.php.net/manual/en/spl.exceptions.php SPL's exceptions}.
*
* Kickback Kingdom exceptions and errors should ALWAYS indirectly
* implement IKickbackThrowable (via the most specific-yet-still-relevant interface
* that implements IKickbackException or KickbackFatalError; or when modelling
* a new broad-level class of exception or error, via IKickbackException or
* KickbackFatalError themselves).
*
* Note that, as a consequence of the above guidelines, catch clauses should
* almost never attempt to catch KickbackThrowable, KickbackException,
* Fatal\KickbackFatalError, and possibly any concrete exception type.
* Catching exception/error interfaces is almost always a better way to go!
*
* Any catch clauses attempting to express a catch-all for Kickback-specific
* errors and exceptions should always catch `IKickbackThrowable` instead of
* `KickbackThrowable`, simply because it is not always possible for
* exceptions/errors to extend `KickbackThrowable` whenever integrating
* with other exception/error trees,
* such as {@link https://www.php.net/manual/en/reserved.exceptions.php PHP's pre-defined exceptions}
* or {@link https://www.php.net/manual/en/spl.exceptions.php SPL's exceptions}.
*
* Although caller code will almost never want to "catch" this type,
* it is still useful as a root-type for defining some concrete throwables.
* Fatal\KickbackFatalError is an example of this, and allows many concrete
* error types to be easily defined. (With the fallback being
* the KickbackThrowableTrait, and in either case, implmementing Fatal\KickbackFatalError.)
*
* @see IKickbackThrowable
*/
class KickbackThrowable extends \Exception implements IKickbackThrowable
{
    // This class was taken from a comment written by `ask at nilpo do
    // (ask\@nilpo.com) on the PHP Exceptions documentation page:
    // https://www.php.net/manual/en/language.exceptions.php#91159
    //
    // The contents of the class have been moved into `KickbackThrowableTrait`
    // to make it easier to do the same thing for exceptions that should
    // extend PHP's built-in (or SPL) exceptions. An example of that would
    // be `UnexpectedNullException`, which extends `UnexpectedValueException`.
    use KickbackThrowableTrait;
}
?>
