<?php
declare(strict_types=1);

namespace Kickback\Backend\Controllers;

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
     * Calculate the expected score between two ratings.
     */
    public function calculateExpectedScore(int $ratingA, int $ratingB) : float
    {
        return 1 / (1 + pow(10, ($ratingB - $ratingA) / 400));
    }

    /**
     * Calculate a new rating for a player.
     */
    public function calculateNewRating(mixed $currentRating, mixed $expectedScore, mixed $actualScore) : int
    {
        return intval(round($currentRating + $this->kFactor * ($actualScore - $expectedScore)));
    }

    /**
     * Calculate team average rating.
     * @param array<int,int> $ratings
     * @param array<int>     $team
     */
    public function calculateTeamAverage(array $ratings, array $team) : float
    {
        $totalRating = array_reduce($team, function ($sum, $accountId) use ($ratings) {
            return $sum + ($ratings[$accountId] ?? $this->baseRating);
        }, 0);

        return $totalRating / count($team);
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
     * Calculate ELO changes for group matches.
     * @param array<int> $ratings
     * @param array<int> $loserIds
     * @return array<array{
     *     winner_id:     int,
     *     winner_change: int,
     *     loser_id:      int,
     *     loser_change:  int
     * }>
     */
    public function calculateGroupMatchElo(array $ratings, int $winnerId, array $loserIds) : array
    {
        $updates = [];

        foreach ($loserIds as $loserId) {
            $expectedWin = $this->calculateExpectedScore($ratings[$winnerId], $ratings[$loserId]);
            $expectedLose = 1 - $expectedWin;

            // Winner update
            $newWinnerRating = $this->calculateNewRating($ratings[$winnerId], $expectedWin, 1);
            $eloChangeWinner = $newWinnerRating - $ratings[$winnerId];
            $ratings[$winnerId] = $newWinnerRating;

            // Loser update
            $newLoserRating = $this->calculateNewRating($ratings[$loserId], $expectedLose, 0);
            $eloChangeLoser = $newLoserRating - $ratings[$loserId];
            $ratings[$loserId] = $newLoserRating;

            // Collect changes
            $updates[] = [
                'winner_id' => $winnerId,
                'winner_change' => $eloChangeWinner,
                'loser_id' => $loserId,
                'loser_change' => $eloChangeLoser,
            ];
        }

        return $updates;
    }

    /**
     * Adjust ELO for tied matches in group battles.
     * @param array<int,int> $ratings
     * @param array<int>     $participantIds
     * @return array<array{account_id: int,  elo_change: int}>
     */
    public function calculateTieElo(array $ratings, array $participantIds) : array
    {
        $updates = [];
        $averageRating = $this->calculateTeamAverage($ratings, $participantIds);

        foreach ($participantIds as $accountId) {
            $eloChange = 0; // No change in a tie
            $updates[] = [
                'account_id' => $accountId,
                'elo_change' => $eloChange,
            ];
        }

        return $updates;
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
                if (!isset($teamWins[$team])) {
                    $teamWins[$team] = $win;
                }

                $ratings[$gameId][$accountId] = $ratings[$gameId][$accountId] ?? $this->getBaseRating();
                $wins[$gameId][$accountId] = $wins[$gameId][$accountId] ?? 0;
                $losses[$gameId][$accountId] = $losses[$gameId][$accountId] ?? 0;
                $matchesPlayed[$gameId][$accountId] = $matchesPlayed[$gameId][$accountId] ?? 0;
            }

            $teamNames = array_keys($teams);
            if (2 !== count($teamNames)) {
                continue; // process only matches with exactly two teams
            }

            $teamA = $teamNames[0];
            $teamB = $teamNames[1];

            $teamAElo = $this->calculateTeamAverage($ratings[$gameId], $teams[$teamA]);
            $teamBElo = $this->calculateTeamAverage($ratings[$gameId], $teams[$teamB]);

            $expectedA = $this->calculateExpectedScore((int)$teamAElo, (int)$teamBElo);
            $expectedB = $this->calculateExpectedScore((int)$teamBElo, (int)$teamAElo);

            $teamAWon = $teamWins[$teamA] > $teamWins[$teamB];
            $teamBWon = $teamWins[$teamB] > $teamWins[$teamA];

            $actualA = $teamAWon ? 1.0 : ($teamBWon ? 0.0 : 0.5);
            $actualB = $teamBWon ? 1.0 : ($teamAWon ? 0.0 : 0.5);

            foreach ([$teamA, $teamB] as $index => $teamName) {
                $expected = $index === 0 ? $expectedA : $expectedB;
                $actual = $index === 0 ? $actualA : $actualB;

                foreach ($teams[$teamName] as $accountId) {
                    $oldRating = $ratings[$gameId][$accountId];
                    $newRating = $this->calculateNewRating($oldRating, $expected, $actual);
                    $eloChange = $newRating - $oldRating;

                    $ratings[$gameId][$accountId] = $newRating;
                    $matchesPlayed[$gameId][$accountId]++;

                    if (1.0 === $actual) {
                        $wins[$gameId][$accountId]++;
                    } elseif (0.0 === $actual) {
                        $losses[$gameId][$accountId]++;
                    }

                    $eloUpdates[] = [
                        'game_match_id' => $matchId,
                        'account_id' => $accountId,
                        'elo_change' => $eloChange,
                    ];
                }
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
