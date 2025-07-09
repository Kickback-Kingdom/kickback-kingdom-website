<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: (__DIR__ . "/..")) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");

use Kickback\Backend\Controllers\MerchantGuildController;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vDateTime;
use Kickback\Backend\Views\vDecimal;
$accountId = $_GET['accountId'] ?? null;
if (isset($_GET['date'])) {
    $inputDate = $_GET['date'] . '-01'; // Append '-01' to set it to the first day of the month
    $targetDate = $inputDate;
} else {
    die('Error no date was entered');
}



if (!$accountId) {
    die('Error: No account was entered');
}

$accountId = new vRecordId('', (int)$accountId);

$targetDate = vDateTime::fromDB($targetDate);

$qualifiedInterestDate = $targetDate->subMonths(1);
$lastStatementDate = $targetDate->subMonths(1);


$purchases = MerchantGuildController::PullPurchasesUntil($accountId, $targetDate)->data;
$historicalLoot = MerchantGuildController::PullHistoricalShares($accountId, $targetDate)->data;

$lastStatement = MerchantGuildController::BuildStatement($accountId, $lastStatementDate);
$currentStatement = MerchantGuildController::BuildStatement($accountId, $targetDate);


//$lastStatement = MerchantGuildController::BuildStatement($accountId, $targetDate, true, true);
//$currentStatement = MerchantGuildController::BuildStatement($accountId, $targetDate, true, false);

//$lastStatement2 = MerchantGuildController::BuildStatement($accountId, $targetDate->addMonths(1), true, true);
//$currentStatement2 = MerchantGuildController::BuildStatement($accountId, $targetDate->addMonths(1), true, false);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Historical Loot</title>
    
    <script src="https://kit.fontawesome.com/f098b8e570.js" crossorigin="anonymous"></script>
    <style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
    }

    table {
        border-collapse: collapse;
        width: 100%;
        margin-top: 24px;
    }

    th, td {
        border: 1px solid #ddd;
        padding: 10px 12px;
        font-size: 0.95rem;
    }

    th {
        background-color: #f2f2f2;
        font-weight: bold;
        text-align: left;
    }

    tr:hover {
        background-color: #f9f9f9;
    }

    .container {
        padding: 40px 20px;
    }

    .statements-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(360px, 1fr));
        gap: 36px;
        margin-top: 40px;
    }

    .statement {
        max-width: 100%;
        border: 1px solid #ddd;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        line-height: 1.6;
        background-color: white;
    }

    .last-month-statement {
        background-color: #f9f9f9;
    }

    .current-month-statement {
        background-color: #eafbea;
    }

    .statement h2 {
        text-align: center;
        font-size: 1.4rem;
        margin-bottom: 4px;
    }

    .statement p {
        text-align: center;
        margin-top: 0;
        color: #666;
        font-size: 0.95rem;
    }

    .statement h3 {
        font-size: 1.1rem;
        color: #222;
        margin-top: 32px;
        margin-bottom: 16px;
        border-bottom: 1px solid #ccc;
        padding-bottom: 4px;
    }

    .statement-row {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        margin: 2px 0;
        border-bottom: 1px solid #eee;
    }

    .statement-label {
        font-weight: bold;
        font-size: 0.95rem;
    }

    .statement-value {
        font-size: 1rem;
        text-align: right;
        white-space: nowrap;
    }

    .indented {
        padding-left: 20px;
    }

    .supplementary-text {
        font-size: 0.85rem;
        color: #888;
    }

    .end-of-period-summary {
        background-color: #e6f2ff;
        border-radius: 6px;
        padding: 20px;
        margin-top: 30px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    }

    .end-of-period-title {
        font-weight: bold;
        font-size: 1.15rem;
        border-bottom: 2px solid #99c2ff;
        padding-bottom: 8px;
        margin-bottom: 16px;
    }

    .finalize-button {
        display: inline-block;
        padding: 10px 20px;
        background-color: #007BFF;
        color: white;
        border-radius: 5px;
        font-weight: bold;
        border: none;
        cursor: pointer;
        transition: background-color 0.3s;
    }

    .finalize-button:hover {
        background-color: #0056b3;
    }

    .needs-finalization {
        background-color: #fff8cc;
    }

    .notification-banner {
        background-color: #FFD700;
        color: #333;
        padding: 10px 0;
        text-align: center;
        font-weight: bold;
        position: fixed;
        width: 100%;
        top: 0;
        z-index: 1000;
    }
    .supplementary-text {
        color: #777;
        font-size: 0.85rem;
        line-height: 1.4;
    }

