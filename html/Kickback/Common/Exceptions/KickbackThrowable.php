<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions;

// use Kickback\Common\Exceptions\ThrowableWithContextMessages; // Indirect dependency via IKickbackThrowable
use Kickback\Common\Exceptions\ThrowableContextMessageHandlingTrait;
use Kickback\Common\Exceptions\IKickbackThrowable;

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
    /** @use KickbackThrowableTrait<\Throwable> */
    use KickbackThrowableTrait;
    use ThrowableContextMessageHandlingTrait {
        ThrowableContextMessageHandlingTrait::__toString insteadof KickbackThrowableTrait;
    }
}
?>
