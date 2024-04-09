<?php

function GetAllGames()
{
    // We don't need the mysqli_real_escape_string line as we're not injecting any external values into the SQL query
    
    // Define SQL query to select all records from the game table
    $sql = "SELECT * FROM v_game_info order by Name";

    // Execute the SQL query
    $result = mysqli_query($GLOBALS["conn"], $sql);

    // Check the number of rows returned
    $num_rows = mysqli_num_rows($result);

    // If there are results, fetch all rows
    if($num_rows > 0) {
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        return (new APIResponse(true, "Available Games",  $rows ));
    }
    
    // If no results were found, return an appropriate message
    return (new APIResponse(false, "No games found", []));
}


function SearchForGame($searchTerm, $page, $itemsPerPage) {
    $db = $GLOBALS['conn'];

    // Add the wildcards to the searchTerm itself and convert to lowercase
    $searchTerm = "%" . strtolower($searchTerm) . "%";

    $offset = ($page - 1) * $itemsPerPage;

    // Convert both the column data and the searchTerm to lowercase
    $countQuery = "SELECT COUNT(*) as total FROM v_game_info WHERE (LOWER(Name) LIKE ? OR LOWER(`Desc`) LIKE ? OR LOWER(ShortName) LIKE ?)";
    $stmtCount = mysqli_prepare($db, $countQuery);
    mysqli_stmt_bind_param($stmtCount, 'sss', $searchTerm, $searchTerm, $searchTerm);

    $query = "SELECT *, 
        (
            (CASE WHEN LOWER(Name) LIKE ? THEN 3 ELSE 0 END) +
            (CASE WHEN LOWER(`ShortName`) LIKE ? THEN 2 ELSE 0 END) +
            (CASE WHEN LOWER(`Desc`) LIKE ? THEN 1 ELSE 0 END)
        ) AS relevancy_score 
        FROM v_game_info 
        WHERE (LOWER(Name) LIKE ? OR LOWER(`Desc`) LIKE ? OR LOWER(ShortName) LIKE ?)
        ORDER BY relevancy_score DESC, `Name`
        LIMIT ? OFFSET ?";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, 'ssssssii', $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $itemsPerPage, $offset);

    // Execute the count statement
    mysqli_stmt_execute($stmtCount);
    $resultCount = mysqli_stmt_get_result($stmtCount);
    $count = mysqli_fetch_assoc($resultCount)["total"];
    mysqli_stmt_close($stmtCount);

    // Execute the main search statement
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $games = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);


    return (new APIResponse(true, "Games", [
        'total' => $count,
        'gameItems' => $games
    ]));
}

?>