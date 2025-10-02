<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions;

use Kickback\Common\Exceptions\IKickbackException;
use Kickback\Common\Exceptions\KickbackException;

/**
* This is thrown when a record fails one or more validation checks.
*/
interface IValidationException extends IKickbackException {}

/**
* @see IValidationException
*/
class ValidationException extends KickbackException implements IValidationException {}
?>
