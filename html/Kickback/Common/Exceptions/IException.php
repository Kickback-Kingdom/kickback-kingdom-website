<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions;

/// This interface was taken from a comment written by `ask at nilpo dot com`
/// (ask@nilpo.com) on the PHP Exceptions documentation page:
/// https://www.php.net/manual/en/language.exceptions.php#91159
///
/// This supports the CustomException class.
/// See that file for more details, such as purpose and usage example(s).
///
interface IException
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
    //   PHP Fatal error:  Declaration of Exception::getCode() must be compatible with Kickback\Utility\IException::getCode(): int in /var/www/localhost/kickback-kingdom-website/html/Kickback/Utility/CustomException.php on line 0
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

    /* Protected methods inherited from Exception class */
    public function getMessage() : string;
    public function getPrevious() : ?\Throwable;
    /** @return int */
    public function getCode();           // User-defined Exception code
    public function getFile() : string;  // Source filename
    public function getLine() : int;     // Source line
    /** @return (mixed[])[] */
    public function getTrace() : array;
    public function getTraceAsString() : string;           // Formated string of trace

    /* Overrideable methods inherited from Exception class */
    public function __toString() : string;                 // formated string for display
    public function __construct(?string $message = null, int $code = 0, ?\Throwable $previous = null);
}
?>
