<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions;

use Kickback\Common\Exceptions\CustomExceptionTrait;
use Kickback\Common\Exceptions\IException;

class UnexpectedNullException extends \UnexpectedValueException implements IException
{
    use CustomExceptionTrait;
}
?>
