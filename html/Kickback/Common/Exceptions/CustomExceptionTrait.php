<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions;

/**
* See `CustomException` for an explanation of the origins and intent of this code.
*/
trait CustomExceptionTrait
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
