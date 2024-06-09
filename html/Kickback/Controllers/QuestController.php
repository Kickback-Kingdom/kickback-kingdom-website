<?php
declare(strict_types=1);

namespace Kickback\Controllers;

use Kickback\Models\Response;
use Kickback\Services\Database;
use Kickback\Services\Session;
use Kickback\Views\vQuest;
use Kickback\Views\vQuestReward;
use Kickback\Views\vRecordId;
use Kickback\Views\vDateTime;
use Kickback\Views\vMedia;
use Kickback\Views\vAccount;
use Kickback\Views\vContent;
use Kickback\Views\vItem;
use Kickback\Views\vReviewStatus;
use Kickback\Views\vQuestLine;
use Kickback\Models\PlayStyle;
use Kickback\Models\ItemType;
use Kickback\Models\ItemRarity;
use Kickback\Views\vTournament;
use Kickback\Views\vQuestApplicant;
use Kickback\Controllers\AccountController;

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
        $stmt->execute();

        // Get the result
        $result = $stmt->get_result();
        $quests = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $quests[] = self::row_to_vQuest($row);
            }
            return new Response(true, "Quest Information.", $quests);
        }

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

    function GetAvailableQuests() : Response {
        $id = mysqli_real_escape_string($GLOBALS["conn"], $id);
        $sql = "SELECT * FROM kickbackdb.v_quest_info  WHERE end_date > CURRENT_TIMESTAMP and published = 1 order by end_date asc";

        
        $result = mysqli_query($GLOBALS["conn"],$sql);

        $num_rows = mysqli_num_rows($result);
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        
        return (new Response(true, "Available Quests",  $rows ));
    }
        
    function GetArchivedQuests() : Response {
        $id = mysqli_real_escape_string($GLOBALS["conn"], $id);
        $sql = "SELECT * FROM kickbackdb.v_quest_info  WHERE end_date <= CURRENT_TIMESTAMP and published = 1 and finished = 1 order by end_date desc";

        
        $result = mysqli_query($GLOBALS["conn"],$sql);

        $num_rows = mysqli_num_rows($result);
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        
        return (new Response(true, "Available Quests",  $rows ));
    }

    function GetTBAQuests() : Response {
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
        case when `quest_applicants`.`participated` = 1 
        or `quest_applicants`.`seed_score` > 0 then rank() over (
          partition by `quest_applicants`.`quest_id` 
          order by 
            `quest_applicants`.`seed_score` desc, 
            `account`.`exp` desc, 
            `account`.`Id` desc
        ) else NULL end AS `seed`, 
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

    private static function row_to_vQuest($row) : vQuest {
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
            $date = new vDateTime();
            $date->setDateTimeFromString($row["end_date"]);
            $quest->endDate = $date;
        }

        if ($row["image_id"] != null)
        {
            $banner = new vMedia('',$row["image_id"]);
            $banner->setMediaPath($row["imagePath"]);
            $quest->banner = $banner;
        }

        if ($row["image_id_icon"] != null)
        {
            $icon = new vMedia('',$row["image_id_icon"]);
            $icon->setMediaPath($row["imagePath_icon"]);
            $quest->icon = $icon;
        }

        if ($row["image_id_mobile"] != null)
        {
            $bannerMobile = new vMedia('',$row["image_id_mobile"]);
            $bannerMobile->setMediaPath($row["imagePath_mobile"]);
            $quest->bannerMobile = $bannerMobile;
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
            $quest->content->htmlContent = $row["desc"];
        }

        if ($row["quest_line_id"] != null)
        {
            $quest->questLine = new vQuestLine('',$row["quest_line_id"]);
        }

        $host1 = new vAccount('', $row["host_id"]);
        $host1->username = $row["host_name"];

        $quest->host1 = $host1;

        $quest->requiresApplication = (bool)$row["req_apply"];

        $quest->playStyle = PlayStyle::from($row["play_style"]);

        return $quest;
    }

    private static function row_to_vQuestReward($row) : vQuestReward {
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

    private static function row_to_vQuestApplicant($row) : vQuestApplicant {
        $questApplicant = new vQuestApplicant();

        $questApplicant->account = AccountController::row_to_vAccount($row);

        $questApplicant->seed = $row["seed"];
        $questApplicant->rank = $row["rank"];
        return $questApplicant;
    }
}
?>
