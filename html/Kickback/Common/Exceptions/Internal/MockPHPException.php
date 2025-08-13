<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions\Internal;

use Kickback\Common\Exceptions\Internal\DefaultMethods;
use Kickback\Common\Exceptions\Internal\IKickbackThrowableRaw;

/**
* Interface representing all accessors that can configure a `MockPHPException`.
*
* This is also purely _just_ the configuration accessors, and none of the
* other Throwable functions. See `IMockPHPException` for a more complete
* interface, or `MockPHPException` for an implementation of said interface.
*
* @see MockPHPException
* @see IMockPHPException
* @internal
* @phpstan-import-type  kkdebug_frame_a      from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
* @phpstan-import-type  kkdebug_backtrace_a  from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
*/
interface IMockPHPException__ConfigAccessors
{
    public function                file(?string $new_value = null) : string;
    public function                line(?int    $new_value = null) : int;
    public function                code(?int    $new_value = null) : int;
    public function caller_context_file(?string $new_value = null) : string;
    public function caller_context_line(?int    $new_value = null) : int;
    public function             message(?string $new_value = null) : string;
    public function      mock_class_fqn(?string $new_value = null) : string;
    public function            previous(?IMockPHPException  ...$new_value) : ?IMockPHPException;

    /**
    * @param  kkdebug_backtrace_a   $new_value=
    * @return kkdebug_backtrace_a
    */
    public function trace(?array $new_value = null) : array;
}

/**
* Exception-like type that can be used for testing exception functionality.
*
* Note that this does not extend \Exception: this is intentional, as it
* allows us to use mock data in our stack trace, file, line, etc.
* The PHP \Exception object does not allow most of its methods
* to be overridden.
*
* @see MockPHPException
*/
interface IMockPHPException extends IKickbackThrowableRaw, IMockPHPException__ConfigAccessors
{
    public function getPrevious() : ?IMockPHPException;

    public function __construct(?string $message = null, int $code = 0, ?IMockPHPException $previous = null);
}

/**
* Concrete counterpart of \Kickback\Common\Exceptions\Internal\IMockPHPException
*
* This class's purpose is to provide ready-made functions for managing
* all of the state/data that is placed into a mock exception.
*
* @see IMockPHPException
* @internal
* @phpstan-import-type  kkdebug_frame_a      from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
* @phpstan-import-type  kkdebug_backtrace_a  from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
*/
class MockPHPException implements IMockPHPException
{
    // Note: We don't _actually_ implement Throwable because it
    // will cause an error stating that we should extend \Exception or \Error,
    // which we can't because those don't allow us to override all of the
    // getMessage, getFile, getLine, getTrace, etc. functions.
    private string                   $message_ = '';
    private int                      $code_ = 0;
    private int                      $line_ = 0;
    private string                   $file_ = '';

    private ?IMockPHPException       $previous_ = null;

    /** @var  kkdebug_backtrace_a */
    private array  $trace_ = [];

    // Mocking-specific state
    private string       $mock_class_fqn_;
    private string       $caller_context_file_ = '';
    private int          $caller_context_line_ = 0;

    public function getMessage()  : string             { return $this->message_;  }
    public function getPrevious() : ?IMockPHPException { return $this->previous_; }

    /** @return int */
    public function getCode() : int    { return $this->code_; }
    public function getLine() : int    { return $this->line_; }
    public function getFile() : string { return $this->file_; }

    /** @return kkdebug_backtrace_a */
    public function getTrace() : array { return $this->trace_; }
    public function getTraceAsString() : string
    {
        return DefaultMethods::getTraceAsString($this);
    }

    private function infer_file_and_line() : void {
        if(\count($this->trace_) < 2) {
            $this->file_ = '';
            $this->line_ = 0;
            return;
        }
        $frame = $this->trace_[1];
        $file = \array_key_exists('file',$frame) ? $frame['file'] : '{unknown file}';
        $line = \array_key_exists('line',$frame) ? $frame['line'] : 0;
        $this->file_ = $file;
        $this->line_ = $line;
    }


    public function file(?string $new_value = null) : string {
        if (!isset($new_value)) {
            return $this->file_;
        }
        $this->file_ = $new_value;
        return $this->file_;
    }

    public function line(?int $new_value = null) : int {
        if (!isset($new_value)) {
            return $this->line_;
        }
        $this->line_ = $new_value;
        return $this->line_;
    }

    public function code(?int $new_value = null) : int {
        if (!isset($new_value)) {
            return $this->code_;
        }
        $this->code_ = $new_value;
        return $this->code_;
    }

    public function caller_context_file(?string $new_value = null) : string {
        if (!isset($new_value)) {
            return $this->caller_context_file_;
        }
        $this->caller_context_file_ = $new_value;
        return $this->caller_context_file_;
    }

    public function caller_context_line(?int $new_value = null) : int {
        if (!isset($new_value)) {
            return $this->caller_context_line_;
        }
        $this->caller_context_line_ = $new_value;
        return $this->caller_context_line_;
    }

    public function message(?string $new_value = null) : string {
        if (!isset($new_value)) {
            return $this->message_;
        }
        $this->message_ = $new_value;
        return $this->message_;
    }

    /**
    * @param  ?IMockPHPException  $new_value=
    * @return ?IMockPHPException
    */
    public function previous(?IMockPHPException ...$new_value) : ?IMockPHPException {
        if ( \count($new_value) !== 1 ) {
            return $this->previous_;
        }
        $this->previous_ = $new_value[0];
        return $this->previous_;
    }

    /**
    * @param  ?kkdebug_backtrace_a   $new_value=
    * @return kkdebug_backtrace_a
    */
    public function trace(?array $new_value = null) : array {
        if (!isset($new_value)) {
            return $this->trace_;
        }
        $this->trace_ = $new_value;
        $this->infer_file_and_line();
        return $this->trace_;
    }

    // Unused but potentially useful?
    // /**
    // * Version of \class_exists() that does autoloading but guards against infinite recursion.
    // *
    // * @phpstan-assert-if-true  class-string  $class_fqn
    // */
    // private static function class_defined(string $class_fqn) : bool
    // {
    //     static $recursion = false;
    //     if ( $recursion ) {
    //         return \class_exists($class_fqn, false);
    //     } else {
    //         $recursion = true;
    //         $result = false;
    //         try {
    //             $result = \class_exists($class_fqn, true);
    //         } finally {
    //             $recursion = false;
    //         }
    //         return $result;
    //     }
    // }

    // NOTE: These are NOT `class-string`, because we probably won't use
    // actual class names during testing (to keep things predictable
    // and tolerant of change).
    public function mock_class_fqn(?string $new_value = null) : string {
        if (!isset($new_value)) {
            return $this->mock_class_fqn_;
        }
        $this->mock_class_fqn_ = $new_value;
        return $this->mock_class_fqn_;
    }

    /**
    * @phpstan-pure
    * @throws void
    */
    public function __toString() : string {
        return DefaultMethods::toString($this);
    }

    public function __construct(?string $message = null, int $code = 0, ?IMockPHPException $previous = null, bool $do_backtrace = true)
    {
        if (!isset($message)) { $message = ''; }
        $this->message_  = $message;
        $this->code_     = $code;
        $this->previous_ = $previous;
        if ( $do_backtrace ) {
            $this->trace_    = \debug_backtrace();
            $this->infer_file_and_line();
        } else {
            $this->trace_ = [];
            $this->file_ = '';
            $this->line_ = 0;
        }
        $this->mock_class_fqn_ = \get_class($this);
    }
}
?>
