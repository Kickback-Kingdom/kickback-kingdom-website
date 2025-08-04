<?php 
declare(strict_types=1);

namespace Kickback\Backend\Views;

use DateTime;

class vDateTime
{
    public string $valueString;
    public DateTime $value;
    public string $formattedBasic = "DATE ERROR";
    public string $formattedDetailed = "DATE ERROR";
    public string $formattedYmd = "DATE ERROR";
    public string $formattedYm = "DATE ERROR";
    public string $formattedHi = "DATE ERROR";
    public string $formattedMonthYear = "DATE ERROR";
    public string $dbValue = "DATE ERROR";

    public function expired() : bool {
        return ($this->value < (New DateTime()));
    }
    public function isBefore(vDateTime $other): bool {
        return $this->value < $other->value;
    }
    
    public function isAfter(vDateTime $other): bool {
        return $this->value > $other->value;
    }
    
    public function isSameOrBefore(vDateTime $other): bool {
        return $this->value <= $other->value;
    }
    
    public function isSameOrAfter(vDateTime $other): bool {
        return $this->value >= $other->value;
    }
    
    public function setDateTime(DateTime $dateTime) : void
    {
        //change timezone to utc
        //$dateTime->setTimezone(new \DateTimeZone("UTC"));
        $this->value = $dateTime;
        $this->formattedBasic = date_format($this->value,"M j, Y");
        $this->formattedDetailed = date_format($this->value,"M j, Y H:i:s");
        $this->formattedYmd = date_format($this->value,"Y-m-d");
        $this->formattedYm = date_format($this->value,"Y-m");
        $this->formattedHi = date_format($this->value,"H:i");
        $this->valueString = date_format($this->value, "Y-m-d\TH:i:s\Z");
        $this->formattedMonthYear = date_format($this->value, "F, Y");
        $this->dbValue = $dateTime->format("Y-m-d H:i:s");
    }

    public static function fromDateTime(DateTime $dateTime) : vDateTime
    {
        $date = new vDateTime();
        $date->setDateTime($dateTime);
        $date->dbValue = $dateTime->format("Y-m-d H:i:s");

        return $date;
    }

    public static function fromDB(string $dateTimeString) : vDateTime
    {
        $dateTime = new vDateTime();
        $dateTime->setDateTimeFromString($dateTimeString);

        return $dateTime;
    }

    public static function now(): vDateTime {
        return new vDateTime();
    }
    
    public function setDateTimeFromString(string $dateTimeString) : void
    {
        $this->dbValue = $dateTimeString;
        $this->setDateTime(new DateTime($dateTimeString, new \DateTimeZone("UTC")));
    }

    public function getDateTimeElement(?string $id = null) : string
    {
        return '<span class="date" '.($id == null?'':' id="'.$id.'" ').' data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="'.$this->formattedDetailed.' UTC" data-datetime-utc="' . $this->valueString . '" data-db-value="'.$this->dbValue.'">'.$this->formattedBasic.'</span>';
    }

    
    function __construct(?string $dateString = null)
    {
        if (isset($dateString) && $dateString !== "") {
            $this->setDateTimeFromString($dateString);
        } else {
            $this->setDateTime(new \DateTime("now", new \DateTimeZone("UTC")));
        }
    }


    public static function getValueString(?vDateTime $dateTime) : string {
        if ($dateTime == null)
            return "";
        return $dateTime->valueString;
    }

    public static function getFormattedYmd(?vDateTime $dateTime) : string {
        if ($dateTime == null)
            return "";
        return $dateTime->formattedYmd; 
    }

    public static function getFormattedHi(?vDateTime $dateTime) : string {
        if ($dateTime == null)
            return "";
        return $dateTime->formattedHi; 
    }
    
    public function timeElapsedString(bool $full = false) : string
    {
        $now = new DateTime;
        $diff = $now->diff($this->value);
        $n_components = $full ? 0 : 1;
        $tstr = self::timeIntervalToString($diff, $n_components);
        if ( strlen($tstr) !== 0 ) {
            return $tstr . ' ago';
        } else {
            return 'just now';
        }
    }

