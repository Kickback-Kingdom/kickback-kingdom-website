<?php
declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use Kickback\Backend\Models\LichCard;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vLichCard;
use Kickback\Backend\Views\vLichSet;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Models\RecordId;
use Kickback\Services\Database;
use Kickback\Backend\Views\vReviewStatus;
use Kickback\Backend\Views\vMedia;
use Kickback\Backend\Views\vContent;

class LichCardController
{
    public static function getAllLichSets(): Response
    {
        $conn = Database::getConnection();

        // Query to fetch all Lich sets
        $sql = "
            SELECT 
                ls.ctime, 
                ls.crand, 
                ls.name, 
                ls.locator,
                ls.content_id,
                ls.description
            FROM lich_set ls
            ORDER BY ls.ctime DESC
        ";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return new Response(false, "Failed to prepare the SQL statement for Lich Sets.");
        }

        if (!$stmt->execute()) {
            return new Response(false, "Failed to execute the SQL statement for Lich Sets.");
        }

        $result = $stmt->get_result();
        if (!$result) {
            return new Response(false, "Failed to retrieve the result set for Lich Sets.");
        }

        $lichSets = [];
        while ($row = $result->fetch_assoc()) {
            $lichSets[] = self::row_to_vLichSet($row);
        }

        $stmt->close();

        if (empty($lichSets)) {
            return new Response(false, "No Lich Sets found.");
        }

