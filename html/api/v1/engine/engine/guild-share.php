<?php


function SaveMerchantShareStatement($statement) {
    // Use the mysqli connection from the global scope
    $conn = $GLOBALS["conn"];
    
    // Prepare the SQL statement to call the procedure
    $stmt = mysqli_prepare($conn, "CALL SaveStatement(?, ?, ?, ?, ?, ?, ?)");

    // Check if the statement preparation was successful
    if (!$stmt) {
        return (new APIResponse(false, "Failed to prepare the statement", mysqli_error($conn)));
    }

    // Bind the parameters to the SQL statement
    mysqli_stmt_bind_param($stmt, 'isiiddd', 
        $statement["accountId"], 
        $statement["statement_date"], 
        $statement["total_shares"], 
        $statement["interest_bearing_shares"], 
        $statement["fractional_shares"], 
        $statement["fractional_shares_earned"], 
        $statement["interest_rate"]
    );

    // Execute the SQL statement
    mysqli_stmt_execute($stmt);

    // If there's an error during execution
    if (mysqli_stmt_error($stmt)) {
        // Free the statement
        mysqli_stmt_close($stmt);
        return (new APIResponse(false, "Error occurred while saving the statement", mysqli_stmt_error($stmt)));
    }

    // If everything went smoothly
    else {
        // Free the statement
        mysqli_stmt_close($stmt);
        return (new APIResponse(true, "Merchant share statement saved successfully", null));
    }
}

function GiveNewFullSharesAfterInterest($statement)
{
    $newShares = $statement["new_full_shares_earned"];

    // Ensure newShares is an integer and greater than zero
    if (is_numeric($newShares) && $newShares > 0) {
        for ($i = 0; $i < $newShares; $i++) {
            GiveMerchantGuildShare($statement["accountId"], $statement["statement_date"]);
        }
    }
}


function ProcessMonthlyStatements($statement_date) {
    // Connect to your database
    $conn = $GLOBALS["conn"];
    mysqli_begin_transaction($conn);
    
    // Query to fetch account IDs based on your criterion
    $sql = "SELECT DISTINCT AccountId FROM share_purchase WHERE PurchaseDate < ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 's', $statement_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $accountId = $row['AccountId'];
        
        // Create a savepoint for this account
        $savepoint = "SAVEPOINT_for_account_$accountId";
        if (!mysqli_query($conn, "SAVEPOINT $savepoint")) {
            // Handle savepoint error
        }

        try {
            $statementData = BuildStatement($accountId, $statement_date);
            GiveNewFullSharesAfterInterest($statementData);
            SaveMerchantShareStatement($statementData);
            
            // If successful, release the savepoint
            if (!mysqli_query($conn, "RELEASE SAVEPOINT $savepoint")) {
                // Handle release error
            }
        } catch (Exception $e) {
            // If there was an error, roll back to the savepoint
            mysqli_query($conn, "ROLLBACK TO $savepoint");
            // Consider logging the error or taking other action here
        }
    }
    
    mysqli_stmt_close($stmt);   // Close the statement
    mysqli_free_result($result); // Free the result set

    // Commit the main transaction at the end
    mysqli_commit($conn);
}

function ProcessMerchantSharePurchase($purchaseID, $shareAmount) {
    // Use the mysqli connection from the global scope
    $conn = $GLOBALS["conn"];
    
    // Prepare the SQL statement to call the procedure
    $stmt = mysqli_prepare($conn, "CALL ProcessPurchase(?, ?)");

    // Check if the statement preparation was successful
    if (!$stmt) {
        return (new APIResponse(false, "Failed to prepare the statement", mysqli_error($conn)));
    }

    // Bind the parameters to the SQL statement
    mysqli_stmt_bind_param($stmt, 'ii', $purchaseID, $shareAmount);

    // Execute the SQL statement
    mysqli_stmt_execute($stmt);

    // If there's an error during execution
    if (mysqli_stmt_error($stmt)) {
        // Free the statement
        mysqli_stmt_close($stmt);
        return (new APIResponse(false, "Error occurred while processing the share purchase", mysqli_stmt_error($stmt)));
    }

    // If everything went smoothly
    else {
        // Free the statement
        mysqli_stmt_close($stmt);
        return (new APIResponse(true, "Merchant share purchase processed successfully", null));
    }
}


