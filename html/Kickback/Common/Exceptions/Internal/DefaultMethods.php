<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions\Internal;

use Kickback\Common\Traits\StaticClassTrait;

use Kickback\Common\Exceptions\Internal\IKickbackThrowableRaw;

/**
* @phpstan-import-type  kkdebug_frame_a               from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
* @phpstan-import-type  kkdebug_backtrace_a           from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
* @phpstan-import-type  kkdebug_frame_paranoid_a      from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
* @phpstan-import-type  kkdebug_backtrace_paranoid_a  from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
*
* @internal
*/
final class DefaultMethods
{
    use StaticClassTrait;

    // TODO: Where would it print \Throwable->getCode()? Does it print that at all? (untested)

    // Some notes about `Exception::__toString()`
    // and how exceptions are printed by PHP:
    //
    // These strings:
    //   "PHP Fatal error:  Uncaught "
    //   "\n  thrown in $file on line $line"
    //
    // ... are printed by PHP and do not come from `__toString()`.
    // Removing those parts of the output is not possible here,
    // but might be doable with the help of a function
    // like `set_exception_handler` (if ever needed/desired).
    //
    // Note that it IS possible to potentially emit text BEFORE the
    // `'PHP Fatal error:  Uncaught '`, by echoing/logging the text
    // within the `__toString()` method. This will cause it to be printed
    // before PHP prints everything else. This is not recommended,
    // however, because it is fragile:
    // * There's no guarantee that the `__toString()` results were even
    //     going to end up printed, so it could end up printing one-but-not-the-other.
    // * It could be tricky or impossible to know what stream/output the
    //     exception is being printed to. So whatever echo/log command
    //     is used can easily output the text to the wrong place.
    //
    // Meanwhile, all of the
    // "$throwable_class_fqn $message in $file($line)\n"
    // "#0 $trace[0]['file']($trace[0]['line']): $trace[0]['class']::$trace[0]['function']()\n"
    // "#1 $trace[1]['file']($trace[1]['line']): $trace[1]['class']::$trace[1]['function']()\n"
    // ...
    // "#N $trace[N]['file']($trace[N]['line']): $trace[N]['class']::$trace[N]['function']()\n"
    //
    // ...content is from __toString().
    //
    // Here's PHP's exception format in total, as printed to CLI:
    //   PHP Fatal error:  Uncaught $throwable_class_fqn $message in $file($line)\n
    //   #0 $trace[0]['file']($trace[0]['line']): $trace[0]['class']::$trace[0]['function']()\n
    //   #1 $trace[1]['file']($trace[1]['line']): $trace[1]['class']::$trace[1]['function']()\n
    //   ...
    //   #N $trace[N]['file']($trace[N]['line']): $trace[N]['class']::$trace[N]['function']()\n
    //     thrown in $file on line $line\n
    //
    // Example (simple):
    //   PHP Fatal error:  Uncaught Kickback\Common\Exceptions\KickbackException 'hello world!' in /var/www/localhost/kickback-kingdom-website/html/scratch-pad/test.php(752)
    //     thrown in /var/www/localhost/kickback-kingdom-website/html/scratch-pad/test.php on line 754
    //
    // Example (more complicated):
    // ```
    // PHP Fatal error:  Uncaught Error: Failed opening required '/var/www/localhost/kickback-kingdom-website/html/Kickback/InitializationScripts/../../Kickback/Common/FunctionCapture/Internal/FunctionCapture.php' (include_path='.:/usr/share/php8:/usr/share/php') in /var/www/localhost/kickback-kingdom-website/html/Kickback/InitializationScripts/autoload_classes.php:1445
    // Stack trace:
    // #0 /var/www/localhost/kickback-kingdom-website/html/Kickback/InitializationScripts/autoload_classes.php(1484): Kickback\InitializationScripts\generic_autoload_function_impl()
    // #1 /var/www/localhost/kickback-kingdom-website/html/Kickback/InitializationScripts/autoload_classes.php(1744): Kickback\InitializationScripts\generic_autoload_function()
    // #2 /var/www/localhost/kickback-kingdom-website/html/Kickback/InitializationScripts/autoload_classes.php(1780): Kickback\InitializationScripts\autoload_function_impl()
    // #3 /var/www/localhost/kickback-kingdom-website/html/Kickback/Common/UnittestEntryPoint.php(26): Kickback\InitializationScripts\autoload_function()
    // #4 /var/www/localhost/kickback-kingdom-website/html/Kickback/UnittestEntryPoint.php(85): Kickback\Common\UnittestEntryPoint::unittests()
    // #5 /var/www/localhost/kickback-kingdom-website/html/scratch-pad/unittest.php(825): Kickback\UnittestEntryPoint::unittests()
    // #6 {main}
    //   thrown in /var/www/localhost/kickback-kingdom-website/html/Kickback/InitializationScripts/autoload_classes.php on line 1445
    // ```
    // (This last example, notably, was likely emitted by a `require`
    // statement and not by a thrown exception.)
    //
    // On the web, it does seem to put <br> instead of (or in addition to)
    // the "\n" newline characters.
    //
    // (End of notes about PHP Exception printing)

