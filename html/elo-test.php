<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");

function updateEloScoreAndRankedStatus($accountId, $gameId, $eloScore, $totalMatches, $wins, $losses, $winRate, $minRankedMatches) {
    $isRanked = $totalMatches >= $minRankedMatches ? 1 : 0;
    $stmt = mysqli_prepare($GLOBALS["conn"], 'INSERT INTO account_game_elo (account_id, game_id, elo_rating, is_ranked, total_matches, total_wins, total_losses, win_rate) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE elo_rating = VALUES(elo_rating), is_ranked = VALUES(is_ranked), total_matches = VALUES(total_matches), total_wins = VALUES(total_wins), total_losses = VALUES(total_losses), win_rate = VALUES(win_rate)');
    if (!$stmt) {
        die("Error preparing statement: " . mysqli_error($GLOBALS["conn"]));
    }
    mysqli_stmt_bind_param($stmt, 'iiidiiid', $accountId, $gameId, $eloScore, $isRanked, $totalMatches, $wins, $losses, $winRate);
    if (!mysqli_stmt_execute($stmt)) {
        die("Error executing statement: " . mysqli_stmt_error($stmt));
    }
    mysqli_stmt_close($stmt);
}

$baseRating = 1500;
$kFactor = 30;
$ratings = [];
$usernames = [];
$matchesPlayed = [];
$wins = [];
$losses = [];

// Fetch all game matches and MinRankedMatches
$result = mysqli_query($GLOBALS["conn"], 'SELECT game_match.Id, game_id, game.name as game_name, game.MinRankedMatches FROM game_match left join game on game_match.game_id = game.Id');
if (!$result) {
    die("Error fetching game matches: " . mysqli_error($GLOBALS["conn"]));
}
$matches = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_free_result($result);

foreach ($matches as $match) {
    $game_match_id = $match['Id'];
    $game_id = $match['game_id'];
    $game_name = $match['game_name'];
    $minRankedMatches = $match['MinRankedMatches'];

    // Prepare and execute SQL statement
    $stmt = mysqli_prepare($GLOBALS["conn"], 'SELECT account_id, win, team_name, account.Username FROM game_record left join account on game_record.account_id = account.Id WHERE game_match_id = ? AND game_id = ?');
    if (!$stmt) {
        die("Error preparing statement: " . mysqli_error($GLOBALS["conn"]));
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
    $teamNames = []; // Add this line to initialize the teamNames array
    foreach ($records as $record) {
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
            $wins[$game_id][$accountId]++;
        } else {
            $losses[$game_id][$accountId]++;
        }

        // Store the username for each account ID
        $usernames[$accountId] = $username;

        if (!isset($ratings[$game_id][$accountId])) {
            $ratings[$game_id][$accountId] = $baseRating;
            $matchesPlayed[$game_id][$accountId] = 0;  // Initialize match count for this player
        }
        
        // Increment match count for this player
        $matchesPlayed[$game_id][$accountId]++;

        // Add player to team in $teams
        $teams[$teamName][] = $accountId;

        // Add team name to $teamNames if it isn't there already
        if (!in_array($teamName, $teamNames)) {
            $teamNames[] = $teamName;
        }
    }


    // Compute average ratings for each team
    $averageRatings = [];
    foreach ($teams as $teamName => $team) {
        $sum = 0;
        foreach ($team as $accountId) {
            $sum += $ratings[$game_id][$accountId];
        }
        $averageRatings[$teamName] = $sum / count($team);
    }

    // Create pairs of teams
    $pairs = [];
    for ($i = 0; $i < count($teamNames); $i++) {
        for ($j = $i + 1; $j < count($teamNames); $j++) {
            $pairs[] = [$teamNames[$i], $teamNames[$j]];
        }
    }

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

    // Compute Elo rating updates for each pair of teams
    foreach ($pairs as $pair) {
        // Compute expected scores
        $expected1 = 1 / (1 + pow(10, ($averageRatings[$pair[1]] - $averageRatings[$pair[0]]) / 400));
        $expected2 = 1 / (1 + pow(10, ($averageRatings[$pair[0]] - $averageRatings[$pair[1]]) / 400));

        // Set actual scores depending on whether each team won or lost
        $actual1 = in_array($pair[0], $winningTeams) ? 1 : (in_array($pair[1], $winningTeams) ? 0 : 0.5);
        $actual2 = in_array($pair[1], $winningTeams) ? 1 : (in_array($pair[0], $winningTeams) ? 0 : 0.5);

        // Update ratings for all players on each team
        foreach ($teams[$pair[0]] as $accountId) {
            $ratings[$game_id][$accountId] = round($ratings[$game_id][$accountId] + $kFactor * ($actual1 - $expected1));
        }
        foreach ($teams[$pair[1]] as $accountId) {
            $ratings[$game_id][$accountId] = round($ratings[$game_id][$accountId] + $kFactor * ($actual2 - $expected2));
        }
    }

}

// Rest of your script...
// You could put any other logic you need here.

// Print final ratings
foreach ($ratings as $game_id => $game_ratings) {
    $minRankedMatches = $matches[array_search($game_id, array_column($matches, 'game_id'))]['MinRankedMatches'];
    echo "<h1>".$matches[array_search($game_id, array_column($matches, 'game_id'))]['game_name']."</h1>";
    arsort($game_ratings);  // Sort ratings in descending order

    foreach ($game_ratings as $accountId => $rating) {
        $totalMatches = $matchesPlayed[$game_id][$accountId];
        $totalWins = $wins[$game_id][$accountId];
        $totalLosses = $losses[$game_id][$accountId];
        $winRate = $totalWins / ($totalMatches); 
        updateEloScoreAndRankedStatus($accountId, $game_id, $rating, $totalMatches, $totalWins, $totalLosses, $winRate, $minRankedMatches);

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
