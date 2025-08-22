<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions;

use Kickback\Common\Exceptions\Internal\DefaultMethods;
use Kickback\Common\Exceptions\ThrowableAssignableFieldsTrait;

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
    use ThrowableAssignableFieldsTrait;

    /** @param TThrowable $previous */
    public function __construct(?string $message = null, int $code = 0, ?\Throwable $previous = null)
    {
        if (!isset($message)) {
            $message = '';
        }
        parent::__construct($message, $code, $previous);
        $this->ThrowableAssignableFieldsTrait_init($message, $this->getFile(), $this->getLine());
    }

    public function __toString() : string
    {
        return DefaultMethods::toString($this);
    }
}
?>
