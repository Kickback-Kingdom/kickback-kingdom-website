<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions;

use Kickback\Common\Exceptions\IKickbackUnexpectedValueException;
use Kickback\Common\Exceptions\KickbackUnexpectedValueException;

interface IUnexpectedTypeException extends IKickbackUnexpectedValueException {}
class UnexpectedTypeException extends KickbackUnexpectedValueException implements IUnexpectedTypeException {}
?>
