<?php


function GetNewsFeed($page = 1, $itemsPerPage = 10)
{
    $offset = ($page - 1) * $itemsPerPage;

    $sql = "SELECT * FROM kickbackdb.v_feed WHERE type in ('QUEST','BLOG-POST') and published = 1 LIMIT ? OFFSET ?";

    // Prepare the statement
    $stmt = mysqli_prepare($GLOBALS["conn"], $sql);

    if (!$stmt) {
        die("Failed to prepare statement: " . mysqli_error($GLOBALS["conn"]));
    }

    // Bind parameters
    mysqli_stmt_bind_param($stmt, "ii", $itemsPerPage, $offset); // "ii" indicates two integer parameters

    // Execute the statement
    if (!mysqli_stmt_execute($stmt)) {
        die("Failed to execute statement: " . mysqli_stmt_error($stmt));
    }

    // Get the result
    $result = mysqli_stmt_get_result($stmt);

    // Fetch all the rows
    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

    // Free the result
    mysqli_free_result($result);

    // Close the statement
    mysqli_stmt_close($stmt);

    return (new APIResponse(true, "news feed", $rows));
}

function GetHomeFeed()
{
    return GetAllQuests();
}

?>