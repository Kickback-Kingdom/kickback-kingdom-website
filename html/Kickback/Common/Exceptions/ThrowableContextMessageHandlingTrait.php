<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions;

use Kickback\Common\Exceptions\ThrowableWithAssignableFields;
use Kickback\Common\Exceptions\ThrowableWithContextMessages;
use Kickback\Common\Exceptions\Internal\DefaultMethods;
use Kickback\Common\Exceptions\Internal\IKickbackThrowableRaw;
use Kickback\Common\Exceptions\Internal\MockPHPException;
use Kickback\Common\Exceptions\Internal\IMockPHPException;
use Kickback\Common\Exceptions\Internal\IMockPHPException__ConfigAccessors;
use Kickback\Common\Exceptions\Internal\Misc;
use Kickback\Common\Meta\Location;

// Implementation note:
//
// PLEASE keep this low-dependency or no-dependency.
//
// _Even if it means violating DRY principle sometimes._
//
// This should _especially_ have NO dependencies that can throw exceptions.
// Such a situation could turn an exception into a non-printing hang,
// and it would be very difficult to determine what went wrong.
//
// For other dependencies, it is still desirable to keep them out,
// usually by writing simpler alternatives within this code.
// This is because other dependencies can have regressions,
// and those dependencies might not otherwise have the same
// severity of consequence for regressions as does the exception
// handling subsystem. For example: a minor text stream oopsie
// might be seen as an inconvenient display regression elsewhere,
// but meanwhile prevent exceptions from being printed. This would
// make it very difficult to troubleshoot everything, including
// the inciting display regression.

// Future directions: Color output in CLI and HTML.
//   (Most display methods will have color and some formatting options,
//   so this is a good way to improve readability. That's really important
//   actually, given how cryptic exceptions and stack traces can look!)

/**
* @phpstan-import-type  kkdebug_frame_a               from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
* @phpstan-import-type  kkdebug_backtrace_a           from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
* @phpstan-import-type  kkdebug_frame_paranoid_a      from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
* @phpstan-import-type  kkdebug_backtrace_paranoid_a  from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
*
* @phpstan-require-implements  \Kickback\Common\Exceptions\ThrowableWithContextMessages
* @phpstan-require-implements  \Kickback\Common\Exceptions\ThrowableWithAssignableFields
*/
trait ThrowableContextMessageHandlingTrait
{
    // NOTE: Kickback\Common\Exceptions\Internal\DefaultMethods
    // contains a comment that explains PHP's exception printing.

    /** @var ?array<string|\Closure():string> */
    private ?array $kk_prepend_messages_ = null;

    /** @var ?array<string> */
    private ?array $kk_prepend_msg_path_ = null;

    /** @var ?array<string> */
    private ?array $kk_prepend_msg_func_ = null;

    /** @var ?array<int> */
    private ?array $kk_prepend_msg_line_ = null;

    /** @var ?array<string> */
    private ?array $kk_prepend_msg_loc_full_ = null;

    /**
    * @see  ThrowableWithContextMessages::say_before_message
    * @see  ThrowableWithContextMessages::say_after_message
    *
    * @param  string|\Closure():string          $msg
    * @param ?string                            $in_file
    * @param  ($in_file is string ? string : (?string)
    *         )                                 $in_function
    * @param int                                $at_line
    * @param int<0,max>                         $at_trace_depth
    * @param ?kkdebug_backtrace_paranoid_a      $trace
    *
    * @phpstan-impure
    * @throws void
    */
    public function say_before_message(
        string|\Closure   $msg,
        ?string           $in_file = null,
        ?string           $in_function = null,
        int               $at_line = \PHP_INT_MIN,
        int               $at_trace_depth = 0,
        ?array            $trace = null
    ) : void
    {
        // Optimization:
        // We only care about file+line from the caller, so we can
        // avoid taking too much memory/time with debug_backtrace
        // by asking it to leave arguments out, and to only grab
        // the frames that we need.
        // (Also, if the caller provides us with file+line info,
        // we can avoid calling \debug_backtrace entirely.)
        // @var kkdebug_backtrace_a
        if (!($this instanceof IMockPHPException__ConfigAccessors)
        &&  Location::need_backtrace(
            $in_file, $in_function, $at_line, $at_trace_depth, $trace))
        {
            $at_trace_depth = 2 + $at_trace_depth;
            $trace = \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $at_trace_depth);
        }
        // `Location::process_info` is handled in `ThrowableContextMessageHandling::add_context_message`

