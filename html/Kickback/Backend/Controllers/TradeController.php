<?php
declare(strict_types = 1);

namespace Kickback\Backend\Controllers;

use \Kickback\Services\Database;

use \Kickback\Backend\Models\Trade;
use \Kickback\Backend\Models\Response;

use \Kickback\Backend\Views\vTrade;
use \Kickback\Backend\Views\vRecordId;

use Exception;


class TradeController extends DatabaseController
{

    protected static ?TradeController $instance_ = null;
    protected array $batchInsertionParams;

    private function __construct()
    {
        $this->batchInsertionParams = [];
    }

    protected function allTableColumns() : string
    {
        return "
        id, 
        from_account_id, 
        to_account_id, 
        loot_id, 
        trade_date, 
        from_account_obtain_date
        ";
    }

    protected function allViewColumns() : string
    {
        return "
        id, 
        from_account_id, 
        to_account_id, 
        loot_id, 
        trade_date, 
        from_account_obtain_date
        ";
    }
    
    protected function rowToView(array $row) : object  
    {
        $fromAccountId = new vRecordId('', $row["from_account_id"]);
        $toAccountId = new vRecordId('', $row["to_account_id"]);
        $lootId = new vRecordId($row["from_account_obtain_date"], $row["loot_id"]);

        $ctime = $row["trade_date"];
        $crand = $row["id"];

        return new vTrade(
            $ctime, 
            $crand, 
            $fromAccountId, 
            $toAccountId, 
            $lootId
        );
    }

    protected function valuesToInsert(object $trade) :  array
    {
        return [
            $trade->crand, 
            $trade->fromAccountId->crand,
            $trade->toAccountId->crand,
            $trade->lootId->crand,
            $trade->ctime,
            $trade->lootId->ctime
        ];
    }

    protected function tableName() : string
    {
        return "trade";
    }

    public static function instance() : object
    {
        if(is_null(static::$instance_))
        {
            static::$instance_ = new static();
        }

        return static::$instance_;
    }

    public static function trade(vRecordId $lootId, vRecordId $fromAccountId, vRecordId $toAccountId) : Response
    {
        $resp = new Response(false, "Unknown error in trading loot", null);

        try
        {
            $tradeRecord = new Trade($fromAccountId, $toAccountId, $lootId);

            $createTradeRecordResp = TradeController::insert($tradeRecord);

            if($createTradeRecordResp->success)
            {
                $reassignLootResp = TradeController::reassignLoot($lootId, $toAccountId, $fromAccountId);

                if($reassignLootResp->success)
                {
                    $resp->success = true;
                    $resp->message = "Successfully traded loot";
                }
                else
                {
                    $deleteTradeRecordResp = TradeController::remove($tradeRecord);

                    if($deleteTradeRecordResp->success)
                    {
                        $resp->message = "Error in reassigning loot during trade, trade record removed : ".$reassignLootResp->message;
                    }
                    else
                    {
                        throw new Exception("Erroneous Trade record created and not removed : ".json_encode($tradeRecord));
                    }
                }
            }
            else
            {
                $resp->message = "Error in inserting trade record : ".$createTradeRecordResp->message;
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while attempting to trade loot : ".$e);
        }

        return $resp;
    }

    public static function tradeArray(array $lootIdArray, vRecordId $toAccountId, vRecordId $fromAccountId, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unkown error in trading array", null);

        try
        {
            $prepResp = TradeController::prepBatchinsertionParamsFromLootIdArray($lootIdArray, $toAccountId, $fromAccountId);

            if($prepResp->success)
            {
                $insertTradesResp = new Response(false, "exception caught executing batch insertion", null);

                $insertTradesResp = TradeController::executeBatchInsertion($databaseName);
                
                if($insertTradesResp->success)
                {

                    $reassignResp = TradeController::reassignLootArray($lootIdArray, $toAccountId, $fromAccountId, $databaseName);
    
                    if($reassignResp->success)
                    {
                        $resp->success = true;
                        $resp->message = "Succesfully traded loot array";
                    }  
                    else
                    {
                        $resp->message = "Error in reassigning loot array : ".$reassignResp->message;
                    }
                }
                else
                {
                    $resp->message = "Error in executing batch insertion of trade records : ".$insertTradesResp->message;
                }
            }
            else
            {
                $resp->message = "Error in preparing batch insertion parameters from loot id array : ".$prepResp->message;
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while trading array of loot : $e");
        }

        

        return $resp;
    }

    public static function returnKickbackAccountId() : vRecordId
    {
        return new vRecordId('2023-11-16 09:28:12', 46);
    }

    public static function reassignLootArray(array $lootIdArray, vRecordId $toAccountId, vRecordId $fromAccountId, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unkown error in reassigning loot array to account", null);

        try
        {
            $conn = Database::getConnection();

            if($databaseName != null)
            {
                Database::changeDatabase($databaseName);
            }

            $conn->begin_transaction();

            foreach($lootIdArray as $lootId)
            {
                $reassignResp = TradeController::reassignLoot($lootId, $toAccountId, $fromAccountId, $databaseName);

                if($reassignResp->success == false)
                {
                    $conn->rollback();

                    $resp->message = "Reasign loot update query failed : ".$reassignResp->message. " | Transaction Rolled Back";

                    return $resp;
                }
            }

            $commitSuccess = $conn->commit();

            if($commitSuccess)
            {
                $resp->success = true;
                $resp->message = "loot array has been reassigned";
            }
            else
            {
                $resp->message = "transaction failed";
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while reassigning loot array to account : ".$e);
        }
        
        return $resp;
    }

    private static function reassignLoot(vRecordId $lootId, vRecordId $toAccount, vRecordId $fromAccountId, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unkown error in reassigning loot", null);

        $query = "UPDATE loot SET account_id = ? WHERE item_id = ? AND account_id = ? LIMIT 1;";

        $params = [$toAccount->crand, $lootId->crand, $fromAccountId->crand];


        try
        {
            if($databaseName != null)
            {
                Database::changeDatabase($databaseName);
            }
            
            Database::executeSqlQuery($query, $params);

            $resp->success = true;
            $resp->message = "Successfully reassigned loot";
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while rassigning loot : ".$e);
        }

        return $resp;
    }

    private static function prepBatchinsertionParamsFromLootIdArray(array $lootIdArray, vRecordId $toAccountId, vRecordId $fromAccountId) : Response
    {
        $resp = new Response(false, "unkown error in preparing batch insertion parameters from loot id array", null);

        try 
        {
            foreach($lootIdArray as $lootId)
            {
                $trade = new Trade($fromAccountId, $toAccountId, $lootId);

                TradeController::prepInsertForBatch($trade);
            }

            $resp->success = true;
            $resp->message = "prepared batch insertio0n parametsr from loot id array";

        } 
        catch (Exception $e) 
        {
            throw new Exception("Exception caught while preparing batch insertion paraemtesr from loot id array : ".$e);
        }

        return $resp;
    }
}
?>