function PreProcessPurchase($purchase, $currentStatement)
{
    $preOwnedFullShares = $currentStatement["total_shares"];
    $preOwnedPartialShares = $currentStatement["fractional_shares"];

    $FullSharesPurchased = floor($purchase["SharesPurchased"]);

    $PartialSharesPurchased = $purchase["SharesPurchased"]-$FullSharesPurchased;

    $preProcessData["fullSharesPurchased"] = $FullSharesPurchased;
    $preProcessData["partialSharesPurchased"] = $PartialSharesPurchased;
    $preProcessData["preOwnedFullShares"] = $preOwnedFullShares;
    $preProcessData["preOwnedPartialShares"] = $preOwnedPartialShares;

    $partialShareSum = $preOwnedPartialShares+$PartialSharesPurchased;

    $completedShares = floor($partialShareSum);
    $remainingPartialShares = $partialShareSum-$completedShares;

    $preProcessData["completedShares"] = $completedShares;
    $preProcessData["remainingPartialShares"] = $remainingPartialShares;

    $sharesCertificatesToBeGiven = $completedShares+$FullSharesPurchased;

    $preProcessData["shareCertificatesToBeGivien"] = $sharesCertificatesToBeGiven;
    $preProcessData["currentStatementJSON"] = json_encode($currentStatement, JSON_PRETTY_PRINT); 
    $preProcessData["last_statement_date"] = $currentStatement["last_statement_date"];
    return $preProcessData;
}

function BuildStatement($accountId, $statement_date, $calc_interest = true)
{
    $interestRate = 0.005;
    $interestDate = date('Y-m-d', strtotime("$statement_date -1 month"));

    $statement = PullStatementFromDB($accountId, $statement_date);

    if (date('j', strtotime($statement_date)) == 1) {
        // If it is, subtract one month to get the first day of the previous month
        $lastStatementDate = date('Y-m-01', strtotime("$statement_date -1 month"));
    } else {
        // If not, simply set lastStatementDate to the first day of this month
        $lastStatementDate = date('Y-m-01', strtotime($statement_date));
    }

    if ($statement == null)
    {
        
        //$lastStatementDate = $interestDate;

        $statement["last_statement_date"] = $lastStatementDate;
        $purchases = PullPurchasesUntil($accountId, $statement_date);
        $totalPurchases = count($purchases);


        if ($totalPurchases <= 0)
        {
            
            $statement["needsFinalized"] = false;

            $statement["statementId"] = "TBD";
            $statement["accountId"] = $accountId;
            $statement["statement_date"] = $statement_date;
            $statement["statement_period"] = $interestDate;
            $statement["total_shares"] = 0;
            $statement["fractional_shares"] = 0;
        
            $statement["interestId"] = "TBD";
            $statement["interest_rate"] = $interestRate*100;
            $statement["interest_bearing_shares"] = 0;
            $statement["fractional_shares_earned"] = 0;
            $statement["new_full_shares_earned"] = 0;
            $statement["fractional_shares_after_interest"] = 0;
            $statement["shares_acquired_this_period"] = 0;
            $statement["SharesPurchasedThisPeriod"] = 0;

            $statement["last_statement_fractional_shares"] = 0;

            return $statement;
            //return null;
        }

        $historicalShares = PullHistoricalShares($accountId, $statement_date);
        $totalShares = count($historicalShares);
        $lastStatement = BuildStatement($accountId, $lastStatementDate);
        if ($lastStatement != null)
        {

            $lastStatementFractionalSharesAfterInterest = $lastStatement["fractional_shares_after_interest"];
        }
        else
        {
            $lastStatementFractionalSharesAfterInterest = 0;
        }
        $purchaseInfo = PullPeriodPurchaseInformation($accountId, $statement_date);
        $currentFractionalShares = $lastStatementFractionalSharesAfterInterest + $purchaseInfo["fractional"];
        $currentFractionalShares = $currentFractionalShares-floor($currentFractionalShares);
        $historicalInterestBearingShares = PullHistoricalShares($accountId, $lastStatementDate);
        $interestBearingShares = count($historicalInterestBearingShares);

        
        if ($calc_interest) {
            $interestEarnedThisMonth = multiply($interestBearingShares, $interestRate, 6); // Calculating with 4 places of precision.
            $fractionalSharesAfterInterest = add($currentFractionalShares, $interestEarnedThisMonth, 6); // Keep the precision while calculating.
        } else {
            $interestEarnedThisMonth = 0;
            $fractionalSharesAfterInterest = $currentFractionalShares;
        }
        
        $new_full_shares_earned = floor($fractionalSharesAfterInterest); // Full shares earned.
        $fractionalSharesAfterInterest = subtract($fractionalSharesAfterInterest, $new_full_shares_earned, 6); // Actual fractional shares after interest.


        $fullSharesAcquiredThisPeriod = $totalShares-$interestBearingShares;
        $sharesPurchasedThisPeriod = $purchaseInfo["fractional"]+$purchaseInfo["full"];

        $statement["needsFinalized"] = true;

        $statement["statementId"] = "TBD";
        $statement["accountId"] = $accountId;
        $statement["statement_date"] = $statement_date;
        $statement["statement_period"] = $interestDate;
        $statement["total_shares"] = $totalShares;
        $statement["fractional_shares"] = $currentFractionalShares;
    
        $statement["interestId"] = "TBD";
        $statement["interest_rate"] = $interestRate*100;
        $statement["interest_bearing_shares"] = $interestBearingShares;
        $statement["fractional_shares_earned"] = $interestEarnedThisMonth;
        $statement["new_full_shares_earned"] = $new_full_shares_earned;
        $statement["fractional_shares_after_interest"] = $fractionalSharesAfterInterest;
        $statement["shares_acquired_this_period"] = $fullSharesAcquiredThisPeriod;
        $statement["SharesPurchasedThisPeriod"] = $sharesPurchasedThisPeriod;

        $statement["last_statement_fractional_shares"] = $lastStatementFractionalSharesAfterInterest;
        

    }
    else
    {
        
        $statement["last_statement_date"] = $lastStatementDate;
        $statement["needsFinalized"] = false;
    }

    return $statement;
}