        ThrowableContextMessageHandling::add_context_message(
            $this,
            $this->kk_prepend_messages_,
            $this->kk_prepend_msg_path_,
            $this->kk_prepend_msg_func_,
            $this->kk_prepend_msg_line_,
            $this->kk_prepend_msg_loc_full_,
            $msg, true, $in_file, $in_function, $at_line, $at_trace_depth, $trace);
    }

    /** @var ?array<string|\Closure():string> */
    private ?array $kk_append_messages_ = null;

    /** @var ?array<string> */
    private ?array $kk_append_msg_path_ = null;

    /** @var ?array<string> */
    private ?array $kk_append_msg_func_ = null;

    /** @var ?array<int> */
    private ?array $kk_append_msg_line_ = null;

    /** @var ?array<string> */
    private ?array $kk_append_msg_loc_full_ = null;

    /**
    * @see ThrowableWithContextMessages::say_before_message
    * @see ThrowableWithContextMessages::say_after_message
    *
    * @param  string|\Closure():string          $msg
    * @param ?string                            $in_file
    * @param  ($in_file is string ? string : (?string)
    *         )                                 $in_function
    * @param int                                $at_line
    * @param int<0,max>                         $at_trace_depth
    * @param ?kkdebug_backtrace_paranoid_a      $trace
    *
    * @phpstan-impure
    * @throws void
    */
    public function say_after_message(
        string|\Closure   $msg,
        ?string           $in_file = null,
        ?string           $in_function = null,
        int               $at_line = \PHP_INT_MIN,
        int               $at_trace_depth = 0,
        ?array            $trace = null
    ) : void
    {
        // Optimization:
        // We only care about file+line from the caller, so we can
        // avoid taking too much memory/time with debug_backtrace
        // by asking it to leave arguments out, and to only grab
        // the frames that we need.
        // (Also, if the caller provides us with file+line info,
        // we can avoid calling \debug_backtrace entirely.)
        // @var kkdebug_backtrace_a
        if (!($this instanceof IMockPHPException__ConfigAccessors)
        &&  Location::need_backtrace(
            $in_file, $in_function, $at_line, $at_trace_depth, $trace))
        {
            $at_trace_depth = 2 + $at_trace_depth;
            $trace = \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $at_trace_depth);
        }
        // `Location::process_info` is handled in `ThrowableContextMessageHandling::add_context_message`

        ThrowableContextMessageHandling::add_context_message(
            $this,
            $this->kk_append_messages_,
            $this->kk_append_msg_path_,
            $this->kk_append_msg_func_,
            $this->kk_append_msg_line_,
            $this->kk_append_msg_loc_full_,
            $msg, false, $in_file, $in_function, $at_line, $at_trace_depth, $trace);
    }

    public final function clear_context_messages() : void
    {
        $this->kk_prepend_messages_       = null;
        $this->kk_prepend_msg_path_       = null;
        $this->kk_prepend_msg_func_       = null;
        $this->kk_prepend_msg_line_       = null;
        $this->kk_prepend_msg_loc_full_   = null;

        $this->kk_append_messages_        = null;
        $this->kk_append_msg_path_        = null;
        $this->kk_append_msg_func_        = null;
        $this->kk_append_msg_line_        = null;
        $this->kk_append_msg_loc_full_    = null;
    }

    /**
    * @see ThrowableWithContextMessages::have_context_messages
    *
    * @phpstan-pure
    * @throws void
    */
    public final function have_context_messages() : bool {
        return (0 < $this->context_message_count());
    }

    // These methods are helpful for other abstractions to be able to
    // use the `say_after`/`say_before` feature programmatically.
    // One example is in `Kickback\Common\Exceptions\Reporting\Report`.

    /**
    * @see ThrowableWithContextMessages::say_before_message_count
    *
    * @phpstan-pure
    * @throws void
    */
    public final function say_before_message_count() : int {
        return isset($this->kk_prepend_messages_) ? \count($this->kk_prepend_messages_) : 0;
    }

    /**
    * @see ThrowableWithContextMessages::say_after_message_count
    *
    * @phpstan-pure
    * @throws void
    */
    public final function say_after_message_count() : int {
        return isset($this->kk_append_messages_) ? \count($this->kk_append_messages_) : 0;
    }

    /**
    * @see ThrowableWithContextMessages::context_message_count
    *
    * @phpstan-pure
    * @throws void
    */
    public final function context_message_count() : int {
        return $this->say_before_message_count() + $this->say_after_message_count();
    }

    /**
    * @phpstan-pure
    * @throws void
    */
    public function __toString() : string
    {
        // if (!isset($this->text_colorizer)) {
        //     $this->text_colorizer = new TextColorizer(TextColorPalette::defaults());
        // }
        // $c = $this->text_colorizer;

        $this_msg = $this->message_pure();
        $is_multiline_msg = \str_contains($this_msg, "\n");
        $have_context_messages = $this->have_context_messages();
        if (!$have_context_messages && !$is_multiline_msg)
        {
            if ( $this instanceof IMockPHPException ) {
                return DefaultMethods::toString($this, $this->mock_class_fqn(), $this_msg);
            } else {
                // We used to attempt to call `parent::__toString()`
                // if `\get_parent_class($this)` returned something,
                // but that was before the `ThrowableWithAssignableFields->message()`
                // was implemented. If we call `parent::__toString()`, then
                // `message()` might not be used instead of `getMessage()`,
                // which could violate promises made by the
                // `ThrowableWithAssignableFields` interface.
                // So instead, we just unconditionally use
                // `DefaultMethods::toString(...)` because it WILL
                // use the correct `message()` property-method.
                return DefaultMethods::toString($this, null, $this_msg);
            }
        }

        // We can always have a `message_prefix`, even if the
        // `say_*_message` methods are never called.
        //
        // There are a few different ways this can go:
        // (1) The exception has a single-line message and no context messages.
        // (2) The exception has a multi-line message and zero or more context messages.
        // (3) The exception has a single-line message and one or more context messages.
        // (4) The exxeption has a multi-line message and one or more context messages.
        //
        // In case 1, we wouldn't have a prefix, but this is also the case
        // that just calls `DefaultMethods::toString`.
        //
        // Cases 2-4 all would have already executed operations that
        // populate the `$this->message_prefix()` property-method.
        //
        // And yes, a multi-line main message will still trigger context
        // printing, even if we don't have other context messages.
        // (Because this printer is MUCH better at handling multi-line!)
        $main_message_prefix = $this->message_prefix();

        // The below can happen if the main message is a closure AND multi-line.
        // In that case, the detection logic in `ThrowableAssignableFieldsTrait`
        // would not be able to call `$this->populate_message_prefix()`
        // on itself earlier, because that would call the closure prematurely.
        if (!isset($main_message_prefix)) {
            // It'd be nice to be able to call $this->populate_message_prefix()
            // so that it doesn't have to be recalculated, but
            // we don't want to invalidate the promise that this
            // method is `pure` (and doesn't modify the exception object).
            $main_message_prefix =
                Misc::calculate_message_line_prefix(
                        $this->file(), $this->line());
        }

        $padding =
            ThrowableContextMessageHandling::
                calculate_context_message_padding(
                    $main_message_prefix,
                    $this->kk_prepend_msg_loc_full_,
                    $this->kk_append_msg_loc_full_);

        if ( $this instanceof IMockPHPException__ConfigAccessors ) {
            $class_fqn = $this->mock_class_fqn();
        } else {
            $class_fqn = \get_class($this);
        }

        $strbuf  = $class_fqn . "\n";
        // $strbuf .= $c->begin($c->BOLD, $c->WHITE);
        $strbuf .= "Messages:\n";
        // $strbuf .= $c->end();

        $indent_str = '  ';
        $blank_prefix = \str_repeat(' ', $padding+2);

        // Context messages that go BEFORE $this->message().
        $len = isset($this->kk_prepend_messages_) ? \count($this->kk_prepend_messages_) : 0;
        for ($i = $len-1; $i >= 0; $i--) {
            assert(isset($this->kk_prepend_msg_loc_full_));
            assert(isset($this->kk_prepend_messages_));
            ThrowableContextMessageHandling::
                emit_context_lines_from_message(
                    $strbuf, $blank_prefix, $padding, $indent_str,
                    $this->kk_prepend_msg_loc_full_[$i],
                    $this->kk_prepend_messages_[$i], '', "\n");
        }

        if ( !$have_context_messages ) {
            // In the other cases, we point out the "thrown from here"
            // message. But if there's only one message, then it is
            // unnecessary and might just be confusing, so we don't print it.
            ThrowableContextMessageHandling::
                emit_context_lines_from_message(
                    $strbuf, $blank_prefix, $padding, $indent_str,
                    $main_message_prefix, $this_msg, '', "\n");
        }
        else if ( $is_multiline_msg )
        {
            ThrowableContextMessageHandling::
                emit_context_lines_from_message(
                    $strbuf, $blank_prefix, $padding, $indent_str,
                    $main_message_prefix, '(<- thrown from here)', '', "\n");
            ThrowableContextMessageHandling::
                emit_context_lines_from_message(
                    $strbuf, $blank_prefix, $padding, $indent_str,
                    '', $this_msg, '', "\n");
        } else {
            ThrowableContextMessageHandling::
                emit_context_lines_from_message(
                    $strbuf, $blank_prefix, $padding, $indent_str,
                    $main_message_prefix, $this_msg, '', '');
            $strbuf .= "  (thrown from here)\n";
        }

        // Context messages that go AFTER $this->message().
        $len = isset($this->kk_append_messages_) ? \count($this->kk_append_messages_) : 0;
        for ($i = 0; $i < $len; $i++) {
            assert(isset($this->kk_append_msg_loc_full_));
            assert(isset($this->kk_append_messages_));
            ThrowableContextMessageHandling::
                emit_context_lines_from_message(
                    $strbuf, $blank_prefix, $padding, $indent_str,
                    $this->kk_append_msg_loc_full_[$i],
                    $this->kk_append_messages_[$i], '', "\n");
        }

        $strbuf .= "\n";
        // $strbuf .= $c->begin($c->BOLD, $c->WHITE);
        $strbuf .= "Trace:\n";
        // $strbuf .= $c->end();
        $strbuf .= $this->getTraceAsString();

        //echo "__toString() called\n";
        return $strbuf;
    }
}

