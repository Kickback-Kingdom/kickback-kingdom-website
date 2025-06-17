<?php

declare(strict_types=1);

namespace Kickback\Backend\Controllers;


class ScheduleController
{

    /**
    * @return array<array{
    *     Id:            int,
    *     title:         string,
    *     description:   string,
    *     start_date:    string,
    *     end_date:      string,
    *     recurrence:    string,
    *     day_of_week:   string,
    *     day_of_month:  int,
    *     week_of_month: string,
    *     month:         int,
    *     event_type:    string
    * }>
    */
    public static function getCalendarEvents(int $month, int $year) : array
    {
        // Use global connection
        $db = $GLOBALS['conn'];

        // Retrieve events for the specified month and year
        $query = "SELECT * FROM calendar_events WHERE MONTH(start_date) = ? AND YEAR(start_date) = ?";
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, 'ss', strval($month), strval($year));

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        // Fetch the events into an associative array
        $events = mysqli_fetch_all($result, MYSQLI_ASSOC);

        mysqli_stmt_close($stmt);

        return $events;
    }
}
?>
