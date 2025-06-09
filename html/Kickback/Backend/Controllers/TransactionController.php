<?php

declare(strict_types = 1);

namespace Kickback\Backend\Controllers;

use Kickback\Services\Database;

use Kickback\Backend\Models\Response;

use Exception;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vTransaction;
use Kickback\Backend\Views\vPrice;

class TransactionController extends DatabaseController
{
    protected static ?TransactionController $instance_ = null;
    protected array $batchInsertionParams;

    private function __construct()
    {
        $this->batchInsertionParams = [];
    }

    public static function runUnitTests()
    {

    }

    protected function allViewColumns() : string
    {
        return "
        ctime, 
        crand,
        amount,
        ref_currency_item_ctime,
        ref_currency_item_crand,
        FirstName,
        LastName,
        payed,
        complete,
        void,
        ref_account_ctime,
        ref_account_crand
        ";
    }

    protected function allTableColumns() : string
    {
        return "
        ctime, 
        crand,
        amount,
        ref_currency_item_ctime,
        ref_currency_item_crand,
        payed,
        complete,
        void,
        ref_account_ctime,
        ref_account_crand
        ";
    }

    protected function rowToView(array $row) : object  
    {
        $accountId = new vRecordId($row["ref_account_ctime"], $row["ref_account_crand"]);
        $currencyItem = new vRecordId($row["ref_currency_item_ctime"], $row["ref_currency_item_crand"]);
        $price = new vPrice($row["amount"], $currencyItem);

        return new vTransaction(
            $row["ctime"], 
            $row["crand"],
            $price,
            $row["FirstName"],
            $row["LastName"],
            $row["payed"],
            $row["complete"],
            $row["void"],
            $accountId
        );
    }

    protected function valuesToInsert(object $transaction) :  array
    {
        return [
            $transaction->ctime, 
            $transaction->crand,
            $transaction->amount,
            $transaction->currencyItem->ctime,
            $transaction->currencyItem->crand,
            (int)$transaction->payed,
            (int)$transaction->complete,
            (int)$transaction->void,
            $transaction->accountId->ctime,
            $transaction->accountId->crand
        ];
    }

    protected function tableName() : string
    {
        return "transaction";
    }

    public static function instance() : object
    {
        if(is_null(static::$instance_))
        {
            static::$instance_ = new static();
        }

        return static::$instance_;
    }

    public static function markAllInGroupAsPaid(vRecordId $groupId, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unkown error in marking all transactions in group as paid");



        $query = "UPDATE ".TransactionController::getTableName()." tx 
        LEFT JOIN ".TransactionCartTransactionGroupLinkController::getTableName()." txgrouplink
        ON txgrouplink.ref_transaction_ctime = tx.ctime AND txgrouplink.ref_transaction_crand = tx.crand 
        SET tx.payed = 1 
        WHERE txgrouplink.ref_cart_transaction_group_ctime = ? AND txgrouplink.ref_cart_transaction_group_crand = ?;";

        $params = [$groupId->ctime, $groupId->crand];

        try
        {
            if($databaseName != null)
            {
                Database::changeDatabase($databaseName);
            }

            $executeResp = TransactionController::executeQuery($query, $params);

            if($executeResp->success)
            {
                $resp->success = true;
                $resp->message = "successfully marked transactions as paid";
            }
            else
            {
                $resp->message = "Error in executing query to mark all transactions as paid : ".$executeResp->message;
            }
        }
        catch(Exception $e)
        {
            throw new Exception ("Exception caught while marking all transactions in group as paid : ".$e);
        }

        return $resp;
    }

    public static function markAllInGroupAsComplete(vRecordId $groupId, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "unkown error in marking all transactinos in group as complete", null);

        $query = "UPDATE ".TransactionController::getTableName()." tx
        LEFT JOIN ".TransactionCartTransactionGroupLinkController::getTableName()." txgrouplink
        ON txgrouplink.ref_transaction_ctime = tx.ctime AND txgrouplink.ref_transaction_crand = tx.crand
        SET tx.complete = 1 
        WHERE txgrouplink.ref_cart_transaction_group_ctime = ? AND txgrouplink.ref_cart_transaction_group_crand = ?;";

        $params = [$groupId->ctime, $groupId->crand];

        try
        {
            if($databaseName != null)
            {
                Database::changeDatabase($databaseName);
            }

            $updateResp = TransactionController::executeQuery($query, $params);

            if($updateResp->success)
            {
                $resp->success = true;
                $resp->message = "successfully marked all transactions in group as complete";
            }  
            else
            {
                $resp->message = "Error in updating transaction table as complete : ".$updateResp->message;
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught marking all transactions in group as complete : ".$e);
        }

        return $resp;
    }
}

?>