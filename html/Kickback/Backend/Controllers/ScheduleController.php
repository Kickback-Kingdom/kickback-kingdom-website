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
    * @param bool     $includeAll   When true, include quests from all hosts
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
    *     event_type:    string,
    *     participants:  ?int
    * }>
    */
    public static function getCalendarEvents(int $month, int $year, ?int $questGiverId = null, bool $includeAll = false) : array
    {
        // Use dedicated database connection service
        $db = Database::getConnection();

        // Retrieve global calendar events for the specified month and year
        $query = "SELECT *, NULL AS host_id, NULL AS host_name FROM calendar_events WHERE MONTH(start_date) = ? AND YEAR(start_date) = ?";
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, 'ii', $month, $year);

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $events = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
        mysqli_stmt_close($stmt);
        // ensure consistent keys for consumers
        foreach ($events as &$e) {
            $e['participants'] = null;
            $e['host_id'] = null;
            $e['host_name'] = null;
        }
        unset($e);

        // Include quest events
        if ($includeAll || $questGiverId !== null) {
            if ($includeAll) {
                $questQuery =
                    "SELECT q.Id AS id, q.name AS title, q.`desc` AS description, "
                    . "q.end_date AS start_date, q.end_date AS end_date, "
                    . "'NONE' AS recurrence, NULL AS day_of_week, NULL AS day_of_month, "
                    . "NULL AS week_of_month, NULL AS month, 'QUEST' AS event_type, "
                    . "COUNT(qa.participated) AS participants, q.host_id AS host_id, a.Username AS host_name "
                    . "FROM quest q "
                    . "JOIN account a ON a.Id = q.host_id "
                    . "LEFT JOIN quest_applicants qa ON qa.quest_id = q.Id AND qa.participated = 1 "
                    . "WHERE MONTH(q.end_date) = ? AND YEAR(q.end_date) = ? "
                    . "AND q.published = 1 "
                    . "AND q.raffle_id IS NULL "
                    . "GROUP BY q.Id";
                $stmt2 = mysqli_prepare($db, $questQuery);
                if ($stmt2) {
                    mysqli_stmt_bind_param($stmt2, 'ii', $month, $year);
                    mysqli_stmt_execute($stmt2);
                    $result2 = mysqli_stmt_get_result($stmt2);
                    if ($result2) {
                        $questEvents = mysqli_fetch_all($result2, MYSQLI_ASSOC);
                        $events = array_merge($events, $questEvents);
                    }
                    mysqli_stmt_close($stmt2);
                }
            } elseif ($questGiverId !== null) {
                $questQuery =
                    "SELECT q.Id AS id, q.name AS title, q.`desc` AS description, "
                    . "q.end_date AS start_date, q.end_date AS end_date, "
                    . "'NONE' AS recurrence, NULL AS day_of_week, NULL AS day_of_month, "
                    . "NULL AS week_of_month, NULL AS month, 'QUEST' AS event_type, "
                    . "COUNT(qa.participated) AS participants, q.host_id AS host_id, a.Username AS host_name "
                    . "FROM quest q "
                    . "JOIN account a ON a.Id = q.host_id "
                    . "LEFT JOIN quest_applicants qa ON qa.quest_id = q.Id AND qa.participated = 1 "
                    . "WHERE (q.host_id = ? OR q.host_id_2 = ?) "
                    . "AND MONTH(q.end_date) = ? AND YEAR(q.end_date) = ? "
                    . "AND q.raffle_id IS NULL "
                    . "GROUP BY q.Id";

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
        }

        return $events;
    }

    /**
    * Project future dates with high expected engagement.
    * Combines historic averages by weekday with current calendar events.
    *
    * @return array<array{date: string, score: int, reasons: string[]}>
    */
    public static function getSuggestedDates(int $month, int $year, ?int $questGiverId = null, int $limit = 3) : array
    {
        $db = Database::getConnection();

        // Historical engagement averages by weekday for all quests (1=Sunday .. 7=Saturday)
        $avgQuery = "SELECT DAYOFWEEK(q.end_date) AS dow,
                            COUNT(*) AS quest_count,
                            AVG(COALESCE(p.participants, 0)) AS avg_participants
                       FROM quest q
                       LEFT JOIN (
                           SELECT quest_id, COUNT(*) AS participants
                           FROM quest_applicants
                           WHERE participated = 1
                           GROUP BY quest_id
                       ) p ON p.quest_id = q.Id
                       WHERE q.end_date IS NOT NULL
                         AND q.end_date < CURDATE()
                         AND q.raffle_id IS NULL
                       GROUP BY dow";
        $avgResult = mysqli_query($db, $avgQuery);
        $globalAverages = [];
        if ($avgResult) {
            while ($row = mysqli_fetch_assoc($avgResult)) {
                $dow = intval($row['dow']);
                $globalAverages[$dow] = [
                    'avg' => floatval($row['avg_participants'] ?? 0),
                    'count' => intval($row['quest_count'] ?? 0),
                ];
            }
            mysqli_free_result($avgResult);
        }

        // Personal participation averages by weekday for the quest giver
        $personalAverages = [];
        if ($questGiverId !== null) {
            $personalQuery = "SELECT DAYOFWEEK(q.end_date) AS dow,
                                     COUNT(*) AS quest_count,
                                     AVG(COALESCE(p.participants, 0)) AS avg_participants,
                                     MAX(q.end_date) AS last_run
                               FROM quest q
                               LEFT JOIN (
                                   SELECT quest_id, COUNT(*) AS participants
                                   FROM quest_applicants
                                   WHERE participated = 1
                                   GROUP BY quest_id
                               ) p ON p.quest_id = q.Id
                               WHERE (q.host_id = ? OR q.host_id_2 = ?) AND q.end_date < CURDATE() AND q.raffle_id IS NULL
                               GROUP BY dow";
            $stmt = mysqli_prepare($db, $personalQuery);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'ii', $questGiverId, $questGiverId);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                if ($res) {
                    while ($row = mysqli_fetch_assoc($res)) {
                        $dow = intval($row['dow']);
                        $personalAverages[$dow] = [
                            'avg' => floatval($row['avg_participants'] ?? 0),
                            'count' => intval($row['quest_count'] ?? 0),
                            'last' => $row['last_run'] ?? null,
                        ];
                    }
                    mysqli_free_result($res);
                }
                mysqli_stmt_close($stmt);
            }
        }

        // Upcoming events for the specified month/year from all hosts
        $events = self::getCalendarEvents($month, $year, null, true);
        $eventsByDate = [];
        foreach ($events as $event) {
            $dateKey = date('Y-m-d', strtotime($event['start_date']));
            $eventsByDate[$dateKey][] = $event;
        }

        $suggestions = [];
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $today = date('Y-m-d');
        $todayTs = strtotime($today);
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
            if ($dateStr <= $today) {
                continue;
            }
            $dow = intval(date('w', strtotime($dateStr))) + 1; // MySQL style
            $score = 0.0;
            $reasons = [];
            $dayName = date('l', strtotime($dateStr));

            if (isset($globalAverages[$dow]) && $globalAverages[$dow]['count'] > 0) {
                $avgGlobal = $globalAverages[$dow]['avg'];
                $countGlobal = $globalAverages[$dow]['count'];
                $score += round($avgGlobal);
                $globalPlural = round($avgGlobal) === 1 ? '' : 's';
                $reasons[] = sprintf(
                    'Across %d quest%s, hosts average %s adventurer%s on %s.',
                    $countGlobal,
                    $countGlobal === 1 ? '' : 's',
                    number_format($avgGlobal, 1),
                    $globalPlural,
                    $dayName
                );
            } else {
                $reasons[] = 'Limited community data for ' . $dayName . '.';
            }

            if ($questGiverId !== null) {
                if (isset($personalAverages[$dow]) && $personalAverages[$dow]['count'] > 0) {
                    $avgPersonal = $personalAverages[$dow]['avg'];
                    $countPersonal = $personalAverages[$dow]['count'];
                    $lastRun = $personalAverages[$dow]['last'];
                    // Personal performance gets extra weight in the score.
                    $score += round($avgPersonal * 1.5);
                    $personalPlural = round($avgPersonal) === 1 ? '' : 's';
                    $reason = sprintf(
                        'Your %d quest%s on %s average %s adventurer%s',
                        $countPersonal,
                        $countPersonal === 1 ? '' : 's',
                        $dayName,
                        number_format($avgPersonal, 1),
                        $personalPlural
                    );
                    if (!empty($lastRun)) {
                        $reason .= ' (last run ' . date('M j', strtotime($lastRun)) . ')';
                    }
                    $reasons[] = $reason . '.';
                } else {
                    $reasons[] = 'You have no personal turnout history for ' . $dayName . '.';
                }
            }

            $targetTs = strtotime($dateStr);
            $leadDays = (int) floor(($targetTs - $todayTs) / 86400);
            if ($leadDays < 2) {
                $score -= 15;
                $reasons[] = 'Less than 48 hours of lead time for players to join.';
            } elseif ($leadDays < 5) {
                $penalty = (5 - $leadDays) * 2;
                $score -= $penalty;
                $reasons[] = 'Only ' . $leadDays . ' day' . ($leadDays === 1 ? '' : 's') . ' of notice for players.';
            } else {
                $bonus = (int) floor(min($leadDays, 14) / 2);
                if ($bonus > 0) {
                    $score += $bonus;
                }
                $reasons[] = 'Provides ' . $leadDays . ' day' . ($leadDays === 1 ? '' : 's') . ' to promote the quest.';
            }

            $conflictCount = 0;
            $conflictSamples = [];
            $selfConflicts = [];
            if (isset($eventsByDate[$dateStr])) {
                foreach ($eventsByDate[$dateStr] as $ev) {
                    $hostId = $ev['host_id'] !== null ? intval($ev['host_id']) : null;
                    $title = trim((string)($ev['title'] ?? ''));
                    $timeStr = date('g:ia', strtotime($ev['start_date']));
                    if ($questGiverId !== null && $hostId === $questGiverId) {
                        $selfConflicts[] = $title !== '' ? sprintf('"%s" at %s', $title, $timeStr) : 'an existing quest';
                        continue;
                    }
                    $conflictCount++;
                    if (count($conflictSamples) < 2) {
                        $hostName = trim((string)($ev['host_name'] ?? 'another host'));
                        if ($title === '') {
                            $conflictSamples[] = sprintf('another event at %s', $timeStr);
                        } else {
                            $conflictSamples[] = sprintf('"%s" by %s at %s', $title, $hostName ?: 'another host', $timeStr);
                        }
                    }
                }
            }

            if (!empty($selfConflicts)) {
                $score -= 25;
                $reasons[] = 'You already have ' . implode(' and ', $selfConflicts) . ' scheduled on this day.';
            }

            if ($conflictCount > 0) {
                $score -= 5 * $conflictCount;
                $detail = '';
                if (!empty($conflictSamples)) {
                    $detail = ' such as ' . implode(' and ', $conflictSamples);
                }
                $reasons[] = 'Competes with ' . $conflictCount . ' other event' . ($conflictCount === 1 ? '' : 's') . $detail . '.';
            } else {
                $reasons[] = 'No competing events on the community calendar.';
            }

            $suggestions[] = [
                'date' => $dateStr,
                'score' => (int) round($score),
                'reasons' => $reasons
            ];
        }

        usort($suggestions, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($suggestions, 0, $limit);
    }
}
?>