    public static function timeIntervalToString(\DateInterval $diff,  int $max_number_of_components = 0) : string
    {
        $days = $diff->d;
        $weeks = intdiv($days, 7); // floor($days / 7);
        $days -= $weeks * 7;

        $parts = array(
            [$diff->y, 'year'  ],
            [$diff->m, 'month' ],
            [$weeks  , 'week'  ],
            [$days   , 'day'   ],
            [$diff->h, 'hour'  ],
            [$diff->i, 'minute'],
            [$diff->s, 'second'],
        );

        if ( $max_number_of_components === 0 ) {
            $max_number_of_components = count($parts);
        }

        $components = [];
        foreach($parts as $part)
        {
            $amount = $part[0];
            if ( $amount > 0 ) {
                $unit   = $part[1];
                $components[] = $amount . ' ' . $unit . ($amount > 1 ? 's' : '');
            }
        }

        if (count($components) > $max_number_of_components) {
            $components = array_slice($components, 0, $max_number_of_components);
        }
        return implode(', ', $components);
    }

    private static function unittest_timeIntervalToString() : void
    {
        $toString = function($date_str, $max) {
            $test_time_delta = new \DateInterval($date_str);
            return self::timeIntervalToString($test_time_delta, $max);
        };

        assert(strlen($toString('P0000-00-00T00:00:00', 0)) === 0);
        assert(strlen($toString('P0000-00-00T00:00:00', 1)) === 0);
        assert(strlen($toString('P0000-00-00T00:00:00', 2)) === 0);
        assert(strlen($toString('P0000-00-00T00:00:00', 7)) === 0);
        assert(strlen($toString('P0000-00-00T00:00:00', 8)) === 0);

        assert($toString('P0000-00-00T00:00:01', 0) === '1 second');
        assert($toString('P0000-00-00T00:00:02', 0) === '2 seconds');
        assert($toString('P0001-00-00T00:00:00', 0) === '1 year');
        assert($toString('P0002-00-00T00:00:00', 0) === '2 years');
        assert($toString('P0001-00-00T00:00:01', 0) === '1 year, 1 second');
        assert($toString('P0001-00-00T00:00:02', 0) === '1 year, 2 seconds');
        assert($toString('P0002-00-00T00:00:01', 0) === '2 years, 1 second');
        assert($toString('P0002-00-00T00:00:02', 0) === '2 years, 2 seconds');

        assert($toString('P0001-01-01T01:01:01', 0) === '1 year, 1 month, 1 day, 1 hour, 1 minute, 1 second');
        assert($toString('P0001-01-07T01:01:01', 0) === '1 year, 1 month, 1 week, 1 hour, 1 minute, 1 second');
        assert($toString('P0001-01-08T01:01:01', 0) === '1 year, 1 month, 1 week, 1 day, 1 hour, 1 minute, 1 second');
        assert($toString('P0001-01-21T01:01:01', 0) === '1 year, 1 month, 3 weeks, 1 hour, 1 minute, 1 second');
        assert($toString('P0001-01-22T01:01:01', 0) === '1 year, 1 month, 3 weeks, 1 day, 1 hour, 1 minute, 1 second');
        assert($toString('P0002-02-02T02:02:02', 0) === '2 years, 2 months, 2 days, 2 hours, 2 minutes, 2 seconds');
        assert($toString('P0002-02-14T02:02:02', 0) === '2 years, 2 months, 2 weeks, 2 hours, 2 minutes, 2 seconds');
        assert($toString('P0002-02-16T02:02:02', 0) === '2 years, 2 months, 2 weeks, 2 days, 2 hours, 2 minutes, 2 seconds');

        assert($toString('P0001-01-01T01:01:01', 1) === '1 year');
        assert($toString('P0001-01-07T01:01:01', 1) === '1 year');
        assert($toString('P0001-01-08T01:01:01', 1) === '1 year');
        assert($toString('P0001-01-21T01:01:01', 1) === '1 year');
        assert($toString('P0001-01-22T01:01:01', 1) === '1 year');
        assert($toString('P0002-02-02T02:02:02', 1) === '2 years');
        assert($toString('P0002-02-14T02:02:02', 1) === '2 years');
        assert($toString('P0002-02-16T02:02:02', 1) === '2 years');

        assert($toString('P0001-01-01T01:01:01', 2) === '1 year, 1 month');
        assert($toString('P0001-01-07T01:01:01', 2) === '1 year, 1 month');
        assert($toString('P0001-01-08T01:01:01', 2) === '1 year, 1 month');
        assert($toString('P0001-01-21T01:01:01', 2) === '1 year, 1 month');
        assert($toString('P0001-01-22T01:01:01', 2) === '1 year, 1 month');
        assert($toString('P0002-02-02T02:02:02', 2) === '2 years, 2 months');
        assert($toString('P0002-02-14T02:02:02', 2) === '2 years, 2 months');
        assert($toString('P0002-02-16T02:02:02', 2) === '2 years, 2 months');

        assert($toString('P0001-01-01T01:01:01', 5) === '1 year, 1 month, 1 day, 1 hour, 1 minute');
        assert($toString('P0001-01-07T01:01:01', 5) === '1 year, 1 month, 1 week, 1 hour, 1 minute');
        assert($toString('P0001-01-08T01:01:01', 6) === '1 year, 1 month, 1 week, 1 day, 1 hour, 1 minute');
        assert($toString('P0001-01-21T01:01:01', 5) === '1 year, 1 month, 3 weeks, 1 hour, 1 minute');
        assert($toString('P0001-01-22T01:01:01', 6) === '1 year, 1 month, 3 weeks, 1 day, 1 hour, 1 minute');
        assert($toString('P0002-02-02T02:02:02', 5) === '2 years, 2 months, 2 days, 2 hours, 2 minutes');
        assert($toString('P0002-02-14T02:02:02', 5) === '2 years, 2 months, 2 weeks, 2 hours, 2 minutes');
        assert($toString('P0002-02-16T02:02:02', 6) === '2 years, 2 months, 2 weeks, 2 days, 2 hours, 2 minutes');

        assert($toString('P0001-01-01T01:01:01', 6) === '1 year, 1 month, 1 day, 1 hour, 1 minute, 1 second');
        assert($toString('P0001-01-07T01:01:01', 6) === '1 year, 1 month, 1 week, 1 hour, 1 minute, 1 second');
        assert($toString('P0001-01-08T01:01:01', 7) === '1 year, 1 month, 1 week, 1 day, 1 hour, 1 minute, 1 second');
        assert($toString('P0001-01-21T01:01:01', 6) === '1 year, 1 month, 3 weeks, 1 hour, 1 minute, 1 second');
        assert($toString('P0001-01-22T01:01:01', 7) === '1 year, 1 month, 3 weeks, 1 day, 1 hour, 1 minute, 1 second');
        assert($toString('P0002-02-02T02:02:02', 6) === '2 years, 2 months, 2 days, 2 hours, 2 minutes, 2 seconds');
        assert($toString('P0002-02-14T02:02:02', 6) === '2 years, 2 months, 2 weeks, 2 hours, 2 minutes, 2 seconds');
        assert($toString('P0002-02-16T02:02:02', 7) === '2 years, 2 months, 2 weeks, 2 days, 2 hours, 2 minutes, 2 seconds');

        assert($toString('P0001-01-01T01:01:01', 8) === '1 year, 1 month, 1 day, 1 hour, 1 minute, 1 second');
        assert($toString('P0001-01-07T01:01:01', 8) === '1 year, 1 month, 1 week, 1 hour, 1 minute, 1 second');
        assert($toString('P0001-01-08T01:01:01', 8) === '1 year, 1 month, 1 week, 1 day, 1 hour, 1 minute, 1 second');
        assert($toString('P0001-01-21T01:01:01', 8) === '1 year, 1 month, 3 weeks, 1 hour, 1 minute, 1 second');
        assert($toString('P0001-01-22T01:01:01', 8) === '1 year, 1 month, 3 weeks, 1 day, 1 hour, 1 minute, 1 second');
        assert($toString('P0002-02-02T02:02:02', 8) === '2 years, 2 months, 2 days, 2 hours, 2 minutes, 2 seconds');
        assert($toString('P0002-02-14T02:02:02', 8) === '2 years, 2 months, 2 weeks, 2 hours, 2 minutes, 2 seconds');
        assert($toString('P0002-02-16T02:02:02', 8) === '2 years, 2 months, 2 weeks, 2 days, 2 hours, 2 minutes, 2 seconds');

        echo("  ".__FUNCTION__."()\n");
    }

