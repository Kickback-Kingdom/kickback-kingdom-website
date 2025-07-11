<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

class vMerchantGuildPurchasePreProcessData
{
    public int $fullSharesPurchased;
    public vDecimal $partialSharesPurchased;

    public int $preOwnedFullShares;
    public vDecimal $preOwnedPartialShares;

    public int $completedShares;
    public vDecimal $remainingPartialShares;

    public int $shareCertificatesToBeGiven;

    public string $currentStatementJSON;
    public vDateTime $lastStatementDate;

    public function __construct(
        int $fullSharesPurchased,
        vDecimal $partialSharesPurchased,
        int $preOwnedFullShares,
        vDecimal $preOwnedPartialShares,
        int $completedShares,
        vDecimal $remainingPartialShares,
        int $shareCertificatesToBeGiven,
        string $currentStatementJSON,
        vDateTime $lastStatementDate
    ) {
        $this->fullSharesPurchased = $fullSharesPurchased;
        $this->partialSharesPurchased = $partialSharesPurchased;
        $this->preOwnedFullShares = $preOwnedFullShares;
        $this->preOwnedPartialShares = $preOwnedPartialShares;
        $this->completedShares = $completedShares;
        $this->remainingPartialShares = $remainingPartialShares;
        $this->shareCertificatesToBeGiven = $shareCertificatesToBeGiven;
        $this->currentStatementJSON = $currentStatementJSON;
        $this->lastStatementDate = $lastStatementDate;
    }
}
?>