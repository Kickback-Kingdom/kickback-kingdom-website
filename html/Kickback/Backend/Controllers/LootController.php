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
use Kickback\Services\Session;

class LootController
{
    public static function nicknameLoot(vRecordId $lootId, string $nickname, string $description) : Response {
        $conn = Database::getConnection();

        
        $sql = "UPDATE loot SET nickname = ?, description = ? WHERE account_id = ? AND Id = ?";

        $stmt = mysqli_prepare($conn, $sql);
        
        if (!$stmt) {
            mysqli_close($conn);
            return new Response(false, "Database error: " . mysqli_error($conn), null);
        }
    
        $accountId = Session::getCurrentAccount();

        mysqli_stmt_bind_param($stmt, "ssii", $nickname, $description, $accountId->crand, $lootId->crand);
    
        if (!mysqli_stmt_execute($stmt)) {
            $error = mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
            return new Response(false, "Nicknaming loot failed: " . $error, null);
        }
    
        // Get the number of affected rows *after* execution
        $affectedRows = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);
    
        
        return new Response(true, "Loot nicknamed successfully.");
    }

    public static function transferLootIntoContainer(vRecordId $itemLootId, vRecordId $toContainerId): Response {
        $conn = Database::getConnection();
        $accountId = Session::getCurrentAccount();
    
        // Validate that the destination is a container and is owned by the user
        if (!self::isValidContainer($conn, $toContainerId, $accountId)) {
            return new Response(false, "Invalid destination: It must be a container owned by you.", null);
        }
    
        // Perform the loot transfer
        return self::updateLootContainer($conn, $itemLootId, $toContainerId, $accountId);
    }

    private static function updateLootContainer(\mysqli $conn, vRecordId $itemLootId, vRecordId $toContainerId, vRecordId $accountId) : Response {
        $sql = "UPDATE loot SET container_loot_id = ? WHERE account_id = ? AND Id = ?";
    
        $stmt = mysqli_prepare($conn, $sql);
        
        if (!$stmt) {
            mysqli_close($conn);
            return new Response(false, "Database error: " . mysqli_error($conn), null);
        }
    
        mysqli_stmt_bind_param($stmt, "iii", $toContainerId->crand, $accountId->crand, $itemLootId->crand);
    
        if (!mysqli_stmt_execute($stmt)) {
            $error = mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
            return new Response(false, "Loot transfer failed: " . $error, null);
        }
    
        // Get the number of affected rows *after* execution
        $affectedRows = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);
    
        if ($affectedRows === 1) {
            return new Response(true, "Loot transferred successfully.");
        } elseif ($affectedRows === 0) {
            return new Response(false, "No loot was transferred. Either the item does not exist or is already in the target container.");
        } else {
            return new Response(false, "Unexpected error: multiple rows affected.");
        }
    }
    
    private static function isValidContainer(\mysqli $conn, vRecordId $containerId, vRecordId $accountId): bool {
        $sql = "
            SELECT l.id 
            FROM loot l
            JOIN item i ON l.item_id = i.id
            WHERE l.id = ? 
            AND l.account_id = ? 
            AND i.is_container = 1
        ";
        
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) return false;
    
        mysqli_stmt_bind_param($stmt, "ii", $containerId->crand, $accountId->crand);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
    
        $isValid = mysqli_stmt_num_rows($stmt) > 0;
        mysqli_stmt_close($stmt);
    
        return $isValid;
    }

    public static function getLootStackForContainer(vRecordId $containerLootId, vRecordId $itemId, vRecordId $accountId): Response {
        $conn = Database::getConnection();
    
        $sql = "SELECT * FROM `loot` 
                WHERE container_loot_id = ? 
                  AND item_id = ? 
                  AND opened = 1 
                  AND account_id = ?
                ORDER BY Id ASC";
    
        $stmt = mysqli_prepare($conn, $sql);
    
        if ($stmt === false) {
            return new Response(false, mysqli_error($conn), null);
        }
    
        mysqli_stmt_bind_param($stmt, "iii", $containerLootId->crand, $itemId->crand, $accountId->crand);
        mysqli_stmt_execute($stmt);
    
        $result = $stmt->get_result();
        if (!$result) {
            return new Response(false, "Failed to retrieve loot stack.");
        }
    
        $lootStack = [];
        while ($row = $result->fetch_assoc()) {
            $lootStack[] = self::row_to_vLoot($row, false);
        }
    
        $stmt->close();
    
        return new Response(true, "Loot stack retrieved.", $lootStack);
    }

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

    public static function getLichCardBindersByAccount(vRecordId $accountId) : Response {
        $conn = Database::getConnection();
        
        $sql = "select 
                `v_account_inventory`.`account_id` AS `account_id`, 
                `v_account_inventory`.`item_id` AS `item_id`, 
                `v_account_inventory`.`amount` AS `amount`, 
                `v_account_inventory`.`item_loot_id` AS `item_loot_id`, 
                `v_account_inventory`.`next_loot_id` AS `next_loot_id`, 
                v_account_inventory.container_loot_id,
                v_account_inventory.nickname as nickname,
                v_account_inventory.description as description,
                concat(
                    `large_image`.`Directory`, '/', `large_image`.`Id`, 
                    '.', `large_image`.`extension`
                ) AS `large_image`, 
                concat(
                    `small_image`.`Directory`, '/', `small_image`.`Id`, 
                    '.', `small_image`.`extension`
                ) AS `small_image`, 
                COALESCE(
                    CONCAT(back_image.Directory, '/', back_image.Id, '.', back_image.extension),
                    CONCAT(large_image.Directory, '/', large_image.Id, '.', large_image.extension)
                ) AS back_image,
                `account_artist`.`Username` AS `artist`, 
                `account_nominator`.`Username` AS `nominator`, 
                `large_image`.`DateCreated` AS `DateCreated`, 
                `item`.*
                from 
                v_account_inventory
                JOIN item ON v_account_inventory.item_id = item.Id
                JOIN Media large_image ON item.media_id_large = large_image.Id
                JOIN Media small_image ON item.media_id_small = small_image.Id
                LEFT JOIN Media back_image ON item.media_id_back = back_image.Id
                JOIN account account_artist ON large_image.author_id = account_artist.Id
                LEFT JOIN account account_nominator ON item.nominated_by_id = account_nominator.Id
                WHERE 
                v_account_inventory.account_id = ? and item.item_category = 3;";
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt === false) {
            return new Response(false, mysqli_error($conn), null);
        }
        
        mysqli_stmt_bind_param($stmt, "i", $accountId->crand);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result === false) {
            return new Response(false, mysqli_stmt_error($stmt), null);
        }
        
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        $newsList = array_map([self::class, 'row_to_vItemStack'], $rows);
        return new Response(true, "Account Binders", $newsList);
    }

    public static function getLichCardLootByContainer(vRecordId $containerLootId) : Response {
        $conn = Database::getConnection();
        
        $sql = "select 
                `v_account_inventory`.`account_id` AS `account_id`, 
                `v_account_inventory`.`item_id` AS `item_id`, 
                `v_account_inventory`.`amount` AS `amount`, 
                `v_account_inventory`.`item_loot_id` AS `item_loot_id`, 
                `v_account_inventory`.`next_loot_id` AS `next_loot_id`, 
                v_account_inventory.container_loot_id,
                v_account_inventory.nickname as nickname,
                v_account_inventory.description as description,
                concat(
                    `large_image`.`Directory`, '/', `large_image`.`Id`, 
                    '.', `large_image`.`extension`
                ) AS `large_image`, 
                concat(
                    `small_image`.`Directory`, '/', `small_image`.`Id`, 
                    '.', `small_image`.`extension`
                ) AS `small_image`, 
                COALESCE(
                    CONCAT(back_image.Directory, '/', back_image.Id, '.', back_image.extension),
                    CONCAT(large_image.Directory, '/', large_image.Id, '.', large_image.extension)
                ) AS back_image,
                `account_artist`.`Username` AS `artist`, 
                `account_nominator`.`Username` AS `nominator`, 
                `large_image`.`DateCreated` AS `DateCreated`, 
                `item`.*,
                lc.ctime as _lich_card_ctime,
                lc.crand as _lich_card_crand,
                lc.name as _lich_card_name,
                lc.type as _lich_card_type,
                lc.rarity as _lich_card_rarity,
                lc.description as _lich_card_description,
                lc.font_size_name as _lich_card_font_size_name,
                lc.font_size_type as _lich_card_font_size_type,
                lc.font_size_description as _lich_card_font_size_description,
                lc.stat_health as _lich_card_stat_health,
                lc.stat_intelligence as _lich_card_stat_intelligence,
                lc.stat_defense as _lich_card_stat_defense,
                lc.source_arcanic as _lich_card_source_arcanic,
                lc.source_abyssal as _lich_card_source_abyssal,
                lc.source_thermic as _lich_card_source_thermic,
                lc.source_verdant as _lich_card_source_verdant,
                lc.source_luminate as _lich_card_source_luminate,
                lc.media_id as _lich_card_media_id,
                lc.finished_card_media_id as _lich_card_finished_card_media_id,
                lc.locator as _lich_card_locator,
                lc.published as _lich_card_published,
                lc.being_reviewed as _lich_card_being_reviewed,
                lc.content_id as _lich_card_content_id,
                lc.lich_set_ctime as _lich_card_lich_set_ctime,
                lc.lich_set_crand as _lich_card_lich_set_crand,
                lc.item_id as _lich_card_item_id,
                lc.subtypes as _lich_card_subtypes,
                lc.frontImageURL as _lich_card_frontImageURL

                from 
                v_account_inventory
                JOIN item ON v_account_inventory.item_id = item.Id
                JOIN Media large_image ON item.media_id_large = large_image.Id
                JOIN Media small_image ON item.media_id_small = small_image.Id
                LEFT JOIN Media back_image ON item.media_id_back = back_image.Id
                JOIN account account_artist ON large_image.author_id = account_artist.Id
                LEFT JOIN account account_nominator ON item.nominated_by_id = account_nominator.Id
                left join loot container_loot on container_loot.Id = v_account_inventory.container_loot_id
                left join item container_loot_item on container_loot_item.Id = container_loot.item_id
                left join v_lich_card_item_info lc on lc.item_id = item.Id
                WHERE 
                item.item_category = 1 and (container_loot_item.Id is null or container_loot_item.is_container = 1) and container_loot.Id = ?;";
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt === false) {
            return new Response(false, mysqli_error($conn), null);
        }
        
        mysqli_stmt_bind_param($stmt, "i", $containerLootId->crand);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result === false) {
            return new Response(false, mysqli_stmt_error($stmt), null);
        }
        
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        $newsList = array_map([self::class, 'row_to_vItemStack'], $rows);
        return new Response(true, "Container Items", $newsList);
    }

    public static function getLichCardLootFromBindersByAccount(vRecordId $acountId) : Response {
        $conn = Database::getConnection();
        
        $sql = "select 
                `v_account_inventory`.`account_id` AS `account_id`, 
                `v_account_inventory`.`item_id` AS `item_id`, 
                `v_account_inventory`.`amount` AS `amount`, 
                `v_account_inventory`.`item_loot_id` AS `item_loot_id`, 
                `v_account_inventory`.`next_loot_id` AS `next_loot_id`, 
                v_account_inventory.container_loot_id,
                v_account_inventory.nickname as nickname,
                v_account_inventory.description as description,
                concat(
                    `large_image`.`Directory`, '/', `large_image`.`Id`, 
                    '.', `large_image`.`extension`
                ) AS `large_image`, 
                concat(
                    `small_image`.`Directory`, '/', `small_image`.`Id`, 
                    '.', `small_image`.`extension`
                ) AS `small_image`, 
                COALESCE(
                    CONCAT(back_image.Directory, '/', back_image.Id, '.', back_image.extension),
                    CONCAT(large_image.Directory, '/', large_image.Id, '.', large_image.extension)
                ) AS back_image,
                `account_artist`.`Username` AS `artist`, 
                `account_nominator`.`Username` AS `nominator`, 
                `large_image`.`DateCreated` AS `DateCreated`, 
                `item`.*,
                lc.ctime as _lich_card_ctime,
                lc.crand as _lich_card_crand,
                lc.name as _lich_card_name,
                lc.type as _lich_card_type,
                lc.rarity as _lich_card_rarity,
                lc.description as _lich_card_description,
                lc.font_size_name as _lich_card_font_size_name,
                lc.font_size_type as _lich_card_font_size_type,
                lc.font_size_description as _lich_card_font_size_description,
                lc.stat_health as _lich_card_stat_health,
                lc.stat_intelligence as _lich_card_stat_intelligence,
                lc.stat_defense as _lich_card_stat_defense,
                lc.source_arcanic as _lich_card_source_arcanic,
                lc.source_abyssal as _lich_card_source_abyssal,
                lc.source_thermic as _lich_card_source_thermic,
                lc.source_verdant as _lich_card_source_verdant,
                lc.source_luminate as _lich_card_source_luminate,
                lc.media_id as _lich_card_media_id,
                lc.finished_card_media_id as _lich_card_finished_card_media_id,
                lc.locator as _lich_card_locator,
                lc.published as _lich_card_published,
                lc.being_reviewed as _lich_card_being_reviewed,
                lc.content_id as _lich_card_content_id,
                lc.lich_set_ctime as _lich_card_lich_set_ctime,
                lc.lich_set_crand as _lich_card_lich_set_crand,
                lc.item_id as _lich_card_item_id,
                lc.subtypes as _lich_card_subtypes,
                lc.frontImageURL as _lich_card_frontImageURL

                from 
                v_account_inventory
                JOIN item ON v_account_inventory.item_id = item.Id
                JOIN Media large_image ON item.media_id_large = large_image.Id
                JOIN Media small_image ON item.media_id_small = small_image.Id
                LEFT JOIN Media back_image ON item.media_id_back = back_image.Id
                JOIN account account_artist ON large_image.author_id = account_artist.Id
                LEFT JOIN account account_nominator ON item.nominated_by_id = account_nominator.Id
                left join loot container_loot on container_loot.Id = v_account_inventory.container_loot_id
                left join item container_loot_item on container_loot_item.Id = container_loot.item_id
                left join v_lich_card_item_info lc on lc.item_id = item.Id
                WHERE 
                item.item_category = 1 and (container_loot_item.Id is null or container_loot_item.item_category = 3) and v_account_inventory.account_id = ?;";
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt === false) {
            return new Response(false, mysqli_error($conn), null);
        }
        
        mysqli_stmt_bind_param($stmt, "i", $acountId->crand);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result === false) {
            return new Response(false, mysqli_stmt_error($stmt), null);
        }
        
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        $newsList = array_map([self::class, 'row_to_vItemStack'], $rows);
        return new Response(true, "Container Items", $newsList);
    }

    public static function getLootByContainerLootId(vRecordId $containerLootId) : Response {
        
        $conn = Database::getConnection();
        
        $sql = "select 
                `v_account_inventory`.`account_id` AS `account_id`, 
                `v_account_inventory`.`item_id` AS `item_id`, 
                `v_account_inventory`.`amount` AS `amount`, 
                `v_account_inventory`.`item_loot_id` AS `item_loot_id`, 
                `v_account_inventory`.`next_loot_id` AS `next_loot_id`, 
                v_account_inventory.container_loot_id,
                v_account_inventory.nickname as nickname,
                v_account_inventory.description as description,
                concat(
                    `large_image`.`Directory`, '/', `large_image`.`Id`, 
                    '.', `large_image`.`extension`
                ) AS `large_image`, 
                concat(
                    `small_image`.`Directory`, '/', `small_image`.`Id`, 
                    '.', `small_image`.`extension`
                ) AS `small_image`, 
                COALESCE(
                    CONCAT(back_image.Directory, '/', back_image.Id, '.', back_image.extension),
                    CONCAT(large_image.Directory, '/', large_image.Id, '.', large_image.extension)
                ) AS back_image,
                `account_artist`.`Username` AS `artist`, 
                `account_nominator`.`Username` AS `nominator`, 
                `large_image`.`DateCreated` AS `DateCreated`, 
                `item`.*
                from 
                v_account_inventory
                JOIN item ON v_account_inventory.item_id = item.Id
                JOIN Media large_image ON item.media_id_large = large_image.Id
                JOIN Media small_image ON item.media_id_small = small_image.Id
                LEFT JOIN Media back_image ON item.media_id_back = back_image.Id
                JOIN account account_artist ON large_image.author_id = account_artist.Id
                LEFT JOIN account account_nominator ON item.nominated_by_id = account_nominator.Id
                WHERE 
                v_account_inventory.container_loot_id = ?;";
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt === false) {
            return new Response(false, mysqli_error($conn), null);
        }
        
        mysqli_stmt_bind_param($stmt, "i", $containerLootId->crand);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result === false) {
            return new Response(false, mysqli_stmt_error($stmt), null);
        }
        
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        $newsList = array_map([self::class, 'row_to_vItemStack'], $rows);
        return new Response(true, "Container Items", $newsList);
    }

    public static function getLootByAccountId(vRecordId $recordId) : Response {
        
        $conn = Database::getConnection();
        
        $sql = "select 
                `v_account_inventory`.`account_id` AS `account_id`, 
                `v_account_inventory`.`item_id` AS `item_id`, 
                `v_account_inventory`.`amount` AS `amount`, 
                `v_account_inventory`.`item_loot_id` AS `item_loot_id`, 
                `v_account_inventory`.`next_loot_id` AS `next_loot_id`, 
                v_account_inventory.container_loot_id,
                v_account_inventory.nickname as nickname,
                v_account_inventory.description as description,
                concat(
                    `large_image`.`Directory`, '/', `large_image`.`Id`, 
                    '.', `large_image`.`extension`
                ) AS `large_image`, 
                concat(
                    `small_image`.`Directory`, '/', `small_image`.`Id`, 
                    '.', `small_image`.`extension`
                ) AS `small_image`, 
                COALESCE(
                    CONCAT(back_image.Directory, '/', back_image.Id, '.', back_image.extension),
                    CONCAT(large_image.Directory, '/', large_image.Id, '.', large_image.extension)
                ) AS back_image,
                `account_artist`.`Username` AS `artist`, 
                `account_nominator`.`Username` AS `nominator`, 
                `large_image`.`DateCreated` AS `DateCreated`, 
                `item`.*
                from 
                v_account_inventory
                JOIN item ON v_account_inventory.item_id = item.Id
                JOIN Media large_image ON item.media_id_large = large_image.Id
                JOIN Media small_image ON item.media_id_small = small_image.Id
                LEFT JOIN Media back_image ON item.media_id_back = back_image.Id
                JOIN account account_artist ON large_image.author_id = account_artist.Id
                LEFT JOIN account account_nominator ON item.nominated_by_id = account_nominator.Id
                WHERE 
                v_account_inventory.account_id = ? and v_account_inventory.container_loot_id is null;";
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

    public static function getLootById(vRecordId $lootId) : Response {
        
        $conn = Database::getConnection();
        
        $sql = "select 
                `v_account_inventory`.`account_id` AS `account_id`, 
                `v_account_inventory`.`item_id` AS `item_id`, 
                `v_account_inventory`.`amount` AS `amount`, 
                `v_account_inventory`.`item_loot_id` AS `item_loot_id`, 
                `v_account_inventory`.`next_loot_id` AS `next_loot_id`, 
                v_account_inventory.container_loot_id,
                v_account_inventory.nickname as nickname,
                v_account_inventory.description as description,
                concat(
                    `large_image`.`Directory`, '/', `large_image`.`Id`, 
                    '.', `large_image`.`extension`
                ) AS `large_image`, 
                concat(
                    `small_image`.`Directory`, '/', `small_image`.`Id`, 
                    '.', `small_image`.`extension`
                ) AS `small_image`, 
                COALESCE(
                    CONCAT(back_image.Directory, '/', back_image.Id, '.', back_image.extension),
                    CONCAT(large_image.Directory, '/', large_image.Id, '.', large_image.extension)
                ) AS back_image,
                `account_artist`.`Username` AS `artist`, 
                `account_nominator`.`Username` AS `nominator`, 
                `large_image`.`DateCreated` AS `DateCreated`, 
                `item`.*
                from 
                v_account_inventory
                JOIN item ON v_account_inventory.item_id = item.Id
                JOIN Media large_image ON item.media_id_large = large_image.Id
                JOIN Media small_image ON item.media_id_small = small_image.Id
                LEFT JOIN Media back_image ON item.media_id_back = back_image.Id
                JOIN account account_artist ON large_image.author_id = account_artist.Id
                LEFT JOIN account account_nominator ON item.nominated_by_id = account_nominator.Id
                WHERE 
                v_account_inventory.item_loot_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt === false) {
            return new Response(false, mysqli_error($conn), null);
        }
        
        mysqli_stmt_bind_param($stmt, "i", $lootId->crand);
        mysqli_stmt_execute($stmt);
        
        $result = $stmt->get_result();
        if (!$result) {
            return new Response(false, "Failed to retrieve the result set.");
        }

        $itemStack = null;
        $row = $result->fetch_assoc();
        if (isset($row) && $row !== false) {
            $itemStack = self::row_to_vItemStack($row);
        }

        $stmt->close();

        if ($itemStack === null) {
            return new Response(false, "Lich Set not found.");
        }
        
        return new Response(true, "Loot", $itemStack);
    }

    public static function givePrestigeToken(vRecordId $account_id) : Response {
        return self::giveLoot($account_id, 3);
    }

    public static function giveBadge(vRecordId $account_id, vRecordId $item_id) : Response {
        return self::giveLoot($account_id, $item_id);
    }

    public static function giveRaffleTicket(vRecordId $account_id) : Response {
        return self::giveLoot($account_id, 4);
    }

    public static function giveWritOfPassage(vRecordId $account_id) : Response {
        return self::giveLoot($account_id, new vRecordId('', 14));
    }

    public static function giveMerchantGuildShare(vRecordId $account_id, vDateTime $date) : Response {
        return self::giveLoot($account_id, new vRecordId('', 16), $date);
    }

    public static function giveLoot(vRecordId $account_id,vRecordId $item_id, ?vDateTime $dateObtained = null, ?\mysqli $conn = null) : Response
    {
        if ($conn === null) {
            $conn = Database::getConnection();
        }
    

        // If no date given, use current UTC datetime
        if ($dateObtained === null) {
            $dateObtained = vDateTime::now();

        }

        // Make sure we are binding a string (in UTC database format)
        $dateString = $dateObtained->dbValue;

        // Prepare the SQL statement
        $sql = "INSERT INTO loot (item_id, opened, account_id, dateObtained) VALUES (?, 0, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt === false) {
            return (new Response(false, "Failed to prepare SQL statement!", null));
        }

        // Bind parameters
        mysqli_stmt_bind_param($stmt, 'iis', $item_id->crand, $account_id->crand, $dateString);

        // Execute the statement
        $result = mysqli_stmt_execute($stmt);

        // Close the statement
        mysqli_stmt_close($stmt);

        if ($result) {
            return (new Response(true, "Successfully gave loot to account", null));
        } else {
            return (new Response(false, "Failed to award account", null));
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
    
    public static function row_to_vLoot(array $row, bool $populateItem = true) : vLoot {
        
        $crand = (int)($row["Id"] ?? -1);
        $ctime = $row["ctime"] ?? '';



        $loot = new vLoot($ctime, $crand);
        $loot->opened = (bool)($row["opened"] ?? false);
        $loot->ownerId = new vRecordId('', (int)($row["account_id"] ?? -1));

        if (!empty($row["quest_id"])) {
            $quest = new vQuest('', (int)$row["quest_id"]);
            $quest->title = $row["quest_name"] ?? '';
            $quest->locator = $row["quest_locator"] ?? '';
            $loot->quest = $quest;
        }
        
        $loot->dateObtained = vDateTime::fromDB($row["dateObtained"] ?? '');

        if ($populateItem)
            $loot->item = ItemController::row_to_vItem($row);

        if (array_key_exists('container_loot_id', $row) && isset($row["container_loot_id"])) {
            $loot->containerLoot = new vLoot('', intval($row["container_loot_id"]));
        } else {
            $loot->containerLoot = null;
        }

        $loot->nickname = $row["nickname"] ?? '';
        $loot->description = $row["description"] ?? '';
    
        return $loot;
    }

    public static function row_to_vItemStack(array $row) : vItemStack {
        $lootStack = new vItemStack();
        $lootStack->item = ItemController::row_to_vItem($row);
        $lootStack->isContainer = (bool)$row["is_container"];


        $lootStack->nickname = $row["nickname"] ?? "";
        $lootStack->description = $row["description"] ?? "";

        if (array_key_exists("_lich_card_name",$row))
        {

            $lichRow = self::convertToLichRow($row);
            $lootStack->item->auxData["lichCard"] = LichCardController::row_to_vLichCard($lichRow); 
        }
        
        if (array_key_exists("amount",$row) && $row["amount"] != null)
        {
            $lootStack->amount = (int)$row["amount"];
        }
        else
        {
            $lootStack->amount = 0;
        }
        if (array_key_exists("next_loot_id",$row))
        {
            if ($row["next_loot_id"] != null)
            {
                $lootStack->nextLootId = new vRecordId('', (int)$row["next_loot_id"]);
            }
            else
            {
                $lootStack->nextLootId = null;
            }
        }
        
        if (array_key_exists("item_loot_id",$row) && $row["item_loot_id"] != null)
        {
            $lootStack->itemLootId = new vRecordId('', (int)$row["item_loot_id"]);
        }else{
            throw new \Exception("Item loot id was not pulled.");
        }

        if (array_key_exists("container_loot_id",$row))
        {
            if ($row["container_loot_id"] != null)
            {
                $lootStack->containerLootId = new vRecordId('', (int)$row["container_loot_id"]);
            }
            else
            {
                $lootStack->containerLootId = null;
            }
        }
        if (array_key_exists("account_id",$row) && $row["account_id"] != null)
        {
            $lootStack->ownerId = new vRecordId('', (int)$row["account_id"]);
        }

        if ($lootStack->containerLootId != null)
        {
            $lootStack->lootStack = self::getLootStackForContainer($lootStack->containerLootId, $lootStack->item, $lootStack->ownerId)->data;
        }

        return $lootStack;
    }

    public static function countWropUsedByNewAccounts(int $accountId): int
    {
        $conn = Database::getConnection();

        $sql = "SELECT COUNT(*)
                FROM loot
                JOIN account ON account.passage_id = loot.Id
                WHERE loot.item_id = 14
                AND loot.account_id = ?
                AND account.Id != loot.account_id";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return 0;
        }

        $stmt->bind_param("i", $accountId);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        return (int)$count;
    }
    public static function countWropUsedByNewAccountsBetween(int $accountId, string $startDate, string $endDate): int
    {
        $conn = Database::getConnection();
    
        $sql = "SELECT COUNT(*)
                FROM loot
                JOIN account ON account.passage_id = loot.Id
                WHERE loot.item_id = 14
                  AND loot.account_id = ?
                  AND account.Id != loot.account_id
                  AND account.DateCreated BETWEEN ? AND ?";
    
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return 0;
        }
    
        $stmt->bind_param("iss", $accountId, $startDate, $endDate);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
    
        return (int)$count;
    }
    

    private static function convertToLichRow(array $row): array {
        return [
            'ctime' => $row['_lich_card_ctime'] ?? '',
            'crand' => $row['_lich_card_crand'] ?? -1,
            'name' => $row['_lich_card_name'] ?? '',
            'type' => $row['_lich_card_type'] ?? 0,
            'rarity' => $row['_lich_card_rarity'] ?? 0,
            'description' => $row['_lich_card_description'] ?? '',
            'nameFontSize' => $row['_lich_card_font_size_name'] ?? 1.0,
            'typeFontSize' => $row['_lich_card_font_size_type'] ?? 1.0,
            'descriptionFontSize' => $row['_lich_card_font_size_description'] ?? 1.0,
            'health' => $row['_lich_card_stat_health'] ?? 0,
            'intelligence' => $row['_lich_card_stat_intelligence'] ?? 0,
            'defense' => $row['_lich_card_stat_defense'] ?? 0,
            'arcanic' => $row['_lich_card_source_arcanic'] ?? 0,
            'abyssal' => $row['_lich_card_source_abyssal'] ?? 0,
            'thermic' => $row['_lich_card_source_thermic'] ?? 0,
            'verdant' => $row['_lich_card_source_verdant'] ?? 0,
            'luminate' => $row['_lich_card_source_luminate'] ?? 0,
            'mediaId' => $row['_lich_card_media_id'] ?? null,
            'finishedMediaId' => $row['_lich_card_finished_card_media_id'] ?? null,
            'cardImagePath' => $row['_lich_card_frontImageURL'] ?? '',
            'locator' => $row['_lich_card_locator'] ?? '',
            'published' => $row['_lich_card_published'] ?? false,
            'being_reviewed' => $row['_lich_card_being_reviewed'] ?? false,
            'content_id' => $row['_lich_card_content_id'] ?? -1,
            'lich_set_ctime' => $row['_lich_card_lich_set_ctime'] ?? '',
            'lich_set_crand' => $row['_lich_card_lich_set_crand'] ?? 0,
            'lich_set_name' => $row['_lich_card_lich_set_name'] ?? '',
            'lich_set_locator' => $row['_lich_card_lich_set_locator'] ?? '',
            'item_id' => $row['_lich_card_item_id'] ?? null,
            'types' => JSON_DECODE($row['_lich_card_subtypes'] ?? '[]'),
            'artPath' => $row['_lich_card_frontImageURL'] ?? '',
        ];
    }

}
?>