        return new Response(true, "Lich Sets retrieved successfully.", $lichSets);
    }

    public static function getLichSetByLocator(string $locator): Response
    {
        $conn = Database::getConnection();

        $sql = "
            SELECT 
                ls.ctime, 
                ls.crand, 
                ls.name, 
                ls.locator,
                ls.content_id,
                ls.description
            FROM lich_set ls
            WHERE ls.locator = ?
        ";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return new Response(false, "Failed to prepare the SQL statement.");
        }

        if (!$stmt->bind_param('s', $locator)) {
            return new Response(false, "Failed to bind parameters.");
        }

        if (!$stmt->execute()) {
            return new Response(false, "Failed to execute the SQL statement.");
        }

        $result = $stmt->get_result();
        if (!$result) {
            return new Response(false, "Failed to retrieve the result set.");
        }

        $lichSet = null;
        if ($row = $result->fetch_assoc()) {
            $lichSet = self::row_to_vLichSet($row);
        }

        $stmt->close();

        if ($lichSet === null) {
            return new Response(false, "Lich Set not found.");
        }

        return new Response(true, "Lich Set retrieved successfully.", $lichSet);
    }

    public static function getLichSetByRecordId(vRecordId $recordId): Response
    {
        $conn = Database::getConnection();

        $sql = "
            SELECT 
                ls.ctime, 
                ls.crand, 
                ls.name, 
                ls.locator,
                ls.content_id,
                ls.description
            FROM lich_set ls
            WHERE ls.ctime = ? AND ls.crand = ?
        ";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return new Response(false, "Failed to prepare the SQL statement.");
        }

        // Bind the parameters
        if (!$stmt->bind_param('si', $recordId->ctime, $recordId->crand)) {
            return new Response(false, "Failed to bind parameters.");
        }

        // Execute the statement
        if (!$stmt->execute()) {
            return new Response(false, "Failed to execute the SQL statement.");
        }

        // Get the result
        $result = $stmt->get_result();
        if (!$result) {
            return new Response(false, "Failed to retrieve the result set.");
        }

        $lichSet = null;
        if ($row = $result->fetch_assoc()) {
            $lichSet = self::row_to_vLichSet($row);
        }

        $stmt->close();

        if ($lichSet === null) {
            return new Response(false, "Lich Set not found.");
        }

        return new Response(true, "Lich Set retrieved successfully.", $lichSet);
    }



    public static function getAllLichCards(): Response
    {
        $conn = Database::getConnection();

        $sql = "
            SELECT 
                lc.ctime, lc.crand, lc.name, lc.type, lc.rarity, lc.description,
                lc.font_size_name AS nameFontSize,
                lc.font_size_type AS typeFontSize,
                lc.font_size_description AS descriptionFontSize,
                lc.stat_health AS health,
                lc.stat_intelligence AS intelligence,
                lc.stat_defense AS defense,
                lc.source_arcanic AS arcanic,
                lc.source_abyssal AS abyssal,
                lc.source_thermic AS thermic,
                lc.source_verdant AS verdant,
                lc.source_luminate AS luminate,
                lc.locator,
                lc.published,
                lc.being_reviewed,
                lc.content_id,
                lc.lich_set_ctime,
                lc.lich_set_crand,
                ls.name as lich_set_name,
                ls.locator as lich_set_locator,
                
                CONCAT(m.Directory, '/', m.Id, '.', m.extension) AS artPath,
                m.Id AS mediaId,
                m.name AS mediaName,
                m.desc AS mediaDesc,

                CONCAT(fm.Directory, '/', fm.Id, '.', fm.extension) AS cardImagePath,
                fm.Id AS finishedMediaId,
                fm.name AS finishedMediaName,
                fm.desc AS finishedMediaDesc

            FROM lich_card lc
            LEFT JOIN Media m ON lc.media_id = m.Id
            LEFT JOIN Media fm ON lc.finished_card_media_id = fm.Id
            LEFT JOIN lich_set ls on lc.lich_set_ctime = ls.ctime and lc.lich_set_crand = ls.crand
        ";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return new Response(false, "Failed to prepare the SQL statement.");
        }


        if (!$stmt->execute()) {
            return new Response(false, "Failed to execute the SQL statement.");
        }

        $result = $stmt->get_result();
        if (!$result) {
            return new Response(false, "Failed to retrieve the result set.");
        }

        $lichCards = [];
        while ($row = $result->fetch_assoc()) { // Use while loop to fetch all rows
            $lichCards[] = self::row_to_vLichCard($row);
        }

        $stmt->close();

        return new Response(true, "Lich Card retrieved successfully.", $lichCards);
    }

    public static function getAllSubtypes(): Response
    {
        $conn = Database::getConnection();

        $sql = "SELECT crand, ctime, name FROM lich_card_subtypes ORDER BY name ASC";
        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            return new Response(false, "SQL statement preparation failed.", null);
        }

        if (!mysqli_stmt_execute($stmt)) {
            return new Response(false, "Query execution failed.", null);
        }

        $result = mysqli_stmt_get_result($stmt);

        $subtypes = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $subtypes[] = [
                'crand' => $row['crand'],
                'ctime' => $row['ctime'],
                'name' => $row['name']
            ];
        }

        mysqli_stmt_close($stmt);

        if (empty($subtypes)) {
            return new Response(false, "No subtypes found.", []);
        }

        return new Response(true, "Subtypes retrieved successfully.", $subtypes);
    }

    public static function saveLichCard(LichCard $model): Response
    {
        $conn = Database::getConnection();

        $conn->begin_transaction(); // Start a transaction to ensure consistency.

        try {
            // If the model has a positive crand, update it; otherwise, insert it
            if ($model->crand > 0) {
                $sql = "UPDATE lich_card 
                        SET name = ?, type = ?, rarity = ?, description = ?, 
                            font_size_name = ?, font_size_type = ?, font_size_description = ?, 
                            stat_health = ?, stat_intelligence = ?, stat_defense = ?, 
                            source_arcanic = ?, source_abyssal = ?, source_thermic = ?, source_verdant = ?, source_luminate = ?, 
                            media_id = ?, locator = ?, lich_set_ctime = ?, lich_set_crand = ?, finished_card_media_id = ?
                        WHERE ctime = ? and crand = ? and published = 0";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    'siisdddiiiiiiiiissiisi',
                    $model->name,
                    $model->type,
                    $model->rarity,
                    $model->description,
                    $model->nameFontSize,
                    $model->typeFontSize,
                    $model->descriptionFontSize,
                    $model->health,
                    $model->intelligence,
                    $model->defense,
                    $model->arcanic,
                    $model->abyssal,
                    $model->thermic,
                    $model->verdant,
                    $model->luminate,
                    $model->art->crand,
                    $model->locator,
                    $model->set->ctime,
                    $model->set->crand,
                    $model->cardImage->crand,
                    $model->ctime,
                    $model->crand
                );

                if (!$stmt->execute()) {
                    throw new \Exception("Update failed: " . $stmt->error);
                }
            } else {
                // Generate ctime and crand for new records
                $model->ctime = RecordId::getCTime();
                $model->crand = RecordId::generateCRand();

                $sql = "INSERT INTO lich_card 
                        (ctime, crand, name, type, rarity, description, 
                        font_size_name, font_size_type, font_size_description, 
                        stat_health, stat_intelligence, stat_defense, source_arcanic, source_abyssal, source_thermic, source_verdant, source_luminate, media_id, locator, lich_set_ctime, lich_set_crand) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);

                while (true) {

                    $stmt->bind_param(
                        'sisiisdddiiiiiiiiissi',
                        $model->ctime,
                        $model->crand,
                        $model->name,
                        $model->type,
                        $model->rarity,
                        $model->description,
                        $model->nameFontSize,
                        $model->typeFontSize,
                        $model->descriptionFontSize,
                        $model->health,
                        $model->intelligence,
                        $model->defense,
                        $model->arcanic,
                        $model->abyssal,
                        $model->thermic,
                        $model->verdant,
                        $model->luminate,
                        $model->art->crand,
                        $model->locator,
                        $model->set->ctime,
                        $model->set->crand
                    );

                    if (!$stmt->execute()) {
                        if ($stmt->errno == 1062) {
                            // Duplicate entry error (likely on crand), retry with a new crand
                            $model->crand = RecordId::generateCRand();
                            continue;
                        } else {
                            return new Response(false, "Failed to save Lich Card: " . $stmt->error, null);
                        }
                    } else {
                        break; // Exit loop if the query was successful
                    }
                }
            }

            $stmt->close();


            // Get existing subtypes for the card
            $sql = "SELECT subtype_crand, subtype_ctime FROM lich_card_subtype WHERE lich_card_crand = ? AND lich_card_ctime = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('is', $model->crand, $model->ctime);
            $stmt->execute();
            $result = $stmt->get_result();
            $existingSubtypes = [];

            while ($row = $result->fetch_assoc()) {
                $existingSubtypes[] = ['crand' => $row['subtype_crand'], 'ctime' => $row['subtype_ctime']];
            }

            $stmt->close();

            // Get the list of current subtype names in the model
            $currentSubtypeCrands = [];

            foreach ($model->subTypes as $subTypeName) {
                // Check if subtype exists
                $sql = "SELECT ctime, crand FROM lich_card_subtypes WHERE name = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('s', $subTypeName);
                $stmt->execute();
                $result = $stmt->get_result();
    
                if ($row = $result->fetch_assoc()) {
                    // Subtype exists; reuse its ctime and crand
                    $subTypeCTime = $row['ctime'];
                    $subTypeCRand = $row['crand'];
                } else {
                    // Subtype doesn't exist; insert it
                    $subtypeDetails = self::insertSubtype($subTypeName, $conn);
                    $subTypeCTime = $subtypeDetails['ctime'];
                    $subTypeCRand = $subtypeDetails['crand'];
                }
    
                $stmt->close();
    
                // Add linking record in lich_card_subtype
                $sql = "INSERT IGNORE INTO lich_card_subtype 
                        (lich_card_crand, lich_card_ctime, subtype_crand, subtype_ctime) 
                        VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    'isis',
                    $model->crand,
                    $model->ctime,
                    $subTypeCRand,
                    $subTypeCTime
                );
    
                if (!$stmt->execute()) {
                    throw new \Exception("Failed to link card and subtype: " . $stmt->error);
                }
    
                $stmt->close();
    
                // Track the current subtypes for comparison
                $currentSubtypeCrands[] = $subTypeCRand;
            }
    
            // Identify and delete subtypes that are no longer linked
            foreach ($existingSubtypes as $existingSubtype) {
                if (!in_array($existingSubtype['crand'], $currentSubtypeCrands)) {
                    $sql = "DELETE FROM lich_card_subtype 
                            WHERE lich_card_crand = ? AND lich_card_ctime = ? 
                              AND subtype_crand = ? AND subtype_ctime = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param(
                        'isis',
                        $model->crand,
                        $model->ctime,
                        $existingSubtype['crand'],
                        $existingSubtype['ctime']
                    );
    
                    if (!$stmt->execute()) {
                        throw new \Exception("Failed to delete old subtype links: " . $stmt->error);
                    }
    
                    $stmt->close();
                }
            }
    
            $conn->commit(); // Commit the transaction
            return new Response(true, "Lich Card saved successfully.", $model);

        } catch (Exception $e) {
            $conn->rollback(); // Rollback transaction on failure.
            return new Response(false, "Error saving Lich Card: " . $e->getMessage(), null);
        }
    }

    /**
     * Inserts a subtype into the database, ensuring uniqueness of the crand.
     * 
     * @param string $name The name of the subtype.
     * @param \mysqli $conn The database connection.
     * @return array An associative array with 'ctime' and 'crand' of the inserted or existing subtype.
     * @throws \Exception If insertion fails due to reasons other than a duplicate crand.
     */
    private static function insertSubtype(string $name, \mysqli $conn): array
    {
        $subTypeCTime = RecordId::getCTime();
        $subTypeCRand = RecordId::generateCRand();

        $sqlInsert = "INSERT INTO lich_card_subtypes (ctime, crand, name) VALUES (?, ?, ?)";
        $stmtInsert = $conn->prepare($sqlInsert);

        if (!$stmtInsert) {
            throw new \Exception("Failed to prepare insert statement for subtypes: " . $conn->error);
        }

        while (true) {
            try {
                $stmtInsert->bind_param('sis', $subTypeCTime, $subTypeCRand, $name);
                if ($stmtInsert->execute()) {
                    // Successfully inserted, return the subtype details.
                    return ['ctime' => $subTypeCTime, 'crand' => $subTypeCRand];
                }

                // Check for duplicate crand error (errno 1062).
                if ($stmtInsert->errno === 1062) {
                    $subTypeCRand = RecordId::generateCRand(); // Generate a new crand and retry.
                } else {
                    throw new \Exception("Failed to insert subtype: " . $stmtInsert->error);
                }
            } catch (\Exception $e) {
                throw new \Exception("Error while inserting subtype: " . $e->getMessage());
            }
        }
    }


    public static function getLichCardByRecordId(vRecordId $recordId): Response
    {
        $conn = Database::getConnection();

        $sql = "
            SELECT 
                lc.ctime, lc.crand, lc.name, lc.type, lc.rarity, lc.description,
                lc.font_size_name AS nameFontSize,
                lc.font_size_type AS typeFontSize,
                lc.font_size_description AS descriptionFontSize,
                lc.stat_health AS health,
                lc.stat_intelligence AS intelligence,
                lc.stat_defense AS defense,
                lc.source_arcanic AS arcanic,
                lc.source_abyssal AS abyssal,
                lc.source_thermic AS thermic,
                lc.source_verdant AS verdant,
                lc.source_luminate AS luminate,
                lc.locator,
                lc.published,
                lc.being_reviewed,
                lc.content_id,
                lc.lich_set_ctime,
                lc.lich_set_crand,
                ls.name as lich_set_name,
                ls.locator as lich_set_locator,

                CONCAT(m.Directory, '/', m.Id, '.', m.extension) AS artPath,
                m.Id AS mediaId,
                m.name AS mediaName,
                m.desc AS mediaDesc,

                CONCAT(fm.Directory, '/', fm.Id, '.', fm.extension) AS cardImagePath,
                fm.Id AS finishedMediaId,
                fm.name AS finishedMediaName,
                fm.desc AS finishedMediaDesc

            FROM lich_card lc
            LEFT JOIN Media m ON lc.media_id = m.Id
            LEFT JOIN Media fm ON lc.finished_card_media_id = fm.Id
            LEFT JOIN lich_set ls on lc.lich_set_ctime = ls.ctime and lc.lich_set_crand = ls.crand
            WHERE lc.ctime = ? AND lc.crand = ?
        ";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return new Response(false, "Failed to prepare the SQL statement.");
        }

        if (!$stmt->bind_param('si', $recordId->ctime, $recordId->crand)) {
            return new Response(false, "Failed to bind parameters.");
        }

        if (!$stmt->execute()) {
            return new Response(false, "Failed to execute the SQL statement.");
        }

        $result = $stmt->get_result();
        if (!$result) {
            return new Response(false, "Failed to retrieve the result set.");
        }

        $lichCard = null;
        if ($row = $result->fetch_assoc()) {
            $lichCard = self::row_to_vLichCard($row, true);
        }

        $stmt->close();

        if ($lichCard === null) {
            return new Response(false, "Lich Card not found.");
        }

        return new Response(true, "Lich Card retrieved successfully.", $lichCard);
    }
   
    public static function getLichCardByLocator(string $locator): Response
    {
        $conn = Database::getConnection();

        $sql = "
            SELECT 
                lc.ctime, lc.crand, lc.name, lc.type, lc.rarity, lc.description,
                lc.font_size_name AS nameFontSize,
                lc.font_size_type AS typeFontSize,
                lc.font_size_description AS descriptionFontSize,
                lc.stat_health AS health,
                lc.stat_intelligence AS intelligence,
                lc.stat_defense AS defense,
                lc.source_arcanic AS arcanic,
                lc.source_abyssal AS abyssal,
                lc.source_thermic AS thermic,
                lc.source_verdant AS verdant,
                lc.source_luminate AS luminate,
                lc.locator,
                lc.published,
                lc.being_reviewed,
                lc.content_id,
                lc.lich_set_ctime,
                lc.lich_set_crand,
                ls.name as lich_set_name,
                ls.locator as lich_set_locator,
                
                CONCAT(m.Directory, '/', m.Id, '.', m.extension) AS artPath,
                m.Id AS mediaId,
                m.name AS mediaName,
                m.desc AS mediaDesc,

                CONCAT(fm.Directory, '/', fm.Id, '.', fm.extension) AS cardImagePath,
                fm.Id AS finishedMediaId,
                fm.name AS finishedMediaName,
                fm.desc AS finishedMediaDesc

            FROM lich_card lc
            LEFT JOIN Media m ON lc.media_id = m.Id
            LEFT JOIN Media fm ON lc.finished_card_media_id = fm.Id
            LEFT JOIN lich_set ls on lc.lich_set_ctime = ls.ctime and lc.lich_set_crand = ls.crand
            WHERE lc.locator = ?
        ";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return new Response(false, "Failed to prepare the SQL statement.");
        }

        if (!$stmt->bind_param('s', $locator)) {
            return new Response(false, "Failed to bind parameters.");
        }

        if (!$stmt->execute()) {
            return new Response(false, "Failed to execute the SQL statement.");
        }

        $result = $stmt->get_result();
        if (!$result) {
            return new Response(false, "Failed to retrieve the result set.");
        }

        $lichCard = null;
        if ($row = $result->fetch_assoc()) {
            $lichCard = self::row_to_vLichCard($row, true);
        }

        $stmt->close();

        if ($lichCard === null) {
            return new Response(false, "Lich Card not found.");
        }

        return new Response(true, "Lich Card retrieved successfully.", $lichCard);
    }

    public static function updateLichCardWikiContent(vRecordId $lichCardId, int $contentId) : Response 
    {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("UPDATE lich_card SET content_id = ? WHERE ctime = ? and crand = ?");
        mysqli_stmt_bind_param($stmt, 'isi', $contentId, $lichCardId->ctime, $lichCardId->crand);
        mysqli_stmt_execute($stmt);

        return (new Response(true, "Lich Card content updated.", null));
    }

    public static function updateLichSetWikiContent(vRecordId $lichSetId, int $contentId): Response
    {
        $conn = Database::getConnection();
        
        // Prepare the SQL query to update the content_id in the lich_set table
        $stmt = $conn->prepare("UPDATE lich_set SET content_id = ? WHERE ctime = ? AND crand = ?");
        if (!$stmt) {
            return new Response(false, "Failed to prepare the SQL statement: " . $conn->error, null);
        }
        
        // Bind the parameters to the prepared statement
        $stmt->bind_param('isi', $contentId, $lichSetId->ctime, $lichSetId->crand);

        // Execute the statement
        if (!$stmt->execute()) {
            return new Response(false, "Failed to execute the SQL statement: " . $stmt->error, null);
        }

        // Close the statement
        $stmt->close();

        return new Response(true, "Lich Set content updated successfully.", null);
    }


    private static function getCardSubTypes(vRecordId $lichCardId): array
    {
        $conn = Database::getConnection();

        $sql = "
            SELECT lst.name
            FROM lich_card_subtype lcs
            JOIN lich_card_subtypes lst 
            ON lcs.subtype_crand = lst.crand 
            AND lcs.subtype_ctime = lst.ctime
            WHERE lcs.lich_card_crand = ? AND lcs.lich_card_ctime = ?
        ";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new \Exception("Failed to prepare SQL statement for subtypes: " . $conn->error);
        }

        $stmt->bind_param('is', $lichCardId->crand, $lichCardId->ctime);
        $stmt->execute();
        $result = $stmt->get_result();

        $subTypes = [];
        while ($row = $result->fetch_assoc()) {
            $subTypes[] = $row['name'];
        }

        $stmt->close();
        return $subTypes;
    }


    private static function row_to_vLichCard(array $row, bool $populateSubTypes = false): vLichCard
    {
        
        $lichCard = new vLichCard($row['ctime'], $row['crand']);

        $contentId = isset($row["content_id"]) ? (int)$row["content_id"] : -1;
        $lichCard->content = new vContent('', $contentId);
        
         // Check if content ID is null and handle content insertion if needed
         if (!$lichCard->hasPageContent()) {
            $newContentId = ContentController::insertNewContent();
            self::updateLichCardWikiContent($lichCard, $newContentId);

            // Re-fetch the quest line after inserting the content
            $lichCardResp = self::getLichCardByRecordId($lichCard);
            if (!$lichCardResp->success) {
                return new Response(false, "Failed to find newly inserted lich card by id after inserting content record.", $lichCard);
            }


            $lichCard = $lichCardResp->data;
        }
        else
        {
            //$lichCard = new vLichCard($row['ctime'], $row['crand']);
            $lichCard->name = $row['name'];
            $lichCard->type = $row['type'];
            $lichCard->rarity = $row['rarity'];
            $lichCard->description = $row['description'];
            $lichCard->nameFontSize = (float)$row['nameFontSize'];
            $lichCard->typeFontSize = (float)$row['typeFontSize'];
            $lichCard->descriptionFontSize = (float)$row['descriptionFontSize'];
            $lichCard->health = (int)$row['health'];
            $lichCard->intelligence = (int)$row['intelligence'];
            $lichCard->defense = (int)$row['defense'];
            $lichCard->arcanic = (int)$row['arcanic'];
            $lichCard->abyssal = (int)$row['abyssal'];
            $lichCard->thermic = (int)$row['thermic'];
            $lichCard->verdant = (int)$row['verdant'];
            $lichCard->luminate = (int)$row['luminate'];
            $lichCard->locator = $row['locator'];
            $lichCard->reviewStatus = new vReviewStatus((bool)$row['published'], (bool)$row['being_reviewed']);

            $lichCard->set = new vLichSet($row["lich_set_ctime"],(int)$row["lich_set_crand"]);
            $lichCard->set->name = $row["lich_set_name"];
            $lichCard->set->locator = $row["lich_set_locator"];

            if ($populateSubTypes)
            {
                $lichCard->subTypes = self::getCardSubTypes($lichCard);
            }


            // Handle art media
            if (!empty($row['mediaId'])) {
                $art = new vMedia('', $row['mediaId']);
                $art->setMediaPath($row['artPath']);
                $lichCard->art = $art;
            } else {
                $lichCard->art = vMedia::defaultIcon();
            }

            // Handle finished card media
            if (!empty($row['finishedMediaId'])) {
                $finishedCard = new vMedia('', $row['finishedMediaId']);
                $finishedCard->setMediaPath($row['cardImagePath']);
                $lichCard->cardImage = $finishedCard;
            } else {
                $lichCard->cardImage = vMedia::defaultIcon();
            }

        }

        

        return $lichCard;
    }

    private static function row_to_vLichSet(array $row): vLichSet
    {
        $lichSet = new vLichSet($row['ctime'], (int)$row['crand']);

        $contentId = isset($row["content_id"]) ? (int)$row["content_id"] : -1;
        $lichSet->content = new vContent('', $contentId);
        // Check if content ID is null and handle content insertion if needed
        if (!$lichSet->hasPageContent()) {
            $newContentId = ContentController::insertNewContent();
            self::updateLichSetWikiContent($lichSet, $newContentId);

            // Re-fetch the quest line after inserting the content
            $lichSetResp = self::getLichSetByRecordId($lichSet);
            if (!$lichSetResp->success) {
                return new Response(false, "Failed to find newly inserted lich card by id after inserting content record.", $lichSet);
            }

            $lichSet = $lichSetResp->data;
        }
        else
        {
            $lichSet->name = $row['name'];
            $lichSet->locator = $row['locator'];
            $lichSet->description = $row['description'] ?? "";
        }
        return $lichSet;
    }
}