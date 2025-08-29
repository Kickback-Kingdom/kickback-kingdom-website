<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions;

use Kickback\Common\Exceptions\IKickbackException;
use Kickback\Common\Exceptions\KickbackException;

interface JSON_IDecodeException extends IKickbackException {}
class JSON_DecodeException extends KickbackException implements JSON_IDecodeException {}
?>
