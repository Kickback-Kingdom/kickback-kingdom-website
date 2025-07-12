<?php
declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use Kickback\Backend\Views\vShareholder;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vSharePurchase;
use Kickback\Backend\Views\vDateTime;
use Kickback\Backend\Views\vDecimal;
use Kickback\Backend\Views\vMerchantGuildProcessingTask;
use Kickback\Backend\Views\vMerchantShareInterestStatement;
use Kickback\Backend\Views\vMerchantGuildPurchasePreProcessData;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Models\DecimalScale;
use Kickback\Services\Database;
use Kickback\Services\Session;

class MerchantGuildController
{
    
    public static function PullMerchantGuildShareHolders() : Response
    {
        $conn = Database::getConnection();
        $query = "SELECT a.*, s.shares FROM kickbackdb.v_total_owned_merchant_shares s left join v_account_info a on s.account_id = a.Id";

        $result = $conn->query($query);
        if (!$result) {
            return new Response(false, "Failed to get shareholders", []);
        }
        
        $objects = array_map([self::class, 'row_to_vShareholder'], $result->fetch_all(MYSQLI_ASSOC));
        return new Response(true, "shareholders", $objects);
    }

    public static function PullMerchantGuildPurchaseInformation(vRecordId $purchaseId): Response
    {
        $conn = Database::getConnection();

        $query = "SELECT 
                            sp.Id AS purchase_id,
                            sp.AccountId AS account_id,
                            a.*, 
                            sp.SharesPurchased AS SharesPurchased,
                            sp.PurchaseDate AS PurchaseDate,
                            sp.Amount AS Amount,
                            sp.Currency AS Currency,
                            sp.ADAValue AS ADAValue,
                            sp.ADA_USD_Closing AS ADA_USD_Closing, 
                            DATE_FORMAT(sp.PurchaseDate, '%Y-%m-01') + INTERVAL 1 MONTH AS statement_date,
                            0 AS TaskType,
                            IF(sp.processed = 1, TRUE, FALSE) AS processed
                            FROM 
                            share_purchase sp
                            LEFT JOIN 
                            v_account_info a ON sp.AccountId = a.Id
                            WHERE 
                            sp.Id = ?";
        $stmt = $conn->prepare($query);

        if (!$stmt) {
            return new Response(false, "Failed to prepare statement: " . $conn->error);
        }

        $stmt->bind_param("i", $purchaseId->crand);

        if (!$stmt->execute()) {
            return new Response(false, "Failed to execute statement: " . $stmt->error);
        }

        $result = $stmt->get_result();
        if (!$result) {
            return new Response(false, "Failed to fetch result: " . $stmt->error);
        }

        $row = $result->fetch_assoc();
        if (!$row) {
            return new Response(false, "No task found for purchase ID $purchaseId.");
        }

        $task = self::row_to_vMerchantGuildProcessingTask($row);

        return new Response(true, "merchant guild purchase task", $task);
    }

    public static function PullUnprocessedPurchasedShares(vRecordId $accountId, vDateTime $statementDate): Response
    {
        $conn = Database::getConnection();
    
        $timestamp = strtotime($statementDate->dbValue);
        $dayOfMonth = (int)date('j', $timestamp);
        $startDate = $dayOfMonth === 1
            ? date('Y-m-01', strtotime('-1 month', $timestamp))
            : date('Y-m-01', $timestamp);
    
        $query = "
            SELECT 
                COALESCE(SUM(SharesPurchased), 0) AS unprocessed
            FROM 
                share_purchase
            WHERE 
                AccountId = ? 
                AND PurchaseDate >= ? 
                AND PurchaseDate < ? 
                AND processed = 0
        ";
    
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            return new Response(false, "Failed to prepare query: " . $conn->error);
        }
    
        $stmt->bind_param("iss", $accountId->crand, $startDate, $statementDate->formattedYmd);
    
        if (!$stmt->execute()) {
            return new Response(false, "Failed to execute query: " . $stmt->error);
        }
    
        $result = $stmt->get_result();
        if (!$result) {
            return new Response(false, "Failed to fetch result: " . $stmt->error);
        }
    
