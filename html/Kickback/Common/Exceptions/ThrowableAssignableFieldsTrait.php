<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions;

use Kickback\Common\Exceptions\Internal\Misc;
use Kickback\Common\Exceptions\ThrowableWithAssignableFields;
use Kickback\Common\Meta\Location;

// TODO: This ended up complicated enough that it could
//         probably justify having its own unittests.
//         Fortunately, for the time being, it seems to be
//         pretty well covered by the tests in `ThrowableContextMessageHandlingTrait`
//         and `Kickback\Common\Exceptions\Reporting\Report`.
/**
* @phpstan-import-type  kkdebug_frame_a               from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
* @phpstan-import-type  kkdebug_backtrace_a           from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
* @phpstan-import-type  kkdebug_frame_paranoid_a      from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
* @phpstan-import-type  kkdebug_backtrace_paranoid_a  from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
*
* @phpstan-require-implements  \Kickback\Common\Exceptions\ThrowableWithAssignableFields
*/
trait ThrowableAssignableFieldsTrait
{
    private int                $kk_code_;
    private string             $kk_main_message_path_;
    private string             $kk_main_message_func_;
    /** @var int<0,max> */
    private int                $kk_main_message_line_;
    private string|\Closure    $kk_main_message_;

    private ?string $kk_main_message_prefix_ = null;

    /**
    * The text, usually file and line, that precedes the
    * `message()` field in the output of `__toString()`.
    *
    * This may be `null` if the message is a single line
    * (e.g. contains no newline characters) and there are
    * no other reasons for the message prefix to exists
    * (ex: `say_before_message` and `say_after_message` lines
    * from `ThrowableContextMessageHandlingTrait` will necessitate
    * that there is a `message_prefix`).
    *
    * If the caller requires this to be non-null, then call
    * `populate_message_prefix` before calling this property-method.
    *
    * @phpstan-pure
    * @throws void
    */
    protected function message_prefix() : ?string {
        return $this->kk_main_message_prefix_;
    }

    /**
    * Causes the `message_prefix()` field to be calculated (unconditionally).
    *
    * Code outside of the `ThrowableAssignableFieldsTrait` should have no
    * reason to call this. To populate the `message_prefix` field from
    * outside of the trait, call `populate_message_prefix`.
    *
    * @phpstan-impure
    * @throws void
    */
    private function calculate_message_prefix() : void {
        $this->kk_main_message_prefix_ =
            Misc::calculate_message_line_prefix(
                $this->kk_main_message_path_, $this->kk_main_message_line_);
    }

    /**
    * Causes the `message_prefix()` field to be recalculated.
    *
    * This is only called from within the trait because the only thing that can
    * invalidate the _contents_ of the prefix are changes to the file path
    * or line number, and those are already handled internally within
    * the trait.
    *
    * If there is currently no prefix, then this will do nothing.
    *
    * @phpstan-impure
    * @throws void
    */
    private function invalidate_message_prefix() : void {
        if (!isset($this->kk_main_message_prefix_)) {
            return;
        }
        $this->calculate_message_prefix();
    }

    /**
    * Cause this throwable's `message_prefix` field to be calculated (once).
    *
    * This should be called whenever the Throwable/Exception's fields/state
    * changes in a way that would require `__toString()` to print multiple
    * messages or a message with multiple lines.
    *
    * If the `message_prefix` has already been calculated, this will do nothing.
    *
    * (This is not a(n) (in)validation routine because the only thing that can
    * invalidate the _contents_ of the prefix are changes to the file path
    * or line number, and those are already handled internally within
    * the trait.)
    *
    * Here are two known examples of when this is required:
    * * The `message()` field changed and is now a multi-line message.
    * * The `say_before_message` or `say_after_message` methods
    *     from `ThrowableContextMessageHandlingTrait` were called.
    *     Now there are multiple messages to print, which will require
    *     the main message to have a file+line prefix to distinguish
    *     it from the other messages.
    *
    * @phpstan-impure
    * @throws void
    */
    public function populate_message_prefix() : void {
        if (isset($this->kk_main_message_prefix_)) {
            return;
        }
        $this->calculate_message_prefix();
    }

