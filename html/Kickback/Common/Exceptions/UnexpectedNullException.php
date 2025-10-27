<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions;

use Kickback\Common\Exceptions\IKickbackUnexpectedValueException;
use Kickback\Common\Exceptions\KickbackUnexpectedValueException;

interface IUnexpectedNullException extends IKickbackUnexpectedValueException {}
class UnexpectedNullException extends KickbackUnexpectedValueException implements IUnexpectedNullException {}
?>
