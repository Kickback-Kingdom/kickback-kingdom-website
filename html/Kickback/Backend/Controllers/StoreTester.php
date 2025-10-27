<?php

declare(strict_types =1);

namespace Kickback\Backend\Controllers;

use DateTime;
use \Kickback\Backend\Models\Account;
use \Kickback\Backend\Models\RecordId;
use \Kickback\Backend\Models\Store;
use \Kickback\Services\Database;

use \Exception;
use Kickback\Backend\Models\Item;
use Kickback\Backend\Models\Price;
use Kickback\Backend\Models\Product;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vCart;
use Kickback\Backend\Views\vRecordId;
use React\Dns\Model\Record;

class StoreTester
{
    public static function testStoreController()
    {
        StoreController::runUnitTests();
        static::fullIntegrationTest();

        
    }

    private static function fungiblityTest() : void
    {
        $database = static::createTestEnviroment("FUNGIBILITY_");

        try
        {
            $storeController = new StoreController;

            $storeAccount = static::returnStoreAccount();
            static::insertStoreAccount($storeAccount);
            static::insertTestProductLootForTestStore(2, $storeAccount);

            $fungibleItem = new Item();
            $fungibleItem->

            $store = static::returnTestStore();
            StoreController::addStore($store);
            $product = new Product("FungiblePricedProduct", "test", "test", $store->ctime, $store->crand, );
            StoreController::upsertProduct();

            $prices = static::testSelectOrInsertPrices_Insert($testStore);
            $prices = static::convertPriceViewArrayToModels($prices);
            static::testUpsertProduct_Insert($testStore, $prices);
            $product = static::testUpsertProduct_Update($testStore, $prices);

            $cart = static::testGetCartForAccount($testStore);

            $link = static::testAddProductToCart($product, $cart);
            static::testRemoveProductFromCart($link);

            $cartOwner = static::getTestAccount(static::buyerAccountUsername());
            $link = StoreController::AddProductToCart($product, $cart);
            $cartResp = StoreController::GetCartForAccount($cartOwner, $testStore);
            if(is_null($cartResp->data))
            { 
                throw new Exception(json_encode($cartResp));
            }
            static::testCheckout($cartResp->data);

            static::testRemoveProduct($testStore, $product);
        }
        catch(Exception $e)
        {
            //static::cleanupTestEnviroment($database);
            throw $e;
        }
        finally
        {
            //static::cleanupTestEnviroment($database);
        }
    }

    private static function fullIntegrationTest() : void
    {
        $database = static::createTestEnviroment();

        try
        {
            $storeController = new StoreController;

            $storeAccount = static::returnStoreAccount();
            static::insertStoreAccount($storeAccount);
            static::insertTestProductLootForTestStore(2, $storeAccount);

            $testStore = static::testAddStore($storeController);
            $prices = static::testSelectOrInsertPrices_Insert($testStore);
            $prices = static::convertPriceViewArrayToModels($prices);
            static::testUpsertProduct_Insert($testStore, $prices);
            $product = static::testUpsertProduct_Update($testStore, $prices);

            $cart = static::testGetCartForAccount($testStore);

            $link = static::testAddProductToCart($product, $cart);
            static::testRemoveProductFromCart($link);

            $cartOwner = static::getTestAccount(static::buyerAccountUsername());
            $link = StoreController::AddProductToCart($product, $cart);
            $cartResp = StoreController::GetCartForAccount($cartOwner, $testStore);
            if(is_null($cartResp->data))
            { 
                throw new Exception(json_encode($cartResp));
            }
            static::testCheckout($cartResp->data);

            static::testRemoveProduct($testStore, $product);
        }
        catch(Exception $e)
        {
            //static::cleanupTestEnviroment($database);
            throw $e;
        }
        finally
        {
            //static::cleanupTestEnviroment($database);
        }
    }
    
    private static function storeUsername() : string
    {
        return "store";
    }

    private static function buyerAccountUsername() : string
    {
        return "JoeDoe";
    }

    public static function testCheckout(vCart $cart) : void
    {

        $checkoutResp = StoreController::checkoutCart($cart);

        if(!$checkoutResp->success)
        {
            throw new Exception("COMPONENT TEST FAILED : failed to checkout cart : $checkoutResp->message");
        }
    }

    public static function testRemoveProductFromCart($link) : void
    {
        try
        {
            $resp = StoreController::removeProductFromCart($link);

            if($resp->success)
            {
                $testSql = "SELECT removed FROM cart_product_link WHERE ctime = ? AND crand = ?;";

                $result = database::executeSqlQuery($testSql, [$link->ctime, $link->crand]);

                if($result->num_rows == 1)
                {
                    $row = $result->fetch_assoc();
                    $removed = boolval($row["removed"]);

                    if(!$removed)
                    {
                        $json = json_encode($row);
                        throw new Exception("COMPONENT TEST FAILED : product was not removed while testing removeProductFromCart : $json");
                    }
                }
                elseif($result->num_rows <= 0)
                {
                    $json = json_encode($link);
                    throw new Exception("COMPONENT TEST FAILED : No rows returned for matching link while testing removeProductFromCart : $json");
                }
                else
                {
                    $json = json_encode($link);
                    throw new Exception("COMPONENT TEST FAILED : More than one row returned for mathcing link while testing removeProductFromCart : $json");
                }
            }
            else
            {
                throw new Exception("COMPONENT TEST FAILED : $resp->message");
            }
        }
        catch(Exception $e)
        {
            throw new Exception("COMPONENT TEST FAILED : Exception caught while testing removeProductFromCart : $e");
        }
        
    }

