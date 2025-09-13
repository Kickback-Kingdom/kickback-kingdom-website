<?php
declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use Kickback\Backend\Models\Response;
use Kickback\Services\Database;
use Kickback\Services\Session;
use Kickback\Backend\Views\vQuest;
use Kickback\Backend\Views\vQuestReward;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vDateTime;
use Kickback\Backend\Views\vMedia;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vContent;
use Kickback\Backend\Views\vItem;
use Kickback\Backend\Views\vReviewStatus;
use Kickback\Backend\Views\vQuestLine;
use Kickback\Backend\Models\PlayStyle;
use Kickback\Backend\Models\ItemType;
use Kickback\Backend\Models\Quest;
use Kickback\Backend\Models\ItemRarity;
use Kickback\Backend\Views\vTournament;
use Kickback\Backend\Views\vQuestApplicant;
use Kickback\Backend\Views\vRaffle;
use Kickback\Backend\Views\vQuestReviewSummary;
use Kickback\Backend\Views\vQuestReviewDetail;
use Kickback\Backend\Controllers\AccountController;
use Kickback\Backend\Controllers\SocialMediaController;
use Kickback\Common\Primitives\Str;

class QuestController
{

    /**
    * @phpstan-assert-if-true =vQuest $quest
    */
    public static function queryQuestByIdInto(vRecordId $recordId, ?vQuest &$quest): bool
    {
        $resp = self::queryQuestByIdAsResponse($recordId);
        if ( $resp->success ) {
            $quest = $resp->data;
            return true;
        } else {
            $quest = null;
            return false;
        }
    }

    public static function queryQuestByIdAsResponse(vRecordId $recordId): Response
    {
        $conn = Database::getConnection();
        $sql = "SELECT * FROM v_quest_info WHERE Id = ?";
    
        // Prepare the SQL statement
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            return new Response(false, "Failed to prepare statement", null);
        }
    
        // Bind the parameter
        $stmt->bind_param('i', $recordId->crand);
    
        // Execute the statement
        if (!$stmt->execute()) {
            return new Response(false, "Failed to execute statement: " . $stmt->error, null);
        }
    
        // Get the result
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $quest = self::row_to_vQuest($row);
    
            // Close the statement
            $stmt->close();
    
