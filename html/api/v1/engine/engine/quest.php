<?php


///quest
function QuestNameIsValid($questName) {
    $valid = StringIsValid($questName, 10);
    if ($valid) 
    {
        if (strtolower($questName) == "new quest")
            $valid = false;
    }

    return $valid;
}

function QuestSummaryIsValid($questSummary) {
    $valid = StringIsValid($questSummary, 200);

    return $valid;
}

function QuestPageContentIsValid($pageContent)
{

    return count($pageContent) > 0;
}

function QuestLocatorIsValid($locator)
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

function QuestImageIsValid($media_id)
{
    return isset($media_id) && !is_null($media_id);
}

function QuestImagesAreValid($quest)
{
    return QuestImageIsValid($quest["image_id"]) && QuestImageIsValid($quest["image_id_icon"]) && QuestImageIsValid($quest["image_id_mobile"]);
}

function QuestRewardsAreValid($questRewards) {
    return count($questRewards) > 0;
}

function QuestIsValidForPublish($quest, $pageContent, $questRewards)
{
    return QuestNameIsValid($quest["name"]) && QuestSummaryIsValid($quest["summary"]) && QuestLocatorIsValid($quest["locator"]) && QuestPageContentIsValid($pageContent) && QuestImageIsValid($quest["imagePath_icon"]) && QuestRewardsAreValid($questRewards);
}

function IsQuestHost($quest)
{
    if (IsLoggedIn())
    {
        return ($_SESSION["account"]["Id"] == $quest["host_id"] || $_SESSION["account"]["Id"] == $quest["host_id_2"] );
    }
    else{
        return false;
    }
    
}

function CanEditQuest($quest)
{
    return IsQuestHost($quest) || IsAdmin();
}

