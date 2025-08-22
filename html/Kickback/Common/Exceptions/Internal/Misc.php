<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions\Internal;

use Kickback\Common\Traits\StaticClassTrait;

/**
* @phpstan-import-type  kkdebug_frame_a               from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
* @phpstan-import-type  kkdebug_backtrace_a           from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
* @phpstan-import-type  kkdebug_frame_paranoid_a      from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
* @phpstan-import-type  kkdebug_backtrace_paranoid_a  from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
*
* @internal
*/
final class Misc
{
    use StaticClassTrait;

    /**
    * @phpstan-pure
    * @throws void
    */
    public static function calculate_message_line_prefix(
        ?string $path,  ?int $line
    ) : string
    {
        if ( isset($path) && 0 < \strlen($path) ) {
            $sep_pos = \strrpos($path, '/');
            if ( $sep_pos !== false ) {
                $basename = \substr($path, $sep_pos+1);
            } else {
                $basename = $path;
            }
        } else {
            $basename = null;
        }

        $line_str = ((isset($line) && $line !== 0) ? \strval($line) : null);
        if ( isset($basename) && isset($line_str) ) {
            $loc_full = "$basename($line_str): ";
        } else
        if ( isset($basename) /* && !isset($line_str) */ ) {
            $loc_full = "$basename: ";
        } else
        if ( /*!isset($basename) && */ isset($line_str) ) {
            $loc_full = "($line_str): ";
        } else
        { // !isset($basename) && !isset($line_str)
            $loc_full = '';
        }

        return $loc_full;
    }

    // TODO: Delete after git commit
    // public final function msg(string|\Closure $msg, string|int $in_file_or_at_stack_depth = 0, int $at_line = 0) : void
    // {
    //     // Optimization:
    //     // We only care about file+line from the caller, so we can
    //     // avoid taking too much memory/time with debug_backtrace
    //     // by asking it to leave arguments out, and to only grab
    //     // the frames that we need.
    //     // (Also, if the caller provides us with file+line info,
    //     // we can avoid calling \debug_backtrace entirely.)
    //     // @var kkdebug_backtrace_a
    //     $trace = null;
    //     if ( \is_int($in_file_or_at_stack_depth) )
    //     {
    //         // Capture desired frame from \debug_backtrace, if it is needed.
    //         $stack_depth = 2 + $in_file_or_at_stack_depth;
    //         $trace = \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $stack_depth);
    //         $frame = $trace[$stack_depth-1];
    //         $path = \array_key_exists('file',$frame) ? $frame['file'] : '';
    //         $line = \array_key_exists('line',$frame) ? $frame['line'] : 0;
    //         if ( $at_line !== 0 ) {
    //             // Caller wishes to determine file dynamically,
    //             // but override the line number with something specific.
    //             $line = $at_line;
    //         }
    //     }
    //     else
    //     {
    //         // Explicitly provided file+line.
    //         $path = $in_file_or_at_stack_depth;
    //         $line = $at_line;
    //     }
    // }
    //
    // public static function process_location_info(
    //     ThrowableWithAssignableFields  $exc,
    //     ?array                         $trace,
    //     string|int                     $in_file_or_at_stack_depth,
    //     int                            $at_line = 0)
    // : void
    // {
    //     if ( is_int($in_file_or_at_stack_depth) ) {
    //         // Update by \debug_backtrace.
    //         assert(isset($trace));
    //         assert($in_file_or_at_stack_depth <= \count($trace));
    //         $frame = $trace[\count($trace)-1];
    //         $path = \array_key_exists('file',$frame) ? $frame['file'] : '{unknown file}';
    //         $line = \array_key_exists('line',$frame) ? $frame['line'] : 0;
    //         if ( $at_line !== 0 ) {
    //             // Caller wishes to determine file dynamically,
    //             // but override the line number with something specific.
    //             $line = $at_line;
    //         }
    //     } else {
    //         // Update using arguments.
    //         assert(!isset($trace));
    //         assert(is_string($in_file_or_at_stack_depth)); // phpstan-ignore function.alreadyNarrowedType, function.alreadyNarrowedType
    //         $path = $in_file_or_at_stack_depth;
    //         $line = $at_line;
    //     }
    // }

    /**
    * @param      kkdebug_backtrace_paranoid_a      $trace
    * @param      string|int                        $in_file_or_at_stack_depth
    * @param      ?string                           $in_function
    * @param      int                               $at_line
    * @param      ?string                           $path
    * @param-out  string                            $path
    * @param      ?string                           $func
    * @param-out  ($in_function is null ? (?string) : string
    *             )                                 $func
    * @param      ?int                              $line
    * @param-out  int                               $line
    *
    * @phpstan-impure
    * @throws void
    */
    public static function process_location_info_into(
        ?array                         $trace,
        string|int                     $in_file_or_at_stack_depth,
        ?string                        $in_function,
        int                            $at_line,
        ?string                        &$path,
        ?string                        &$func,
        ?int                           &$line
    ) : void
    {
        if (isset($trace)) {
            // Process results of \debug_backtrace, if it was needed.
            assert(\is_int($in_file_or_at_stack_depth));
            assert($in_file_or_at_stack_depth <= \count($trace));
            $frame = $trace[\count($trace)-1];
            $path = \array_key_exists('file',    $frame) ? $frame['file']     : '{unknown file}';
            $func = \array_key_exists('function',$frame) ? $frame['function'] : '{unknown function}';
            $line = \array_key_exists('line',    $frame) ? $frame['line']     : 0;
            if (isset($in_function)) {
                // Caller wishes to determine file dynamically,
                // but override the function name with something specific.
                $func = $in_function;
            }
            if ( $at_line !== 0 ) {
                // Caller wishes to determine file dynamically,
                // but override the line number with something specific.
                $line = $at_line;
            }
        } else
        if (\is_string($in_file_or_at_stack_depth)) {
            // If the caller provided a file+line, then
            // we didn't need to call \debug_backtrace.
            // We'll just assign those here.
            assert(\is_string($in_file_or_at_stack_depth)); // @phpstan-ignore function.alreadyNarrowedType, function.alreadyNarrowedType
            $path = $in_file_or_at_stack_depth; // file name/path
            $func = $in_function;
            $line = $at_line;
            // Note: `if (is_int($in_file_or_at_stack_depth)) {...}` is handled
            // by the \debug_backtrace case, because the integer version
            // specifies a stack depth to query with \debug_backtrace.
        }
    }
}
?>
