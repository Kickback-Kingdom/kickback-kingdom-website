<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions;

use Kickback\Common\Exceptions\Internal\Misc;
use Kickback\Common\Exceptions\ThrowableWithAssignableFields;

// TODO: This ended up complicated enough that it could
//         probably justify having its own unittests.
//         Fortunately, for the time being, it seems to be
//         pretty well covered by the tests in `ThrowableContextMessageHandlingTrait`
//         and `Kickback\Common\Exceptions\Reporting\Report`.
/**
* @phpstan-import-type  kkdebug_frame_a      from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
* @phpstan-import-type  kkdebug_backtrace_a  from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
*
* @phpstan-require-implements  \Kickback\Common\Exceptions\ThrowableWithAssignableFields
*/
trait ThrowableAssignableFieldsTrait
{
    private string             $kk_main_message_path_;
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
        string|\Closure $msg, string|int $in_file_or_at_stack_depth, int $at_line = 0
    ) : void {
        if ( \is_int($in_file_or_at_stack_depth) ) {
            // Compensate stack depth for the call to
            // `ThrowableAssignableFieldsTrait_init`
            $in_file_or_at_stack_depth++;
        }
        $this->message($msg, $in_file_or_at_stack_depth, $at_line);
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
    * @param  string|int|null                   $in_file_or_at_stack_depth
    * @param  int                               $at_line
    *
    * @throws void
    */
    public function message(string|\Closure|null $msg = null, string|int|null $in_file_or_at_stack_depth = null, int $at_line = 0) : string
    {
        if (!isset($msg)) {
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
        if (!isset($in_file_or_at_stack_depth)) {
            if ( $is_multiline_msg ) {
                // If needed, calculate the message prefix
                // using whatever file/line info is already present.
                $this->populate_message_prefix();
            }
            return $this->message_pure();
        }

        // Update file/line information

        // Optimization:
        // We only care about file+line from the caller, so we can
        // avoid taking too much memory/time with debug_backtrace
        // by asking it to leave arguments out, and to only grab
        // the frames that we need.
        // (Also, if the caller provides us with file+line info,
        // we can avoid calling \debug_backtrace entirely.)
        // @var kkdebug_backtrace_a
        $trace = null;
        if (is_int($in_file_or_at_stack_depth))
        {
            $stack_depth = 2 + $in_file_or_at_stack_depth;
            $trace = \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $stack_depth);
        }

        // Assumption: this indirectly calls `$this->invalidate_message_prefix()`.
        ThrowableAssignableFields::
            process_location_info($this, $trace, $in_file_or_at_stack_depth, $at_line);

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
    * @see ThrowableWithAssignableFields::file
    *
    * @phpstan-pure
    * @throws void
    */
    public function file() : string
    {
        // Note: This property isn't a setter because it will typically
        // be paired with a call to `line`, and each one would need to
        // call `invalidate_message_prefix`, thus doubling
        // the invalidation cost.
        //
        // By forcing the caller to use, for example, `set_location` instead,
        // we make doubled (unnecessary) invalidation very unlikely.
        //
        return $this->kk_main_message_path_;
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
        // be paired with a call to `file`, and each one would need to
        // call `invalidate_message_prefix`, thus doubling
        // the invalidation cost.
        //
        // By forcing the caller to use, for example, `set_location` instead,
        // we make doubled (unnecessary) invalidation very unlikely.
        //
        return $this->kk_main_message_line_;
    }

    /**
    * @see ThrowableWithAssignableFields::set_location
    *
    * @phpstan-impure
    * @throws void
    */
    public function set_location(string $file_path, int $at_line) : void
    {
        $this->kk_main_message_path_ = $file_path;
        $this->kk_main_message_line_ = $at_line;
        $this->invalidate_message_prefix();
    }


    /**
    * @see ThrowableWithAssignableFields::calculate_location_from
    *
    * @param  string|int   $in_file_or_at_stack_depth
    * @param  int          $at_line
    *
    * @phpstan-impure
    * @throws void
    */
    public function calculate_location_from(string|int $in_file_or_at_stack_depth, int $at_line = 0) : void
    {
        // Optimization:
        // We only care about file+line from the caller, so we can
        // avoid taking too much memory/time with debug_backtrace
        // by asking it to leave arguments out, and to only grab
        // the frames that we need.
        // (Also, if the caller provides us with file+line info,
        // we can avoid calling \debug_backtrace entirely.)
        // @var kkdebug_backtrace_a
        $trace = null;
        if (is_int($in_file_or_at_stack_depth))
        {
            $stack_depth = 2 + $in_file_or_at_stack_depth;
            $trace = \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $stack_depth);
        }
        ThrowableAssignableFields::
            process_location_info($this, $trace, $in_file_or_at_stack_depth, $at_line);
    }

}

/**
* @phpstan-import-type  kkdebug_frame_a      from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
* @phpstan-import-type  kkdebug_backtrace_a  from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
*/
final class ThrowableAssignableFields
{
    /**
    * Sets the exception's location based on either the given backtrace, or explicit values.
    *
    * If `$in_file_or_at_stack_depth` is given as an integer,
    * then this will call \debug_backtrace to figure out the file and line
    * number from the backtrace. If `$in_file_or_at_stack_depth` is `0`,
    * then this will be the file and line number of the location where
    * the `message()` method is called.
    *
    * If `$in_file_or_at_stack_depth` is given as a string,
    * then it will provide the file/path and `$at_line` will provide
    * the line number. This is similar to calling
    * `set_location($in_file_or_at_stack_depth, $at_line)`.
    *
    * @param  ?kkdebug_backtrace_a  $trace
    * @param  string|int            $in_file_or_at_stack_depth
    * @param  int                   $at_line
    *
    * @phpstan-impure
    * @throws void
    */
    public static function process_location_info(
        ThrowableWithAssignableFields  $exc,
        ?array                         $trace,
        string|int                     $in_file_or_at_stack_depth,
        int                            $at_line = 0)
    : void
    {
        Misc::process_location_info_into(
            $trace, $in_file_or_at_stack_depth, null, $at_line,
            $path, $func, $line);

        $exc->set_location($path,$line);
    }
}
?>
