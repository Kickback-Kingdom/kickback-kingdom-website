<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions\Reporting;

use Kickback\Common\Exceptions\Internal\Misc; // process_location_info_into

use Kickback\Common\Exceptions\IKickbackException;
use Kickback\Common\Exceptions\KickbackException;
use Kickback\Common\Exceptions\IKickbackThrowable;
use Kickback\Common\Exceptions\KickbackThrowable;
use Kickback\Common\Exceptions\IMockException;
use Kickback\Common\Exceptions\MockException;

/**
* @phpstan-import-type  kkdebug_frame_a      from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
* @phpstan-import-type  kkdebug_backtrace_a  from \Kickback\Common\Exceptions\DebugBacktraceAliasTypes
*/
final class Report
{
    // Optimization:
    // We can use a "blank" instance as a sentinel value when
    // executing code that requires error tracking or message reporting.
    // This allows that code to avoid having to allocate a `Report` object
    // JUST to check for errors; instead, we allocate that lazily, only
    // generating a `Report` object if there are any errors.
    // If there aren't any errors, then this type "has unity", because
    // every blank `Report` will be indistinguishable from the next
    // (they are all the same algebraic value), and no allocations
    // are necessary to acquire "normal" behavior.
    private static ?self $blank_ = null;

    /**
    * This singleton represents the "blank" accumulator.
    *
    * It is useful because most code will only ever need a blank accumulator,
    * and we don't need to allocate more than one of it, because they would
    * all be the same anyways.
    *
    * So when a conditional method like `::enforce` is called, it will only
    * allocate a `Report` if there is actually a message to add.
    *
    * The `blank` instance itself is read-only (immutable) and should never
    * be modified. The intent is that any situations requiring non-empty
    * accumulators will allocate a new accumulator and which will immediately
    * end up with one message in it.
    */
    public static function blank() : self {
        if (isset(self::$blank_)) {
            return self::$blank_;
        }
        self::$blank_ = new Report();
        return self::$blank_;
    }

    // This will be non-null if the caller opts to provide
    // an exception in advance of adding messages.
    // This is slightly more efficient because it eliminates
    // a copy: we don't need to move data into
    // the {$messages_,$files_,$funcs_,$lines_} arrays and THEN
    // call `->say_(before|after)` on an exception object,
    // we can instead just directly do the `->say_(before|after)`
    // step as the user provides messages.
    // Given how the `enforce` function works, this does
    // limit the caller's ability to provide arguments to
    // the exception's constructor.
    private IKickbackThrowable|IMockException|null  $exception_ = null;

    /**
    * Returns the Report's exception object.
    *
    * This will be `null` unless the Report object was given
    * an exception object during construction, or the `Report::enforce`
    * function was provided with an exception class name when
    * its condition evaluated to false.
    */
    public function exception() : IKickbackThrowable|IMockException|null {
        return $this->exception_;
    }

    /** @var ?array<string|\Closure():string> */
    private ?array $messages_ = null;

    /** @var ?array<string> */
    private ?array $files_    = null;

    /** @var ?array<string> */
    private ?array $funcs_    = null;

    /** @var ?array<int> */
    private ?array $lines_    = null;
    //private array $throwables_ = [];

    public function __construct(IKickbackThrowable|IMockException|null  $exc = null)
    {
        if (isset($exc)) {
            $this->exception_ = $exc;
        } else {
            $this->messages_ = [];
            $this->files_    = [];
            $this->funcs_    = [];
            $this->lines_    = [];
        }
    }