</style>

</head>
<body>

<?php if ($currentStatement->needsFinalized) { ?>
<div class="notification-banner">
    The current statement needs finalization! Please review and click "Finalize".
</div>
<?php } ?>

<div class="container">
<h2>Merchant Guild Account Shares Overview</h2>

<!-- Navigation Bar -->
<form action="" method="GET">
    <label for="accountId">Account ID:</label>
    <input type="text" id="accountId" name="accountId" value="<?= $accountId->crand; ?>">
    
    <label for="date">Date:</label>
    <input type="month" id="date" name="date" value="<?= $targetDate->formattedYm; ?>"> <!-- Only get YYYY-MM -->

    <input type="submit" value="Go">
    <button type="submit" name="date" value="<?= $targetDate->subMonths(1)->formattedYm; ?>">
        Previous Month
    </button>
    <button type="submit" name="date" value="<?= $targetDate->addMonths(1)->formattedYm; ?>">
        Next Month
    </button>
</form>
<p>
<?= json_encode($lastStatementDate); ?>
</p>
<p>
<?= json_encode($targetDate); ?>
</p>
<div class="statements-container">
<?php
$statements = [
  'last-month-statement' => $lastStatement,
    'current-month-statement' => $currentStatement,
    //'last-month-statement2' => $lastStatement2,
    //  'current-month-statement2' => $currentStatement2
];

foreach ($statements as $class => $statement):
?>
<section class="statement <?= $class ?> <?= ($statement->needsFinalized ? "needs-finalization" : ""); ?>">

<h2 style="text-align:center; margin-bottom:5px;">
  <?= $statement->reportingPeriod->formattedMonthYear; ?> Merchant Guild Share Statement
</h2>
<p style="text-align:center; margin-top:0;">
  Issued on <?= $statement->issuedDate->formattedBasic; ?>
</p>

<div class="statement-row">
  <span class="statement-label">Statement ID</span>
  <span class="statement-value">#<?= $statement->crand; ?></span>
</div>
<div class="statement-row">
  <span class="statement-label">Account ID</span>
  <span class="statement-value">#<?= $statement->accountId->crand; ?></span>
</div>
<div class="statement-row">
  <span class="statement-label">Reporting Period</span>
  <span class="statement-value"><?= $statement->reportingPeriod->formattedMonthYear; ?></span>
</div>

<?php if ($statement->needsFinalized): ?>
  <div class="statement-row warning">
    <span class="statement-label">⚠️ Statement Status</span>
    <span class="statement-value">Pending Finalization</span>
  </div>
<?php endif; ?>

<hr/>

<h3>Start of Period Holdings</h3>
<div class="statement-row">
  <span class="statement-label">Full Merchant Guild Shares</span>
  <span class="statement-value"><?= $statement->getStartingShares()->toWholeUnitsInt(); ?></span>
</div>
<div class="statement-row indented supplementary-text">
  <span class="statement-label">Share Shards (Incomplete)</span>
  <span class="statement-value"><?= $statement->getStartingShares()->getFractional(); ?></span>
</div>

<h3>Shares Acquired This Period</h3>
<div class="statement-row">
  <span class="statement-label">Shares Purchased – Full</span>
  <span class="statement-value"><?= $statement->getWholePurchasedShares(); ?></span>
</div>
<div class="statement-row indented supplementary-text">
  <span class="statement-label">Shares Purchased – Partial</span>
  <span class="statement-value"><?= $statement->sharesPurchasedThisPeriod->getFractional(); ?></span>
</div>
<div class="statement-row indented supplementary-text">
  <span class="statement-label">Shares Purchased – Unprocessed</span>
  <span class="statement-value"><?= $statement->getUnprocessedSharesPurchased(); ?></span>
</div>
<div class="statement-row indented supplementary-text">
  <span class="statement-label">Shares Purchased – Processed</span>
  <span class="statement-value"><?= $statement->getProcessedPurchases(); ?></span>
</div>
<div class="statement-row indented supplementary-text">
  <span class="statement-label">fractionalShares</span>
  <span class="statement-value"><?= $statement->fractionalShares; ?></span>
</div>
<div class="statement-row indented supplementary-text">
  <span class="statement-label">totalShares</span>
  <span class="statement-value"><?= $statement->totalShares; ?></span>
</div>
<div class="statement-row">
  <span class="statement-label">Shares Gained from Trades (Net)</span>
  <span class="statement-value"><?= $statement->getNetSharesTradedThisPeriod(); ?></span>
</div>

