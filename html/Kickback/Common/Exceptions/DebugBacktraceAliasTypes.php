<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions;

/**
* @phpstan-type  kkdebug_frame_paranoid_a  array{
*       function? : string,
*       line?     : int,
*       file?     : string,
*       class?    : class-string,
*       type?     : '->'|'::',
*       args?     : array<array-key, mixed>,
*       object?   : object
*   }
*
* @phpstan-type  kkdebug_frame_a  array{
*       function  : string,
*       line?     : int,
*       file?     : string,
*       class?    : class-string,
*       type?     : '->'|'::',
*       args?     : array<array-key, mixed>,
*       object?   : object
*   }
*
* @phpstan-type  kkdebug_backtrace_paranoid_a  array<int, kkdebug_frame_paranoid_a>
* @phpstan-type  kkdebug_backtrace_a           array<int, kkdebug_frame_a>
*/
interface DebugBacktraceAliasTypes {}
?>
