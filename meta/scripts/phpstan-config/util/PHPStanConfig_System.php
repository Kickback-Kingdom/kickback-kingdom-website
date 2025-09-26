<?php
declare(strict_types=1);

class PHPStanConfig_System
{
    public  static bool $do_unittesting = false;
    private static bool $do_debug_msg = false;

    public static function phpstan_running() : bool {
        static $phpstan_running_ = null;
        if (isset($phpstan_running_)) {
            return $phpstan_running_;
        }
        $phpstan_running_ = false;
        if (\defined('Kickback\InitializationScripts\PARENT_PROCESS_TYPE')
        &&  Kickback\InitializationScripts\PARENT_PROCESS_TYPE === 'PHPSTAN') {
            $phpstan_running_ = true;
        }
        $PHPSTAN_RUNNING = \getenv('PHPSTAN_RUNNING');
        if ($PHPSTAN_RUNNING !== false) {
            $phpstan_running_ = true;
        }
        return $phpstan_running_;
    }

    public static function echo_msg(string $msg) : bool
    {
        if (self::phpstan_running()) {
            return true;
        }
        fwrite(STDERR,$msg);
        return true;
    }

    public static function debug_msg(string $msg) : bool
    {
        if (self::phpstan_running()) {
            return true;
        }
        if (!self::$do_debug_msg) {
            return true;
        }
        $trace = \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $line = \array_key_exists('line',$trace[0]) ? \strval($trace[0]['line']) : '0';
        if (1 < \count($trace)) {
            $func = $trace[1]['function'];
            fwrite(STDERR, "$func, $line: $msg\n");
            return true;
        }
        fwrite(STDERR, __FILE__.", $line: $msg\n");
        return true;
    }
}