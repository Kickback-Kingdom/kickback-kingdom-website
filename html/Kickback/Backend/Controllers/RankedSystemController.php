<?php
declare(strict_types=1);

namespace Kickback\Backend\Controllers;

class RankedSystemController
{
    private \mysqli $conn;
    private EloController $elo;

    public function __construct(\mysqli $conn, EloController $elo)
    {
        $this->conn = $conn;
        $this->elo = $elo;
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

                $ratings[$gameId][$accountId] = $ratings[$gameId][$accountId] ?? $this->elo->getBaseRating();
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

            $teamAElo = $this->elo->calculateTeamAverage($ratings[$gameId], $teams[$teamA]);
            $teamBElo = $this->elo->calculateTeamAverage($ratings[$gameId], $teams[$teamB]);

            $expectedA = $this->elo->calculateExpectedScore((int)$teamAElo, (int)$teamBElo);
            $expectedB = $this->elo->calculateExpectedScore((int)$teamBElo, (int)$teamAElo);

            $teamAWon = $teamWins[$teamA] > $teamWins[$teamB];
            $teamBWon = $teamWins[$teamB] > $teamWins[$teamA];

            $actualA = $teamAWon ? 1.0 : ($teamBWon ? 0.0 : 0.5);
            $actualB = $teamBWon ? 1.0 : ($teamAWon ? 0.0 : 0.5);

            foreach ([$teamA, $teamB] as $index => $teamName) {
                $expected = $index === 0 ? $expectedA : $expectedB;
                $actual = $index === 0 ? $actualA : $actualB;

                foreach ($teams[$teamName] as $accountId) {
                    $oldRating = $ratings[$gameId][$accountId];
                    $newRating = $this->elo->calculateNewRating($oldRating, $expected, $actual);
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

        $this->elo->batchUpdateEloChange($this->conn, $eloUpdates);

        foreach ($ratings as $gameId => $players) {
            foreach ($players as $accountId => $rating) {
                $this->elo->updatePlayerStats(
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
