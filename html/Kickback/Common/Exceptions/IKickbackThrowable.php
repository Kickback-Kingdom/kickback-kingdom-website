<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions;

use Kickback\Common\Exceptions\Internal\IKickbackPHPThrowable;
use Kickback\Common\Exceptions\ThrowableWithContextMessages;

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
* @see IKickbackException
*
* @extends IKickbackPHPThrowable<\Throwable>
* @phpstan-import-type  kkdebug_frame_a      from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
* @phpstan-import-type  kkdebug_backtrace_a  from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
*/
interface IKickbackThrowable extends IKickbackPHPThrowable, ThrowableWithContextMessages
{
}
?>
