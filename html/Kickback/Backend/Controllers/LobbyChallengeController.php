<?php
declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use Kickback\Backend\Models\LobbyChallenge;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Models\PlayStyle;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vLobbyChallenge;
use Kickback\Services\Database;
use Kickback\Services\Session;
use Kickback\Backend\Views\vChallengePlayer;
use Kickback\Backend\Views\vDateTime;
use Kickback\Backend\Views\vChallengeDetailsConsensus;
use Kickback\Backend\Views\vPlayerConsensusDetails;

class LobbyChallengeController
{
    
    public static function processFinalResults(vRecordId $challengeId): Response {
        $conn = Database::getConnection();

        try {
            // Begin a transaction
            $conn->begin_transaction();

            // Step 1: Check if all players have submitted their results
            $submissionCheck = self::hasEveryoneSubmitted($challengeId);
            if (!$submissionCheck->success) {
                throw new \Exception("Failed to check submission status: " . $submissionCheck->message);
            }

            if (!$submissionCheck->data) {
                throw new \Exception("Not all players have submitted their results.");
            }

            // Step 2: Get the consensus for the challenge
            $consensusResp = self::getChallengeConsensus($challengeId);
            if (!$consensusResp->success) {
                throw new \Exception("Failed to calculate consensus: " . $consensusResp->message);
            }
            $consensus = $consensusResp->data;

            // Step 3: Retrieve game_id
            $gameIdQuery = "
                SELECT l.game_id
                FROM lobby_challenge lc
                INNER JOIN lobby l ON lc.ref_lobby_ctime = l.ctime AND lc.ref_lobby_crand = l.crand
                WHERE lc.ctime = ? AND lc.crand = ?";
            $stmt = $conn->prepare($gameIdQuery);
            if (!$stmt) {
                throw new \Exception("Failed to prepare game_id query.");
            }
            $stmt->bind_param('si', $challengeId->ctime, $challengeId->crand);
            $stmt->execute();
            $stmt->bind_result($gameId);
            if (!$stmt->fetch() || !$gameId) {
                throw new \Exception("Failed to retrieve game_id for the challenge.");
            }
            $stmt->close();

            // Step 4: Insert into game_match
            $gameMatchInsertQuery = "
                INSERT INTO game_match (game_id, `desc`, `bracket`, `round`, `match`, `set`)
                SELECT
                    l.game_id,
                    'Final match results for challenge',
                    lc.bracket,
                    lc.round,
                    lc.match,
                    lc.set
                FROM lobby_challenge lc
                INNER JOIN lobby l ON lc.ref_lobby_ctime = l.ctime AND lc.ref_lobby_crand = l.crand
                WHERE lc.ctime = ? AND lc.crand = ?";
            $stmt = $conn->prepare($gameMatchInsertQuery);
            if (!$stmt) {
                throw new \Exception("Failed to prepare game_match insert query.");
            }
            $stmt->bind_param('si', $challengeId->ctime, $challengeId->crand);
            $stmt->execute();

            if ($stmt->affected_rows <= 0) {
                throw new \Exception("Failed to insert game match record.");
            }

            $gameMatchId = $stmt->insert_id;
            $stmt->close();

            // Step 5: Insert into game_record for each player
            $gameRecordInsertQuery = "
                INSERT INTO game_record (game_id, account_id, win, game_match_id, team_name, `character`, random_character)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($gameRecordInsertQuery);
            if (!$stmt) {
                throw new \Exception("Failed to prepare game_record insert query.");
            }

            foreach ($consensus->playerConsensusDetails as $playerId => $playerConsensus) {
                $win = ($consensus->winningTeam === $playerConsensus->teamName) ? 1 : 0;
                $randomCharacter = $playerConsensus->pickedRandom !== null ? (int)$playerConsensus->pickedRandom : 0;

                $stmt->bind_param(
                    'iiisssi',
                    $gameId,                        // Retrieved game_id
                    $playerId,                      // account_id
                    $win,                           // win
                    $gameMatchId,                   // game_match_id
                    $playerConsensus->teamName,     // team_name
                    $playerConsensus->character,    // character
                    $randomCharacter                // random_character
                );
                $stmt->execute();

                if ($stmt->errno) {
                    throw new \Exception("Failed to insert game record for player ID $playerId: " . $stmt->error);
                }
            }
            $stmt->close();

            // Commit the transaction
            $conn->commit();

            // Step 6: Return success
            return new Response(
                true,
                "Final results processed successfully.",
                [
                    'game_match_id' => $gameMatchId,
                    'consensus' => $consensus,
                ]
            );
        } catch (\Exception $e) {
            // Roll back the transaction on failure
            $conn->rollback();

            return new Response(false, $e->getMessage(), null);
        }
    }

