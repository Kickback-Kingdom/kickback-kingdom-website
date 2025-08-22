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