function UpdateQuestImages($questId, $desktop_banner_id, $mobile_banner_id, $icon_id) {


    $db = $GLOBALS['conn'];

    $questResp = GetQuestById($questId);

    if (!$questResp->Success)
    {
        return (new APIResponse(false, "Error updating quest. Could not find quest by Id.", null));
    }
    $quest = $questResp->Data;

    if (!CanEditQuest($quest))
    {
        return (new APIResponse(false, "Error updating quest. You do not have permission to edit this quest.", null));
    }

    // Prepare the update statement
    $query = "UPDATE quest SET image_id_icon=?, image_id=?, image_id_mobile=?, `published` = 0, `being_reviewed` = 0 WHERE Id=?";
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

function UpdateQuestOptions($data)
{
    
    $db = $GLOBALS['conn'];

    $questId = $data["edit-quest-id"];
    $questName = $data["edit-quest-options-title"];
    $questLocator = $data["edit-quest-options-locator"];
    $questHostId2 = $data["edit-quest-options-host-2-id"];
    $questSummary = $data["edit-quest-options-summary"];
    $hasADate = isset($data["edit-quest-options-has-a-date"]);
    $dateTime = $data["edit-quest-options-datetime"];
    $playStyle = $data["edit-quest-options-style"];
    $questLineId = $data["edit-quest-options-questline"];
    $questLineIdValue = empty($questLineId) ? NULL : $questLineId;

    $questResp = GetQuestById($questId);

    if (!$questResp->Success)
    {
        return (new APIResponse(false, "Error updating quest. Could not find quest by Id.", null));
    }
    $quest = $questResp->Data;

    if (StringStartsWith($questLocator, 'new-quest-'))
    {
        if ($questLocator != 'new-quest-'.$quest["host_id"])
        {
            return (new APIResponse(false, "Cannot change the quest locator to ".$questLocator, null));
        }
    }

    if (!CanEditQuest($quest))
    {
        return (new APIResponse(false, "Error updating quest. You do not have permission to edit this quest.", null));
    }

    // Prepare the update statement
    $query = "UPDATE quest SET name = ?, locator = ?, host_id_2 = ?, summary = ?, end_date = ?, play_style = ?, quest_line_id = ?, `published` = 0, `being_reviewed` = 0 WHERE Id = ?";
    $stmt = mysqli_prepare($db, $query);

    // Determine the value for host_id_2 and end_date
    $host_id_2 = empty($questHostId2) ? NULL : $questHostId2;
    $end_date = $hasADate && !empty($dateTime) ? $dateTime : NULL;
    
    //$date = $data["edit-quest-options-datetime-date"];
    //$time = $data["edit-quest-options-datetime-time"];
    //$end_date = $hasADate && !empty($date) && !empty($time) ? $date . ' ' . $time . ":00": NULL;

    // Bind the parameters
    mysqli_stmt_bind_param($stmt, 'ssissiii', $questName, $questLocator, $host_id_2, $questSummary, $end_date, $playStyle, $questLineIdValue, $questId);


    // Execute the statement
    $success = mysqli_stmt_execute($stmt);

    // Close the statement
    mysqli_stmt_close($stmt);

    if($success) {
        $locatorChanged = $quest["locator"] != $questLocator;
        
        // Construct a data object to return more explicit information
        $responseData = (object)[
            'locator' => $questLocator, // Always return the current locator
            'locatorChanged' => $locatorChanged // Explicitly indicate if the locator was changed
        ];
        return (new APIResponse(true, "Quest options updated successfully!", $responseData));
    } else {
        return (new APIResponse(false, "Error updating quest options with unknown error.", null));
    }
}

function SubmitQuestForReview($data) {
    // Access the global database connection
    $db = $GLOBALS['conn'];

    // Retrieve the quest ID from the provided data
    $questId = $data["quest-id"];
    
    // Fetch the quest details to check its existence and editability
    $questResp = GetQuestById($questId); // Corrected variable name
    if (!$questResp->Success) {
        return new APIResponse(false, "Quest submission failed. Quest not found.", null);
    }

    // Verify editing permissions for the quest
    $quest = $questResp->Data; // Corrected variable name
    if (!CanEditQuest($quest)) { // Corrected function call
        return new APIResponse(false, "Submission denied. Insufficient permissions to edit this quest.", null);
    }

    // Prepare the SQL statement to mark the quest as being reviewed
    $stmt = $db->prepare("UPDATE quest SET published = 0, being_reviewed = 1 WHERE Id = ?"); // Corrected table name
    if (!$stmt) {
        // Handle preparation errors
        return new APIResponse(false, "Failed to prepare the review submission statement.", null);
    }

    // Bind the quest ID to the statement
    $stmt->bind_param('i', $questId); // Corrected variable name

    // Execute the update statement
    if (!$stmt->execute()) {
        // Handle execution errors
        $stmt->close();
        return new APIResponse(false, "Quest review submission failed due to an execution error.", null);
    }

    // Close the prepared statement
    $stmt->close();

    // Successfully updated the quest status to being reviewed
    return new APIResponse(true, "Quest successfully submitted for review. This may take a few days to be reviewed. Thank you for your patience.", null);
}


function ApproveQuestReview($data) {
    // Access the global database connection
    $db = $GLOBALS['conn'];

    // Retrieve the quest ID from the provided data
    $questId = $data["quest-id"];
    
    if (!IsAdmin()) {
        return new APIResponse(false, "Approval denied. Insufficient permissions to edit this quest.", null);
    }

    // Prepare the SQL statement to mark the quest as being reviewed
    $stmt = $db->prepare("UPDATE quest SET published = 1, being_reviewed = 0 WHERE Id = ?");
    if (!$stmt) {
        // Handle preparation errors
        return new APIResponse(false, "Failed to prepare the review approval statement.", null);
    }

    // Bind the quest ID to the statement
    $stmt->bind_param('i', $questId);

    // Execute the update statement
    if (!$stmt->execute()) {
        // Handle execution errors
        $stmt->close();
        return new APIResponse(false, "Quest review approval failed due to an execution error.", null);
    }

    // Close the prepared statement
    $stmt->close();

    // Successfully updated the quest status to being reviewed
    return new APIResponse(true, "Quest successfully approved and published.", null);
}

function RejectQuestReviewById($questId)
{
     // Access the global database connection
     $db = $GLOBALS['conn'];
     
     if (!IsAdmin()) {
         return new APIResponse(false, "Rejection denied. Insufficient permissions to edit this quest.", null);
     }
 
     // Prepare the SQL statement to mark the quest as being reviewed
     $stmt = $db->prepare("UPDATE quest SET published = 0, being_reviewed = 0 WHERE Id = ?");
     if (!$stmt) {
         // Handle preparation errors
         return new APIResponse(false, "Failed to prepare the review rejection statement.", null);
     }
 
     // Bind the quest ID to the statement
     $stmt->bind_param('i', $questId);
 
     // Execute the update statement
     if (!$stmt->execute()) {
         // Handle execution errors
         $stmt->close();
         return new APIResponse(false, "Quest review rejection failed due to an execution error.", null);
     }
 
     // Close the prepared statement
     $stmt->close();
 
     // Successfully updated the quest status to being reviewed
     return new APIResponse(true, "Quest publish rejected.", null);
}

function RejectQuestReview($data) {
    // Retrieve the quest ID from the provided data
    $questId = $data["quest-id"];
    
    return RejectQuestReviewById($questId);
}

function GetAllQuestGivers()
{
    // Prepare the SQL statement
    $stmt = mysqli_prepare($GLOBALS["conn"], "SELECT * FROM v_account_info WHERE IsQuestGiver = 1 ORDER BY level DESC, exp_current DESC");
    
    // Execute the SQL statement
    mysqli_stmt_execute($stmt);

    // Get the result of the SQL query
    $result = mysqli_stmt_get_result($stmt);

    $num_rows = mysqli_num_rows($result);
    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

    // Free the statement
    mysqli_stmt_close($stmt);
    
    if ($num_rows === 0) {
        return (new APIResponse(false, "Couldn't find quest givers", null));
    } else {
        return (new APIResponse(true, "All quest givers",  $rows ));
    }
}


function GetQuestRewardsByQuestId($id)
{
    // Prepare the SQL statement
    $stmt = mysqli_prepare($GLOBALS["conn"], "SELECT * FROM v_quest_reward_info WHERE quest_id = ?");

    // Bind the parameter to the placeholder in the SQL statement
    mysqli_stmt_bind_param($stmt, "i", $id); // "i" signifies that the parameter is an integer

    // Execute the prepared statement
    mysqli_stmt_execute($stmt);

    // Store the result of the query
    $result = mysqli_stmt_get_result($stmt);

    $num_rows = mysqli_num_rows($result);
    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

    // Free the result & close the statement
    mysqli_free_result($result);
    mysqli_stmt_close($stmt);

    return (new APIResponse(true, "Quest Rewards Loaded",  $rows ));
}

function GetQuestByLocator($name)
{
    $name = mysqli_real_escape_string($GLOBALS["conn"], $name);
    $sql = "SELECT * from v_quest_info where locator = '$name'";

    $result = mysqli_query($GLOBALS["conn"],$sql);

    $num_rows = mysqli_num_rows($result);
    if ($num_rows > 0)
    {
        $row = mysqli_fetch_assoc($result);
    
        if ($row != null)
            return (new APIResponse(true, "Quest Information.",  $row ));
    }
    return (new APIResponse(false, "Couldn't find a quest with that locator", null));
}

function GetQuestsByQuestLineId($questLineId, $page = 1, $itemsPerPage = 10) {
    $conn = $GLOBALS["conn"];
    $offset = ($page - 1) * $itemsPerPage;

    // Use placeholders for parameters in your SQL query
    $sql = "SELECT f.* FROM kickbackdb.v_feed f
            LEFT JOIN quest q ON f.Id = q.Id 
            WHERE f.published = 1 AND q.quest_line_id = ? AND f.type = 'QUEST'
            LIMIT ? OFFSET ?";

    // Prepare the SQL statement
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        // Handle error properly
        return new APIResponse(false, "Failed to prepare the SQL statement.", null);
    }

    // Bind the parameters to the prepared statement
    // "iii" denotes the types of the variables passed: 3 integers.
    mysqli_stmt_bind_param($stmt, 'iii', $questLineId, $itemsPerPage, $offset);

    // Execute the prepared statement
    mysqli_stmt_execute($stmt);

    // Get the result of the query
    $result = mysqli_stmt_get_result($stmt);

    if (!$result) {
        // Handle error properly
        return new APIResponse(false, "Failed to execute the query.", null);
    }

    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

    // Cleanup
    mysqli_stmt_close($stmt);

    return new APIResponse(true, "Available Quests", $rows);
}