    public static function testAddProductToCart(Product $testProduct, vCart $testCart) : vRecordId
    {
        

        try
        {
            $addProductToCartResp = StoreController::addProductToCart($testProduct, $testCart);

            if($addProductToCartResp->success)
            {
                if($addProductToCartResp->data)
                {
                    $checkSql = "SELECT * FROM v_cart_product_link WHERE product_ctime = ? AND product_crand = ? AND cart_ctime = ? AND cart_crand = ?";

                    $checkParams = [$testProduct->ctime, $testProduct->crand, $testCart->ctime, $testCart->crand];

                    $checkResult = database::executeSqlQuery($checkSql, $checkParams);

                    if($checkResult)
                    {
                        if($checkResult->num_rows == 1)
                        {
                            $link = StoreController::cartProductLinkToView($checkResult->fetch_assoc());
                            
                            if($link->product->ctime != $testProduct->ctime 
                            || $link->product->crand != $testProduct->crand 
                            || $link->cart->ctime != $testCart->ctime 
                            || $link->cart->crand != $testCart->crand)
                            {
                                $linkIdString = "PRODUCT : ActualCtime = '".$link->product->ctime."' ExpectedCtime = '$testProduct->ctime' ActualCrand = '".$link->product->crand."' ExpectedCrand = '$testProduct->crand' | CART : ActualCtime = '".$link->cart->ctime."' ExpectedCtime = '$testCart->ctime' ActualCrand = '".$link->cart->crand."' ExpectedCrand = '$testCart->crand'";

                                throw new Exception("COMPONENT TEST FAILED | added link did not match test product and cart provided to add product to cart method : $linkIdString");
                            }

                            return $link;
                        }
                        elseif($checkResult->num_rows > 1)
                        {
                            throw new Exception("COMPONENT TEST FAILED | multiple links to added product were found when 1 was expected");
                        }
                        else
                        {
                            throw new Exception("COMPONENT TEST FAILED | no links to added product were found.");
                        }
                    }
                    else
                    {
                        throw new Exception("Failed to check add product to cart");
                    }
                }
                else
                {
                    throw new Exception("COMPONENT TEST FAILED | product had no stock for test : $addProductToCartResp->message");
                }
            }
            else
            {
                throw new Exception("COMPONENT TEST FAILED | failed to add product to cart : $addProductToCartResp->message");
            }
        }
        catch(Exception $e)
        {
            throw new Exception("COMPONENT TEST FAILED | exception caught while testing add product to cart : $e");
        }
        
    }
    
    public static function testGetCartForAccount(vRecordId $storeId) : vCart
    {
        $cartOwner = static::getTestAccount(static::buyerAccountUsername());

        $cart = StoreController::getCartForAccount($cartOwner, $storeId);

        if(!$cart->success)
        {
            $json = json_encode($cart);
            throw new Exception("COMPONENT TEST FAILED : getCartForAccount : $json");
        }

        return $cart->data;
    }

    public static function testAddStore(StoreController $storeController) : store
    {
        $store = static::returnTestStore();

        try
        {
            $resp = StoreController::addStore($store);
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while testing addStore : $e");
        }

        return $store;
    }

    private static function testUpsertProduct_Update(vRecordId $store, array $prices) : product
    {
        try
        {
            $product = static::returnTestProduct($store, $prices);

            $insertResp = StoreController::upsertProduct($product);

            if(!$insertResp->success)
            {
                throw new Exception("COMPONENT TEST FAILED | failed to insert product : $insertResp->message");
            }

            $getProduct = StoreController::getProductById($product->getVRecordId());

            if(!$getProduct->success)
            {
                throw new Exception("COMPONENT TEST FAILED | failed to get product for testing upsert product : $getProduct->message");
            }

            if($getProduct->data->locator != "Test product locator")
            {
                throw new Exception("Locator did not match expected before product was updated. Expected : 'Test product locator' | Actual : '$getProduct->data->locator'");
            }

            $product->locator = "this was updated!";

            StoreController::upsertProduct($product);

            $getProduct = StoreController::getProductById($product->getVRecordId());

            if($getProduct->data->locator != $product->locator)
            {
                throw new Exception("Locator did not match expected after product was updated. Expected : '$product->locator' | Actual : '$getProduct->data->locator'");
            }

            return $product;
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while testing upsertProduct : $e");
        }
    }

    private static function convertPriceViewArrayToModels(array $priceViews) : array
    {
        $priceModels = [];

        foreach($priceViews as $view)
        {
            $model = new Price($view->amount, $view->currencyCode, $view->item);

            array_push($priceModels, $model);
        }

        return $priceModels;
    }