/**
* @internal
*/
class ThrowableContextMessageHandlingTrait__Mock extends MockPHPException implements ThrowableWithContextMessages
{
    use ThrowableContextMessageHandlingTrait;
}

/**
* @phpstan-import-type  kkdebug_frame_a               from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
* @phpstan-import-type  kkdebug_backtrace_a           from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
* @phpstan-import-type  kkdebug_frame_paranoid_a      from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
* @phpstan-import-type  kkdebug_backtrace_paranoid_a  from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
*/
class ThrowableContextMessageHandling
{
    private static function unittest_ThrowableWithContextMessages_toString() : void
    {
        echo("  ".__FUNCTION__."()\n");

        $exc = new ThrowableContextMessageHandlingTrait__Mock();

        // To start off, we'll make the __toString() output
        // easier to test by providing our mock object with predictable
        // data for all of the things we aren't testing, or that would
        // otherwise just be confounding elements in our tests.
        $exc->trace([]); // We aren't testing trace output, so just don't emit it.
        $exc->mock_class_fqn('MockException'); // Shorter, and by forcing it, it's more predictable.
        $exc->set_location('my_file.php', '{unknown function}', 42);

        $to_string =
            function(string $msg) use(&$exc) : string
        {
            $exc->message($msg);
            return $exc->__toString();
        };

        assert($to_string("Hello World!")    ===
            "MockException Hello World! in my_file.php(42)\n");

        assert($to_string("Hello World!\n")  ===
            "MockException\n".
            "Messages:\n".
            "  my_file.php(42): Hello World!\n".
            "\n".
            "\n".
            "Trace:\n");

        assert($to_string("Hello\nWorld!") ===
            "MockException\n".
            "Messages:\n".
            "  my_file.php(42): Hello\n".
            "                   World!\n".
            "\n".
            "Trace:\n");

        assert($to_string("Hello\nWorld!\n") ===
            "MockException\n".
            "Messages:\n".
            "  my_file.php(42): Hello\n".
            "                   World!\n".
            "\n".
            "\n".
            "Trace:\n");

        $exc->caller_context_file('foo.php');
        $exc->caller_context_func('a');
        $exc->caller_context_line(137);
        $exc->say_before_message("Pre-message context.");

        // @phpstan-ignore function.impossibleType, identical.alwaysFalse
        assert($to_string("Hello World!")    ===
            "MockException\n".
            "Messages:\n".
            "  foo.php(137):    Pre-message context.\n".
            "  my_file.php(42): Hello World!  (thrown from here)\n".
            "\n".
            "Trace:\n");

        // The extra newline in $message technically counts as multiline.
        // @phpstan-ignore function.impossibleType, identical.alwaysFalse
        assert($to_string("Hello World!\n")  ===
            "MockException\n".
            "Messages:\n".
            "  foo.php(137):    Pre-message context.\n".
            "  my_file.php(42): (<- thrown from here)\n".
            "                   Hello World!\n".
            "\n".
            "\n".
            "Trace:\n");

        // @phpstan-ignore function.impossibleType, identical.alwaysFalse
        assert($to_string("Hello\nWorld!") ===
            "MockException\n".
            "Messages:\n".
            "  foo.php(137):    Pre-message context.\n".
            "  my_file.php(42): (<- thrown from here)\n".
            "                   Hello\n".
            "                   World!\n".
            "\n".
            "Trace:\n");

        // @phpstan-ignore function.impossibleType, identical.alwaysFalse
        assert($to_string("Hello\nWorld!\n") ===
            "MockException\n".
            "Messages:\n".
            "  foo.php(137):    Pre-message context.\n".
            "  my_file.php(42): (<- thrown from here)\n".
            "                   Hello\n".
            "                   World!\n".
            "\n".
            "\n".
            "Trace:\n");


        $exc->caller_context_file('bar.php');
        $exc->caller_context_func('b');
        $exc->caller_context_line(23);
        $exc->say_after_message("Post-message context.");

        // @phpstan-ignore function.impossibleType, identical.alwaysFalse
        assert($to_string("Hello World!")    ===
            "MockException\n".
            "Messages:\n".
            "  foo.php(137):    Pre-message context.\n".
            "  my_file.php(42): Hello World!  (thrown from here)\n".
            "  bar.php(23):     Post-message context.\n".
            "\n".
            "Trace:\n");

        // The extra newline in $message technically counts as multiline.
        // @phpstan-ignore function.impossibleType, identical.alwaysFalse
        assert($to_string("Hello World!\n")  ===
            "MockException\n".
            "Messages:\n".
            "  foo.php(137):    Pre-message context.\n".
            "  my_file.php(42): (<- thrown from here)\n".
            "                   Hello World!\n".
            "\n".
            "  bar.php(23):     Post-message context.\n".
            "\n".
            "Trace:\n");

        // @phpstan-ignore function.impossibleType, identical.alwaysFalse
        assert($to_string("Hello\nWorld!") ===
            "MockException\n".
            "Messages:\n".
            "  foo.php(137):    Pre-message context.\n".
            "  my_file.php(42): (<- thrown from here)\n".
            "                   Hello\n".
            "                   World!\n".
            "  bar.php(23):     Post-message context.\n".
            "\n".
            "Trace:\n");

        // @phpstan-ignore function.impossibleType, identical.alwaysFalse
        assert($to_string("Hello\nWorld!\n") ===
            "MockException\n".
            "Messages:\n".
            "  foo.php(137):    Pre-message context.\n".
            "  my_file.php(42): (<- thrown from here)\n".
            "                   Hello\n".
            "                   World!\n".
            "\n".
            "  bar.php(23):     Post-message context.\n".
            "\n".
            "Trace:\n");

        $exc->clear_context_messages();

        $exc->caller_context_file('bar.php');
        $exc->caller_context_func('b');
        $exc->caller_context_line(23);
        $exc->say_after_message("Post-message context.");

        // @phpstan-ignore function.impossibleType, identical.alwaysFalse
        assert($to_string("Hello World!")    ===
            "MockException\n".
            "Messages:\n".
            "  my_file.php(42): Hello World!  (thrown from here)\n".
            "  bar.php(23):     Post-message context.\n".
            "\n".
            "Trace:\n");

        // The extra newline in $message technically counts as multiline.
        // @phpstan-ignore function.impossibleType, identical.alwaysFalse
        assert($to_string("Hello World!\n")  ===
            "MockException\n".
            "Messages:\n".
            "  my_file.php(42): (<- thrown from here)\n".
            "                   Hello World!\n".
            "\n".
            "  bar.php(23):     Post-message context.\n".
            "\n".
            "Trace:\n");

        // @phpstan-ignore function.impossibleType, identical.alwaysFalse
        assert($to_string("Hello\nWorld!") ===
            "MockException\n".
            "Messages:\n".
            "  my_file.php(42): (<- thrown from here)\n".
            "                   Hello\n".
            "                   World!\n".
            "  bar.php(23):     Post-message context.\n".
            "\n".
            "Trace:\n");

        // @phpstan-ignore function.impossibleType, identical.alwaysFalse
        assert($to_string("Hello\nWorld!\n") ===
            "MockException\n".
            "Messages:\n".
            "  my_file.php(42): (<- thrown from here)\n".
            "                   Hello\n".
            "                   World!\n".
            "\n".
            "  bar.php(23):     Post-message context.\n".
            "\n".
            "Trace:\n");

        $exc->caller_context_file('foo.php');
        $exc->caller_context_func('a');
        $exc->caller_context_line(137);
        $exc->say_before_message("Pre-message context.");

        // @phpstan-ignore function.impossibleType, identical.alwaysFalse
        assert($to_string("Hello World!")    ===
            "MockException\n".
            "Messages:\n".
            "  foo.php(137):    Pre-message context.\n".
            "  my_file.php(42): Hello World!  (thrown from here)\n".
            "  bar.php(23):     Post-message context.\n".
            "\n".
            "Trace:\n");

        // The extra newline in $message technically counts as multiline.
        // @phpstan-ignore function.impossibleType, identical.alwaysFalse
        assert($to_string("Hello World!\n")  ===
            "MockException\n".
            "Messages:\n".
            "  foo.php(137):    Pre-message context.\n".
            "  my_file.php(42): (<- thrown from here)\n".
            "                   Hello World!\n".
            "\n".
            "  bar.php(23):     Post-message context.\n".
            "\n".
            "Trace:\n");

        // @phpstan-ignore function.impossibleType, identical.alwaysFalse
        assert($to_string("Hello\nWorld!") ===
            "MockException\n".
            "Messages:\n".
            "  foo.php(137):    Pre-message context.\n".
            "  my_file.php(42): (<- thrown from here)\n".
            "                   Hello\n".
            "                   World!\n".
            "  bar.php(23):     Post-message context.\n".
            "\n".
            "Trace:\n");

        // @phpstan-ignore function.impossibleType, identical.alwaysFalse
        assert($to_string("Hello\nWorld!\n") ===
            "MockException\n".
            "Messages:\n".
            "  foo.php(137):    Pre-message context.\n".
            "  my_file.php(42): (<- thrown from here)\n".
            "                   Hello\n".
            "                   World!\n".
            "\n".
            "  bar.php(23):     Post-message context.\n".
            "\n".
            "Trace:\n");

        $exc->caller_context_file('abc.php');
        $exc->caller_context_func('c');
        $exc->caller_context_line(4990);
        $exc->say_before_message(fn() => "More pre-message context.");

        // @phpstan-ignore function.impossibleType, identical.alwaysFalse
        assert($to_string("Hello\nWorld!") ===
            "MockException\n".
            "Messages:\n".
            "  abc.php(4990):   More pre-message context.\n".
            "  foo.php(137):    Pre-message context.\n".
            "  my_file.php(42): (<- thrown from here)\n".
            "                   Hello\n".
            "                   World!\n".
            "  bar.php(23):     Post-message context.\n".
            "\n".
            "Trace:\n");

        $exc->caller_context_file('asdf/qwer.php');
        $exc->caller_context_func('d');
        $exc->caller_context_line(47);
        $exc->say_after_message(fn() => "More\npost-message\ncontext.\n");

        // @phpstan-ignore function.impossibleType, identical.alwaysFalse
        assert($to_string("Hello\nWorld!") ===
            "MockException\n".
            "Messages:\n".
            "  abc.php(4990):   More pre-message context.\n".
            "  foo.php(137):    Pre-message context.\n".
            "  my_file.php(42): (<- thrown from here)\n".
            "                   Hello\n".
            "                   World!\n".
            "  bar.php(23):     Post-message context.\n".
            "  qwer.php(47):    More\n".
            "                   post-message\n".
            "                   context.\n".
            "\n".
            "\n".
            "Trace:\n");

        $exc->caller_context_file('P/Q/MyClass.php');
        $exc->caller_context_func('e');
        $exc->caller_context_line(420);
        $exc->say_before_message(fn() => "More\npre-message\ncontext.\n");

        // @phpstan-ignore function.impossibleType, identical.alwaysFalse
        assert($to_string("Hello\nWorld!") ===
            "MockException\n".
            "Messages:\n".
            "  MyClass.php(420): More\n".
            "                    pre-message\n".
            "                    context.\n".
            "\n".
            "  abc.php(4990):    More pre-message context.\n".
            "  foo.php(137):     Pre-message context.\n".
            "  my_file.php(42):  (<- thrown from here)\n".
            "                    Hello\n".
            "                    World!\n".
            "  bar.php(23):      Post-message context.\n".
            "  qwer.php(47):     More\n".
            "                    post-message\n".
            "                    context.\n".
            "\n".
            "\n".
            "Trace:\n");

        $exc->clear_context_messages();

        $exc->set_location('pitcher.php', '{unknown function}', 42);
        $exc->caller_context_file('batter.php');
        $exc->caller_context_func('C');
        $exc->caller_context_line(67);
        $exc->say_before_message("Function C (4)");

        // @phpstan-ignore function.impossibleType, identical.alwaysFalse
        assert($to_string("Hello World!")    ===
            "MockException\n".
            "Messages:\n".
            "  batter.php(67):  Function C (4)\n".
            "  pitcher.php(42): Hello World!  (thrown from here)\n".
            "\n".
            "Trace:\n");

        $exc->caller_context_file('batter.php');
        $exc->caller_context_func('C');
        $exc->caller_context_line(82);
        $exc->say_before_message("Function C (5)");

        // @phpstan-ignore function.impossibleType, identical.alwaysFalse
        assert($to_string("Hello World!")    ===
            "MockException\n".
            "Messages:\n".
            "  batter.php(67):  Function C (4)\n".
            "  batter.php(82):  Function C (5)\n".
            "  pitcher.php(42): Hello World!  (thrown from here)\n".
            "\n".
            "Trace:\n");

        $exc->caller_context_file('catcher.php');
        $exc->caller_context_func('B');
        $exc->caller_context_line(537);
        $exc->say_before_message("Function B (3)");

        // @phpstan-ignore function.impossibleType, identical.alwaysFalse
        assert($to_string("Hello World!")    ===
            "MockException\n".
            "Messages:\n".
            "  catcher.php(537): Function B (3)\n".
            "  batter.php(67):   Function C (4)\n".
            "  batter.php(82):   Function C (5)\n".
            "  pitcher.php(42):  Hello World!  (thrown from here)\n".
            "\n".
            "Trace:\n");

        $exc->caller_context_file('catcher.php');
        $exc->caller_context_func('A');
        $exc->caller_context_line(589);
        $exc->say_before_message("Function A (2)");

        // @phpstan-ignore function.impossibleType, identical.alwaysFalse
        assert($to_string("Hello World!")    ===
            "MockException\n".
            "Messages:\n".
            "  catcher.php(589): Function A (2)\n".
            "  catcher.php(537): Function B (3)\n".
            "  batter.php(67):   Function C (4)\n".
            "  batter.php(82):   Function C (5)\n".
            "  pitcher.php(42):  Hello World!  (thrown from here)\n".
            "\n".
            "Trace:\n");

        $exc->caller_context_file('catcher.php');
        $exc->caller_context_func('A');
        $exc->caller_context_line(556);
        $exc->say_before_message("Function A (1)");

        // @phpstan-ignore function.impossibleType, identical.alwaysFalse
        assert($to_string("Hello World!")    ===
            "MockException\n".
            "Messages:\n".
            "  catcher.php(556): Function A (1)\n".
            "  catcher.php(589): Function A (2)\n".
            "  catcher.php(537): Function B (3)\n".
            "  batter.php(67):   Function C (4)\n".
            "  batter.php(82):   Function C (5)\n".
            "  pitcher.php(42):  Hello World!  (thrown from here)\n".
            "\n".
            "Trace:\n");
    }

