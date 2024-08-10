<?php
declare(strict_types=1);

namespace Kickback\Controllers;

use Kickback\Models\Response;
use Kickback\Services\Database;
use Kickback\Views\vPrestigeReview;
use Kickback\Views\vRecordId;
use Kickback\Views\vAccount;
use Kickback\Views\vQuest;
use Kickback\Views\vMedia;
use Kickback\Views\vDateTime;

class PrestigeController
{
    public static function getPrestigeReviewsByAccountTo(vRecordId $recordId) : Response {
        $conn = Database::getConnection();
        
        // Debugging: Log the value of $recordId->crand
        error_log("Account ID to: " . $recordId->crand);
    
        $stmt = mysqli_prepare($conn, "SELECT * FROM v_prestige_info WHERE account_id_to = ?");
        if ($stmt === false) {
            return new Response(false, "Failed to prepare the statement: " . mysqli_error($conn), []);
        }
    
        // Ensure $recordId->crand is an integer
        $accountIdTo = (int) $recordId->crand;
        mysqli_stmt_bind_param($stmt, "i", $accountIdTo);
    
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($result === false) {
                return new Response(false, "Failed to get result: " . mysqli_stmt_error($stmt), []);
            }
    
            $prestigeReviews = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
            // Debugging: Log the number of rows fetched
            error_log("Number of rows fetched: " . count($prestigeReviews));
    
            $prestigeReviewObjects = [];
            foreach ($prestigeReviews as $row) {
                $prestigeReview = self::row_to_vPrestigeReview($row);
                $prestigeReviewObjects[] = $prestigeReview;
            }
    
            return new Response(true, "Account Prestige Reviews", $prestigeReviewObjects);
        } else {
            return new Response(false, "Failed to execute the statement: " . mysqli_stmt_error($stmt), []);
        }
    }
    

    public static function getAccountPrestigeValue($prestigeReviews)
    {
        $prestigeNet = 0;
        for ($i=0; $i < count($prestigeReviews); $i++) { 
            $review = $prestigeReviews[$i];
    
            if ($review->commend == 1)
            {
                $prestigeNet++;
            }
            else{
                $prestigeNet--;
            }
        }
    
        return $prestigeNet;
    }

    private static function row_to_vPrestigeReview($row) : vPrestigeReview {

        $prestigeReview = new vPrestigeReview();

        $prestigeReview->commend = (bool) ($row["commend"] == "1");
        $prestigeReview->message = (string) $row["desc"];
        $prestigeReview->dateTime = new vDateTime();
        $prestigeReview->dateTime->setDateTimeFromString($row["date"]);

        $account = new vAccount('', $row["account_id_from"]);
        $account->username = $row["Username"];

        if ($row['account_from_avatar_media'] != null)
        {
            $avatar = new vMedia();
            $avatar->setMediaPath($row['account_from_avatar_media']);
            $account->avatar = $avatar;
        }

        if ($row['quest_id'] != null)
        {
            $quest = new vQuest('', $row["quest_id"]);
            $quest->title = $row["name"];
            $quest->locator = $row["locator"];

            $questIcon = new vMedia();
            $questIcon->setMediaPath($row["imagePath"]);
            $quest->icon = $questIcon;
            $prestigeReview->fromQuest = $quest;
        }

        $prestigeReview->fromAccount = $account;

        return $prestigeReview;
    }
}
?>