function PullStatementFromDB($accountId, $statement_date) {
    $query = "SELECT *
              FROM v_merchant_share_interest_statement 
              WHERE accountId = $accountId AND statement_date = '$statement_date'";
    $result = mysqli_query($GLOBALS["conn"], $query);
    if (!$result) {
        die("Error pulling statement from database: " . mysqli_error($GLOBALS["conn"]));
    }
    $row = mysqli_fetch_assoc($result);
    $result->free();  // Use the object-oriented style free
    //free_mysqli_resources($GLOBALS["conn"]);  // Use the function to free any remaining results
    return $row;
}

function PullPeriodPurchaseInformation($accountId, $statement_date) {
    // Calculate the start date (one month before targetDate).
    //$startDate = date('Y-m-d', strtotime("$statement_date -1 month"));

    if (date('j', strtotime($statement_date)) == 1) {
        // If it is, subtract one month to get the first day of the previous month
        $startDate = date('Y-m-01', strtotime("$statement_date -1 month"));
    } else {
        // If not, simply set lastStatementDate to the first day of this month
        $startDate = date('Y-m-01', strtotime($statement_date));
    }


    // Fetch the sum of fractional shares between startDate and targetDate.
    $query = "SELECT COALESCE(SUM(SharesPurchased - FLOOR(SharesPurchased)), 0) AS fractional, COALESCE(FLOOR(SUM(SharesPurchased)),0) as full
              FROM share_purchase 
              WHERE AccountId = $accountId 
              AND PurchaseDate >= '$startDate' AND PurchaseDate < '$statement_date'";
    $result = mysqli_query($GLOBALS["conn"], $query);
    if (!$result) {
        die("Error getting fractional shares from purchases: " . mysqli_error($GLOBALS["conn"]));
    }
    $row = mysqli_fetch_assoc($result);
    $result->free();  // Free the result
    //free_mysqli_resources($GLOBALS["conn"]);  // Use the function to free any remaining results
    return $row;
}

