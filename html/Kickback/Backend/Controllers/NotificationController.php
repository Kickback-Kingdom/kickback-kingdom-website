<?php
declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vNotification;
use Kickback\Backend\Views\vDateTime;
use Kickback\Backend\Views\vQuest;
use Kickback\Services\Database;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Models\NotificationType;
use Kickback\Backend\Views\vPrestigeReview;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vQuestReview;
use Kickback\Backend\Views\vMedia;
use Kickback\Backend\Models\PlayStyle;
use Kickback\Backend\Controllers\AccountController;

class NotificationController
{
    /**
    * @return array<vNotification>
    */
    public static function queryNotificationsByAccount(vRecordId $accountId) : array
    {
        $resp = self::queryNotificationsByAccountAsResponse($accountId);
        if ($resp->success) {
            // @phpstan-ignore-next-line
            return $resp->data;
        } else {
            throw new \Exception($resp->message);
        }
    }

    public static function queryNotificationsByAccountAsResponse(vRecordId $accountId) : Response
    {
        $conn = Database::getConnection();
        $stmt = mysqli_prepare($conn, "SELECT * FROM v_notifications WHERE account_id = ?");
        
        mysqli_stmt_bind_param($stmt, "i", $accountId->crand);
        mysqli_stmt_execute($stmt);
    
        $result = mysqli_stmt_get_result($stmt);
        
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        $num_rows = mysqli_num_rows($result);
        if ($num_rows === 0)
        {
            return (new Response(true, "Couldn't find notifications for Id", []));
        }
        else
        {
            $newsList = array_map([self::class, 'row_to_vNotification'], $rows);
            return (new Response(true, "Account notifications",  $newsList ));
        }
    }

    public static function row_to_vNotification(array $row) : vNotification {
        
        $not = new vNotification();
        $not->type = NotificationType::from($row["Type"]);
        $not->date = new vDateTime($row["date"]);
        if ($row["quest_id"] != null )
        {
            $quest = new vQuest('', $row["quest_id"]);
            $quest->title = $row["name"];
            $quest->locator = $row["locator"];
            $quest->icon = new vMedia();
            $quest->icon->setMediaPath($row["image"]);
            $quest->summary = $row["text"];
            $quest->playStyle = PlayStyle::from($row["style"]);

            $quest->host1 = new vAccount('', $row["host_id"]);
            $quest->host1->username = $row["host_name"];

            if ($row["host_id_2"] != null && $row["host_id_2"] > 0)
            {
                $quest->host2 = new vAccount('', $row["host_id_2"]);
                $quest->host2->username = $row["host_name_2"];
            }


            $not->quest = $quest;
        }
        else{
            $not->quest = null;
        }
        
        if ($not->type == NotificationType::PRESTIGE)
        {
            $prestigeReview = new vPrestigeReview('', $row["Id"]);
            $prestigeReview->message = $row["text"];
            $prestigeReview->commend = ($row["style"] == "1");
            $prestigeReview->fromAccount = new vAccount('', $row["from_id"]);
            $prestigeReview->fromAccount->username = $row["locator"];

            $not->prestigeReview = $prestigeReview;
        }
        if ($not->type == NotificationType::QUEST_REVIEWED)
        {
            $questReview = new vQuestReview('', $row["Id"]);
            $questReview->fromAccount = new vAccount('', $row["from_id"]);
            $questReview->fromAccount->username = $row["from_name"];


            $not->questReview = $questReview;
        }


        return $not;
    }

    public static function queryQuestReviewsByHostAsResponse(vRecordId $hostId): Response
    {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("SELECT * FROM v_notifications_reviewed_quests WHERE account_id = ? ORDER BY date DESC");
        if ($stmt === false) {
            return new Response(false, "Failed to prepare query", null);
        }

        $stmt->bind_param('i', $hostId->crand);
        $stmt->execute();
        $result = $stmt->get_result();

        $reviews = [];
        $accountCache = [];
        while ($row = $result->fetch_assoc()) {
            $quest = new vQuest('', $row['quest_id']);
            $quest->title = $row['name'];
            $quest->locator = $row['locator'];
            $quest->icon = new vMedia();
            $quest->icon->setMediaPath($row['image']);
            $quest->playStyle = PlayStyle::from($row['play_style']);

            $review = new vQuestReview('', $row['Id']);
            $review->questRating = (int)$row['quest_rating'];
            $review->hostRating = (int)$row['host_rating'];
            $review->message = $row['text'];

            $accountIdFrom = (int)$row['account_id_from'];
            if (!isset($accountCache[$accountIdFrom])) {
                $accountLookup = new vAccount('', $accountIdFrom);
                $accountResp = AccountController::getAccountById($accountLookup);
                if ($accountResp->success && $accountResp->data instanceof vAccount) {
                    $accountCache[$accountIdFrom] = $accountResp->data;
                } else {
                    $accountLookup->username = $row['from_name'];
                    $accountCache[$accountIdFrom] = $accountLookup;
                }
            }
            $review->fromAccount = $accountCache[$accountIdFrom];
            $review->dateTime = new vDateTime($row['date']);

            $reviews[] = ['quest' => $quest, 'review' => $review];
        }

        return new Response(true, "Quest reviews loaded.", $reviews);
    }
}
?>
