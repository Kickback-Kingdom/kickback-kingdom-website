<?php
declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use Kickback\Backend\Models\Response;
use Kickback\Backend\Models\ForeignRecordId;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vChallengeHistory;
use Kickback\Backend\Views\vPageResult;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vDateTime;
use Kickback\Services\Database;
use Kickback\Services\Session;

class ChallengeHistoryController
{
    public static function getMatchHistory(vRecordId $gameId, int $page, int $itemsPerPage): Response
    {
        $conn = Database::getConnection();
        $page = max(1, $page);
        $itemsPerPage = max(1, $itemsPerPage);
        $offset = ($page - 1) * $itemsPerPage;

        // Count query to get the total number of matches
        $countSql = '
            SELECT 
                COUNT(*) AS total
            FROM 
                game_match gm
            WHERE 
                ((gm.bracket = 0 AND gm.round = 0 AND gm.`match` = 0 AND gm.`set` = 0)
                OR (gm.bracket <> 0 AND gm.round <> 0 AND gm.`match` <> 0 AND gm.`set` = 1))
                AND gm.game_id = ?;
        ';

        $stmt = $conn->prepare($countSql);
        if (!$stmt) {
            return new Response(false, 'Failed to prepare count statement for match history', null);
        }

        $stmt->bind_param('i', $gameId->crand);
        $stmt->execute();
        $countResult = $stmt->get_result();
        $totalMatchesStr = $countResult->fetch_assoc()['total'] ?? '0';
        $totalMatches = intval($totalMatchesStr);
        $stmt->close();

        if ($totalMatches === 0) {
            return new Response(false, 'No match history found for the given game ID', new vPageResult(0, [], $itemsPerPage, $page));
        }

        // Main query to fetch paginated match history
        $mainSql = '
            SELECT 
                gm.id AS match_id,
                gm.game_id,
                gm.tournament_id,
                gm.date AS match_date,
                gm.bracket,
                gm.round,
                gm.match,
                gm.set,
                COUNT(gr.account_id) AS player_count
            FROM 
                game_match gm
            LEFT JOIN 
                game_record gr
            ON 
                gm.id = gr.game_match_id
            WHERE 
                ((gm.bracket = 0 AND gm.round = 0 AND gm.`match` = 0 AND gm.`set` = 0)
                OR (gm.bracket <> 0 AND gm.round <> 0 AND gm.`match` <> 0 AND gm.`set` = 1))
                AND gm.game_id = ?
            GROUP BY 
                gm.id, gm.game_id, gm.tournament_id, gm.date, gm.bracket, gm.round, gm.match, gm.set
            ORDER BY 
                gm.date DESC
            LIMIT ? OFFSET ?;
        ';

        $stmt = $conn->prepare($mainSql);
        if (!$stmt) {
            return new Response(false, 'Failed to prepare main query for match history', null);
        }

        $stmt->bind_param('iii', $gameId->crand, $itemsPerPage, $offset);
        $stmt->execute();
        $result = $stmt->get_result();

        $matchHistory = [];
        while ($row = $result->fetch_assoc()) {
            $matchHistory[] = self::row_to_vChallengeHistory($row, true);
        }

        $stmt->close();

        $pageResult = new vPageResult($totalMatches, $matchHistory, $itemsPerPage, $page);

        return new Response(true, 'Match history retrieved successfully', $pageResult);
    }


    /**
    * @return array<string,array<vAccount>>
    */
    public static function queryMatchPlayersGroupedByTeamName(vChallengeHistory $challengeHistory) : array
    {
        $resp = self::queryMatchPlayersGroupedByTeamNameAsResponse($challengeHistory);
        if ($resp->success) {
            // @phpstan-ignore-next-line
            return $resp->data;
        } else {
            throw new \Exception($resp->message);
        }
    }

    public static function queryMatchPlayersGroupedByTeamNameAsResponse(vChallengeHistory $challengeHistory) : Response
    {
        $conn = Database::getConnection();
        $sql = 'SELECT a.*, r.elo_change, r.team_name, r.character, r.random_character, r.game_match_id, r.game_id FROM `game_record` r
                inner join v_account_info a on r.account_id = a.Id
                where r.game_match_id = ?';
                
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return new Response(false, 'Failed to prepare main query for match players', null);
        }
        
        $stmt->bind_param('i', $challengeHistory->crand);
        $stmt->execute();
        $result = $stmt->get_result();
        if ( $result === false ) {
            return new Response(false, 'SQL `get_result` function failed; could not retrieve match players', null);
        }

        $teamPlayers = self::sqlResultToPlayersGroupedByTeamName($result);

        $stmt->close();
        
        return new Response(true, 'Match players retrieved successfully', $teamPlayers);
    }

    /**
    * @return array<string,array<vAccount>>
    */
    private static function sqlResultToPlayersGroupedByTeamName(\mysqli_result $result) : array
    {
        $teamPlayers = [];
        while ($row = $result->fetch_assoc()) {
            $player = AccountController::row_to_vAccount($row);
            $teamPlayers[$row["team_name"]][] = $player;
        }
        return $teamPlayers;
    }

    public static function row_to_vChallengeHistory(array $row, bool $populatePlayers = false) : vChallengeHistory {
        $challengeHistory = new vChallengeHistory('', (int)$row['match_id']);
        $challengeHistory->gameId = new ForeignRecordId('', (int)$row['game_id']);
        $challengeHistory->tournamentId = new ForeignRecordId('', (int)$row['tournament_id']);
        $challengeHistory->playerCount = (int)$row['player_count'];
        $challengeHistory->dateTime = new vDateTime((string)$row["match_date"]);
        if ($populatePlayers)
        {
            $challengeHistory->teams = self::queryMatchPlayersGroupedByTeamName($challengeHistory);
        }

        return $challengeHistory;
    }
}

?>
