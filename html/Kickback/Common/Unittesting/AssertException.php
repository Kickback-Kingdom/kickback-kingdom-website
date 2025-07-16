<?php
declare(strict_types=1);

namespace Kickback\Common\Unittesting;

use Kickback\Common\Exceptions\IKickbackException;
use Kickback\Common\Exceptions\KickbackException;

interface IAssertException extends IKickbackException {}
class AssertException extends KickbackException implements IAssertException {}
?>
