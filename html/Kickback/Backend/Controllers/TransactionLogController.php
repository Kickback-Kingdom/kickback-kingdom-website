<?php

declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use \Kickback\Backend\Views\vRecordId;
use \Kickback\Backend\Views\vTransactionLog;

use \Kickback\Backend\Models\TransactionLog;
use \Kickback\Backend\Models\Response;

use Exception;


class TransactionLogController extends DatabaseController
{
    public static ?TransactionLogController $instance_ = null;
    protected array $batchInsertionParams;

    private function __construct()
    {
        $this->batchInsertionParams = [];
    }

    public static function instance()
    {
        if(is_null(static::$instance_))
        {
            static::$instance_ = new static();
        }

        return static::$instance_;
    }


    protected function allViewColumns() : string
    {
        return '
        ctime, 
        crand, 
        first_name, 
        last_name, 
        username,
        description, 
        json, 
        ref_account_ctime, 
        ref_account_crand
        ';
    }

    protected function allTableColumns() : string
    {
        return '
        ctime, 
        crand, 
        description, 
        json, 
        ref_account_ctime, 
        ref_account_crand
        ';
    }

    protected function tableName() : string
    {
        return 'transaction_log';
    }

    protected function rowToView(array $row) : vTransactionLog
    {
        return TransactionLogController::rowToVTransaction($row);
    }

    protected function valuesToInsert(object $TransactionLog) : array
    {
        return [
            $TransactionLog->ctime,
            $TransactionLog->crand,
            $TransactionLog->description,
            $TransactionLog->jsonString,
            $TransactionLog->accountId->ctime,
            $TransactionLog->accountId->crand
        ];
    }

    public static function logTransaction(string $description, string $jsonString, vRecordId $accountId, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unkown error in logging TransactionLog", null);

        try
        {
            $TransactionLog = new TransactionLog($description, $jsonString, $accountId);
            $insertResp = TransactionLogController::insert($TransactionLog, $databaseName);

            if($insertResp->success)
            {
                $resp->success = true;
                $resp->message = "TransactionLog successfully logged";
            }
            else
            {
                $resp->message = "Failed to insert Transaction log : ".$insertResp->message;
            }

            
        }
        catch(Exception $e)
        {
            $resp->message = "Exception caught in logging TransactionLog : ".$e;
        }

        return $resp;
    }

    private function rowToVTransaction(array $row) : vTransactionLog
    {
        $TransactionLog = new vTransactionLog(
            $row["ctime"], 
            $row["crand"], 
            $row["first_name"],
            $row["last_name"], 
            $row["username"],
            $row["description"], 
            $row["json"],
            $row["ref_account_ctime"], 
            $row["ref_account_crand"]
        );

        return $TransactionLog;
    }

}

?>