    public static function hasEveryoneSubmitted(vRecordId $challengeId): Response
    {
        $conn = Database::getConnection();

        try {
            // Query to count total players in the challenge
            $playerCountQuery = "
                SELECT COUNT(*) AS player_count
                FROM lobby_challenge_account
                WHERE ref_challenge_ctime = ? AND ref_challenge_crand = ?";
            $stmt = $conn->prepare($playerCountQuery);
            if (!$stmt) {
                throw new \Exception("Failed to prepare player count query.");
            }
            $stmt->bind_param('si', $challengeId->ctime, $challengeId->crand);
            $stmt->execute();
            $playerCountResult = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $totalPlayers = (int)$playerCountResult['player_count'];

            // Query to count unique players who have submitted results
            $submissionCountQuery = "
                SELECT COUNT(DISTINCT account_id) AS submission_count
                FROM lobby_challenge_result
                WHERE ref_challenge_ctime = ? AND ref_challenge_crand = ?";
            $stmt = $conn->prepare($submissionCountQuery);
            if (!$stmt) {
                throw new \Exception("Failed to prepare submission count query.");
            }
            $stmt->bind_param('si', $challengeId->ctime, $challengeId->crand);
            $stmt->execute();
            $submissionCountResult = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $submittedPlayers = (int)$submissionCountResult['submission_count'];

            // Determine if all players have submitted
            $everyoneSubmitted = $submittedPlayers === $totalPlayers;

            return new Response(
                true,
                $everyoneSubmitted
                    ? "All players have submitted their results."
                    : "Not all players have submitted their results.",
                $everyoneSubmitted // Return true if all players have submitted, false otherwise
            );
        } catch (\Exception $e) {
            return new Response(false, $e->getMessage(), false);
        }
    }


    public static function getChallengeConsensus(vRecordId $challengeId): Response
    {
        $conn = Database::getConnection();
        $query = "SELECT 
                a.account_id AS reporter_id,
                a.team_name as reporter_team,
                r.reported_winning_team,
                r.did_win,
                r.vote_void,
                rd.reported_player_id,
                rd.team_name,
                rd.character,
                rd.picked_random
            FROM 
                lobby_challenge_account a
            left join
                lobby_challenge_result r on r.account_id = a.account_id and r.ref_challenge_ctime = a.ref_challenge_ctime and r.ref_challenge_crand = a.ref_challenge_crand
            LEFT JOIN 
                lobby_challenge_result_details rd
                ON r.ref_challenge_ctime = rd.ref_challenge_ctime
                AND r.ref_challenge_crand = rd.ref_challenge_crand
                AND r.account_id = rd.reporter_id
            WHERE 
                r.ref_challenge_ctime = ? 
                AND r.ref_challenge_crand = ?";

        $stmt = $conn->prepare($query);
        if (!$stmt) {
            return new Response(false, 'Failed to prepare query.', null);
        }

        $stmt->bind_param('si', $challengeId->ctime, $challengeId->crand);
        $stmt->execute();

        $result = $stmt->get_result();
        if (!$result) {
            return new Response(false, 'Failed to execute query.', null);
        }

        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (empty($rows)) {
            return new Response(false, 'No data found for the given challenge.', null);
        }

        // Process rows into consensus data
        $consensusData = self::calculateChallengeConsensus($rows);

        return new Response(true, 'Consensus calculated successfully.', $consensusData);
    }


