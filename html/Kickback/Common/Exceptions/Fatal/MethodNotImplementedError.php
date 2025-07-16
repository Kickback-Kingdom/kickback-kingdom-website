<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions\Fatal;

use Kickback\Common\Exceptions\Fatal\INotImplementedError;
use Kickback\Common\Exceptions\Fatal\NotImplementedError;

interface IMethodNotImplementedError extends INotImplementedError {}
class MethodNotImplementedError extends NotImplementedError implements IMethodNotImplementedError {}
?>