    public function addYears(int $years): vDateTime {
        return $this->modifyClone("P{$years}Y");
    }
    
    public function subYears(int $years): vDateTime {
        return $this->modifyClone("P{$years}Y", true);
    }
    
    public function addMonths(int $months): vDateTime {
        return $this->modifyClone("P{$months}M");
    }
    
    public function subMonths(int $months): vDateTime {
        return $this->modifyClone("P{$months}M", true);
    }
    
    public function addDays(int $days): vDateTime {
        return $this->modifyClone("P{$days}D");
    }
    
    public function subDays(int $days): vDateTime {
        return $this->modifyClone("P{$days}D", true);
    }
    
    public function addHours(int $hours): vDateTime {
        return $this->modifyClone("PT{$hours}H");
    }
    
    public function subHours(int $hours): vDateTime {
        return $this->modifyClone("PT{$hours}H", true);
    }
    
    public function addMinutes(int $minutes): vDateTime {
        return $this->modifyClone("PT{$minutes}M");
    }
    
    public function subMinutes(int $minutes): vDateTime {
        return $this->modifyClone("PT{$minutes}M", true);
    }
    
    public function addSeconds(int $seconds): vDateTime {
        return $this->modifyClone("PT{$seconds}S");
    }
    
    public function subSeconds(int $seconds): vDateTime {
        return $this->modifyClone("PT{$seconds}S", true);
    }

