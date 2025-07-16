<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions\Fatal;

use Kickback\Common\Exceptions\Fatal\KickbackFatalErrorTrait;
use Kickback\Common\Exceptions\Fatal\ILogicError;

interface INotImplementedError extends ILogicError {}

class NotImplementedError extends \BadMethodCallException implements INotImplementedError {
    use KickbackFatalErrorTrait;
}
?>
