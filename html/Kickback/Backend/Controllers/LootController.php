<?php
declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use Kickback\Backend\Views\vLoot;
use Kickback\Backend\Views\vItemStack;
use Kickback\Backend\Views\vItem;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vQuest;
use Kickback\Backend\Views\vMedia;
use Kickback\Backend\Views\vDateTime;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Models\Response;
use Kickback\Services\Database;

class LootController
{
    
    public static function getBadgesByAccount(vRecordId $recordId) : Response {
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

    public static function getLootByAccountId(vRecordId $recordId) : Response {
        
        $conn = Database::getConnection();
        
        $sql = "SELECT * FROM kickbackdb.v_account_inventory_desc WHERE account_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt === false) {
            return new Response(false, mysqli_error($conn), null);
        }
        
        mysqli_stmt_bind_param($stmt, "i", $recordId->crand);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result === false) {
            return new Response(false, mysqli_stmt_error($stmt), null);
        }
        
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        $newsList = array_map([self::class, 'row_to_vItemStack'], $rows);
        return new Response(true, "Account Inventory", $newsList);
    }

    public static function givePrestigeToken(vRecordId $account_id) : Response {
        return GiveLoot($account_id, 3);
    }

    public static function giveBadge(vRecordId $account_id,  $item_id) : Response {
        return GiveLoot($account_id, $item_id);
    }

    public static function giveRaffleTicket(vRecordId $account_id) : Response {
        return GiveLoot($account_id, 4);
    }

    public static function giveWritOfPassage(vRecordId $account_id) : Response {
        return self::giveLoot($account_id, new vRecordId('', 14));
    }

    public static function giveMerchantGuildShare(vRecordId $account_id, $date) : Response {
        return GiveLoot($account_id, 16, $date);
    }

    public static function giveLoot(vRecordId $account_id,vRecordId $item_id, $dateObtained = null) : Response {
        $conn = Database::getConnection();

        // Checking if dateObtained is null
        if ($dateObtained === null) {
            $dateObtained = date('Y-m-d H:i:s');  // Set to current date and time
        }
        
        // Prepare the SQL statement
        $sql = "INSERT INTO loot (item_id, opened, account_id, dateObtained) VALUES (?, 0, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt) {
            // Bind parameters
            mysqli_stmt_bind_param($stmt, 'iis', $item_id->crand, $account_id->crand, $dateObtained);
            
            // Execute the statement
            $result = mysqli_stmt_execute($stmt);
            
            // Close the statement
            mysqli_stmt_close($stmt);
            
            if ($result) {
                return (new Response(true, "Successfully gave loot to account", null));
            } else {
                return (new Response(false, "Failed to award account", null));
            }
        } else {
            return (new Response(false, "Failed to prepare SQL statement!", null));
        }
    }

    public static function closeChest(vRecordId $chestId, vRecordId $accountId) : Response {
        $conn = Database::getConnection();
    
        $sql = "UPDATE loot SET opened = 1 WHERE Id = ? AND account_id = ?";
        $stmt = $conn->prepare($sql);
    
        if ($stmt === false) {
            return new Response(false, "Failed to prepare statement", null);
        }
    
        $stmt->bind_param("ii", $chestId->crand, $accountId->crand);
        $result = $stmt->execute();
    
        if ($result) {
            return new Response(true, "Chest closed successfully", null);
        } else {
            return new Response(false, "Failed to close chest with error: " . $stmt->error, null);
        }
    }
    
    private static function row_to_vLoot($row) : vLoot {
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

    private static function row_to_vItemStack($row) : vItemStack {
        $lootStack = new vItemStack();
        $lootStack->item = ItemController::row_to_vItem($row);
        $lootStack->amount = (int)$row["amount"];
        $lootStack->nextLootId = new vRecordId('', $row["next_loot_id"]);
        return $lootStack;
    }

}
?>
