<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: (__DIR__ . "/..")) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");


use Kickback\Services\Database;

function getInvestorsWithShares() {
    $conn = Database::getConnection();

    $query = "SELECT account_id, count(*) as shares FROM loot where item_id = 16 group by account_id";
    $result = mysqli_query($conn, $query);
    if (!$result) {
        die("Error getting investors: " . mysqli_error($conn));
    }
    $investors = mysqli_fetch_all($result, MYSQLI_ASSOC);
    $result->free();
    free_mysqli_resources($conn);
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