    /**
    * Place a generic message into the report.
    *
    * Usually this is to report some form of error, albeit without specifying
    * an exception class. The intent is that this message will become part
    * of a longer message that will be constructed and thrown
    * if the caller requests it.
    *
    * Although exception chaining is a possibility, it can be much cleaner
    * and easier to read an error report if a single exception is thrown,
    * and components of that error are intentionally formatted into a list
    * of things that (may) require correction.
    *
    * Hence why this approach is useful: there is often no reason to provide
    * additional exception objects when we only want to throw one.
    *
    * This method allows us to still provide useful fact-by-fact information
    * in such a list-like situation.
    *
    * @param  string|\Closure():string   $msg
    * @param  string|int                 $in_file_or_at_stack_depth
    * @param  ($in_file_or_at_stack_depth is string ? string : (?string)
    *         )                          $in_function
    * @param  int                        $at_line
    *
    * @phpstan-impure
    * @throws void
    */
    public final function msg(
        string|\Closure     $msg,
        string|int          $in_file_or_at_stack_depth = 0,
        ?string             $in_function = null,
        int                 $at_line = 0) : void
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
        if ( \is_int($in_file_or_at_stack_depth) )
        {
            // Capture desired frame from \debug_backtrace, if it is needed.
            $stack_depth = 2 + $in_file_or_at_stack_depth;
            $trace = \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $stack_depth);
        }

        // The $in_function parameter is mandatory/required if an explicit
        // file name/path is provided. (As opposed to a stack depth number
        // being provided, which is the default.)
        // @phpstan-ignore isset.variable
        assert((\is_string($in_file_or_at_stack_depth) && isset($in_function))
        || !\is_string($in_file_or_at_stack_depth));

        Misc::process_location_info_into(
            $trace, $in_file_or_at_stack_depth, $in_function, $at_line,
            $path, $func, $line);

        assert(isset($func));

        // Grow the list.
        if (isset($this->exception_)) {
            $this->exception_->say_before_message($msg, $path, $func, $line);
        } else {
            $this->messages_[] = $msg;
            $this->files_[] = $path;
            $this->funcs_[] = $func;
            $this->lines_[] = $line;
        }
    }
    /*
    * @param  (self|string)&  $report
    * @param  ($report is string ? string : string|(\Closure():string))  $msg
    */

    /**
    * Conditionally place a generic message into the report.
    *
    * Usually this is to report some form of error, albeit without specifying
    * an exception class. The intent is that this message will become part
    * of a longer message that will be constructed and thrown
    * if the caller requests it.
    *
    * Although exception chaining is a possibility, it can be much cleaner
    * and easier to read an error report if a single exception is thrown,
    * and components of that error are intentionally formatted into a list
    * of things that (may) require correction.
    *
    * Hence why this approach is useful: there is often no reason to provide
    * additional exception objects when we only want to throw one.
    *
    * This method allows us to still provide useful fact-by-fact information
    * in such a list-like situation.
    *
    * Conventional usage is to pass `Report::blank()` into the `$report`
    * parameter initially, OR provide the name of an exception to construct
    * once there is an error.
    *
    * The `$report` parameter will be replaced with a `Report` object
    * when `$condition` is false. This allows the caller to avoid performing
    * any allocations on the non-exceptional codepath (aside from priming
    * the `Report::blank()` instance once for the entire script/program execution).
    *
    * Example:
    * ```
    * $report = Report::blank();
    * Report::enforce($report, array_key_exists('foo',$_GET), 'No \'foo\' argument in HTTP request.');
    * if ( 0 < $report->count() ) {
    *     throw $report->generate_exception(MissingKeyException::class, 'HTTP request validation failed.');
    * }
    * ```
    *
    * Or equivalently:
    * ```
    * $report = MissingKeyException::class;
    * Report::enforce($report, array_key_exists('foo',$_GET), 'No \'foo\' argument in HTTP request.');
    * if ( 0 < $report->count() ) {
    *     throw $report->generate_exception(null, 'HTTP request validation failed.');
    * }
    * ```
    *
    * If a class-string is passed into `$report`, then it must be of a class
    * that inherits from KickbackThrowable (or MockException).
    *
    * @param  (self|class-string<KickbackThrowable|MockException>)&        $report
    * @param  bool                                                         $condition
    * @param  string|\Closure():string                                     $msg
    * @param  string|int                                                   $in_file_or_at_stack_depth
    * @param  ($in_file_or_at_stack_depth is string ? string : (?string))  $in_function
    * @param  int                                                          $at_line
    *
    * @phpstan-impure
    * @throws void
    */
    public static function enforce(
        self|string      &$report,
        bool             $condition,
        string|\Closure  $msg,
        string|int       $in_file_or_at_stack_depth = 0,
        ?string          $in_function = null,
        int              $at_line = 0
    ) : void
    {
        if ( $condition ) {
            return;
        }

        if (is_string($report)) {
            $exception_class_fqn = $report;
            $report = new Report(new $exception_class_fqn(null));
        } else
        if ($report === self::blank()) {
            $report = new Report();
        }
        $report->msg($msg, $in_file_or_at_stack_depth, $in_function, $at_line);
    }

    // Future directions?
    // This would provide the possibility of lazily-instantiating exceptions
    // if the accumulator is used to thrown something.
    //public function emsg(string $msg, string $exception_class_fqn, string|int $in_file_or_at_stack_depth = 0, int $at_line = 0) : void;

    public final function count() : int
    {
        if (isset($this->exception_)) {
            return $this->exception_->context_message_count();
        } else {
            assert(isset($this->messages_));
            return \count($this->messages_);
        }
    }

    /**
    * Generate an exception that can print this Report.
    *
    * The `$kickback_exception_class_fqn` is the name of the exception class
    * to use when generating the exception. It must be a KickbackThrowable
    * type, because the Report class relies on Kickback's context message
    * (`say_before_message`, `say_after_message`) features to print the report.
    *
    * If the Report already has an exception object (e.g. `isset($report->exception())`),
    * then `$kickback_exception_class_fqn` must be `null`, an empty string,
    * or must match the class name (fully qualified name, case-sensitive)
    * that was provided during the Report's construction (or through Report::enforce).
    *
    * @param  ?class-string<KickbackThrowable|MockException>  $kickback_exception_class_fqn
    * @param  ?string                                         $msg
    */
    public final function generate_exception(?string $kickback_exception_class_fqn, ?string $msg = null, mixed ...$args) : IKickbackThrowable|IMockException|null
    {
        // Ensure that we either have an existing exception,
        // or the caller provided a non-null and non-empty
        // class name to construct.
        // (Ignore smaller.alwaysTrue because PHPStan says
        // "Comparison operation "<" between 0 and int<1, max> is always true."
        // which is probably because of the `class-string` param constraint.)
        // @phpstan-ignore smaller.alwaysTrue
        assert((isset($kickback_exception_class_fqn) && (0 < \strlen($kickback_exception_class_fqn)))
            || isset($this->exception_));

        // Ensure that `$kickback_exception_class_fqn`
        // and `$this->exception_` agree (as needed).
        assert(!isset($this->exception_) ? true
        : (    !isset($kickback_exception_class_fqn)
        ||     (0 === \strlen($kickback_exception_class_fqn)) // @phpstan-ignore identical.alwaysFalse
        ||     ($kickback_exception_class_fqn === \get_class($this->exception_))
        ));

        // If `$this->exception_` is already populated/constructed,
        // then the caller should not provide constructor arguments.
        assert(!isset($this->exception_) || (0 === \count($args)));

        $len = $this->count();
        if ( $len === 0 ) {
            return null;
        }

        if ( isset($this->exception_) ) {
            $this->exception_->message($msg);
            return $this->exception_;
        }

        // Exception is not pre-populated and must actually be generated.
        assert(isset($this->messages_));
        assert(isset($this->files_));
        assert(isset($this->funcs_));
        assert(isset($this->lines_));

        if ( 0 < \count($args) ) {
            $exc = new $kickback_exception_class_fqn($msg, ...$args);
        } else {
            $exc = new $kickback_exception_class_fqn($msg);
        }
        assert($exc instanceof KickbackThrowable || $exc instanceof MockException);

        for ($i = $len-1; $i >= 0; $i--) {
            $msg  = $this->messages_[$i];
            $file = $this->files_[$i];
            $func = $this->funcs_[$i];
            $line = $this->lines_[$i];
            $exc->say_before_message($msg, $file, $func, $line);
        }

        return $exc;
    }

    private static function unittest_MessageAccumulator_construct_exception_early() : void
    {
        echo("  ".__FUNCTION__."()\n");

        $normalize_mock_exception =
            function(MockException &$mock_exception) : void
        {
            $mock_exception->trace([]); // We aren't testing trace output, so just don't emit it.
            $mock_exception->mock_class_fqn('MockException');
            $mock_exception->set_location('path/exc.php', 42);
        };

        // As of this writing, the `ThrowableWithAssignableFields`
        // trait/interface does not print the function name, so
        // it doesn't matter which one we choose. It DOES matter
        // that it's the same one every time, because that trait/interface
        // uses function-identity to determine message-ordering when
        // printing the exception messages.
        // So, in this case, we can just use __FUNCTION__.
        $func = __FUNCTION__;

        $report = MockException::class;

        $header = [
            'id'     => 0,
            'name'   => '',
            'foostr' => '',
            'barint' => 0
        ];

        $row = [
            'id'     => '7',
            'name'   => 'xyz',
            'foostr' => 6,
            'bar'    => 37
        ];

        // @phpstan-ignore function.alreadyNarrowedType
        assert($report === MockException::class);
        // @phpstan-ignore function.alreadyNarrowedType
        Report::enforce($report, \array_key_exists('id',$row),     'Required field \'id\' not found.',     'path/test.php', $func, 1);

        assert($report === MockException::class);
        // @phpstan-ignore function.alreadyNarrowedType
        Report::enforce($report, \array_key_exists('name',$row),   'Required field \'name\' not found.',   'path/test.php', $func, 2);

        assert($report === MockException::class);
        // @phpstan-ignore function.alreadyNarrowedType
        Report::enforce($report, \array_key_exists('foostr',$row), 'Required field \'foostr\' not found.', 'path/test.php', $func, 3);

        assert($report === MockException::class);
        // @phpstan-ignore function.impossibleType
        Report::enforce($report, \array_key_exists('barint',$row), 'Required field \'barint\' not found.', 'path/test.php', $func, 4);

        assert($report !== MockException::class);
        assert($report instanceof Report);

        $n_errors = $report->count();

        $exc = $report->generate_exception(null, "Validation failed.\n$n_errors error(s).");
        $normalize_mock_exception($exc);
        assert($exc->__toString() ===
            "MockException\n".
            "Messages:\n".
            "  test.php(4): Required field 'barint' not found.\n".
            "  exc.php(42): (<- thrown from here)\n".
            "               Validation failed.\n".
            "               1 error(s).\n".
            "\n".
            "Trace:\n");

        $id     = $row['id'];
        $name   = $row['name'];
        $foostr = $row['foostr'];
        // $barint is missing/mispelled in this scenario.
        $foostrstr = \strval($foostr);

        // @phpstan-ignore function.impossibleType
        Report::enforce($report, \is_int($id),        "Expected field 'id' to be an `int`, instead got '$id' of type `string`",         'path/test.php', $func, 5);

        // @phpstan-ignore function.alreadyNarrowedType
        Report::enforce($report, \is_string($name),   "Expected field 'name' to be a `string`, instead got \$name of type ???",         'path/test.php', $func, 6);

        // @phpstan-ignore function.impossibleType
        Report::enforce($report, \is_string($foostr), "Expected field 'foostr' to be a `string`, instead for $foostrstr of type `int`", 'path/test.php', $func, 7);

        $n_errors = $report->count();

        $exc = $report->generate_exception(null, "Validation failed.\n$n_errors error(s).");
        $normalize_mock_exception($exc);
        assert($exc->__toString() ===
            "MockException\n".
            "Messages:\n".
            "  test.php(4): Required field 'barint' not found.\n".
            "  test.php(5): Expected field 'id' to be an `int`, instead got '$id' of type `string`\n".
            "  test.php(7): Expected field 'foostr' to be a `string`, instead for $foostrstr of type `int`\n".
            "  exc.php(42): (<- thrown from here)\n".
            "               Validation failed.\n".
            "               3 error(s).\n".
            "\n".
            "Trace:\n");

        $undefined_fields = \array_diff_key($row, $header);

        $line = 10;
        foreach($undefined_fields as $key => $value) {
            $dbg_type = \get_debug_type($value);
            $value_str = \strval($value);
            Report::enforce($report, false, "Found unexpected field/column named `$key` in the row. Type is `$dbg_type`. Value is '$value_str'.", 'path/test.php', $func, $line);
            $line++;
        }

        $n_errors = $report->count();

        $exc = $report->generate_exception(null, "Validation failed.\n$n_errors error(s).");
        $normalize_mock_exception($exc);
        assert($exc->__toString() ===
            "MockException\n".
            "Messages:\n".
            "  test.php(4):  Required field 'barint' not found.\n".
            "  test.php(5):  Expected field 'id' to be an `int`, instead got '$id' of type `string`\n".
            "  test.php(7):  Expected field 'foostr' to be a `string`, instead for $foostrstr of type `int`\n".
            "  test.php(10): Found unexpected field/column named `bar` in the row. Type is `int`. Value is '37'.\n".
            "  exc.php(42):  (<- thrown from here)\n".
            "                Validation failed.\n".
            "                4 error(s).\n".
            "\n".
            "Trace:\n");
    }

    private static function unittest_MessageAccumulator_construct_exception_late() : void
    {
        echo("  ".__FUNCTION__."()\n");

        $normalize_mock_exception =
            function(MockException &$mock_exception) : void
        {
            $mock_exception->trace([]); // We aren't testing trace output, so just don't emit it.
            $mock_exception->mock_class_fqn('MockException');
            $mock_exception->set_location('path/exc.php', 42);
        };

        // As of this writing, the `ThrowableWithAssignableFields`
        // trait/interface does not print the function name, so
        // it doesn't matter which one we choose. It DOES matter
        // that it's the same one every time, because that trait/interface
        // uses function-identity to determine message-ordering when
        // printing the exception messages.
        // So, in this case, we can just use __FUNCTION__.
        $func = __FUNCTION__;

        // @var self
        $report = Report::blank();

        $header = [
            'id'     => 0,
            'name'   => '',
            'foostr' => '',
            'barint' => 0
        ];

        $row = [
            'id'     => '7',
            'name'   => 'xyz',
            'foostr' => 6,
            'bar'    => 37
        ];

        assert($report === Report::blank());
        $exc = $report->generate_exception(MockException::class, "There should be no errors right now.");
        assert(!isset($exc));

        assert($report === Report::blank());
        // @phpstan-ignore function.alreadyNarrowedType
        Report::enforce($report, \array_key_exists('id',$row),     'Required field \'id\' not found.',     'path/test.php', $func, 1);

        assert($report === Report::blank());
        // @phpstan-ignore function.alreadyNarrowedType
        Report::enforce($report, \array_key_exists('name',$row),   'Required field \'name\' not found.',   'path/test.php', $func, 2);

        assert($report === Report::blank());
        // @phpstan-ignore function.alreadyNarrowedType
        Report::enforce($report, \array_key_exists('foostr',$row), 'Required field \'foostr\' not found.', 'path/test.php', $func, 3);

        assert($report === Report::blank());
        // @phpstan-ignore function.impossibleType
        Report::enforce($report, \array_key_exists('barint',$row), 'Required field \'barint\' not found.', 'path/test.php', $func, 4);

        assert($report !== Report::blank());

        $n_errors = $report->count();

        $exc = $report->generate_exception(MockException::class, "Validation failed.\n$n_errors error(s).");
        $normalize_mock_exception($exc);
        assert($exc->__toString() ===
            "MockException\n".
            "Messages:\n".
            "  test.php(4): Required field 'barint' not found.\n".
            "  exc.php(42): (<- thrown from here)\n".
            "               Validation failed.\n".
            "               1 error(s).\n".
            "\n".
            "Trace:\n");

        $id     = $row['id'];
        $name   = $row['name'];
        $foostr = $row['foostr'];
        // $barint is missing/mispelled in this scenario.
        $foostrstr = \strval($foostr);

        // @phpstan-ignore function.impossibleType
        Report::enforce($report, \is_int($id),        "Expected field 'id' to be an `int`, instead got '$id' of type `string`",         'path/test.php', $func, 5);

        // @phpstan-ignore function.alreadyNarrowedType
        Report::enforce($report, \is_string($name),   "Expected field 'name' to be a `string`, instead got \$name of type ???",         'path/test.php', $func, 6);

        // @phpstan-ignore function.impossibleType
        Report::enforce($report, \is_string($foostr), "Expected field 'foostr' to be a `string`, instead for $foostrstr of type `int`", 'path/test.php', $func, 7);

        $n_errors = $report->count();

        $exc = $report->generate_exception(MockException::class, "Validation failed.\n$n_errors error(s).");
        $normalize_mock_exception($exc);
        assert($exc->__toString() ===
            "MockException\n".
            "Messages:\n".
            "  test.php(4): Required field 'barint' not found.\n".
            "  test.php(5): Expected field 'id' to be an `int`, instead got '$id' of type `string`\n".
            "  test.php(7): Expected field 'foostr' to be a `string`, instead for $foostrstr of type `int`\n".
            "  exc.php(42): (<- thrown from here)\n".
            "               Validation failed.\n".
            "               3 error(s).\n".
            "\n".
            "Trace:\n");

        $undefined_fields = \array_diff_key($row, $header);

        $line = 10;
        foreach($undefined_fields as $key => $value) {
            $dbg_type = \get_debug_type($value);
            $value_str = \strval($value);
            Report::enforce($report, false, "Found unexpected field/column named `$key` in the row. Type is `$dbg_type`. Value is '$value_str'.", 'path/test.php', $func, $line);
            $line++;
        }

        $n_errors = $report->count();

        $exc = $report->generate_exception(MockException::class, "Validation failed.\n$n_errors error(s).");
        $normalize_mock_exception($exc);
        assert($exc->__toString() ===
            "MockException\n".
            "Messages:\n".
            "  test.php(4):  Required field 'barint' not found.\n".
            "  test.php(5):  Expected field 'id' to be an `int`, instead got '$id' of type `string`\n".
            "  test.php(7):  Expected field 'foostr' to be a `string`, instead for $foostrstr of type `int`\n".
            "  test.php(10): Found unexpected field/column named `bar` in the row. Type is `int`. Value is '37'.\n".
            "  exc.php(42):  (<- thrown from here)\n".
            "                Validation failed.\n".
            "                4 error(s).\n".
            "\n".
            "Trace:\n");
    }

    public static function unittests() : void
    {
        $class_fqn = self::class;
        echo("Running `$class_fqn::unittests()`\n");

        self::unittest_MessageAccumulator_construct_exception_early();
        self::unittest_MessageAccumulator_construct_exception_late();

        echo("  ... passed.\n\n");
    }
}
?>
