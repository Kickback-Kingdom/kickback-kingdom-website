<?php

function QuestLineNameIsValid($questName) {
    $valid = StringIsValid($questName, 10);
    if ($valid) 
    {
        if (strtolower($questName) == "new quest")
            $valid = false;
    }

    return $valid;
}

function QuestLineSummaryIsValid($questSummary) {
    $valid = StringIsValid($questSummary, 200);

    return $valid;
}

function QuestLinePageContentIsValid($pageContent)
{

    return count($pageContent) > 0;
}

function QuestLineLocatorIsValid($locator)
{
    $valid = StringIsValid($locator, 5);
    if ($valid) 
    {
        if (strpos(strtolower($locator), 'new-quest-') === 0) {
            $valid = false;
        }
    }

    return $valid;
}

function QuestLineImageIsValid($media_id)
{
    return isset($media_id) && !is_null($media_id);
}

function QuestLineImagesAreValid($quest)
{
    return QuestImageIsValid($quest["imagePath"]) && QuestImageIsValid($quest["imagePath_icon"]) && QuestImageIsValid($quest["imagePath_mobile"]);
}

function QuestLineIsValidForPublish($quest, $pageContent)
{
    return QuestNameIsValid($quest["name"]) && QuestSummaryIsValid($quest["desc"]) && QuestLocatorIsValid($quest["locator"]) && QuestPageContentIsValid($pageContent) && QuestImageIsValid($quest["imagePath_icon"]);
}

function GetQuestLineByLocator($locator)
{
    $conn = $GLOBALS["conn"];
    $stmt = $conn->prepare("SELECT * FROM v_quest_line_info WHERE locator = ?");
    $stmt->bind_param("s", $locator);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0)
    {
        $row = $result->fetch_assoc();
        $stmt->close();
        return (new APIResponse(true, "Quest Line Information.", $row));
    }
    else
    {
        $stmt->close();
        return (new APIResponse(false, "Couldn't find a quest line with that locator.", null));
    }
}

function GetMyQuestLines($accountId = null, $publishedOnly = true)
{
    if ($accountId == null)
    {
        $accountId = $_SESSION["account"]["Id"];
    }
    $conn = $GLOBALS["conn"];
    if ($publishedOnly)
    {
        $sql = "SELECT * FROM v_quest_line_info WHERE created_by_id = ? and published = 1";
    }
    else
    {
        $sql = "SELECT * FROM v_quest_line_info WHERE created_by_id = ?";
    }
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $accountId);
    $stmt->execute();
    $result = $stmt->get_result();


    $num_rows = mysqli_num_rows($result);
    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
    return (new APIResponse(true, "My Quest Lines",  $rows ));
}

function GetAvailableQuestLinesFeed($page = 1, $itemsPerPage = 10)
{
    $offset = ($page - 1) * $itemsPerPage;
    $sql = "SELECT * FROM kickbackdb.v_feed WHERE type = 'QUEST-LINE' order by date asc";

    
    $result = mysqli_query($GLOBALS["conn"],$sql);

    $num_rows = mysqli_num_rows($result);
    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
    return (new APIResponse(true, "Available Quest Lines",  $rows ));
}


