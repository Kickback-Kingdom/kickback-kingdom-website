<?php

declare(strict_types=1);

namespace Kickback\Backend\Controllers;

class EloController
{
    private $kFactor;
    private $baseRating;

    public function __construct($baseRating = 1500, $kFactor = 30)
    {
        $this->baseRating = $baseRating;
        $this->kFactor = $kFactor;
    }

    public function getBaseRating()
    {
        return $this->baseRating;
    }

    public function getKFactor()
    {
        return $this->kFactor;
    }
    
    /**
     * Calculate the expected score between two players.
     */
    public function calculateExpectedScore($ratingA, $ratingB)
    {
        return 1 / (1 + pow(10, ($ratingB - $ratingA) / 400));
    }

    /**
     * Calculate new rating for a player.
     */
    public function calculateNewRating($currentRating, $expectedScore, $actualScore)
    {
        return round($currentRating + $this->kFactor * ($actualScore - $expectedScore));
    }

    /**
     * Calculate team average rating.
     */
    public function calculateTeamAverage($ratings, $team)
    {
        $totalRating = array_reduce($team, function ($sum, $accountId) use ($ratings) {
            return $sum + ($ratings[$accountId] ?? $this->baseRating);
        }, 0);

        return $totalRating / count($team);
    }

    /**
     * Batch update ELO changes in the database.
     */
    public function batchUpdateEloChange($conn, array $eloUpdates)
    {
        if (empty($eloUpdates)) {
            return;
        }

        $query = 'UPDATE game_record SET elo_change = CASE';
        $ids = [];

        foreach ($eloUpdates as $update) {
            $gameMatchId = (int)$update['game_match_id'];
            $accountId = (int)$update['account_id'];
            $eloChange = (int)$update['elo_change'];

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
        $conn,
        $accountId,
        $gameId,
        $newRating,
        $matchesPlayed,
        $wins,
        $losses,
        $minRankedMatches
    ) {
        $isRanked = $matchesPlayed >= $minRankedMatches ? 1 : 0;

        $stmt = mysqli_prepare($conn, 'INSERT INTO account_game_elo (account_id, game_id, elo_rating, is_ranked, total_matches, total_wins, total_losses, win_rate)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE elo_rating = VALUES(elo_rating), is_ranked = VALUES(is_ranked), total_matches = VALUES(total_matches), total_wins = VALUES(total_wins), total_losses = VALUES(total_losses), win_rate = VALUES(win_rate)');
        if (!$stmt) {
            die("Error preparing statement: " . mysqli_error($conn));
        }

        $winRate = $matchesPlayed > 0 ? $wins / $matchesPlayed : 0;
        mysqli_stmt_bind_param($stmt, 'iiidiiid', $accountId, $gameId, $newRating, $isRanked, $matchesPlayed, $wins, $losses, $winRate);

        if (!mysqli_stmt_execute($stmt)) {
            die("Error executing statement: " . mysqli_stmt_error($stmt));
        }

        mysqli_stmt_close($stmt);
    }

    /**
     * Calculate ELO changes for group matches.
     */
    public function calculateGroupMatchElo($ratings, $winnerId, $loserIds)
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
     */
    public function calculateTieElo($ratings, $participantIds)
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
}