function GetQuestByRaffleId($raffle_id) {
    $stmt = mysqli_prepare($GLOBALS["conn"], "SELECT * FROM v_quest_info WHERE raffle_id = ?");
    
    if (!$stmt) {
        return new APIResponse(false, "Failed to prepare query", null);
    }

    mysqli_stmt_bind_param($stmt, 'i', $raffle_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $row = mysqli_fetch_assoc($result);
    if ($row != null) {
        return new APIResponse(true, "Quest Information.", $row);
    }
    
    return new APIResponse(false, "We couldn't find a quest with that raffle id", null);
}


function GetQuestById($id)
{
    $id = mysqli_real_escape_string($GLOBALS["conn"], $id);
    $sql = "SELECT * from v_quest_info where Id = $id";

    $result = mysqli_query($GLOBALS["conn"],$sql);

    $num_rows = mysqli_num_rows($result);
    if ($num_rows > 0)
    {
        $row = mysqli_fetch_assoc($result);
    
        if ($row != null)
            return (new APIResponse(true, "Quest Information.",  $row ));
    }
    return (new APIResponse(false, "We couldn't find a quest with that id", null));
}

function GetTBAQuestsFeed($page = 1, $itemsPerPage = 10)
{
    $offset = ($page - 1) * $itemsPerPage;
    if (IsLoggedIn())
    {
        if (IsAdmin())
        {
            // Prepare the SQL statement
            $sql = "SELECT * FROM kickbackdb.v_feed WHERE type = 'QUEST' and published = 0 order by date desc";
            $stmt = mysqli_prepare($GLOBALS["conn"], $sql);
            if(!$stmt) {
                // Handle error, maybe return an API response indicating the error
                return new APIResponse(false, "Database error", []);
            }
    
            //mysqli_stmt_bind_param($stmt, "ii", $_SESSION["account"]["Id"], $_SESSION["account"]["Id"]);

        }
        else
        {
            // Prepare the SQL statement
            $sql = "SELECT * FROM kickbackdb.v_feed WHERE type = 'QUEST' and published = 0 and (account_1_id = ? or account_2_id = ?) order by date desc";
            $stmt = mysqli_prepare($GLOBALS["conn"], $sql);
            if(!$stmt) {
                // Handle error, maybe return an API response indicating the error
                return new APIResponse(false, "Database error", []);
            }
    
            mysqli_stmt_bind_param($stmt, "ii", $_SESSION["account"]["Id"], $_SESSION["account"]["Id"]);

        }

        // Execute the statement
        if(mysqli_stmt_execute($stmt))
        {
            $result = mysqli_stmt_get_result($stmt);
            $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
            return new APIResponse(true, "TBA Quests", $rows);
        }
        else 
        {
            // Handle execution error, maybe return an API response indicating the error
            return new APIResponse(false, "Query execution error", []);
        }
    }
    else
    {
        return new APIResponse(true, "TBA Quests", []);
    }
}

function GetAvailableQuestsFeed($page = 1, $itemsPerPage = 10)
{
    $offset = ($page - 1) * $itemsPerPage;
    $sql = "SELECT * FROM kickbackdb.v_feed WHERE type = 'QUEST' and date > CURRENT_TIMESTAMP and published = 1 order by date asc";

    
    $result = mysqli_query($GLOBALS["conn"],$sql);

    $num_rows = mysqli_num_rows($result);
    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
    return (new APIResponse(true, "Available Quests",  $rows ));
}

function GetArchivedQuestsFeed($page = 1, $itemsPerPage = 10)
{
    $offset = ($page - 1) * $itemsPerPage;
    $sql = "SELECT * FROM kickbackdb.v_feed WHERE type = 'QUEST' and date <= CURRENT_TIMESTAMP and published = 1 and finished = 1 order by date desc";

    
    $result = mysqli_query($GLOBALS["conn"],$sql);

    $num_rows = mysqli_num_rows($result);
    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
    return (new APIResponse(true, "Available Quests",  $rows ));
}

function GetAllQuests()
{
    $sql = "SELECT * FROM kickbackdb.v_quest_info where published = 1 order by end_date desc";

    
    $result = mysqli_query($GLOBALS["conn"],$sql);

    $num_rows = mysqli_num_rows($result);
    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
    return (new APIResponse(true, "Available Quests",  $rows ));

}

function UpdateQuestContent($questId, $contentId)
{
    global $conn; 
    $stmt = $conn->prepare("UPDATE quest SET content_id = ? WHERE Id = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $contentId, $questId);
    mysqli_stmt_execute($stmt);

    return (new APIResponse(true, "Quest content updated.", null));
}

function InsertNewQuest()
{
    if (IsQuestGiver())
    {
        
        global $conn;
        $questName = "New Quest";
        $questLocator = "new-quest-".$_SESSION["account"]["Id"];

        $questResp = GetQuestByLocator($questLocator);
        if (!$questResp->Success)
        {

            $stmt = $conn->prepare("INSERT INTO quest (name, locator, host_id) values (?,?,?)");
            mysqli_stmt_bind_param($stmt, 'ssi', $questName, $questLocator, $_SESSION["account"]["Id"]);
            mysqli_stmt_execute($stmt);
            $newId = mysqli_insert_id($conn);
            $questResp = GetQuestByLocator($questLocator);
            $rewardResp = SetupStandardParticipationRewards($newId);
        }
        if ($questResp->Data["content_id"] == null)
        {
            $newContentId = InsertNewContent();
            
            UpdateQuestContent($questResp->Data["Id"],$newContentId);

            $questResp = GetQuestByLocator($questLocator);
        }

        return (new APIResponse(true, "New quest created.", $questResp->Data));
    }
    else{
        return (new APIResponse(false, "You do not have permissions to post a new quest.", null));
    }
}

function GetArchivedQuests()
{
    $id = mysqli_real_escape_string($GLOBALS["conn"], $id);
    $sql = "SELECT * FROM kickbackdb.v_quest_info  WHERE end_date <= CURRENT_TIMESTAMP and published = 1 and finished = 1 order by end_date desc";

    
    $result = mysqli_query($GLOBALS["conn"],$sql);

    $num_rows = mysqli_num_rows($result);
    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
    return (new APIResponse(true, "Available Quests",  $rows ));
}

function GetTBAQuests()
{
    if (IsLoggedIn())
    {
        if (IsAdmin())
        {
            // Prepare the SQL statement
            $sql = "SELECT * FROM kickbackdb.v_quest_info WHERE published = 0 order by end_date desc";
            $stmt = mysqli_prepare($GLOBALS["conn"], $sql);
            if(!$stmt) {
                // Handle error, maybe return an API response indicating the error
                return new APIResponse(false, "Database error", []);
            }
    
            //mysqli_stmt_bind_param($stmt, "ii", $_SESSION["account"]["Id"], $_SESSION["account"]["Id"]);

        }
        else
        {
            // Prepare the SQL statement
            $sql = "SELECT * FROM kickbackdb.v_quest_info WHERE published = 0 and (host_id = ? or host_id_2 = ?) order by end_date desc";
            $stmt = mysqli_prepare($GLOBALS["conn"], $sql);
            if(!$stmt) {
                // Handle error, maybe return an API response indicating the error
                return new APIResponse(false, "Database error", []);
            }
    
            mysqli_stmt_bind_param($stmt, "ii", $_SESSION["account"]["Id"], $_SESSION["account"]["Id"]);

        }

        // Execute the statement
        if(mysqli_stmt_execute($stmt))
        {
            $result = mysqli_stmt_get_result($stmt);
            $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
            return new APIResponse(true, "TBA Quests", $rows);
        }
        else 
        {
            // Handle execution error, maybe return an API response indicating the error
            return new APIResponse(false, "Query execution error", []);
        }
    }
    else
    {
        return new APIResponse(true, "TBA Quests", []);
    }
}

function GetAvailableQuests()
{
    $id = mysqli_real_escape_string($GLOBALS["conn"], $id);
    $sql = "SELECT * FROM kickbackdb.v_quest_info  WHERE end_date > CURRENT_TIMESTAMP and published = 1 order by end_date asc";

    
    $result = mysqli_query($GLOBALS["conn"],$sql);

    $num_rows = mysqli_num_rows($result);
    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
    return (new APIResponse(true, "Available Quests",  $rows ));
}

function SubmitRaffleTicket($account_id, $raffle_id)
{
    $loot_id = GetUnusedAccountRaffleTicket($account_id)->Data;
    $raffle_id = mysqli_real_escape_string($GLOBALS["conn"], $raffle_id);

    $sql = "INSERT INTO raffle_submissions (raffle_id, loot_id) VALUES ($raffle_id,$loot_id);";
    $result = mysqli_query($GLOBALS["conn"],$sql);
    if ($result === TRUE) {

        return (new APIResponse(true, "Submitted Raffle Ticket!",null));
    } 
    else 
    {
        return (new APIResponse(false, "Failed to submit raffle ticket with error: ".GetSQLError(), null));
    }

}

function GetRaffleParticipants($raffle_id)
{
    // Use the mysqli connection from the global scope
    $conn = $GLOBALS["conn"];
    
    // Prepare the SQL statement
    $stmt = mysqli_prepare($conn, "SELECT * FROM v_raffle_participants WHERE raffle_id = ?");

    // Bind the raffle_id parameter to the SQL statement
    mysqli_stmt_bind_param($stmt, 'i', $raffle_id);

    // Execute the SQL statement
    mysqli_stmt_execute($stmt);

    // Get the result of the SQL query
    $result = mysqli_stmt_get_result($stmt);

    $num_rows = mysqli_num_rows($result);
    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

    // Free the statement
    mysqli_stmt_close($stmt);

    return (new APIResponse(true, "Raffle Participants",  $rows ));
}

function CheckSpecificParticipationRewardsExistById($questRewardsByCategory) {
    // Define the specific reward Ids to check for
    $specificRewardIds = [3, 4, 15];
    
    // Check if 'Participation' category exists
    if (!isset($questRewardsByCategory['Participation'])) {
        return false;
    }

    // Extract Ids of the rewards in the Participation category
    $participationRewardIds = array_map(function($reward) {
        return $reward['Id'];
    }, $questRewardsByCategory['Participation']);

    // Check for the existence of each specific reward Id
    foreach ($specificRewardIds as $specificRewardId) {
        if (!in_array($specificRewardId, $participationRewardIds)) {
            return false;
        }
    }

    // If all specific reward Ids are found, return true
    return true;
}

function SetupStandardParticipationRewards($questId)
{
    $resp = RemoveStandardParticipationRewards($questId);

    if ($resp->Success)
    {
        return AddStandardParticipationRewards($questId);
    }
    else
    {
        return $resp;
    }

}

function RemoveStandardParticipationRewards($questId) {
    $questResp = GetQuestById($questId);
    $quest = $questResp->Data;
    if (!CanEditQuest($quest))
    {
        return new APIResponse(false, "You do not have permissions to edit this quest.", null);
    }

    $removeRewardResp = RejectQuestReviewById($questId);
    if (!$removeRewardResp->Success)
    {
        return $removeRewardResp;
    }


    $conn = $GLOBALS["conn"];
    
    // SQL to delete specific standard participation rewards for the given questId
    $sql = "DELETE FROM quest_reward WHERE quest_id = ? AND item_id IN (3, 4, 15)";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $questId);
    $result = mysqli_stmt_execute($stmt);

    if ($result) {
        // Successful deletion
        mysqli_stmt_close($stmt);
        return new APIResponse(true, "Successfully removed standard participation rewards for quest ID: $questId", null);
    } else {
        // Deletion failed, capture error
        $error = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        return new APIResponse(false, "Failed to remove standard participation rewards for quest ID: $questId. Error: $error", null);
    }
}

function AddStandardParticipationRewards($questId) {
    $questResp = GetQuestById($questId);
    $quest = $questResp->Data;
    if (!CanEditQuest($quest))
    {
        return new APIResponse(false, "You do not have permissions to edit this quest.", null);
    }
    
    $addRewardResp = RejectQuestReviewById($questId);
    if (!$addRewardResp->Success)
    {
        return $addRewardResp;
    }


    $conn = $GLOBALS["conn"];
    // Predefined standard reward IDs
    $standardRewardIds = [3, 4, 15];
    $success = true;
    $errorMessages = [];

    foreach ($standardRewardIds as $rewardId) {
        $sql = "INSERT INTO quest_reward (quest_id, item_id, category, participation) VALUES (?, ?, 'Participation',1)";
        
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            $success = false;
            $errorMessages[] = "Failed to prepare statement: " . mysqli_error($conn);
            break; // Optionally remove this break to attempt inserting all rewards even if one fails
        }

        mysqli_stmt_bind_param($stmt, 'ii', $questId, $rewardId);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if (!$result) {
            $success = false;
            $errorMessages[] = "Failed to insert reward ID $rewardId for quest ID $questId: " . mysqli_error($conn);
            // Optionally break here to stop at the first error, or remove to try inserting all rewards
            break;
        }
    }

    if ($success) {
        return new APIResponse(true, "Successfully added standard participation rewards for quest ID: $questId", null);
    } else {
        // Join all error messages into a single string if there are multiple
        $errorMessage = implode(" | ", $errorMessages);
        return new APIResponse(false, "Error adding standard participation rewards: $errorMessage", null);
    }
}

