<?php

$session = require($_SERVER['DOCUMENT_ROOT'] . "/api/v1/engine/session/verifySession.php");

function free_mysqli_resources($mysqli) {
    while ($mysqli->more_results() && $mysqli->next_result()) {
        $dummyResult = $mysqli->use_result();
        if ($dummyResult instanceof mysqli_result) {
            $dummyResult->free();
        }
    }
}

function getInvestorsWithShares() {
    $query = "SELECT account_id, count(*) as shares FROM loot where item_id = 16 group by account_id";
    $result = mysqli_query($GLOBALS["conn"], $query);
    if (!$result) {
        die("Error getting investors: " . mysqli_error($GLOBALS["conn"]));
    }
    $investors = mysqli_fetch_all($result, MYSQLI_ASSOC);
    $result->free();
    free_mysqli_resources($GLOBALS["conn"]);
    return $investors;
}

$investors = getInvestorsWithShares();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Investor Statements Overview</title>
    <!-- You can link to a stylesheet here for additional styling -->
</head>
<body>

<table>
    <thead>
        <tr>
            <th>Account ID</th>
            <th>Shares</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($investors as $investor): ?>
            <tr>
                <td><?= $investor['account_id']; ?></td>
                <td><?= $investor['shares']; ?></td>
                <td>
                    <a href="statement_overview.php?accountId=<?= $investor['account_id']; ?>">View Earliest Unfinalized Statement</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>