    private function modifyClone(string $intervalSpec, bool $isSubtract = false): vDateTime
    {
        $newDate = clone $this->value;
        $interval = new \DateInterval($intervalSpec);
    
        if ($isSubtract) {
            $newDate->sub($interval);
        } else {
            $newDate->add($interval);
        }
    
        return vDateTime::fromDateTime($newDate);
    }

    public function withParts(
        ?int $year = null,
        ?int $month = null,
        ?int $day = null,
        ?int $hour = null,
        ?int $minute = null,
        ?int $second = null
    ): vDateTime
    {
        $dt = $this->value;
    
        $newDateTime = new \DateTime(
            sprintf(
                "%04d-%02d-%02d %02d:%02d:%02d",
                $year ?? (int)$dt->format("Y"),
                $month ?? (int)$dt->format("m"),
                $day ?? (int)$dt->format("d"),
                $hour ?? (int)$dt->format("H"),
                $minute ?? (int)$dt->format("i"),
                $second ?? (int)$dt->format("s")
            ),
            new \DateTimeZone("UTC")
        );
    
        return vDateTime::fromDateTime($newDateTime);
    }

    
    // ======== SETTERS ========

    public function setDay(int $day): vDateTime {
        return $this->withParts(day: $day);
    }

    public function setMonth(int $month): vDateTime {
        return $this->withParts(month: $month);
    }

    public function setYear(int $year): vDateTime {
        return $this->withParts(year: $year);
    }

    public function setTime(int $hour, int $minute, int $second = 0): vDateTime {
        return $this->withParts(hour: $hour, minute: $minute, second: $second);
    }

    // ======== GETTERS ========

    public function getDay(): int {
        return (int)$this->value->format("j");
    }

    public function getMonth(): int {
        return (int)$this->value->format("n");
    }

    public function getYear(): int {
        return (int)$this->value->format("Y");
    }

    public function getHour(): int {
        return (int)$this->value->format("H");
    }

    public function getMinute(): int {
        return (int)$this->value->format("i");
    }

    public function getSecond(): int {
        return (int)$this->value->format("s");
    }

    public static function unittests() : void
    {
        $class_fqn = self::class;
        echo("Running `$class_fqn::unittests()`\n");

        self::unittest_timeIntervalToString();

        echo("  ... passed.\n\n");
    }
}

// =============================================================================
// TODO: Integrate these with the whole-system unittests
// (It miiiight wait until we have a test runner; ideally one capable of HTML output.)
//
// These are intentionally inaccessible from the web for security reasons.
//
// To run them, place a script in your `html/scratch-pad/` directory with these contents:
// ```
// // (html/)scratch-pad/foobar.php
// declare(strict_types=1);
//
// require_once(($_SERVER["DOCUMENT_ROOT"] ?: (__DIR__ . "/..")) . "/Kickback/init.php");
//
// use Kickback\Backend\Views\vDateTime__Tests;
// use Kickback\Backend\Views\vDecimal__Tests;
//
// $ret = 0;
// $ret |= vDateTime__Tests::unittests() << 0;
// $ret |= vDecimal__Tests::unittests()  << 1;
// return $ret;
// ```
// Of course, add in the PHP start tag and optional end tag!
// (I can't put those in the comment without breaking the file)
//
// Then navigate your browser to
// https://127.0.0.1/scratch-pad/foobar.php
// ... and the tests should run.
//
final class vDateTime__Tests
{
    use \Kickback\Common\Traits\StaticClassTrait;