    /**
    * @param      IKickbackThrowableRaw             $exc
    * @param      ?array<string|\Closure():string>  $messages
    * @param-out  array<string|\Closure():string>   $messages
    * @param      ?array<string>                    $msg_loc_path
    * @param-out  array<string>                     $msg_loc_path
    * @param      ?array<string>                    $msg_loc_func
    * @param-out  array<string>                     $msg_loc_func
    * @param      ?array<int>                       $msg_loc_line
    * @param-out  array<int>                        $msg_loc_line
    * @param      ?array<string>                    $msg_loc_full
    * @param-out  array<string>                     $msg_loc_full
    * @param      string|\Closure():string          $msg
    * @param      bool                              $prepend
    * @param      ?string                           $in_file
    * @param      ($in_file is string ? string : (?string)
    *             )                                 $in_function
    * @param      int                               $at_line
    * @param      int<0,max>                        $at_trace_depth
    * @param      ?kkdebug_backtrace_paranoid_a     $trace
    *
    * @phpstan-impure
    * @throws void
    */
    public static function add_context_message(
        IKickbackThrowableRaw  $exc,
        ?array                 &$messages,
        ?array                 &$msg_loc_path,
        ?array                 &$msg_loc_func,
        ?array                 &$msg_loc_line,
        ?array                 &$msg_loc_full,
        string|\Closure        $msg,
        bool                   $prepend,
        ?string                $in_file,
        ?string                $in_function,
        int                    $at_line,
        int                    $at_trace_depth,
        ?array                 $trace
    ) : void
    {
        if ( !isset($messages) ) {
            $messages     = [];
            $msg_loc_path = [];
            $msg_loc_func = [];
            $msg_loc_line = [];
            $msg_loc_full = [];

            $exc->populate_message_prefix();
        }
        assert(isset($msg_loc_path));
        assert(isset($msg_loc_func));
        assert(isset($msg_loc_line));
        assert(isset($msg_loc_full));

        // This if-condition means the following:
        // If the caller hasn't explicitly specified the file/func/line
        // information (or specified that it be acquired from \debug_backtrace),
        // AND the exception is an IMockPHPException (we're unittesting),
        // then use the `caller_context_*()` functions to get file/func/line
        // information.
        if (($exc instanceof IMockPHPException__ConfigAccessors)
        && !isset($trace) && !isset($in_file) && $at_line === \PHP_INT_MIN) {
            // During testing, `caller_context_file` and `caller_context_line`
            // are convenient because they retain state between multiple
            // tests which allows us to use the same file+line as a reference.
            $path = $exc->caller_context_file();
            $func = $exc->caller_context_func();
            $line = $exc->caller_context_line();
        } else {
            // Normal path.

            // The $in_function parameter is mandatory/required if an explicit
            // file name/path is provided.
            // @phpstan-ignore isset.variable
            assert((isset($in_file) && isset($in_function)) || !isset($in_file));

            Location::process_info(
                $in_file, $in_function, $at_line, $at_trace_depth, $trace);

            $path = $in_file;
            $func = $in_function;
            $line = $at_line;
        }

        $full = Misc::calculate_message_line_prefix($path, $line);

        // Logic to ensure that `say_before_message` lines from the
        // same function will be printed in the same order as they appear
        // (top-to-bottom) within that function.
        $insert_at = \PHP_INT_MAX;
        $len = \count($messages);
        if ($prepend && (0 < $len)) {
            $prev = $len-1;
            while( ($prev >= 0)
            &&  ($msg_loc_path[$prev] === $path)
            &&  ($msg_loc_func[$prev] === $func)
            &&  ($msg_loc_line[$prev]  <  $line)
            )
            {
                $insert_at = $prev;
                $prev--;
            }
        }

        if($insert_at < \PHP_INT_MAX) {
            \array_splice($messages,     $insert_at, 0, $msg);
            \array_splice($msg_loc_path, $insert_at, 0, $path);
            \array_splice($msg_loc_func, $insert_at, 0, $func);
            \array_splice($msg_loc_line, $insert_at, 0, $line);
            \array_splice($msg_loc_full, $insert_at, 0, $full);
        } else {
            $messages[]     = $msg;
            $msg_loc_path[] = $path;
            $msg_loc_func[] = $func;
            $msg_loc_line[] = $line;
            $msg_loc_full[] = $full;
        }
    }

