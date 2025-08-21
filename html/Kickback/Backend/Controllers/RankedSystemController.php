<?php
declare(strict_types=1);

namespace Kickback\Backend\Controllers;
use Kickback\Backend\Services\RankedMatchCalculator;

class RankedSystemController
{
    private \mysqli $conn;
    private int $kFactor;
    private int $baseRating;

    public function __construct(\mysqli $conn, int $baseRating = 1500, int $kFactor = 30)
    {
        $this->conn = $conn;
        $this->baseRating = $baseRating;
        $this->kFactor = $kFactor;
    }

    public function getBaseRating() : int
    {
        return $this->baseRating;
    }

    public function getKFactor() : int
    {
        return $this->kFactor;
    }

    /**
     * Batch update ELO changes in the database.
     * @param array<array<string,bool|float|int|string>> $eloUpdates
     */
    public function batchUpdateEloChange(\mysqli $conn, array $eloUpdates) : void
    {
        if (0 === count($eloUpdates)) {
            return;
        }

        $query = 'UPDATE game_record SET elo_change = CASE';
        $ids = [];

        foreach ($eloUpdates as $update) {
            $gameMatchId = intval($update['game_match_id']);
            $accountId = intval($update['account_id']);
            $eloChange = intval($update['elo_change']);

            $query .= " WHEN game_match_id = $gameMatchId AND account_id = $accountId THEN $eloChange";
            $ids[] = "($gameMatchId, $accountId)";
        }

        $query .= ' END WHERE (game_match_id, account_id) IN (' . implode(',', $ids) . ')';

        if (!mysqli_query($conn, $query)) {
            die("Error batch updating elo_change: " . mysqli_error($conn));
        }
    }

    /**
     * Update player ranking and statistics.
     */
    public function updatePlayerStats(
        \mysqli $conn,
        int     $accountId,
        int     $gameId,
        int     $newRating,
        int     $matchesPlayed,
        int     $wins,
        int     $losses,
        int     $minRankedMatches
    ) : void
    {
        $isRanked = $matchesPlayed >= $minRankedMatches ? 1 : 0;

        $stmt = mysqli_prepare($conn, 'INSERT INTO account_game_elo (account_id, game_id, elo_rating, is_ranked, total_matches,
total_wins, total_losses, win_rate)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE elo_rating = VALUES(elo_rating), is_ranked = VALUES(is_ranked), total_matches = VALUES(t
otal_matches), total_wins = VALUES(total_wins), total_losses = VALUES(total_losses), win_rate = VALUES(win_rate)');
        if (!$stmt) {
            die("Error preparing statement: " . mysqli_error($conn));
        }

        $winRate = $matchesPlayed > 0 ? $wins / $matchesPlayed : 0;
        mysqli_stmt_bind_param(
            $stmt,
            'iiidiiid',
            $accountId,
            $gameId,
            $newRating,
            $isRanked,
            $matchesPlayed,
            $wins,
            $losses,
            $winRate
        );

        if (!mysqli_stmt_execute($stmt)) {
            die("Error executing statement: " . mysqli_stmt_error($stmt));
        }

        mysqli_stmt_close($stmt);
    }

    /**
     * Process all pending ranked matches and update player ratings.
     */
    public function processRankedMatches() : void
    {
        $sql = 'SELECT gm.Id AS game_match_id, gm.game_id, g.MinRankedMatches
                FROM game_match gm
                JOIN game g ON g.Id = gm.game_id
                WHERE gm.elo_processed = 0';

        $result = mysqli_query($this->conn, $sql);
        if (!$result) {
            throw new \RuntimeException("Error fetching pending matches: " . mysqli_error($this->conn));
        }
        $matches = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_free_result($result);

        $ratings = [];
        $wins = [];
        $losses = [];
        $matchesPlayed = [];
        $minRankedMatches = [];
        $eloUpdates = [];
        $calculator = new RankedMatchCalculator($this->baseRating, $this->kFactor);

        foreach ($matches as $match) {
            $matchId = (int)$match['game_match_id'];
            $gameId = (int)$match['game_id'];
            $minRankedMatches[$gameId] = (int)$match['MinRankedMatches'];

            $stmt = mysqli_prepare($this->conn, 'SELECT account_id, win, team_name FROM game_record WHERE game_match_id = ? AND game_id = ?');
            if (!$stmt) {
                throw new \RuntimeException('Error preparing statement: ' . mysqli_error($this->conn));
            }
            mysqli_stmt_bind_param($stmt, 'ii', $matchId, $gameId);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $records = mysqli_fetch_all($res, MYSQLI_ASSOC);
            mysqli_stmt_close($stmt);

            $teams = [];
            $teamWins = [];
            foreach ($records as $record) {
                $accountId = (int)$record['account_id'];
                $team = $record['team_name'] ?? 'team';
                $win = (int)$record['win'];

                $teams[$team][] = $accountId;
                $teamWins[$team] = $win;

                $ratings[$gameId][$accountId] = $ratings[$gameId][$accountId] ?? $this->baseRating;
                $wins[$gameId][$accountId] = $wins[$gameId][$accountId] ?? 0;
                $losses[$gameId][$accountId] = $losses[$gameId][$accountId] ?? 0;
                $matchesPlayed[$gameId][$accountId] = $matchesPlayed[$gameId][$accountId] ?? 0;
            }

            $maxWin = max($teamWins);
            $matchTeams = [];
            foreach ($teams as $name => $players) {
                $rank = ($teamWins[$name] === $maxWin) ? 1 : 2;
                $matchTeams[] = [
                    'players' => $players,
                    'rank' => $rank,
                ];
            }

            $currentRatings = $ratings[$gameId] ?? [];
            $newRatings = $calculator->calculate($matchTeams, $currentRatings);

            foreach ($newRatings as $accountId => $newRating) {
                $oldRating = $ratings[$gameId][$accountId] ?? $this->baseRating;
                $eloChange = $newRating - $oldRating;
                $ratings[$gameId][$accountId] = $newRating;
                $matchesPlayed[$gameId][$accountId]++;

                $teamRank = 0;
                foreach ($matchTeams as $team) {
                    if (in_array($accountId, $team['players'], true)) {
                        $teamRank = $team['rank'];
                        break;
                    }
                }
                if (1 === $teamRank) {
                    $wins[$gameId][$accountId]++;
                } elseif (2 === $teamRank) {
                    $losses[$gameId][$accountId]++;
                }

                $eloUpdates[] = [
                    'game_match_id' => $matchId,
                    'account_id' => $accountId,
                    'elo_change' => $eloChange,
                ];
            }
        }

        $this->batchUpdateEloChange($this->conn, $eloUpdates);

        foreach ($ratings as $gameId => $players) {
            foreach ($players as $accountId => $rating) {
                $this->updatePlayerStats(
                    $this->conn,
                    (int)$accountId,
                    (int)$gameId,
                    (int)$rating,
                    $matchesPlayed[$gameId][$accountId],
                    $wins[$gameId][$accountId],
                    $losses[$gameId][$accountId],
                    $minRankedMatches[$gameId] ?? 0
                );
            }
        }
    }
}
