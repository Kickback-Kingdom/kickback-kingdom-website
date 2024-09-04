<?php
declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use Kickback\Backend\Models\Response;
use Kickback\Services\Database;
use Kickback\Backend\Views\vActivity;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vMedia;
use Kickback\Backend\Views\vDateTime;

class ActivityController
{
    public static function getActivityByAccount(vAccount $account) : Response
    {
        $conn = Database::getConnection();
        $stmt = mysqli_prepare($conn, "SELECT * FROM v_account_activity where account_id = ? and finished = 1 and published = 1");
        mysqli_stmt_bind_param($stmt, "i", $account->crand);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        
        
        $activities = [];
        foreach ($rows as $row) {
            $activity = self::row_to_vActivity($row, $account);
            $activities[] = $activity;
        }

        
        return (new Response(true, "Account Activity",  $activities ));
    }
    private static function row_to_vActivity(array $row, vAccount $account) : vActivity
    {
        $activity = new vActivity();
        $activity->type = $row["type"];
        $activity->account = $account;
        $activity->verb = $row["event_verb"];
        $activity->nameId = (int) $row["event_name_id"];
        $activity->name = $row["event_name"];
        $activity->team = $row["event_team"];
        $activity->character = $row["event_character"];
        $activity->characterWasRandom = is_null($row["event_character_was_random"]) ? null : $row["event_character_was_random"] == 1 ;

        $eventDate = new vDateTime();
        $eventDate->setDateTimeFromString($row["event_date"]);

        $activity->dateTime = $eventDate;

        if ($row['event_icon_id'] != null)
        {
            $icon = new vMedia('', $row["event_icon_id"]);
            $icon->setMediaPath($row['event_icon_path']);
            $activity->icon = $icon;
        }
        else
        {

            $otherAccount = new vAccount('',$activity->nameId);
            $activity->icon = $otherAccount->avatar;
        }

        $activity->url = $row["event_url"];

        return $activity;
    }
}
?>
