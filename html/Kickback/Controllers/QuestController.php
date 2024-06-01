<?php
declare(strict_types=1);

namespace Kickback\Controllers;

use Kickback\Models\Response;
use Kickback\Services\Database;
use Kickback\Views\vQuest;
use Kickback\Views\vQuestReward;
use Kickback\Views\vRecordId;
use Kickback\Views\vDateTime;
use Kickback\Views\vMedia;
use Kickback\Views\vAccount;


class QuestController
{
    
    public static function getQuestById(vRecordId $recordId): Response
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

    public static function getQuestByLocator(string $locator): Response
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

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return new Response(true, "Quest Information.", self::row_to_vQuest($row));
        }

        return new Response(false, "Couldn't find a quest with that locator", null);
    }

    public static function getQuestsByQuestLineId(vRecordId $questLineId, int $page = 1, int $itemsPerPage = 10): Response
    {
        $conn = Database::getConnection();
        $offset = ($page - 1) * $itemsPerPage;

        $sql = "SELECT f.* FROM kickbackdb.v_feed f
                LEFT JOIN quest q ON f.Id = q.Id 
                WHERE f.published = 1 AND q.quest_line_id = ? AND f.type = 'QUEST'
                LIMIT ? OFFSET ?";

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

    public static function getQuestByRaffleId(vRecordId $raffleId): Response
    {
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

    public static function getQuestRewardsByQuestId(vRecordId $questId) : Response
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

        if ($result) {
            $rewards = [];
            while ($row = $result->fetch_assoc()) {
                $rewards[] = self::row_to_vQuestReward($row);
            }
            return new Response(true, "Quest Rewards Loaded", $rewards);
        }

        return new Response(false, "Couldn't find rewards for that quest id", null);
    }

    public static function getQuestApplicants(vRecordId $questId): Response
    {
        $conn = Database::getConnection();
        $sql = "SELECT * FROM kickbackdb.v_quest_applicants_account WHERE quest_id = ? ORDER BY seed ASC, exp DESC, prestige DESC";

        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            return new Response(false, "Failed to prepare statement", null);
        }

        $stmt->bind_param('i', $questId->crand);
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

    
    private static function row_to_vQuest($row) : vQuest
    {
        $quest = new vQuest();

        $quest->title = $row["name"];
        $quest->locator = $row["locator"];
        $quest->published = (bool)$row["published"];
        if ($row["tournament_id"] != null)
        {
            $quest->tournamet = new vTournament('', $row["tournament_id"]);
            $quest->tournamet->hasBracket = $row["hasBracket"];
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

        $host1 = new vAccount('', $row["host_id"]);
        $host1->username = $row["host_name"];

        $quest->host1 = $host1;

        $quest->requiresApplication = (bool)$row["req_apply"];

        return $quest;
    }

    private static function row_to_vQuestReward($row) : vQuestReward
    {
        $questReward = new vQuestReward();



        return $questReward;
    }
    private static function row_to_vQuestApplicant($row) : vQuestApplicant
    {
        $questReward = new vQuestApplicant();



        return $questReward;
    }
}
?>
