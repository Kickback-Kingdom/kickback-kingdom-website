<?php

namespace Kickback\Backend\Controllers;

use Kickback\Backend\Views\vQuestLine;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vDateTime;
use Kickback\Backend\Views\vContent;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vReviewStatus;
use Kickback\Backend\Views\vMedia;
use Kickback\Services\Database;
use Kickback\Backend\Models\Response;
use Kickback\Services\Session;

class QuestLineController {
    
    public static function queryQuestLineById(vRecordId $questLineId) : vQuestLine
    {
        $resp = self::queryQuestLineByIdAsResponse($questLineId);
        if ($resp->success) {
            // @phpstan-ignore return.type
            return $resp->data;
        } else {
            throw new \Exception($resp->message);
        }
    }

    public static function queryQuestLineByIdAsResponse(vRecordId $questLineId) : Response
    {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("SELECT * FROM v_quest_line_info WHERE Id = ?");
        $stmt->bind_param("i", $questLineId->crand);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0)
        {
            $row = $result->fetch_assoc();
            $stmt->close();
            return (new Response(true, "Quest Line Information.", self::row_to_vQuestLine($row)));
        }
        else
        {
            $stmt->close();
            return (new Response(false, "We couldn't find a quest line with that id.", null));
        }
    }

    /**
    * @phpstan-assert-if-true =vQuestLine $questLine
    */
    public static function queryQuestLineByLocatorInto(string $locator, ?vQuestLine &$questLine): bool
    {
        $resp = self::queryQuestLineByLocatorAsResponse($locator);
        if ( $resp->success ) {
            $questLine = $resp->data;
            return true;
        } else {
            $questLine = null;
            return false;
        }
    }

    public static function queryQuestLineByLocator(string $locator) : vQuestLine
    {
        $resp = self::queryQuestLineByLocatorAsResponse($locator);
        if ($resp->success) {
            // @phpstan-ignore-next-line
            return $resp->data;
        } else {
            throw new \Exception($resp->message);
        }
    }

    public static function queryQuestLineByLocatorAsResponse(string $locator) : Response
    {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("SELECT * FROM v_quest_line_info WHERE locator = ?");
        $stmt->bind_param("s", $locator);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0)
        {
            $row = $result->fetch_assoc();
            $stmt->close();
            return (new Response(true, "Quest Line Information.", self::row_to_vQuestLine($row)));
        }
        else
        {
            $stmt->close();
            return (new Response(false, "Couldn't find a quest line with that locator.", null));
        }
    }

    public static function getMyQuestLines(?vAccount $account = null, bool $publishedOnly = true) : Response
    {
        if ($account == null)
        {
            $account = Session::getCurrentAccount();
        }
        $conn = Database::getConnection();
        if ($publishedOnly)
        {
            $sql = "SELECT * FROM v_quest_line_info WHERE created_by_id = ? and published = 1";
        }
        else
        {
            $sql = "SELECT * FROM v_quest_line_info WHERE created_by_id = ?";
        }
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $account->crand);
        $stmt->execute();
        $result = $stmt->get_result();
    
    
        $num_rows = mysqli_num_rows($result);
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        
        $questLines = [];
        foreach($rows as $row) {
            // Remove unwanted fields

            $questLine = self::row_to_vQuestLine($row);

            $questLines[] = $questLine;
        }

        return (new Response(true, "My Quest Lines",  $questLines ));
    }

    public static function updateQuestLineContent(vRecordId $questLineId, vRecordId $contentId): Response
    {
        $conn = Database::getConnection();

        // Prepare the SQL statement to update the content ID in the quest line
        $stmt = $conn->prepare("UPDATE quest_line SET content_id = ? WHERE Id = ?");
        if (!$stmt) {
            return new Response(false, "Failed to prepare statement for updating quest line content.", null);
        }

        // Bind the parameters and execute the statement
        $stmt->bind_param('ii', $contentId->crand, $questLineId->crand);
        $stmt->execute();

        // Check if any rows were affected
        if ($stmt->affected_rows > 0) {
            $stmt->close();
            return new Response(true, "Quest line content updated successfully.", null);
        } else {
            $stmt->close();
            return new Response(false, "Failed to update quest line content or no changes made.", null);
        }
    }

    public static function insertNewQuestLine(): Response {
        if (!Session::isQuestGiver()) {
            return new Response(false, "You do not have permissions to post a new quest line.", null);
        }

        $conn = Database::getConnection();
        $questLineName = "New Quest Line";
        $questLineLocator = "new-quest-line-" . Session::getCurrentAccount()->crand;

        // Check if the quest line already exists by locator
        $questLineResp = self::queryQuestLineByLocatorAsResponse($questLineLocator);
        if (!$questLineResp->success) {
            // Prepare the insert statement
            $stmt = $conn->prepare("INSERT INTO quest_line (name, locator, created_by_id, `desc`) VALUES (?, ?, ?, '')");
            if (!$stmt) {
                // Handle preparation failure
                return new Response(false, "Failed to prepare statement for inserting new quest line.", null);
            }

            // Bind parameters and execute the statement
            $stmt->bind_param('ssi', $questLineName, $questLineLocator, Session::getCurrentAccount()->crand);
            if (!$stmt->execute()) {
                // Handle execution failure
                return new Response(false, "Failed to execute statement for inserting new quest line.", null);
            }

            // Get the new ID of the inserted quest line
            $newId = $stmt->insert_id;
            $stmt->close();

            if ($newId == 0) {
                // Insert failed, no new ID generated
                return new Response(false, "Insert operation failed or did not generate a new ID.", null);
            }

            // Fetch the newly inserted quest line
            $questLineResp = self::queryQuestLineByLocatorAsResponse($questLineLocator);
            if (!$questLineResp->success) {
                return new Response(false, "Failed to find newly inserted quest by locator.", $questLineLocator);
            }
        }

        // Check if content ID is null and handle content insertion if needed
        if (!$questLineResp->data->hasPageContent()) {
            $newContentId = ContentController::insertNewContent();
            self::updateQuestLineContent($questLineResp->data, $newContentId);

            // Re-fetch the quest line after inserting the content
            $questLineResp = self::queryQuestLineByLocatorAsResponse($questLineLocator);
            if (!$questLineResp->success) {
                return new Response(false, "Failed to find newly inserted quest by locator after inserting content record.", $questLineLocator);
            }
        }

        if (!$questLineResp->success) {
            return new Response(false, "Failed to find newly inserted quest.", $questLineResp);
        }

        return new Response(true, "New quest line created.", $questLineResp->data);
    }

    public static function updateQuestLineImages(vRecordId $questId, vRecordId $desktopBannerId, vRecordId $mobileBannerId, vRecordId $iconId): Response {
        $conn = Database::getConnection();

        // Fetch the quest line by ID
        $questResp = self::queryQuestLineByIdAsResponse($questId);
        if (!$questResp->success) {
            return new Response(false, "Error updating quest. Could not find quest by Id.", null);
        }

        $questLine = $questResp->data;

        // Check if the user has permission to edit the quest line
        if (!$questLine->canEdit()) {
            return new Response(false, "Error updating quest. You do not have permission to edit this quest.", null);
        }

        // Prepare the SQL statement to update the images
        $query = "UPDATE quest_line SET image_id_icon = ?, image_id = ?, image_id_mobile = ?, `published` = 0, `being_reviewed` = 0 WHERE Id = ?";
        $stmt = $conn->prepare($query);

        if (!$stmt) {
            // Handle preparation errors
            return new Response(false, "Failed to prepare the update statement.", null);
        }

        // Bind the parameters
        $stmt->bind_param('iiii', $iconId->crand, $desktopBannerId->crand, $mobileBannerId->crand, $questId->crand);

        // Execute the statement
        $success = $stmt->execute();

        // Close the prepared statement
        $stmt->close();

        // Check if the update was successful
        if ($success) {
            return new Response(true, "Quest images updated successfully!", null);
        } else {
            return new Response(false, "Error updating quest images with unknown error.", null);
        }
    }

    public static function approveQuestLineReview(array $data): Response {
        $conn = Database::getConnection();

        // Retrieve the quest line ID from the provided data
        $questLineId = $data["quest-line-id"];

        // Check if the user has admin privileges
        if (!Session::isMagisterOfTheAdventurersGuild()) {
            return new Response(false, "Approval denied. Insufficient permissions to approve this quest line.", null);
        }

        // Prepare the SQL statement to mark the quest line as approved and published
        $stmt = $conn->prepare("UPDATE quest_line SET published = 1, being_reviewed = 0 WHERE Id = ?");

        if (!$stmt) {
            // Handle preparation errors
            return new Response(false, "Failed to prepare the review approval statement.", null);
        }

        // Bind the quest line ID to the statement
        $stmt->bind_param('i', $questLineId);

        // Execute the update statement
        if (!$stmt->execute()) {
            // Handle execution errors
            $stmt->close();
            return new Response(false, "Quest line review approval failed due to an execution error.", null);
        }

        // Close the prepared statement
        $stmt->close();

        // Successfully approved and published the quest line
        return new Response(true, "Quest line successfully approved and published.", null);
    }

    public static function rejectQuestLineReview(array $data): Response {
        $conn = Database::getConnection();

        // Retrieve the quest line ID from the provided data
        $questLineId = $data["quest-line-id"];

        // Optional: If you want to restrict rejection to admins only, uncomment this block
        /*if (!Session::isAdmin()) {
            return new Response(false, "Rejection denied. Insufficient permissions to reject this quest line.", null);
        }*/

        // Prepare the SQL statement to mark the quest line as not published and not being reviewed
        $stmt = $conn->prepare("UPDATE quest_line SET published = 0, being_reviewed = 0 WHERE Id = ? AND (being_reviewed = 1 OR published = 1)");

        if (!$stmt) {
            // Handle preparation errors
            return new Response(false, "Failed to prepare the review rejection statement.", null);
        }

        // Bind the quest line ID to the statement
        $stmt->bind_param('i', $questLineId);

        // Execute the update statement
        if (!$stmt->execute()) {
            // Handle execution errors
            $stmt->close();
            return new Response(false, "Quest line review rejection failed due to an execution error.", null);
        }

        // Close the prepared statement
        $stmt->close();

        // Successfully updated the quest line status to not being reviewed and unpublished
        return new Response(true, "Quest line publish rejected.", null);
    }

    public static function submitQuestLineForReview(array $data): Response {
        $conn = Database::getConnection();

        // Retrieve the quest line ID from the provided data
        $questLineId = $data["quest-line-id"];

        // Fetch the quest line details to check its existence and editability
        $questLineResp = self::queryQuestLineByIdAsResponse(new vRecordId('', $questLineId));
        if (!$questLineResp->success) {
            return new Response(false, "Quest line submission failed. Quest line not found.", null);
        }

        // Verify editing permissions for the quest line
        $questLine = $questLineResp->data;
        if (!$questLine->canEdit()) {
            return new Response(false, "Submission denied. Insufficient permissions to edit this quest line.", null);
        }

        // Prepare the SQL statement to mark the quest line as being reviewed
        $stmt = $conn->prepare("UPDATE quest_line SET published = 0, being_reviewed = 1 WHERE Id = ?");

        if (!$stmt) {
            // Handle preparation errors
            return new Response(false, "Failed to prepare the review submission statement.", null);
        }

        // Bind the quest line ID to the statement
        $stmt->bind_param('i', $questLineId);

        // Execute the update statement
        if (!$stmt->execute()) {
            // Handle execution errors
            $stmt->close();
            return new Response(false, "Quest line review submission failed due to an execution error.", null);
        }

        // Close the prepared statement
        $stmt->close();

        // Successfully updated the quest line status to being reviewed
        return new Response(true, "Quest line successfully submitted for review.", null);
    }

    public static function updateQuestLineOptions(array $data): Response {
        $conn = Database::getConnection();

        $questLineId = $data["edit-quest-line-id"];
        $questLineName = $data["edit-quest-line-options-title"];
        $questLineLocator = $data["edit-quest-line-options-locator"];
        $questLineSummary = $data["edit-quest-line-options-summary"];

        // Fetch the quest line by ID
        $questLineResp = self::queryQuestLineByIdAsResponse(new vRecordId('', $questLineId));
        if (!$questLineResp->success) {
            return new Response(false, "Error updating quest line. Could not find quest line by Id.", null);
        }

        $questLine = $questLineResp->data;

        // Check if the user has permission to edit the quest line
        if (!$questLine->canEdit()) {
            return new Response(false, "Error updating quest line. You do not have permission to edit this quest line.", null);
        }

        // Prepare the update statement, adjust field names according to your quest_line table
        $stmt = $conn->prepare(
            "UPDATE quest_line SET name = ?, locator = ?, `desc` = ?, `published` = 0, `being_reviewed` = 0 WHERE Id = ?"
        );

        if (false === $stmt) {
            return new Response(false, "Error preparing the update statement.", null);
        }

        // Bind the parameters
        $stmt->bind_param('sssi', $questLineName, $questLineLocator, $questLineSummary, $questLineId);

        // Execute the statement
        $success = $stmt->execute();

        // Close the statement
        $stmt->close();

        if ($success) {
            // Determine if the locator was changed
            $locatorChanged = $questLine->locator != $questLineLocator;

            // Construct a response object
            $responseData = (object)[
                'locator' => $questLineLocator,
                'locatorChanged' => $locatorChanged
            ];

            return new Response(true, "Quest line options updated successfully!", $responseData);
        } else {
            return new Response(false, "Error updating quest line options with unknown error.", null);
        }
    }
    
    private static function row_to_vQuestLine($row) : vQuestLine {
        $questLine = new vQuestLine('',$row["Id"]);

        $questLine->title = $row["name"];
        $questLine->summary = $row["desc"];
        $questLine->dateCreated = new vDateTime();
        $questLine->dateCreated->setDateTimeFromString($row["date_created"]);
        $questLine->locator = $row["locator"];


        if ($row["content_id"] != null)
        {
            $questLine->content = new vContent('', $row["content_id"]);
        }
        else{
            $questLine->content = new vContent();
            $questLine->content->htmlContent = $row["desc"];
        }

        $questLine->createdBy = new vAccount('', $row["created_by_id"]);
        $questLine->createdBy->username = $row["created_by_username"];
        $questLine->reviewStatus = new vReviewStatus((bool)$row["published"], (bool)$row["being_reviewed"]);

        
        if ($row["image_id"] != null)
        {
            $banner = new vMedia('',$row["image_id"]);
            $banner->setMediaPath($row["imagePath"]);
            $questLine->banner = $banner;
        }
        else{
            
            $questLine->banner = vMedia::defaultBanner();
        }

        if ($row["image_id_icon"] != null)
        {
            $icon = new vMedia('',$row["image_id_icon"]);
            $icon->setMediaPath($row["imagePath_icon"]);
            $questLine->icon = $icon;
        }
        else{
            
            $questLine->icon = vMedia::defaultIcon();
        }

        if ($row["image_id_mobile"] != null)
        {
            $bannerMobile = new vMedia('',$row["image_id_mobile"]);
            $bannerMobile->setMediaPath($row["imagePath_mobile"]);
            $questLine->bannerMobile = $bannerMobile;
        }
        else{
            
            $questLine->bannerMobile = vMedia::defaultBannerMobile();
        }

        return $questLine;
    }
}


?>