function GetSubmittedRaffleTickets($raffle_id)
{
    //SELECT Id FROM kickbackdb.raffle_submissions where raffle_id = 1
    $raffle_id = mysqli_real_escape_string($GLOBALS["conn"], $raffle_id);

    $sql = "SELECT Id FROM kickbackdb.raffle_submissions where raffle_id = $raffle_id";

    
    $result = mysqli_query($GLOBALS["conn"],$sql);

    $num_rows = mysqli_num_rows($result);
    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
    return (new APIResponse(true, "Raffle Ticket Submissions",  $rows ));
}

function CheckIfTimeForRaffleWinner($quest)
{

    $date_from_db = new DateTime($quest["end_date"]);

    // Get current date
    $current_date = new DateTime();
    $current_date->modify('+10 seconds');
    // Check if the date from the DB has passed
    if ($date_from_db <= $current_date) {
        ChooseRaffleWinner($quest["raffle_id"]);
    }
}

function ChooseRaffleWinner($raffle_id)
{
    
    $raffle_id = mysqli_real_escape_string($GLOBALS["conn"], $raffle_id);
    

    $submittedTicketsResp = GetSubmittedRaffleTickets($raffle_id);
    $submittedTickets = $submittedTicketsResp->Data;
    $totalTickets = sizeof($submittedTickets);
    if ($totalTickets > 0){
        $winnerIndex = random_int(0,$totalTickets-1);


        $winnerTicketSubmissionId = $submittedTickets[$winnerIndex]["Id"];
    
        //update raffle set winner_submission_id = 4 where Id = 1 and winner_submission_id is null;
    
        
        $sql = "update raffle set winner_submission_id = $winnerTicketSubmissionId where Id = $raffle_id and winner_submission_id is null;";
        $result = mysqli_query($GLOBALS["conn"],$sql);
        $affectedRows = mysqli_affected_rows($GLOBALS["conn"]);
        if ($result === TRUE) {
            if ($affectedRows > 0)
            {
                $raffleQuest = GetQuestByRaffleId($raffle_id)->Data;
                $raffleWinner = GetRaffleWinner($raffle_id)->Data;
                $msg = GetRaffleWinnerAnnouncement($raffleQuest["name"], $raffleWinner[0]["Username"]);
                DiscordWebHook($msg);
            }
            return (new APIResponse(true, "Selected Raffle Winner!", null));
            } else {
            return (new APIResponse(false, "Failed to select raffle winner with error: ".GetSQLError(), null));
            }
    }
    else{
        return (new APIResponse(true, "No raffle tickets were entered!", null));

    }
}

