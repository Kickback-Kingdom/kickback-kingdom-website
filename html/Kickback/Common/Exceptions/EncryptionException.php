<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions;

use Kickback\Common\Exceptions\IKickbackException;
use Kickback\Common\Exceptions\KickbackException;

interface IEncryptionException extends IKickbackException {}
class EncryptionException extends KickbackException implements IEncryptionException {}
?>
