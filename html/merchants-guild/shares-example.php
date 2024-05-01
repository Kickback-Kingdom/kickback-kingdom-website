<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: (__DIR__ . "/..")) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");

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

$interestDate = date('Y-m-d', strtotime("$targetDate -1 month"));
$lastStatementDate = date('Y-m-d', strtotime("$targetDate -1 month"));
$lastLastStatementDate = date('Y-m-d', strtotime("$targetDate -2 month"));


function free_mysqli_resources($mysqli) {
    while ($mysqli->more_results() && $mysqli->next_result()) {
        $dummyResult = $mysqli->use_result();
        if ($dummyResult instanceof mysqli_result) {
            $dummyResult->free();
        }
    }
}
// Fetch interest-bearing shares
function getInterestBearingShares($accountId, $interestDate) {
    $result = mysqli_query($GLOBALS["conn"], "CALL GetHistoricalLoot($accountId, '$interestDate', 16)");
    if (!$result) {
        die("Error getting interest bearing shares: " . mysqli_error($GLOBALS["conn"]));
    }
    $shares = mysqli_num_rows($result);
    $result->free();  // Use the object-oriented style free
    free_mysqli_resources($GLOBALS["conn"]);  // Use the function to free any remaining results
    return $shares;
}

function getPurchasesUntil($accountId, $targetDate)
{
    $query = "SELECT Id, AccountId, Amount, Currency, SharesPurchased, PurchaseDate, ADAValue, ADA_USD_Closing 
    FROM kickbackdb.share_purchase 
    where accountId = $accountId and PurchaseDate < '$targetDate'";

    $result = mysqli_query($GLOBALS["conn"], $query);

    if (!$result) {
        die("Error purchases: " . mysqli_error($GLOBALS["conn"]));
    }
    $purchases = mysqli_fetch_all($result, MYSQLI_ASSOC);
    $result->free();  // Use the object-oriented style free
    free_mysqli_resources($GLOBALS["conn"]);  // Use the function to free any remaining results
    return $purchases;
}

// Fetch total shares
function getTotalShares($accountId, $targetDate) {
    $result = mysqli_query($GLOBALS["conn"], "CALL GetHistoricalLoot($accountId, '$targetDate', 16)");
    if (!$result) {
        die("Error getting total shares: " . mysqli_error($GLOBALS["conn"]));
    }
    $shares = mysqli_num_rows($result);
    $historicalLoot = mysqli_fetch_all($result, MYSQLI_ASSOC);
    $result->free();  // Use the object-oriented style free
    free_mysqli_resources($GLOBALS["conn"]);  // Use the function to free any remaining results
    return $historicalLoot;
}

// Fetch last statement's fractional shares
function getLastStatementFractionalShares($accountId, $lastStatementDate) {
    $query = "SELECT COALESCE(fractional_shares_after_interest, fractional_shares) as fractional_shares
              FROM v_merchant_share_interest_statement 
              WHERE accountId = $accountId AND statement_date = '$lastStatementDate'";
    $result = mysqli_query($GLOBALS["conn"], $query);
    if (!$result) {
        die("Error getting last statment fractional shares: " . mysqli_error($GLOBALS["conn"]));
    }
    $row = mysqli_fetch_assoc($result);
    $result->free();  // Use the object-oriented style free
    free_mysqli_resources($GLOBALS["conn"]);  // Use the function to free any remaining results
    return $row ? $row['fractional_shares'] : 0;
}

function getLastStatement($accountId, $lastStatementDate) {
    $query = "SELECT *
              FROM v_merchant_share_interest_statement 
              WHERE accountId = $accountId AND statement_date = '$lastStatementDate'";
    $result = mysqli_query($GLOBALS["conn"], $query);
    if (!$result) {
        die("Error getting last statment fractional shares: " . mysqli_error($GLOBALS["conn"]));
    }
    $row = mysqli_fetch_assoc($result);
    $result->free();  // Use the object-oriented style free
    free_mysqli_resources($GLOBALS["conn"]);  // Use the function to free any remaining results
    return $row;
}

// Fetch fractional shares since the last statement
function getFractionFromPurchase($accountId, $targetDate) {
    // Calculate the start date (one month before targetDate).
    $startDate = date('Y-m-d', strtotime("$targetDate -1 month"));

    // Fetch the sum of fractional shares between startDate and targetDate.
    $query = "SELECT COALESCE(SUM(SharesPurchased - FLOOR(SharesPurchased)), 0) AS fractional
              FROM share_purchase 
              WHERE AccountId = $accountId 
              AND PurchaseDate BETWEEN '$startDate' AND '$targetDate'";
    $result = mysqli_query($GLOBALS["conn"], $query);
    if (!$result) {
        die("Error getting fractional shares from purchases: " . mysqli_error($GLOBALS["conn"]));
    }
    $row = mysqli_fetch_assoc($result);
    $result->free();  // Free the result
    free_mysqli_resources($GLOBALS["conn"]);  // Use the function to free any remaining results
    return $row ? $row['fractional'] : 0;
}

