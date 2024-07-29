<?php
declare(strict_types=1);

namespace Kickback\Controllers;

use Kickback\Views\vItem;
use Kickback\Views\vMedia;
use Kickback\Views\vRecordId;
use Kickback\Models\Response;
use Kickback\Services\Database;
use Kickback\Views\vAccount;
use Kickback\Models\ItemEquipmentSlot;
class ItemController
{

    public static function getItemById(vRecordId $item_id) : Response {
        // Prepare SQL statement
        $conn = Database::getConnection();
        $stmt = mysqli_prepare($conn, "SELECT * FROM item WHERE Id = ?");
   
        mysqli_stmt_bind_param($stmt, "i", $item_id->crand);
    
        // Execute the SQL statement
        mysqli_stmt_execute($stmt);
    
        // Get the result of the SQL query
        $result = mysqli_stmt_get_result($stmt);
    
        $num_rows = mysqli_num_rows($result);
        if ($num_rows === 0)
        {
            // Free the statement
            mysqli_stmt_close($stmt);
    
            return (new Response(false, "Couldn't find an item with that Id", null));
            
        }
        else
        {
            $row = mysqli_fetch_assoc($result);
    
            // Free the statement
            mysqli_stmt_close($stmt);
            
            return (new Response(true, "Item information.",  self::row_to_vItem($row) ));
        }
    }

    public static function usePrestigeToken(vRecordId $fromAccountId, vRecordId $toAccountId, bool $commend, string $desc) : Response {
        if ($fromAccountId == null) {
            return new Response(false, "Failed to use prestige token because you provided a null fromAccountId.", $fromAccountId);
        }
        if ($toAccountId == null) {
            return new Response(false, "Failed to use prestige token because you provided a null toAccountId.", $toAccountId);
        }
        if ($fromAccountId->crand == $toAccountId->crand) {
            return new Response(false, "You cannot leave a review on yourself.", $toAccountId);
        }
        if ($commend === null) {
            return new Response(false, "Failed to use prestige token because you provided a null rating.", $commend);
        }
        if ($desc == null) {
            return new Response(false, "Failed to use prestige token because you provided a null review.", $desc);
        }
    
        $conn = Database::getConnection();
        // Get unused prestige token
        $prestigeTokenResp = AccountController::getPrestigeTokens($fromAccountId);
        $prestigeTokenInfo = $prestigeTokenResp->data;
    
        if ($prestigeTokenInfo["remaining"] > 0) {
            $lootId = $prestigeTokenInfo["next_token"];
    
            $sql = "INSERT INTO prestige (account_id_from, account_id_to, commend, loot_id, `Desc`) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
    
            if ($stmt === false) {
                return new Response(false, "Failed to prepare statement", $prestigeTokenResp);
            }
    
            $stmt->bind_param("iiiis", $fromAccountId->crand, $toAccountId->crand, $commend, $lootId, $desc);
            $result = $stmt->execute();
    
            if ($result) {
                return new Response(true, "Successfully used a prestige token", null);
            } else {
                return new Response(false, "Failed to leave review with error: " . $stmt->error, $prestigeTokenResp);
            }
        } else {
            return new Response(false, "Failed to use prestige token because you have none.", $prestigeTokenResp);
        }
    }
    

    public static function row_to_vItem($row) : vItem {
        $item = new vItem();
        $item->name = $row["name"];
        $item->description = $row["desc"];

        if (array_key_exists("item_id",$row) && $row["item_id"] != null)
        {
            $item->crand = $row["item_id"];
        }

        if (array_key_exists("nominated_by_id",$row) && $row["nominated_by_id"] != null)
        {
            $nominatedBy = new vAccount('', $row["nominated_by_id"]);
            $nominatedBy->username = $row["nominated_by"];
            $item->nominatedBy = $nominatedBy;
        }

        if (array_key_exists("BigImgPath",$row) && $row["BigImgPath"] != null)
        {
            $bigImg = new vMedia();
            $bigImg->setMediaPath($row["BigImgPath"]);
            $item->iconBig = $bigImg;
        }
        if (array_key_exists("large_image",$row) && $row["large_image"] != null)
        {
            $bigImg = new vMedia();
            $bigImg->setMediaPath($row["large_image"]);
            $bigImg->author = new vAccount();
            $bigImg->author->username = $row["artist"];
            $item->iconBig = $bigImg;
        }

        if (array_key_exists("SmallImgPath",$row) && $row["SmallImgPath"] != null)
        {
            
            $smallImg = new vMedia();
            $smallImg->setMediaPath($row["SmallImgPath"]);
            $item->iconSmall = $smallImg;

        }
        if (array_key_exists("small_image",$row) && $row["small_image"] != null)
        {
            
            $smallImg = new vMedia();
            $smallImg->setMediaPath($row["small_image"]);
            $smallImg->author = new vAccount();
            $smallImg->author->username = (string) $row["artist"];
            $item->iconSmall = $smallImg;
        }
        if (array_key_exists("equipable",$row))
        {
            $item->equipable = $row["equipable"] == 1;
        }
        if (array_key_exists("equipment_slot",$row) && $row["equipment_slot"] != null)
        {
            $item->equipmentSlot = ItemEquipmentSlot::fromString($row["equipment_slot"]);
        }
        return $item;
    }

}
?>
