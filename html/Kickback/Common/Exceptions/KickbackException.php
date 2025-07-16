<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions;

use Kickback\Common\Exceptions\IKickbackThrowable;
use Kickback\Common\Exceptions\KickbackThrowable;
use Kickback\Common\Exceptions\KickbackExceptionTrait;

/**
* All Kickback Kingdom non-fatal exceptions should (indirectly) implement this.
*
* This is useful for quickly defining new types of exceptions,
* especially new broad classifications of exceptions.
* (Though, if you're not defining a broad class of exceptions,
* do try to extend something more specific.)
*
* Example usage:
*   <code>
*   <?php
*   declare(strict_types=1);
*   namespace ((your namespace here));
*
*   use Kickback\Common\Exceptions\IKickbackException;
*   use Kickback\Common\Exceptions\KickbackException;
*
*   interface ITestException extends IKickbackException {}
*   class TestException extends KickbackException implements ITestException {}
*   ?>
*   </code>
*
* When defining an exception, and if it's appropriate to inherit from an
* exception already available
* in {@link https://www.php.net/manual/en/reserved.exceptions.php PHP's pre-defined exceptions}
* or in {@link https://www.php.net/manual/en/spl.exceptions.php SPL's exceptions},
* then the recommendation is to have the new exception class extend from
* the PHP (or 3rd party) class, then have the interface extend from
* IKickbackException. KickbackThrowableTrait can be used to simplify
* implementation of commonly needed methods.
*
* Example usage:
*   <code>
*   <?php
*   declare(strict_types=1);
*   namespace ((your namespace here));
*
*   use Kickback\Common\Exceptions\IKickbackException;
*   use Kickback\Common\Exceptions\KickbackThrowableTrait;
*
*   interface ITestException extends IKickbackException {}
*
*   class TestException extends KickbackException implements ITestException {
*       use KickbackThrowableTrait;
*   }
*   ?>
*   </code>
*
* @see Fatal\KickbackFatalError
*/
interface IKickbackException extends IKickbackThrowable {}

// Notable jank consequence of PHP's predefined exception hierarchy:
// IKickbackThrowable actually extends \Extension, because \Throwable
// is an interface and can't be extends.
//
// On the upside: this means we can extends from IKickbackThrowable
// and ALSO extend \Exception _at the same time_!
//
// None of it really matters though, because concrete exception classes
// are only really useful for callee-side where we need to be able to
// construct an object to throw. As far as ontology/taxonomy/tagging goes,
// interfaces are where it's at, because they can define DAWGs where
// an exception can be in multiple categories (more like a tag).
// Catch-clauses should always be working with interfaces due to that
// versatility.
//

/**
* Concrete counterpart of \Kickback\Common\Exceptions\IKickbackException.
*
* Along with IKickbackException, this is useful for quickly defining
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
*   use Kickback\Common\Exceptions\IKickbackException;
*   use Kickback\Common\Exceptions\KickbackException;
*
*   interface ITestException extends IKickbackException {}
*   class TestException extends KickbackException implements ITestException {}
*   ?>
*   </code>
*
* @see IKickbackException
*/
class KickbackException extends KickbackThrowable implements IKickbackException
{
    use KickbackExceptionTrait;
}
?>