function getSharesPurchasedThisPeriod($accountId, $targetDate) {
    // Calculate the start date (one month before targetDate).
    $startDate = date('Y-m-d', strtotime("$targetDate -1 month"));

    // Fetch the sum of fractional shares between startDate and targetDate.
    $query = "SELECT sum(SharesPurchased) as SharesPurchased
              FROM share_purchase 
              WHERE AccountId = $accountId 
              AND PurchaseDate BETWEEN '$startDate' AND '$targetDate'";
    $result = mysqli_query($GLOBALS["conn"], $query);
    if (!$result) {
        die("Error getting purchased shares this period: " . mysqli_error($GLOBALS["conn"]));
    }
    $row = mysqli_fetch_assoc($result);
    $result->free();  // Free the result
    free_mysqli_resources($GLOBALS["conn"]);  // Use the function to free any remaining results
    return $row ? $row['SharesPurchased'] : 0;
}

$purchases = getPurchasesUntil($accountId, $targetDate);
$sharesPurchasedThisPeriod = getSharesPurchasedThisPeriod($accountId, $targetDate);
$interestBearingShares = getInterestBearingShares($accountId, $interestDate);
$historicalLoot = getTotalShares($accountId, $targetDate);
$totalShares = count($historicalLoot);
$lastStatement = getLastStatement($accountId, $lastStatementDate);
$currentStatement = getLastStatement($accountId, $targetDate);
$lastStatementFractionalShares = getLastStatementFractionalShares($accountId, $lastStatementDate);
$lastLastStatementFractionalShares = getLastStatementFractionalShares($accountId, $lastLastStatementDate);
$fractionFromPurchase = getFractionFromPurchase($accountId, $targetDate);
$interestRate = 0.005;
$currentFractionalShares = $lastStatementFractionalShares + $fractionFromPurchase;
$currentFractionalShares = $currentFractionalShares-floor($currentFractionalShares);
$fullSharesAcquiredThisPeriod = $totalShares-$interestBearingShares;


$interestEarnedThisMonth = $interestBearingShares*$interestRate;
$fractionalSharesAfterInterest = $currentFractionalShares+$interestEarnedThisMonth;

$needsFinalized = false;

