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

    public function setDateTime(DateTime $dateTime) {
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

    public static function fromDateTime(DateTime $dateTime) : vDateTime {

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

    public function setDateTimeFromString(string $dateTimeString)
    {
        $this->dbValue = $dateTimeString;
        $this->setDateTime(new DateTime($dateTimeString, new \DateTimeZone("UTC")));
    }

    public function getDateTimeElement($id = null) {
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
    
    public function timeElapsedString($full = false) {
        $now = new DateTime;
        $diff = $now->diff($this->value);

        $days = $diff->d;
        $weeks = floor($days / 7);
        $days -= $weeks * 7;


        $string = array(
            'y' => 'year',
            'm' => 'month',
            'w' => 'week',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        );
        foreach ($string as $k => &$v) {
            if ($k == 'w' && $weeks > 0) {
                $v = $weeks . ' ' . $v . ($weeks > 1 ? 's' : '');
            } elseif ($k == 'd' && $days > 0) {
                $v = $days . ' ' . $v . ($days > 1 ? 's' : '');
            } elseif ($k != 'w' && $k != 'd' && $diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }

        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' ago' : 'just now';
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

    private function modifyClone(string $intervalSpec, bool $isSubtract = false): vDateTime {
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
    ): vDateTime {
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

    
}

?>
