<?php
declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use Exception;

use \Kickback\Services\Database;

use \Kickback\Backend\Views\vRecordId;
use \Kickback\Backend\Views\vAccount;
use \Kickback\Backend\Views\vStore;

use \Kickback\Backend\Views\vMedia;

use \Kickback\Backend\Models\RecordId;
use \Kickback\Backend\Models\Response;
use \Kickback\Backend\Models\Account;
use \Kickback\Backend\Models\Store;
use \Kickback\Backend\Models\ForeignRecordId;
use Kickback\Backend\Views\vProduct;

class StoreController extends DatabaseController
{

    protected static ?StoreController $instance_ = null;
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
        return 'ctime, crand, name, description, locator, ref_account_ctime, ref_account_crand';
    }

    protected function allTableColumns() : string
    {
        return 'ctime, crand, name, description, locator, ref_account_ctime, ref_account_crand';
    }

    protected function tableName() : string
    {
        return 'store';
    }

    protected function rowToView(array $row) : vStore
    {
        return StoreController::rowToVStore($row);
    }

    protected function valuesToInsert(object $store) : array
    {
        return [
            $store->ctime,
            $store->crand,
            $store->name,
            $store->description,
            $store->locator,
            $store->ownerId->ctime,
            $store->ownerId->crand
        ];
    }

    public static function rowToVStore(array $row)
    {
        $store = new vStore(
            $row["ctime"], 
            $row['crand'], 
            $row['name'], 
            $row['description'],
            $row['locator'], 
            $row['ref_account_ctime'], 
            $row['ref_account_crand']);

        return $store;
    }

    public static function getProductsByStoreLocator(string $storeLocator) : Response
    {
        $resp = new response(false, "Unkown error in getting prodcuts from store locator", null);

        try
        {
            $getStoreResp = StoreController::getWhere(["locator"=>$storeLocator]);

            if($getStoreResp->success)
            {
                $store = $getStoreResp->data[0];

                $productsResp = ProductController::getWhere(["ref_store_ctime"=>$store->ctime, "ref_store_crand"=>$store->crand]);

                if($productsResp->success)
                {
                    $products = $productsResp->data;

                    $resp->success = true;
                    $resp->data = $products;

                    if(count($products) > 0)
                    {
                        $resp->message = "Found and returned products";
                    }
                    else
                    {
                        $resp->message = "No products found for store";
                    }
                }
                else
                {
                    $resp->message = "Failed to get products with matching ref_store_ctime($store->ctime) and ref_store_crand($store->crand) : $productsResp->message";
                }
            }
            else
            {
                $resp->message = "Store with locator '$storeLocator' not found";
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Excpetion caught while getting prodcuts from locator : $storeLocator Exception : $e");
        }

        return $resp;
    }

}

?>