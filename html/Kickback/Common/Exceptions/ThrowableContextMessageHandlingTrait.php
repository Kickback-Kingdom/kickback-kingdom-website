<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions;

use Kickback\Common\Exceptions\ThrowableWithContextMessages;
use Kickback\Common\Exceptions\Internal\DefaultMethods;
use Kickback\Common\Exceptions\Internal\IKickbackThrowableRaw;
use Kickback\Common\Exceptions\Internal\MockPHPException;
use Kickback\Common\Exceptions\Internal\IMockPHPException;
use Kickback\Common\Exceptions\Internal\IMockPHPException__ConfigAccessors;

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
* @phpstan-require-implements  \Kickback\Common\Exceptions\ThrowableWithContextMessages
*/
trait ThrowableContextMessageHandlingTrait
{
    // NOTE: Kickback\Common\Exceptions\Internal\DefaultMethods
    // contains a comment that explains PHP's exception printing.

    private ?string  $kk_main_message_prefix_ = null;

    /** @var ?array<string|\Closure():string> */
    private ?array $kk_prepend_messages_ = null;

    /** @var ?array<string> */
    private ?array $kk_prepend_msg_loc_full_ = null;

    /**
    * @see  ThrowableWithContextMessages::say_before_message
    * @see  ThrowableWithContextMessages::say_after_message
    *
    * @param      string|\Closure():string          $msg
    *
    * @phpstan-impure
    * @throws void
    */
    public function say_before_message(string|\Closure $msg) : void
    {
        // Optimization:
        // We only care about file+line from the caller, so we can
        // avoid taking too much memory/time with debug_backtrace
        // by asking it to leave arguments out, and to only grab
        // the frames that we need.
        // @var kkdebug_backtrace_a
        $trace = null;
        if ( !($this instanceof IMockPHPException) ) {
            $trace = \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        }
        ThrowableContextMessageHandling::add_context_message(
            $this, $msg, $trace,
            $this->kk_main_message_prefix_,
            $this->kk_prepend_messages_,
            $this->kk_prepend_msg_loc_full_);
    }

    /** @var ?array<string|\Closure():string> */
    private ?array $kk_append_messages_ = null;

    /** @var ?array<string> */
    private ?array $kk_append_msg_loc_full_ = null;

    /**
    * @see ThrowableWithContextMessages::say_before_message
    * @see ThrowableWithContextMessages::say_after_message
    *
    * @param      string|\Closure():string          $msg
    *
    * @phpstan-impure
    * @throws void
    */
    public function say_after_message(string|\Closure $msg) : void
    {
        // Optimization:
        // We only care about file+line from the caller, so we can
        // avoid taking too much memory/time with debug_backtrace
        // by asking it to leave arguments out, and to only grab
        // the frames that we need.
        // @var kkdebug_backtrace_a
        $trace = null;
        if ( !($this instanceof IMockPHPException) ) {
            $trace = \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        }
        ThrowableContextMessageHandling::add_context_message(
            $this, $msg, $trace,
            $this->kk_main_message_prefix_,
            $this->kk_append_messages_,
            $this->kk_append_msg_loc_full_);
    }

    public final function clear_context_messages() : void
    {
            $this->kk_main_message_prefix_    = null;
            $this->kk_append_messages_        = null;
            $this->kk_append_msg_loc_full_    = null;

            $this->kk_main_message_prefix_    = null;
            $this->kk_prepend_messages_       = null;
            $this->kk_prepend_msg_loc_full_   = null;

            // The `getMessage()` results can't be changed on
            // things inheriting from `\Exception`, so we can
            // just assume that this won't change.
            //
            // However, the mock exception doesn't need to be
            // _actually_ throwable/catchable, so it can have
            // its message changed repeatedly. So it's a good
            // idea to reset this metadata while reseting
            // everything else, as it will provide more
            // accurate testing.
            if ( $this instanceof MockPHPException ) {
                $this->kk_main_message_prefix_ = null;
            }
    }

