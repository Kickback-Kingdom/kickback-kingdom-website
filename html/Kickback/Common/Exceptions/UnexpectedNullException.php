<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions;

use Kickback\Common\Exceptions\IKickbackException;
use Kickback\Common\Exceptions\KickbackThrowableTrait;

interface IUnexpectedNullException extends IKickbackException {}

class UnexpectedNullException extends \UnexpectedValueException implements IUnexpectedNullException
{
    use KickbackThrowableTrait;
}
?>