    /**
    * @throws void
    */
    private static function widen_to_max_padding(int &$padding,  string $prefix) : void
    {
        $padding_to_test = \strlen($prefix);
        if ( $padding < $padding_to_test ) {
            $padding = $padding_to_test;
        }
    }

    /**
    * @param  array<string>   $msg_loc_full
    *
    * @throws void
    */
    private static function widen_to_max_padding_from_prefix_list(int &$padding,  array $msg_loc_full) : void
    {
        $trace = \debug_backtrace();
        $len = \count($msg_loc_full);
        for($i = 0; $i < $len; $i++) {
            self::widen_to_max_padding($padding, $msg_loc_full[$i]);
        }
    }

    /**
    * @param  array<string>     $prepend_msg_loc_full
    * @param  array<string>     $append_msg_loc_full
    * @phpstan-pure
    * @throws void
    */
    public static function calculate_context_message_padding(
        ?string    $main_message_prefix,
        ?array     $prepend_msg_loc_full,
        ?array     $append_msg_loc_full
    ) : int
    {
        $padding = 0;

        if (isset($prepend_msg_loc_full)) {
            self::widen_to_max_padding_from_prefix_list(
                $padding, $prepend_msg_loc_full);
        }

        if (isset($append_msg_loc_full)) {
            self::widen_to_max_padding_from_prefix_list(
                $padding, $append_msg_loc_full);
        }

        if (isset($main_message_prefix)) {
            self::widen_to_max_padding(
                $padding, $main_message_prefix);
        }

        return $padding;
    }

