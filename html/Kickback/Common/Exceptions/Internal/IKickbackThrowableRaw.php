<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions\Internal;

use Kickback\Common\Exceptions\ThrowableWithAssignableFields;

//use Kickback\Common\Exceptions\Internal\MockPHPException;
/**
* Alternative to \Throwable for classes that can't extend \Exception|\Error.
*
* This is important for MockPHPException to live in the same ancestry
* as things like KickbackThrowable, so that tests can work on Throwables
* that have the same type signatures as non-mock exceptions.
*
* This class lacks any corresponding concrete implementation
* (e.g. there is no `KickbackThrowableRaw` class) because this interface
* is slightly too generic to have an implementation.
*
* As of this writing, any exception-like class must
* choose between two possible strategies:
*  * Inherit from PHP's `\Exception` class
*        (to be able to throw and catch exceptions)
*  * Inherit from a mock class like `MockPHPException`
*        (to be able to freely manipulate internals like file, line,
*        message text, backtrace, and so on, at the expense of being
*        unable to throw and catch).
*
* Notably, the latter tradeoff works whenever testing doesn't involve
* try/catch statements. That's because most of an exception's remaining
* functionality are within its `__toString()` function, which can be called
* without using try and catch statements. (Likewise for `getTraceAsString()`.)
*
* This `IKickbackThrowableRaw` is an ancestor class to either of the above
* two possibilities, so there might not be any reason to have a concrete
* implementation of something that doesn't fulfill one of those two
* use-cases. Also, those use-cases are already implemented as
* `KickbackThrowable` and `MockPHPException`, respectively.
*
* @see IKickbackPHPThrowable
* @see IMockPHPException
* @internal
* @phpstan-import-type  kkdebug_frame_a      from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
* @phpstan-import-type  kkdebug_backtrace_a  from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
*/
interface IKickbackThrowableRaw extends ThrowableWithAssignableFields
{
    // Credit:
    // This interface was taken from a comment written by `ask at nilpo dot com`
    // (ask@nilpo.com) on the PHP Exceptions documentation page:
    // https://www.php.net/manual/en/language.exceptions.php#91159

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

    public function getPrevious() : \Throwable|self|null;
    /** @return int */
    public function getCode();           // User-defined Exception code
    public function getFile() : string;  // Source filename
    public function getLine() : int;     // Source line
    /** @return kkdebug_backtrace_a */
    public function getTrace() : array;
    public function getTraceAsString() : string;           // Formated string of trace

    /* Overrideable methods inherited from Throwable class */
    /**
    * @phpstan-pure
    * @throws void
    */
    public function __toString() : string;                 // formated string for display

    public function __construct(?string $message = null, int $code = 0);
}
?>