    private static function calculateChallengeConsensus(array $rows): vChallengeDetailsConsensus
    {
        $globalConsensus = [
            'winning_team' => [],
            'vote_void' => [],
        ];

        $playerDetails = [];

        $myPlayerId = -1;
        $iVoted = false;
        $allVoted = true;
        if (Session::isLoggedIn())
        {
            $myPlayerId = Session::getCurrentAccount()->crand;
        }
        // Step 1: Aggregate data from rows
        foreach ($rows as $row) {
            // Global consensus for reported winning team and void votes
            if ((int)$row["reporter_id"] == $myPlayerId)
            {
                $iVoted = true;
            }
            if ($row["reported_winning_team"] == null)
            {
                $allVoted = false;
                continue;
            }
            self::incrementConsensusCount($globalConsensus['winning_team'], $row['reported_winning_team']);
            self::incrementConsensusCount($globalConsensus['vote_void'], $row['vote_void']);
            
            // Per-player consensus for team, character, and picked random
            $playerId = $row['reported_player_id'];

            if ($playerId === null) {
                continue;
            }

            $playerId = (int)$playerId; // Ensure it's an integer after checking for null

            if (!isset($playerDetails[$playerId])) {
                $playerDetails[$playerId] = [
                    'team_name' => [],
                    'character' => [],
                    'picked_random' => [],
                ];
            }

            // Increment player-specific consensus fields
            self::incrementConsensusCount($playerDetails[$playerId]['team_name'], $row['team_name']);
            self::incrementConsensusCount($playerDetails[$playerId]['character'], $row['character']);
            self::incrementConsensusCount($playerDetails[$playerId]['picked_random'], $row['picked_random']);
        }

        // Step 2: Calculate player-specific consensus
        $playerConsensusDetails = [];
        foreach ($playerDetails as $playerId => $details) {
            $teamConsensus = self::calculateConsensusValue($details['team_name']);
            $characterConsensus = self::calculateConsensusValue($details['character']);
            $pickedRandomConsensus = self::calculateConsensusValue($details['picked_random']);

            $playerConsensusDetails[$playerId] = new vPlayerConsensusDetails(
                $playerId,
                $teamConsensus['value'],
                $teamConsensus['percentage'],
                $characterConsensus['value'],
                $characterConsensus['percentage'],
                $pickedRandomConsensus['value'] !== null ? (bool)$pickedRandomConsensus['value'] : null,
                $pickedRandomConsensus['percentage']
            );
        }

        // Step 3: Calculate global consensus
        $winningTeamConsensus = self::calculateConsensusValue($globalConsensus['winning_team']);
        $voteVoidPercentage = round(
            ($globalConsensus['vote_void'][1] ?? 0) / max(1, array_sum($globalConsensus['vote_void'])) * 100,
            2
        );

        // Step 4: Return the aggregated consensus object
        return new vChallengeDetailsConsensus(
            $winningTeamConsensus['value'],
            $winningTeamConsensus['percentage'],
            $voteVoidPercentage,
            $playerConsensusDetails,
            $iVoted,
            $allVoted
        );
    }

    private static function incrementConsensusCount(array &$field, $value): void
    {
        if ($value !== null) {
            $field[$value] = ($field[$value] ?? 0) + 1;
        }
    }
    
    private static function calculateConsensusValue(array $values): array
    {
        if (empty($values)) {
            return ['value' => null, 'percentage' => 0.0];
        }

        $total = array_sum($values); // Total votes
        arsort($values); // Sort values descending

        // Check for a tie (if the top two values have the same count)
        $topValues = array_slice($values, 0, 2, true);
        if (count($topValues) > 1 && reset($topValues) === end($topValues)) {
            return ['value' => null, 'percentage' => 0.0];
        }

        $mostCommonValue = key($values);
        $percentage = round(($values[$mostCommonValue] / $total) * 100, 2);

        if ($percentage === 0.0) {
            return ['value' => null, 'percentage' => 0.0];
        }

        return ['value' => $mostCommonValue, 'percentage' => $percentage];
    }