    private static function testUpsertProduct_Insert(vRecordId $store, array $prices) : Product
    {
        try
        {
            $product = static::returnTestProduct($store, $prices);

            static::validatePriceArray($product->prices);

            StoreController::upsertProduct($product);

            return $product;
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while testing upsertProduct : $e");
        }
    }

    private static function testRemoveProduct(vRecordId $store, Product $product) : void
    {
        try
        {
            $product->locator = "Remove this locator";

            StoreController::upsertProduct($product);
            $deleteResp = StoreController::removeProductById($product);

            $existResp = StoreController::doesProductExistByLocator($product->locator);

            if($existResp->success)
            {
                if($existResp->data)
                {
                    throw new Exception("Object still exists after deletion : $deleteResp->message");
                }
            }
            else
            {
                throw new Exception("Failed to check products existance after deletion : $existResp->message");
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while testing removeProduct : $e");
        }
    }

    private static function testSelectOrInsertPrices_Insert() : array
    {
        $prices = [static::returnTestPrice()];

        $resp = StoreController::selectOrInsertPrices($prices);

        return $resp->data;
    }

    private static function validatePriceArray(array $prices): bool
    {
        foreach ($prices as $price) {
            if (!$price instanceof Price) {
                throw new Exception("Price is not a price model : ".get_class($price));
            }
        }
        return true;
    }

    public static function createTestEnviroment(string $uniqueName = '') : string
    {
        $randomId = new RecordId;
        $database = "TEST_".$uniqueName."DATABASE_STORE_DELETE_IF_STILL_PRESENT_$randomId->crand";

        $query = "CREATE DATABASE $database";
        Database::executeSqlQuery($query, []);

        Database::changeDatabase($database);


        //Account
        static::createAccountTable();
        static::createAccountView();
        $buyerAccount = static::returnTestAccount();
        static::insertTestAccount($buyerAccount);
        $buyer  = static::getTestAccount($buyerAccount->username);

        //Media
        static::createMediaTable();
        static::createMediaView();
        static::insertRaffleTicketMedia();
        static::insertTableTopSimluatorMedia();

        //Item
        static::createItemTable();
        static::insertRaffleTicketItem();
        static::insertTableTopSimluatorItem();
        static::createItemView();

        //Store
        static::createStoreTable();
        static::createStoreView();

        //Loot
        static::createLootTable();
        static::createLootView();
        static::insertRaffleTicketsForTestAccount(5, $buyer);

        //Price
        static::createPriceTable();
        static::createPriceView();

        //Product
        static::createProductTable();
        static::createProductView();

        //ProductPriceLink
        static::createProductPriceLinkTable();

        //Cart
        static::createCartTable();
        static::createCartView();

        //CartProductLink
        static::createProductCartLinkTable();
        static::createProductCartLinkView();

        //CartItem
        static::createCartItemView();

        //Trade
        static::createTradeTable();
        static::createTradeView();

        return $database;
    }

    public static function cleanupTestEnviroment(string $database) : void
    {
        $sql = "DROP DATABASE $database";

        Database::changeDatabase($database);
        Database::executeSqlQuery($sql, []);
    }

    //Product Cart Link

    private static function createProductCartLinkTable() : void
    {
        $query = "CREATE TABLE cart_product_link (
            ctime datetime(6) not null,
            crand bigint not null,
            removed boolean not null default 0,
            checked_out boolean not null default 0,
            ref_cart_ctime datetime(6) not null,
            ref_cart_crand bigint not null,
            ref_product_ctime datetime(6) not null,
            ref_product_crand bigint not null,
            
        PRIMARY KEY (ctime, crand),
            
        CONSTRAINT fk_cart_product_link_ref_cart_ctime_crand FOREIGN KEY (ref_cart_ctime, ref_cart_crand) REFERENCES cart(ctime, crand),
        CONSTRAINT fk_cart_product_link_ref_product_ctime_crand FOREIGN KEY (ref_product_ctime, ref_product_crand) REFERENCES product(ctime, crand)
        );
        ";

        database::executeSqlQuery($query, []);
    }

    private static function createProductCartLinkView() : void
    {
        $query = "CREATE VIEW v_cart_product_link AS (
            SELECT
                cplink.ctime,
                cplink.crand,
                vp.name as `product_name`,
                vp.description as `product_description`,
                vp.locator as `product_locator`,
                cplink.removed,
                cplink.checked_out,
                vc.account_username,
                vc.store_name,
                vp.store_locator,
                cplink.ref_cart_ctime as `cart_ctime`,
                cplink.ref_cart_crand as `cart_crand`,
                cplink.ref_product_ctime as `product_ctime`,
                cplink.ref_product_crand as `product_crand`,
                vc.account_ctime,
                vc.account_crand,
                vc.store_ctime,
                vc.store_crand,
                vp.large_media_media_path,
                vp.small_media_media_path,
                vp.back_media_media_path
            FROM cart_product_link cplink
                LEFT JOIN v_product vp on cplink.ref_product_ctime = vp.ctime and cplink.ref_product_crand = vp.crand
                LEFT JOIN v_cart vc on cplink.ref_cart_ctime = vc.ctime and cplink.ref_cart_crand = vc.crand
            );";