function GetRaffleWinner($raffle_id)
{
    //SELECT * FROM kickbackdb.v_raffle_winners;
    $raffle_id = mysqli_real_escape_string($GLOBALS["conn"], $raffle_id);

    $sql = "SELECT * FROM kickbackdb.v_raffle_winners where raffle_id = $raffle_id";

    
    $result = mysqli_query($GLOBALS["conn"],$sql);

    $num_rows = mysqli_num_rows($result);
    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
    return (new APIResponse(true, "Raffle Ticket Winner",  $rows ));

}

function GetQuestApplicants($questId)
{
    // Use the mysqli connection from the global scope
    $conn = $GLOBALS["conn"];
    
    // Prepare the SQL statement
    $stmt = mysqli_prepare($conn, "SELECT * FROM kickbackdb.v_quest_applicants_account WHERE quest_id = ? ORDER BY seed ASC, exp DESC, prestige DESC");

    // Bind the questId parameter to the SQL statement
    mysqli_stmt_bind_param($stmt, 'i', $questId);

    // Execute the SQL statement
    mysqli_stmt_execute($stmt);

    // Get the result of the SQL query
    $result = mysqli_stmt_get_result($stmt);

    $num_rows = mysqli_num_rows($result);
    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

    // Free the statement
    mysqli_stmt_close($stmt);

    return (new APIResponse(true, "Quest Applicants",  $rows ));
}