function PullHistoricalShares($accountId, $targetDate) {
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

function PullPurchasesUntil($accountId, $targetDate)
{
    $query = "SELECT Id as `purchase_id`, AccountId, Amount, Currency, SharesPurchased, PurchaseDate, ADAValue, ADA_USD_Closing 
    FROM kickbackdb.share_purchase 
    where accountId = $accountId and PurchaseDate < '$targetDate'";

    $result = mysqli_query($GLOBALS["conn"], $query);

    if (!$result) {
        die("Error purchases: " . mysqli_error($GLOBALS["conn"]));
    }
    $purchases = mysqli_fetch_all($result, MYSQLI_ASSOC);
    $result->free();  // Use the object-oriented style free
    //free_mysqli_resources($GLOBALS["conn"]);  // Use the function to free any remaining results
    return $purchases;
}

function PullPurchasesUntilForAll($targetDate) {
    $query = "SELECT share_purchase.Id as `purchase_id`, AccountId, Amount, Currency, SharesPurchased, PurchaseDate, ADAValue, ADA_USD_Closing, a.* 
    FROM kickbackdb.share_purchase left join v_account_info a on share_purchase.AccountId = a.Id
    where PurchaseDate < '$targetDate'";

    $result = mysqli_query($GLOBALS["conn"], $query);

    if (!$result) {
        die("Error getting purchases: " . mysqli_error($GLOBALS["conn"]));
    }
    $purchases = mysqli_fetch_all($result, MYSQLI_ASSOC);
    $result->free();  // Use the object-oriented style free
    //free_mysqli_resources($GLOBALS["conn"]);  // Use the function to free any remaining results
    return $purchases;
}

function PullPurchasesNotProcessed() {
    $query = "SELECT share_purchase.Id as `purchase_id`, AccountId, Amount, Currency, SharesPurchased, PurchaseDate, ADAValue, ADA_USD_Closing, a.* 
    FROM kickbackdb.share_purchase left join v_account_info a on share_purchase.AccountId = a.Id
    where processed = 0
    order by share_purchase.Id";

    $result = mysqli_query($GLOBALS["conn"], $query);

    if (!$result) {
        die("Error getting purchases: " . mysqli_error($GLOBALS["conn"]));
    }
    $purchases = mysqli_fetch_all($result, MYSQLI_ASSOC);
    $result->free();  // Use the object-oriented style free
    //free_mysqli_resources($GLOBALS["conn"]);  // Use the function to free any remaining results
    return $purchases;
}

function PullMerchantGuildPurchaseInformation($pid)
{
    // Get the global connection variable
    $conn = $GLOBALS["conn"];

    // Prepare SQL statement with placeholder
    $stmt = $conn->prepare("SELECT * from v_merchant_guild_share_processing_tasks WHERE purchase_id = ?");

    // Check if preparation is successful
    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }

    // Bind the parameter to the placeholder
    $stmt->bind_param("i", $pid); // Assuming 'Id' is an integer

    // Execute the statement
    if (!$stmt->execute()) {
        die("Error executing statement: " . $stmt->error);
    }

    // Fetch the result
    $result = $stmt->get_result();

    // Fetch data as an associative array
    $task = $result->fetch_assoc();

    // Free the result set
    $result->free();

    // Close the prepared statement
    $stmt->close();

    return $task;
}

function PullMerchantGuildProcessingTasks() {
    //v_merchant_guild_share_processing_tasks

    $query = "SELECT * from v_merchant_guild_share_processing_tasks where processed = 0";

    $result = mysqli_query($GLOBALS["conn"], $query);

    if (!$result) {
        die("Error getting tasks: " . mysqli_error($GLOBALS["conn"]));
    }
    $tasks = mysqli_fetch_all($result, MYSQLI_ASSOC);
    $result->free();  // Use the object-oriented style free
    //free_mysqli_resources($GLOBALS["conn"]);  // Use the function to free any remaining results
    return $tasks;
}

function PullMerchantGuildShareHolders() {
    $query = "SELECT a.*, s.shares FROM kickbackdb.v_total_owned_merchant_shares s left join v_account_info a on s.account_id = a.Id";
    $result = mysqli_query($GLOBALS["conn"], $query);

    if (!$result) {
        die("Error getting shareholders: " . mysqli_error($GLOBALS["conn"]));
    }
    $shareholders = mysqli_fetch_all($result, MYSQLI_ASSOC);
    $result->free();  // Use the object-oriented style free
    //free_mysqli_resources($GLOBALS["conn"]);  // Use the function to free any remaining results
    return $shareholders;
}

?>