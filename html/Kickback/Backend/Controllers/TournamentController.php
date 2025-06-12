<?php
declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use Kickback\Common\Database\Row;
use Kickback\Common\Database\RowInterface;

use Kickback\Backend\Views\vTournament;
use Kickback\Backend\Views\vTournamentResult;
use Kickback\Backend\Models\Response;
use Kickback\Services\Database;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vBracketInfo;
use Kickback\Backend\Views\vGameRecord;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vGame;
use Kickback\Backend\Views\vGameMatch;
use Kickback\Backend\Views\vDateTime;

class TournamentController
{

    /**
    * @return array<vBracketInfo>
    */
    public static function requestTournamentBracketInfos(vRecordId $tournament_id) : array
    {
        $tournamentBracketInfoResp = self::requestTournamentBracketInfosResponse($tournament_id);
        if (!$tournamentBracketInfoResp->success) {
            throw new \Exception($tournamentBracketInfoResp->message);
        }

        // @phpstan-ignore-next-line
        return $tournamentBracketInfoResp->data;
    }

    public static function requestTournamentBracketInfosResponse(vRecordId $tournament_id) : Response
    {
        $conn = Database::getConnection();
        $stmt = mysqli_prepare($conn, "SELECT * FROM v_tournament_bracket_info WHERE tournament_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $tournament_id->crand);
        mysqli_stmt_execute($stmt);
    
        $result = mysqli_stmt_get_result($stmt);
        
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        
        $bracketInfo = [];
        foreach ($rows as $row) {

            $bracketInfo[] = self::row_to_vBracketInfo(Row::from_array($row));
        }
        return (new Response(true, "Tournament Bracket information.",  $bracketInfo ));
    }
    
    /**
    * @return array<vTournamentResult>
    */
    public static function requestTournamentResults(vRecordId $tournament_id) : array
    {
        $tournamentResultResp = self::requestTournamentResultsResponse($tournament_id);
        if (!$tournamentResultResp->success) {
            throw new \Exception($tournamentResultResp->message);
        }

        // @phpstan-ignore-next-line
        return $tournamentResultResp->data;
    }

    public static function requestTournamentResultsResponse(vRecordId $tournament_id) : Response
    {
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

    private static function row_to_vBracketInfo(RowInterface $row) : vBracketInfo
    {
        $bracketInfo = new vBracketInfo();

        $gameRecord = new vGameRecord('', $row->int("Id"));
        $gameRecord->game = new vGame('', $row->int("game_id"));
        $gameRecord->won = ($row->int("win") == 1);
        $gameRecord->teamName = $row->string("team_name");
        $gameRecord->date = new vDateTime($row->string("Date"));

        $bracketInfo->gameRecord = $gameRecord;

        $bracketInfo->account = new vAccount('', $row->int("account_id"));
        $bracketInfo->account->username = $row->string("Username");

        $gameMatch = new vGameMatch('', $row->int("game_match_id"));
        $gameMatch->bracket = $row->int("bracket");
        $gameMatch->round = $row->int("round");
        $gameMatch->match = $row->int("match");
        $gameMatch->set = $row->int("set");

        // Assertion is here to flag this in PHPStan for later.
        assert(true, 'This looks wrong. $characterHint is given $row["desc"] instead of $row["character"]');
        $gameMatch->description   = $gameMatch->description = $row->nstring("desc") ?? "";
        $gameMatch->characterHint = $gameMatch->description = $row->nstring("character") ?? "";

        $bracketInfo->gameMatch = $gameMatch;

        return $bracketInfo;
    }

}


?>
