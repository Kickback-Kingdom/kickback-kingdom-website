<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions;

use Kickback\Common\Exceptions\Internal\DefaultMethods;

// See KickbackThrowable for an explanation of the origins and intent of this code.
/**
* Assists with defining exceptions/errors when extending PHP or 3rd party exceptions.
*
* In almost all cases, you will want to use KickbackExceptionTrait
* or Fatal\KickbackFatalErrorTrait instead.
*
* @template TThrowable of \Throwable
*
* @phpstan-require-implements  \Kickback\Common\Exceptions\IKickbackThrowable
*
* @see KickbackThrowable
*/
trait KickbackThrowableTrait
{
    //protected string    $message = 'Unknown exception';     // Exception message
    //private   string     $string;                            // Unknown
    //protected int        $code    = 0;                       // User-defined exception code
    //protected string     $file;                              // Source filename of exception
    //protected int        $line;                              // Source line of exception
    /// @var (mixed[])[]
    //private   array      $trace;                             // Unknown
    //private   ?Throwable $previous = null;

    /** @param TThrowable $previous */
    public function __construct(?string $message = null, int $code = 0, ?\Throwable $previous = null)
    {
        if (!isset($message)) {
            $message = '';
        }
        parent::__construct($message, $code, $previous);
    }

    public function __toString() : string
    {
        return DefaultMethods::toString($this);
    }
}
?>
