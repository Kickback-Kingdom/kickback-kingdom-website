<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions\Internal;

use Kickback\Common\Exceptions\Internal\IKickbackThrowableRaw;

/**
* This class currently lacks a concrete implementation because it is purely
* used for type-hinting and type-narrowing when using PHPStan, so there is
* no run-time use-case for this. For common implementations, see these classes:
* `KickbackThrowable`, `KickbackException`, and `KickbackFatalError`.
*
* @template TThrowable of \Throwable
* @internal
* @phpstan-import-type  kkdebug_frame_a      from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
* @phpstan-import-type  kkdebug_backtrace_a  from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
*/
interface IKickbackPHPThrowable extends IKickbackThrowableRaw, \Throwable
{
    /** @return TThrowable */
    public function getPrevious() : ?\Throwable;

    /** @param ?TThrowable $previous*/
    public function __construct(?string $message = null, int $code = 0, ?\Throwable $previous = null);
}

?>
