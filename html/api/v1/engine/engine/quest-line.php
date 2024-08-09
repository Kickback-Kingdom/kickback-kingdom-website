<?php



function UpdateQuestLineContent($questLineId, $contentId)
{
    $conn = $GLOBALS["conn"];
    // Assuming the quest_line table structure is similar and has a content_id column
    $stmt = $conn->prepare("UPDATE quest_line SET content_id = ? WHERE Id = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $contentId, $questLineId);
    mysqli_stmt_execute($stmt);

    // Assuming the APIResponse class is used similarly for success/failure notification
    if(mysqli_stmt_affected_rows($stmt) > 0) {
        return (new Kickback\Models\Response(true, "Quest line content updated successfully.", null));
    } else {
        return (new Kickback\Models\Response(false, "Failed to update quest line content or no changes made.", null));
    }
}


function InsertNewQuestLine()
{
    if (Kickback\Services\Session::isQuestGiver())
    {
        $conn = $GLOBALS["conn"];
        $questLineName = "New Quest Line";
        $questLineLocator = "new-quest-line-" . Kickback\Services\Session::getCurrentAccount()->crand;

        // Assuming GetQuestLineByLocator is a function you have for checking quest lines
        $questLineResp = GetQuestLineByLocator($questLineLocator);

        if (!$questLineResp->success)
        {
            $stmt = $conn->prepare("INSERT INTO quest_line (name, locator, created_by_id, `desc`) VALUES (?, ?, ?, '')");
            if (!$stmt) {
                // Prepare failed.
                return new Kickback\Models\Response(false, "Failed to prepare statement for inserting new quest line.", null);
            }

            mysqli_stmt_bind_param($stmt, 'ssi', $questLineName, $questLineLocator, Kickback\Services\Session::getCurrentAccount()->crand);
            if (Kickback\Services\Session::isAdmin())
            {
                if (!mysqli_stmt_execute($stmt)) {
                    error_log(mysqli_error($conn)); // Log the error to the PHP error log
                    return new Kickback\Models\Response(false, "Failed to execute statement for inserting new quest line: " . mysqli_error($conn), null);
                }

            }
            else
            {

                if (!mysqli_stmt_execute($stmt)) {
                    // Execute failed.
                    return new Kickback\Models\Response(false, "Failed to execute statement for inserting new quest line.", null);
                }
            }
            
            
            $newId = mysqli_insert_id($conn);
            if ($newId == 0) {
                // Insert failed, no new ID generated.
                return new Kickback\Models\Response(false, "Insert operation failed or did not generate a new ID.", null);
            }

            // Assuming you will fetch the newly inserted quest line
            $questLineResp = GetQuestLineByLocator($questLineLocator);
            if (!$questLineResp->success)
            {
                return new Kickback\Models\Response(false, "Failed to find newly inserted quest by locator", $questLineLocator);
            }
        }

        // This section seems to imply content handling that's outside the scope of provided details
        if ($questLineResp->data["content_id"] == null)
        {
            $newContentId = InsertNewContent();
            UpdateQuestLineContent($questLineResp->data["Id"], $newContentId);

            $questLineResp = GetQuestLineByLocator($questLineLocator);
            if (!$questLineResp->success)
            {
                return new Kickback\Models\Response(false, "Failed to find newly inserted quest by locator after inserting content record", $questLineLocator);
            }
        }

        if (!$questLineResp->success)
        {
            return new Kickback\Models\Response(false, "Failed to find newly inserted quest.", $questLineResp);
        }

        return new Kickback\Models\Response(true, "New quest line created.", $questLineResp->data);
    }
    else
    {
        return new Kickback\Models\Response(false, "You do not have permissions to post a new quest line.", null);
    }
}



function UpdateQuestLineImages($questId, $desktop_banner_id, $mobile_banner_id, $icon_id) {


    $db = $GLOBALS['conn'];

    $questResp = GetQuestLineById($questId);

    if (!$questResp->success)
    {
        return (new Kickback\Models\Response(false, "Error updating quest. Could not find quest by Id.", null));
    }
    $quest = $questResp->data;

    if (!CanEditQuestLine($quest))
    {
        return (new Kickback\Models\Response(false, "Error updating quest. You do not have permission to edit this quest.", null));
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
        return (new Kickback\Models\Response(true, "Quest images updated successfully!", null));
    } else {
        return (new Kickback\Models\Response(false, "Error updating quest images with unknown error.", null));
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
    if (!$questLineResp->success)
    {
        return (new Kickback\Models\Response(false, "Error updating quest line. Could not find quest line by Id.", null));
    }

    $questLine = $questLineResp->data;
    if (!CanEditQuestLine($questLine))
    {
        return (new Kickback\Models\Response(false, "Error updating quest line. You do not have permission to edit this quest line.", null));
    }

    // Prepare the update statement, adjust field names according to your quest_line table
    $stmt = $db->prepare("UPDATE quest_line SET name = ?, locator = ?, `desc` = ?, `published` = 0, `being_reviewed` = 0 WHERE Id = ?");
    if (false === $stmt) {
        // Error handling, e.g., log or throw
        return (new Kickback\Models\Response(false, "Error preparing the update statement.", null));
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
    
        return new Kickback\Models\Response(true, "Quest line options updated successfully!", $responseData);
    } else {
        // Consider more detailed error handling or logging here
        return new Kickback\Models\Response(false, "Error updating quest line options with unknown error.", null);
    }
    
}

function SubmitQuestLineForReview($data) {
    // Access the global database connection
    $db = $GLOBALS['conn'];

    // Retrieve the quest line ID from the provided data
    $questLineId = $data["quest-line-id"];
    
    // Fetch the quest line details to check its existence and editability
    $questLineResp = GetQuestLineById($questLineId);
    if (!$questLineResp->success) {
        return new Kickback\Models\Response(false, "Quest line submission failed. Quest line not found.", null);
    }

    // Verify editing permissions for the quest line
    $questLine = $questLineResp->data;
    if (!CanEditQuestLine($questLine)) {
        return new Kickback\Models\Response(false, "Submission denied. Insufficient permissions to edit this quest line.", null);
    }

    // Prepare the SQL statement to mark the quest line as being reviewed
    $stmt = $db->prepare("UPDATE quest_line SET published = 0, being_reviewed = 1 WHERE Id = ?");
    if (!$stmt) {
        // Handle preparation errors
        return new Kickback\Models\Response(false, "Failed to prepare the review submission statement.", null);
    }

    // Bind the quest line ID to the statement
    $stmt->bind_param('i', $questLineId);

    // Execute the update statement
    if (!$stmt->execute()) {
        // Handle execution errors
        $stmt->close();
        return new Kickback\Models\Response(false, "Quest line review submission failed due to an execution error.", null);
    }

    // Close the prepared statement
    $stmt->close();

    // Successfully updated the quest line status to being reviewed
    return new Kickback\Models\Response(true, "Quest line successfully submitted for review.", null);
}


function ApproveQuestLineReview($data) {
    // Access the global database connection
    $db = $GLOBALS['conn'];

    // Retrieve the quest line ID from the provided data
    $questLineId = $data["quest-line-id"];
    
    if (!Kickback\Services\Session::isAdmin()) {
        return new Kickback\Models\Response(false, "Approval denied. Insufficient permissions to edit this quest line.", null);
    }

    // Prepare the SQL statement to mark the quest line as being reviewed
    $stmt = $db->prepare("UPDATE quest_line SET published = 1, being_reviewed = 0 WHERE Id = ?");
    if (!$stmt) {
        // Handle preparation errors
        return new Kickback\Models\Response(false, "Failed to prepare the review approval statement.", null);
    }

    // Bind the quest line ID to the statement
    $stmt->bind_param('i', $questLineId);

    // Execute the update statement
    if (!$stmt->execute()) {
        // Handle execution errors
        $stmt->close();
        return new Kickback\Models\Response(false, "Quest line review approval failed due to an execution error.", null);
    }

    // Close the prepared statement
    $stmt->close();

    // Successfully updated the quest line status to being reviewed
    return new Kickback\Models\Response(true, "Quest line successfully approved and published.", null);
}


function RejectQuestLineReview($data) {
    // Access the global database connection
    $db = $GLOBALS['conn'];

    // Retrieve the quest line ID from the provided data
    $questLineId = $data["quest-line-id"];
    
    /*if (!Kickback\Services\Session::isAdmin()) {
        return new Kickback\Models\Response(false, "Rejection denied. Insufficient permissions to edit this quest line.", null);
    }*/

    // Prepare the SQL statement to mark the quest line as being reviewed
    $stmt = $db->prepare("UPDATE quest_line SET published = 0, being_reviewed = 0 WHERE Id = ? and (being_reviewed = 1 or published = 1)");
    if (!$stmt) {
        // Handle preparation errors
        return new Kickback\Models\Response(false, "Failed to prepare the review rejection statement.", null);
    }

    // Bind the quest line ID to the statement
    $stmt->bind_param('i', $questLineId);

    // Execute the update statement
    if (!$stmt->execute()) {
        // Handle execution errors
        $stmt->close();
        return new Kickback\Models\Response(false, "Quest line review rejection failed due to an execution error.", null);
    }

    // Close the prepared statement
    $stmt->close();

    // Successfully updated the quest line status to being reviewed
    return new Kickback\Models\Response(true, "Quest line publish rejected.", null);
}


?>