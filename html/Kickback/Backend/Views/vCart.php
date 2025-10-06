<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

use \Kickback\Backend\Models\RecordId;

use \Exception;
use Kickback\Backend\Controllers\StoreController;
use Kickback\Services\Database;

class vCart extends vRecordId
{
    public vAccount $account;
    public vStore $store;
    public vTransaction $transaction;



    public bool $checkedOut;
    public bool $void;

    public array $cartProducts;

    public array $totals;
}



?>