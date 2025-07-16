<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions\Fatal;

use Kickback\Common\Version;
use Kickback\Common\Exceptions\KickbackThrowableTrait;

/**
* Assists with defining errors when extending PHP or 3rd party exceptions.
*
* @see KickbackFatalError
*/
trait KickbackFatalErrorTrait
{
    use KickbackThrowableTrait;

    /**
    * Should fatal errors should crash or throw?
    *
    * @see KickbackFatalError::should_crash_on_fatal_errors
    * @see KickbackFatalError::throw_or_crash
    *
    */
    public static function should_crash_on_fatal_errors() : bool
    {
        // @phpstan-ignore identical.alwaysFalse, booleanAnd.alwaysFalse
        if ( Version::in_debug_mode() && \Kickback\InitializationScripts\PARENT_PROCESS_TYPE === 'CLI' ) {
            return true; // Crash
        } else {
            return false; // Throw
        }
    }

    /**
    * Thrown or halt unconditionally, depending on system|host context.
    *
    * @see KickbackFatalError::should_crash_on_fatal_errors
    * @see KickbackFatalError::throw_or_crash
    *
    * @return never
    */
    public static function static_throw_or_crash(mixed ...$args) : void
    {
        $instance = new (static::class)(...$args);
        $instance->obj_throw_or_crash();
    }

    /**
    * Thrown or halt unconditionally, depending on system|host context.
    *
    * @see KickbackFatalError::should_crash_on_fatal_errors
    * @see KickbackFatalError::throw_or_crash
    *
    * @return never
    */
    private function obj_throw_or_crash() : void
    {
        if ( self::should_crash_on_fatal_errors() ) {
            $this->obj_crash();
        } else {
            throw $this;
        }
    }

    /**
    * Alias of throw_or_crash.
    *
    * @see KickbackFatalError::throw_or_crash
    *
    * @return never
    */
    public static function static_crash_or_throw(mixed ...$args) : void
    {
        $instance = new (static::class)(...$args);
        $instance->obj_crash_or_throw();
    }

    /**
    * Alias of throw_or_crash.
    *
    * @see KickbackFatalError::throw_or_crash
    *
    * @return never
    */
    private function obj_crash_or_throw() : void
    {
        $this->obj_throw_or_crash();
    }

    /**
    * Halt unconditionally, after displaying or logging the error message.
    *
    * @see KickbackFatalError::crash
    *
    * @return never
    */
    public static function static_crash(mixed ...$args) : void
    {
        $instance = new (static::class)(...$args);
        $instance->obj_crash();
    }

    /**
    * Halt unconditionally, after displaying or logging the error message.
    *
    * @see KickbackFatalError::crash
    *
    * @return never
    */
    private function obj_crash() : void
    {
        // TODO: Improve this once we have generalized error reporting capabilities.
        // @phpstan-ignore identical.alwaysFalse
        if ( \Kickback\InitializationScripts\PARENT_PROCESS_TYPE === 'CLI' ) {
            fwrite(STDERR, $this->__toString());
        } else {
            error_log($this->__toString());
        }

        $error_code = $this->getCode();
        if ( $error_code !== 0 )
        {
            // Return a non-zero error code to indicate failure.
            die($error_code); // Alias of `exit($error_code);`
        }
        else
        {
            // A response code of 0 indicates success.
            // This isn't success.
            // We should make sure it's non-zero,
            // or our parent process might do unwise things.
            // '1' is usually the default error code
            // for programs failing with non-specific errors,
            // so that's what we'll use here.
            die(1); // Alias of `exit(1);`
        }
    }

    /**
    * @return never
    */
    public static function __callStatic(string $method_name, array $args) : void
    {
        switch($method_name)
        {
            case 'throw_or_crash':               static::static_throw_or_crash(...$args);
            case 'crash_or_throw':               static::static_crash_or_throw(...$args);
            case 'crash':                        static::static_crash(...$args);
            default:
                // oh god what now?!
                // Maybe this, but move MethodNotImplementedError and MethodNotImplementedError__Thrown into different classes.
                //MethodNotImplementedError::throw_or_crash('');
                $arglist = implode(',',$args);
                throw new \Kickback\Common\Exceptions\KickbackException(
                    'Invalid call to non-existant static method: '.
                    static::class."::$method_name($arglist)");
        }
    }

    /** @return never */
    public function __call(string $method_name, array $args) : void
    {
        switch($method_name)
        {
            case 'throw_or_crash':               $this->obj_throw_or_crash();
            case 'crash_or_throw':               $this->obj_crash_or_throw();
            case 'crash':                        $this->obj_crash();
            default:
                // oh god what now?!
                // Maybe this, but move MethodNotImplementedError and MethodNotImplementedError__Thrown into different classes.
                //MethodNotImplementedError::throw_or_crash('');
                $arglist = implode(',',$args);
                throw new \Kickback\Common\Exceptions\KickbackException(
                    'Invalid call to non-existant method: '.
                    static::class."::$method_name($arglist)");
        }
    }
}
?>
