<?php 
declare(strict_types=1);

namespace Kickback\Views;

use DateTime;

class vDateTime
{
    public string $valueString;
    public DateTime $value;
    public string $formattedBasic = "DATE ERROR";
    public string $formattedDetailed = "DATE ERROR";
    public string $formattedYmd = "DATE ERROR";
    public string $formattedHi = "DATE ERROR";
    public function isExpired() : bool {
        return ($this->value < (New DateTime()));
    }

    public function setDateTime(DateTime $dateTime) {
        $this->value = $dateTime;
        $this->formattedBasic = date_format($this->value,"M j, Y");
        $this->formattedDetailed = date_format($this->value,"M j, Y H:i:s");
        $this->formattedYmd = date_format($this->value,"Y-m-d");
        $this->formattedHi = date_format($this->value,"H:i");
        $this->valueString = date_format($this->value, "Y-m-d H:i:s");
    }

    public function setDateTimeFromString(string $dateTimeString)
    {
        $this->setDateTime(date_create($dateTimeString));
    }

    public function getDateTimeElement($id = null) {
        return '<span class="date" '.($id == null?'':' id="'.$id.'" ').' data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="'.$this->formattedDetailed.' UTC">'.$this->formattedBasic.'</span>';
    }

    
    function __construct(?string $dateString = null)
    {
        if (isset($dateString) && $dateString != null && $dateString != "")
            $this->setDateTimeFromString($dateString);
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
}

?>