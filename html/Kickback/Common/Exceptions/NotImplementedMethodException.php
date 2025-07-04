<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions;

use Kickback\Common\Exceptions\CustomExceptionTrait;
use Kickback\Common\Exceptions\IException;

class NotImplementedMethodException extends \BadMethodCallException implements IException
{
    use CustomExceptionTrait;
}
?>
