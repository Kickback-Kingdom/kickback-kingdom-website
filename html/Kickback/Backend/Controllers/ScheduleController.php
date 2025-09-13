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

    /**
    * Project future dates with high expected engagement.
    * Combines historic averages by weekday with current calendar events.
    *
    * @return array<array{date: string, score: int, reason: string}>
    */
    public static function getSuggestedDates(int $month, int $year, int $limit = 3) : array
    {
        $db = $GLOBALS['conn'];

        // Historical engagement averages by day of week (1=Sunday .. 7=Saturday)
        $avgQuery = "SELECT DAYOFWEEK(start_date) AS dow, COUNT(*) AS cnt
                     FROM calendar_events
                     WHERE start_date < CURDATE()
                     GROUP BY dow";
        $avgResult = mysqli_query($db, $avgQuery);
        $averages = array_fill(1, 7, 0);
        if ($avgResult) {
            while ($row = mysqli_fetch_assoc($avgResult)) {
                $averages[intval($row['dow'])] = intval($row['cnt']);
            }
            mysqli_free_result($avgResult);
        }

        // Upcoming events for the specified month/year
        $events = self::getCalendarEvents($month, $year);
        $eventsByDate = [];
        foreach ($events as $event) {
            $dateKey = date('Y-m-d', strtotime($event['start_date']));
            $eventsByDate[$dateKey][] = $event;
        }

        $suggestions = [];
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $dow = intval(date('w', strtotime($dateStr))) + 1; // MySQL style
            $score = $averages[$dow] ?? 0;
            $reason = [];

            if ($score > 0) {
                $reason[] = 'Historically high engagement on ' . date('l', strtotime($dateStr));
            } else {
                $reason[] = 'No historical data for ' . date('l', strtotime($dateStr));
            }

            if (isset($eventsByDate[$dateStr])) {
                // Boost score if event already exists on this date
                $score += 5;
                $titles = array_map(fn($e) => $e['title'], $eventsByDate[$dateStr]);
                $reason[] = 'Existing events: ' . implode(', ', $titles);
            } else {
                $reason[] = 'No conflicting events';
            }

            $suggestions[] = [
                'date' => $dateStr,
                'score' => $score,
                'reason' => implode('. ', $reason)
            ];
        }

        usort($suggestions, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($suggestions, 0, $limit);
    }
}
?>
