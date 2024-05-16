<?php
declare(strict_types=1);

namespace Kickback\Utility;

//use \Kickback\Utility\IException;


/** This class was taken from a comment written by `ask at nilpo dot com`
*   (ask\@nilpo.com) on the PHP Exceptions documentation page:
*   https://www.php.net/manual/en/language.exceptions.php#91159
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
    //protected string    $message = 'Unknown exception';     // Exception message
    //private   string     $string;                            // Unknown
    //protected int        $code    = 0;                       // User-defined exception code
    //protected string     $file;                              // Source filename of exception
    //protected int        $line;                              // Source line of exception
    /// @var (mixed[])[]
    //private   array      $trace;                             // Unknown
    //private   ?Throwable $previous = null;


    public function __construct(?string $message = null, int $code = 0, ?\Throwable $previous = null)
    {
        if (!isset($message)) {
            throw new $this('Unknown '. get_class($this));
        }
        parent::__construct($message, $code, $previous);
    }

    public function __toString()
    {
        return get_class($this)
            . " '{$this->message}' in {$this->file}({$this->line})\n"
            . "{$this->getTraceAsString()}";
    }
}
?>
