<?php 
declare(strict_types=1);

namespace Kickback\Backend\Views;


class vSharePurchase extends vRecordId
{
    public vAccount $account;

    public vDecimal $Amount;
    public string $Currency;
    public vDecimal $SharesPurchased;
    public vDateTime $PurchaseDate;
    public vDecimal $ADAValue;
    public vDecimal $ADA_USD_Closing;
    
    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);


    }
}

?>