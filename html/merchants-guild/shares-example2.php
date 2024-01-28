<?php

$session = require($_SERVER['DOCUMENT_ROOT'] . "/api/v1/engine/session/verifySession.php");

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


$purchases = PullPurchasesUntil($accountId, $targetDate);
$historicalLoot = PullHistoricalShares($accountId, $targetDate);
$lastStatement = BuildStatement($accountId, $lastStatementDate);
$currentStatement = BuildStatement($accountId, $targetDate);
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

<?php if ($currentStatement["needsFinalized"]) { ?>
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
        <span class="statement-value supplementary-text"><?php echo $lastStatement["last_statement_fractional_shares"] ?? 'N/A'; ?></span>
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
            <span class="statement-value"><?php echo $lastStatement['fractional_shares_after_interest'] ?? 'N/A'; ?></span>
        </div>
        <div class="statement-row">
            <span class="statement-label">Full Shares Earned After Interest</span>
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
<section class="statement current-month-statement <?php echo $currentStatement["needsFinalized"]?"needs-finalization":""; ?>" >
    <h2>
    Statement for <?php echo date("F, Y", strtotime($currentStatement["statement_period"])); ?>
        <?php if ($currentStatement["needsFinalized"]) { ?><i class="fa fa-exclamation-circle" style="color: red;" aria-hidden="true"></i><?php } ?>
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
        <span class="statement-value supplementary-text"><?php echo $currentStatement["last_statement_fractional_shares"] ?? 'N/A'; ?></span>
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
            <span class="statement-value"><?php echo $currentStatement['fractional_shares_after_interest'] ?? 'N/A'; ?></span>
        </div>
        <div class="statement-row">
            <span class="statement-label">Full Shares Earned After Interest</span>
            <span class="statement-value"><?php echo $currentStatement['new_full_shares_earned'] ?? 'N/A'; ?></span>
        </div>
    </div>
    
    <?php 
    } else {
        ?>
    <p>You have no shares this period eligible for interest.</p>
        <?php } 
        
        if ($currentStatement["needsFinalized"])
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
