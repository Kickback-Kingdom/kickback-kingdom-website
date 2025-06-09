<?php

declare(strict_types = 1);

namespace Kickback\Backend\Controllers;

use \Kickback\Services\Database;

use \Kickback\Backend\Models\CartTransactionGroup;
use \Kickback\Backend\Models\Response;

use \Kickback\Backend\Views\vCartTransactionGroup;
use \Kickback\Backend\Views\vRecordId;

use Exception;


class CartTransactionGroupController extends DatabaseController
{

    protected static ?CartTransactionGroupController $instance_ = null;
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
        payed, 
        completed, 
        void,
        ref_cart_ctime, 
        ref_cart_crand
        ";
    }

    protected function allViewColumns() : string
    {
        return "
        ctime, 
        crand, 
        payed, 
        completed, 
        void,
        ref_cart_ctime, 
        ref_cart_crand
        ";
    }
    
    protected function rowToView(array $row) : object  
    {
        $cartId = new vRecordId($row["ref_cart_ctime"], $row["ref_cart_crand"]);
        
        return new vCartTransactionGroup(
            $row["ctime"], 
            $row["crand"], 
            $cartId,
            (bool)$row["payed"], 
            (bool)$row["completed"],
            (bool)$row["void"]
        );
    }

    protected function valuesToInsert(object $transactionGroup) :  array
    {
        return [
            $transactionGroup->ctime, 
            $transactionGroup->crand,
            $transactionGroup->payed,
            $transactionGroup->completed,
            $transactionGroup->void,
            $transactionGroup->cartId->ctime,
            $transactionGroup->cartId->crand
        ];
    }

    protected function tableName() : string
    {
        return "cart_transaction_group";
    }

    public static function instance() : object
    {
        if(is_null(static::$instance_))
        {
            static::$instance_ = new static();
        }

        return static::$instance_;
    }

    public static function markAsPaid(vRecordId $cartTransactionGroupId, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unkown error in marking transaction gropu as paid", null);

        $query = "UPDATE ".CartTransactionGroupController::getTableName()." 
        SET payed = 1 
        WHERE ctime = ? AND crand = ?;"; 

        $params = [$cartTransactionGroupId->ctime, $cartTransactionGroupId->crand];
        
        try
        {
            if($databaseName != null)
            {
                Database::changeDatabase($databaseName);
            }

            $executeResp = CartTransactionGroupController::executeQuery($query, $params);

            if($executeResp->success)
            {
                $resp->success = true;
                $resp->message = "Successfully marked transaction as paid";
            }
            else
            {
                $resp->message = "Error in marking transaction group as paid : ".$executeResp->message  ;
            }
        }
        catch(Exception $e)
        {
            throw new Exception();
        }

        return $resp;
    }

    public static function markAssociatedTransactionAsPaid(vRecordId $cartTransactionGroupId, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unkown error in marking associated transactions as paid");

        try
        {
            $markTransactionsResp = TransactionController::markAllInGroupAsPaid($cartTransactionGroupId, $databaseName);

            if($markTransactionsResp->success)
            {
                $markTransactionGroupResp = CartTransactionGroupController::markAsPaid($cartTransactionGroupId, $databaseName);
                
                if($markTransactionGroupResp->success)
                {
                    $resp->success = true;
                    $resp->message = "Successfully marked all associated transactions as paid";
                }
                else
                {
                    $resp->message = "Error in marking transaction group as paid : ".$markTransactionGroupResp->message;
                }
            }
            else
            {
                $resp->message = "Error in marking transactions as paid : ".$markTransactionsResp->message;
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught marking all transactions as paid : ".$e);
        }

        return $resp;
    }

    public static function markAssociatedTransactionAsComplete(vRecordId $cartTransactionGroupId, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "unkown error in marking all associated transactions as complete", null);

        try
        {
            $transactionMarkResp = TransactionController::markAllInGroupAsComplete($cartTransactionGroupId, $databaseName);

            if($transactionMarkResp->success)
            {
                $transactionGroupMarkResp = CartTransactionGroupController::markAsComplete($cartTransactionGroupId, $databaseName);

                if($transactionGroupMarkResp->success)
                {
                    $resp->success = true;
                    $resp->message = "Successfully marked all associated transactions as complete";
                }
                else
                {
                    $resp->message = "Error in marking transaction group as complete : ".$transactionGroupMarkResp->message;
                }
            }
            else
            {
                $resp->message = "Error in marking all transactions in group as complete : ".$transactionMarkResp->message;
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Execption caught marking all associated transactions as complete : ".$e);
        }

        return $resp;
    }

    private static function markAsComplete(vRecordId $cartTransactionGroupId, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "unkown error in marking transaction group as complete", null);

        $query = "UPDATE ".CartTransactionGroupController::getTableName()." 
        SET completed = 1 
        WHERE ctime = ? AND crand = ?;";

        $params = [$cartTransactionGroupId->ctime, $cartTransactionGroupId->crand];

        try
        {
            if($databaseName != null)
            {
                Database::changeDatabase($databaseName);
            }

            $executeResp = CartTransactionGroupController::executeQuery($query, $params);

            if($executeResp->success)
            {
                $resp->success = true;
                $resp->message = "successfully marked transaction group as complete";
            }
            else
            {
                $resp->message = "error in marking transaction group as complete : ".$executeResp->message;
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught marking transaction group as complete : ".$e);
        }

        return $resp;
    }
}

?>