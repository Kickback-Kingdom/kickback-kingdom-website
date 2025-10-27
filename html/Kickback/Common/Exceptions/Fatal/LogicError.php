<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions\Fatal;

use Kickback\Common\Exceptions\Fatal\IKickbackFatalError;
use Kickback\Common\Exceptions\Fatal\KickbackFatalError;

/**
* Errors caused by bugs in the code's internal logic.
*
* This is a very common class of fatal error.
*
* Any time one of these is thrown, it should (eventually) lead to a bugfix.
*/
interface ILogicError extends IKickbackFatalError {}
class LogicError extends KickbackFatalError implements ILogicError {}
?>
