<?php


function GetTournamentResults($tournament_id)
{
    
    // Use the mysqli connection from the global scope
    $conn = $GLOBALS["conn"];

    // Prepare the SQL statement
    $stmt = mysqli_prepare($conn, "SELECT r.*, a.*, (a.Id = t.team_captain) as team_captain
    FROM kickbackdb.v_tournament_results r
    left join v_account_info a on r.account_id = a.Id
    left join tournament_record t on t.tournament_id = r.tournament_id and t.team_name = r.team_name
    where r.tournament_id = ?
    order by r.win desc, team_captain desc");

    // Bind the raffle_id parameter to the SQL statement
    mysqli_stmt_bind_param($stmt, 'i', $tournament_id);

    // Execute the SQL statement
    mysqli_stmt_execute($stmt);

    // Get the result of the SQL query
    $result = mysqli_stmt_get_result($stmt);

    $num_rows = mysqli_num_rows($result);
    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

    // Free the statement
    mysqli_stmt_close($stmt);

    return (new APIResponse(true, "Champions",  $rows ));
}

?>