    // Perhaps this would be better as a `public` function,
    // but right now we'll wait to see if there's a good use-case
    // for it, and also wait for the code to mature a bit,
    // before we make that promise.
    /**
    * @phpstan-pure
    * @throws void
    */
    protected final function have_context_messages() : bool {
        return (isset($this->kk_prepend_messages_) && 0 < \count($this->kk_prepend_messages_))
            || (isset($this->kk_append_messages_)  && 0 < \count($this->kk_append_messages_));
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

        $this_msg = $this->getMessage();
        $is_multiline_msg = \str_contains($this_msg, "\n");
        $have_context_messages = $this->have_context_messages();
        if (!$have_context_messages && !$is_multiline_msg)
        {
            if ( $this instanceof IMockPHPException ) {
                return DefaultMethods::toString($this, $this->mock_class_fqn());
            }

            $parent = \get_parent_class($this);
            if ( $parent !== false ) {
                return parent::__toString();
            } else {
                return DefaultMethods::toString($this);
            }
        }

        // This is possible if `say_*_message` methods are never called,
        // yet the exception just has a multi-line `$this->getMessage()`.
        // The multi-line situation will still trigger context printing,
        // even if we don't have other context messages.
        // (Because this printer is MUCH better at handling multi-line!)
        if (!isset($this->kk_main_message_prefix_)) {
            // It'd be nice to assign this to `$this->kk_main_message_prefix_`
            // so that it doesn't have to be recalculated, but
            // we don't want to invalidate the promise that this
            // method is `pure`.
            $main_message_prefix =
                ThrowableContextMessageHandling::
                    calculate_context_message_line_prefix(
                        $this->getFile(), $this->getLine());
        } else {
            $main_message_prefix = $this->kk_main_message_prefix_;
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

        // Context messages that go BEFORE $this->getMessage().
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

        // Context messages that go AFTER $this->getMessage().
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
* @phpstan-import-type  kkdebug_frame_a      from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
* @phpstan-import-type  kkdebug_backtrace_a  from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
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
        $exc->file('my_file.php');
        $exc->line(42);

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
    }

    /**
    * @phpstan-pure
    * @throws void
    */
    public static function calculate_context_message_line_prefix(
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
    * @param      IKickbackThrowableRaw             $exc
    * @param      string|\Closure():string          $msg
    * @param      kkdebug_backtrace_a               $trace
    * @param      ?array<string|\Closure():string>  $messages
    * @param-out  array<string|\Closure():string>   $messages
    * @param      ?array<string>                    $msg_loc_full
    * @param-out  array<string>                     $msg_loc_full
    *
    * @phpstan-impure
    * @throws void
    */
    public static function add_context_message(
        IKickbackThrowableRaw  $exc,
        string|\Closure        $msg,
        ?array                 $trace,
        ?string                &$main_message_prefix,
        ?array                 &$messages,
        ?array                 &$msg_loc_full
    ) : void
    {

        if ( !isset($messages) ) {
            $messages     = [];
            //$msg_loc_path = [];
            //$msg_loc_line = [];
            $msg_loc_full = [];

            if ( !isset($main_message_prefix) ) {
                $main_message_prefix =
                    self::calculate_context_message_line_prefix(
                        $exc->getFile(), $exc->getLine());
            }
        }

        $messages[] = $msg;

        if (isset($trace)) {
            assert(2 <= \count($trace));
            $frame = $trace[1];
            $path = \array_key_exists('file',$frame) ? $frame['file'] : null;
            $line = \array_key_exists('line',$frame) ? $frame['line'] : null;
        } else {
            assert($exc instanceof IMockPHPException__ConfigAccessors);
            $path = $exc->caller_context_file();
            $line = $exc->caller_context_line();
        }

        $msg_loc_full[] =
            self::calculate_context_message_line_prefix($path, $line);
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
