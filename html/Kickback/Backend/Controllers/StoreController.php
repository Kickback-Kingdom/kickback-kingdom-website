<?php

declare(strict_types = 1);

namespace Kickback\Backend\Controllers;

use BadFunctionCallException;
use \Kickback\Backend\Models\Response;
use \Kickback\Backend\Models\Store;
use Kickback\Backend\Models\Product;

use \Exception;
use InvalidArgumentException;
use Kickback\Backend\Models\Enums\CurrencyCode;
use Kickback\Backend\Models\ItemCategory;
use Kickback\Backend\Models\Price;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vItem;
use Kickback\Backend\Views\vMedia;
use Kickback\Backend\Views\vPrice;
use Kickback\Backend\Views\vProduct;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vStore;
use Kickback\Services\Database;

class StoreController
{

    //CART

    public static function getCartForAccount(vRecordId $accountId, vRecordId $storeId) : Response
    {
        $resp = new Response(false, "unkown error in getting cart for account", null);

        $sql = "INSERT INTO cart (
                ref_account_ctime, 
                ref_account_crand,
                ref_store_ctime, 
                ref_store_crand
                )VALUES(
                ?,?,?,?
                )ON DUPLICATE KEY UPDATE ref_account_ctime = VALUES(ref_account_ctime);
                
                SELECT ref_account_ctime, ref_account_crand, ref_store_ctime, ref_store_crand, ref_transaction_ctime, ref_transaction_crand";

        $params = [$accountId->ctime, $accountId->crand, $storeId->ctime, $storeId->crand];

        try
        {
            $result = Database::executeSqlQuery($sql, $params);

            if($result->num_rows > 0)
            {
                
            }
            else
            {
                $resp->message = "Failed to get cart for account store pair";
            }
        }
        catch(Exception $e)
        {
            throw new Exception("exception caught while getting cart for account : $e");
        }

