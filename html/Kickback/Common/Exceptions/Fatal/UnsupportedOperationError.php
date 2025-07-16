<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions\Fatal;

use Kickback\Common\Exceptions\Fatal\ILogicError;
use Kickback\Common\Exceptions\Fatal\LogicError;

interface IUnsupportedOperationError extends ILogicError {}
class UnsupportedOperationError extends LogicError implements IUnsupportedOperationError {}
?>
