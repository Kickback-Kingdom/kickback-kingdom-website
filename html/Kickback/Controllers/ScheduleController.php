<?php

declare(strict_types=1);

namespace Kickback\Controllers;


class ScheduleController
{

    public static function GetCalendarEvents($month, $year)
    {
        // Use global connection
        $db = $GLOBALS['conn'];
    
        // Retrieve events for the specified month and year
        $query = "SELECT * FROM calendar_events WHERE MONTH(start_date) = ? AND YEAR(start_date) = ?";
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, 'ss', $month, $year);
    
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    
        // Fetch the events into an associative array
        $events = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
        mysqli_stmt_close($stmt);
    
        return $events;
    }
}
?>