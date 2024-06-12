<?php
declare(strict_types=1);

namespace Kickback\Controllers;

use Kickback\Views\vLoot;
use Kickback\Views\vItem;
use Kickback\Views\vAccount;
use Kickback\Views\vQuest;
use Kickback\Views\vMedia;
use Kickback\Views\vDateTime;
use Kickback\Views\vRecordId;
use Kickback\Models\Response;
use Kickback\Services\Database;

class LootController
{
    
    public static function getBadgesByAccount(vRecordId $recordId) : Response
    {
        $conn = Database::getConnection();
        // Prepare the SQL statement
        $sql = "SELECT * from v_account_badge_info where account_id = ?";

        // Initialize the prepared statement
        $stmt = mysqli_prepare($conn, $sql);

        if($stmt === false) {
            return (new Response(false, "Failed to prepare the SQL statement."));
        }

        // Bind the parameter to the prepared statement
        mysqli_stmt_bind_param($stmt, "i", $recordId->crand);

        // Execute the prepared statement
        mysqli_stmt_execute($stmt);

        $result = $stmt->get_result();
        if (!$result) {
            return new Response(false, "Failed to retrieve the result set.");
        }
    
        
        $badges = [];
        while ($row = $result->fetch_assoc()) {
            $badge = self::row_to_vLoot($row);
            $badges[] = $badge;
        }

        $stmt->close();


        return (new Response(true, "Requested users badges.",  $badges ));
    }

    private static function row_to_vLoot($row) : vLoot
    {
        $loot = new vLoot();

        $ownerId = new vRecordId('',$row["account_id"]);

        $loot->ownerId = $ownerId;


        if ($row["quest_id"] != null)
        {
            $quest = new vQuest('', $row["quest_id"]);
            $quest->title = $row["quest_name"];
            $quest->locator = $row["quest_locator"];

            $loot->quest = $quest;
        }

        $dateObtained = new vDateTime();
        $dateObtained->setDateTimeFromString($row["dateObtained"]);
        $loot->dateObtained = $dateObtained;

        $loot->item = ItemController::row_to_vItem($row);

        return $loot;
    }

}
?>