    /**
    * @param  null|string|callable():?string  $message
    * @phpstan-impure
    * @throws void
    */
    public static function emit_context_lines_from_message(
        string                &$strbuf,
        string                $blank_prefix, // should already include $indent_str
        int                   $padding,      // should equal (\strlen($blank_prefix) - \strlen($indent_str))
        string                $indent_str,   // only for use with $padding+$prefix
        string                $prefix,       // e.g.: "$file($line): "
        null|string|callable  $message,
        string                $first_line_suffix,
        string                $end_char
    ) : int
    {
        // Should there ever come to be an indenting-stream method or class
        // in the rest of the framework, _don't use it here_, even if
        // the functionality largely overlaps.
        //
        // It is good that this code relies on almost nothing:
        // If there are no dependencies, then there is no risk of dependencies
        // throwing exceptions during an exception handler in the midst of
        // an uncaught exception.
        //
        // It also makes the exception handling mechanism less fragile:
        // this code works in a very certain way, and if it ends up with
        // a dependency that later has a regression or a change in behavior,
        // then it not only breaks a program's display output, it would
        // break a program's _error handling ability_. We don't want that,
        // so this code should remain dependency-free (and hopefully
        // it won't need to change much, either).
        //
        // Notably: it might be worthwhile to use lightweight dependencies
        // to implement colored text output. For example, this would likely
        // involve using the \Kickback\Common\IO\Terminal\ANSI class
        // to retrieve color codes. HTML output might end up with another
        // set of reference values elsewhere.

        // Even though the context interface in general doesn't allow nulls,
        // we handle them here regardless, just in case someone returns
        // null and doesn't check their static analyser (ex: PHPStan).
        if ( is_null($message) )     { $message = ''; }
        if ( is_callable($message) ) { $message = $message(); }
        if ( is_null($message) )     { $message = ''; }

        $len = \strlen($message);
        $line_len = \strcspn($message, "\n");
        // $str_len     = \strval($len);
        // $str_line_hi = \strval($line_len);
        // echo "emit: '$message';  hi = $str_line_hi;  len = $str_len\n";
        if ( $line_len === $len ) {
            // Single-line: we must provide an additional \n at the end.
            // (or whatever $end_char is set to)
            $strbuf .= \sprintf('%s%-*s%s%s%s',
                $indent_str, $padding, $prefix, $message,
                $first_line_suffix, $end_char);
            return 1;
        }

        // Multi-line: use the input's own "\n" characters when possible
        // to avoid additional string concatenations/allocations.

        // First line.
        $line_count = 1;
        $line_start = 0;
        $strbuf .= \sprintf("%s%-*s%s%s\n",
            $indent_str, $padding, $prefix,
            \substr($message, $line_start, $line_len),
            $first_line_suffix);
        $line_len++;

        // Second line up through second-to-last line.
        while(true)
        {
            $line_start += $line_len;
            $line_len = \strcspn($message, "\n", $line_start);
            if ( ($line_start + $line_len) === $len ) {
                break; // Stop just before last line.
            }

            $line_count++;
            if ( 0 === $line_len ) {
                // Empty line : don't emit any prefix or message.
                $line_len++;
                $strbuf .= "\n";
            } else {
                // Regular line
                $line_len++;
                $strbuf .= $blank_prefix;
                $strbuf .= \substr($message, $line_start, $line_len);
            }
        }

        // Last line
        $line_count++;
        $have_printable_end_chars = (0 < \strlen(\rtrim($end_char)));
        if ( 0 < $line_len || $have_printable_end_chars ) {
            // Regular line
            $strbuf .= $blank_prefix;
            $strbuf .= \substr($message, $line_start, $line_len);
        }
        // else {
        //       Empty line : don't emit any prefix or message.
        // }
        if ( 0 < \strlen($end_char) ) {
            $strbuf .= $end_char;
        }

        return $line_count;
    }