            return new Response(true, "Quest Information.", $quest);
        }
    
        // Close the statement
        $stmt->close();
    
        return new Response(false, "We couldn't find a quest with that id", null);
    }

    /**
    * @phpstan-assert-if-true =vQuest $quest
    */
    public static function queryQuestByLocatorInto(string $locator, ?vQuest &$quest): bool
    {
        $resp = self::queryQuestByLocatorAsResponse($locator);
        if ( $resp->success ) {
            $quest = $resp->data;
            return true;
        } else {
            $quest = null;
            return false;
        }
    }

    public static function queryQuestByLocator(string $locator) : vQuest
    {
        $resp = self::queryQuestByLocatorAsResponse($locator);
        if ($resp->success) {
            // @phpstan-ignore return.type
            return $resp->data;
        } else {
            throw new \Exception($resp->message);
        }
    }
    
    public static function queryQuestByLocatorAsResponse(string $locator): Response
    {
        $conn = Database::getConnection();
        $sql = "SELECT * FROM v_quest_info WHERE locator = ?";

        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            return new Response(false, "Failed to prepare statement", null);
        }

        $stmt->bind_param('s', $locator);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result !== false && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return new Response(true, "Quest Information.", self::row_to_vQuest($row));
        }

        return new Response(false, "Couldn't find a quest with that locator", null);
    }

    function getAvailableQuests() : Response
    {
        $sql = "SELECT * FROM kickbackdb.v_quest_info  WHERE end_date > CURRENT_TIMESTAMP and published = 1 order by end_date asc";

        $result = mysqli_query($GLOBALS["conn"],$sql);

        $num_rows = mysqli_num_rows($result);
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        
        return (new Response(true, "Available Quests",  $rows ));
    }
        
    function getArchivedQuests() : Response
    {
        $sql = "SELECT * FROM kickbackdb.v_quest_info  WHERE end_date <= CURRENT_TIMESTAMP and published = 1 and finished = 1 order by end_date desc";

        $result = mysqli_query($GLOBALS["conn"],$sql);

        $num_rows = mysqli_num_rows($result);
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        
        return (new Response(true, "Available Quests",  $rows ));
    }

    function getTBAQuests() : Response {
        if (Session::isLoggedIn())
        {
            if (Session::isMagisterOfTheAdventurersGuild())
            {
                // Prepare the SQL statement
                $sql = "SELECT * FROM kickbackdb.v_quest_info WHERE published = 0 order by end_date desc";
                $stmt = mysqli_prepare($GLOBALS["conn"], $sql);
                if(!$stmt) {
                    // Handle error, maybe return an API response indicating the error
                    return new Response(false, "Database error", []);
                }
        
                //mysqli_stmt_bind_param($stmt, "ii", Kickback\Services\Session::getCurrentAccount()->crand, Kickback\Services\Session::getCurrentAccount()->crand);

            }
            else
            {
                // Prepare the SQL statement
                $sql = "SELECT * FROM kickbackdb.v_quest_info WHERE published = 0 and (host_id = ? or host_id_2 = ?) order by end_date desc";
                $stmt = mysqli_prepare($GLOBALS["conn"], $sql);
                if(!$stmt) {
                    // Handle error, maybe return an API response indicating the error
                    return new Response(false, "Database error", []);
                }
        
                mysqli_stmt_bind_param($stmt, "ii", Session::getCurrentAccount()->crand, Session::getCurrentAccount()->crand);

            }

            // Execute the statement
            if(mysqli_stmt_execute($stmt))
            {
                $result = mysqli_stmt_get_result($stmt);
                $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
                return new Response(true, "TBA Quests", $rows);
            }
            else 
            {
                // Handle execution error, maybe return an API response indicating the error
                return new Response(false, "Query execution error", []);
            }
        }
        else
        {
            return new Response(true, "TBA Quests", []);
        }
    }


    public static function queryHostedFutureQuests(vRecordId $hostId): Response
    {
        return self::queryHostedQuests($hostId, true);
    }

    public static function queryHostedPastQuests(vRecordId $hostId): Response
    {
        return self::queryHostedQuests($hostId, false);
    }

    private static function queryHostedQuests(vRecordId $hostId, bool $future): Response
    {
        $conn = Database::getConnection();

        if ($future) {
            $sql = "SELECT * FROM v_quest_info WHERE (host_id = ? OR host_id_2 = ?) AND end_date > CURRENT_TIMESTAMP AND published = 1 ORDER BY end_date ASC";
        } else {
            $sql = "SELECT * FROM v_quest_info WHERE (host_id = ? OR host_id_2 = ?) AND end_date <= CURRENT_TIMESTAMP AND published = 1 ORDER BY end_date DESC";
        }

        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            return new Response(false, "Failed to prepare query", null);
        }

        $stmt->bind_param('ii', $hostId->crand, $hostId->crand);
        $stmt->execute();
        $result = $stmt->get_result();

        $quests = [];
        while ($row = $result->fetch_assoc()) {
            $quests[] = self::row_to_vQuest($row);
        }

        return new Response(true, "Hosted quests loaded.", $quests);
    }

    /**
    * @return array<vQuest>
    */
    public static function queryQuestsByQuestLineId(vRecordId $questLineId, int $page = 1, int $itemsPerPage = 10): array
    {
        $resp = self::queryQuestsByQuestLineIdAsResponse($questLineId, $page, $itemsPerPage);
        if ($resp->success) {
            // @phpstan-ignore return.type
            return $resp->data;
        } else {
            throw new \Exception($resp->message);
        }
    }

    public static function queryQuestsByQuestLineIdAsResponse(vRecordId $questLineId, int $page = 1, int $itemsPerPage = 10): Response
    {
        $conn = Database::getConnection();
        $offset = ($page - 1) * $itemsPerPage;
        $sql = "SELECT * FROM v_quest_info where quest_line_id = ? LIMIT ? OFFSET ?";
        
        /*$sql = "SELECT f.* FROM kickbackdb.v_feed f
                LEFT JOIN quest q ON f.Id = q.Id 
                WHERE f.published = 1 AND q.quest_line_id = ? AND f.type = 'QUEST'
                LIMIT ? OFFSET ?";*/

        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            return new Response(false, "Failed to prepare the SQL statement.", null);
        }

        $stmt->bind_param('iii', $questLineId->crand, $itemsPerPage, $offset);
        $stmt->execute();
        $result = $stmt->get_result();

        if (!$result) {
            return new Response(false, "Failed to execute the query.", null);
        }

        $quests = [];
        while ($row = $result->fetch_assoc()) {
            $quests[] = self::row_to_vQuest($row);
        }

        $stmt->close();
        return new Response(true, "Available Quests", $quests);
    }

    public static function getQuestByRaffleId(vRecordId $raffleId): Response {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("SELECT * FROM v_quest_info WHERE raffle_id = ?");
        if ($stmt === false) {
            return new Response(false, "Failed to prepare query", null);
        }

        $stmt->bind_param('i', $raffleId->crand);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row !== null) {
            return new Response(true, "Quest Information.", self::row_to_vQuest($row));
        }

        return new Response(false, "We couldn't find a quest with that raffle id", null);
    }

    public static function queryQuestByKickbackUpcoming(): vQuest
    {
        $resp = self::queryQuestByKickbackUpcomingAsResponse();
        if ($resp->success) {
            // @phpstan-ignore return.type
            return $resp->data;
        } else {
            throw new \Exception($resp->message);
        }
    }

    public static function queryQuestByKickbackUpcomingAsResponse(): Response
    {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("SELECT * FROM v_quest_info WHERE (host_id = 46 or host_id_2 = 46) and finished = 0 and published = 1 order by end_date LIMIT 1");
        if ($stmt === false) {
            return new Response(false, "Failed to prepare query", null);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row !== null) {
            return new Response(true, "Quest Information.", self::row_to_vQuest($row));
        }

        return new Response(false, "We couldn't find a quest with that raffle id", null);
    }

    /**
    * @return array<vQuestReward>
    */
    public static function queryQuestRewardsByQuestId(vRecordId $questId) : array
    {
        $questRewardsResp = QuestController::queryQuestRewardsByQuestIdAsResponse($questId);
        if ($questRewardsResp->success) {
            // @phpstan-ignore-next-line
            return $questRewardsResp->data;
        } else {
            throw new \Exception($questRewardsResp->message);
        }
    }

    public static function queryQuestRewardsByQuestIdAsResponse(vRecordId $questId) : Response
    {
        $conn = Database::getConnection();
        $sql = "SELECT * FROM v_quest_reward_info WHERE quest_id = ?";

        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            return new Response(false, "Failed to prepare statement", null);
        }

        $stmt->bind_param('i', $questId->crand);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result === false) {
            return new Response(false, "Couldn't find rewards for that quest id", null);
        }

        $rewards = [];
        while ($row = $result->fetch_assoc()) {
            $rewards[] = self::row_to_vQuestReward($row);
        }

        return new Response(true, "Quest Rewards Loaded", $rewards);
    }

    /**
    * @return array<vQuestApplicant>
    */
    public static function queryQuestApplicants(vQuest $quest): array
    {
        $questApplicantsResponse = self::queryQuestApplicantsAsResponse($quest);
        if (!$questApplicantsResponse->success) {
            throw new \Exception($questApplicantsResponse->message);
        }

        // @phpstan-ignore-next-line
        return $questApplicantsResponse->data;
    }

    public static function queryQuestApplicantsAsResponse(vQuest $quest): Response
    {
        $conn = Database::getConnection();
        //$sql = "SELECT * FROM kickbackdb.v_quest_applicants_account WHERE quest_id = ? ORDER BY seed ASC, exp DESC, prestige DESC";
        $sql = "select 
        `account`.*,
        `quest_applicants`.`accepted` AS `accepted`, 
        `quest_applicants`.`participated` AS `participated`, 
        `quest_applicants`.`quest_id` AS `quest_id`, 
        `quest_applicants`.`seed_score` AS `seed_score`, 
        CASE 
        WHEN `quest_applicants`.`seed_score` > 0 
        THEN RANK() OVER (
            PARTITION BY `quest_applicants`.`quest_id` 
            ORDER BY 
                `quest_applicants`.`seed_score` DESC, 
                `account`.`Id` DESC
        ) 
        ELSE RANK() OVER (
            PARTITION BY `quest_applicants`.`quest_id` 
            ORDER BY 
                CASE WHEN `v_game_rank`.`rank` IS NULL THEN 1 ELSE 0 END ASC, 
                `v_game_rank`.`rank` ASC, 
                `account`.`Id` DESC
        ) 
        END AS `seed`, 
        `v_game_rank`.`rank` AS `rank`
      from 
        (
          (
            (
              (
                `quest_applicants` 
                join `v_account_info` `account` on(
                  `quest_applicants`.`account_id` = `account`.`Id`
                )
              ) 
              left join `quest` on(
                `quest_applicants`.`quest_id` = `quest`.`Id`
              )
            ) 
            left join `tournament` on(
              `quest`.`tournament_id` = `tournament`.`Id`
            )
          ) 
          left join `v_game_elo_rank_info` `v_game_rank` on(
            `v_game_rank`.`account_id` = `account`.`Id` 
            and `v_game_rank`.`game_id` = `tournament`.`game_id`
          )
        )
      where quest_applicants.quest_id = ?
      ORDER BY seed ASC, exp DESC, prestige DESC";

        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            return new Response(false, "Failed to prepare statement", null);
        }

        $stmt->bind_param('i', $quest->crand);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result === false) {
            return new Response(false, "Couldn't find applicants for that quest id", null);
        }

        $applicants = [];
        while ($row = $result->fetch_assoc()) {
            $applicants[] = self::row_to_vQuestApplicant($row);
        }

        return new Response(true, "Quest Applicants", $applicants);
    }

    /**
    * Fetch applicants for multiple quests in a single query.
    *
    * @param array<int> $questIds
    * @return array<int, array<vQuestApplicant>> keyed by quest id
    */
    public static function queryQuestApplicantsForQuests(array $questIds): array
    {
        if (empty($questIds)) {
            return [];
        }

        $conn = Database::getConnection();

        $placeholders = implode(',', array_fill(0, count($questIds), '?'));
        $sql = "select
        `account`.*,
        `quest_applicants`.`accepted` AS `accepted`,
        `quest_applicants`.`participated` AS `participated`,
        `quest_applicants`.`quest_id` AS `quest_id`,
        `quest_applicants`.`seed_score` AS `seed_score`,
        CASE
        WHEN `quest_applicants`.`seed_score` > 0
        THEN RANK() OVER (
            PARTITION BY `quest_applicants`.`quest_id`
            ORDER BY
                `quest_applicants`.`seed_score` DESC,
                `account`.`Id` DESC
        )
        ELSE RANK() OVER (
            PARTITION BY `quest_applicants`.`quest_id`
            ORDER BY
                CASE WHEN `v_game_rank`.`rank` IS NULL THEN 1 ELSE 0 END ASC,
                `v_game_rank`.`rank` ASC,
                `account`.`Id` DESC
        )
        END AS `seed`,
        `v_game_rank`.`rank` AS `rank`
      from
        (
          (
            (
              (
                `quest_applicants`
                join `v_account_info` `account` on(
                  `quest_applicants`.`account_id` = `account`.`Id`
                )
              )
              left join `quest` on(
                `quest_applicants`.`quest_id` = `quest`.`Id`
              )
            )
            left join `tournament` on(
              `quest`.`tournament_id` = `tournament`.`Id`
            )
          )
          left join `v_game_elo_rank_info` `v_game_rank` on(
            `v_game_rank`.`account_id` = `account`.`Id`
            and `v_game_rank`.`game_id` = `tournament`.`game_id`
          )
        )
      where quest_applicants.quest_id IN ($placeholders)
      ORDER BY quest_applicants.quest_id ASC, seed ASC, exp DESC, prestige DESC";

        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            return [];
        }

        $types = str_repeat('i', count($questIds));
        // @phpstan-ignore-next-line
        $stmt->bind_param($types, ...$questIds);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result === false) {
            return [];
        }

        $byQuest = [];
        while ($row = $result->fetch_assoc()) {
            $qid = (int)$row['quest_id'];
            $byQuest[$qid][] = self::row_to_vQuestApplicant($row);
        }

        return $byQuest;
    }

    public static function getTotalUnusedRaffleTickets(vAccount $account) : Response {
        $conn = Database::getConnection();

        // Prepare the SQL query with a placeholder for the account_id
        $sql = "SELECT count(*) FROM kickbackdb.v_raffle_tickets WHERE raffle_id IS NULL AND account_id = ?";

        // Prepare the statement
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            return new Response(false, "Failed to prepare statement: " . $conn->error, 0);
        }

        // Bind the parameter
        $accountId = $account->crand;
        $stmt->bind_param('i', $accountId);

        // Execute the statement
        if (!$stmt->execute()) {
            return new Response(false, "Failed to execute statement: " . $stmt->error, 0);
        }

        // Get the result
        $result = $stmt->get_result();
        $row = $result->fetch_row();
        $unused = $row[0] ?? 0;

        // Close the statement
        $stmt->close();

        return new Response(true, "Unused raffle tickets", $unused);
    }

    public static function checkIfTimeForRaffleWinner(vQuest $quest) : void {

        // Get current date
        $current_date = new \DateTime();
        $current_date->modify('+10 seconds');
        // Check if the date from the DB has passed
        if ($quest->endDate()->value <= $current_date) {
            self::chooseRaffleWinner($quest->raffle);
        }
    }

    public static function getSubmittedRaffleTickets(vRaffle $raffle): Response {
        $conn = Database::getConnection();
        $sql = "SELECT Id FROM kickbackdb.raffle_submissions WHERE raffle_id = ?";
        
        // Prepare the SQL statement
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            return new Response(false, "Failed to prepare statement: " . $conn->error, null);
        }

        // Bind the parameter
        $stmt->bind_param('i', $raffle->crand);

        // Execute the statement
        if (!$stmt->execute()) {
            return new Response(false, "Failed to execute statement: " . $stmt->error, null);
        }

        // Get the result
        $result = $stmt->get_result();
        if ($result === false) {
            return new Response(false, "Failed to get result: " . $stmt->error, null);
        }

        // Fetch all the rows
        $rows = $result->fetch_all(MYSQLI_ASSOC);

        // Free the result and close the statement
        $result->free();
        $stmt->close();

        return new Response(true, "Raffle Ticket Submissions", $rows);
    }

    public static function chooseRaffleWinner(vRaffle $raffle) : Response {
        // Get submitted raffle tickets
        $submittedTicketsResp = self::getSubmittedRaffleTickets($raffle);
        if (!$submittedTicketsResp->success) {
            return new Response(false, "Failed to get submitted tickets", null);
        }
        
        $submittedTickets = $submittedTicketsResp->data;
        $totalTickets = count($submittedTickets);

        if ($totalTickets > 0) {
            $winnerIndex = random_int(0, $totalTickets - 1);
            $winnerTicketSubmissionId = $submittedTickets[$winnerIndex]["Id"];

            // Update raffle to set the winner
            $conn = Database::getConnection();
            $sql = "UPDATE raffle SET winner_submission_id = ? WHERE Id = ? AND winner_submission_id IS NULL";
            
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                return new Response(false, "Failed to prepare statement: " . $conn->error, null);
            }

            $stmt->bind_param('ii', $winnerTicketSubmissionId, $raffle->crand);

            if (!$stmt->execute()) {
                return new Response(false, "Failed to execute statement: " . $stmt->error, null);
            }

            $affectedRows = $stmt->affected_rows;
            $stmt->close();

            if ($affectedRows > 0) {
                $raffleQuestResp = self::getQuestByRaffleId($raffle);
                if (!$raffleQuestResp->success) {
                    return new Response(false, "Failed to get raffle quest", null);
                }
                
                $raffleQuest = $raffleQuestResp->data;
                
                $raffleWinnerResp = self::getRaffleWinner($raffle);
                if (!$raffleWinnerResp->success) {
                    return new Response(false, "Failed to get raffle winner", null);
                }

                $raffleWinner = $raffleWinnerResp->data;
                $msg = FlavorTextController::getRaffleWinnerAnnouncement($raffleQuest->title, $raffleWinner["Username"]);
                SocialMediaController::DiscordWebHook($msg);

                return new Response(true, "Selected Raffle Winner!", null);
            } else {
                return new Response(false, "No rows affected, raffle winner not set", null);
            }
        } else {
            return new Response(true, "No raffle tickets were entered!", null);
        }
    }

    public static function getRaffleWinner(vRaffle $raffle): Response {
        $conn = Database::getConnection();
    
        // Prepare the SQL statement
        $sql = "SELECT raffle_id, account_id, Username FROM kickbackdb.v_raffle_winners WHERE raffle_id = ?";
    
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            return new Response(false, "Failed to prepare statement: " . $conn->error, null);
        }
    
        // Bind the parameter
        $stmt->bind_param('i', $raffle->crand);
    
        // Execute the statement
        if (!$stmt->execute()) {
            return new Response(false, "Failed to execute statement: " . $stmt->error, null);
        }
    
        // Fetch results
        $result = $stmt->get_result();
    
        if ($result === false || $result->num_rows === 0) {
            $stmt->close();
            return new Response(false, "No winner found for the given raffle ID", null);
        }
    
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    
        // Populate the winner property in the raffle object with the first result
        $winner = $rows[0];
    
        // Return winner details
        return new Response(true, "Raffle Ticket Winner", $winner);
    }
    

    public static function getRaffleParticipants(vRaffle $raffle): Response {
        $conn = Database::getConnection();
        $sql = "SELECT `v_account_info`.*,
                    `v_raffle_tickets`.`raffle_id` AS `raffle_id`
                FROM
                    (`v_raffle_tickets`
                    LEFT JOIN `v_account_info` ON (`v_raffle_tickets`.`account_id` = `v_account_info`.`Id`))
                WHERE
                    `v_raffle_tickets`.`raffle_id` = ?
                GROUP BY `v_raffle_tickets`.`raffle_id` , `v_raffle_tickets`.`account_id`";

        // Prepare the SQL statement
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            return new Response(false, "Failed to prepare statement: " . $conn->error, null);
        }

        // Bind the parameter
        $stmt->bind_param('i', $raffle->crand);

        // Execute the statement
        if (!$stmt->execute()) {
            return new Response(false, "Failed to execute statement: " . $stmt->error, null);
        }

        // Get the result
        $result = $stmt->get_result();
        if ($result === false) {
            return new Response(false, "Failed to get result: " . $stmt->error, null);
        }

        // Fetch all the rows
        //$rows = $result->fetch_all(MYSQLI_ASSOC);

        $accounts = [];
        while ($row = $result->fetch_assoc()) {
            $accounts[] = AccountController::row_to_vAccount($row, true);
        }
        // Free the result and close the statement
        $result->free();
        $stmt->close();

        return new Response(true, "Raffle Participants", $accounts);
    }

    public static function removeStandardParticipationRewards(vRecordId $questId) : Response
    {
        if (!self::queryQuestByIdInto($questId, $quest)) {
            return new Response(false, "Could not find quest with that ID; can't remove standard participation rewards.", null);
        }

        if (!$quest->canEdit()) {
            return new Response(false, "You do not have permissions to remove standard participation rewards from this quest.", null);
        }

        $removeRewardResp = self::rejectQuestReviewById($questId);
        if (!$removeRewardResp->success)
        {
            return $removeRewardResp;
        }


        $conn = Database::getConnection();
        
        // SQL to delete specific standard participation rewards for the given questId
        $sql = "DELETE FROM quest_reward WHERE quest_id = ? AND item_id IN (3, 4, 15)";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $questId->crand);
        $result = mysqli_stmt_execute($stmt);

        if ($result) {
            // Successful deletion
            mysqli_stmt_close($stmt);
            return new Response(true, "Successfully removed standard participation rewards for quest", null);
        } else {
            // Deletion failed, capture error
            $error = mysqli_error($conn);
            mysqli_stmt_close($stmt);
            return new Response(false, "Failed to remove standard participation rewards for quest. Error: $error", null);
        }
    }
        
    public static function setupStandardParticipationRewards(vRecordId $questId) : Response
    {
        $resp = self::removeStandardParticipationRewards($questId);

        if ($resp->success)
        {
            return self::addStandardParticipationRewards($questId);
        }
        else
        {
            return $resp;
        }

    }

    public static function addStandardParticipationRewards(vRecordId $questId) : Response
    {
        if (!self::queryQuestByIdInto($questId, $quest)) {
            return new Response(false, "Could not find quest with that ID; can't add standard participation rewards.", null);
        }

        if (!$quest->canEdit()) {
            return new Response(false, "You do not have permissions to add standard participation rewards to this quest.", null);
        }
        
        $addRewardResp = self::rejectQuestReviewById($questId);
        if (!$addRewardResp->success)
        {
            return $addRewardResp;
        }

        $conn = Database::getConnection();
        // Predefined standard reward IDs
        $standardRewardIds = [3, 4, 15];
        $success = true;
        $errorMessages = [];

        foreach ($standardRewardIds as $rewardId) {
            $sql = "INSERT INTO quest_reward (quest_id, item_id, category, participation) VALUES (?, ?, 'Participation',1)";
            
            $stmt = mysqli_prepare($conn, $sql);
            if (!$stmt) {
                $success = false;
                $errorMessages[] = "Failed to prepare statement: " . mysqli_error($conn);
                break; // Optionally remove this break to attempt inserting all rewards even if one fails
            }

            mysqli_stmt_bind_param($stmt, 'ii', $questId->crand, $rewardId);
            $result = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            if (!$result) {
                $success = false;
                $questIdStr = strval($questId->crand);
                $errorMessages[] = "Failed to insert reward ID $rewardId for quest ID $questIdStr: " . mysqli_error($conn);
                // Optionally break here to stop at the first error, or remove to try inserting all rewards
                break;
            }
        }

        if ($success) {
            return new Response(true, "Successfully added standard participation rewards for quest", null);
        } else {
            // Join all error messages into a single string if there are multiple
            $errorMessage = implode(" | ", $errorMessages);
            return new Response(false, "Error adding standard participation rewards: $errorMessage", null);
        }
    }

    public static function rejectQuestReviewById(vRecordId $questId) : Response
    {
        $conn = Database::getConnection();
         // Prepare the SQL statement to mark the quest as being reviewed
         $stmt = $conn->prepare("UPDATE quest SET published = 0, being_reviewed = 0 WHERE Id = ? and (being_reviewed = 1 or published = 1)");
         if (!$stmt) {
             // Handle preparation errors
             return new Response(false, "Failed to prepare the review rejection statement.", null);
         }
     
         // Bind the quest ID to the statement
         $stmt->bind_param('i', $questId->crand);
     
         // Execute the update statement
         if (!$stmt->execute()) {
             // Handle execution errors
             $stmt->close();
             return new Response(false, "Quest review rejection failed due to an execution error.", null);
         }
     
         // Close the prepared statement
         $stmt->close();
     
         // Successfully updated the quest status to being reviewed
         return new Response(true, "Quest publish rejected.", null);
    }

    public static function updateQuestOptions(array $data) : Response
    {
        $conn = Database::getConnection();

        $questId = new vRecordId('', (int)$data["edit-quest-id"]);
        $questName = $data["edit-quest-options-title"];
        $questLocator = $data["edit-quest-options-locator"];
        $questHostId2 = $data["edit-quest-options-host-2-id"];
        $questSummary = $data["edit-quest-options-summary"];
        $hasADate = isset($data["edit-quest-options-has-a-date"]);
        $dateTime = $data["edit-quest-options-datetime"];
        $playStyle = $data["edit-quest-options-style"];
        if ( array_key_exists('edit-quest-options-questline', $data) && isset($data["edit-quest-options-questline"]) ) {
            $questLineId = $data["edit-quest-options-questline"];
            $questLineIdValue = intval($questLineId) === 0 ? null : intval($questLineId);
        } else {
            $questLineIdValue = null;
        }

        if (!self::queryQuestByIdInto($questId, $quest)) {
            return (new Response(false, "Error updating quest. Could not find quest by Id.", null));
        }

        if (str_starts_with($questLocator, 'new-quest-'))
        {
            if ($questLocator != 'new-quest-'.$quest->host1->crand)
            {
                return (new Response(false, "Cannot change the quest locator to ".$questLocator, null));
            }
        }

        if (!$quest->canEdit())
        {
            return (new Response(false, "Error updating quest. You do not have permission to edit this quest.", null));
        }

        // Prepare the update statement
        $query = "UPDATE quest SET name = ?, locator = ?, host_id_2 = ?, summary = ?, end_date = ?, play_style = ?, quest_line_id = ?, `published` = 0, `being_reviewed` = 0 WHERE Id = ?";
        $stmt = mysqli_prepare($conn, $query);

        // Determine the value for host_id_2 and end_date
        $host_id_2 = Str::empty($questHostId2) ? NULL : $questHostId2;
        $end_date = $hasADate && !Str::empty($dateTime) ? $dateTime : NULL;

        //$date = $data["edit-quest-options-datetime-date"];
        //$time = $data["edit-quest-options-datetime-time"];
        //$end_date = $hasADate && !Str::empty($date) && !Str::empty($time) ? $date . ' ' . $time . ":00": NULL;

        // Bind the parameters
        mysqli_stmt_bind_param($stmt, 'ssissiii', $questName, $questLocator, $host_id_2, $questSummary, $end_date, $playStyle, $questLineIdValue, $questId->crand);

        // Execute the statement
        $success = mysqli_stmt_execute($stmt);

        // Close the statement
        mysqli_stmt_close($stmt);

        if($success) {
            $locatorChanged = $quest->locator != $questLocator;
            
            // Construct a data object to return more explicit information
            $responseData = (object)[
                'locator' => $questLocator, // Always return the current locator
                'locatorChanged' => $locatorChanged // Explicitly indicate if the locator was changed
            ];
            return (new Response(true, "Quest options updated successfully!", $responseData));
        } else {
            return (new Response(false, "Error updating quest options with unknown error.", null));
        }
    }
    
    public static function updateQuestImages(vRecordId $questId,vRecordId  $desktop_banner_id,vRecordId  $mobile_banner_id,vRecordId  $icon_id) : Response
    {
        $conn = Database::getConnection();

        if (!self::queryQuestByIdInto($questId, $quest)) {
            return (new Response(false, "Error updating quest. Could not find quest by Id.", null));
        }

        if (!$quest->canEdit())
        {
            return (new Response(false, "Error updating quest. You do not have permission to edit this quest.", null));
        }

        // Prepare the update statement
        $query = "UPDATE quest SET image_id_icon=?, image_id=?, image_id_mobile=?, `published` = 0, `being_reviewed` = 0 WHERE Id=?";
        $stmt = mysqli_prepare($conn, $query);

        // Bind the parameters
        mysqli_stmt_bind_param($stmt, 'iiii', $icon_id->crand, $desktop_banner_id->crand, $mobile_banner_id->crand,  $questId->crand);

        // Execute the statement
        $success = mysqli_stmt_execute($stmt);

        // Close the statement
        mysqli_stmt_close($stmt);

        // Check if the update was successful
        if($success) {
            return (new Response(true, "Quest images updated successfully!", null));
        } else {
            return (new Response(false, "Error updating quest images with unknown error.", null));
        }
    }
    
    public static function submitQuestForReview(vRecordId $questId) : Response { 

        $conn = Database::getConnection();

        if (!self::queryQuestByIdInto($questId, $quest)) {
            return new Response(false, "Quest submission failed. Quest not found.", null);
        }

        if (!$quest->canEdit()) {
            return new Response(false, "Submission denied. Insufficient permissions to edit this quest.", null);
        }

        
        $stmt = $conn->prepare("UPDATE quest SET published = 0, being_reviewed = 1 WHERE Id = ?"); // Corrected table name
        if (!$stmt) {
            return new Response(false, "Failed to prepare the review submission statement.", null);
        }

        $stmt->bind_param('i', $questId->crand); 

        if (!$stmt->execute()) {
            $stmt->close();
            return new Response(false, "Quest review submission failed due to an execution error.", null);
        }

        $stmt->close();

        return new Response(true, "Quest successfully submitted for review. This may take a few days to be reviewed. Thank you for your patience.", null);
    }

    public static function approveQuestReviewById(vRecordId $questId) : Response {
        
        $conn = Database::getConnection();
        
        if (!Session::isMagisterOfTheAdventurersGuild()) {
            return new Response(false, "Approval denied. Insufficient permissions to edit this quest.", null);
        }

        $stmt = $conn->prepare("UPDATE quest SET published = 1, being_reviewed = 0 WHERE Id = ?");
        if (!$stmt) {
            return new Response(false, "Failed to prepare the review approval statement.", null);
        }

        $stmt->bind_param('i', $questId->crand);

        if (!$stmt->execute()) {
            $stmt->close();
            return new Response(false, "Quest review approval failed due to an execution error.", null);
        }

        $stmt->close();

        return new Response(true, "Quest successfully approved and published.", null);
    }

    public static function updateQuestContent(vRecordId $questId, vRecordId $contentId) : Response {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("UPDATE quest SET content_id = ? WHERE Id = ?");
        mysqli_stmt_bind_param($stmt, 'ii', $contentId->crand, $questId->crand);
        mysqli_stmt_execute($stmt);

        return (new Response(true, "Quest content updated.", null));
    }

    public static function submitRaffleTicket(vRecordId $account_id, vRecordId $raffle_id) : Response {
        $conn = Database::getConnection();
    
        // Get the unused account raffle ticket loot ID
        $loot_id = AccountController::getUnusedAccountRaffleTicket($account_id)->data;
    
        // Prepare the statement to insert the raffle ticket
        $stmt = $conn->prepare("INSERT INTO raffle_submissions (raffle_id, loot_id) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, 'ii', $raffle_id->crand, $loot_id);
    
        // Execute the statement
        if (mysqli_stmt_execute($stmt)) {
            return (new Response(true, "Submitted Raffle Ticket!", null));
        } else {
            return (new Response(false, "Failed to submit raffle ticket with error: " . mysqli_stmt_error($stmt), null));
        }
    }

    public static function accountHasRegisteredOrAppliedForQuest(vRecordId $account_id, vRecordId $quest_id) : Response {

        $conn = Database::getConnection();
        // Prepare the SQL statement for checking existing entry
        $stmt = mysqli_prepare($conn, "SELECT 1 FROM quest_applicants WHERE account_id = ? AND quest_id = ?");
        mysqli_stmt_bind_param($stmt, 'ii', $account_id->crand, $quest_id->crand);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $exists = mysqli_num_rows($result) > 0;
        mysqli_stmt_close($stmt);
        
        if ($exists) {
            return (new Response(true, "Account has already registered or applied for the quest.", null));
        } else {
            return (new Response(false, "Account has not registered or applied for the quest.", null));
        }
    }

    public static function applyOrRegisterForQuest(vRecordId $account_id, vRecordId $quest_id) : Response
    {
        $conn = Database::getConnection();
        // Check if the account has already registered or applied for the quest
        $checkResponse = self::accountHasRegisteredOrAppliedForQuest($account_id, $quest_id);
        if ($checkResponse->success) {
            return $checkResponse; // Return the response from the check function
        }

        // Prepare the SQL statement for inserting a new entry
        $stmt = mysqli_prepare($conn, "INSERT INTO quest_applicants (account_id, accepted, quest_id, participated) VALUES (?, '0', ?, '0')");
        mysqli_stmt_bind_param($stmt, 'ii', $account_id->crand, $quest_id->crand);
        if (mysqli_stmt_execute($stmt)) {
            $account = AccountController::getAccountById($account_id)->data;
            if (!self::queryQuestByIdInto($quest_id, $quest)) {
                return new Response(false, "Could not find quest with that ID; failed to register for quest.", null);
            }
            SocialMediaController::DiscordWebHook(FlavorTextController::getRandomGreeting() . ', ' . $account->username . ' just signed up for the ' . $quest->title . ' quest.');
            mysqli_stmt_close($stmt);
            return (new Response(true, "Registered for quest successfully", null));
        } else {
            $error = mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
            return (new Response(false, "Failed to register for quest with error: " . $error, null));
        }
    }

    private static function row_to_vQuest(array $row) : vQuest {
        $quest = new vQuest('',$row["Id"]);

        $quest->title = $row["name"];

        $quest->locator = $row["locator"];
        
        $quest->reviewStatus = new vReviewStatus((bool)$row["published"], (bool)$row["being_reviewed"]);


        $quest->summary = is_null($row["summary"]) ? "":$row["summary"];



        if ($row["tournament_id"] != null)
        {
            $quest->tournament = new vTournament('', $row["tournament_id"]);
            $quest->tournament->hasBracket((bool)$row["hasBracket"]==1);
        }

        if ($row["end_date"] != null)
        {
            $quest->endDate(new vDateTime($row["end_date"]));
        }

        if ($row["image_id"] != null)
        {
            $banner = new vMedia('',$row["image_id"]);
            $banner->setMediaPath($row["imagePath"]);
            $quest->banner = $banner;
        }
        else{
            $quest->banner = vMedia::defaultBanner();
        }

        if ($row["image_id_icon"] != null)
        {
            $icon = new vMedia('',$row["image_id_icon"]);
            $icon->setMediaPath($row["imagePath_icon"]);
            $quest->icon = $icon;
        }
        else{
            $quest->icon = vMedia::defaultIcon();
        }

        if ($row["image_id_mobile"] != null)
        {
            $bannerMobile = new vMedia('',$row["image_id_mobile"]);
            $bannerMobile->setMediaPath($row["imagePath_mobile"]);
            $quest->bannerMobile = $bannerMobile;
        }
        else{
            $quest->bannerMobile = vMedia::defaultBannerMobile();
        }

        if ($row["raffle_id"] != null)
        {
            $quest->raffle = new vRaffle('', $row["raffle_id"]);
        }

        if ($row["content_id"] != null)
        {
            $quest->content = new vContent('', $row["content_id"]);
        }
        else{
            $quest->content = new vContent();
            $quest->content->htmlContent = $row["desc"] ?? "";
        }

        if ($row["quest_line_id"] != null)
        {
            $quest->questLine = new vQuestLine('',$row["quest_line_id"]);
        }

        $host1 = new vAccount('', $row["host_id"]);
        $host1->username = $row["host_name"];

        $quest->host1 = $host1;

        if ($row["host_id_2"] != null && $row["host_name_2"] != null)
        {

            $host2 = new vAccount('', $row["host_id_2"]);
            $host2->username = $row["host_name_2"];
    
            $quest->host2 = $host2;
        }

        $quest->requiresApplication = (bool)$row["req_apply"];

        $quest->playStyle = PlayStyle::from($row["play_style"]);

        return $quest;
    }

    private static function row_to_vQuestReward(array $row) : vQuestReward {
        $questReward = new vQuestReward();

        $questReward->questId = new vRecordId('',$row["quest_id"]);

        $questReward->category = $row["category"];

        $item = new vItem('',$row["Id"]);
        $item->type = ItemType::from((int)$row["type"]);
        $item->rarity = ItemRarity::from((int)$row["rarity"]);
        $item->description = $row["desc"];
        $item->name = $row["name"];

        $questReward->item = $item;

        if ($row["BigImgPath"] != null)
        {
            $bigImg = new vMedia();
            $bigImg->setMediaPath($row["BigImgPath"]);
            
            $author = new vAccount();
            $author->username = $row["artist"];
            $bigImg->author = $author;
            $item->iconBig = $bigImg;
        }

        if ($row["SmallImgPath"] != null)
        {
            $smallImg = new vMedia();
            $smallImg->setMediaPath($row["SmallImgPath"]);
            
            $author = new vAccount();
            $author->username = $row["artist"];
            $smallImg->author = $author;

            $item->iconSmall = $smallImg;
        }

        $item->iconBack = $item->iconBig;

        return $questReward;
    }

    private static function row_to_vQuestApplicant(array $row) : vQuestApplicant {
        $questApplicant = new vQuestApplicant();

        $questApplicant->account = AccountController::row_to_vAccount($row);

        $questApplicant->seed = $row["seed"];
        $questApplicant->rank = (int)($row["rank"] ?? -1);
        $questApplicant->accepted = boolval($row["accepted"] ?? false);
        $questApplicant->participated = boolval($row["participated"] ?? false);
        return $questApplicant;
    }

    public static function submitFeedbackAndCollectRewards(vRecordId $account_id, vRecordId $quest_id, ?int $host_rating, ?int $quest_rating, ?string $feedback) : Response {
        $conn = Database::getConnection();
        
        //giving error
        //assert($conn instanceof mysqli);
        
        // Prepare the SQL statement
        $stmt = $conn->prepare("CALL SubmitFeedbackAndCollectRewards(?, ?, ?, ?, ?)");
        if (false === $stmt) {
            error_log($conn->error);
            return new Response(false,
                "Error occurred while collecting rewards",
                ["Error in SubmitFeedbackAndCollectRewards(...) when preparing SQL query. (mysqli_prepare)"]);
        }

        // Bind the parameters to the SQL statement
        $success = $stmt->bind_param('iiiss', $account_id->crand, $quest_id->crand, $host_rating, $quest_rating, $feedback);
        if (false === $success) {
            error_log($stmt->error);
            $stmt->close();
            return new Response(false,
                "Error occurred while collecting rewards",
                ["Error in SubmitFeedbackAndCollectRewards(...) binding SQL query parameters. (mysqli_stmt_bind_param)"]);
        }

        // Execute the SQL statement
        $success = $stmt->execute();
        if (false === $success) {
            error_log($stmt->error);
            $stmt->close();
            return new Response(false,
                "Error occurred while collecting rewards",
                ["Error in SubmitFeedbackAndCollectRewards(...) when executing SQL query. (mysqli_stmt_execute)"]);
        }

        // Success
        $stmt->close();
        return (new Response(true, "Feedback submitted and rewards converted successfully", null));
    }

    public static function insertNewQuest() : Response
    {
        if (!Session::isQuestGiver()) {
            return (new Response(false, "You do not have permissions to post a new quest.", null));
        }

        $questName = "New Quest";
        $questLocator = "new-quest-".Session::getCurrentAccount()->crand;

        if (!self::queryQuestByLocatorInto($questLocator, $quest))
        {
            $questModel = new Quest();
            $questModel->title = $questName;
            $questModel->locator = $questLocator;
            $insertResp = self::insert($questModel);
            $quest = $insertResp->data;
        }

        if (!$quest->hasPageContent())
        {
            $newContentId = ContentController::insertNewContent();

            self::updateQuestContent($quest,new vRecordId('', $newContentId));

            $quest = self::queryQuestByLocator($questLocator);
        }

        return (new Response(true, "New quest created.", $quest));
    }

    public static function insert(Quest $quest) : Response
    {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("INSERT INTO quest (name, locator, host_id) values (?,?,?)");
        mysqli_stmt_bind_param($stmt, 'ssi', $quest->title, $quest->locator, Session::getCurrentAccount()->crand);
        mysqli_stmt_execute($stmt);
        //$newId = mysqli_insert_id($conn);
        $questResp = self::queryQuestByLocatorAsResponse($quest->locator);
        $rewardResp = self::setupStandardParticipationRewards($questResp->data);
        $questResp = self::queryQuestByLocatorAsResponse($quest->locator);
        return $questResp;
    }

    public static function countQuestParticipations(int $accountId): int
    {
        $conn = Database::getConnection();

        $sql = "SELECT COUNT(*) FROM quest_applicants
                WHERE account_id = ? AND participated = 1";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return 0;
        }

        $stmt->bind_param("i", $accountId);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        return (int)$count;
    }
    
    public static function countQuestParticipationsBetween(int $accountId, string $startDate, string $endDate): int
    {
        $conn = Database::getConnection();
    
        $sql = "SELECT COUNT(*) FROM quest_applicants qa
                JOIN quest q ON qa.quest_id = q.Id
                WHERE qa.account_id = ?
                  AND qa.participated = 1
                  AND q.end_date BETWEEN ? AND ?";
    
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return 0;
        }
    
        $stmt->bind_param("iss", $accountId, $startDate, $endDate);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
    
        return (int)$count;
    }
    public static function countRaffleEntries(int $accountId): int
    {
        $conn = Database::getConnection();

        $sql = "SELECT COUNT(*) 
                FROM raffle_submissions rs
                JOIN loot l ON rs.loot_id = l.Id
                WHERE l.account_id = ?";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return 0;
        }

        $stmt->bind_param("i", $accountId);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        return (int)$count;
    }
    public static function countRaffleEntriesBetween(int $accountId, string $startDate, string $endDate): int
    {
        $conn = Database::getConnection();
    
        $sql = "SELECT COUNT(*)
                FROM raffle_submissions rs
                JOIN loot l ON rs.loot_id = l.Id
                JOIN quest q ON rs.raffle_id = q.raffle_id
                WHERE l.account_id = ?
                  AND q.end_date BETWEEN ? AND ?";
    
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return 0;
        }
    
        $stmt->bind_param("iss", $accountId, $startDate, $endDate);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        return (int)$count;
    }

    public static function queryQuestReviewsByHostAsResponse(vRecordId $hostId): Response
    {
        $conn = Database::getConnection();
        $stmt = $conn->prepare(
            "SELECT q.Id AS quest_id, q.name, q.locator, q.end_date, q.imagePath_icon, q.imagePath, qa.host_rating, qa.quest_rating, qa.feedback " .
            "FROM quest_applicants qa " .
            "JOIN v_quest_info q ON qa.quest_id = q.Id " .
            "WHERE (q.host_id = ? OR q.host_id_2 = ?) AND qa.host_rating IS NOT NULL " .
            "ORDER BY q.end_date DESC"
        );
        if ($stmt === false) {
            return new Response(false, "Failed to prepare query", null);
        }

        $stmt->bind_param('ii', $hostId->crand, $hostId->crand);
        $stmt->execute();
        $result = $stmt->get_result();

        $questRatings = [];
        while ($row = $result->fetch_assoc()) {
            $questId = (int)$row['quest_id'];
            $hostRating = (int)$row['host_rating'];
            $questRating = (int)$row['quest_rating'];
            $hasComment = trim((string)$row['feedback']) !== '';

            if (!isset($questRatings[$questId])) {
                $questRatings[$questId] = [
                    'questId' => $questId,
                    'questTitle' => $row['name'],
                    'questLocator' => $row['locator'],
                    'questEndDate' => $row['end_date'],
                    'questIcon' => $row['imagePath_icon'],
                    'questBanner' => $row['imagePath'],
                    'hostRatingSum' => $hostRating,
                    'questRatingSum' => $questRating,
                    'count' => 1,
                    'hasComments' => $hasComment,
                ];
            } else {
                $questRatings[$questId]['hostRatingSum'] += $hostRating;
                $questRatings[$questId]['questRatingSum'] += $questRating;
                $questRatings[$questId]['count']++;
                if ($hasComment) {
                    $questRatings[$questId]['hasComments'] = true;
                }
            }
        }

        $averages = [];
        foreach ($questRatings as $data) {
            $icon = new vMedia();
            $icon->setMediaPath($data['questIcon']);
            $banner = new vMedia();
            $banner->setMediaPath($data['questBanner']);
            $summary = new vQuestReviewSummary();
            $summary->questId = $data['questId'];
            $summary->questTitle = $data['questTitle'];
            $summary->questLocator = $data['questLocator'];
            $summary->questEndDate = $data['questEndDate'];
            $summary->questIcon = $icon->getFullPath();
            $summary->questBanner = $banner->getFullPath();
            $summary->avgHostRating = $data['hostRatingSum'] / $data['count'];
            $summary->avgQuestRating = $data['questRatingSum'] / $data['count'];
            $summary->hasComments = $data['hasComments'];
            $averages[] = $summary;
        }

        return new Response(true, "Quest review averages loaded.", $averages);
    }

    /**
     * @param array<vQuestApplicant>|null $applicants
     * @deprecated Use queryQuestReviewDetailsForQuests() for bulk queries.
     */
    public static function queryQuestReviewDetailsAsResponse(vQuest $quest, ?array $applicants = null): Response
    {
        $conn = Database::getConnection();

        // If applicants were not preloaded, fetch both applicants and reviews in a single query
        if ($applicants === null) {
            $stmt = $conn->prepare(
                "SELECT qa.account_id, acc.Username AS username, acc.avatar_media, qa.host_rating, qa.quest_rating, qa.feedback AS text
                 FROM quest_applicants qa
                 JOIN v_account_info acc ON qa.account_id = acc.Id
                 WHERE qa.quest_id = ? AND qa.participated = 1"
            );
            if ($stmt === false) {
                return new Response(false, 'Failed to prepare query', null);
            }

            $stmt->bind_param('i', $quest->crand);
            $stmt->execute();
            $result = $stmt->get_result();

            $details = [];
            while ($row = $result->fetch_assoc()) {
                $id = (int)$row['account_id'];
                if ($id === $quest->host1->crand || (isset($quest->host2) && $id === $quest->host2->crand)) {
                    continue;
                }

                $avatar = null;
                if (!empty($row['avatar_media'])) {
                    $media = new vMedia();
                    $media->setMediaPath($row['avatar_media']);
                    $avatar = $media->getFullPath();
                }

                $detail = new vQuestReviewDetail();
                $detail->accountId = $id;
                $detail->username = $row['username'];
                $detail->avatar = $avatar;
                $detail->hostRating = isset($row['host_rating']) ? (int)$row['host_rating'] : null;
                $detail->questRating = isset($row['quest_rating']) ? (int)$row['quest_rating'] : null;
                $detail->message = $row['text'] ?? null;
                $details[] = $detail;
            }

            return new Response(true, 'Quest review details loaded.', $details);
        }

        // Applicants were provided, fetch only review data
        $stmt = $conn->prepare(
            'SELECT account_id, host_rating, quest_rating, feedback AS text FROM quest_applicants WHERE quest_id = ? AND participated = 1'
        );
        if ($stmt === false) {
            return new Response(false, 'Failed to prepare query', null);
        }

        $stmt->bind_param('i', $quest->crand);
        $stmt->execute();
        $result = $stmt->get_result();

        $reviews = [];
        while ($row = $result->fetch_assoc()) {
            $reviews[(int)$row['account_id']] = [
                'hostRating' => isset($row['host_rating']) ? (int)$row['host_rating'] : null,
                'questRating' => isset($row['quest_rating']) ? (int)$row['quest_rating'] : null,
                'message' => $row['text'] ?? null,
            ];
        }

        $details = [];
        foreach ($applicants as $applicant) {
            if (!$applicant->participated) {
                continue;
            }

            $account = $applicant->account;
            $id = $account->crand;
            if ($id === $quest->host1->crand || (isset($quest->host2) && $id === $quest->host2->crand)) {
                continue;
            }

            $avatar = $account->avatar ? $account->avatar->getFullPath() : null;
            $detail = new vQuestReviewDetail();
            $detail->accountId = $id;
            $detail->username = $account->username;
            $detail->avatar = $avatar;
            if (isset($reviews[$id])) {
                $detail->hostRating = $reviews[$id]['hostRating'];
                $detail->questRating = $reviews[$id]['questRating'];
                $detail->message = $reviews[$id]['message'];
            } else {
                $detail->hostRating = null;
                $detail->questRating = null;
                $detail->message = null;
            }
            $details[] = $detail;
        }

        return new Response(true, 'Quest review details loaded.', $details);
    }

    /**
     * Fetch review details for multiple quests in a single query.
     *
     * @param array<int> $questIds
     * @return array<int, array<vQuestReviewDetail>> keyed by quest id
     */
    public static function queryQuestReviewDetailsForQuests(array $questIds): array
    {
        if (empty($questIds)) {
            return [];
        }

        $conn = Database::getConnection();
        $placeholders = implode(',', array_fill(0, count($questIds), '?'));
        $sql = "SELECT qa.quest_id, qa.account_id, acc.Username AS username, acc.avatar_media, qa.host_rating, qa.quest_rating, qa.feedback AS text, q.host_id, q.host_id_2 " .
            "FROM quest_applicants qa " .
            "JOIN v_account_info acc ON qa.account_id = acc.Id " .
            "JOIN quest q ON qa.quest_id = q.Id " .
            "WHERE qa.quest_id IN ($placeholders) AND qa.participated = 1";

        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            return [];
        }

        $types = str_repeat('i', count($questIds));
        // @phpstan-ignore-next-line
        $stmt->bind_param($types, ...$questIds);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result === false) {
            return [];
        }

        $byQuest = [];
        while ($row = $result->fetch_assoc()) {
            $qid = (int)$row['quest_id'];
            $accountId = (int)$row['account_id'];
            $host1 = (int)$row['host_id'];
            $host2 = isset($row['host_id_2']) ? (int)$row['host_id_2'] : null;
            if ($accountId === $host1 || ($host2 !== null && $accountId === $host2)) {
                continue;
            }

            $avatar = null;
            if (!empty($row['avatar_media'])) {
                $media = new vMedia();
                $media->setMediaPath($row['avatar_media']);
                $avatar = $media->getFullPath();
            }

            $detail = new vQuestReviewDetail();
            $detail->accountId = $accountId;
            $detail->username = $row['username'];
            $detail->avatar = $avatar;
            $detail->hostRating = isset($row['host_rating']) ? (int)$row['host_rating'] : null;
            $detail->questRating = isset($row['quest_rating']) ? (int)$row['quest_rating'] : null;
            $detail->message = $row['text'] ?? null;

            $byQuest[$qid][] = $detail;
        }

        return $byQuest;
    }

    public static function queryHostStatsForAccounts(array $accountIds): array
    {
        if (empty($accountIds)) {
            return [];
        }

        $conn = Database::getConnection();
        $placeholders = implode(',', array_fill(0, count($accountIds), '?'));
        $sql = "SELECT h.host_id AS host_id, COUNT(DISTINCT h.quest_id) AS questsHosted, " .
            "AVG(qa.host_rating) AS avgHostRating, AVG(qa.quest_rating) AS avgQuestRating " .
            "FROM (" .
            "  SELECT q.Id AS quest_id, q.host_id AS host_id FROM v_quest_info q WHERE q.published = 1 AND q.host_id IN ($placeholders) " .
            "  UNION ALL " .
            "  SELECT q.Id AS quest_id, q.host_id_2 AS host_id FROM v_quest_info q WHERE q.published = 1 AND q.host_id_2 IN ($placeholders) " .
            ") h " .
            "LEFT JOIN quest_applicants qa ON qa.quest_id = h.quest_id " .
            "GROUP BY h.host_id";

        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            return [];
        }

        $params = array_merge($accountIds, $accountIds);
        $types = str_repeat('i', count($params));
        // @phpstan-ignore-next-line
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result === false) {
            return [];
        }

        $stats = [];
        while ($row = $result->fetch_assoc()) {
            $hostId = (int)$row['host_id'];
            $stats[$hostId] = [
                'questsHosted' => (int)$row['questsHosted'],
                'avgHostRating' => isset($row['avgHostRating']) ? (float)$row['avgHostRating'] : 0.0,
                'avgQuestRating' => isset($row['avgQuestRating']) ? (float)$row['avgQuestRating'] : 0.0,
            ];
        }

        return $stats;
    }

}
?>