function ApplyOrRegisterForQuest($account_id, $quest_id)
{
    $account_id = mysqli_real_escape_string($GLOBALS["conn"], $account_id);
    $quest_id = mysqli_real_escape_string($GLOBALS["conn"], $quest_id);
    $sql = "INSERT INTO quest_applicants (account_id, accepted, quest_id, participated) VALUES ('$account_id', '0', '$quest_id', '0')";
    $result = mysqli_query($GLOBALS["conn"],$sql);
    if ($result === TRUE) {

        $account = GetAccountById($account_id)->Data;
        $quest = GetQuestById($quest_id)->Data;
        DiscordWebHook(GetRandomGreeting().', '.$account['Username'].' just signed up for the '.$quest['name'].' quest.');
        return (new APIResponse(true, "Registered for quest successfully",$login));
    } 
    else 
    {
        return (new APIResponse(false, "Failed to register for quest with error: ".GetSQLError(), null));
    }
}

function GetPlayStyleJSON()
{
    $playStyles = [];
    array_push($playStyles, [PlayStyleToName(0), PlayStyleToDesc(0)]);
    array_push($playStyles, [PlayStyleToName(1), PlayStyleToDesc(1)]);
    array_push($playStyles, [PlayStyleToName(2), PlayStyleToDesc(2)]);
    array_push($playStyles, [PlayStyleToName(3), PlayStyleToDesc(3)]);

    return json_encode($playStyles);
}

