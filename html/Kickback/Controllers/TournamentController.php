<?php
declare(strict_types=1);

namespace Kickback\Controllers;

use Kickback\Views\vTournament;
use Kickback\Views\vTournamentResult;
use Kickback\Models\Response;
use Kickback\Services\Database;
use Kickback\Views\vRecordId;
use Kickback\Views\vBracketInfo;
use Kickback\Views\vGameRecord;
use Kickback\Views\vAccount;
use Kickback\Views\vGame;
use Kickback\Views\vGameMatch;
use Kickback\Views\vDateTime;

class TournamentController
{
    public static function getTournamentBracketInfo(vRecordId $tournament_id) : Response {
        $conn = Database::getConnection();
        $stmt = mysqli_prepare($conn, "SELECT * FROM v_tournament_bracket_info WHERE tournament_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $tournament_id->crand);
        mysqli_stmt_execute($stmt);
    
        $result = mysqli_stmt_get_result($stmt);
        
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        
        $bracketInfo = [];
        foreach ($rows as $row) {

            $bracketInfo[] = self::row_to_vBracketInfo($row);
        }
        return (new Response(true, "Tournament Bracket information.",  $bracketInfo ));
    }
    
    public static function getTournamentResults(vRecordId $tournament_id) : Response {
        $conn = Database::getConnection();

        // Prepare the SQL statement
        $sql = "SELECT r.*, a.*, (a.Id = t.team_captain) as team_captain
        FROM kickbackdb.v_tournament_results r
        left join v_account_info a on r.account_id = a.Id
        left join tournament_record t on t.tournament_id = r.tournament_id and t.team_name = r.team_name
        where r.tournament_id = ?
        order by r.win desc, team_captain desc";

        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            return new Response(false, "Failed to prepare statement: " . $conn->error, []);
        }

        // Bind the tournament_id parameter to the SQL statement
        $stmt->bind_param('i', $tournament_id->crand);

        // Execute the SQL statement
        if (!$stmt->execute()) {
            return new Response(false, "Failed to execute statement: " . $stmt->error, []);
        }

        // Get the result of the SQL query
        $result = $stmt->get_result();
        if ($result === false) {
            return new Response(false, "Failed to get result: " . $stmt->error, []);
        }

        // Fetch all the rows
        $rows = $result->fetch_all(MYSQLI_ASSOC);

        // Free the result and close the statement
        $result->free();
        $stmt->close();

        // Process the results into vTournamentResult objects
        $tournamentResults = [];
        foreach ($rows as $row) {
            $teamName = $row['team_name'];
            $teamCaptain = (bool)$row['team_captain'];
            $champion = (bool)$row['win'];
            $account = AccountController::row_to_vAccount($row);
            $setsPlayed = (int)$row['sets_played']; // Assuming this field exists

            $tournamentResults[] = new vTournamentResult($teamName, $teamCaptain, $champion, $account, $setsPlayed);
        }

        // Return the response object with the tournament results
        return new Response(true, "Tournament Results", $tournamentResults);
    }

    private static function row_to_vBracketInfo(array $row) : vBracketInfo {
        $bracketInfo = new vBracketInfo();

        $gameRecord = new vGameRecord('',$row["Id"]);
        $gameRecord->game = new vGame('',$row["game_id"]);
        $gameRecord->won = (bool)($row["win"] == 1);
        $gameRecord->teamName = $row["team_name"];
        $gameRecord->date = new vDateTime($row["Date"]);

        $bracketInfo->gameRecord = $gameRecord;

        $bracketInfo->account = new vAccount('',$row["account_id"]);
        $bracketInfo->account->username = $row["Username"];

        $gameMatch = new vGameMatch('', $row["game_match_id"]);
        $gameMatch->bracket = (int)$row["bracket"];
        $gameMatch->round = (int)$row["round"];
        $gameMatch->match = (int)$row["match"];
        $gameMatch->set = (int)$row["set"];
        $gameMatch->description = $gameMatch->description = $row["desc"] ?? "";
        $gameMatch->characterHint = $gameMatch->description = $row["character"] ?? "";
        $bracketInfo->gameMatch = $gameMatch;

        return $bracketInfo;
    }

}


?>