<div class="statement-row">
  <span class="statement-label">Shares Completed from Prior Shards</span>
  <span class="statement-value">
    <?= $statement->sharesAcquiredThisPeriod - $statement->getWholePurchasedShares() - $statement->getNetSharesTradedThisPeriod(); ?>
  </span>
</div>

<div class="statement-row">
  <span class="statement-label">Total Full Shares Gained This Period</span>
  <span class="statement-value"><?= $statement->sharesAcquiredThisPeriod; ?></span>
</div>

<div class="statement-row">
  <span class="statement-label">
    Remaining Share Shards Before Interest<br>
    <small class="supplementary-text">
      These are partial shares accumulated from purchases and trades that haven't reached 1.0 yet.
    </small>
  </span>
  <span class="statement-value"><?= $statement->getTotalOwnedBeforeInterest()->getFractional(); ?></span>
</div>

<div class="statement-row">
  <span class="statement-label">Total Shares Gained (Full + Shards)</span>
  <span class="statement-value"><?= $statement->getTotalGainedBeforeInterest(); ?></span>
</div>

<div class="statement-row">
  <span class="statement-label">Total Shares Before Interest</span>
  <span class="statement-value"><?= $statement->getTotalOwnedBeforeInterest(); ?></span>
</div>

<h3>Interest Earnings</h3>
<div class="statement-row">
  <span class="statement-label">
    Interest-Earning Shares<br>
    <small class="supplementary-text">
      Based on the full Merchant Guild Shares held at the start of this period.
    </small>
  </span>
  <span class="statement-value"><?= $statement->interestBearingShares; ?></span>
</div>

<div class="statement-row">
  <span class="statement-label">Interest Earned – Share Shards</span>
  <span class="statement-value"><?= $statement->fractionalSharesEarned; ?></span>
</div>
<div class="statement-row indented supplementary-text">
  <span class="statement-label">Converted to Full Shares</span>
  <span class="statement-value"><?= $statement->newFullSharesEarned; ?></span>
</div>

<hr/>

<div class="end-of-period-summary">
  <h3 class="end-of-period-title" style="margin-top: 0px;">End of Period Summary</h3>

  <div class="statement-row">
    <span class="statement-label">Total Merchant Guild Shares</span>
    <span class="statement-value"><?= $statement->getEndingShares()->toWholeUnitsInt(); ?></span>
  </div>
  <div class="statement-row">
    <span class="statement-label">Unconverted Share Shards</span>
    <span class="statement-value"><?= $statement->getRemainingFractional(); ?></span>
  </div>
  <div class="statement-row indented supplementary-text">
    <span class="statement-label">Combined Total (Full + Shards)</span>
    <span class="statement-value"><?= $statement->getEndingShares(); ?></span>
  </div>
</div>
<p><pre>
  <?= $statement->getEndingShares(); ?>
<?= json_encode($statement, JSON_PRETTY_PRINT); ?>
</pre>

</p>
</section>





<?php endforeach; ?>

</div>


<section>
    <h3>Purchase History</h3>
    <table>
    <thead>
            <tr>
                <!-- Add table headers based on the columns you're expecting -->
                <th>Purchase Id</th>
                <th>Amount</th>
                <th>Date</th>
                <th>Shares Purchased</th>
                <th>ADA Value</th>
                <th>Purchased This Period</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($purchases as $purchase): ?>
                <tr>
                    <td><?= $purchase->crand; ?></td>
                    <td><?= $purchase->Amount." ".$purchase->Currency; ?></td>
                    <td><?= $purchase->PurchaseDate->formattedDetailed; ?></td>
                    <td><?= $purchase->SharesPurchased; ?></td>
                    <td><?= $purchase->ADAValue; ?></td>
                    <td><?= $purchase->PurchaseDate->value>=$qualifiedInterestDate->value?"YES":"NO"; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section>
    <h3>Shares History</h3>
    <table>
    <thead>
            <tr>
                <!-- Add table headers based on the columns you're expecting -->
                <th>Share Id</th>
                <th>Type</th>
                <th>Date Obtained</th>
                <th>Obtained By</th>
                <th>Interest Bearing</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($historicalLoot as $loot): ?>
                <tr>
                    <td><?= $loot->crand; ?></td>
                    <td>Merchant Guild Share</td>
                    <td><?= $loot->dateObtained->formattedDetailed; ?></td>
                    <td>Purchase</td>
                    <td><?= $loot->dateObtained->value<=$qualifiedInterestDate->value?"YES":"NO"; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

</div>
</body>
</html>