function PlayStyleToName($play_style)
{
    switch ($play_style) {
        case 0:
            return "Casual";
            break;
        case 1:
            return "Ranked";

        case 2:
            return "Hardcore";

        case 3:
            return "Roleplay";
    }


    return "Unknown";
}

function PlayStyleToDesc($play_style)
{
    switch ($play_style) {
        case 0:
            return "This refers to a play style or game mode where the primary focus is on fun, relaxation, and social interaction. The rules are usually easier to grasp, the competition level is lower, and there is less emphasis on long-term strategy or high levels of skill. This can also include social activities such as raffles or conversations.";
            break;
        case 1:
            return "Ranked gameplay involves a high level of competition and results will be recorded in the ranking system. Players are typically more dedicated and spend more time honing their skills to compete against other players. Games may involve teams battling against each other or individual players competing for the top spot.";

        case 2:
            return "In this mode, players confront intense challenges underscored by the stern reality of permadeath. A character's demise can be definitive either individually or when an entire team falls. Mastery in skill, precision, and unyielding focus is essential. A deep understanding of game mechanics and unwavering concentration are crucial to navigating this demanding environment and claiming victory.";

        case 3:
            return "In roleplay modes, players assume the roles of characters and create narratives collaboratively. Gameplay may be guided by rules or freeform, but the emphasis is typically on story development, character interaction, and exploration. Players are expected to stay \"in character\" at all times and participate in the collective storytelling.";
    }


    return "Unknown";
}

function GetRaffleWinnerAnnouncement($raffleName, $winnerUsername) {
    return "🎉 Exciting Announcement! 🎉 The $raffleName has come to a thrilling conclusion. Congratulations to $winnerUsername, the lucky winner! We thank everyone who participated and encourage you to stay tuned for more exciting events and opportunities in the future.";
}

?>