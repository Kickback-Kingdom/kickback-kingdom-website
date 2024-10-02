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
use Kickback\Backend\Controllers\AccountController;
use Kickback\Backend\Controllers\SocialMediaController;

class QuestController
{
    public static function getQuestById(vRecordId $recordId): Response {
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
    
    public static function getQuestByLocator(string $locator): Response {
        $conn = Database::getConnection();
        $sql = "SELECT * FROM v_quest_info WHERE locator = ?";

        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            return new Response(false, "Failed to prepare statement", null);
        }

        $stmt->bind_param('s', $locator);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return new Response(true, "Quest Information.", self::row_to_vQuest($row));
        }

        return new Response(false, "Couldn't find a quest with that locator", null);
    }

    function getAvailableQuests() : Response {
        $id = mysqli_real_escape_string($GLOBALS["conn"], $id);
        $sql = "SELECT * FROM kickbackdb.v_quest_info  WHERE end_date > CURRENT_TIMESTAMP and published = 1 order by end_date asc";

        
        $result = mysqli_query($GLOBALS["conn"],$sql);

        $num_rows = mysqli_num_rows($result);
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        
        return (new Response(true, "Available Quests",  $rows ));
    }
        
    function getArchivedQuests() : Response {
        $id = mysqli_real_escape_string($GLOBALS["conn"], $id);
        $sql = "SELECT * FROM kickbackdb.v_quest_info  WHERE end_date <= CURRENT_TIMESTAMP and published = 1 and finished = 1 order by end_date desc";

        
        $result = mysqli_query($GLOBALS["conn"],$sql);

        $num_rows = mysqli_num_rows($result);
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        
        return (new Response(true, "Available Quests",  $rows ));
    }

    function getTBAQuests() : Response {
        if (Session::isLoggedIn())
        {
            if (Session::isAdmin())
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

    public static function getQuestsByQuestLineId(vRecordId $questLineId, int $page = 1, int $itemsPerPage = 10): Response {
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

    public static function getQuestRewardsByQuestId(vRecordId $questId) : Response {
        $conn = Database::getConnection();
        $sql = "SELECT * FROM v_quest_reward_info WHERE quest_id = ?";

        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            return new Response(false, "Failed to prepare statement", null);
        }

        $stmt->bind_param('i', $questId->crand);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result) {
            $rewards = [];
            while ($row = $result->fetch_assoc()) {
                $rewards[] = self::row_to_vQuestReward($row);
            }
            return new Response(true, "Quest Rewards Loaded", $rewards);
        }

        return new Response(false, "Couldn't find rewards for that quest id", null);
    }

    public static function getQuestApplicants(vQuest $quest): Response {
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

        if ($result) {
            $applicants = [];
            while ($row = $result->fetch_assoc()) {
                $applicants[] = self::row_to_vQuestApplicant($row);
            }
            return new Response(true, "Quest Applicants", $applicants);
        }

        return new Response(false, "Couldn't find applicants for that quest id", null);
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
        if ($quest->endDate->value <= $current_date) {
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
                $msg = FlavorTextController::getRaffleWinnerAnnouncement($raffleQuest["name"], $raffleWinner[0]["Username"]);
                SocialMediaController::DiscordWebHook($msg);

                return new Response(true, "Selected Raffle Winner!", null);
            } else {
                return new Response(false, "No rows affected, raffle winner not set", null);
            }
        } else {
            return new Response(true, "No raffle tickets were entered!", null);
        }
    }

    public static function getRaffleParticipants(vRaffle $raffle): Response {
        $conn = Database::getConnection();
        $sql = "SELECT * FROM v_raffle_participants WHERE raffle_id = ?";

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

        return new Response(true, "Raffle Participants", $rows);
    }

    public static function removeStandardParticipationRewards(vRecordId $questId) : Response {
        $questResp = self::getQuestById($questId);
        $quest = $questResp->data;
        if (!$quest->canEdit())
        {
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
        
    public static function setupStandardParticipationRewards(vRecordId $questId) : Response {
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

    public static function addStandardParticipationRewards($questId) : Response {
        $questResp = self::getQuestById($questId);
        $quest = $questResp->data;
        if (!$quest->canEdit())
        {
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
                $errorMessages[] = "Failed to insert reward ID $rewardId for quest ID $questId: " . mysqli_error($conn);
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

    public static function rejectQuestReviewById(vRecordId $questId) : Response {
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

    public static function updateQuestOptions($data) : Response {
        
        $conn = Database::getConnection();

        $questId = new vRecordId('', (int)$data["edit-quest-id"]);
        $questName = $data["edit-quest-options-title"];
        $questLocator = $data["edit-quest-options-locator"];
        $questHostId2 = $data["edit-quest-options-host-2-id"];
        $questSummary = $data["edit-quest-options-summary"];
        $hasADate = isset($data["edit-quest-options-has-a-date"]);
        $dateTime = $data["edit-quest-options-datetime"];
        $playStyle = $data["edit-quest-options-style"];
        $questLineId = $data["edit-quest-options-questline"];
        $questLineIdValue = empty($questLineId) ? NULL : $questLineId;

        $questResp = self::getQuestById($questId);

        if (!$questResp->success)
        {
            return (new Response(false, "Error updating quest. Could not find quest by Id.", null));
        }
        $quest = $questResp->data;

        if (StringStartsWith($questLocator, 'new-quest-'))
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
        $host_id_2 = empty($questHostId2) ? NULL : $questHostId2;
        $end_date = $hasADate && !empty($dateTime) ? $dateTime : NULL;
        
        //$date = $data["edit-quest-options-datetime-date"];
        //$time = $data["edit-quest-options-datetime-time"];
        //$end_date = $hasADate && !empty($date) && !empty($time) ? $date . ' ' . $time . ":00": NULL;

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
    
    public static function updateQuestImages(vRecordId $questId,vRecordId  $desktop_banner_id,vRecordId  $mobile_banner_id,vRecordId  $icon_id) : Response {

        $conn = Database::getConnection();

        $questResp = self::getQuestById($questId);

        if (!$questResp->success)
        {
            return (new Response(false, "Error updating quest. Could not find quest by Id.", null));
        }
        $quest = $questResp->data;

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

        
        $questResp = self::getQuestById($questId); 
        if (!$questResp->success) {
            return new Response(false, "Quest submission failed. Quest not found.", null);
        }

        $quest = $questResp->data;
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
        
        if (!Session::isAdmin()) {
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
        $loot_id = GetUnusedAccountRaffleTicket($account_id)->data;
        $raffle_id = mysqli_real_escape_string($conn, $raffle_id);

        $sql = "INSERT INTO raffle_submissions (raffle_id, loot_id) VALUES ($raffle_id,$loot_id);";
        $result = mysqli_query($conn,$sql);
        if ($result === TRUE) {

            return (new Response(true, "Submitted Raffle Ticket!",null));
        } 
        else 
        {
            return (new Response(false, "Failed to submit raffle ticket with error: ".GetSQLError(), null));
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

    public static function applyOrRegisterForQuest(vRecordId $account_id, vRecordId $quest_id) : Response {
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
            $quest = self::getQuestById($quest_id)->data;
            SocialMediaController::DiscordWebHook(FlavorTextController::GetRandomGreeting() . ', ' . $account->username . ' just signed up for the ' . $quest->title . ' quest.');
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
            $quest->tournament->hasBracket = (bool)$row["hasBracket"]==1;
        }

        if ($row["end_date"] != null)
        {
            $quest->endDate = new vDateTime($row["end_date"]);
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

        return $questReward;
    }

    private static function row_to_vQuestApplicant(array $row) : vQuestApplicant {
        $questApplicant = new vQuestApplicant();

        $questApplicant->account = AccountController::row_to_vAccount($row);

        $questApplicant->seed = $row["seed"];
        $questApplicant->rank = ((int)$row["rank"] ?? -1);
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

    public static function insertNewQuest() : Response {
        if (Session::isQuestGiver())
        {
            
            $questName = "New Quest";
            $questLocator = "new-quest-".Session::getCurrentAccount()->crand;

            $questResp = self::getQuestByLocator($questLocator);
            if (!$questResp->success)
            {
                $questModel = new Quest();
                $questModel->title = $questName;
                $questModel->locator = $questLocator;
                $insertResp = self::insert($questModel);
                $quest = $insertResp->data;
            }
            else
            {
                $quest = $questResp->data;
            }
            if (!$quest->hasPageContent())
            {
                $newContentId = ContentController::insertNewContent();
                
                self::updateQuestContent($questResp->data,new vRecordId('', $newContentId));

                $questResp = self::getQuestByLocator($questLocator);
            }

            return (new Response(true, "New quest created.", $questResp->data));
        }
        else
        {
            return (new Response(false, "You do not have permissions to post a new quest.", null));
        }
    }

    public static function insert(Quest $quest) : Response {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("INSERT INTO quest (name, locator, host_id) values (?,?,?)");
        mysqli_stmt_bind_param($stmt, 'ssi', $quest->title, $quest->locator, Session::getCurrentAccount()->crand);
        mysqli_stmt_execute($stmt);
        //$newId = mysqli_insert_id($conn);
        $questResp = self::getQuestByLocator($quest->locator);
        $rewardResp = SetupStandardParticipationRewards($questResp->data);
        return self::getQuestByLocator($quest->locator);
    }
}
?>
