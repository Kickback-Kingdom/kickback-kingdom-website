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

        echo("  unittest_timeIntervalToString()\n");
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

    private function withParts(
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

    public static function unittest() : void
    {
        echo("Running `\\Kickback\\Backend\\Views\\vDateTime::unittest()`\n");
        self::unittest_timeIntervalToString();
        echo("  ... passed.\n\n");
    }
}

?>
