<?php
declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vTreasureHuntEvent;
use Kickback\Backend\Views\vTreasureHuntObject;
use Kickback\Backend\Views\vMedia;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vDateTime;
use Kickback\Backend\Views\vContent;
use Kickback\Backend\Models\RecordId;
use Kickback\Services\Database;
use Kickback\Services\Session;

class TreasureHuntController
{
    public static function getPossibleTreasureItems(): Response
    {
        $conn = Database::getConnection();

        // Hard-coded list of allowed item IDs (feel free to expand)
        $allowedItemIds = [4, 5, 14, 115, 124];

        // Build parameter placeholders
        $placeholders = implode(',', array_fill(0, count($allowedItemIds), '?'));

        // Prepare SQL
        $sql = "SELECT i.Id, i.name
                FROM item i
                WHERE i.Id IN ($placeholders)";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return new Response(false, "Failed to prepare statement: " . $conn->error, null);
        }

        // Bind parameters dynamically
        $types = str_repeat('i', count($allowedItemIds));
        $stmt->bind_param($types, ...$allowedItemIds);
        $stmt->execute();
        $result = $stmt->get_result();

        if (!$result) {
            return new Response(false, "Failed to fetch possible items", []);
        }

        // Format as objects
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $item = new \stdClass();
            $item->crand = (int) $row['Id'];
            $item->ctime = '';
            $item->name = $row['name'];
            $items[] = $item;
        }

        return new Response(true, "Possible items retrieved", $items);
    }


    public static function deleteObject(vRecordId $objectId): Response
    {
        if (!Session::isEventOrganizer()) {
            return new Response(false, "You do not have permission to delete objects.");
        }

        $conn = Database::getConnection();

        // Optional: Check if object exists
        $checkSql = "SELECT 1 FROM treasure_hunt_objects WHERE ctime = ? AND crand = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("si", $objectId->ctime, $objectId->crand);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows === 0) {
            return new Response(false, "Treasure object not found.", null);
        }

        // Delete object
        $deleteSql = "DELETE FROM treasure_hunt_objects WHERE ctime = ? AND crand = ?";
        $deleteStmt = $conn->prepare($deleteSql);
        $deleteStmt->bind_param("si", $objectId->ctime, $objectId->crand);

        if (!$deleteStmt->execute()) {
            return new Response(false, "Failed to delete object: " . $deleteStmt->error);
        }

        return new Response(true, "Object successfully deleted.");
    }


    public static function hideTreasureObject(
        vRecordId $eventId,
        vRecordId $itemId,
        string $pageUrl,
        float $xPercent,
        float $yPercent,
        vRecordId $mediaId,
        bool $oneTimeOnly = false
    ): Response {
        if (!Session::isEventOrganizer()) {
            return new Response(false, "You do not have permission to hide treasures.", null);
        }
    
        $conn = Database::getConnection();
    
        $model = new vTreasureHuntObject();
        $model->ctime = RecordId::getCTime();
        $model->crand = RecordId::generateCRand();
    
        $sql = "INSERT INTO treasure_hunt_objects (
                    ctime, crand,
                    ref_event_ctime, ref_event_crand,
                    item_id, page_url,
                    x_percentage, y_percentage,
                    one_time_only, media_id_object
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return new Response(false, "Failed to prepare insert statement: " . $conn->error, null);
        }
    
        while (true) {
            $oneTimeInt = $oneTimeOnly ? 1 : 0;
    
            $stmt->bind_param(
                'sisiisddii',
                $model->ctime,
                $model->crand,
                $eventId->ctime,
                $eventId->crand,
                $itemId->crand,
                $pageUrl,
                $xPercent,
                $yPercent,
                $oneTimeInt,
                $mediaId->crand
            );
    
            if (!$stmt->execute()) {
                if ($stmt->errno === 1062) {
                    // Retry on duplicate crand
                    $model->crand = \Kickback\Backend\Models\RecordId::generateCRand();
                    continue;
                }
    
                return new Response(false, "Failed to insert hidden object: " . $stmt->error, null);
            }
    
            break;
        }
    
        return new Response(true, "Object successfully hidden!", new vRecordId($model->ctime, $model->crand));
    }

    
    /**
     * Insert a new Treasure Hunt event.
     */
    public static function insertNewTreasureHuntEvent() : Response
    {
        if (!Session::isEventOrganizer()) { // Adjust permission check as needed
            return new Response(false, "You do not have permissions to create a Treasure Hunt event.", null);
        }

        $eventName = "New Treasure Hunt Event";
        $eventLocator = "new-treasure-hunt-" . Session::getCurrentAccount()->crand;

        if (!self::queryEventByLocatorInto($eventLocator,$event))
        {
            // Event didn't exist; make a new one
            $insertResp = self::createTreasureHuntEvent(
                $eventName,
                "A new exciting treasure hunt awaits!",
                date('Y-m-d H:i:s'),  // Start now
                date('Y-m-d H:i:s', strtotime('+7 days')) // Ends in 7 days
            );
            $event = $insertResp->data;
        }

        // Ensure event has associated content
        if (!$event->hasPageContent()) {
            $newContentId = ContentController::insertNewContent();
            self::updateEventContent($event, new vRecordId('', $newContentId));
            self::queryEventByLocatorInto($eventLocator,$event);
        }

        return new Response(true, "New Treasure Hunt event created.", $event);
    }

    /**
    * @phpstan-assert-if-true vTreasureHuntEvent $event
    */
    public static function queryEventByLocatorInto(string $locator, ?vTreasureHuntEvent &$event): bool
    {
        $resp = self::queryEventByLocatorAsResponse($locator);
        if ( $resp->success ) {
            $event = $resp->data;
            return true;
        } else {
            $event = null;
            return false;
        }
    }

    /**
    * Get a Treasure Hunt Event by its locator.
    */
    public static function queryEventByLocatorAsResponse(string $locator): Response
    {
        $conn = Database::getConnection();

        $sql = "SELECT 
                    e.ctime, e.crand, e.name, e.`desc`, e.start_date, e.end_date, e.locator, e.content_id,
                    mIcon.Directory AS icon_directory, mIcon.Id AS icon_id, mIcon.extension AS icon_extension,
                    mBanner.Directory AS banner_directory, mBanner.Id AS banner_id, mBanner.extension AS banner_extension,
                    mBannerMobile.Directory AS banner_mobile_directory, mBannerMobile.Id AS banner_mobile_id, mBannerMobile.extension AS banner_mobile_extension,
                    mDateCard.Directory AS banner_date_directory, mDateCard.Id AS banner_date_id, mDateCard.extension AS banner_date_extension,
                    mProgressCard.Directory AS banner_progress_directory, mProgressCard.Id AS banner_progress_id, mProgressCard.extension AS banner_progress_extension
                FROM treasure_hunt_event e
                LEFT JOIN Media mIcon ON e.media_id_icon = mIcon.Id
                LEFT JOIN Media mBanner ON e.media_id_banner_desktop = mBanner.Id
                LEFT JOIN Media mBannerMobile ON e.media_id_banner_mobile = mBannerMobile.Id
                LEFT JOIN Media mDateCard ON e.media_id_banner_date_card = mDateCard.Id
                LEFT JOIN Media mProgressCard ON e.media_id_banner_progress_card = mProgressCard.Id
                WHERE e.locator = ?
                LIMIT 1";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return new Response(false, "Failed to prepare statement: " . $conn->error, null);
        }

        $stmt->bind_param("s", $locator);
        $stmt->execute();
        $result = $stmt->get_result();

        if (!$result || $result->num_rows === 0) {
            return new Response(false, "Event not found.", null);
        }

        $event = self::row_to_vTreasureHuntEvent($result->fetch_assoc());
        return new Response(true, "Treasure Hunt Event found.", $event);
    }



    /**
     * Update the content_id reference for a Treasure Hunt Event.
     */
    public static function updateEventContent(vRecordId $eventId, vRecordId $contentId): Response
    {
        $conn = Database::getConnection();
        
        // Prepare the SQL statement to update content_id
        $stmt = $conn->prepare("UPDATE treasure_hunt_event SET content_id = ? WHERE ctime = ? AND crand = ?");
        if (!$stmt) {
            return new Response(false, "Failed to prepare the SQL statement: " . $conn->error, null);
        }

        // Bind parameters
        $stmt->bind_param('isi', $contentId->crand, $eventId->ctime, $eventId->crand);

        // Execute the update
        if (!$stmt->execute()) {
            return new Response(false, "Failed to execute the SQL statement: " . $stmt->error, null);
        }

        // Close the statement
        $stmt->close();

        return new Response(true, "Treasure Hunt Event content updated successfully.", null);
    }



    /**
     * Mark a hidden object as found and give the user the corresponding loot.
     * If loot cannot be given, the collection entry is rolled back.
     */
    public static function markObjectAsFound(vRecordId $object): Response
    {
        if (!Session::isLoggedIn()) {
            return new Response(false, "User must be logged in to collect objects.", null);
        }

        if (Session::isEventOrganizer())
        {
            //return new Response(false, "It is working! but you are an event organizer and cannot collect treasure!");
        }

        $conn = Database::getConnection();
        $userId = Session::getCurrentAccount()->crand;

        try {
            $conn->begin_transaction();

            // Step 1: Validate object and check collection count
            $sql = "SELECT o.item_id, o.one_time_only,
                        (SELECT COUNT(*) FROM treasure_hunt_collections 
                            WHERE ref_object_ctime = o.ctime AND ref_object_crand = o.crand) AS collected_count
                    FROM treasure_hunt_objects o
                    WHERE o.ctime = ? AND o.crand = ?";

            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new \Exception("Failed to prepare validation statement: " . $conn->error);
            }

            $stmt->bind_param("si", $object->ctime, $object->crand);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result === false || $result->num_rows === 0) {
                throw new \Exception("Treasure hunt object not found.");
            }

            $row = $result->fetch_assoc();
            if (!isset($row)) {
                throw new \Exception("SQL client fetch_assoc() function failed");
            }

            $itemId = (int)$row["item_id"];
            $oneTimeOnly = (bool)$row["one_time_only"];
            $collectedCount = (int)$row["collected_count"];

            if ($oneTimeOnly && $collectedCount > 0) {
                throw new \Exception("This object has already been collected and cannot be collected again.");
            }

            // Step 2: Ensure user hasn't already collected
            $checkSql = "SELECT 1 FROM treasure_hunt_collections 
                        WHERE account_id = ? AND ref_object_ctime = ? AND ref_object_crand = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("isi", $userId, $object->ctime, $object->crand);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();

            if ($checkResult !== false && $checkResult->num_rows > 0) {
                throw new \Exception("You have already collected this object.");
            }

            // Step 3: Insert collection record with generated ID
            $insertSql = "INSERT INTO treasure_hunt_collections 
                            (ctime, crand, account_id, ref_object_ctime, ref_object_crand) 
                        VALUES (?, ?, ?, ?, ?)";
            $insertStmt = $conn->prepare($insertSql);
            if (!$insertStmt) {
                throw new \Exception("Failed to prepare insert statement: " . $conn->error);
            }

            while (true) {
                $ctime = RecordId::getCTime();
                $crand = RecordId::generateCRand();

                $insertStmt->bind_param("siisi", $ctime, $crand, $userId, $object->ctime, $object->crand);
                $insertStmt->execute();

                if ($insertStmt->errno === 1062) {
                    continue; // Retry on collision
                } elseif (0 < $insertStmt->errno) {
                    throw new \Exception("Insert error: " . $insertStmt->error);
                }

                break;
            }

            // Step 4: Grant loot
            $lootResp = LootController::giveLoot(
                new vRecordId('', $userId),
                new vRecordId('', $itemId)
            );

            if (!$lootResp->success) {
                throw new \Exception("Loot failed to be granted: " . $lootResp->message);
            }

            $conn->commit();
            return new Response(true, "Object collected successfully and loot granted!", null);

        } catch (\Exception $e) {
            $conn->rollback();
            return new Response(false, $e->getMessage() . " [Trace] " . $e->getTraceAsString(), $object);

        }
    }




    /**
     * Get all hidden objects for a specific treasure hunt event
     */
    public static function getAllHiddenObjectsForEvent(vRecordId $event): Response
    {
        $conn = Database::getConnection();
        $userId = Session::isLoggedIn() ? Session::getCurrentAccount()->crand : null;

        $sql = "SELECT o.ctime, o.crand, o.item_id, o.x_percentage, o.y_percentage, 
                    o.one_time_only, i.name, i.media_id_small, 
                    CONCAT(m_small.Directory, '/', m_small.Id, '.', m_small.extension) AS small_image,
                    CONCAT(m_small_i.Directory, '/', m_small_i.Id, '.', m_small_i.extension) AS item_image,
                    m_small.author_id AS media_author_id, a.Username AS media_author, the.locator as locator,
                    CASE 
                        WHEN o.one_time_only = 1 AND EXISTS (
                            SELECT 1 FROM treasure_hunt_collections cc 
                            WHERE cc.ref_object_ctime = o.ctime 
                            AND cc.ref_object_crand = o.crand
                        ) THEN 1
                        WHEN ? IS NOT NULL AND EXISTS (
                            SELECT 1 FROM treasure_hunt_collections c2
                            WHERE c2.ref_object_ctime = o.ctime
                            AND c2.ref_object_crand = o.crand
                            AND c2.account_id = ?
                        ) THEN 1
                        ELSE 0
                    END AS found,
                    
                    CASE 
                        WHEN ? IS NOT NULL AND EXISTS (
                            SELECT 1 FROM treasure_hunt_collections c3
                            WHERE c3.ref_object_ctime = o.ctime
                            AND c3.ref_object_crand = o.crand
                            AND c3.account_id = ?
                        ) THEN 1
                        ELSE 0
                    END AS foundByMe
                FROM treasure_hunt_objects o
                JOIN item i ON o.item_id = i.Id
                join treasure_hunt_event the on the.ctime = o.ref_event_ctime and the.crand = o.ref_event_crand
                LEFT JOIN Media m_small ON o.media_id_object = m_small.Id
                LEFT JOIN Media m_small_i ON i.media_id_small = m_small_i.Id
                LEFT JOIN account a ON m_small.author_id = a.Id
                WHERE o.ref_event_ctime = ? 
                AND o.ref_event_crand = ?";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return new Response(false, "Failed to prepare statement", null);
        }

        // Use the same value twice for the userId check
        $stmt->bind_param("iiiisi", $userId, $userId, $userId, $userId, $event->ctime, $event->crand);

        $stmt->execute();
        $result = $stmt->get_result();

        if (!$result) {
            return new Response(false, "Failed to fetch event objects", []);
        }

        $objects = array_map([self::class, 'row_to_vTreasureHuntObject'], $result->fetch_all(MYSQLI_ASSOC));
        return new Response(true, "Hidden objects in event", $objects);
    }



    /**
     * Get all active treasure hunt events as objects, including media.
     */
    public static function getCurrentEvents(): Response
    {
        $conn = Database::getConnection();
        $sql = "SELECT e.ctime, e.crand, e.name, e.`desc`, e.start_date, e.end_date, e.locator, e.content_id,
                    mIcon.Directory AS icon_directory, mIcon.Id AS icon_id, mIcon.extension AS icon_extension,
                    mBanner.Directory AS banner_directory, mBanner.Id AS banner_id, mBanner.extension AS banner_extension,
                    mBannerMobile.Directory AS banner_mobile_directory, mBannerMobile.Id AS banner_mobile_id, mBannerMobile.extension AS banner_mobile_extension
                FROM treasure_hunt_event e
                LEFT JOIN Media mIcon ON e.media_id_icon = mIcon.Id
                LEFT JOIN Media mBanner ON e.media_id_banner_desktop = mBanner.Id
                LEFT JOIN Media mBannerMobile ON e.media_id_banner_mobile = mBannerMobile.Id
                WHERE e.start_date <= NOW() AND e.end_date >= NOW()";

        $result = $conn->query($sql);
        if (!$result) {
            return new Response(false, "Failed to fetch events", []);
        }

        $events = array_map([self::class, 'row_to_vTreasureHuntEvent'], $result->fetch_all(MYSQLI_ASSOC));
        return new Response(true, "Active events", $events);
    }

    /**
    * @return array<vTreasureHuntEvent>
    */
    public static function queryCurrentEventsAndUpcoming(): array
    {
        $resp = self::queryCurrentEventsAndUpcomingAsResponse();
        if ($resp->success) {
            // @phpstan-ignore return.type
            return $resp->data;
        } else {
            throw new \Exception($resp->message);
        }
    }

    public static function queryCurrentEventsAndUpcomingAsResponse(): Response
    {
        $conn = Database::getConnection();
        
        $sql = "SELECT e.ctime, e.crand, e.name, e.`desc`, e.start_date, e.end_date, e.locator, e.content_id,
                    mIcon.Directory AS icon_directory, mIcon.Id AS icon_id, mIcon.extension AS icon_extension,
                    mBanner.Directory AS banner_directory, mBanner.Id AS banner_id, mBanner.extension AS banner_extension,
                    mBannerMobile.Directory AS banner_mobile_directory, mBannerMobile.Id AS banner_mobile_id, mBannerMobile.extension AS banner_mobile_extension
                FROM treasure_hunt_event e
                LEFT JOIN Media mIcon ON e.media_id_icon = mIcon.Id
                LEFT JOIN Media mBanner ON e.media_id_banner_desktop = mBanner.Id
                LEFT JOIN Media mBannerMobile ON e.media_id_banner_mobile = mBannerMobile.Id
                WHERE (e.start_date <= NOW() AND e.end_date >= NOW()) 
                OR (e.start_date > NOW() AND e.start_date <= DATE_ADD(NOW(), INTERVAL 14 DAY))
                ORDER BY e.start_date ASC";

        $result = $conn->query($sql);
        if (!$result) {
            return new Response(false, "Failed to fetch events", []);
        }

        $events = array_map([self::class, 'row_to_vTreasureHuntEvent'], $result->fetch_all(MYSQLI_ASSOC));
        return new Response(true, "Current and upcoming events", $events);
    }

    /**
     * Get hidden objects for a given page URL, excluding collected objects.
     * If an object is marked `one_time_only = 1`, it will be hidden if anyone has collected it.
     */
    public static function getHiddenObjectsOnPage(string $pageUrl): Response
    {
        $conn = Database::getConnection();
        $userId = Session::isLoggedIn() ? Session::getCurrentAccount()->crand : null;

        $sql = "SELECT * FROM (SELECT o.ctime, o.crand, o.item_id, o.x_percentage, o.y_percentage, 
                    o.one_time_only, i.name, i.media_id_small, 
                    CONCAT(m_small.Directory, '/', m_small.Id, '.', m_small.extension) AS small_image,
                    CONCAT(m_small_i.Directory, '/', m_small_i.Id, '.', m_small_i.extension) AS item_image,
                    m_small.author_id AS media_author_id, a.Username AS media_author, the.locator as locator,";

        if ($userId !== null) {
            $sql .= "CASE 
                        WHEN EXISTS (
                            SELECT 1 FROM treasure_hunt_collections c 
                            WHERE c.ref_object_ctime = o.ctime 
                            AND c.ref_object_crand = o.crand 
                            AND c.account_id = ?
                        ) THEN 1 
                        ELSE 0 
                    END AS found,
                    CASE 
                        WHEN EXISTS (
                            SELECT 1 FROM treasure_hunt_collections c2
                            WHERE c2.ref_object_ctime = o.ctime
                            AND c2.ref_object_crand = o.crand
                            AND c2.account_id = ?
                        ) THEN 1
                        ELSE 0
                    END AS foundByMe ";
        } else {
            $sql .= "0 AS found, 0 AS foundByMe ";
        }

        $sql .= "FROM treasure_hunt_objects o
                JOIN item i ON o.item_id = i.Id
                join treasure_hunt_event the on the.ctime = o.ref_event_ctime and the.crand = o.ref_event_crand
                LEFT JOIN Media m_small ON o.media_id_object = m_small.Id
                LEFT JOIN Media m_small_i ON i.media_id_small = m_small_i.Id
                LEFT JOIN account a ON m_small.author_id = a.Id
                WHERE o.page_url = ?
                AND EXISTS (
                    SELECT 1 FROM treasure_hunt_event e 
                    WHERE e.ctime = o.ref_event_ctime 
                    AND e.crand = o.ref_event_crand 
                    AND e.start_date <= NOW() 
                    AND e.end_date >= NOW()
                )
                AND NOT EXISTS (
                    SELECT 1 FROM treasure_hunt_collections c 
                    WHERE c.ref_object_ctime = o.ctime 
                    AND c.ref_object_crand = o.crand 
                    AND o.one_time_only = 1
                )) AS sub
        WHERE sub.foundByMe = 0";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return new Response(false, "Failed to prepare statement", null);
        }

        if ($userId !== null) {
            $stmt->bind_param("sss", $userId, $userId, $pageUrl);
        } else {
            $stmt->bind_param("s", $pageUrl);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if (!$result) {
            return new Response(false, "Failed to fetch objects", []);
        }

        $objects = array_map([self::class, 'row_to_vTreasureHuntObject'], $result->fetch_all(MYSQLI_ASSOC));
        return new Response(true, "Hidden objects on page", $objects);
    }

    
    /**
     * Convert DB row to vTreasureHuntEvent object, including media and ensuring content exists.
     */
    private static function row_to_vTreasureHuntEvent(array $row): vTreasureHuntEvent
    {
        $event = new vTreasureHuntEvent($row["ctime"], (int)$row["crand"]);
        $event->locator = $row["locator"];

        // Assign content (If missing, create a new content record)
        $contentId = isset($row["content_id"]) ? (int)$row["content_id"] : -1;
        $event->content = new vContent('', $contentId);

        if (!$event->hasPageContent()) {
            // Insert new content and update event
            $newContentId = ContentController::insertNewContent();
            self::updateEventContent($event, new vRecordId('', $newContentId));

            // Re-fetch event to get updated data
            $eventResp = self::queryEventByLocatorAsResponse($event->locator);
            if (!$eventResp->success) {
                return new Response(false, "Failed to fetch newly updated event after adding content.", $event);
            }

            $event = $eventResp->data;
        } else {

            $event->name = $row["name"];
            $event->desc = $row["desc"];
            $event->startDate = new vDateTime($row["start_date"]);
            $event->endDate = new vDateTime($row["end_date"]);

            // Assign media icon if available
            if (array_key_exists('icon_id', $row) && isset($row['icon_id'])) {
                $iconMedia = new vMedia();
                $iconMedia->setMediaPath("{$row['icon_directory']}/{$row['icon_id']}.{$row['icon_extension']}");
                $event->icon = $iconMedia;
            } else {
                $event->icon = vMedia::defaultIcon();
            }

            // Assign desktop banner if available
            if (array_key_exists('banner_id', $row) && isset($row['banner_id'])) {
                $bannerMedia = new vMedia();
                $bannerMedia->setMediaPath("{$row['banner_directory']}/{$row['banner_id']}.{$row['banner_extension']}");
                $event->banner = $bannerMedia;
            } else {
                $event->banner = vMedia::defaultBanner();
            }

            // Assign mobile banner if available
            if (array_key_exists('banner_mobile_id', $row) && isset($row['banner_mobile_id'])) {
                $mobileBannerMedia = new vMedia();
                $mobileBannerMedia->setMediaPath("{$row['banner_mobile_directory']}/{$row['banner_mobile_id']}.{$row['banner_mobile_extension']}");
                $event->bannerMobile = $mobileBannerMedia;
            } else {
                $event->bannerMobile = vMedia::defaultBannerMobile();
            }

            // Date Card Banner
            if (array_key_exists('banner_date_id', $row) && isset($row['banner_date_id'])) {
                $bannerDateMedia = new vMedia();
                $bannerDateMedia->setMediaPath("{$row['banner_date_directory']}/{$row['banner_date_id']}.{$row['banner_date_extension']}");
                $event->bannerDate = $bannerDateMedia;
            } else {
                $event->bannerDate = vMedia::defaultBanner();;
            }

            // Progress Card Banner
            if (array_key_exists('banner_progress_id', $row) && isset($row['banner_progress_id'])) {
                $bannerProgressMedia = new vMedia();
                $bannerProgressMedia->setMediaPath("{$row['banner_progress_directory']}/{$row['banner_progress_id']}.{$row['banner_progress_extension']}");
                $event->bannerProgress = $bannerProgressMedia;
            } else {
                $event->bannerProgress = vMedia::defaultBanner();;
            }
        }

        return $event;
    }

    /**
     * Convert DB row to vTreasureHuntObject object with vMedia and found status
     */
    private static function row_to_vTreasureHuntObject(array $row): vTreasureHuntObject
    {
        $object = new vTreasureHuntObject($row["ctime"], (int)$row["crand"]);
        $object->item = ItemController::getItemById(new vRecordId('', (int)$row["item_id"]))->data;

        if (array_key_exists("item_image", $row))
        {
            $object->item->iconSmall = new vMedia();
            $object->item->iconSmall->setMediaPath($row["item_image"]);
        }

        $object->xPercentage = (float)$row["x_percentage"];
        $object->yPercentage = (float)$row["y_percentage"];
        $object->found = (bool)$row["found"]; // Track if the user has found the object
        $object->foundByMe = (bool)$row["foundByMe"];

        $object->oneTimeFind = (bool)$row["one_time_only"];
        $object->locator = $row["locator"];
        // Assign media if available
        if (array_key_exists('small_image', $row) && isset($row['small_image'])) {
            $media = new vMedia();
            $media->setMediaPath($row["small_image"]);

            // Attach author info if available
            if (array_key_exists('media_author_id', $row) && isset($row['media_author_id'])) {
                $author = new vAccount('', (int)$row["media_author_id"]);
                $author->username = $row["media_author"];
                $media->author = $author;
            }

            $object->media = $media;
        } else {
            $object->media = vMedia::defaultIcon();
        }

        return $object;
    }
}
?>
