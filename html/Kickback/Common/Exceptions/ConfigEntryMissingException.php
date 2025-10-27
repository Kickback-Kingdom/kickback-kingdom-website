<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions;

use Kickback\Common\Exceptions\IKickbackException;
use Kickback\Common\Exceptions\KickbackException;

interface IConfigEntryMissingException extends IKickbackException {}
class ConfigEntryMissingException extends KickbackException implements IConfigEntryMissingException {}
?>
