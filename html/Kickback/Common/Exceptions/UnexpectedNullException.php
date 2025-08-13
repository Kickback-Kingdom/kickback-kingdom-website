<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions;

use Kickback\Common\Exceptions\IKickbackException;
use Kickback\Common\Exceptions\KickbackThrowableTrait;

use Kickback\Common\Exceptions\ThrowableWithContextMessages;
use Kickback\Common\Exceptions\ThrowableContextMessageHandlingTrait;

interface IUnexpectedNullException extends IKickbackException {}

class UnexpectedNullException extends \UnexpectedValueException implements IUnexpectedNullException
{
    /** @use KickbackThrowableTrait<\Throwable> */
    use KickbackThrowableTrait;
    use ThrowableContextMessageHandlingTrait {
        ThrowableContextMessageHandlingTrait::__toString insteadof KickbackThrowableTrait;
    }
}
?>