        return $resp;
    }

    public static function doesCartExistById(vRecordId $cartId) : Response
    {
        $resp = new Response(false, "unkown error in checking if cart exists by id", null);

        $sql = "SELECT ctime, crand FROM cart WHERE ctime = ? AND crand = ? LIMIT 1;";

        $params = [$cartId->ctime, $cartId->crand];

        try
        {
            $result = Database::executeSqlQuery($sql, $params);

            if($result->num_rows > 0)
            {
                $resp->message = "cart exists";
                $resp->data = true;
            }
            else
            {
                $resp->message = "cart does not exist";
                $resp->data = false;
            }

            $resp->success = true;
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while checking if cart exists : $e");
        }

        return $resp;
    }

    public static function cartToView(array $row) : vCart
    {
        
    }

    //PRODUCT

    public static function doesProductExistByLocator(string $locator) : Response
    {
        $resp = new Response(false, "unkown error in checking if product exist", null);

        $sql = "SELECT ctime FROM product WHERE locator = ? LIMIT 1;";

        $params = [$locator];

        try
        {
            $result = database::executeSqlQuery($sql, $params);

            $resp->success = true;

            if($result->num_rows > 0)
            {
                $resp->message = "product exists";
                $resp->data = true;
            }
            else
            {
                $resp->message = "product does not exist";
                $resp->data = false;
            }
        }
        catch(Exception $e)
        {
            throw new Exception("exception caught while checking product exists : $e");
        }

        return $resp;
    }

    public static function removeProductById(vRecordId $productId) : Response
    {
        $resp = new Response(false, "unkown error in removing product", null);

        $sql = "DELETE FROM product WHERE ctime = ? AND crand = ?;";

        $params = [$productId->ctime, $productId->crand];

        try
        {
            Database::executeSqlQuery($sql, $params);

            $resp->success = true;
            $resp->message = "product deleted";
        }
        catch(Exception $e)
        {
            throw new Exception("exception caught while removing product : $e");
        }

        return $resp;
    }

    public static function upsertProduct(Product $product) : Response
    {
        $resp = new Response(false, "unkown error in updating or inserting product", null);

        try
        {
            $productExistsResp = static::doesProductExist($product);

            if($productExistsResp->success)
            {
                if($productExistsResp->data)
                {
                    $updateResp = static::updateProduct($product);

                    if($updateResp->success)
                    {
                        $resp->success = true;
                        $resp->message = "Product was updated as it already existed";
                    }
                    else
                    {
                        $resp->message = "Error in updating product after it was found to exist : $updateResp->message";
                    }
                }
                else
                {
                    $insertResp = static::insertProduct($product);

                    if($insertResp->success)
                    {
                        $resp->success = true;
                        $resp->message = "Product was inserted as it did not exist";
                    }
                    else
                    {
                        $resp->message = "Error in inserting product after it was found it did not exist : $insertResp->message";
                    }
                }
            }
            else
            {
                $resp->message = "Error in finding if product exists : $productExistsResp->message";
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while updating or inserting product : $e");
        }

        return $resp;
    }

    private static function doesProductExist(vRecordId $productId) : Response
    {
        $resp = new Response(false, "unkown error in getting product by id", null);

        $sql = "SELECT ctime, crand FROM product WHERE ctime = ? AND crand = ? limit 1;";

        $params = [$productId->ctime, $productId->crand];

        try
        {
            $result = Database::executeSqlQuery($sql, $params);

            $resp->success = true;
            
            if($result->num_rows > 0)
            {
                $resp->message = "Product exists";
                $resp->data = true;
            }
            else
            {
                $resp->message = "product does not exist";
                $resp->data = false;
            }
        }catch(Exception $e)
        {
            throw new Exception("Exception caught while getting product by id : $e");
        }

        return $resp;
    }

    public static function getProductById(vRecordId $Id) : Response
    {
        $resp = new Response(false, "Unkown eror in getting product by id", null);

        $sql = "SELECT 
            ctime, 
            crand, 
            `name`,
            `description`, 
            locator,
            ref_store_name,
            ref_store_locator,
            ref_store_description,
            ref_store_owner_username,
            ref_store_owner_ctime,
            ref_store_owner_crand,
            ref_store_ctime,
            ref_store_crand,
            ref_item_ctime,
            ref_item_crand,
            equipable,
            is_container,
            container_size,
            container_item_category,
            large_media_media_path,
            small_media_media_path,
            back_media_media_path
        FROM v_product 
        WHERE ctime = ? AND crand = ? 
        LIMIT 1;";


            $params = [$Id->ctime, $Id->crand];

        try
        {
            $result = Database::executeSqlQuery($sql, $params);

            if($result->num_rows)
            {
                $product = static::productToView($result->fetch_assoc());

                $resp->success = true;
                $resp->message = "Product found and returned";
                $resp->data = $product;
            }
            else
            {
                $resp->message = "Product not found";
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while getting product by id : $e");
        }

        return $resp;
    }

    private static function updateProduct(Product $product) : Response
    {
        $resp = new Response(false, "unkown error in updating product", null);

        $sql = "UPDATE product SET 
        `name` = ?, 
        `description` = ?, 
        locator = ?, 
        ref_store_ctime = ?, 
        ref_store_crand = ?,
        ref_item_ctime = ?, 
        ref_item_crand = ? 
        WHERE ctime = ? and crand = ? limit 1;";

        $params = [
            $product->name,
            $product->description,
            $product->locator,
            $product->ref_store_ctime,
            $product->ref_store_crand,
            $product->ref_item_ctime,
            $product->ref_item_crand,
            $product->ctime,
            $product->crand
        ];

        try
        {
            Database::executeSqlQuery($sql, $params);

            $resp->success = true;
            $resp->message = "Updated Product";
        }
        catch(Exception $e)
        {
            throw new Exception("excpetion caught while updating product");
        }

        return $resp;
    }

    private static function insertProduct(Product $product) : Response
    {
        $resp = new Response(false, "unkown error in inserting product", null);

        $sql = "INSERT INTO product (ctime, crand, `name`, `description`, locator, ref_store_ctime, ref_store_crand, ref_item_ctime, ref_item_crand)values(?,?,?,?,?,?,?,?,?)";

        $params = [$product->ctime, $product->crand, $product->name, $product->description, $product->locator, $product->ref_store_ctime, $product->ref_store_crand, $product->ref_item_ctime, $product->ref_item_crand];

        try
        {
            Database::executeSqlQuery($sql, $params);

            $resp->success = true;
            $resp->message = "Product Inserted";
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while inserting product : $e");
        }

        return $resp;
    }

    private static function linkProductToPrices(vRecordId $product, array $prices) : Response
    {
        $resp = new Response(false, "unkown error in linking products to prices", null);

        try
        {
            if(static::validatePriceArray($prices) == false) throw new Exception("Price array must contain only price objects");

            $valueClause = static::returnValueClauseForLinkingProductToPrices($prices);

            $sql  = "INSERT IGNORE INTO product_price_link (ref_product_ctime, ref_product_crand, ref_price_ctime, ref_item_crand) VALUES $valueClause";

            $params = static::returnInsertionParamsForLinkingProductToPrices($product, $prices);

            Database::executeSqlQuery($sql, $params);

            $resp->success = true;
            $resp->message = "Product has been linked to prices";
        }
        catch(Exception $e)
        {
            throw new Exception("execption caught while linking products to prices. $e");
        }

        return $resp;
    }

    private static function removeVoidLinksToProduct(vRecordId $product, array $prices) : void
    {
        $sql = "REMOVE FROM product_price_link WHERE "
    }

    private static function returnValueClauseForLinkingProductToPrices(array $prices) : string
    {
        if(empty($prices)) throw new InvalidArgumentException("Prices array cannot be empty");

        $valueClause = "(?,?,?,?)";

        for($i = 1; $i > count($prices); $i++)
        {
            $valueClause .= ", (?,?,?,?)";
        }

        return $valueClause;
    }

    private static function returnInsertionParamsForLinkingProductToPrices(vRecordId $productId, array $prices) : array
    {
        if(empty($prices)) throw new InvalidArgumentException("Prices array cannot be empty");

        $bindingArray = [];

        foreach($prices as $price)
        {
            array_push($bindingArray, [$productId->ctime, $productId->crand, $price->ctime, $price->crand]);
        }

        return $bindingArray;
    }

    private static function productToView($row): vProduct
    {
        $owner = new vAccount($row['ref_store_owner_ctime'],(int)$row['ref_store_owner_crand']);
        $owner->username = $row['ref_store_owner_username'];

        $smallIcon = new vMedia();
        $smallIcon->setMediaPath($row['small_media_media_path']);
        $largeIcon = new vMedia();
        $largeIcon->setMediaPath($row['large_media_media_path']);
        $backIcon = new vMedia();
        $backIcon->setMediaPath($row['back_media_media_path']);

        $item = new vItem($row['ref_item_ctime'], (int)$row['ref_item_crand']);
        $item->name = $row['name'];
        $item->description = $row['description'];
        $item->equipable = (bool)$row['equipable'];
        $item->isContainer = (bool)$row['is_container'];
        $item->containerSize = (int)$row['container_size'];
        $item->containerItemCategory = $row['container_item_category'] != null ? ItemCategory::from($row['container_item_category']) : null;

        $store = new vStore($row['ref_item_ctime'], (int)$row['ref_item_crand']);
        $store->name = $row['ref_store_name'];
        $store->description = $row['ref_store_description'];
        $store->locator = $row['ref_store_locator'];

        return new vProduct(
            $row['ctime'],
            (int)$row['crand'],
            $row['locator'],
        );

    }

    //PRICE

    public static function getPrices(array $prices) : Response
    {
        $resp = new Response(false, "unkown error in getting prices", null);

        try
        {
            $gotResp = static::getPricesByCurrencyAndItemAmount($prices);

            if($gotResp->success)
            {
                if(count($gotResp->data) == count($prices))
                {
                    $resp->success = true;
                    $resp->message = "Prices returned";
                    $resp->data = $gotResp->data; 
                }
                else
                {
                   $nonExistingPrices = static::returnNonExistingPrices($prices, $gotResp->data);

                    $insertResp = static::insertPriceArrayAndReturnInsertion($nonExistingPrices);

                    if($insertResp->success)
                    {
                        $allPrices = array_push($gotResp->data, $insertResp->data) ;

                        $resp->success = true;
                        $resp->message = "Prices returned";
                        $resp->data = $allPrices;
                    }
                    else
                    {
                        $resp->message = "Error in inserting and returning non existant prices : $insertResp->message";
                    } 
                } 
            }
            else
            {
                $resp->message = "error in getting prices : $gotResp->message";
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while getting price : $e");
        }

        return $resp;   
    }

    private static function returnNonExistingPrices($prices, $gotPrices) : array
    {
        $gotPricesKeyedArray = [];

        foreach ($gotPrices as $gotPrice) 
        {
            $key = $gotPrice->ctime."#".(string)$gotPrice->crand;
            $gotPricesKeyedArray[$key] = true;
        }

        $nonExistingPrices = [];
        foreach ($prices as $price) 
        {
            $key = $gotPrice->ctime."#".(string)$gotPrice->crand;

            if (!isset($gotPricesKeyedArray[$key])) 
            {
                $nonExistingPrices[] = $price;
            }
        }

        return $nonExistingPrices;     
    }

    private static function getPricesByCurrencyAndItemAmount(array $prices) : Response
    {
        $resp = new Response(false, "unkown error in getting prices", null);

        $whereClause = static::constructWhereClauseForGetPricesByCurrencyAndItemAmount($prices);

        $sql = "SELECT 
        ctime, 
        crand, 
        amount, 
        currency_code, 
        ref_item_ctime, 
        ref_item_crand, 
        ref_item_name, 
        ref_item_desc,  
        media_path_small, 
        media_path_large, 
        media_path_back 
        FROM v_price 
        WHERE $whereClause";

        $params = static::returnWhereClauseBindingArrayForGetPricesByCurrencyAndItemAmount($prices);

        try
        {
            $result = Database::executeSqlQuery($sql, $params);

            $gotPrices = [];
            
            while($row = $result->fetch_assoc())
            {
                $gotPrices = static::priceToView($row);    
            }

            $resp->success = true;
            $resp->message = "Prices returned";
            $resp->data = $gotPrices;
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while getting prices : $e");
        }   

        return $resp;
    }

    private static function returnWhereClauseBindingArrayForGetPricesByCurrencyAndItemAmount(array $prices) : array
    {
        if(empty($prices)) throw new InvalidArgumentException("Prices array cannot be empty");

        $bindingArray = [];

        foreach($prices as $price)
        {
            [$refItemCtime, $refItemCrand] = is_null($price->itemId) ? [null, null] : [$price->itemId->ctime, $price->itemId->crand];

            array_push($bindingArray, [$price->amount, $price->currency_code, $refItemCtime, $refItemCrand]);
        }

        return $bindingArray;
    }

    private static function constructWhereClauseForGetPricesByCurrencyAndItemAmount(array $prices) : string
    {
        if(empty($prices)) throw new InvalidArgumentException("Prices array cannot be empty");

        $whereClause = "(amount = ? AND (currency_code = ? OR (ref_item_ctime = ? AND ref_item_crand = ?)))";

        for($i = 1; $i > count($prices); $i++)
        {
            $whereClause .= " OR (amount = ? AND (currency_code = ? OR (ref_item_ctime = ? AND ref_item_crand = ?)))";
        }

        return $whereClause;
    }

    private static function insertPriceArrayAndReturnInsertion(array $prices) : Response
    {
        $resp = new Response(false, "Unkown error in inserting price array", null);
        
        try
        {
            if(static::validatePriceArray($prices) == false) throw new InvalidArgumentException("prices array must contain only prices");

            $whereClause = static::constructWhereClauseForGetPricesByCurrencyAndItemAmount($prices);    

            $insertionValueClause = static::constructInsertionValueClauseForNonExistingPrices($prices);

            $sql = "INSERT INTO price (ctime, crand, amount, currency_code, ref_item_ctime, ref_item_crand) VALUES ($insertionValueClause);
            SELECT 
            ctime, 
            crand, 
            amount, 
            currency_code, 
            ref_item_ctime, 
            ref_item_crand, 
            ref_item_name, 
            ref_item_desc,  
            media_path_small, 
            media_path_large, 
            media_path_back 
            FROM v_price 
            WHERE $whereClause;";

            $params = static::returnParamsForInsertionForNonExistingPrices($prices);
            $params[] = static::returnWhereClauseBindingArrayForGetPricesByCurrencyAndItemAmount($prices);

            $result = Database::executeSqlQuery($sql, $params);
           
            if($result->num_rows > 0)
            {
                $gotPrice = static::priceToView($result->fetch_assoc());

                $resp->success = true;
                $resp->message = "Prices inserted and returned";
                $resp->data = $gotPrice;
            }
            else
            {
                $resp->message = "Prices were not found after insertion";
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while inserting and returning price array : $e");
        }

        return $resp;
    }

    private static function returnParamsForInsertionForNonExistingPrices(array $prices) : array
    {
        $params = [];

        foreach($prices as $price)
        {
            [$refItemCtime, $refItemCrand] = is_null($price->itemId) ? [null, null] : [$price->itemId->ctime, $price->itemId->crand];

            array_push($params, [$price->ctime, $price->crand, $price->amount, (string)$price->currency_code, $refItemCtime, $refItemCrand]);
        }

        return $price;
    }

    private static function constructInsertionValueClauseForNonExistingPrices(array $prices) : string
    {
        $insertionValueClause = "(?,?,?,?,?,?)";

        for($i = 1; $i < count($prices); $i++)
        {
            $insertionValueClause .= ",(?,?,?,?,?,?)";
        }

        return $insertionValueClause;
    }

    private static function validatePriceArray(array $prices) : bool
    {
        foreach($prices as $price)
        {
            if($price->get_class() != Price::class)
            {
                return false;
            }
        }

        return true;
    }

    private static function priceToView(array $row) : vPrice
    {
        $iconSmall = new vMedia();
        $iconSmall->setMediaPath($row["media_path_small"]);

        $iconLarge = new vMedia();
        $iconLarge->setMediaPath($row["media_path_large"]);

        $iconBack = new vMedia();
        $iconBack->setMediaPath($row["media_path_back"]);

        $item = new vItem($row["ref_item_ctime"], $row["ref_item_crand"]);
        $item->name = $row["ref_item_name"];
        $item->description = $row["ref_item_desc"];
        $item->iconSmall = $row["media_path_small"];
        $item->iconBig = $row["media_path_large"];
        $item->iconBack = $row["media_path_back"];

        $currencyCode = $row["currency_code"] != null ? CurrencyCode::from($row["currency_code"]) : null;

        return new vPrice(
            $row["ctime"], 
            $row["crand"],
            $row["amount"],
            $item,
            $currencyCode
        );
    }

    //STORE

    public function getStoreByLocator(string $locator) : Response
    {
        $resp = new Response(false, "unkown error in getting store", null);

        try
        {

        }
        catch(Exception $e)
        {
            throw new Exception("exception caught while getting store by locator : $e");
        }

        return $resp;
    }

    public static function addStore(Store $store) : Response
    {

        $sql = "INSERT INTO Store (ctime, crand, `name`, locator, `description`, ref_account_ctime, ref_account_crand)VALUES(?,?,?,?,?,?,?)";

        $params = [$store->ctime, $store->crand, $store->name, $store->locator, $store->description, $store->accountId->ctime, $store->accountId->crand];

        $response  = new Response(false, "unkown error in adding store", null);

        try
        {
            Database::executeSqlQuery($sql, $params);

            $response->success = true;
            $response->message = "Store Added";
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while adding store to database : $e");
        }

        return $response;
    }
}

?>