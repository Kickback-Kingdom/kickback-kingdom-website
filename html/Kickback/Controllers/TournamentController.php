<?php
declare(strict_types=1);

namespace Kickback\Controllers;

use Kickback\Views\vTournament;
use Kickback\Models\Response;
use Kickback\Services\Database;

class TournamentController
{
    function GetTournamentBracketInfo($tournament_id) : Response {
        $conn = Database::getConnection();
        $stmt = mysqli_prepare($conn, "SELECT * FROM v_tournament_bracket_info WHERE tournament_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $tournament_id);
        mysqli_stmt_execute($stmt);
    
        $result = mysqli_stmt_get_result($stmt);
        
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        $num_rows = mysqli_num_rows($result);
        if ($num_rows === 0)
        {
            return (new Response(false, "Couldn't find tournament bracket info Id", null));
        }
        else
        {
            return (new Response(true, "Tournament Bracket information.",  $rows ));
        }
    }
    
    function GetTournamentResults($tournament_id) : Response {
        $conn = Database::getConnection();

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

        return (new Response(true, "Champions",  $rows ));
    }

}


?>