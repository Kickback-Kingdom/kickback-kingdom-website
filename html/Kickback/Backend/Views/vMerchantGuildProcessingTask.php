<?php 
declare(strict_types=1);

namespace Kickback\Backend\Views;


class vMerchantGuildProcessingTask
{
    public ?vSharePurchase $sharePurchase;

    public vDateTime $statement_date;
    public int $TaskType;
    public bool $processed;
}

?>