function GetQuestLineById($id)
{
    $conn = $GLOBALS["conn"];
    $stmt = $conn->prepare("SELECT * FROM v_quest_line_info WHERE Id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0)
    {
        $row = $result->fetch_assoc();
        $stmt->close();
        return (new APIResponse(true, "Quest Line Information.", $row));
    }
    else
    {
        $stmt->close();
        return (new APIResponse(false, "We couldn't find a quest line with that id.", null));
    }
}


function UpdateQuestLineContent($questLineId, $contentId)
{
    global $conn; 
    // Assuming the quest_line table structure is similar and has a content_id column
    $stmt = $conn->prepare("UPDATE quest_line SET content_id = ? WHERE Id = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $contentId, $questLineId);
    mysqli_stmt_execute($stmt);

    // Assuming the APIResponse class is used similarly for success/failure notification
    if(mysqli_stmt_affected_rows($stmt) > 0) {
        return (new APIResponse(true, "Quest line content updated successfully.", null));
    } else {
        return (new APIResponse(false, "Failed to update quest line content or no changes made.", null));
    }
}


function InsertNewQuestLine()
{
    if (IsQuestGiver())
    {
        global $conn;
        $questLineName = "New Quest Line";
        $questLineLocator = "new-quest-line-" . $_SESSION["account"]["Id"];

        // Assuming GetQuestLineByLocator is a function you have for checking quest lines
        $questLineResp = GetQuestLineByLocator($questLineLocator);

        if (!$questLineResp->Success)
        {
            $stmt = $conn->prepare("INSERT INTO quest_line (name, locator, created_by_id) VALUES (?, ?, ?)");
            if (!$stmt) {
                // Prepare failed.
                return new APIResponse(false, "Failed to prepare statement for inserting new quest line.", null);
            }

            mysqli_stmt_bind_param($stmt, 'ssi', $questLineName, $questLineLocator, $_SESSION["account"]["Id"]);
            if (!mysqli_stmt_execute($stmt)) {
                // Execute failed.
                return new APIResponse(false, "Failed to execute statement for inserting new quest line.", null);
            }
            
            $newId = mysqli_insert_id($conn);
            if ($newId == 0) {
                // Insert failed, no new ID generated.
                return new APIResponse(false, "Insert operation failed or did not generate a new ID.", null);
            }

            // Assuming you will fetch the newly inserted quest line
            $questLineResp = GetQuestLineByLocator($questLineLocator);
        }

        // This section seems to imply content handling that's outside the scope of provided details
        if ($questLineResp->Data["content_id"] == null)
        {
            $newContentId = InsertNewContent();
            UpdateQuestLineContent($questLineResp->Data["Id"], $newContentId);

            $questLineResp = GetQuestLineByLocator($questLineLocator);
        }

        return new APIResponse(true, "New quest line created.", $questLineResp->Data);
    }
    else
    {
        return new APIResponse(false, "You do not have permissions to post a new quest line.", null);
    }
}

function IsQuestLineCreator($questLine)
{
    if (IsLoggedIn())
    {
        return ($_SESSION["account"]["Id"] == $questLine["created_by_id"] );
    }
    else{
        return false;
    }
    
}

function CanEditQuestLine($questLine)
{
    return IsQuestLineCreator($questLine) || IsAdmin();
}


function UpdateQuestLineImages($questId, $desktop_banner_id, $mobile_banner_id, $icon_id) {


    $db = $GLOBALS['conn'];

    $questResp = GetQuestLineById($questId);

    if (!$questResp->Success)
    {
        return (new APIResponse(false, "Error updating quest. Could not find quest by Id.", null));
    }
    $quest = $questResp->Data;

    if (!CanEditQuestLine($quest))
    {
        return (new APIResponse(false, "Error updating quest. You do not have permission to edit this quest.", null));
    }

    // Prepare the update statement
    $query = "UPDATE quest_line SET image_id_icon=?, image_id=?, image_id_mobile=?, `published` = 0, `being_reviewed` = 0  WHERE Id=?";
    $stmt = mysqli_prepare($db, $query);

    // Bind the parameters
    mysqli_stmt_bind_param($stmt, 'iiii', $icon_id, $desktop_banner_id, $mobile_banner_id,  $questId);

    // Execute the statement
    $success = mysqli_stmt_execute($stmt);

    // Close the statement
    mysqli_stmt_close($stmt);

    // Check if the update was successful
    if($success) {
        return (new APIResponse(true, "Quest images updated successfully!", null));
    } else {
        return (new APIResponse(false, "Error updating quest images with unknown error.", null));
    }
}

function UpdateQuestLineOptions($data)
{
    $db = $GLOBALS['conn'];

    $questLineId = $data["edit-quest-line-id"];
    $questLineName = $data["edit-quest-line-options-title"];
    $questLineLocator = $data["edit-quest-line-options-locator"];
    $questLineSummary = $data["edit-quest-line-options-summary"];
    

    // Ensure GetQuestLineById and CanEditQuestLine functions are implemented similar to their quest counterparts
    $questLineResp = GetQuestLineById($questLineId);
    if (!$questLineResp->Success)
    {
        return (new APIResponse(false, "Error updating quest line. Could not find quest line by Id.", null));
    }

    $questLine = $questLineResp->Data;
    if (!CanEditQuestLine($questLine))
    {
        return (new APIResponse(false, "Error updating quest line. You do not have permission to edit this quest line.", null));
    }

    // Prepare the update statement, adjust field names according to your quest_line table
    $stmt = $db->prepare("UPDATE quest_line SET name = ?, locator = ?, `desc` = ?, `published` = 0, `being_reviewed` = 0 WHERE Id = ?");
    if (false === $stmt) {
        // Error handling, e.g., log or throw
        return (new APIResponse(false, "Error preparing the update statement.", null));
    }

    // Bind the parameters
    $stmt->bind_param('sssi', $questLineName, $questLineLocator, $questLineSummary, $questLineId);

    // Execute the statement
    $success = $stmt->execute();

    // Close the statement
    $stmt->close();

    if ($success) {
        // Determine if the locator was changed
        $locatorChanged = $questLine["locator"] != $questLineLocator;
        
        // Construct a data object to return more explicit information
        $responseData = (object)[
            'locator' => $questLineLocator, // Always return the current locator
            'locatorChanged' => $locatorChanged // Explicitly indicate if the locator was changed
        ];
    
        return new APIResponse(true, "Quest line options updated successfully!", $responseData);
    } else {
        // Consider more detailed error handling or logging here
        return new APIResponse(false, "Error updating quest line options with unknown error.", null);
    }
    
}

function SubmitQuestLineForReview($data) {
    // Access the global database connection
    $db = $GLOBALS['conn'];

    // Retrieve the quest line ID from the provided data
    $questLineId = $data["quest-line-id"];
    
    // Fetch the quest line details to check its existence and editability
    $questLineResp = GetQuestLineById($questLineId);
    if (!$questLineResp->Success) {
        return new APIResponse(false, "Quest line submission failed. Quest line not found.", null);
    }

    // Verify editing permissions for the quest line
    $questLine = $questLineResp->Data;
    if (!CanEditQuestLine($questLine)) {
        return new APIResponse(false, "Submission denied. Insufficient permissions to edit this quest line.", null);
    }

    // Prepare the SQL statement to mark the quest line as being reviewed
    $stmt = $db->prepare("UPDATE quest_line SET published = 0, being_reviewed = 1 WHERE Id = ?");
    if (!$stmt) {
        // Handle preparation errors
        return new APIResponse(false, "Failed to prepare the review submission statement.", null);
    }

    // Bind the quest line ID to the statement
    $stmt->bind_param('i', $questLineId);

    // Execute the update statement
    if (!$stmt->execute()) {
        // Handle execution errors
        $stmt->close();
        return new APIResponse(false, "Quest line review submission failed due to an execution error.", null);
    }

    // Close the prepared statement
    $stmt->close();

    // Successfully updated the quest line status to being reviewed
    return new APIResponse(true, "Quest line successfully submitted for review.", null);
}


function ApproveQuestLineReview($data) {
    // Access the global database connection
    $db = $GLOBALS['conn'];

    // Retrieve the quest line ID from the provided data
    $questLineId = $data["quest-line-id"];
    
    if (!IsAdmin()) {
        return new APIResponse(false, "Approval denied. Insufficient permissions to edit this quest line.", null);
    }

    // Prepare the SQL statement to mark the quest line as being reviewed
    $stmt = $db->prepare("UPDATE quest_line SET published = 1, being_reviewed = 0 WHERE Id = ?");
    if (!$stmt) {
        // Handle preparation errors
        return new APIResponse(false, "Failed to prepare the review approval statement.", null);
    }

    // Bind the quest line ID to the statement
    $stmt->bind_param('i', $questLineId);

    // Execute the update statement
    if (!$stmt->execute()) {
        // Handle execution errors
        $stmt->close();
        return new APIResponse(false, "Quest line review approval failed due to an execution error.", null);
    }

    // Close the prepared statement
    $stmt->close();

    // Successfully updated the quest line status to being reviewed
    return new APIResponse(true, "Quest line successfully approved and published.", null);
}


function RejectQuestLineReview($data) {
    // Access the global database connection
    $db = $GLOBALS['conn'];

    // Retrieve the quest line ID from the provided data
    $questLineId = $data["quest-line-id"];
    
    if (!IsAdmin()) {
        return new APIResponse(false, "Rejection denied. Insufficient permissions to edit this quest line.", null);
    }

    // Prepare the SQL statement to mark the quest line as being reviewed
    $stmt = $db->prepare("UPDATE quest_line SET published = 0, being_reviewed = 0 WHERE Id = ?");
    if (!$stmt) {
        // Handle preparation errors
        return new APIResponse(false, "Failed to prepare the review rejection statement.", null);
    }

    // Bind the quest line ID to the statement
    $stmt->bind_param('i', $questLineId);

    // Execute the update statement
    if (!$stmt->execute()) {
        // Handle execution errors
        $stmt->close();
        return new APIResponse(false, "Quest line review rejection failed due to an execution error.", null);
    }

    // Close the prepared statement
    $stmt->close();

    // Successfully updated the quest line status to being reviewed
    return new APIResponse(true, "Quest line publish rejected.", null);
}


?>