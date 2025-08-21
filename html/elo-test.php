<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");

use Kickback\Services\Database;
use Kickback\Backend\Controllers\EloController;

$conn = Database::getConnection();

$eloController = new EloController(1500, 30);

$ratings = [];
$usernames = [];
$matchesPlayed = [];
$wins = [];
$losses = [];

// Fetch all game matches and MinRankedMatches
$result = mysqli_query($conn, 'SELECT 
    gm.Id,
    gm.game_id,
    gm.tournament_id,
    g.name AS game_name,
    g.MinRankedMatches,
    gm.bracket,
    gm.round,
    gm.`match`,
    gm.`set`,
    (gm.`set` = (
        SELECT MAX(s.`set`)
        FROM game_match s
        WHERE s.game_id       = gm.game_id
          AND s.bracket       = gm.bracket
          AND s.round         = gm.round
          AND s.`match`       = gm.`match`
          AND s.tournament_id <=> gm.tournament_id
    )) AS is_last_set,
    CASE
      WHEN gm.tournament_id IS NULL
       AND gm.bracket = 0 AND gm.round = 0 AND gm.`match` = 0
      THEN gm.Id
      ELSE (
        SELECT s2.Id
        FROM game_match s2
        WHERE s2.game_id       = gm.game_id
          AND s2.bracket       = gm.bracket
          AND s2.round         = gm.round
          AND s2.`match`       = gm.`match`
          AND s2.tournament_id <=> gm.tournament_id
        ORDER BY s2.`set` ASC, s2.Id ASC
        LIMIT 1
      )
    END AS first_set_id
FROM game_match gm
LEFT JOIN game g ON g.Id = gm.game_id
ORDER BY
  gm.game_id,
  gm.bracket,
  gm.round,
  gm.`match`,
  gm.`set`,
  gm.Id

');
if (!$result) {
    die("Error fetching game matches: " . mysqli_error($conn));
}
$matches = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_free_result($result);

foreach ($matches as $match) {
    $game_match_id = $match['Id'];
    $game_id = $match['game_id'];
    $game_name = $match['game_name'];
    $minRankedMatches = (int)$match['MinRankedMatches'];
    $bracketNum = (int)$match['bracket'];
    $roundNum = (int)$match['round'];
    $matchNum = (int)$match['match'];
    $setNum = (int)$match['set'];
    $isMatchComplete = ((int)$match['is_last_set'] === 1);
    $first_set_id = (int)$match['first_set_id'];

    echo "<h1>$game_name</h1>";
    echo "<h2>Match $game_match_id Bracket: $bracketNum Round: $roundNum Match: $matchNum Set: $setNum</h2>";
    // Determine if this is a standalone match or part of a larger bracket

    // Prepare and execute SQL statement
    $stmt = mysqli_prepare($conn, 'SELECT game_record.Id, account_id, win, team_name, account.Username FROM game_record left join account on game_record.account_id = account.Id WHERE game_match_id = ? AND game_id = ?');
    if (!$stmt) {
        die("Error preparing statement: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, 'ii', $game_match_id, $game_id);
    if (!mysqli_stmt_execute($stmt)) {
        die("Error executing statement: " . mysqli_stmt_error($stmt));
    }
    $result = mysqli_stmt_get_result($stmt);
    $records = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);

    // Initialize data structures for teams
    $teams = [];
    $teamNames = [];
    foreach ($records as $record) {
        $game_record_id = $record["Id"];
        $accountId = $record['account_id'];
        $teamName = $record['team_name'];
        $username = $record['Username'];
        $win = $record['win'];
        
        // Initialize wins and losses
        if (!isset($wins[$game_id][$accountId])) {
            $wins[$game_id][$accountId] = 0;
            $losses[$game_id][$accountId] = 0;
        }
        if ($win == 1) {
            //$wins[$game_id][$accountId]++;
        } else {
            //$losses[$game_id][$accountId]++;
        }

        // Store the username for each account ID
        $usernames[$accountId] = $username;

        if (!isset($ratings[$game_id][$accountId])) {
            $ratings[$game_id][$accountId] = $eloController->getBaseRating();
            $matchesPlayed[$game_id][$accountId] = 0;  // Initialize match count for this player
        }
        
        // Increment match count for this player
        //$matchesPlayed[$game_id][$accountId]++;

        // Add player to team in $teams
        $teams[$teamName][] = $accountId;

        // Add team name to $teamNames if it isn't there already
        if (!in_array($teamName, $teamNames)) {
            $teamNames[] = $teamName;
            echo "Team: $teamName<br>";
        }
    }
    echo "GameInstance: $game_match_id GameRecord: $game_record_id IsMatchComplete: $isMatchComplete setNum: $setNum<br>";
    if ($isMatchComplete) {
        echo "Match is complete. lets calculate the ELO<br>";

        foreach ($teams as $teamName => $team) {
            foreach ($team as $accountId) {
                $matchesPlayed[$game_id][$accountId]++;
                echo "Account $accountId: Matches Played Incremented. Total: {$matchesPlayed[$game_id][$accountId]}<br>";
    
                if ($records[array_search($accountId, array_column($records, 'account_id'))]['win'] == 1) {
                    $wins[$game_id][$accountId]++;
                    echo "Account $accountId: Win Incremented. Total Wins: {$wins[$game_id][$accountId]}<br>";
                } else {
                    $losses[$game_id][$accountId]++;
                    echo "Account $accountId: Loss Incremented. Total Losses: {$losses[$game_id][$accountId]}<br>";
                }
            }
        }


        $teamCount = count($teams);
        $moreThan2Teams = $teamCount > 2;

        // Compute average ratings for each team
        $averageRatings = [];
        foreach ($teams as $teamName => $team) {
            $averageRatings[$teamName] = $eloController->calculateTeamAverage($ratings[$game_id], $team);
        }
        echo "Average ratings for teams:<br>";
        print_r($averageRatings);
        echo "<br>";
        
        // Create pairs of teams
        $pairs = [];
        for ($i = 0; $i < count($teamNames); $i++) {
            for ($j = $i + 1; $j < count($teamNames); $j++) {
                $pairs[] = [$teamNames[$i], $teamNames[$j]];
            }
        }
        echo "Generated pairs for calculation:<br>";
        print_r($pairs);
        echo "<br>";


        // Get list of winning teams and losing teams
        $winningTeams = [];
        $losingTeams = [];
        foreach ($teams as $teamName => $team) {
            if ($records[array_search($team[0], array_column($records, 'account_id'))]['win'] == 1) {
                $winningTeams[] = $teamName;
            } else {
                $losingTeams[] = $teamName;
            }
        }
        echo "Winning teams:<br>";
        print_r($winningTeams);
        echo "<br>";
        echo "Losing teams:<br>";
        print_r($losingTeams);
        echo "<br>";

        
        $eloUpdates = [];
        // Compute Elo rating updates for each pair of teams
        foreach ($pairs as $pair) {
            echo "<h3>Pair: $pair[0] v $pair[1]</h3>";

            $team0Won = in_array($pair[0], $winningTeams);
            $team1Won = in_array($pair[1], $winningTeams);

            $team0Elo = $averageRatings[$pair[0]];
            $team1Elo = $averageRatings[$pair[1]];

            $isTie = ($team0Won == $team1Won);
            $shouldSkip = ($isTie && $moreThan2Teams); //when teams tie they shouldnt affect each others elo if they are in a group match with more than 2 teams
            
            echo "shouldSkip: $shouldSkip, isTie: $isTie, moreThan2Teams: $moreThan2Teams<br>";

            if ($shouldSkip) {
                echo "Skipping $pair[0] v $pair[1] because they tied on a group game<br>";
                continue; // Skip if this is a tie in a multi-team match
            }
            
            // Compute expected scores
            $expected1 = 1 / (1 + pow(10, ($team1Elo - $team0Elo) / 400));
            $expected2 = 1 / (1 + pow(10, ($team0Elo - $team1Elo) / 400));

            // Set actual scores depending on whether each team won or lost
            $actual1 = $team0Won ? 1 : ($team1Won ? 0 : 0.5);
            $actual2 = $team1Won ? 1 : ($team0Won ? 0 : 0.5);
            
            // Update ratings for all players on each team
            //$eloUpdates = [];
            // Process each team
            foreach ([$pair[0], $pair[1]] as $index => $teamName) {
                $expected = $index === 0 ? $expected1 : $expected2;
                $actual = $index === 0 ? $actual1 : $actual2;

                foreach ($teams[$teamName] as $accountId) {
                    $oldRating = $ratings[$game_id][$accountId];
                    $newRating = round($oldRating + $eloController->getKFactor() * ($actual - $expected));
                    $eloChange = $newRating - $oldRating;

                    echo "<b>$teamName: </b> Account $accountId: Old Rating = $oldRating, New Rating = $newRating, Elo Change = $eloChange<br>";
                    // Update rating
                    $ratings[$game_id][$accountId] = $newRating;

                    // Accumulate `elo_change`
                    if (!isset($eloUpdates[$accountId])) {
                        $eloUpdates[$accountId] = [
                            'elo_change' => 0,
                            'game_match_id' => $first_set_id,
                            'account_id' => $accountId
                        ];
                    }
                    $eloUpdates[$accountId]['elo_change'] += $eloChange;
                }
            }
        }
            
        // Apply ELO changes for this match
        $eloController->batchUpdateEloChange($conn, $eloUpdates);
    }
    else{
        
        echo "Match is not complete.<br>";
    }
}


// Print final ratings
foreach ($ratings as $game_id => $game_ratings) {
    $minRankedMatches = $matches[array_search($game_id, array_column($matches, 'game_id'))]['MinRankedMatches'];
    echo "<h1>".$matches[array_search($game_id, array_column($matches, 'game_id'))]['game_name']."</h1>";
    arsort($game_ratings);  // Sort ratings in descending order

    foreach ($game_ratings as $accountId => $rating) {
        $totalMatches = $matchesPlayed[$game_id][$accountId];
        $totalWins = $wins[$game_id][$accountId];
        $totalLosses = $losses[$game_id][$accountId];
        $winRate = $totalMatches > 0 ? ($totalWins / $totalMatches) : 0.0;

        echo "Updating DB for Account $accountId: Elo Rating = $rating, Total Matches = $totalMatches, Wins = $totalWins, Losses = $totalLosses, Win Rate = $winRate<br>";
        $eloController->updatePlayerStats($conn, $accountId, $game_id, $rating, $totalMatches, $totalWins, $totalLosses, $minRankedMatches);

    }
    echo "<h2>Ranked Players:</h2>";
    foreach ($game_ratings as $accountId => $rating) {
        if ($matchesPlayed[$game_id][$accountId] >= $minRankedMatches) {
            echo "<b>".$usernames[$accountId]."</b> - Elo Rating: $rating<br/>";
        }
    }
    echo "<h2>Unranked Players:</h2>";
   
    foreach ($game_ratings as $accountId => $rating) {
        if ($matchesPlayed[$game_id][$accountId] < $minRankedMatches) {
            echo "<b>".$usernames[$accountId]."</b> - Elo Rating: $rating<br/>";
        }
    }
}


?>