if ($currentStatement == null)
{
    $needsFinalized = true;

    $currentStatement["statementId"] = "TBD";
    $currentStatement["accountId"] = $accountId;
    $currentStatement["statement_date"] = $targetDate;
    $currentStatement["statement_period"] = $interestDate;
    $currentStatement["total_shares"] = $totalShares;
    $currentStatement["fractional_shares"] = $currentFractionalShares;

    $currentStatement["interestId"] = "TBD";
    $currentStatement["interest_rate"] = $interestRate*100;
    $currentStatement["interest_bearing_shares"] = $interestBearingShares;
    $currentStatement["fractional_shares_earned"] = $interestEarnedThisMonth;
    $currentStatement["new_full_shares_earned"] = floor($interestEarnedThisMonth);
    $currentStatement["fractional_shares_after_interest"] = $fractionalSharesAfterInterest;
    $currentStatement["shares_acquired_this_period"] = $fullSharesAcquiredThisPeriod;
    $currentStatement["SharesPurchasedThisPeriod"] = $sharesPurchasedThisPeriod;
}
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
            padding: 20px;
            padding-left:0px;
            padding-right:0px;
            margin-right:0px;
            margin-left:0px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:hover {
            background-color: #ddd;
        }
        .container {
            padding:20px;
        }
        .statement {
            max-width: 600px;
            margin: 0 auto;
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .statement h3 {
            text-align: center;
            margin-bottom: 20px;
        }

        .statement h2 {
            text-align: center;
        }

        .statement-row {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid #eee;
            padding: 10px 0;
        }

        .statement-label {
            font-weight: bold;
        }

        .statement-value {
            text-align: right;
        }

        .statements-container {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;  /* Provide some gap between statements */
            margin-top: 32px;
        }

        .last-month-statement {
            background-color: #f7f7f7; /* A light gray background to visually differentiate */
        }

        .current-month-statement {
            background-color: #e5f5e0; /* A light green background to visually differentiate */
        }

        .end-of-period-summary {
            background-color: #e0f0ff; /* Light blue background color */
            border-radius: 5px; /* Rounded corners for a softer look */
            padding: 15px; /* Increased padding */
            margin-top: 20px; /* Some margin at the top for separation */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* Subtle shadow */
        }

        .end-of-period-title {
            font-weight: bold;
            font-size: 1.2em; /* Slightly larger font size */
            border-bottom: 2px solid #99c2ff; /* Blue border to further emphasize the title */
            padding-bottom: 10px; /* Padding to space out the title and border */
            margin-bottom: 15px; /* Margin for spacing below the title */
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
            background-color: #0056b3; /* Slightly darker shade on hover */
        }

        .needs-finalization {
            background-color: #FFD700; /* Amber background */
        }
        .notification-banner {
            background-color: #FFD700; /* Amber background */
            color: #333;
            padding: 10px 0;
            text-align: center;
            font-weight: bold;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;  /* Keeps the banner above all other elements */
        }
        .indented {
            padding-left: 20px;  /* Or adjust based on your design */
        }

        .supplementary-text {
            font-size: 0.9em; /* Slightly smaller than normal text */
            color: #888;     /* A lighter shade */
        }

    </style>
</head>
<body>

<?php if ($needsFinalized) { ?>
<div class="notification-banner">
    The current statement needs finalization! Please review and click "Finalize".
</div>
<?php } ?>

<div class="container">
<h2>Merchant Guild Account Shares Overview</h2>

<!-- Navigation Bar -->
<form action="" method="GET">
    <label for="accountId">Account ID:</label>
    <input type="text" id="accountId" name="accountId" value="<?php echo $accountId; ?>">
    
    <label for="date">Date:</label>
    <input type="month" id="date" name="date" value="<?php echo substr($targetDate, 0, 7); ?>"> <!-- Only get YYYY-MM -->

    <input type="submit" value="Go">
    <button type="submit" name="date" value="<?php echo date('Y-m', strtotime("$targetDate -1 month")); ?>">
        Previous Month
    </button>
    <button type="submit" name="date" value="<?php echo date('Y-m', strtotime("$targetDate +1 month")); ?>">
        Next Month
    </button>
</form>

<section style="display: none;">
    <h3>Statement Details</h3>
    <p>Statment Period: <?php echo $targetDate; ?></p>
    <p>Interest Bearing Shares: <?php echo $interestBearingShares; ?></p>
    <p>Interest Rate: <?php echo $interestRate * 100; ?>% Monthly</p>
    <p>Total Shares: <?php echo $totalShares; ?></p>
    <p>_______</p>
    <p>Fractional Shares Purchased This Period: <?php echo $fractionFromPurchase; ?></p>
    <p>+</p>
    <p>Last Statements Fractional Shares: <?php echo $lastStatementFractionalShares; ?></p>
    <p>=</p>
    <p>Current Fractional Shares Before Interest: <?php echo $currentFractionalShares; ?></p>
    <p>+</p>
    <p>Interest Earned This Month: <?php echo $interestEarnedThisMonth; ?></p>
    <p>=</p>
    <p>Fractional Shares After Interest: <?php echo $fractionalSharesAfterInterest; ?></p>
    <p>_______</p>
    <p>Last Statement Exists: <?php echo $lastStatement?"True":"False";?></p>
    <p>Current Statement Exists: <?php echo $currentStatement?"True":"False";?></p>

</section>


<div class="statements-container">
<?php 
if ($lastStatement != null)
{

?>
<section class="statement last-month-statement">
    <h2>Statement for <?php echo date("F, Y", strtotime($lastStatement["statement_period"])); ?></h2>
    <p>This statement provides an overview of your shares in the Merchant Guild for the specified month, including any interest earned.</p>
    <h3>Statement</h3>
    <div class="statement-row">
        <span class="statement-label">Statement Period</span>
        <span class="statement-value"><?php echo date("F, Y", strtotime($lastStatement["statement_period"])); ?></span>
    </div>
    <div class="statement-row">
        <span class="statement-label">Statement ID</span>
        <span class="statement-value"><?php echo $lastStatement['statementId']; ?></span>
    </div>
    
    <div class="statement-row">
        <span class="statement-label">Account ID</span>
        <span class="statement-value"><?php echo $lastStatement['accountId'] ?? 'N/A'; ?></span>
    </div>

    <div class="statement-row">
        <span class="statement-label">Full Shares Acquired This Period</span>
        <span class="statement-value"><?php echo $lastStatement['shares_acquired_this_period'] ?? 'N/A'; ?></span>
    </div>
    <div class="statement-row indented">
        <span class="statement-label supplementary-text">Shares Purchased:</span>
        <span class="statement-value supplementary-text"><?php echo $lastStatement['SharesPurchasedThisPeriod'] ?? 'N/A'; ?></span>
    </div>
    <div class="statement-row indented">
        <span class="statement-label supplementary-text">Shares Traded:</span>
        <span class="statement-value supplementary-text">+0</span>
    </div>
    <div class="statement-row indented">
        <span class="statement-label supplementary-text">Previous Partial Shares:</span>
        <span class="statement-value supplementary-text"><?php echo $lastLastStatementFractionalShares ?? 'N/A'; ?></span>
    </div>


    <div class="statement-row">
        <span class="statement-label">Total Full Shares Owned</span>
        <span class="statement-value"><?php echo $lastStatement['total_shares'] ?? 'N/A'; ?></span>
    </div>

    <div class="statement-row">
        <span class="statement-label">Total Partial Shares Owned</span>
        <span class="statement-value"><?php echo $lastStatement['fractional_shares'] ?? 'N/A'; ?></span>
    </div>
    <h3>Interest Earned</h3>
    <?php
    if ($lastStatement['interestId'] != null)
    {
        ?>
    

    <div class="statement-row">
        <span class="statement-label">Interest ID</span>
        <span class="statement-value"><?php echo $lastStatement['interestId'] ?? 'N/A'; ?></span>
    </div>

    <div class="statement-row">
        <span class="statement-label">Interest Rate %</span>
        <span class="statement-value"><?php echo $lastStatement['interest_rate'] ?? 'N/A'; ?></span>
    </div>

    <div class="statement-row">
        <span class="statement-label">Shares Earning Interest</span>
        <span class="statement-value"><?php echo $lastStatement['interest_bearing_shares'] ?? 'N/A'; ?></span>
    </div>
    <div class="statement-row">
        <span class="statement-label">Interest Earned in Shares</span>
        <span class="statement-value"><?php echo $lastStatement['fractional_shares_earned'] ?? 'N/A'; ?></span>
    </div>
    <div class="end-of-period-summary">
        <h3 class="end-of-period-title">End of Period Summary</h3>
    
        <div class="statement-row">
            <span class="statement-label">Total Full Shares Owned After Interest</span>
            <span class="statement-value"><?php echo $lastStatement['total_shares']+$lastStatement['new_full_shares_earned'] ?? 'N/A'; ?></span>
        </div>
        <div class="statement-row">
            <span class="statement-label">Total Partial Shares Owned After Interest</span>
            <span class="statement-value"><?php echo $lastStatement['fractional_shares_after_interest']-$lastStatement['new_full_shares_earned'] ?? 'N/A'; ?></span>
        </div>
        <div class="statement-row">
            <span class="statement-label">Full Shares Earned</span>
            <span class="statement-value"><?php echo $lastStatement['new_full_shares_earned'] ?? 'N/A'; ?></span>
        </div>
    </div>


    <?php 
    } else {
    ?>
<p>You have no shares this period eligible for interest.</p>
    <?php } ?>
</section>
<?php } else { ?>
    
<section class="statement last-month-statement">
    <h2>No Statement Found For <?php echo $interestDate; ?></h2>
</section>
<?php 
}
if ($currentStatement != null)
{

?>
<section class="statement current-month-statement <?php echo $needsFinalized?"needs-finalization":""; ?>" >
    <h2>
    Statement for <?php echo date("F, Y", strtotime($currentStatement["statement_period"])); ?>
        <?php if ($needsFinalized) { ?><i class="fa fa-exclamation-circle" style="color: red;" aria-hidden="true"></i><?php } ?>
    </h2>
    <p>This statement provides an overview of your shares in the Merchant Guild for the specified month, including any interest earned.</p>
    <h3>Statement</h3>
    
    <div class="statement-row">
        <span class="statement-label">Statement Period</span>
        <span class="statement-value"><?php echo date("F, Y", strtotime($currentStatement["statement_period"])); ?></span>
    </div>

    <div class="statement-row">
        <span class="statement-label">Statement ID</span>
        <span class="statement-value"><?php echo $currentStatement['statementId']; ?></span>
    </div>
    
    <div class="statement-row">
        <span class="statement-label">Account ID</span>
        <span class="statement-value"><?php echo $currentStatement['accountId'] ?? 'N/A'; ?></span>
    </div>

    <div class="statement-row">
        <span class="statement-label">Full Shares Acquired This Period</span>
        <span class="statement-value"><?php echo $currentStatement['shares_acquired_this_period'] ?? 'N/A'; ?></span>
    </div>
    <div class="statement-row indented">
        <span class="statement-label supplementary-text">Shares Purchased:</span>
        <span class="statement-value supplementary-text"><?php echo $currentStatement['SharesPurchasedThisPeriod'] ?? 'N/A'; ?></span>
    </div>
    <div class="statement-row indented">
        <span class="statement-label supplementary-text">Shares Traded:</span>
        <span class="statement-value supplementary-text">+0</span>
    </div>
    <div class="statement-row indented">
        <span class="statement-label supplementary-text">Previous Partial Shares:</span>
        <span class="statement-value supplementary-text"><?php echo $lastStatementFractionalShares ?? 'N/A'; ?></span>
    </div>

    <div class="statement-row">
        <span class="statement-label">Total Full Shares Owned</span>
        <span class="statement-value"><?php echo $currentStatement['total_shares'] ?? 'N/A'; ?></span>
    </div>


    <div class="statement-row">
        <span class="statement-label">Total Partial Shares Owned</span>
        <span class="statement-value"><?php echo $currentStatement['fractional_shares'] ?? 'N/A'; ?></span>
    </div>
    
    <h3>Interest Earned</h3>
    <?php
    if ($currentStatement['interestId'] != null)
    {
        ?>

    <div class="statement-row">
        <span class="statement-label">Interest ID</span>
        <span class="statement-value"><?php echo $currentStatement['interestId'] ?? 'N/A'; ?></span>
    </div>

    <div class="statement-row">
        <span class="statement-label">Interest Rate %</span>
        <span class="statement-value"><?php echo $currentStatement['interest_rate'] ?? 'N/A'; ?></span>
    </div>
    <div class="statement-row">
        <span class="statement-label">Shares Earning Interest</span>
        <span class="statement-value"><?php echo $currentStatement['interest_bearing_shares'] ?? 'N/A'; ?></span>
    </div>

    <div class="statement-row">
        <span class="statement-label">Interest Earned in Shares</span>
        <span class="statement-value"><?php echo $currentStatement['fractional_shares_earned'] ?? 'N/A'; ?></span>
    </div>

    
    <div class="end-of-period-summary">
        <h3 class="end-of-period-title">End of Period Summary</h3>
    
        <div class="statement-row">
            <span class="statement-label">Total Full Shares Owned After Interest</span>
            <span class="statement-value"><?php echo $currentStatement['total_shares']+$currentStatement['new_full_shares_earned'] ?? 'N/A'; ?></span>
        </div>
        <div class="statement-row">
            <span class="statement-label">Total Partial Shares Owned After Interest</span>
            <span class="statement-value"><?php echo $currentStatement['fractional_shares_after_interest']-$currentStatement['new_full_shares_earned'] ?? 'N/A'; ?></span>
        </div>
        <div class="statement-row">
            <span class="statement-label">Full Shares Earned</span>
            <span class="statement-value"><?php echo $currentStatement['new_full_shares_earned'] ?? 'N/A'; ?></span>
        </div>
    </div>
    
    <?php 
    } else {
        ?>
    <p>You have no shares this period eligible for interest.</p>
        <?php } 
        
        if ($needsFinalized)
        {

        ?>
        
    <div style="text-align:center; margin-top:20px;">
        <button class="finalize-button" onclick="finalizeStatement()">Finalize Statement</button>
    </div>
    <?php } ?>
</section>
<?php } else { ?>
    
<section class="statement current-month-statement">
    <h2>No Statement Found For <?php echo $targetDate; ?></h2>
</section>
<?php } ?>
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
                    <td><?php echo $purchase['Id']; ?></td>
                    <td><?php echo $purchase['Amount']." ".$purchase['Currency']; ?></td>
                    <td><?php echo $purchase['PurchaseDate']; ?></td>
                    <td><?php echo $purchase['SharesPurchased']; ?></td>
                    <td><?php echo $purchase['ADAValue']; ?></td>
                    <td><?php echo $purchase['PurchaseDate']>=$interestDate?"YES":"NO"; ?></td>
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
                    <td><?php echo $loot['Id']; ?></td>
                    <td>Merchant Guild Share</td>
                    <td><?php echo $loot['dateObtained']; ?></td>
                    <td>Purchase</td>
                    <td><?php echo $loot['dateObtained']<=$interestDate?"YES":"NO"; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

</div>
</body>
</html>