        database::executeSqlQuery($query, []);
    }

    //Cart

    private static function createCartTable() : void
    {  
        $query = "CREATE TABLE cart
        (
            ctime datetime(6) not null,
            crand bigint not null,
            checked_out boolean not null default 0,
            void boolean not null default 0,
            ref_account_ctime datetime(6) not null,
            ref_account_crand int not null,
            ref_store_ctime datetime(6) not null,
            ref_store_crand bigint not null,
            ref_transaction_ctime datetime,
            ref_transaction_crand int,
            
            primary key (ctime, crand),
            
            UNIQUE KEY unique_cart_ref_account_ctime_crand_ref_store_ctime_crand (ref_account_ctime, ref_account_crand, ref_store_ctime, ref_store_crand),
            
            CONSTRAINT fk_cart_ref_account_ctime_crand_account_ctime_crand FOREIGN KEY (ref_account_crand) REFERENCES account(id),
            CONSTRAINT fk_cart_ref_store_ctime_crand_store_ctime_crand FOREIGN KEY (ref_store_ctime, ref_store_crand) REFERENCES store(ctime, crand)
        );
        ";

        database::executeSqlQuery($query, []);
    }

    private static function createCartView() : void
    {
        $query = "CREATE VIEW v_cart AS (
        SELECT
            c.ctime,
            c.crand,
            a.Username as `account_username`,
            s.name as `store_name`,
            s.locator as `store_locator`,
            c.checked_out,
            c.void,
            a.DateCreated AS `account_ctime`,
            a.Id as `account_crand`,
            s.ctime as `store_ctime`,
            s.crand as `store_crand`,
        s.ref_account_ctime as `store_owner_ctime`,
            s.ref_account_crand as `store_owner_crand`,
            c.ref_transaction_ctime as `transaction_ctime`,
            c.ref_transaction_crand as `transaction_crand`
        FROM cart c
            LEFT JOIN store s on c.ref_store_ctime = s.ctime AND c.ref_store_crand = s.crand
            LEFT JOIN account a on a.id = c.ref_account_crand
        );
        ";

        database::executeSqlQuery($query, []);
    }

    private static function createCartItemView() : void
    {
        $sql = "
        CREATE VIEW v_cart_item AS (
        SELECT
            cplink.ctime as 'cart_product_link_ctime',
            cplink.crand as 'cart_product_link_crand',
            cplink.ref_cart_ctime as 'cart_ctime',
            cplink.ref_cart_crand as 'cart_crand',
            cplink.removed,
            cplink.checked_out,
            vprod.ctime as 'product_ctime',
            vprod.crand as 'product_crand',
            vprod.name as 'product_name',
            vprod.description as 'product_description',
            vprod.locator as 'product_locator',
            vprod.small_media_media_path as 'product_small_media_path',
            vprod.large_media_media_path as 'product_large_media_path',
            vprod.back_media_media_path as 'product_back_media_path',
            vprod.stock as 'product_stock',
            vii.DateCreated as 'product_item_ctime',
            vii.Id as 'product_item_crand',
            vprice.ctime as 'price_ctime',
            vprice.crand as 'price_crand',
            vprice.amount as 'price_amount',
            vprice.currency_code as 'price_currency_code',
            vprice.item_name as 'price_item_name',
            vprice.item_desc as'price_item_desc',
            vprice.media_path_small as 'price_media_path_small',
            vprice.media_path_large as 'price_media_path_large',
            vprice.media_path_back as 'price_media_path_back',
            vprice.item_ctime as 'price_item_ctime',
            vprice.item_crand as 'price_item_crand'
        FROM cart_product_link cplink
            JOIN cart c ON cplink.ref_cart_ctime = c.ctime AND cplink.ref_cart_crand = c.crand
            JOIN v_product vprod ON cplink.ref_product_ctime = vprod.ctime AND cplink.ref_product_crand = vprod.crand
            JOIN product_price_link pplink ON vprod.ctime = pplink.ref_product_ctime AND vprod.crand = pplink.ref_product_crand
            JOIN v_price vprice ON pplink.ref_price_ctime = vprice.ctime AND pplink.ref_price_crand = vprice.crand 
            JOIN v_item_info vii ON vii.Id = vprod.item_crand
        );";

        Database::executeSqlQuery($sql, []);
    }

    //PRODUCT

    private static function createProductTable() : void
    {
        $query = "CREATE TABLE product
        (
            ctime datetime(6) not null,
            crand bigint not null,
            `name` varchar(50) not null,
            `description` varchar(200),
            `removed` tinyint not null DEFAULT(0),
            locator varchar(50) not null,
            ref_store_ctime datetime(6) not null,
            ref_store_crand bigint not null,
            ref_item_ctime datetime(6) not null,
            ref_item_crand int not null,
            
            PRIMARY KEY (ctime, crand),
            
            CONSTRAINT fk_product_ref_store_ctime_crand_store_ctime_crand FOREIGN KEY (ref_store_ctime, ref_store_crand) REFERENCES store(ctime, crand),
            CONSTRAINT fk_product_ref_item_ctime_crand FOREIGN KEY (ref_item_crand) REFERENCES item(id)
        );";

        Database::executeSqlQuery($query, []);
    }

    private static function createProductView() : void
    {
        $query = "
            CREATE VIEW v_product AS (
                SELECT
                    p.ctime,
                    p.crand, 
                    p.name,
                    p.description,
                    p.locator,
                    (
                        SELECT COUNT(*)
                        FROM v_loot_item li
                        WHERE li.account_id = s.ownerCrand
                        AND li.item_id = i.Id
                        AND li.opened = 1
                    ) AS stock,
                    s.name AS `store_name`,
                    s.locator AS `store_locator`,
                    s.description AS `store_description`,
                    s.Username AS `store_owner_username`,
                    s.ownerCtime AS `store_owner_ctime`,
                    s.ownerCrand AS `store_owner_crand`,
                    s.ctime as `store_ctime`,
                    s.crand as `store_crand`,
                    '' as `item_ctime`,
                    i.Id as `item_crand`,
                    i.equipable,
                    i.is_container,
                    i.container_size,
                    i.container_item_category,
                    vi.large_image AS `large_media_media_path`,
                    vi.small_image AS `small_media_media_path`,
                    mback.mediaPath AS `back_media_media_path`
                FROM product p 
                LEFT JOIN item i ON p.ref_item_crand = i.Id
                LEFT JOIN v_store s ON s.ctime = p.ref_store_ctime AND s.crand = p.ref_store_crand
                LEFT JOIN v_item_info vi on vi.id = p.ref_item_crand
                LEFT JOIN v_media mback on mback.id = i.media_id_back
            );";

        database::executeSqlQuery($query, []);
    }

    private static function returnTestProduct(vRecordId $storeId, array $prices) : Product
    {
        $testProduct = new Product("CheapTestProduct", "Test-Product-Cheap", "Test product locator", $storeId->ctime, $storeId->crand, '2024-01-09 16:58:40', 81, $prices);

        return $testProduct;
    }

    //PRICE 

    private static function createPriceTable() : void
    {
        $sql = "create table price
        (
            ctime datetime(6) not null,
            crand bigint not null,
            amount int not null,
            currency_code char(3),
            ref_item_ctime datetime,
            ref_item_crand int,
            
            primary key (ctime, crand),
            
            constraint fk_price_ctime_crand_item_ctime_crand FOREIGN KEY (ref_item_crand) REFERENCES item(id)
        );";

        database::executeSqlQuery($sql, []);
    }

    private static function createPriceView() : void
    {
        $sql = "CREATE VIEW v_price AS (
        SELECT 
            p.ctime,
            p.crand,
            p.amount,
            p.currency_code,
            p.ref_item_ctime as `item_ctime`,
            p.ref_item_crand as `item_crand`,
            vi.name as `item_name`,
            vi.desc as `item_desc`,
            vi.small_image as `media_path_small`,
            vi.large_image as `media_path_large`,
            CONCAT(vmback.directory, '/', vmback.Id, '.', vmback.extension) as `media_path_back`
        FROM price p
            LEFT JOIN v_item_info vi on vi.Id = p.ref_item_crand
            LEFT JOIN item i on i.id = p.ref_item_crand
            LEFT JOIN v_media vmback on vmback.Id = i.media_id_back
        );
            ";
        
        database::executeSqlQuery($sql, []);
    }

    private static function returnTestPrice() : Price
    {
        $id = new vRecordId('', 4);
        $price = new Price(1, null, $id);

        return $price;
    }

    //PRODUCT PRICE LINK
    private static function createProductPriceLinkTable() : void
    {
        $sql = "create table product_price_link
        (
            ref_product_ctime datetime(6) not null,
            ref_product_crand bigint not null,
            ref_price_ctime datetime(6) not null,
            ref_price_crand bigint not null,
            void tinyint not null,
            
            primary key (ref_product_ctime, ref_product_crand, ref_price_ctime, ref_price_crand),
            
            CONSTRAINT fk_productPriceLink_ref_product_ctime_crand_product_ctime_crand FOREIGN KEY (ref_product_ctime, ref_product_crand) REFERENCES product(ctime, crand),
            CONSTRAINT fk_productPriceLink_ref_price_ctime_crand_price_ctime_crand FOREIGN KEY (ref_price_ctime, ref_price_crand) REFERENCES price(ctime, crand)
        );";

        database::executeSqlQuery($sql, []);
    }

    //ACCOUNT
    private static function createAccountTable() : void
    {
        $query = "CREATE TABLE account (
            Id int(11) NOT NULL AUTO_INCREMENT,
            Email varchar(30) NOT NULL,
            `Password` varchar(255) NOT NULL,
            FirstName varchar(30) NOT NULL,
            LastName varchar(30) NOT NULL,
            DateCreated timestamp NOT NULL DEFAULT utc_timestamp(),
            Username varchar(30) NOT NULL,
            Banned tinyint(1) NOT NULL DEFAULT 0,
            pass_reset int(11) DEFAULT NULL,
            passage_id int(11) DEFAULT NULL,
            PRIMARY KEY (Id),
            UNIQUE KEY Email (Email),
            UNIQUE KEY unique_passage_id (passage_id),
            FULLTEXT KEY idx_fulltext_name_search (Username,FirstName,LastName,Email)
        )";

        Database::executeSqlQuery($query, []);
    } 

    private static function createAccountView() : void
    {
        $query = "CREATE VIEW v_account_info AS 
            select 
            account.Username AS Username,
            account.FirstName AS FirstName,
            account.LastName AS LastName,
            account.Id AS Id,
            account.Banned AS Banned,
            account.pass_reset AS pass_reset,
            account.Email AS email,
            0 AS exp,
            0 AS level,
            0 AS exp_needed,
            0 AS exp_started,
            0 AS prestige,
            0 AS badges,
            0 AS exp_current,
            0 AS exp_goal,
            0 AS IsAdmin,
            0 AS IsArtist,
            0 AS IsQuestGiver,
            0 AS IsProgressScribe,
            0 AS IsMerchant,
            0 AS IsApprentice,
            0 AS IsMaster,
            0 AS IsSteward,
            0 AS IsMagisterOfAdventurers,
            0 AS IsChancellorOfExpansion,
            0 AS IsChancellorOfTechnology,
            0 AS IsStewardOfExpansion,
            0 AS IsStewardOfTechnology,
            0 AS IsServantOfTheLich,
            0 AS avatar_media,
            0 AS player_card_border_media,
            0 AS banner_media,
            0 AS background_media,
            0 AS charm_media,
            0 AS companion_media 
            from account 
        ;";

        Database::executeSqlQuery($query, []);
    }

    public static function insertStoreAccount(Account $storeAccount) : void
    {
        $query = "INSERT INTO account 
        (id, email, `password`, firstName, lastName, DateCreated, Username, Banned, pass_reset, passage_id) VALUES 
        ($storeAccount->crand, '$storeAccount->email', 'somepasswordhash', '$storeAccount->firstName', '$storeAccount->lastName', '$storeAccount->ctime', '$storeAccount->username', 0, 0, -1);";

        Database::executeSqlQuery($query, []);
    }

    public static function returnStoreAccount() : Account
    {
        $account = new Account();

        $record = new RecordId();
        $account->ctime = $record->ctime;

        $account->email = "teststoreemail@gmail.com";
        $account->firstName = "Store";
        $account->lastName = "Store";
        $account->username = static::storeUsername();
        $account->banned = false;

        return $account;
    }

    public static function insertTestAccount(Account $account) : void
    {

        $query = "INSERT INTO account 
        (id, email, `password`, firstName, lastName, DateCreated, Username, Banned, pass_reset, passage_id) VALUES 
        ($account->crand, '$account->email', 'somepasswordhash', '$account->firstName', '$account->lastName', '$account->ctime', '$account->username', 0, 0, -2);";

        Database::executeSqlQuery($query, []);
    }

    public static function returnTestAccount() : Account
    {
        $account = new Account();

        $record = new RecordId();
        $account->ctime = $record->ctime;

        $account->email = "testemail@gmail.com";
        $account->firstName = "Joe";
        $account->lastName = "Doe";
        $account->username = static::buyerAccountUsername();
        $account->banned = false;

        return $account;
    }

    public static function getTestAccount(string $accountUsername) : vAccount
    {

        $resp = AccountController::getAccountByUsername($accountUsername);

        if($resp->success)
        {
            return $resp->data;
        }
        else
        {
            throw new Exception("failed to get test account from database");
        }
    }

    //STORE

    public static function createStoreTable() : void
    {
        $query = "CREATE TABLE store
        (
            ctime datetime(6) not null,
            crand bigint not null,
            name varchar(50) not null,
            locator varchar(50) not null,
            description varchar(200) not null,
            ref_account_ctime datetime(6) not null,
            ref_account_crand int not null,
            
            PRIMARY KEY (ctime, crand),
            
            CONSTRAINT fk_store_ref_account_ctime_crand_account_ctime_crand FOREIGN KEY (ref_account_crand) references account(Id) 
        );";

        Database::executeSqlQuery($query, []);
    }

    public static function createStoreView() : void
    {
        $query = "CREATE VIEW v_store AS (
            SELECT
                s.ctime,
                s.crand,
                s.name,
                s.locator,
                s.description,
                a.Username,
                a.DateCreated as 'ownerCtime',
                a.Id as 'ownerCrand'
            FROM store s
                LEFT JOIN account a ON s.ref_account_crand = a.Id
            );";

        Database::executeSqlQuery($query, []);
    }

    public static function returnTestStore() : Store
    {
        $accountId = static::getTestAccount(static::storeUsername());

        $store = new Store("testStore", "testLocator", "testDescription", $accountId);

        return $store;
    }

    //ITEM

    private static function createItemTable() : void
    {

        $query = "CREATE TABLE item (
            Id int(11) NOT NULL AUTO_INCREMENT,
            `type` int(11) NOT NULL,
            rarity int(11) NOT NULL,
            media_id_large int(11) NOT NULL,
            media_id_small int(11) NOT NULL,
            `desc` varchar(1024) NOT NULL,
            `name` varchar(255) DEFAULT NULL,
            nominated_by_id int(11) DEFAULT NULL,
            collection_id int(11) DEFAULT NULL,
            equipable tinyint(4) NOT NULL DEFAULT 0,
            equipment_slot enum('AVATAR','PC_BORDER','BANNER','BACKGROUND','CHARM','PET') DEFAULT NULL,
            redeemable tinyint(4) NOT NULL DEFAULT 0,
            useable boolean not null default 0,
            is_container boolean not null default 0,
            container_size int not null default -1,
            container_item_category int,
            item_category int,
            media_id_back int,
            is_fungible tinyint NOT NULL DEFAULT 0
            PRIMARY KEY (Id)
            )";

        Database::executeSqlQuery($query, []);
    }

    private static function createItemView() : void
    {
        $query = "CREATE VIEW v_item_info AS 
            select 
                i.Id AS Id,
                i.type AS `type`,
                i.rarity AS rarity,
                i.media_id_large AS media_id_large,
                i.media_id_small AS media_id_small,
                i.desc AS `desc`,
                i.name AS `name`,
                i.nominated_by_id AS nominated_by_id,
                i.collection_id AS collection_id,
                null AS item_collection_name,
                null AS item_collection_desc,
                i.equipable AS equipable,
                i.equipment_slot AS equipment_slot,
                i.redeemable AS redeemable,
                i.useable AS useable,
                concat(large_image.Directory,'/',large_image.Id,'.',large_image.extension) AS large_image,
                concat(small_image.Directory,'/',small_image.Id,'.',small_image.extension) AS small_image,
                account_artist.Username AS artist,
                account_artist.Id AS artist_id,
                account_nominator.Username AS nominator,
                account_nominator.Id AS nominator_id,
                large_image.DateCreated AS DateCreated 
            from item i 
            left join media large_image on(i.media_id_large = large_image.Id)
            left join media small_image on(i.media_id_small = small_image.Id)
            left join account account_artist on(large_image.author_id = account_artist.Id) 
            left join account account_nominator on(i.nominated_by_id = account_nominator.Id)";

        database::executeSqlQuery($query, []);
    }

    private static function insertRaffleTicketItem() : void
    {

        $query = "INSERT INTO item (Id, `type`, rarity, media_id_large, media_id_small, `desc`, `name`, nominated_by_id, collection_id, equipable, equipment_slot, redeemable, useable, is_container, container_size, container_item_category, item_category, media_id_back) 
        VALUES(4, 2, 0, 21, 21, 'Can be used in Raffle Events to win rewards!', 'Raffle Ticket', NULL, NULL, 0, NULL, 0, 0, 0, -1, null, null, 21)";

        Database::executeSqlQuery($query, []);
    }

    private static function insertTableTopSimluatorItem() : void
    {
        $sql = "INSERT INTO item (Id, `type`, rarity, media_id_large, media_id_small, `desc`, `name`, nominated_by_id, collection_id, equipable, equipment_slot, redeemable, useable, is_container, container_size, container_item_category, item_category, media_id_back)
        VALUES(81, 3, 0, 282, 282, 'test table top simulator copy', 'tabletestsimulator', null, null, 0, null, 1, 0, 0, -1, null, null, 282)";

        Database::executeSqlQuery($sql, []);
    }

    //MEDIA
    private static function createMediaTable() : void
    {

        $query = "CREATE TABLE media (
            Id int(11) NOT NULL AUTO_INCREMENT,
            ServiceKey varchar(36) NOT NULL,
            `name` varchar(45) NOT NULL,
            `desc` varchar(255) NOT NULL,
            author_id int(11) NOT NULL,
            DateCreated timestamp NOT NULL DEFAULT utc_timestamp(),
            extension varchar(10) NOT NULL,
            Directory varchar(255) NOT NULL,
            PRIMARY KEY (Id),
            KEY idx_media_id (DateCreated,Id)
            ) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci";

        Database::executeSqlQuery($query, []);
    }

    private static function createMediaView() : void
    {

        $query = "CREATE VIEW v_media AS (
            select media.Id AS Id,
            media.name AS `name`,
            media.desc AS `desc`,
            media.author_id AS author_id,
            media.DateCreated AS DateCreated,
            media.extension AS extension,
            media.Directory AS directory,
            concat(media.Directory,'/',media.Id,'.',media.extension) AS mediaPath 
            from media)";

        Database::executeSqlQuery($query, []);
    }

    private static function insertRaffleTicketMedia() : void
    {
        $query = "INSERT INTO media (Id, ServiceKey, `name`, `desc`, author_id, dateCreated, extension, directory)
        VALUES (21, 'fdsafdsafdsafdsafdsafdsa', 'raffle ticket', 'raffle ticket', '1', '2023-02-21 20:30:39', 'png', 'items')";

        Database::executeSqlQuery($query, []);
    }

    private static function insertTableTopSimluatorMedia() : void
    {
        $query = "INSERT INTO media (Id, ServiceKey, `name`, `desc`, author_id, dateCreated, extension, directory)
        VALUES (282, 'asdfasdfasdfasdfasdfasf', 'table test simulator logo', 'table top simulator logo', '1', '2023-02-21 20:30:39', 'png', 'games')";

        Database::executeSqlQuery($query, []);
    }

    //LOOT
    private static function createLootTable() : void
    {

        $query = "CREATE TABLE `loot` (
        `Id` int(11) NOT NULL AUTO_INCREMENT,
        `opened` tinyint(4) NOT NULL DEFAULT 0,
        `nickname` varchar(255) DEFAULT '',
        `description` text DEFAULT NULL,
        `account_id` int(11) DEFAULT NULL,
        `item_id` int(11) NOT NULL DEFAULT 0,
        `quest_id` int(11) DEFAULT NULL,
        `dateObtained` datetime NOT NULL DEFAULT current_timestamp(),
        `redeemed` tinyint(4) NOT NULL DEFAULT 0,
        `container_loot_id` int(11) DEFAULT NULL,
        `quantity` bigint(20) NOT NULL DEFAULT 1,
        PRIMARY KEY (`Id`)
        ) ";

        Database::executeSqlQuery($query, []);
    }

    private static function createLootView() : void
    {

        $query = "CREATE VIEW v_loot_item AS 
            select lt.Id AS Id,
            lt.opened AS opened,
            lt.account_id AS account_id,
            lt.item_id AS item_id,
            lt.quest_id AS quest_id,
            it.media_id_small AS media_id_small,
            it.media_id_large AS media_id_large,
            it.media_id_back AS media_id_back,
            it.type AS loot_type,
            it.desc AS `desc`,
            it.rarity AS rarity,
            lt.dateObtained AS dateObtained,
            lt.container_loot_id AS container_loot_id 
            from (loot lt join item it on(lt.item_id = it.Id))";

        Database::executeSqlQuery($query, []);
    }

    private static function insertRaffleTicketsForTestAccount(int $numberOfRaffleTicketsToGive, vRecordId $accountId) : void
    {

        if($numberOfRaffleTicketsToGive <= 0)
        {
            return;
        }

        $insertPart = "(28, 1, $accountId->crand, 4, NULL, '2023-01-14 17:01:39', 1)";

        for($numOfLoot = 1; $numOfLoot < $numberOfRaffleTicketsToGive; $numOfLoot++)
        {
            $id = new RecordId();
            $insertPart = $insertPart.",($id->crand, 1, $accountId->crand, 4, NULL, '2023-01-14 17:01:39', 1)";
        }

        $query = "INSERT INTO `loot` (`Id`, `opened`, `account_id`, `item_id`, `quest_id`, `dateObtained`, `redeemed`) VALUES
            $insertPart;";

        Database::executeSqlQuery($query, []);
    }

    private static function insertTestProductLootForTestStore(int $num, vRecordId $storeOwnerId) : void
    {
        $id = new RecordId();
        $sql = "INSERT INTO loot (Id, opened, nickname, description, account_id, item_id, quest_id, dateObtained, redeemed, container_loot_id, quantity) VALUES ($id->crand, 1, 'testNickname', 'testDescription', $storeOwnerId->crand, 81, null, NOW(), 1, null, 1)";

        for($i = 1; $i < $num; $i++)
        {
            $id = new RecordId();

            $sql = $sql.", ( $id->crand, 1, 'testNickname', 'testDescription', $storeOwnerId->crand, 81, null, NOW(), 1, null, 1)";
        }

        $sql = $sql.";";

        Database::executeSqlQuery($sql, []);
    }

    //TRADE
    private static function createTradeTable() : void
    {

        $query = "CREATE TABLE trade (
            id int(11) NOT NULL AUTO_INCREMENT,
            from_account_id int(11) DEFAULT NULL,
            to_account_id int(11) DEFAULT NULL,
            loot_id int(11) DEFAULT NULL,
            trade_date timestamp NOT NULL DEFAULT utc_timestamp(),
            from_account_obtain_date datetime DEFAULT NULL,
            PRIMARY KEY (id)
            )";

        Database::executeSqlQuery($query, []);
    }

    private static function createTradeView() : void
    {

        $query = "CREATE VIEW v_trade AS (select trade.id AS id,
            trade.from_account_id AS from_account_id,
            trade.to_account_id AS to_account_id,
            trade.loot_id AS loot_id,
            trade.trade_date AS trade_date,
            trade.from_account_obtain_date AS from_account_obtain_date from trade)";

        Database::executeSqlQuery($query, []);
    }

    //Transaction
    private static function createTransactionTable() : void
    {
        $sql = "CREATE TABLE `transaction`
        (
            ctime datetime(6) not null,
            crand bigint not null,
            description varchar(200),
            complete boolean not null default 0,
            void boolean not null default 0,
            ref_prices_ctime_crand LONGTEXT not null default '[]',
            
            PRIMARY KEY (ctime, crand),
            
            CONSTRAINT chk_transaction_complete_void CHECK (
                NOT (complete = TRUE AND void = TRUE)
            )
        );";

        Database::executeSqlQuery($sql, []);
    }

    
}

?>