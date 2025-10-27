<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions;

use Kickback\Common\Exceptions\IKickbackException;
use Kickback\Common\Exceptions\KickbackException;

interface IExtensionMissingException extends IKickbackException {}
class ExtensionMissingException extends KickbackException implements IExtensionMissingException {}
?>
