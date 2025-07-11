<?php
declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use Kickback\Backend\Models\Game;
use Kickback\Backend\Views\vGame;
use Kickback\Backend\Views\vMedia;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Models\Response;
use Kickback\Services\Database;
use Kickback\Common\Primitives\Arr;
use Kickback\Common\Primitives\Str;

class GameController
{
    public static function getDistinctCharacters(vRecordId $gameId): Response {
        $conn = Database::getConnection();
    
        $sql = "
            SELECT DISTINCT `character`
            FROM `game_record`
            WHERE `character` IS NOT NULL
              AND `character` <> ''
              AND game_id = ?
              order by 1
        ";
    
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return new Response(false, "Failed to prepare the SQL statement.");
        }
    
        if (!$stmt->bind_param('i', $gameId->crand)) {
            return new Response(false, "Failed to bind parameters.");
        }
    
        if (!$stmt->execute()) {
            return new Response(false, "Failed to execute the SQL statement.");
        }
    
        $result = $stmt->get_result();
        if (!$result) {
            return new Response(false, "Failed to retrieve the result set.");
        }
    
        $characters = [];
        while ($row = $result->fetch_assoc()) {
            $characters[] = $row['character'];
        }
    
        $stmt->close();
    
        return new Response(true, "Characters retrieved successfully.", $characters);
    }

    public static function getCurrentWinStreak(vRecordId $gameId): Response
    {
        $conn = Database::getConnection();
    
        $sql = "
            WITH RankedGames AS (
                SELECT
                    game_id,
                    account_id,
                    win,
                    game_match_id,
                    Date,
                    ROW_NUMBER() OVER (PARTITION BY game_id, account_id ORDER BY Date DESC) AS rn_desc
                FROM v_ranked_matches
                WHERE game_id = ?
            ),
            WinGroups AS (
                SELECT
                    game_id,
                    account_id,
                    game_match_id,
                    Date,
                    rn_desc,
                    SUM(CASE WHEN win = 1 THEN 0 ELSE 1 END) OVER (PARTITION BY game_id, account_id ORDER BY rn_desc) AS lose_group
                FROM RankedGames
            ),
            Streaks AS (
                SELECT
                    game_id,
                    account_id,
                    COUNT(*) AS current_streak
                FROM WinGroups
                WHERE lose_group = 0 -- Only calculate streaks when lose group is empty
                GROUP BY game_id, account_id
            ),
            MaxStreak AS (
                SELECT
                    game_id,
                    MAX(current_streak) AS max_streak
                FROM Streaks
                WHERE game_id = ?
                GROUP BY game_id
            )
            SELECT
                a.*,
                s.game_id,
                s.current_streak
            FROM Streaks s
            JOIN MaxStreak m
            ON s.game_id = m.game_id AND s.current_streak = m.max_streak
            inner join v_account_info a on a.Id = s.account_id
            WHERE s.game_id = ? and s.current_streak > 1
            ORDER BY s.account_id;
        ";
    
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return new Response(false, "Failed to prepare the SQL statement.");
        }
    
        if (!$stmt->bind_param('iii', $gameId->crand, $gameId->crand, $gameId->crand)) {
            return new Response(false, "Failed to bind parameters.");
        }
    
        if (!$stmt->execute()) {
            return new Response(false, "Failed to execute the SQL statement.");
        }
    
        $result = $stmt->get_result();
        if (!$result) {
            return new Response(false, "Failed to retrieve the result set.");
        }
    
        $winStreaks = [];
        while ($row = $result->fetch_assoc()) {
            $winStreaks[] = [
                'game_id' => $row['game_id'],
                'account' => AccountController::row_to_vAccount($row),
                'current_streak' => (int)$row['current_streak']
            ];
        }
    
        $stmt->close();
    
        if (Arr::empty($winStreaks)) {
            return new Response(false, "No win streaks found for the given game.");
        }
    
        return new Response(true, "Win streaks retrieved successfully.", $winStreaks);
    }

    public static function getAllTimeWinStreak(vRecordId $gameId): Response {
        $conn = Database::getConnection();
    
        $sql = "WITH RankedGames AS (
                    SELECT
                        `game_id`,
                        `account_id`,
                        `win`,
                        `game_match_id`,
                        `Date`,
                        ROW_NUMBER() OVER (PARTITION BY `game_id`, `account_id` ORDER BY `Date` ASC, `game_match_id` ASC) AS rn_asc
                    FROM `v_ranked_matches`
                    WHERE `game_id` = ?
                ),
                WinGroups AS (
                    SELECT
                        `game_id`,
                        `account_id`,
                        `game_match_id`,
                        `Date`,
                        rn_asc,
                        win,
                        SUM(CASE WHEN `win` = 0 THEN 1 ELSE 0 END) OVER (PARTITION BY `game_id`, `account_id` ORDER BY rn_asc) AS lose_group
                    FROM RankedGames
                ),
                AllStreaks AS (
                    SELECT
                        `game_id`,
                        `account_id`,
                        COUNT(*) AS streak_length
                    FROM WinGroups
                    WHERE `win` = 1
                    GROUP BY `game_id`, `account_id`, lose_group
                ),
                MaxStreak AS (
                    SELECT
                        `game_id`,
                        `account_id`,
                        MAX(streak_length) AS max_streak
                    FROM AllStreaks
                    GROUP BY `game_id`, `account_id`
                ),
                GameMaxStreak AS (
                    SELECT
                        `game_id`,
                        MAX(max_streak) AS all_time_max_streak
                    FROM MaxStreak
                    WHERE `game_id` = ?
                    GROUP BY `game_id`
                )
                SELECT
                    a.*,
                    ms.`game_id`,
                    ms.max_streak
                FROM MaxStreak ms
                JOIN GameMaxStreak gm
                    ON ms.`game_id` = gm.`game_id` AND ms.max_streak = gm.all_time_max_streak
                INNER JOIN `v_account_info` a
                    ON a.`Id` = ms.`account_id`
                WHERE ms.`game_id` = ? and ms.max_streak > 1
                ORDER BY a.`username`;

        ";
    
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return new Response(false, "Failed to prepare the SQL statement.");
        }
    
        if (!$stmt->bind_param('iii', $gameId->crand, $gameId->crand, $gameId->crand)) {
            return new Response(false, "Failed to bind parameters.");
        }
    
        if (!$stmt->execute()) {
            return new Response(false, "Failed to execute the SQL statement.");
        }
    
        $result = $stmt->get_result();
        if (!$result) {
            return new Response(false, "Failed to retrieve the result set.");
        }
    
        $winStreaks = [];
        while ($row = $result->fetch_assoc()) {
            $winStreaks[] = [
                'game_id' => $row['game_id'],
                'account' => AccountController::row_to_vAccount($row),
                'max_streak' => (int)$row['max_streak']
            ];
        }
    
        $stmt->close();
    
        if (Arr::empty($winStreaks)) {
            return new Response(false, "No all-time win streaks found for the given game.");
        }
    
        return new Response(true, "All-Time win streaks retrieved successfully.", $winStreaks);
    }

    public static function getBestRandomPlayer(vRecordId $gameId): Response {
        $conn = Database::getConnection();
    
        $sql = "WITH RandomWins AS (
                    SELECT
                        gr.game_id,
                        gr.account_id,
                        COUNT(*) AS total_random_wins
                    FROM game_record gr
                    WHERE gr.random_character = 1 AND gr.win = 1 and gr.game_id = ?
                    GROUP BY gr.game_id, gr.account_id
                ),
                GameBestRandom AS (
                    SELECT
                        game_id,
                        MAX(total_random_wins) AS max_random_wins
                    FROM RandomWins
                    WHERE game_id = ?
                    GROUP BY game_id
                )
                SELECT
                    a.*,
                    rw.game_id,
                    rw.total_random_wins
                FROM RandomWins rw
                JOIN GameBestRandom gbr
                    ON rw.game_id = gbr.game_id AND rw.total_random_wins = gbr.max_random_wins
                INNER JOIN v_account_info a
                    ON a.Id = rw.account_id
                WHERE rw.game_id = ?
                ORDER BY a.username;


        ";
    
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return new Response(false, "Failed to prepare the SQL statement.");
        }
    
        if (!$stmt->bind_param('iii', $gameId->crand, $gameId->crand, $gameId->crand)) {
            return new Response(false, "Failed to bind parameters.");
        }
    
        if (!$stmt->execute()) {
            return new Response(false, "Failed to execute the SQL statement.");
        }
    
        $result = $stmt->get_result();
        if (!$result) {
            return new Response(false, "Failed to retrieve the result set.");
        }
    
        $winStreaks = [];
        while ($row = $result->fetch_assoc()) {
            $winStreaks[] = [
                'game_id' => $row['game_id'],
                'account' => AccountController::row_to_vAccount($row),
                'total_random_wins' => (int)$row['total_random_wins']
            ];
        }
    
        $stmt->close();
    
        if (Arr::empty($winStreaks)) {
            return new Response(false, "No random wins found for the given game.");
        }
    
        return new Response(true, "Random wins retrieved successfully.", $winStreaks);
    }
    
    public static function getGames(
        bool   $rankedOnly = false,
        string $searchTerm = '',
        int    $page = 0,
        int    $pageSize = 0
    ): Response
    {
        $conn = Database::getConnection();
    
        $sql = "SELECT Id, `Name`, `Desc`, MinRankedMatches, ShortName, CanRank, media_icon_id, media_banner_id, media_banner_mobile_id, icon_path, banner_path, banner_mobile_path, locator
                FROM kickbackdb.v_game_info";
    
        $conditions = [];
        $params = [];
        $types = '';
    
        if ($rankedOnly) {
            $conditions[] = "CanRank = ?";
            $params[] = 1;
            $types .= 'i';
        }
    
        // Search term filter
        if (!Str::empty($searchTerm)) {
            $conditions[] = "(Name LIKE ? OR Desc LIKE ?)";
            $params[] = "%{$searchTerm}%";
            $params[] = "%{$searchTerm}%";
            $types .= 'ss';
        }
    
        if (!Arr::empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
    
        $sql .= " ORDER BY Name";
    
        // Pagination handling
        if ($page >= 0 && $pageSize > 0) {
            $offset = $page * $pageSize;
            $sql .= " LIMIT ?, ?";
            $params[] = $offset;
            $params[] = $pageSize;
            $types .= 'ii';
        }
    
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return new Response(false, "Failed to prepare the SQL statement.");
        }

        if (!Arr::empty($params)) {
            if (!$stmt->bind_param($types, ...$params)) {
                return new Response(false, "Failed to bind parameters.");
            }
        }
    
        if (!$stmt->execute()) {
            return new Response(false, "Failed to execute the SQL statement.");
        }
    
        $result = $stmt->get_result();
        if (!$result) {
            return new Response(false, "Failed to retrieve the result set.");
        }
    
        
        $games = [];
        while ($row = $result->fetch_assoc()) {
            $game = self::row_to_vGame($row);
            $games[] = $game;
        }

        $stmt->close();
    
        return new Response(true, "Retrieved games successfully", $games);
    }

    public static function getGameByLocator(string $locator)
    {
        $conn = Database::getConnection();
        
        $sql = "SELECT Id, `Name`, `Desc`, MinRankedMatches, ShortName, CanRank, media_icon_id, media_banner_id, media_banner_mobile_id, icon_path, banner_path, banner_mobile_path, locator 
                FROM kickbackdb.v_game_info 
                WHERE `locator` = ?";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return new Response(false, "Failed to prepare the SQL statement.");
        }

        if (!$stmt->bind_param('s', $locator)) {
            return new Response(false, "Failed to bind parameters.");
        }

        if (!$stmt->execute()) {
            return new Response(false, "Failed to execute the SQL statement.");
        }

        $result = $stmt->get_result();
        if (!$result) {
            return new Response(false, "Failed to retrieve the result set.");
        }

        $game = null;
        if ($row = $result->fetch_assoc()) {
            $game = self::row_to_vGame($row);
        }

        $stmt->close();

        if ($game === null) {
            return new Response(false, "Game not found.");
        }

        return new Response(true, "Game retrieved successfully", $game);
    }

    
    private static function row_to_vGame($row) : vGame {
        $game = new vGame('', $row['Id']);
        $game->name = $row['Name'];
        $game->description = $row['Desc'];
        $game->minRankedMatches = $row['MinRankedMatches'];
        $game->shortName = $row['ShortName'];
        $game->canRank = $row['CanRank'] == 1;
        $game->locator = $row["locator"];

        if ($row['media_icon_id'] != null)
        {
            $icon = new vMedia('', $row['media_icon_id']);
            $icon->setMediaPath($row['icon_path']);
            $game->icon = $icon;
        }

        if ($row['media_banner_id'] != null)
        {
            $banner = new vMedia('', $row['media_banner_id']);
            $banner->setMediaPath($row['banner_path']);
            $game->banner = $banner;
        }

        if ($row['media_banner_mobile_id'] != null)
        {
            $bannerMobile = new vMedia('', $row['media_banner_mobile_id']);
            $bannerMobile->setMediaPath($row['banner_mobile_path']);
            $game->bannerMobile = $bannerMobile;
        }

        return $game;
    }

    private static function insert(Game $game) : Response {
        return new Response(false, 'GameController::Insert not implemented');
    }

    public static function countRankedMatches(int $accountId, ?int $gameId = null): int
    {
        $conn = Database::getConnection();

        if ($gameId !== null) {
            $sql = "SELECT COUNT(*) FROM v_ranked_matches 
                    WHERE account_id = ? AND game_id = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) return 0;
            $stmt->bind_param("ii", $accountId, $gameId);
        } else {
            $sql = "SELECT COUNT(*) FROM v_ranked_matches 
                    WHERE account_id = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) return 0;
            $stmt->bind_param("i", $accountId);
        }

        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        return (int)$count;
    }

    public static function countRankedMatchesBetween(int $accountId, string $startDate, string $endDate, ?int $gameId = null): int
    {
        $conn = Database::getConnection();

        if ($gameId !== null) {
            $sql = "SELECT COUNT(*) FROM v_ranked_matches 
                    WHERE account_id = ? AND game_id = ?
                    AND Date BETWEEN ? AND ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) return 0;
            $stmt->bind_param("iiss", $accountId, $gameId, $startDate, $endDate);
        } else {
            $sql = "SELECT COUNT(*) FROM v_ranked_matches 
                    WHERE account_id = ?
                    AND Date BETWEEN ? AND ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) return 0;
            $stmt->bind_param("iss", $accountId, $startDate, $endDate);
        }

        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        return (int)$count;
    }

    public static function countRankedWins(int $accountId, ?int $gameId = null): int
    {
        $conn = Database::getConnection();

        if ($gameId !== null) {
            $sql = "SELECT COUNT(*) FROM v_ranked_matches 
                    WHERE account_id = ? AND win = 1 AND game_id = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) return 0;
            $stmt->bind_param("ii", $accountId, $gameId);
        } else {
            $sql = "SELECT COUNT(*) FROM v_ranked_matches 
                    WHERE account_id = ? AND win = 1";
            $stmt = $conn->prepare($sql);
            if (!$stmt) return 0;
            $stmt->bind_param("i", $accountId);
        }

        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        return (int)$count;
    }

    public static function countRankedWinsBetween(int $accountId, string $startDate, string $endDate, ?int $gameId = null): int
    {
        $conn = Database::getConnection();

        if ($gameId !== null) {
            $sql = "SELECT COUNT(*) FROM v_ranked_matches 
                    WHERE account_id = ? AND win = 1 AND game_id = ?
                    AND Date BETWEEN ? AND ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) return 0;
            $stmt->bind_param("iiss", $accountId, $gameId, $startDate, $endDate);
        } else {
            $sql = "SELECT COUNT(*) FROM v_ranked_matches 
                    WHERE account_id = ? AND win = 1
                    AND Date BETWEEN ? AND ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) return 0;
            $stmt->bind_param("iss", $accountId, $startDate, $endDate);
        }

        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        return (int)$count;
    }


}
?>
