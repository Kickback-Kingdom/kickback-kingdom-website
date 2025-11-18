<?php
declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use Kickback\Backend\Views\vItem;
use Kickback\Backend\Views\vMedia;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Models\Response;
use Kickback\Services\Database;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Models\ItemEquipmentSlot;
use Kickback\Backend\Models\Item;
use Kickback\Backend\Models\ItemType;
use Kickback\Backend\Models\ItemRarity;
use Kickback\Backend\Models\ItemCategory;

class ItemController
{
    public static function insertItem(Item $item): Response {
        $conn = Database::getConnection();
    
        $sql = "
            INSERT INTO item (
                type,
                rarity,
                media_id_large,
                media_id_small,
                media_id_back,
                `desc`,
                `name`,
                nominated_by_id,
                collection_id,
                equipable,
                equipment_slot,
                redeemable,
                useable,
                is_container,
                container_size,
                container_item_category,
                item_category,
                is_fungible
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
    
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return new Response(false, "Failed to prepare item insert: " . $conn->error);
        }
    
        $equipmentSlot = $item->equipmentSlot?->value;
        $nominatedById = $item->nominatedBy?->crand;
        $collectionId  = $item->collection?->crand;
        $containerItemCategory = $item->containerItemCategory?->value;
        $itemCategory  = $item->itemCategory?->value;
    
        $typeValue   = $item->type->value;
        $rarityValue = $item->rarity->value;

        $mediaIdBack = $item->mediaLarge->crand;

        if ($item->mediaBack != null)
        {
            $mediaIdBack = $item->mediaBack->crand;
        }
        $stmt->bind_param(
            'iiiiissiiiiiiiiiii',
            $typeValue,
            $rarityValue,
            $item->mediaLarge->crand,
            $item->mediaSmall->crand,
            $mediaIdBack,
            $item->desc,
            $item->name,
            $nominatedById,
            $collectionId,
            $item->equipable,
            $equipmentSlot,
            $item->redeemable,
            $item->useable,
            $item->isContainer,
            $item->containerSize,
            $containerItemCategory,
            $itemCategory,
            (int)$item->isFungible
        );
    
        if (!$stmt->execute()) {
            return new Response(false, "Failed to insert item: " . $stmt->error);
        }
    
        $insertedId = $conn->insert_id;
        $stmt->close();
    
        return new Response(true, "Item inserted successfully.", new vRecordId('', (int)$insertedId));
    }
    

    public static function canItemGoInContainer(vRecordId $itemId, vRecordId $containerItemId): bool {
        $conn = Database::getConnection();
    
        $sql = "
            SELECT 
                i.item_category AS itemCategory, 
                c.container_item_category AS allowedCategory, 
                c.container_size AS size, 
                i.is_container AS isContainer
            FROM item i
            JOIN item c ON c.Id = ?
            WHERE i.Id = ?
        ";
    
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $containerItemId->crand, $itemId->crand);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if (!$result || !$row = $result->fetch_assoc()) {
            return false;
        }
    
        // Category check
        $allowedCategory = (int)($row['allowedCategory'] ?? -1);
        $itemCategory = (int)($row['itemCategory'] ?? -1);
        
        if ($allowedCategory !== 0 && $allowedCategory !== $itemCategory) {
            return false;
        }
    
        return true;
    }
    
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
            
            return (new Response(true, "Item information.",  self::row_to_vItem($row, $item_id) ));
        }
    }

    public static function usePrestigeToken(vRecordId $fromAccountId, vRecordId $toAccountId, bool $commend, string $desc) : Response
    {
        if ($fromAccountId == null) {
            return new Response(false, "Failed to use prestige token because you provided a null fromAccountId.", $fromAccountId);
        }
        if ($toAccountId == null) {
            return new Response(false, "Failed to use prestige token because you provided a null toAccountId.", $toAccountId);
        }
        if ($fromAccountId->crand == $toAccountId->crand) {
            return new Response(false, "You cannot leave a review on yourself.", $toAccountId);
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
    

    public static function row_to_vItem($row, ?vRecordId $itemId = null) : vItem {
        $item = new vItem();
        $item->name = $row["name"];
        $item->description = $row["desc"];
        if ($itemId != null)
        {
            $item->ctime = $itemId->ctime;
            $item->crand = $itemId->crand;
        }

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
        
        if (array_key_exists("back_image",$row) && $row["back_image"] != null)
        {
            
            $backImg = new vMedia();
            $backImg->setMediaPath($row["back_image"]);
            $item->iconBack = $backImg;
        }
        else
        {
            $item->iconBack = $item->iconBig;
        }

        if (array_key_exists("equipable",$row))
        {
            $item->equipable = $row["equipable"] == 1;
        }
        if (array_key_exists("equipment_slot",$row) && $row["equipment_slot"] != null)
        {
            $item->equipmentSlot = ItemEquipmentSlot::fromString($row["equipment_slot"]);
        }


        if (array_key_exists("redeemable", $row)) {
            $item->redeemable = (int)$row["redeemable"] === 1;
        }
    
        if (array_key_exists("useable", $row)) {
            $item->useable = (int)$row["useable"] === 1;
        }
    
        if (array_key_exists("is_container", $row)) {
            $item->isContainer = (int)$row["is_container"] === 1;
        }
    
        if (array_key_exists("container_size", $row)) {
            $item->containerSize = (int)$row["container_size"];
        }
    
        if (array_key_exists("type", $row)) {
            $item->type = ItemType::from((int)$row["type"]);
        }
    
        if (array_key_exists("rarity", $row)) {
            $item->rarity = ItemRarity::from((int)$row["rarity"]);
        }

        if (array_key_exists("container_item_category", $row) && $row["container_item_category"] !== null) {
            $category = (int)$row["container_item_category"];
            $item->containerItemCategory = ItemCategory::tryFrom($category);
        }
    
        if (array_key_exists("item_category", $row) && $row["item_category"] !== null) {
            $category = (int)$row["item_category"];
            $item->itemCategory = ItemCategory::tryFrom($category);
        }

        if(array_key_exists("is_fungible", $row))
        {
            $item->isFungible = $row["is_fungible"];
        }

        return $item;
    }

}
?>