        $row = $result->fetch_assoc();
        $stmt->close();
    
        $unprocessed = new vDecimal($row['unprocessed'] ?? '0', DecimalScale::SHARES);
        return new Response(true, "unprocessed shares this period", $unprocessed);
    }

    
    public static function PullPurchasesUntilForAll(vDateTime $targetDate) : Response {
        $conn = Database::getConnection();
        $query = "SELECT share_purchase.Id as `purchase_id`, AccountId, Amount, Currency, SharesPurchased, PurchaseDate, ADAValue, ADA_USD_Closing, a.* 
        FROM kickbackdb.share_purchase left join v_account_info a on share_purchase.AccountId = a.Id
        where PurchaseDate < ?";


        $stmt = $conn->prepare($query);
        if (!$stmt) {
            return new Response(false, "Failed to prepare query: " . $conn->error);
        }

        $stmt->bind_param("s", $targetDate);
        if (!$stmt->execute()) {
            return new Response(false, "Failed to execute query: " . $stmt->error);
        }

        $result = $stmt->get_result();
        if (!$result) {
            return new Response(false, "Failed to get result: " . $stmt->error);
        }

        $objects = array_map([self::class, 'row_to_vSharePurchase'], $result->fetch_all(MYSQLI_ASSOC));
        return new Response(true, "share purchases", $objects);
    }

    public static function PullPurchasesUntil(vRecordId $accountId, vDateTime $targetDate) : Response {
        $conn = Database::getConnection();
        $query = "SELECT share_purchase.Id as `purchase_id`, AccountId, Amount, Currency, SharesPurchased, PurchaseDate, ADAValue, ADA_USD_Closing, a.* 
        FROM kickbackdb.share_purchase left join v_account_info a on share_purchase.AccountId = a.Id
        where accountId = ? and PurchaseDate < ?";


        $stmt = $conn->prepare($query);
        if (!$stmt) {
            return new Response(false, "Failed to prepare query: " . $conn->error);
        }

        $stmt->bind_param("is", $accountId->crand, $targetDate->dbValue);
        if (!$stmt->execute()) {
            return new Response(false, "Failed to execute query: " . $stmt->error);
        }

        $result = $stmt->get_result();
        if (!$result) {
            return new Response(false, "Failed to get result: " . $stmt->error);
        }

        $objects = array_map([self::class, 'row_to_vSharePurchase'], $result->fetch_all(MYSQLI_ASSOC));
        return new Response(true, "share purchases", $objects);
    }

    
    public static function PullMerchantGuildProcessingTasks() : Response {
        //v_merchant_guild_share_processing_tasks
        $conn = Database::getConnection();

        //$query = "SELECT purchase_id, account_id, Username, avatar_media, SharesPurchased, execution_date, Amount, Currency, ADAValue, statement_date, TaskType, processed from v_merchant_guild_share_processing_tasks where processed = 0";
        $query = "SELECT * 
                    FROM (
                        SELECT 
                            sp.Id AS purchase_id,
                            sp.AccountId AS account_id,
                            a.*, 
                            sp.SharesPurchased AS SharesPurchased,
                            sp.PurchaseDate AS PurchaseDate,
                            sp.Amount AS Amount,
                            sp.Currency AS Currency,
                            sp.ADAValue AS ADAValue,
                            DATE_FORMAT(sp.PurchaseDate, '%Y-%m-01') + INTERVAL 1 MONTH AS statement_date,
                            0 AS TaskType,
                            IF(sp.processed = 1, TRUE, FALSE) AS processed,
                            sp.ADA_USD_Closing
                            FROM 
                            share_purchase sp
                            LEFT JOIN 
                            v_account_info a ON sp.AccountId = a.Id
                            WHERE 
                            sp.processed = 0

                            UNION

                            SELECT 
                            NULL AS purchase_id,
                            NULL AS account_id,
                            a.*, 
                            NULL AS SharesPurchased,
                            vmsps.statement_date AS PurchaseDate,
                            NULL AS Amount,
                            NULL AS Currency,
                            NULL AS ADAValue,
                            vmsps.statement_date AS statement_date,
                            1 AS TaskType,
                            IF(vmsps.percent_complete = 1, TRUE, FALSE) AS processed,
                            NULL as ADA_USD_Closing
                            FROM 
                            v_monthly_statement_process_status vmsps
                            LEFT JOIN 
                            v_account_info a ON a.Id IS NULL
                            WHERE 
                            vmsps.percent_complete <> 1
                        ) AS all_tasks
                    ORDER BY 
                        statement_date,
                        TaskType,
                        purchase_id;";
        $result = $conn->query($query);
        if (!$result) {
            return new Response(false, "Failed to get merchant guild processing tasks", []);
        }
        
        $objects = array_map([self::class, 'row_to_vMerchantGuildProcessingTask'], $result->fetch_all(MYSQLI_ASSOC));
        return new Response(true, "merchant guild processing tasks", $objects);
    }

    public static function PullStatementFromDB(vRecordId $accountId, vDateTime $statementDateTime): Response
    {
        $conn = Database::getConnection();

        $query = "SELECT * FROM v_merchant_share_interest_statement 
                WHERE accountId = ? AND statement_date = ?";

        $stmt = $conn->prepare($query);

        if (!$stmt) {
            return new Response(false, "Failed to prepare statement: " . $conn->error);
        }

        $stmt->bind_param("is", $accountId->crand, $statementDateTime->dbValue);

        if (!$stmt->execute()) {
            return new Response(false, "Failed to execute statement: " . $stmt->error);
        }

        $result = $stmt->get_result();

        if (!$result) {
            return new Response(false, "Failed to fetch result: " . $stmt->error);
        }

        $row = $result->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return new Response(false, "No statement found for the provided account and date.");
        }

        return new Response(true, "statement found", self::row_to_vMerchantShareInterestStatement($row));
    }

    
    public static function PullHistoricalShares(vRecordId $accountId, vDateTime $targetDate): Response
    {
        $conn = Database::getConnection();
    
        // Use a prepared statement even for CALL to avoid injection issues
        $stmt = $conn->prepare("CALL GetHistoricalLoot(?, ?, 16)");
    
        if (!$stmt) {
            return new Response(false, "Failed to prepare stored procedure: " . $conn->error);
        }
    
        $accountIdValue = $accountId->crand;
        $dateString = $targetDate->dbValue;
    
        $stmt->bind_param("is", $accountIdValue, $dateString);
    
        if (!$stmt->execute()) {
            return new Response(false, "Failed to execute stored procedure: " . $stmt->error);
        }
    
        $result = $stmt->get_result();
    
        if (!$result) {
            return new Response(false, "Failed to fetch result: " . $stmt->error);
        }
    
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->free_result();
        $stmt->close();
    
        // Convert each row to a vLoot object
        $lootObjects = array_map(fn($row) => LootController::row_to_vLoot($row, false), $rows);

        return new Response(true, "historical shares", $lootObjects);
    }

    public static function PullPeriodPurchaseInformation(vRecordId $accountId, vDateTime $statementDate): Response
    {
        $conn = Database::getConnection();

        // Determine the start date (first of the previous or current month)
        $timestamp = strtotime($statementDate->dbValue);
        $dayOfMonth = (int)date('j', $timestamp);
        $startDate = $dayOfMonth === 1
            ? date('Y-m-01', strtotime('-1 month', $timestamp))
            : date('Y-m-01', $timestamp);

        // Prepared query to safely get fractional and full shares
        $query = "
            SELECT 
                COALESCE(SUM(SharesPurchased - FLOOR(SharesPurchased)), 0) AS fractional, 
                COALESCE(FLOOR(SUM(SharesPurchased)), 0) AS full
            FROM 
                share_purchase 
            WHERE 
                AccountId = ? 
                AND PurchaseDate >= ? 
                AND PurchaseDate < ?
        ";

        $stmt = $conn->prepare($query);
        if (!$stmt) {
            return new Response(false, "Failed to prepare statement: " . $conn->error);
        }

        $accountIdValue = $accountId->crand;
        $stmt->bind_param("iss", $accountIdValue, $startDate, $statementDate->formattedYmd);

        if (!$stmt->execute()) {
            return new Response(false, "Failed to execute statement: " . $stmt->error);
        }

        $result = $stmt->get_result();
        if (!$result) {
            return new Response(false, "Failed to fetch result: " . $stmt->error);
        }

        $row = $result->fetch_assoc();
        $stmt->close();

        // Convert to vDecimal
        $data = [
            'fractional' => new vDecimal($row['fractional'], DecimalScale::SHARES),
            'full' => new vDecimal($row['full'], DecimalScale::SHARES),
        ];

        return new Response(true, "period purchase breakdown", $data);
    }

    public static function PreProcessPurchase(vMerchantGuildProcessingTask $purchaseTask, vMerchantShareInterestStatement $currentStatement) : vMerchantGuildPurchasePreProcessData
    {
        $startingShares = $currentStatement->getStartingShares();
        $preOwnedFullShares = $currentStatement->totalShares;
        $preOwnedPartialShares = $currentStatement->fractionalShares;

        $fullSharesPurchased = $purchaseTask->sharePurchase->SharesPurchased->toWholeUnitsInt();
        $partialSharesPurchased = $purchaseTask->sharePurchase->SharesPurchased->getFractional();

        $partialShareSum = $preOwnedPartialShares->add($partialSharesPurchased);
        $completedShares = $partialShareSum->toWholeUnitsInt();
        $remainingPartialShares = $partialShareSum->getFractional();

        $shareCertificatesToBeGiven = $completedShares+$fullSharesPurchased;

        return new vMerchantGuildPurchasePreProcessData(
            $fullSharesPurchased,
            $partialSharesPurchased,
            $preOwnedFullShares,
            $preOwnedPartialShares,
            $completedShares,
            $remainingPartialShares,
            $shareCertificatesToBeGiven,
            json_encode($currentStatement, JSON_PRETTY_PRINT),
            $currentStatement->getPriorIssuedDate()
        );
    }

    public static function ProcessMonthlyStatements(vDateTime $issuedDate): Response
    {
        $conn = Database::getConnection();
        $dateStr = $issuedDate->formattedYmd;
        $error = null;
        mysqli_begin_transaction($conn);
        $allSuccess = true;

        $query = "SELECT DISTINCT AccountId FROM share_purchase WHERE PurchaseDate < ?";

        $stmt = $conn->prepare($query);
        if (!$stmt) {
            mysqli_rollback($conn);
            return new Response(false, "Database prepare failed: " . $conn->error);
        }

        if (!$stmt->bind_param('s', $dateStr) || !$stmt->execute()) {
            $stmt->close();
            mysqli_rollback($conn);
            return new Response(false, "Failed to bind parameters or execute: " . $stmt->error);
        }

        $result = $stmt->get_result();
        if (!$result) {
            $stmt->close();
            mysqli_rollback($conn);
            return new Response(false, "Failed to get result: " . $stmt->error);
        }

        try {
            while ($row = $result->fetch_assoc()) {
                $accountId = new vRecordId('', (int)($row['AccountId'] ?? -1));

                if ($accountId->crand <= 0) {
                    $allSuccess = false;
                    break;
                }

                try {
                    // You would replace these with real calls (pseudo-code based on your old function)
                    $statementData = self::BuildStatement($accountId, $issuedDate);

                    self::GiveNewFullSharesAfterInterest($statementData);
                    self::SaveMerchantShareStatement($statementData);

                } catch (\Throwable $e) {
                    $error = $e;
                    $allSuccess = false;
                    break;
                }
            }
        } finally {
            $stmt->close();
            if ($result) {
                $result->free();
            }
        }

        if ($allSuccess) {
            mysqli_commit($conn);
            return new Response(true, "Monthly statements processed successfully");
        } else {
            mysqli_rollback($conn);
            return new Response(false, sprintf(
                "An error occurred while processing monthly statements. Rolling back transaction.\n\nError Details:\nMessage: %s\nFile: %s\nLine: %d\nTrace:\n%s",
                $error->getMessage(),
                $error->getFile(),
                $error->getLine(),
                $error->getTraceAsString()
            ));
            
            //return new Response(false, "Errors occurred during processing; all changes were rolled back. ".$error->getMessage());
        }
    }

    public static function SaveMerchantShareStatement(vMerchantShareInterestStatement $statement): Response
    {
        $conn = Database::getConnection();

        $stmt = $conn->prepare("CALL SaveStatement(?, ?, ?, ?, ?, ?, ?)");

        if (!$stmt) {
            return new Response(false, "Failed to prepare the SaveStatement procedure.", $conn->error);
        }

        $accountId = $statement->accountId->crand;
        $issuedDate = $statement->issuedDate->formattedYmd;
        $totalShares = $statement->totalShares;
        $interestBearingShares = $statement->interestBearingShares;
        $fractionalShares = $statement->fractionalShares->toString();
        $fractionalSharesEarned = $statement->fractionalSharesEarned->toString();
        $interestRate = $statement->interestRate->toString();

        $stmt->bind_param(
            'isiisss', 
            $accountId,
            $issuedDate,
            $totalShares,
            $interestBearingShares,
            $fractionalShares,
            $fractionalSharesEarned,
            $interestRate
        );

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            return new Response(false, "Failed to execute SaveStatement procedure.", $error);
        }

        $stmt->close();
        return new Response(true, "Merchant share statement saved successfully.", null);
    }


    public static function GiveNewFullSharesAfterInterest(vMerchantShareInterestStatement $statement): void
    {
        $newShares = $statement->newFullSharesEarned;
    
        // Ensure newShares is an integer and greater than zero
        if (is_int($newShares) && $newShares > 0) {
            for ($i = 0; $i < $newShares; $i++) {
                LootController::giveMerchantGuildShare($statement->accountId, $statement->issuedDate);
            }
        }
    }
    

    public static function ProcessMerchantSharePurchase(vRecordId $purchaseId, int $sharesToGive): Response
    {
        $conn = Database::getConnection();

        $stmt = $conn->prepare("CALL ProcessPurchase(?, ?)");

        if (!$stmt) {
            return new Response(false, "Failed to prepare statement: " . $conn->error);
        }

        $purchaseIdValue = $purchaseId->crand;

        $stmt->bind_param('ii', $purchaseIdValue, $sharesToGive);

        if (!$stmt->execute()) {
            return new Response(false, "Failed to execute stored procedure: " . $stmt->error);
        }

        $stmt->close();
        return new Response(true, "Merchant share purchase processed successfully");
    }


    public static function BuildStatement(vRecordId $accountId, vDateTime $issuedDate, bool $calcInterest = true, bool $pullExisting = true): vMerchantShareInterestStatement
    {
        $interestRate = new vDecimal("0.005", 6);
        $reportingPeriod = $issuedDate->subMonths(1);
    
        if ($pullExisting)
        {

        // Try loading an existing statement from the DB
        $existing = MerchantGuildController::PullStatementFromDB($accountId, $issuedDate)->data;
        if ($existing instanceof vMerchantShareInterestStatement) {
            $existing->needsFinalized = false;
            return $existing;
        }
    
        }
        // If no purchases, return an empty finalized statement
        $purchases = MerchantGuildController::PullPurchasesUntil($accountId, $issuedDate)->data;
        if (empty($purchases)) {
            return vMerchantShareInterestStatement::buildEmptyFinalizedStatement($accountId, $issuedDate, $reportingPeriod, $interestRate);
        }


        $priorStatement = self::BuildStatement($accountId, $reportingPeriod, $calcInterest, $pullExisting);

        $totalShares = count(MerchantGuildController::PullHistoricalShares($accountId, $issuedDate)->data);// + $priorStatement->newFullSharesEarned;
       // $totalShares = count(MerchantGuildController::PullHistoricalShares($accountId, $issuedDate)->data);
        $lastFractional = $priorStatement
                            ? $priorStatement->fractionalSharesAfterInterest
                            : vDecimal::Zero(DecimalScale::SHARES);
                        
        $purchaseInfo = MerchantGuildController::PullPeriodPurchaseInformation($accountId, $issuedDate)->data;
        $sharesPurchased = $purchaseInfo['fractional']->add($purchaseInfo['full']);
        
    
        $interestEligibleShares = count(MerchantGuildController::PullHistoricalShares($accountId, $reportingPeriod)->data);
    
        $interestEarned = $calcInterest
                            ? $interestRate->mulWhole($interestEligibleShares)
                            : vDecimal::Zero(DecimalScale::SHARES);

        // Unified sum of fractional carryover and this month's purchase
        $preInterestCombined = $lastFractional->add($sharesPurchased);

        $preInterestFractional = $preInterestCombined->getFractional();
        $postInterestCombined = $preInterestFractional->add($interestEarned);
        $convertedFromInterest = $postInterestCombined->toWholeUnitsInt();
        $remainingFractional = $postInterestCombined->getFractional();

        $unprocessedResp = self::PullUnprocessedPurchasedShares($accountId, $issuedDate);
        $unprocessedShares = $unprocessedResp->success ? $unprocessedResp->data : vDecimal::Zero(DecimalScale::SHARES);
        
        $priorUnprocessed = $priorStatement->unprocessedPurchasedSharesThisPeriod ?? vDecimal::Zero(DecimalScale::SHARES);

        // Build statement
        $stmt = new vMerchantShareInterestStatement();
        $stmt->needsFinalized = true;
        $stmt->accountId = $accountId;
        $stmt->issuedDate = $issuedDate;
        $stmt->reportingPeriod = $reportingPeriod;
        $stmt->interestId = new vRecordId();

        $stmt->totalShares = $totalShares;
        $stmt->lastStatementFractionalShares = $lastFractional;
        $stmt->interestBearingShares = $interestEligibleShares;
        $stmt->unprocessedPurchasedSharesThisPeriod = $unprocessedShares;

        $stmt->sharesAcquiredThisPeriod = $preInterestCombined->toWholeUnitsInt();
        $stmt->sharesPurchasedThisPeriod = $sharesPurchased;
        $stmt->fractionalShares = $preInterestFractional;
        $stmt->interestRate = $interestRate;

        $stmt->fractionalSharesEarned = $interestEarned;
        $stmt->fractionalSharesAfterInterest = $remainingFractional;
        $stmt->newFullSharesEarned = $convertedFromInterest;
        $stmt->paymentDate = null;
        
        return $stmt;
    }
    
    

    private static function row_to_vMerchantShareInterestStatement(array $row): vMerchantShareInterestStatement
    {
        return vMerchantShareInterestStatement::fromDbRow($row);
    }
    

    private static function row_to_vMerchantGuildProcessingTask(array $row) : vMerchantGuildProcessingTask {
        $task = new vMerchantGuildProcessingTask();

        if ($row["purchase_id"] != null)
            $task->sharePurchase = self::row_to_vSharePurchase($row);
        
        $task->statement_date = vDateTime::fromDB((string)($row['statement_date']));
        $task->TaskType = (int)($row['TaskType'] ?? 0);
        $task->processed = (bool)($row['processed'] ?? false);
    
        return $task;
    }


    private static function row_to_vShareholder(array $row) : vShareholder {

        $shareholder = new vShareholder();
        $shareholder->account = AccountController::row_to_vAccount($row);
        $shareholder->shares = (int)$row["shares"];

        return $shareholder;
    }

    
    private static function row_to_vSharePurchase(array $row) : vSharePurchase {

        $sharePurchase = new vSharePurchase('', (int)$row['purchase_id']);
        $sharePurchase->account = AccountController::row_to_vAccount($row);
        
        $sharePurchase->Amount = new vDecimal($row['Amount'], DecimalScale::SHARES);
        $sharePurchase->Currency = (string)($row['Currency'] ?? '');
        $sharePurchase->SharesPurchased = new vDecimal($row['SharesPurchased'], DecimalScale::SHARES);
        $sharePurchase->PurchaseDate = vDateTime::fromDB((string)$row['PurchaseDate']);
        $sharePurchase->ADAValue = new vDecimal($row['ADAValue'], DecimalScale::SHARES);
        $sharePurchase->ADA_USD_Closing = new vDecimal($row['ADA_USD_Closing'], DecimalScale::SHARES);

        return $sharePurchase;
    }
}

?>