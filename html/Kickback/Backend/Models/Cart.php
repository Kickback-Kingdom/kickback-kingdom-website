<?php

declare(strict_types = 1);

namespace Kickback\Backend\Models;

class Cart extends RecordId
{
    public string $accountCtime;
    public int $accountCrand;
    public string $storeCtime;
    public int $storeCrand;
    public ?string $transactionCtime;
    public ?int $transactionCrand;

    public function __construct(
        string $accountCtime,
        int $accountCrand,
        string $storeCtime,
        int $storeCrand,
        ?string $transactionCtime = null,
        ?int $transactionCrand = null
    )
    {
        parent::__construct();

        $this->accountCtime = $accountCtime;
        $this->accountCrand = $accountCrand;
        $this->storeCtime = $storeCtime;
        $this->storeCrand = $storeCrand;
        $this->transactionCtime = $transactionCtime;
        $this->transactionCrand = $transactionCrand;
    }
}
?>