    /**
    * Initializes state in the `ThrowableAssignableFieldsTrait`.
    *
    * This must be called from the constructor of any object
    * that uses the `ThrowableAssignableFieldsTrait`.
    *
    * This ensures that certain fields within the trait are always non-null.
    *
    * @phpstan-impure
    * @throws void
    */
    protected function ThrowableAssignableFieldsTrait_init(
        string|\Closure|null   $msg = null,
        int                    $code,
        string                 $in_file,
        string                 $in_function,
        int                    $at_line = 0
    ) : void
    {
        $this->code($code);
        $this->message($msg, $in_file, $in_function, $at_line);
    }

    /**
    * @see ThrowableWithAssignableFields::message_pure
    *
    * @phpstan-pure
    * @throws void
    */
    public function message_pure() : string
    {
        //assert(isset($this->kk_main_message_));
        if ( is_string($this->kk_main_message_) ) {
            return $this->kk_main_message_;
        } else {
            return ($this->kk_main_message_)(); // Invoke closure.
        }
    }

    /**
    * @see ThrowableWithAssignableFields::message
    *
    * @param  string|(\Closure():string)|null   $msg
    * @param ?string                            $in_file
    * @param ?string                            $in_function
    * @param int                                $at_line
    * @param ?int<0,max>                        $at_trace_depth
    * @param ?array<array{
    *       function? : string,
    *       line?     : int,
    *       file?     : string,
    *       class?    : class-string,
    *       type?     : '->'|'::',
    *       args?     : array<array-key, mixed>,
    *       object?   : object
    *   }>                                      $trace
    *
    * @throws void
    */
    public function message(
        string|\Closure|null   $msg = null,
        ?string                $in_file = null,
        ?string                $in_function = null,
        int                    $at_line = \PHP_INT_MIN,
        ?int                   $at_trace_depth = null,
        ?array                 $trace = null
    ) : string
    {
        if (!isset($msg)) {
            // If the message isn't being set, it suggests a getter call.
            // To keep things simple, it is illegal to attempt to set
            // location information during such a call.
            assert(!Location::is_set(
                $in_file, $in_function, $at_line, $at_trace_depth, $trace));

            // All we need to do is return the existing message.
            return $this->message_pure();
        }

        // Handle setter call.
        $this->kk_main_message_ = $msg;

        // Check for multiline messages --
        // these require us to populate the message prefix.
        if (is_string($msg)) {
            $is_multiline_msg = \str_contains($msg, "\n");
        } else {
            // This is not necessarily true.
            // However, if `$msg` is a closure, then we shouldn't
            // call it right now. It would be likely too premature.
            $is_multiline_msg = false;
        }

        // Return early if we're not doing anything with file/line info.
        if (!Location::is_set(
            $in_file, $in_function, $at_line, $at_trace_depth, $trace))
        {
            // Caller does not wish to modify this exception's location info.

            // If needed, calculate the message prefix
            // using whatever file/line info is already present.
            if ( $is_multiline_msg ) {
                $this->populate_message_prefix();
            }

            // Get the newly assigned message.
            return $this->message_pure();
        }

        // Default this field, in case the caller passed explicit
        // file + function + line info, but didn't specify trace depth.
        // (which is fairly likely, actually.)
        if (!isset($at_trace_depth)) {
            $at_trace_depth = 0;
        }

        // Update file/line information
        if (Location::need_backtrace(
            $in_file, $in_function, $at_line, $at_trace_depth, $trace))
        {
            $at_trace_depth = 2 + $at_trace_depth;
            $trace = \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $at_trace_depth);
        }
        Location::process_info($in_file, $in_function, $at_line, $at_trace_depth, $trace);

        // Assumption: this indirectly calls `$this->invalidate_message_prefix()`.
        $this->set_location($in_file, $in_function, $at_line);

        // If this call changes a single-line message into a multi-line
        // one, then the above indirect call to `invalidate_message_prefix`
        // will not do anything because the prefix isn't set.
        // So in the multi-line message case, we call `populate_message_prefix`.
        // (And because it doesn't calculate the prefix if it's already
        // calculated, this avoids doubled prefix calculation.)
        // Note that order-of-operation is important:
        // We don't want to do this before `invalidate_message_prefix`
        // gets called, because then there could be situations where
        // `populate_message_prefix` will calculate the prefix and then
        // `invalidate_message_prefix` will calculate it AGAIN.
        if ( $is_multiline_msg ) {
            $this->populate_message_prefix();
        }