    public static function getTraceAsString_inplace(IKickbackThrowableRaw $exc, string &$strbuf) : void
    {
        $trace = $exc->getTrace();
        $len = \count($trace);
        for($i = 0; $i < $len; $i++) {
            $frame = $trace[$i];
            $file = \array_key_exists('file',$frame) ? $frame['file'] : '{unknown file}';
            $line = \array_key_exists('line',$frame) ? '('.\strval($frame['line']).')' : '';
            $class_fqn = \array_key_exists('class',$frame) ? $frame['class'] : '';
            $func = $frame['function'];
            $type = \array_key_exists('type',$frame) ? $frame['type'] : '';
            if ( 0 === \strlen($class_fqn) ) { $type = ''; }
        }
    }

    public static function getTraceAsString(IKickbackThrowableRaw $exc) : string
    {
        $strbuf = '';
        self::getTraceAsString_inplace($exc, $strbuf);
        return $strbuf;
    }

    public static function toString(IKickbackThrowableRaw $exc, ?string $mock_class_fqn = null, ?string $msg_override = null) : string
    {
        if (isset($mock_class_fqn)) {
            $class_fqn = $mock_class_fqn;
        } else {
            $class_fqn = \get_class($exc);
        }

        // Optimization: The `$msg_override` field allows us to avoid
        // calling $exc->message() more than once if the caller _also_
        // needed to call it already. This is especially notable when
        // $exc has a `message()` value that is a string-returning-closure,
        // because each call to $exc->message() may call that closure
        // an additional time. If either `->__toString()` or `->message()`
        // ever guarantee that the $msg closure will never be called more
        // than once, then the $msg_override argument, and the below
        // if-else code, are essential to providing such a guarantee.
        if ( isset($msg_override) ) {
            $message = $msg_override;
        } else {
            $message = $exc->message();
        }

        $file = $exc->file();
        $line = \strval($exc->line());
        $trace = $exc->getTraceAsString();
        return $class_fqn
            . " $message in $file($line)\n"
            . "$trace";
    }

    public const UNKNOWN_FUNCTION_NAME = '{unknown function}';

    /**
    * @param  kkdebug_frame_paranoid_a  $frame
    */
    private static function file_line_mismatch_info(\Throwable $exc, array $frame) : string
    {
        $getFile = $exc->getFile();
        $getLine = $exc->getLine();
        $frameFile = \array_key_exists('file', $frame) ? $frame['file'] : '{unknown file}';
        $frameLine = \array_key_exists('line', $frame) ? $frame['line'] : 0;
        return "\n".
            "exc->getFile and exc->getLine:   $getFile($getLine)\n".
            "frame['file'] and frame['line']: $frameFile($frameLine)\n";
    }

    /**
    * Attempt to determine the function name that would correspond to `getFile` and `getLine`.
    * @param  kkdebug_backtrace_paranoid_a  $trace
    */
    public static function getFunc(
        \Throwable $exc,
        array      $trace
    ) : string
    {
        // $exc->getTrace() was returning the caller's caller's frame instead of the caller's frame.
        //$trace = $exc->getTrace();
        if ( 0 === \count($trace) ) {
            return self::UNKNOWN_FUNCTION_NAME;
        }

        $frame = $trace[0];
        if ( \array_key_exists('function', $frame) ) {
            assert(!\array_key_exists('file', $frame) || $frame['file'] === $exc->getFile(), self::file_line_mismatch_info($exc, $frame));
            assert(!\array_key_exists('line', $frame) || $frame['line'] === $exc->getLine(), self::file_line_mismatch_info($exc, $frame));
            return $frame['function'];
        } else {
            return self::UNKNOWN_FUNCTION_NAME;
        }
    }
}
?>
