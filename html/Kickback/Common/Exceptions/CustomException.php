<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions;

use Kickback\Common\Exceptions\CustomExceptionTrait;
use Kickback\Common\Exceptions\IException;


/** This class was taken from a comment written by `ask at nilpo dot com`
*   (ask\@nilpo.com) on the PHP Exceptions documentation page:
*   https://www.php.net/manual/en/language.exceptions.php#91159
*
*   The contents of the class have been moved into `CustomExceptionTrait`
*   to make it easier to do the same thing for exceptions that should
*   extend PHP's built-in (or SPL) exceptions. An example of that would
*   be `UnexpectedNullException`, which extends `UnexpectedValueException`.
*
*   It allows for very concise definitions of new exception types.
*
*   This is helpful when we want to define exceptions that don't need any
*   features beyond what the normal \Exception class provides, yet we still
*   want our exceptions to be distinct types so that `catch` clauses can
*   distinguish them from other exceptions.
*
*   Example usage:
*   <code>
*   <?php
*   class TestException extends CustomException {}
*   ?>
*   </code>
*/
abstract class CustomException extends \Exception implements IException
{
    use CustomExceptionTrait;
}
?>
