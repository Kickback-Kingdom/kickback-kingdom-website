<?php
declare(strict_types = 1);

namespace Kickback\Backend\Controllers;

use Kickback\Services\Database;

use Kickback\Backend\Models\StoreStockCartLink;
use Kickback\Backend\Models\Response;

use Kickback\Backend\Views\vStoreStockCartLink;
use \Kickback\Backend\Views\vRecordId;
use \Kickback\Backend\Views\vPrice;

use Exception;


class StoreStockCartLinkController extends DatabaseController
{

    protected static ?StoreStockCartLinkController $instance_ = null;
    protected array $batchInsertionParams;

    private function __construct()
    {
        $this->batchInsertionParams = [];
    }

    protected function allViewColumns() : string
    {
        return "
        ctime, 
        crand, 
        name, 
        description, 
        product_price, 
        stock_price, 
        price, 
        ref_currency_item_name, 
        checked_out, 
        removed, 
        ref_cart_ctime, 
        ref_cart_crand, 
        ref_product_ctime,
        ref_product_crand,
        ref_coupon_ctime, 
        ref_coupon_crand, 
        ref_transaction_ctime, 
        ref_transaction_crand,
        ref_currency_item_ctime,
        ref_currency_item_crand, 
        ref_product_currency_item_ctime, 
        ref_product_currency_item_crand, 
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
        checked_out,
        removed, 
        ref_currency_item_ctime,
        ref_currency_item_crand,
        ref_store_stock_ctime, 
        ref_store_stock_crand, 
        ref_cart_ctime, 
        ref_cart_crand, 
        ref_coupon_ctime, 
        ref_coupon_crand, 
        ref_transaction_ctime, 
        ref_transaction_crand
        ";
    }

    protected function rowToView(array $row) : object  
    {
        $cartId = new vRecordId($row["ref_cart_ctime"], $row["ref_cart_crand"]);
        $productId = new vRecordId($row["ref_product_ctime"], $row["ref_product_crand"]);

        $couponId = is_null($row["ref_coupon_ctime"]) || is_null($row["ref_coupon_crand"]) ? 
            null : new vRecordId($row["ref_coupon_ctime"], $row["ref_coupon_crand"]);

        $transactionId = is_null($row["ref_transaction_ctime"]) || is_null($row["ref_transaction_crand"]) ? 
            null : new vRecordId($row["ref_transaction_ctime"], $row["ref_transaction_crand"]);

        $currencyItemId = is_null($row["ref_currency_item_ctime"]) ? 
            null :  new vRecordId($row["ref_currency_item_ctime"], $row["ref_currency_item_crand"]);

        $productCurrencyItemId = is_null($row["ref_product_currency_item_ctime"]) || is_null($row["ref_product_currency_item_crand"]) ? 
            null : new vRecordId($row["ref_product_currency_item_ctime"], $row["ref_product_currency_item_crand"]);

        $productPrice = new vPrice($row["product_price"], $productCurrencyItemId);
        $stockPrice = new vPrice($row["stock_price"], $currencyItemId);
        $price = new vPrice($row["price"], $currencyItemId);

        return new vStoreStockCartLink(
            $row["ctime"], 
            $row["crand"],
            $row["name"],
            $row["description"],
            $productPrice,
            $stockPrice,
            $price,
            $row["ref_currency_item_name"],
            (bool)$row["checked_out"],
            (bool)$row["removed"],
            $cartId,
            $productId,
            $couponId,
            $transactionId,
            $currencyItemId,
            $productCurrencyItemId,
            $row["ref_small_image_path"],
            $row["ref_large_image_path"]
        );
    }

    protected function valuesToInsert(object $storeStockCartLink) :  array
    {
        $currencyItemIdCtime = is_null($storeStockCartLink->currencyItemId) ? null : $storeStockCartLink->currencyItemId->ctime;
        $currencyItemIdCrand = is_null($storeStockCartLink->currencyItemId) ? null : $storeStockCartLink->currencyItemId->crand;
        
        $couponIdCtime = is_null($storeStockCartLink->couponId) ? null : $storeStockCartLink->couponId->ctime;
        $couponIdCrand = is_null($storeStockCartLink->couponId) ? null : $storeStockCartLink->couponId->crand;

        $transactionIdCtime = is_null($storeStockCartLink->transactionId) ? null : $storeStockCartLink->transactionId->ctime;
        $transactionIdCrand = is_null($storeStockCartLink->transactionId) ? null : $storeStockCartLink->transactionId->crand;

        return [
            $storeStockCartLink->ctime,
            $storeStockCartLink->crand,
            $storeStockCartLink->price->smallCurrencyUnit,
            $storeStockCartLink->checkedOut,
            $storeStockCartLink->removed,
            $currencyItemIdCtime,
            $currencyItemIdCrand,
            $storeStockCartLink->storeStockId->ctime,
            $storeStockCartLink->storeStockId->crand,
            $storeStockCartLink->cartId->ctime,
            $storeStockCartLink->cartId->crand,
            $couponIdCtime,
            $couponIdCrand,
            $transactionIdCtime,
            $transactionIdCrand
        ];
    }

