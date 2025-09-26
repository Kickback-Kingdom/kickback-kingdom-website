<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions;

use Kickback\Common\Exceptions\Internal\IMockPHPException;
use Kickback\Common\Exceptions\Internal\MockPHPException;
use Kickback\Common\Exceptions\ThrowableContextMessageHandlingTrait;
use Kickback\Common\Exceptions\ThrowableWithContextMessages;

interface IMockException extends IMockPHPException, ThrowableWithContextMessages {}
class MockException extends MockPHPException implements IMockException
{
    use ThrowableContextMessageHandlingTrait;
}
?>
