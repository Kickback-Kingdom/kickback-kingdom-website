<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions\Fatal;

use Kickback\Common\Exceptions\Fatal\KickbackFatalErrorTrait;
use Kickback\Common\Exceptions\Fatal\ILogicError;

use Kickback\Common\Exceptions\ThrowableWithContextMessages;
use Kickback\Common\Exceptions\ThrowableContextMessageHandlingTrait;

interface INotImplementedError extends ILogicError {}

class NotImplementedError extends \BadMethodCallException implements INotImplementedError {
    use KickbackFatalErrorTrait;
    use ThrowableContextMessageHandlingTrait {
        ThrowableContextMessageHandlingTrait::__toString insteadof KickbackFatalErrorTrait;
    }
}
?>