    protected function tableName() : string
    {
        return "store_stock_cart_link";
    }

    public static function instance() : object
    {
        if(is_null(static::$instance_))
        {
            static::$instance_ = new static();
        }

        return static::$instance_;
    }

    public static function ViewRowToObject(array $row) : Response
    {
        $resp = new Response(false, "unkown error in converting store stock cart link view row to object", null);

        $instance = self::instance();
        
        try
        {
            $object = $instance->rowToView($row);

            $resp->success = true;
            $resp->message = "view row converted to store stock cart link object";
            $resp->data = $object;
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while converting store stock cart link row to object : $e");
        }

        return $resp;
    }

    public static function getGroupedItemPriceArrayForCart(vRecordId $cartId, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unknown error in grouping charges into price array", null);

        $instance = static::instance();

        $tableName = $instance->tableName();

        try
        {
            if($databaseName != null)
            {
                Database::changeDatabase($databaseName);
            }

            $query = "SELECT products.ref_currency_item_name AS currency_item_name, products.total as price_total, loots.total as total_in_inventory, products.ref_currency_item_ctime, products.ref_currency_item_crand 
            FROM 
                (SELECT ref_currency_item_name, sum(price) as total, ref_currency_item_ctime, ref_currency_item_crand
                FROM v_store_stock_cart_link
                WHERE checked_out = 0 AND removed = 0 AND ref_cart_ctime = ? AND ref_cart_crand = ?
                GROUP BY ref_currency_item_ctime, ref_currency_item_crand, ref_currency_item_name)products 
            LEFT JOIN 
                (SELECT l.item_id, COUNT(l.item_id) AS total FROM loot l 
                    INNER JOIN 
                    (SELECT * FROM cart WHERE checked_out = 0 AND ctime = ? AND crand = ?
                    )c ON c.ref_account_crand = l.account_id GROUP BY l.item_id
                )loots ON loots.item_id = products.ref_currency_item_crand;
            ";

            $params = [$cartId->ctime, $cartId->crand, $cartId->ctime, $cartId->crand];

            $executeResp = static::executeQuery($query, $params);

            if($executeResp->success)
            {
                $rows = $executeResp->data;
                
                $priceArray = [];

                try
                {
                    foreach($rows as$row)
                    {
                        $currencyItem = new vRecordId($row["ref_currency_item_ctime"], $row["ref_currency_item_crand"]);
                        $priceGroupTotal = new vPrice((int)$row["price_total"], $currencyItem);
                        $amountInInventory = (int)$row["total_in_inventory"];
                        $priceName = $row["currency_item_name"];
                        $dollarSignRequired = false;

                        if(is_null($priceName))
                        {
                            $priceName = "ADA";
                            $dollarSignRequired = true;
                        }
    
                        $priceArray[] = [
                            "currencyName"=>$priceName, 
                            "price"=>$priceGroupTotal, 
                            "inventoryAmount"=>$amountInInventory, 
                            "dollarSignRequired"=>$dollarSignRequired
                        ];
                    }

                    $resp->success = true;
                    $resp->message = "Successfully grouped prices into array";
                    $resp->data = $priceArray;
                }
                catch(Exception $e)
                {
                    throw new Exception("Exception caught while populating price group array. Price Array : ".json_encode($priceArray)." Exception : ".$e);
                } 
            }
            else
            {
                $resp->message = "error in executing price grouping query with message : ".$executeResp->message;
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while grouping charges into price array with message : ".$resp->message." Exception : ".$e);
        }

        return $resp;
    }

    public static function getGroupedItemPriceArrayForAccount(vRecordId $accountId, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unknown error in grouping charges into price array", null);

        $instance = static::instance();

        $tableName = $instance->tableName();

        try
        {
            if($databaseName != null)
            {
                Database::changeDatabase($databaseName);
            }

            $query = "SELECT ref_currency_item_name AS currency_item_name, SUM(price) as price_total, ref_currency_item_ctime, ref_currency_item_crand 
            FROM 
                (SELECT ref_currency_item_name, price, ref_cart_ctime, ref_cart_crand, ref_currency_item_ctime, ref_currency_item_crand
                FROM v_".$tableName." ss LEFT JOIN cart c ON c.ctime = ss.ref_cart_ctime AND c.crand = ss.ref_cart_crand
                WHERE ss.checked_out = 0 AND ss.removed = 0 AND c.ref_account_ctime = ? AND c.ref_account_crand = ?)x 
            GROUP BY ref_currency_item_ctime, ref_currency_item_crand, currency_item_name;";

            $params = [$accountId->ctime, $accountId->crand];

            $executeResp = static::executeQuery($query, $params);

            if($executeResp->success)
            {
                $rows = $executeResp->data;
                
                $priceArray = [];

                try
                {
                    foreach($rows as $row)
                    {
                        $currencyItem = new vRecordId($row["ref_currency_item_ctime"], $row["ref_currency_item_crand"]);
                        $priceGroupTotal = new vPrice((int)$row["price_total"], $currencyItem);
                        $priceName = $row["currency_item_name"];
    
                        $priceArray[] = ["currencyName"=>$priceName, "price"=>$priceGroupTotal];
                    }

                    $resp->success = true;
                    $resp->message = "Successfully grouped prices into array";
                    $resp->data = $priceArray;
                }
                catch(Exception $e)
                {
                    throw new Exception("Exception caught while populating price group array. Price Array : ".json_encode($priceArray)." Exception : ".$e);
                } 
            }
            else
            {
                $resp->message = "error in executing price grouping query with message : ".$executeResp->message;
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while grouping charges into price array with message : ".$resp->message." Exception : ".$e);
        }

        return $resp;
    }

    public static function getAllActiveStoreStockCartLinksForCart(vRecordId $cartId, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "unknown error in getting all active store stock cart links for cart", null);

        try
        {
            $activeStoreStockCartLinksResp = static::getWhere(["ref_cart_ctime"=>$cartId->ctime, "ref_cart_crand"=>$cartId->crand, "removed"=>0, "checked_out"=>0]);

            if($activeStoreStockCartLinksResp->success)
            {
                $links = $activeStoreStockCartLinksResp->data;

                $resp->success = true;
                $resp->message = "Returned active store stock cart links for cart";
                $resp->data = $links;
            }   
            else
            {
                $resp->message = "Error in getting all active store stock cart links for cart : $activeStoreStockCartLinksResp->message";
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while getting active store stock cart links for cart : $e");
        }

        return $resp;
    }


    public static function getAllStoreStockCartLinksForCart(vRecordId $cartId, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "unkown error in getting all store stock cart links for cart",null);

        try
        {
            if($databaseName != null)
            {
                Database::changeDatabase($databaseName);
            }

            $getWhereResp = self::getWhere(["ref_cart_ctime"=>$cartId->ctime, "ref_cart_crand"=>$cartId->crand]);

            if($getWhereResp->success)
            {
                $storeStockCartLinks = $getWhereResp->data;

                if(count($storeStockCartLinks) > 0)
                {
                    $resp->message = "Returned store stock cart links associated with cart";
                }
                else
                {
                    $resp->message = "No store stock cart links associated with cart";
                }

                $resp->success = true;
                $resp->data = $storeStockCartLinks;
            }
            else
            {
                $resp->message = "error in getting associated store stock cart links to cart : $getWhereResp->message";
            }
        }
        catch(Exception $e)
        {
            throw new Exception("exception caught while getting all store stock cart links for cart : $e");
        }

        return $resp;
    }

    public static function markStoreStockCartLinkAsRemoved(vRecordId $storeStockCartLinkId, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "unknown error in marking store stock cart link as removed", null);

        $instance = static::instance();

        try
        {
            if($databaseName != null)
            {
                Database::changeDatabase($databaseName);
            }

            $sql = "UPDATE ".$instance->tableName()." SET removed = 1 WHERE ctime = ? AND crand = ? LIMIT 1;";

            $params = [$storeStockCartLinkId->ctime, $storeStockCartLinkId->crand];

            $removeResp = Database::executeSqlQuery($sql, $params);

            $resp->success = true;
            $resp->message = "Store Stock Cart Link removed from cart";
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while trying to mark store stock cart link as removed : $e");
        }

        return $resp;
    }
}
?>