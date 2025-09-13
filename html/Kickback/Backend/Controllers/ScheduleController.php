<?php

declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use Kickback\Services\Database;

class ScheduleController
{

    /**
    * @param int      $month        1-12 month value
    * @param int      $year         four digit year
    * @param ?int     $questGiverId Optional quest giver account id to include their quests
    *
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
    public static function getCalendarEvents(int $month, int $year, ?int $questGiverId = null) : array
    {
        // Use dedicated database connection service
        $db = Database::getConnection();

        // Retrieve global calendar events for the specified month and year
        $query = "SELECT * FROM calendar_events WHERE MONTH(start_date) = ? AND YEAR(start_date) = ?";
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, 'ii', $month, $year);

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $events = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
        mysqli_stmt_close($stmt);

        // Include the quest giver's own quest events if an account id is provided
        if ($questGiverId !== null) {
            $questQuery =
                "SELECT Id AS id, name AS title, `desc` AS description, "
                . "end_date AS start_date, end_date AS end_date, "
                . "'NONE' AS recurrence, NULL AS day_of_week, NULL AS day_of_month, "
                . "NULL AS week_of_month, NULL AS month, 'QUEST' AS event_type "
                . "FROM quest WHERE (host_id = ? OR host_id_2 = ?) "
                . "AND MONTH(end_date) = ? AND YEAR(end_date) = ?";

            $stmt2 = mysqli_prepare($db, $questQuery);
            if ($stmt2) {
                mysqli_stmt_bind_param($stmt2, 'iiii', $questGiverId, $questGiverId, $month, $year);
                mysqli_stmt_execute($stmt2);
                $result2 = mysqli_stmt_get_result($stmt2);
                if ($result2) {
                    $questEvents = mysqli_fetch_all($result2, MYSQLI_ASSOC);
                    $events = array_merge($events, $questEvents);
                }
                mysqli_stmt_close($stmt2);
            }
        }

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
        $db = Database::getConnection();

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