    public static function insertChallengeResult(
        vRecordId $refChallenge, 
        vRecordId $accountId, 
        string $reportedWinningTeam, 
        bool $didWin, 
        bool $voteVoid
    ): Response {
        $conn = Database::getConnection();
    
        $sql = "
            INSERT INTO `lobby_challenge_result` 
            (`ref_challenge_ctime`, `ref_challenge_crand`, `account_id`, `reported_winning_team`, `did_win`, `vote_void`, `report_timestamp`) 
            VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
            ON DUPLICATE KEY UPDATE 
            `reported_winning_team` = VALUES(`reported_winning_team`), 
            `did_win` = VALUES(`did_win`), 
            `vote_void` = VALUES(`vote_void`), 
            `report_timestamp` = VALUES(`report_timestamp`)
        ";
    
        $stmt = $conn->prepare($sql);
    
        if (!$stmt) {
            return new Response(false, 'Failed to prepare statement for lobby_challenge_result', null);
        }
    
        $didWinValue = $didWin ? 1 : 0;
        $voteVoidValue = $voteVoid ? 1 : 0;
    
        $stmt->bind_param(
            'siisii', 
            $refChallenge->ctime, 
            $refChallenge->crand, 
            $accountId->crand, 
            $reportedWinningTeam, 
            $didWinValue, 
            $voteVoidValue
        );
    
        $stmt->execute();
    
        if ($stmt->affected_rows >= 0) { // It can be 0 if no updates were made
            $stmt->close();
            return new Response(true, 'Challenge result inserted or updated successfully.', null);
        } else {
            $stmt->close();
            return new Response(false, 'Failed to insert or update challenge result.', null);
        }
    }
    

