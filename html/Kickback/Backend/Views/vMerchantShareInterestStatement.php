<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

use Kickback\Backend\Models\DecimalScale;

class vMerchantShareInterestStatement extends vRecordId
{
    public vRecordId $accountId;
    public vRecordId $interestId;

    public vDateTime $issuedDate;                   // Corresponds to statement_date
    public vDateTime $reportingPeriod;              // statement_date - 1 month

    public int $totalShares;                        //full shares after purchases
    public int $interestBearingShares;
    public int $sharesAcquiredThisPeriod;
    public vDecimal $unprocessedPurchasedSharesThisPeriod;

    public vDecimal $sharesPurchasedThisPeriod;
    public int $sharesTradedThisPeriod;
    public vDecimal $fractionalShares;              //partial shares after purchases
    public vDecimal $fractionalSharesEarned;
    public vDecimal $fractionalSharesAfterInterest;
    public int $newFullSharesEarned;

    public vDecimal $interestRate;
    public ?vDateTime $paymentDate;

    public vDecimal $lastStatementFractionalShares;

    public bool $needsFinalized;

    public function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }

    public function getPriorIssuedDate() : vDateTime {
        return $this->issuedDate->subMonths(1);
    }

    public function getStartingShares(): vDecimal {
        return $this->fractionalShares
            ->addWhole($this->totalShares)
            ->sub($this->getProcessedPurchases())
            ->subWhole($this->getNetSharesTradedThisPeriod());
    }
    

    public function getWholePurchasedShares() : int {
        return $this->getTotalPurchasedShares()->toWholeUnitsInt();
    }

    public function getTotalPurchasedShares() : vDecimal {
        return $this->sharesPurchasedThisPeriod;
    }

    public function getProcessedPurchases() : vDecimal {
        return $this->getTotalPurchasedShares()->sub($this->getUnprocessedSharesPurchased());

    }

    public function getNetSharesTradedThisPeriod(): int {
        
        return $this->sharesAcquiredThisPeriod - $this->getWholePurchasedShares() - $this->getCompletedSharesFromCarryoverPreInterest();
    }

    public function getTotalGainedBeforeInterest() : vDecimal {
        return $this->getTotalPurchasedShares()->addWhole($this->getNetSharesTradedThisPeriod());
    }

    public function getTotalOwnedBeforeInterest() : vDecimal {
        return $this->getTotalGainedBeforeInterest()->add($this->getStartingShares());
    }

    public function getEndingShares() : vDecimal {
        return $this->fractionalShares->addWhole($this->totalShares)->add($this->fractionalSharesEarned);
    }

    public function getRemainingFractional() : vDecimal {
        return $this->getEndingShares()->getFractional();
    }

    public function getCompletedSharesFromCarryoverPreInterest(): int {
        return $this->lastStatementFractionalShares
            ->add($this->sharesPurchasedThisPeriod->getFractional())
            ->toWholeUnitsInt();
    }

    public function getUnprocessedSharesPurchased(): vDecimal {
        return $this->unprocessedPurchasedSharesThisPeriod;
    }
    

    public static function fromDbRow(array $row): self
    {
        $stmt = new self('', (int)($row['statementId'] ?? -1));

        $stmt->accountId = new vRecordId('', (int)($row['accountId'] ?? -1));
        $stmt->interestId = new vRecordId('', (int)($row['interestId'] ?? -1));

        $stmt->issuedDate = vDateTime::fromDB($row['statement_date']);
        $stmt->reportingPeriod = vDateTime::fromDB($row['statement_period']);

        $stmt->totalShares = (int)$row['total_shares'];
        $stmt->interestBearingShares = (int)$row['interest_bearing_shares'];
        $stmt->sharesAcquiredThisPeriod = (int)$row['shares_acquired_this_period'];

        $stmt->sharesPurchasedThisPeriod = new vDecimal($row['SharesPurchasedThisPeriod'], DecimalScale::SHARES);
        $stmt->fractionalShares = new vDecimal($row['fractional_shares'], DecimalScale::SHARES);
        $stmt->fractionalSharesEarned = new vDecimal($row['fractional_shares_earned'], DecimalScale::SHARES);
        $stmt->fractionalSharesAfterInterest = new vDecimal($row['fractional_shares_after_interest'], DecimalScale::SHARES);
        $stmt->newFullSharesEarned = (int)$row['new_full_shares_earned'];

        $stmt->interestRate = new vDecimal($row['interest_rate'] ?? '0', DecimalScale::INTEREST_RATE);
        $stmt->paymentDate = isset($row['payment_date']) ? vDateTime::fromDB($row['payment_date']) : null;

        $stmt->lastStatementFractionalShares = new vDecimal($row['last_statement_fractional_shares'], DecimalScale::SHARES);
        
        $stmt->unprocessedPurchasedSharesThisPeriod = new vDecimal($row['unprocessed_purchased_shares_this_period'] ?? '0', DecimalScale::SHARES);
        return $stmt;
    }

    public static function buildEmptyFinalizedStatement(
        vRecordId $accountId,
        vDateTime $issuedDate,
        vDateTime $reportingPeriod,
        vDecimal $interestRate
    ): vMerchantShareInterestStatement {
        $stmt = new vMerchantShareInterestStatement();
        $stmt->needsFinalized = false;
        $stmt->accountId = $accountId;
        $stmt->issuedDate = $issuedDate;
        $stmt->reportingPeriod = $reportingPeriod;
        $stmt->interestId = new vRecordId();
        $stmt->unprocessedPurchasedSharesThisPeriod = vDecimal::Zero(DecimalScale::SHARES);

        $stmt->totalShares = 0;
        $stmt->interestBearingShares = 0;
        $stmt->sharesAcquiredThisPeriod = 0;
        $stmt->sharesPurchasedThisPeriod = vDecimal::Zero(DecimalScale::SHARES);
        $stmt->fractionalShares = vDecimal::Zero(DecimalScale::SHARES);
        $stmt->fractionalSharesEarned = vDecimal::Zero(DecimalScale::SHARES);
        $stmt->fractionalSharesAfterInterest = vDecimal::Zero(DecimalScale::SHARES);
        $stmt->newFullSharesEarned = 0;
        $stmt->interestRate = $interestRate;
        $stmt->paymentDate = null;
        $stmt->lastStatementFractionalShares = vDecimal::Zero(DecimalScale::SHARES);
    
        return $stmt;
    }
}
?>