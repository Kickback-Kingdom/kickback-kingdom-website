<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions;

use Kickback\Common\Exceptions\IKickbackUnexpectedValueException;
use Kickback\Common\Exceptions\KickbackUnexpectedValueException;

interface IUnexpectedEmptyException extends IKickbackUnexpectedValueException {}
class UnexpectedEmptyException extends KickbackUnexpectedValueException implements IUnexpectedEmptyException {}
?>
