<?php

function GetTournamentBracketInfo($tournament_id)
{
    $stmt = mysqli_prepare($GLOBALS["conn"], "SELECT * FROM v_tournament_bracket_info WHERE tournament_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $tournament_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    
    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
    $num_rows = mysqli_num_rows($result);
    if ($num_rows === 0)
    {
        return (new APIResponse(false, "Couldn't find tournament bracket info Id", null));
    }
    else
    {
        return (new APIResponse(true, "Tournament Bracket information.",  $rows ));
    }
}


?>