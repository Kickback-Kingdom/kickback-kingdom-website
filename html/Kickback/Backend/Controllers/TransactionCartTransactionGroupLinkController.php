<?php
declare(strict_types = 1);

namespace Kickback\Backend\Controllers;

use \Kickback\Services\Database;

use \Kickback\Backend\Models\Test;
use \Kickback\Backend\Models\Response;

use \Kickback\Backend\Views\vTransactionCartTransactionGroupLink;

use Exception;
use Kickback\Backend\Views\vRecordId;

class TransactionCartTransactionGroupLinkController extends DatabaseController
{

    protected static ?TransactionCartTransactionGroupLinkController $instance_ = null;
    protected array $batchInsertionParams;

    private function __construct()
    {
        $this->batchInsertionParams = [];
    }

    public static function runUnitTests()
    {

    }

    protected function allTableColumns() : string
    {
        return "
        ctime, 
        crand, 
        ref_transaction_ctime, 
        ref_transaction_crand, 
        ref_cart_transaction_group_ctime, 
        ref_cart_transaction_group_crand
        ";
    }

    protected function allViewColumns() : string
    {
        return "
        ctime, 
        crand, 
        ref_transaction_ctime, 
        ref_transaction_crand, 
        ref_cart_transaction_group_ctime, 
        ref_cart_transaction_group_crand
        ";
    }
    
    protected function rowToView(array $row) : object  
    {
        $transactionId = new vRecordId($row["ref_transaction_ctime"], $row["ref_transaction_crand"]);
        $cartTransactionGroupId = new vRecordId($row["ref_cart_transaction_ctime"], $row["ref_cart_transaction_group_crand"]);

        return new vTransactionCartTransactionGroupLink(
            $row["ctime"], 
            $row["crand"], 
            $transactionId, 
            $cartTransactionGroupId
        );
    }

    protected function valuesToInsert(object $transactionCartTransactionGroupLink) :  array
    {
        return [
            $transactionCartTransactionGroupLink->ctime, 
            $transactionCartTransactionGroupLink->crand, 
            $transactionCartTransactionGroupLink->transactionId->ctime, 
            $transactionCartTransactionGroupLink->transactionId->crand, 
            $transactionCartTransactionGroupLink->cartTransactionGroupId->ctime, 
            $transactionCartTransactionGroupLink->cartTransactionGroupId->crand
        ];
    }

    protected function tableName() : string
    {
        return "transaction_cart_transaction_group_link";
    }

    public static function instance() : object
    {
        if(is_null(static::$instance_))
        {
            static::$instance_ = new static();
        }

        return static::$instance_;
    }
}

?>