        // Return the message.
        return $this->message_pure();
    }

    /**
    * @see ThrowableWithAssignableFields::code_pure
    *
    * @phpstan-pure
    * @throws void
    */
    public function code_pure() : int
    {
        return $this->kk_code_;
    }

    /**
    * @see ThrowableWithAssignableFields::code
    *
    * @throws void
    */
    public function code(?int $new_code = null) : int
    {
        if(!isset($new_code)) {
            return $this->kk_code_;
        }
        $this->kk_code_ = $new_code;
        return $this->kk_code_;
    }

    /**
    * @see ThrowableWithAssignableFields::file
    *
    * @phpstan-pure
    * @throws void
    */
    public function file() : string
    {
        // Note: This property isn't a setter because it will typically
        // be paired with a call to `line` (and possibly also `function`),
        // and each one would need to call `invalidate_message_prefix`,
        // thus doubling (or tripling) the invalidation cost.
        //
        // By forcing the caller to use, for example, `set_location` instead,
        // we make doubled/tripled (unnecessary) invalidation very unlikely.
        //
        return $this->kk_main_message_path_;
    }

    /**
    * @see ThrowableWithAssignableFields::file
    *
    * @phpstan-pure
    * @throws void
    */
    public function func() : string
    {
        // Note: This property isn't a setter because it will typically
        // happen alongside calls to `file` and `line`, and each one would
        // need to call `invalidate_message_prefix`, thus tripling
        // the invalidation cost.
        //
        // By forcing the caller to use, for example, `set_location` instead,
        // we make tripled (unnecessary) invalidation very unlikely.
        //
        return $this->kk_main_message_func_;
    }

    /**
    * @see ThrowableWithAssignableFields::line
    *
    * @phpstan-pure
    * @throws void
    */
    public function line() : int
    {
        // Note: This property isn't a setter because it will typically
        // be paired with a call to `file` (and possibly also `function`),
        // and each one would need to call `invalidate_message_prefix`,
        // thus doubling (or tripling) the invalidation cost.
        //
        // By forcing the caller to use, for example, `set_location` instead,
        // we make doubled/tripled (unnecessary) invalidation very unlikely.
        //
        return $this->kk_main_message_line_;
    }

    /**
    * @param string                             $in_file
    * @param string                             $in_function
    * @param int<0,max>                         $at_line
    *
    * @phpstan-impure
    * @throws void
    */
    public function set_location(
        string    $in_file,
        string    $in_function,
        int       $at_line
    ) : void
    {
        $this->kk_main_message_path_ = $in_file;
        $this->kk_main_message_func_ = $in_function;
        $this->kk_main_message_line_ = $at_line;
        $this->invalidate_message_prefix();
    }

    /**
    * @see ThrowableWithAssignableFields::calculate_location_from
    *
    * @param ?string                            $in_file
    * @param ?string                            $in_function
    * @param int                                $at_line
    * @param int<0,max>                         $at_trace_depth
    * @param ?array<array{
    *       function? : string,
    *       line?     : int,
    *       file?     : string,
    *       class?    : class-string,
    *       type?     : '->'|'::',
    *       args?     : array<array-key, mixed>,
    *       object?   : object
    *   }>                                      $trace
    *
    * @phpstan-impure
    * @throws void
    */
    public function calculate_location_from(
        ?string               $in_file = null,
        ?string               $in_function = null,
        int                   $at_line = \PHP_INT_MIN,
        int                   $at_trace_depth = 0,
        ?array                $trace = null
    ) : void
    {
        if (Location::need_backtrace(
            $in_file, $in_function, $at_line, $at_trace_depth, $trace))
        {
            $at_trace_depth = 2 + $at_trace_depth;
            $trace = \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $at_trace_depth);
        }
        Location::process_info($in_file, $in_function, $at_line, $at_trace_depth, $trace);

        // Assumption: this indirectly calls `$this->invalidate_message_prefix()`.
        $this->set_location($in_file, $in_function, $at_line);
    }

}
?>