    // Currently used to make `emit_context_lines_from_message` more testable.
    private static function message_to_context_lines(
        string                $blank_prefix, // should already include $indent_str
        int                   $padding,      // should equal (\strlen($prefix) === (\strlen($blank_prefix) - \strlen($indent_str)))
        string                $indent_str,   // only for use with $padding+$prefix
        string                $prefix,       // e.g.: "$file($line): "
        null|string|callable  $message,
        string                $first_line_suffix,
        string                $end_char
    ) : string
    {
        $strbuf = '';
        self::emit_context_lines_from_message(
            $strbuf, $blank_prefix, $padding, $indent_str,
            $prefix, $message, $first_line_suffix, $end_char);
        return $strbuf;
    }

    private static function unittest_message_to_context_lines() : void
    {
        echo("  ".__FUNCTION__."()\n");

        $blp = '##*****'; // blank_prefix
        $ind = '..';      // indent_str
        $pfx  = 'pfx: ';  // prefix
        $pad = \strlen($pfx); // padding

        $msg2context = fn($msg, $first_line_suffix, $end_char) =>
            self::message_to_context_lines($blp,$pad,$ind,$pfx, $msg, $first_line_suffix, $end_char);

        assert($msg2context('',   '', '')     === '..pfx: ');
        assert($msg2context('',   '', "\n")   === "..pfx: \n");
        assert($msg2context('',   '', "EOS")  === "..pfx: EOS");
        assert($msg2context("\n", '', '')     === "..pfx: \n");
        assert($msg2context("\n", '', "\n")   === "..pfx: \n\n");
        assert($msg2context("\n", '', 'EOS')  === "..pfx: \n##*****EOS");
        assert($msg2context('hello!',   '', '')    === '..pfx: hello!');
        assert($msg2context('hello!',   '', "\n")  === "..pfx: hello!\n");
        assert($msg2context('hello!',   '', 'EOS') === "..pfx: hello!EOS");
        assert($msg2context("hello!\n", '', '')    === "..pfx: hello!\n");
        assert($msg2context("hello!\n", '', "\n")  === "..pfx: hello!\n\n");
        assert($msg2context("hello!\n", '', 'EOS') === "..pfx: hello!\n##*****EOS");
        assert($msg2context("hello\nworld!",   '', '')    === "..pfx: hello\n##*****world!");
        assert($msg2context("hello\nworld!",   '', "\n")  === "..pfx: hello\n##*****world!\n");
        assert($msg2context("hello\nworld!",   '', 'EOS') === "..pfx: hello\n##*****world!EOS");
        assert($msg2context("hello\nworld!\n", '', '')    === "..pfx: hello\n##*****world!\n");
        assert($msg2context("hello\nworld!\n", '', "\n")  === "..pfx: hello\n##*****world!\n\n");
        assert($msg2context("hello\nworld!\n", '', 'EOS') === "..pfx: hello\n##*****world!\n##*****EOS");
        assert($msg2context("More\npost-message\ncontext.\n", '', '') === "..pfx: More\n##*****post-message\n##*****context.\n");
        assert($msg2context("More\npost-message\ncontext.\n", '', "\n") === "..pfx: More\n##*****post-message\n##*****context.\n\n");
        assert($msg2context("More\npost-message\ncontext.\n", '', "EOS") === "..pfx: More\n##*****post-message\n##*****context.\n##*****EOS");
    }

    public static function unittests() : void
    {
        $class_fqn = self::class;
        echo("Running `$class_fqn::unittests()`\n");

        self::unittest_message_to_context_lines();
        self::unittest_ThrowableWithContextMessages_toString();

        echo("  ... passed.\n\n");
    }
}
?>