    /** @var array{ passed: int, failed: int } */
    private static array $results = [
        'passed' => 0,
        'failed' => 0
    ];

    public static function test(string $name, callable $fn): void {

        $padded = str_pad("Running: $name", 40, ' ');
        echo $padded;

        try {
            $fn();
            echo "✅ PASS<br/>";
            self::$results['passed']++;
        } catch (\Throwable $e) {
            echo "❌ FAIL<br/>      ↳ {$e->getMessage()}<br/>";
            self::$results['failed']++;
        }
    }

    public static function assertEqual(
        string|int|float|bool  $a,
        string|int|float|bool  $b,
        string                 $msg = ''
    ) : void
    {
        if ($a !== $b) {
            throw new \Exception("Assertion failed: $a !== $b. $msg");
        }
    }

    public static function unittests() : int
    {
        echo "=== vDateTime Unit Tests ===<br/>";

        self::test("Construct from ISO string", function () {
            $d = new vDateTime("2025-04-10T12:34:56Z");
            self::assertEqual($d->formattedYmd, "2025-04-10");
        });

        self::test("Construct from DB string", function () {
            $d = vDateTime::fromDB("2025-04-10 12:34:56");
            self::assertEqual($d->formattedHi, "12:34");
        });

        self::test("FormattedYm produces YYYY-MM", function () {
            $d = new vDateTime("2023-01-15");
            self::assertEqual($d->formattedYm, "2023-01");
        });

        self::test("GetValueString works", function () {
            $d = new vDateTime("2025-01-01T00:00:00Z");
            self::assertEqual(vDateTime::getValueString($d), $d->valueString);
        });

        self::test("GetFormattedYmd null-safe", function () {
            self::assertEqual(vDateTime::getFormattedYmd(null), "");
        });

        self::test("Elapsed string: just now", function () {
            $now = new vDateTime(gmdate("Y-m-d H:i:s"));
            self::assertEqual($now->timeElapsedString(), "just now");
        });

        self::test("Elapsed string: minutes ago", function () {
            $past = (new vDateTime())->subMinutes(5);
            self::assertEqual($past->timeElapsedString(), "5 minutes ago");
        });

        self::test("Add years", function () {
            $d = new vDateTime("2020-01-01");
            self::assertEqual($d->addYears(2)->formattedYmd, "2022-01-01");
        });

        self::test("Subtract years", function () {
            $d = new vDateTime("2020-01-01");
            self::assertEqual($d->subYears(1)->formattedYmd, "2019-01-01");
        });

        self::test("Add months", function () {
            $d = new vDateTime("2020-01-01");
            self::assertEqual($d->addMonths(1)->formattedYmd, "2020-02-01");
        });

        self::test("Subtract months", function () {
            $d = new vDateTime("2020-01-01");
            self::assertEqual($d->subMonths(1)->formattedYmd, "2019-12-01");
        });

        self::test("Add days", function () {
            $d = new vDateTime("2020-01-01");
            self::assertEqual($d->addDays(10)->formattedYmd, "2020-01-11");
        });

        self::test("Subtract days", function () {
            $d = new vDateTime("2020-01-10");
            self::assertEqual($d->subDays(5)->formattedYmd, "2020-01-05");
        });

        self::test("Add hours", function () {
            $d = new vDateTime("2020-01-01 00:00:00");
            self::assertEqual($d->addHours(2)->formattedHi, "02:00");
        });

        self::test("Subtract hours", function () {
            $d = new vDateTime("2020-01-01 05:00:00");
            self::assertEqual($d->subHours(2)->formattedHi, "03:00");
        });

        self::test("Add minutes", function () {
            $d = new vDateTime("2020-01-01 00:00:00");
            self::assertEqual($d->addMinutes(30)->formattedHi, "00:30");
        });

        self::test("Subtract minutes", function () {
            $d = new vDateTime("2020-01-01 01:00:00");
            self::assertEqual($d->subMinutes(30)->formattedHi, "00:30");
        });

        self::test("Add seconds", function () {
            $d = new vDateTime("2020-01-01 00:00:00");
            $new = $d->addSeconds(60);
            self::assertEqual($new->formattedHi, "00:01");
        });

        self::test("Subtract seconds", function () {
            $d = new vDateTime("2020-01-01 00:01:00");
            $new = $d->subSeconds(60);
            self::assertEqual($new->formattedHi, "00:00");
        });

        self::test("Clone modify does not mutate original", function () {
            $original = new vDateTime("2020-01-01");
            $modified = $original->addDays(1);
            self::assertEqual($original->formattedYmd, "2020-01-01");
            self::assertEqual($modified->formattedYmd, "2020-01-02");
        });

        self::test("expired() returns false for future", function () {
            $future = (new vDateTime())->addDays(1);
            self::assertEqual($future->expired(), false);
        });

        self::test("expired() returns true for past", function () {
            $past = (new vDateTime())->subDays(1);
            self::assertEqual($past->expired(), true);
        });

        self::test("getDateTimeElement produces HTML", function () {
            $d = new vDateTime("2025-04-10T12:00:00Z");
            $html = $d->getDateTimeElement("test-id");
            self::assertEqual(strpos($html, "data-bs-title=") !== false, true);
        });

        self::test("Multiple formats are all consistent", function () {
            $d = new vDateTime("2024-12-31 23:59:59");
            self::assertEqual($d->formattedBasic, "Dec 31, 2024");
            self::assertEqual($d->formattedDetailed, "Dec 31, 2024 23:59:59");
            self::assertEqual($d->formattedYmd, "2024-12-31");
            self::assertEqual($d->formattedYm, "2024-12");
            self::assertEqual($d->formattedHi, "23:59");
        });

        self::test("Handles leap year correctly", function () {
            $d = new vDateTime("2020-02-29");
            self::assertEqual($d->addYears(1)->formattedYmd, "2021-03-01");

        });

        self::test("Handles new year correctly", function () {
            $d = new vDateTime("2023-12-31");
            self::assertEqual($d->addDays(1)->formattedYmd, "2024-01-01");
        });
        // === Component Setters ===

        self::test("Set day to 1", function () {
            $d = new vDateTime("2025-04-15 12:00:00");
            $set = $d->setDay(1);
            self::assertEqual($set->formattedYmd, "2025-04-01");
        });

        self::test("Set month to January", function () {
            $d = new vDateTime("2025-04-15 12:00:00");
            $set = $d->setMonth(1);
            self::assertEqual($set->formattedYmd, "2025-01-15");
        });

        self::test("Set year to 2030", function () {
            $d = new vDateTime("2025-04-15 12:00:00");
            $set = $d->setYear(2030);
            self::assertEqual($set->formattedYmd, "2030-04-15");
        });

        self::test("Set time to 08:30:00", function () {
            $d = new vDateTime("2025-04-15 12:45:15");
            $set = $d->setTime(8, 30, 0);
            self::assertEqual($set->formattedHi, "08:30");
        });

        // === Component Getters ===

        self::test("Get day returns correct value", function () {
            $d = new vDateTime("2025-04-15");
            self::assertEqual($d->getDay(), 15);
        });

        self::test("Get month returns correct value", function () {
            $d = new vDateTime("2025-04-15");
            self::assertEqual($d->getMonth(), 4);
        });

        self::test("Get year returns correct value", function () {
            $d = new vDateTime("2025-04-15");
            self::assertEqual($d->getYear(), 2025);
        });

        self::test("Get hour returns correct value", function () {
            $d = new vDateTime("2025-04-15 18:30:00");
            self::assertEqual($d->getHour(), 18);
        });

        self::test("Get minute returns correct value", function () {
            $d = new vDateTime("2025-04-15 18:30:00");
            self::assertEqual($d->getMinute(), 30);
        });

        self::test("Get second returns correct value", function () {
            $d = new vDateTime("2025-04-15 18:30:45");
            self::assertEqual($d->getSecond(), 45);
        });

        self::test("Chained setYear/setMonth/setDay/setTime", function () {
            $d = new vDateTime("2020-01-01 00:00:00");
            $modified = $d->setYear(2024)->setMonth(12)->setDay(25)->setTime(14, 45, 30);
            self::assertEqual($modified->formattedDetailed, "Dec 25, 2024 14:45:30");
        });

        $npassed = self::$results['passed'];
        $nfailed = self::$results['failed'];
        echo "<br/>";
        echo "=== Test Summary ===<br/>";
        echo "✅ Passed: {$npassed}<br/>";
        echo "❌ Failed: {$nfailed}<br/>";
        echo "====================<br/>";

        /** @phpstan-ignore greater.alwaysFalse */
        if (self::$results['failed'] > 0) {
            return 1;
        } else {
            return 0;
        }
    }
}
?>
