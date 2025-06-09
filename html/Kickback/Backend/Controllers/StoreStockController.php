<?php

declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use \Kickback\Backend\Views\vRecordId;

use Kickback\Backend\Models\StoreStock;
use Kickback\Backend\Models\Response;

use Kickback\Backend\Views\vStoreStock;
use \Kickback\Backend\Views\vPrice;
use \Kickback\Backend\Views\vProduct;

use Exception;


class StoreStockController extends DatabaseController
{

    protected static ?StoreStockController $instance_ = null;
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
            name,
            price, 
            product_price,
            removed, 
            locator, 
            ref_currency_item_name, 
            description, 
            store_name, 
            ref_product_ctime, 
            ref_product_crand, 
            ref_product_currency_item_ctime,
            ref_product_currency_item_crand,
            ref_store_ctime, 
            ref_store_crand, 
            ref_currency_item_ctime, 
            ref_currency_item_crand, 
            ref_small_image_path, 
            ref_large_image_path
            ";
    }

    protected function allTableColumns() : string
    {
        return "
            ctime, 
            crand, 
            price,
            removed,
            ref_product_ctime, 
            ref_product_crand, 
            ref_store_ctime, 
            ref_store_crand
            ";
    }

    protected function rowToView(array $row) : object  
    {
        $productId = new vRecordId($row["ref_product_ctime"], $row["ref_product_crand"]);
        $storeId = new vRecordId($row["ref_store_ctime"], $row["ref_store_crand"]);

        $currencyItemCtime = $row["ref_currency_item_ctime"] == null ? '' : $row["ref_currency_item_ctime"];
        $currencyItemId = new vRecordId($currencyItemCtime, $row["ref_currency_item_crand"]);

        $price = new vPrice($row["price"]);
        $productPrice = new vPrice($row["product_price"]);
        $productCurrencyItemId = new vRecordId($row["ref_product_currency_item_ctime"], $row["ref_product_currency_item_crand"]);

        return new vStoreStock(
            $row["ctime"], 
            $row["crand"],
            $row["name"],
            $price,
            $productPrice,
            (bool)$row["removed"],
            $row["locator"],
            $row["ref_currency_item_name"],
            $row["description"],
            $row["store_name"],
            $productId,
            $productCurrencyItemId,
            $storeId,
            $currencyItemId,
            $row["ref_small_image_path"],
            $row["ref_large_image_path"]
        );
    }

    protected function valuesToInsert(object $storeStock) :  array
    {
        return [
            $storeStock->ctime, 
            $storeStock->crand,
            $storeStock->price->smallCurrencyUnit,
            $storeStock->removed,
            $storeStock->productId->ctime,
            $storeStock->productId->crand,
            $storeStock->storeId->ctime,
            $storeStock->storeId->crand
        ];
    }

    protected function tableName() : string
    {
        return "store_stock";
    }

    public static function instance() : object
    {
        if(is_null(static::$instance_))
        {
            static::$instance_ = new static();
        }

        return static::$instance_;
    }

    public static function getStoreStockOfProductForCart(vRecordId $productId, vRecordId $cartId)
    {
        $resp = new Response(false, "Unkown error in getting an available store stock for cart", null);

        $instance = static::instance();

        try
        {
            $sql = "SELECT vss.* FROM
                store_stock ss
                LEFT JOIN store_stock_cart_link link ON ss.ctime = link.ref_store_stock_ctime AND ss.crand = link.ref_store_stock_crand
                INNER JOIN v_store_stock vss  ON vss.ctime = ss.ctime AND vss.crand = ss.crand
                WHERE (link.ref_cart_ctime <> ? OR link.ref_cart_ctime IS NULL) 
                AND (link.ref_cart_crand <> ? OR link.ref_cart_crand IS NULL) 
                AND (link.removed = 0 OR link.removed IS NULL) 
                AND (link.checked_out = 0 OR link.checked_out IS NULL) 
                AND ss.removed = 0 
                AND ss.ref_product_ctime = ? AND ss.ref_product_crand = ? LIMIT 1;
            ";

            $params = [$cartId->ctime, $cartId->crand, $productId->ctime, $productId->crand];

            $getStoreStockResp = static::executeQuery($sql, $params);

            if($getStoreStockResp->success)
            {
                $result = $getStoreStockResp->data;


                if($result->num_rows > 0)
                {
                    $row = $result->fetch_assoc();

                    $storeStock = $instance->rowToView($row);

                    $resp->success = true;
                    $resp->message = "Returned an available store stock for cart";
                    $resp->data = $storeStock;
                }
                else
                {
                    $resp->message = "No available store stock to return";
                }
            }
            else
            {
                $resp->message = "error in executing sql query to get an available store stock for cart";
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while trying to get an available store stock for cart : $e");
        }

        return $resp;
    }

    public static function getNumberOfStoreStocksNotAddToCart(vRecordId $productId, vRecordId $cartId)
    {
        $resp = new Response(false, "unknown error in getting number of store stocks not yet added to cart", null);

        try
        {
            $sql = "SELECT COALESCE(COUNT(ss.ref_product_ctime),0) as AMOUNT FROM
                store_stock ss
                LEFT JOIN store_stock_cart_link link 
                ON ss.ctime = link.ref_store_stock_ctime AND ss.crand = link.ref_store_stock_crand
                WHERE (link.ref_cart_ctime <> ? OR link.ref_cart_ctime IS NULL) 
                AND (link.ref_cart_crand <> ? OR link.ref_cart_crand IS NULL) 
                AND (link.removed = 0 OR link.removed IS NULL) 
                AND (link.checked_out = 0 OR link.checked_out IS NULL) 
                AND ss.removed = 0 
                AND ss.ref_product_ctime = ? AND ss.ref_product_crand = ?
                GROUP BY ss.ref_product_ctime;";

            $params = [$cartId->ctime, $cartId->crand, $productId->ctime, $productId->crand];

            $getNumberResponse = static::executeQuery($sql, $params);

            if($getNumberResponse->success)
            {
                $result = $getNumberResponse->data;

                if($result->num_rows > 0)
                {
                    $row = $result->fetch_assoc();
                    $number = $row["AMOUNT"];

                    $resp->success = true;
                    $resp->message = "Returned number of store stocks not yet added to cart";
                    $resp->data = $number;
                }
                else
                {
                    $resp->message = "Query executed successfully but returned result was empty";
                }
            }
            else
            {
                $resp->message = "Error in executing sql to get number of store stock not yet added to cart : $resp->message";
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while getting number of store stocks not yet added to cart : $e");
        }

        return $resp;

    }

    public static function isStockAvailable(vStoreStock $storeStock)
    {
        $resp = new Response(false, "Unkown error in checking if stock is available", null);

        try
        {
            $existsResp = StoreStockController::exists($storeStock);

            if($existsResp->success)
            {
                $getResp = StoreStockController::get($storeStock);

                if($getResp->success)
                {
                    $resp->success = true;
                    $resp->data = false;

                    $stock = $getResp->data;

                    if(!$stock->removed)
                    {
                        $resp->message = "Stock is available";
                        $resp->data = true;
                    }
                    else
                    {  
                        $resp->message = "Stock has already been removed";
                    }
                }
                else
                {
                    $resp = $getResp;
                    $resp->message = "Error in getting stock | Stock exists | Message : ".$getResp->message;
                }
            }
            else
            {
                $resp= $existsResp;
                $resp->message =  "Stock entry does not exists with message : ".$existsResp->message;
            }
        }
        catch(Exception $e)
        {
            $resp->message = "Exception caught while checking if stock is available : ".$e;
        }

        return $resp;
    }
}
?>