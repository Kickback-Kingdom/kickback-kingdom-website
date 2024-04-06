<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");

// Get game_id and account_ids from query string
$game_id = $_GET['game_id'] ?? null;
$account_ids = $_GET['account_ids'] ?? null;
$team_count = $_GET['team_count'] ?? null;
if (!$game_id || !$account_ids || !$team_count) {
    die("Please specify a game_id, a list of account_ids and a team_count in the query string.");
}

// Convert account_ids from a comma-separated string to an array
$account_ids = explode(',', $account_ids);

// Prepare the account_ids for inclusion in the SQL statement
$account_ids = array_map('intval', $account_ids);  // Ensure each id is an integer
$account_ids = implode(',', $account_ids);  // Convert back to a comma-separated string

// Fetch player data
$result = mysqli_query($GLOBALS["conn"], "
    SELECT 
        account_game_elo.account_id,
        account.Username, 
        account_game_elo.game_id,
        game.name as game_name, 
        account_game_elo.elo_rating,
        account_game_elo.is_ranked,
        account_game_elo.total_matches,
        account_game_elo.total_wins,
        account_game_elo.total_losses,
        account_game_elo.win_rate
    FROM account_game_elo
    JOIN account ON account.id = account_game_elo.account_id
    JOIN game ON game.id = account_game_elo.game_id
    WHERE account_game_elo.game_id = $game_id AND account_game_elo.account_id IN ($account_ids)
    order by account_game_elo.elo_rating desc");
if (!$result) {
    die("Error fetching player data: " . mysqli_error($GLOBALS["conn"]));
}
$account_game_elos = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_free_result($result);

// Group players by game
$games = [];
foreach ($account_game_elos as $player) {
    $games[$player['game_id']][] = $player;
}

// Number of teams
$numTeams = $team_count; // Adjust this as necessary

// Distribute players among teams
foreach ($games as $game_id => $players) {
    // Sort players by rating
    usort($players, function ($a, $b) {
        return ($b['elo_rating'] - $a['elo_rating']);
    });

    // Initialize teams
    $teams = array_fill(0, $numTeams, []);

    // Distribute players among teams
    foreach ($players as $i => $player) {
        //$teams[$i % $numTeams][] = $player;
        usort($teams, function ($a, $b) {
            return array_sum(array_column($a, 'elo_rating')) - array_sum(array_column($b, 'elo_rating'));
        });
        $teams[0][] = $player;
    }

    // Print teams
    echo "<h1>Game: " . $players[0]['game_name'] . "</h1>";
    foreach ($teams as $i => $team) {
        $sum_elo = array_sum(array_column($team, 'elo_rating'));
        $average_elo = $sum_elo / count($team);
        
        echo "<h2>Team " . ($i + 1) . "</h2>";
        echo "Sum Elo: " . $sum_elo . "<br/>";
        echo "Average Elo: " . $average_elo . "<br/>";
        
        foreach ($team as $player) {
            echo "<b>Username: " . $player['Username'] . "</b> - Elo Rating: " . $player['elo_rating'] . "<br/>";
        }
    }
}

?>