    public static function insertChallengeResultDetails(
        vRecordId $refChallenge, 
        vRecordId $reporterId, 
        vRecordId $reportedPlayerId, 
        ?string $teamName, 
        ?string $character, 
        ?bool $pickedRandom
    ): Response {
        $conn = Database::getConnection();
    
        $sql = "
            INSERT INTO `lobby_challenge_result_details` 
            (`ref_challenge_ctime`, `ref_challenge_crand`, `reporter_id`, `reported_player_id`, `team_name`, `character`, `picked_random`, `report_time`) 
            VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ";
    
        $stmt = $conn->prepare($sql);
    
        if (!$stmt) {
            return new Response(false, 'Failed to prepare statement for lobby_challenge_result_details', null);
        }
    
        // Use NULL for optional parameters if they are not provided
        $pickedRandomValue = $pickedRandom !== null ? ($pickedRandom ? 1 : 0) : null;
    
        $stmt->bind_param(
            'siisssi',
            $refChallenge->ctime, 
            $refChallenge->crand, 
            $reporterId->crand, 
            $reportedPlayerId->crand, 
            $teamName, 
            $character, 
            $pickedRandomValue
        );
    
        $stmt->execute();
    
        if ($stmt->affected_rows > 0) {
            $stmt->close();
            return new Response(true, 'Challenge result details inserted or updated successfully.', null);
        } else {
            $stmt->close();
            return new Response(false, 'Failed to insert or update challenge result details.', null);
        }
    }
    

    
    public static function insert(LobbyChallenge $LobbyChallenge): Response
    {
        $conn = Database::getConnection();

        try {
            // Start a transaction
            $conn->begin_transaction();

            // Insert into lobby_challenge
            $sql = "INSERT INTO lobby_challenge (ctime, crand, ref_lobby_ctime, ref_lobby_crand, style) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new \Exception('Failed to prepare statement for lobby_challenge');
            }

            $ctime = $LobbyChallenge->ctime;
            $crand = $LobbyChallenge->crand;
            $refLobbyCtime = $LobbyChallenge->lobbyId->ctime;
            $refLobbyCrand = $LobbyChallenge->lobbyId->crand;
            $styleValue = (int)$LobbyChallenge->style->value;

            do {
                $stmt->bind_param('sisii', $ctime, $crand, $refLobbyCtime, $refLobbyCrand, $styleValue);
                $stmt->execute();

                if ($stmt->errno === 1062) { // Handle duplicate key error
                    $LobbyChallenge->crand = $LobbyChallenge->GenerateCRand();
                    $crand = $LobbyChallenge->crand;
                } elseif ($stmt->errno) {
                    throw new \Exception('Error: ' . $stmt->error);
                } else {
                    break;
                }
            } while (true);

            if ($stmt->affected_rows <= 0) {
                throw new \Exception('Insert failed or no rows affected for lobby_challenge');
            }

            $stmt->close();

            // Insert challenger into lobby_challenge_account
            $challengerResponse = self::insertChallenger($LobbyChallenge, Session::getCurrentAccount(), $conn);
            if (!$challengerResponse->success) {
                throw new \Exception('Failed to insert challenger: ' . $challengerResponse->message);
            }

            // Commit the transaction
            $conn->commit();

            return new Response(true, 'Insert successful', $LobbyChallenge);

        } catch (\Exception $e) {
            // Rollback the transaction on error
            $conn->rollback();
            return new Response(false, $e->getMessage(), null);
        }
    }

    public static function saveCharacterSettings(vRecordId $challengeId, vRecordId $accountId, string $characterToSave, bool $randomCharacter)
    {
        // Get database connection
        $conn = Database::getConnection();

        // Prepare SQL statement for updating character settings
        $sql = "
            UPDATE `lobby_challenge_account` 
            SET 
                `character` = ?, 
                `random_character` = ? 
            WHERE 
                `ref_challenge_ctime` = ? AND 
                `ref_challenge_crand` = ? AND 
                `account_id` = ?
        ";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return new Response(false, 'Failed to prepare statement for lobby_challenge_account', null);
        }


        $character = $characterToSave;           // varchar(255)
        $randomChar = $randomCharacter ? 1 : 0;  // tinyint(1)
        $refChallengeCtime = $challengeId->ctime; // datetime(6)
        $refChallengeCrand = $challengeId->crand; // bigint(20)

        $stmt->bind_param(
            'sissi', // Parameter types: string, integer, string, string, integer
            $character,
            $randomChar,
            $refChallengeCtime,
            $refChallengeCrand,
            $accountId->crand
        );

        $stmt->execute();

        // Check if the query was successful
        if ($stmt->affected_rows > 0) {
            $stmt->close();
            return new Response(true, 'Character settings updated successfully.', null);
        } else {
            $stmt->close();
            return new Response(false, 'No changes were made to character settings.', null);
        }
    }

    public static function startChallenge(vRecordId $lobbyId,vRecordId $challengeId)
    {
        $conn = Database::getConnection();
        
        $sql = "UPDATE `lobby_challenge` SET `started` = '1' WHERE `lobby_challenge`.`ctime` = ? AND `lobby_challenge`.`crand` = ?";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return new Response(false, 'Failed to prepare statement for lobby_challenge', null);
        }

        $ctime = $challengeId->ctime;
        $crand = $challengeId->crand;

        $stmt->bind_param('si', $ctime, $crand);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $stmt->close();
            return new Response(true, 'Challenge started', null);
        } else {
            $stmt->close();
            return new Response(false, 'Challenge failed to start', null);
        }
    }

    public static function insertChallenger(vRecordId $LobbyChallenge, vAccount $challenger, ?\mysqli $conn = null): Response
    {
        if ($conn == null)
        {
            $conn = Database::getConnection();
        }

        $sql = "INSERT INTO `kickbackdb`.`lobby_challenge_account` 
                (`ref_challenge_ctime`, `ref_challenge_crand`, `account_id`, `team_name`, `character`, `random_character`, `win`, `left`, `ready`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 0, 0)
                ON DUPLICATE KEY UPDATE 
                `team_name` = VALUES(`team_name`), 
                `character` = VALUES(`character`), 
                `random_character` = VALUES(`random_character`), 
                `win` = VALUES(`win`), 
                `left` = VALUES(`left`), 
                `ready` = VALUES(`ready`)";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return new Response(false, 'Failed to prepare statement for lobby_challenge_account', null);
        }

        $ctime = $LobbyChallenge->ctime;
        $crand = $LobbyChallenge->crand;
        $accountId = $challenger->crand;
        $teamName = $challenger->username;
        $character = '';
        $randomCharacter = 0;
        $win = 0;

        $stmt->bind_param('siissii', $ctime, $crand, $accountId, $teamName, $character, $randomCharacter, $win);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $stmt->close();
            return new Response(true, 'Insert or update successful for challenger', null);
        } else {
            $stmt->close();
            return new Response(false, 'Insert or update failed or no rows affected for challenger', null);
        }
    }

    

    public static function edit(vRecordId $lobbyId, vRecordId $challengeId, string $gameMode, string $customRules) : Response {
        
        $conn = Database::getConnection();

        $sql = "UPDATE `lobby_challenge` SET `rules` = ? WHERE `lobby_challenge`.`ctime` = ? AND `lobby_challenge`.`crand` = ?";

        $stmt = $conn->prepare($sql);

        
        $stmt->bind_param('ssi', $customRules, $challengeId->ctime, $challengeId->crand);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $stmt->close();
            return new Response(true, 'edit successful for challenge', null);
        } else {
            $stmt->close();
            return new Response(false, 'edit failed for challenge', null);
        }
    }

    public static function leave(vRecordId $challengeId) : Response {
        $conn = Database::getConnection();

        $sql = "UPDATE `lobby_challenge_account` SET `left` = 1 WHERE `ref_challenge_ctime` = ? AND `ref_challenge_crand` = ? AND `account_id` = ? AND ready = 0 ";

        $stmt = $conn->prepare($sql);
        
        $accountCRand = -1;
        if (Session::isLoggedIn())
        {
            $accountCRand = Session::getCurrentAccount()->crand;
        }

        $stmt->bind_param('sii', $challengeId->ctime, $challengeId->crand, $accountCRand);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $stmt->close();
            return new Response(true, 'left challenge', null);
        } else {
            $stmt->close();
            return new Response(false, 'failed to leave challenge', null);
        }
    }

    
    public static function readyUp(vRecordId $challengeId) : Response {
        $conn = Database::getConnection();

        $sql = "UPDATE `lobby_challenge_account` SET `ready` = 1 WHERE `ref_challenge_ctime` = ? AND `ref_challenge_crand` = ? AND `account_id` = ? AND `left` = 0 ";

        $stmt = $conn->prepare($sql);
        
        $accountCRand = -1;
        if (Session::isLoggedIn())
        {
            $accountCRand = Session::getCurrentAccount()->crand;
        }

        $stmt->bind_param('sii', $challengeId->ctime, $challengeId->crand, $accountCRand);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $stmt->close();
            return new Response(true, 'ready up successfull', null);
        } else {
            $stmt->close();
            return new Response(false, 'failed to leave challenge', null);
        }
    }

    public static function getPlayers(vLobbyChallenge $challengeId) : Response {
        $conn = Database::getConnection();

        $sql = "SELECT lca.*, elo.*, a.* FROM `lobby_challenge_account` lca 
inner join v_account_info a on lca.account_id = a.Id
inner join lobby_challenge lc on lca.ref_challenge_ctime = lc.ctime and lca.ref_challenge_crand = lc.crand
inner join lobby l on lc.ref_lobby_ctime = l.ctime and lc.ref_lobby_crand = l.crand
left join v_game_elo_rank_info elo on l.game_id = elo.game_id and elo.account_id = lca.account_id where lca.ref_challenge_ctime = ? and lca.ref_challenge_crand = ?";

        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return new Response(false, 'Failed to prepare statement for account retrieval', null);
        }

        $ctime = $challengeId->ctime;
        $crand = $challengeId->crand;

        $stmt->bind_param('si', $ctime, $crand);
        $stmt->execute();

        $result = $stmt->get_result();

        if (!$result) {
            return new Response(false, "Failed to get challenge accounts: " . $conn->error, []);
        }

        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $accounts = array_map([self::class, 'row_to_vChallengePlayer'], $rows);

        return new Response(true, "Challenge accounts", $accounts);
    }

    private static function row_to_vChallengePlayer(array $row) : vChallengePlayer {

        $player = new vChallengePlayer($row["ref_challenge_ctime"], $row["ref_challenge_crand"]);
        $player->joinedAt = new vDateTime($row["joined_at"]);
        $player->teamName = $row["team_name"];
        $player->character = $row["character"];
        $player->pickedRandom = (bool)$row["random_character"];
        $player->win = (bool)$row["win"];
        $player->ready = (bool)$row["ready"];
        $player->left = (bool)$row["left"];

        if ($row["elo_rating"] != null)
            $player->elo = (int)$row["elo_rating"];
        else
            $player->elo = 1500;
        $player->isRanked = (bool)$row["is_ranked"];
        $player->totalMatches = (int)$row["ranked_matches"];
        $player->totalWins = (int)$row["total_wins"];
        $player->totalLosses = (int)$row["total_losses"];
        $player->minRankedMatches = (int)$row["minimum_ranked_matches_required"];
        //$player->gameName = $row["name"];
        //$player->gameLocator = $row["locator"];
        $player->rank = (int)$row["rank"];
        $player->account = AccountController::row_to_vAccount($row);

        return $player;
    }

}
