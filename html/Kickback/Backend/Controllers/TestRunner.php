<?php

declare(strict_types = 1);

namespace Kickback\Backend\Controllers;

use \Kickback\Services\Database;

use \Kickback\Backend\Models\Test;
use \Kickback\Backend\Models\Cart;
use \Kickback\Backend\Models\Response;
use Kickback\Backend\Models\Account;
use Kickback\Backend\Models\Store;
use Kickback\Backend\Models\RecordId;

use \Kickback\Backend\Views\vTest;
use \Kickback\Backend\Views\vCart;
use \Kickback\Backend\Views\vRecordId;
use \Kickback\Backend\Views\vStore;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vStoreStock;

use \Kickback\Backend\Controllers\StoreController;
use \Kickback\Backend\Controllers\CartController;
use \Kickback\Backend\Controllers\ProductController;

use Exception;
use Kickback\Backend\Models\Media;
use Kickback\Backend\Models\Product;
use Kickback\Backend\Models\StoreStock;
use Kickback\Backend\Views\vPrice;
use Kickback\Backend\Views\vProduct;

class TestRunner
{
    public function addStoreDatabaseSchema()
    {
        $databaseName = "kickbackdb";
        TestRunner::addStoreSchemaToDatabase($databaseName);

        $account = AccountController::getAccountByUsername('KickbackKingdom')->data;

        $store = TestRunner::insertTestStore($account, $databaseName);
        $store = TestRunner::returnTestStore($store, $databaseName);

        $product = TestRunner::insertTestProduct($databaseName, $store);
        $product = TestRunner::returnTestProduct($product, $databaseName);

        $storeStock = TestRunner::insertTestStoreStock($store, $product, $databaseName);
        $storeStock = TestRunner::returnTestStoreStock($storeStock, $databaseName);

        echo "Store Schema Populated To $databaseName";
    }

    

    public function testAll() : void
    {
        $this->testAll_cart();
        //$this->testAll_account_multiple_cart();
    }

    public function testAll_account_multiple_cart() : void
    {
        $databaseName = TestRunner::createTestEnviroment();

        TestRunner::insertTestAccount($databaseName);
        $account = TestRunner::returnTestAccount($databaseName);

        $store = TestRunner::insertTestStore($account, $databaseName);
        $store = TestRunner::returnTestStore($store, $databaseName);

        $anotherStore = TestRunner::insertAnotherTestStore($account, $databaseName);
        $anotherStore = TestRunner::returnTestStore($anotherStore, $databaseName);

        $product = TestRunner::insertTestProduct($databaseName, $store);
        $product = TestRunner::returnTestProduct($product, $databaseName);

        TestRunner::insertCurrencyItem($databaseName);
        
        $cart = TestRunner::returnTestCart($account, $store, $databaseName);
        $anotherCart = TestRunner::returnTestCart($account, $store, $databaseName);

        $storeStock = TestRunner::insertTestStoreStock($store, $product, $databaseName);
        $storeStock = TestRunner::returnTestStoreStock($storeStock, $databaseName);

        $anotherStock = TestRunner::insertTestStoreStock($store, $product, $databaseName);
        $anotherStock = TestRunner::returnTestStoreStock($storeStock, $databaseName);

        $addResp = CartController::addStoreStock($storeStock, $cart);
        $addAnotherResp = CartController::addStoreStock($anotherStock, $anotherCart);
        if(!$addResp->success)
        {
            throw new Exception("Error in adding store stock to cart during account checkout integration test : $addResp->message");
        }

        if(!$addAnotherResp->success)
        {
            throw new Exception("Error in adding another store stock to cart during account checkout integration test : $addResp->message");
        }

        $generateResp = CartController::generateTransactions($account, $databaseName);
        if(!$generateResp->success)
        {
            throw new Exception("Error in generating transcations during account checkout integration test : $generateResp->message");
        }

        $getItemTotalsResp = CartController::getItemTotals($account, $databaseName);
        if(!$getItemTotalsResp->success)
        {
            throw new Exception("Error in getting item totals during account checkout integration test : $getItemTotalsResp->message");
        }

        $payTotalsResp = CartController::payTotals($account, null, $databaseName);
        if(!$payTotalsResp->success)
        {
            throw new Exception("Error in paying totals during account checkout integration test : $payTotalsResp->message");
        }

        $checkoutResp = CartController::checkout($account, $databaseName);
        if(!$checkoutResp->success)
        {
            throw new Exception("Error in checking out cart during account checkout integration test : $checkoutResp->message");
        }

        $cleanedUp = TestRunner::cleanupTestEnviroment($databaseName);

        if($cleanedUp)
        {
            echo "unit tests ran";
        }
        else
        {
            echo "Failed to cleanup test enviroment";
        } 
    }

    public function testAll_cart() : void
    {
        $databaseName = TestRunner::createTestEnviroment();

        TestRunner::insertTestAccount($databaseName);
        $account = TestRunner::returnTestAccount($databaseName);

        $store = TestRunner::insertTestStore($account, $databaseName);
        $store = TestRunner::returnTestStore($store, $databaseName);


        $product = TestRunner::insertTestProduct($databaseName, $store);
        $product = TestRunner::returnTestProduct($product, $databaseName);

        TestRunner::insertCurrencyItem($databaseName);
        
        $cart = TestRunner::returnTestCart($account, $store, $databaseName);

        $storeStock = TestRunner::insertTestStoreStock($store, $product, $databaseName);
        $storeStock = TestRunner::returnTestStoreStock($storeStock, $databaseName);

        $addResp = CartController::addStoreStock($storeStock, $cart);
        if(!$addResp->success)
        {
            throw new Exception("Error in adding store stock to cart : $addResp->message");
        }

        $generateResp = CartController::generateTransactions($cart, $databaseName);
        if(!$generateResp->success)
        {
            throw new Exception("Error in generating transcations during test : $generateResp->message");
        }

        $getItemTotalsResp = CartController::getItemTotals($cart, $databaseName);
        if(!$getItemTotalsResp->success)
        {
            throw new Exception("Error in getting item totals $getItemTotalsResp->message");
        }

        $payTotalsResp = CartController::payTotals($cart, null, $databaseName);
        if(!$payTotalsResp->success)
        {
            throw new Exception("Error in paying totals during test $payTotalsResp->message");
        }

        $checkoutResp = CartController::checkout($cart, $databaseName);
        if(!$checkoutResp->success)
        {
            throw new Exception("Error in checking out cart during test $checkoutResp->message");
        }

        $cleanedUp = TestRunner::cleanupTestEnviroment($databaseName);

        if($cleanedUp)
        {
            echo "unit tests ran";
        }
        else
        {
            echo "Failed to cleanup test enviroment";
        } 
    }

    public function test_getItemTotals() : void
    {
        $databaseName = TestRunner::createTestEnviroment();

        TestRunner::insertTestAccount($databaseName);
        $account = TestRunner::returnTestAccount($databaseName);

        $store = TestRunner::insertTestStore($account, $databaseName);
        $store = TestRunner::returnTestStore($store, $databaseName);

        $product = TestRunner::insertTestProduct($databaseName, $store);
        $product = TestRunner::returnTestProduct($product, $databaseName);

        TestRunner::insertCurrencyItem($databaseName);
        
        $cart = TestRunner::returnTestCart($account, $store, $databaseName);

        $storeStock = TestRunner::insertTestStoreStock($store, $product, $databaseName);
        $storeStock = TestRunner::returnTestStoreStock($storeStock, $databaseName);

        CartController::addStoreStock($storeStock, $cart);
        $resp = CartController::getItemTotals($cart, $databaseName);

        if($resp->success == false)
        {
            throw new Exception("TEST FAILED : genarate transactions returned non-successful response : ". json_encode($resp));
        }
    }

    public function test_generateTranactions() : void
    {
        $databaseName = TestRunner::createTestEnviroment();

        TestRunner::insertTestAccount($databaseName);
        $account = TestRunner::returnTestAccount($databaseName);

        $store = TestRunner::insertTestStore($account, $databaseName);
        $store = TestRunner::returnTestStore($store, $databaseName);

        $product = TestRunner::insertTestProduct($databaseName, $store);
        $product = TestRunner::returnTestProduct($product, $databaseName);

        TestRunner::insertCurrencyItem($databaseName);
        
        $cart = TestRunner::returnTestCart($account, $store, $databaseName);

        $storeStock = TestRunner::insertTestStoreStock($store, $product, $databaseName);
        $storeStock = TestRunner::returnTestStoreStock($storeStock, $databaseName);

        CartController::addStoreStock($storeStock, $cart);
        $resp = CartController::generateTransactions($cart, $databaseName);

        if($resp->success == false)
        {
            throw new Exception("TEST FAILED : genarate transactions returned non-successful response : ". json_encode($resp));
        }
    }

    public static function createDebugCartWithStocks() : Response
    {
        $resp = new Response(false, "Unknown error in creating a debug cart with stocks", null);

        try
        {
            $databaseName = TestRunner::createTestEnviroment();

            TestRunner::insertTestAccount($databaseName);
            $account = TestRunner::returnTestAccount($databaseName);
    
            $store = TestRunner::insertTestStore($account, $databaseName);
            $store = TestRunner::returnTestStore($store, $databaseName);
    
            $product = TestRunner::insertTestProduct($databaseName, $store);
            $product = TestRunner::returnTestProduct($product, $databaseName);
    
            TestRunner::insertCurrencyItem($databaseName);
            
            $cart = TestRunner::returnTestCart($account, $store, $databaseName);
    
            $storeStock = TestRunner::insertTestStoreStock($store, $product, $databaseName);
            $storeStock = TestRunner::returnTestStoreStock($storeStock, $databaseName);
    
            CartController::addStoreStock($storeStock, $cart);
            CartController::generateTransactions($cart, $databaseName);

            $resp->success = true;
            $resp->message = "Debug with stock craeted";
            $resp->data = ["database"=>$databaseName,"cart"=>$cart];
        }
        catch(Exception $e)
        {
            $resp->message = "Exception caught while trying to create debug cart with stocks : $e";
        }

        return $resp;
    }

    private static function addStoreSchemaToDatabase(string $databaseName) : void
    {
        Database::changeDatabase($databaseName);

        TestRunner::createStoreTable($databaseName);
        TestRunner::createProductTable($databaseName);
        TestRunner::createCartTable($databaseName);
        TestRunner::createStoreStockTable($databaseName);
        TestRunner::createCouponTable($databaseName);
        TestRunner::createTransactionTable($databaseName);
        TestRunner::createStoreStockCartLinkTable($databaseName);
        TestRunner::createCartTransactionGroupTable($databaseName);
        TestRunner::createTransactionCartTransactionGroupLinkTable($databaseName);
        TestRunner::createTransactionLogTable($databaseName);

        TestRunner::createStoreView($databaseName);
        TestRunner::createProductView($databaseName);  
        TestRunner::createCartView($databaseName);
        TestRunner::createStoreStockView($databaseName);
        TestRunner::createCouponView($databaseName);
        TestRunner::createTransactionView($databaseName);
        TestRunner::createStoreStockCartLinkView($databaseName);
        TestRunner::createCartTransactionGroupView($databaseName);
        TestRunner::createTransactionCartTransactionGroupLinkView($databaseName);
        TestRunner::createTradeView($databaseName);
        TestRunner::createTransactionLogView($databaseName);
    }

    private static function createTestEnviroment() : string
    {
        $randomId = new RecordId();
        $databaseName = "TEST_DATABASE_STORE_DELETE_IF_STILL_PRESENT_$randomId->crand";

        $query = "CREATE DATABASE $databaseName";
        Database::executeSqlQuery($query, []);

        Database::changeDatabase($databaseName);

        TestRunner::createAccountTable($databaseName);
        TestRunner::createStoreTable($databaseName);
        TestRunner::createItemTable($databaseName);
        TestRunner::createProductTable($databaseName);
        TestRunner::createMediaTable($databaseName);
        TestRunner::createCartTable($databaseName);
        TestRunner::createStoreStockTable($databaseName);
        TestRunner::createLootTable($databaseName);
        TestRunner::createCouponTable($databaseName);
        TestRunner::createTransactionTable($databaseName);
        TestRunner::createStoreStockCartLinkTable($databaseName);
        TestRunner::createCartTransactionGroupTable($databaseName);
        TestRunner::createTransactionCartTransactionGroupLinkTable($databaseName);
        TestRunner::createTradeTable($databaseName);
        TestRunner::createTransactionLogTable($databaseName);

        TestRunner::createMediaView($databaseName);
        TestRunner::createAccountView($databaseName);
        TestRunner::createStoreView($databaseName);
        TestRunner::createProductView($databaseName);  
        TestRunner::createCartView($databaseName);
        TestRunner::createStoreStockView($databaseName);
        TestRunner::createLootView($databaseName);
        TestRunner::createCouponView($databaseName);
        TestRunner::createTransactionView($databaseName);
        TestRunner::createStoreStockCartLinkView($databaseName);
        TestRunner::createCartTransactionGroupView($databaseName);
        TestRunner::createTransactionCartTransactionGroupLinkView($databaseName);
        TestRunner::createTradeView($databaseName);
        TestRunner::createTransactionLogView($databaseName);

        TestRunner::insertTestMediaForProduct($databaseName);
        TestRunner::insertItemObject($databaseName);
        TestRunner::insertKickbackKingdomAccount($databaseName);

        return $databaseName;
    }

    private static function cleanupTestEnviroment(string $databaseName) : bool
    {
        $query = "DROP DATABASE $databaseName";

        Database::executeSqlQuery($query, []);

        return true;
    }

    //RETURN OBJECTS

    private static function returnTestAccount(string $databaseName) : vAccount
    {
        $accountId = new vRecordId("2022-10-06 16:46:07", 1);

        Database::changeDatabase($databaseName);

        $getAccountResp = AccountController::getAccountById($accountId);

        if($getAccountResp->success)
        {
            return $getAccountResp->data;
        }
        else
        {
            throw new Exception("Failed to get test account : ".$getAccountResp->message);
        }
    }

    private static function returnTestStore(vRecordId $store, string $databaseName) : vStore
    {
        $getStoreResp = StoreController::get($store, $databaseName);

        if($getStoreResp->success)
        {
            return $getStoreResp->data;
        }
        else
        {
            throw new Exception("Failed to get test store : ".$getStoreResp->message." STORE INFO : ".json_encode($store));
        }  
    }

    private static function returnTestCart(vRecordId $accountId, vStore $store, string $databaseName) : vCart
    {
        Database::changeDatabase($databaseName);

        $cart = new Cart($accountId, $store);

        $createCartResp = CartController::insert($cart);

        if($createCartResp->success)
        {
            $testCartResp = CartController::get($cart);

            if($testCartResp->success)
            {
                return $testCartResp->data;
            }
            else
            {
                throw new Exception("Failed to get test cart : ".$testCartResp->message);
            }
        }
        else
        {
            throw new Exception("Faile to create test cart : $createCartResp->message");
        }
    }

    private static function returnTestProduct(vRecordId $productId, string $databaseName) : vProduct
    {
        $getProductResp = ProductController::get($productId, $databaseName);
        
        if($getProductResp->success)
        {
            return $getProductResp->data;
        }
        else
        {
            throw new Exception("Product Not Found : $getProductResp->message");
        }
    }

    private static function returnTestProductObject(vRecordId $storeId) : Product
    {
        $testPrice = new vPrice(1);
        $currencyItem = new vRecordId("2024-01-09 11:58:40", 4);
        $itemReference = new vRecordId('2024-01-09 16:58:40', 81);
        $testProduct = new Product("CheapTestProduct", "Test-Product-Cheap", $itemReference, $currencyItem, $testPrice, "Test Description", $storeId);

        return $testProduct;
    }

    private static function returnTestStoreStock(vRecordId $storeStockId, string $databaseName) : vStoreStock
    {
        Database::changeDatabase($databaseName);

        $getTestStoreStockResp = StoreStockController::get($storeStockId);

        if($getTestStoreStockResp->success)
        {
            return $getTestStoreStockResp->data;
        }
        else
        {
            throw new Exception("Failed to get store stock : $getTestStoreStockResp->message");
        }
    }

    //INSERT OBJECTS

    private static function insertItemObject(string $databaseName) : void
    {
        Database::changeDatabase($databaseName);

        $query = "INSERT INTO item (Id, `type`, rarity, media_id_large, media_id_small, `desc`, `name`, nominated_by_id, collection_id, equipable, equipment_slot, redeemable) VALUES
                (81, 3, 0, 63, 63, 'TestProductItemReference', 'TestProductItemReference', NULL, NULL, 0, NULL, 0);";

        Database::executeSqlQuery($query, []);
    }

    private static function insertTestMediaForProduct(string $databaseName) : void
    {
        Database::changeDatabase($databaseName);

        $query = "INSERT INTO media (Id, ServiceKey, `name`, `desc`, author_id, DateCreated, extension, Directory) VALUES
            (63, 'serviceKeyHash', 'medium bag of gold', 'medium bag of gold', 1, '2024-01-09 16:58:40', 'png', 'items');";

        Database::executeSqlQuery($query, []);
    }

    private static function insertCurrencyItem(string $databaseName) : void
    {
        Database::changeDatabase($databaseName);

        $query = "INSERT INTO item (Id, type, rarity, media_id_large, media_id_small, `desc`, `name`, nominated_by_id, collection_id, equipable, equipment_slot, redeemable) VALUES
        (4, 2, 0, 21, 21, 'Can be used in Raffle Events to win rewards!', 'Raffle Ticket', NULL, NULL, 0, NULL, 0)";

        Database::executeSqlQuery($query, []);
    }

    private static function insertTestProduct(string $databaseName, vRecordId $storeId) : Product
    {
        $testProduct = TestRunner::returnTestProductObject($storeId);

        $insertProductResp = ProductController::insert($testProduct, $databaseName);

        if(!$insertProductResp->success)
        {
            throw new Exception("Unable to insert test product : $insertProductResp->message");
        }

        return $testProduct;
    }

    private static function insertTestStore(vRecordId $accountId, string $databaseName) : Store
    {
        $store = new Store("Test_Store_Delete_If_Present", "a test store, delete this if it is still present; tests should have already removed it","Test-Store-Delete-If-Present", $accountId->ctime, $accountId->crand);

        $storeCreateResp = StoreController::insert($store, $databaseName);

        if(!$storeCreateResp->success)
        {
            throw new Exception("Unable to insert test store : $storeCreateResp->message");
        }

        return $store;
    }

    private static function insertAnotherTestStore(vRecordId $accountId, string $databaseName) : Store
    {
        $store = new Store("Another_Test_Store_Delete_If_Present", "a test store, delete this if it is still present; tests should have already removed it","Another_Test-Store-Delete-If-Present", $accountId->ctime, $accountId->crand);

        $storeCreateResp = StoreController::insert($store, $databaseName);

        if(!$storeCreateResp->success)
        {
            throw new Exception("Unable to insert test store : $storeCreateResp->message");
        }

        return $store;
    }

    private static function insertTestAccount(string $databaseName) : void
    {
        Database::changeDatabase($databaseName);

        $query = "INSERT INTO account 
        (id, email, `password`, firstName, lastName, DateCreated, Username, Banned, pass_reset, passage_id) VALUES 
        (1, 'emailgoeshere@gmail.com', 'somepasswordhash', 'john', 'test', '2022-10-06 16:46:07', 'tester', 0, 0, 1);";

        Database::executeSqlQuery($query, []);
    }

    private static function insertTestStoreStock(vStore $store, vProduct $product, string $databaseName) : StoreStock
    {
        $storeStock = new StoreStock($product->price, $product, $store, false);

        $createStoreStockResp = StoreStockController::insert($storeStock);

        if(!$createStoreStockResp->success)
        {
            throw new Exception("Failed to get testStoreStock : $createStoreStockResp->message");
        }
        
        return $storeStock;
    } 

    private static function insertTestLootArray(int $numberOfRaffleTicketsToGive, vRecordId $accountId, string $databaseName) : void
    {
        Database::changeDatabase($databaseName);

        $insertPart = "";

        for($numOfLoot = 0; $numOfLoot < $numberOfRaffleTicketsToGive; $numOfLoot++)
        {

        }

        $query = "INSERT INTO `loot` (`Id`, `opened`, `account_id`, `item_id`, `quest_id`, `dateObtained`, `redeemed`) VALUES
            (28, 1, $accountId->crand, 4, NULL, '2023-01-14 17:01:39', 1);";

        Database::executeSqlQuery($query, []);
    }

    private static function insertKickbackKingdomAccount(string $databaseName) : void
    {
        Database::changeDatabase($databaseName);

        $query = "INSERT INTO `ACCOUNT` (`Id`, `Email`, `Password`, `FirstName`, `LastName`, `DateCreated`, `Username`, `Banned`, `pass_reset`, `passage_id`) VALUES
            (46, 'horsemen@kickback-kingdom.com', 'PassHashForKickbackKingdom', 'Four', 'Horsemen', '2023-11-16 14:28:12', 'KickbackKingdom', 0, 3329915, 174);";

        Database::executeSqlQuery($query, []);
    }

    //CREATE TABLES

    private static function createAccountTable(string $databaseName) : void
    {
        Database::changeDatabase($databaseName);

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

    private static function createStoreTable(string $databaseName) : void
    {
        Database::changeDatabase($databaseName);

        $query = "CREATE TABLE store (
            ctime datetime NOT NULL,
            crand bigint(20) NOT NULL,
            `name` varchar(50) DEFAULT NULL,
            `description` varchar(500) DEFAULT NULL,
            locator varchar(100) NOT NULL,
            ref_account_ctime datetime NOT NULL,
            ref_account_crand bigint(20) NOT NULL,
            PRIMARY KEY (ctime,crand),
            UNIQUE KEY locator (locator),
            KEY index_ref_account (ref_account_ctime,ref_account_crand)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci";

        Database::executeSqlQuery($query, []);
    }

    private static function createProductTable(string $databaseName) : void
    {
        Database::changeDatabase($databaseName);

        $query = "CREATE TABLE product (
            ctime datetime NOT NULL,
            crand bigint(20) NOT NULL,
            `name` varchar(50) NOT NULL,
            `description` varchar(1000) DEFAULT NULL,
            price bigint(20) NOT NULL,
            locator varchar(50) NOT NULL,
            ref_item_ctime datetime NOT NULL,
            ref_item_crand bigint(20) NOT NULL,
            ref_currency_item_ctime datetime NOT NULL,
            ref_currency_item_crand bigint(20) NOT NULL,
            ref_store_ctime DATETIME NOT NULL,
            ref_store_crand bigint(20) NOT NULL,
            PRIMARY KEY (ctime,crand),
            UNIQUE KEY locator (locator),
            KEY idx_product_ref_currency_item_ctime_crand (ref_currency_item_ctime,ref_currency_item_crand),
            KEY idx_product_ref_item_ctime_crand (ref_item_ctime,ref_item_crand),
            /*CONSTRAINT fk_product_ref_item_ctime_crand_item_ctime_crand FOREIGN KEY (ref_item_ctime, ref_item_crand) REFERENCES item (ctime, crand),
            CONSTRAINT fk_product_ref_currency_item_ctime_crand_item_ctime_crand FOREIGN KEY (ref_currency_item_ctime, ref_currency_item_crand) REFERENCES item (ctime, crand),*/
            CONSTRAINT fk_product_ref_store_ctime_crand_store_ctime_crand FOREIGN KEY (ref_store_ctime, ref_store_crand) REFERENCES store(ctime, crand)
            );";

        Database::executeSqlQuery($query, []);
    }

    private static function createItemTable(string $databaseName) : void
    {
        Database::changeDatabase($databaseName);

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
            PRIMARY KEY (Id)
            )";

        Database::executeSqlQuery($query, []);
    }

    private static function createMediaTable(string $databaseName) : void
    {
        Database::changeDatabase($databaseName);

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

    private static function createCartTable(string $databaseName) : void
    {
        Database::changeDatabase($databaseName);

        $query = "CREATE TABLE cart (
            ctime datetime NOT NULL,
            crand bigint(20) NOT NULL,
            checked_out tinyint(1) DEFAULT NULL,
            ref_store_ctime datetime NOT NULL,
            ref_store_crand bigint(20) NOT NULL,
            ref_account_ctime datetime NOT NULL,
            ref_account_crand bigint(20) NOT NULL,
            PRIMARY KEY (ctime,crand),
            KEY idx_cart_ref_store_ctime_crand (ref_store_ctime,ref_store_crand),
            KEY idx_cart_ref_account_ctime_crand (ref_account_ctime,ref_account_crand),
            CONSTRAINT fk_cart_store_ctime_crand FOREIGN KEY (ref_store_ctime, ref_store_crand) REFERENCES store (ctime, crand)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci";

        Database::executeSqlQuery($query, []);
    }

    private static function createStoreStockTable(string $databaseName) : void
    {
        Database::changeDatabase($databaseName);

        $query = "CREATE TABLE store_stock (
            ctime datetime NOT NULL,
            crand bigint(20) NOT NULL,
            price int(11) NOT NULL,
            removed tinyint(1) NOT NULL,
            ref_product_ctime datetime NOT NULL,
            ref_product_crand bigint(20) NOT NULL,
            ref_store_ctime datetime NOT NULL,
            ref_store_crand bigint(20) NOT NULL,
            PRIMARY KEY (ctime,crand),
            KEY idx_store_stock_ref_product_ctime_crand (ref_product_ctime,ref_product_crand),
            KEY idx_store_stock_ref_store_ctime_crand (ref_store_ctime,ref_store_crand),
            CONSTRAINT fk_store_stock_product_ctime_crand FOREIGN KEY (ref_product_ctime, ref_product_crand) REFERENCES product (ctime, crand),
            CONSTRAINT fk_store_stock_store_ctime_crand FOREIGN KEY (ref_store_ctime, ref_store_crand) REFERENCES store (ctime, crand)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci";

        Database::executeSqlQuery($query, []);
    }

    private static function createLootTable(string $databaseName) : void
    {
        Database::changeDatabase($databaseName);

        $query = " 	CREATE TABLE loot (
            Id int(11) NOT NULL AUTO_INCREMENT,
            opened tinyint(4) NOT NULL DEFAULT 0,
            account_id int(11) DEFAULT NULL,
            item_id int(11) NOT NULL DEFAULT 0,
            quest_id int(11) DEFAULT NULL,
            dateObtained datetime NOT NULL DEFAULT current_timestamp(),
            redeemed tinyint(4) NOT NULL DEFAULT 0,
            PRIMARY KEY (Id)
            ) ENGINE=InnoDB AUTO_INCREMENT=2993 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci";

        Database::executeSqlQuery($query, []);
    }

    private static function createStoreStockCartLinkTable(string $databaseName) : void
    {
        Database::changeDatabase($databaseName);

        $query = "CREATE TABLE store_stock_cart_link (
            ctime datetime NOT NULL,
            crand bigint(20) NOT NULL,
            price int(11) DEFAULT NULL,
            checked_out tinyint(1) NOT NULL,
            removed tinyint(1) NOT NULL,
            ref_currency_item_ctime datetime DEFAULT NULL,
            ref_currency_item_crand bigint(20) DEFAULT NULL,
            ref_store_stock_ctime datetime NOT NULL,
            ref_store_stock_crand bigint(20) NOT NULL,
            ref_cart_ctime datetime NOT NULL,
            ref_cart_crand bigint(20) NOT NULL,
            ref_coupon_ctime datetime DEFAULT NULL,
            ref_coupon_crand bigint(20) DEFAULT NULL,
            ref_transaction_ctime datetime DEFAULT NULL,
            ref_transaction_crand bigint(20) DEFAULT NULL,
            PRIMARY KEY (ctime,crand),
            KEY idx_store_stock_cart_link_ref_store_stock_ctime_crand (ref_store_stock_ctime,ref_store_stock_crand),
            KEY idx_store_stock_cart_link_ref_cart_ctime_crand (ref_cart_ctime,ref_cart_crand),
            KEY idx_store_stock_cart_link_ref_coupon_ctime_crand (ref_coupon_ctime,ref_coupon_crand),
            KEY idx_store_stock_cart_link_ref_transaction_ctime_crand (ref_transaction_ctime,ref_transaction_crand),
            CONSTRAINT fk_store_stock_cart_link_cart_ctime_crand FOREIGN KEY (ref_cart_ctime, ref_cart_crand) REFERENCES cart (ctime, crand),
            CONSTRAINT fk_store_stock_cart_link_coupon_ctime_crand FOREIGN KEY (ref_coupon_ctime, ref_coupon_crand) REFERENCES coupon (ctime, crand),
            CONSTRAINT fk_store_stock_cart_link_store_stock_ctime_crand FOREIGN KEY (ref_store_stock_ctime, ref_store_stock_crand) REFERENCES store_stock (ctime, crand),
            CONSTRAINT fk_store_stock_cart_link_transaction_ctime_crand FOREIGN KEY (ref_transaction_ctime, ref_transaction_crand) REFERENCES `transaction` (ctime, crand)
        )";

        Database::executeSqlQuery($query, []);
    }

    private static function createCouponTable(string $databaseName) : void
    {
        Database::changeDatabase($databaseName);

        $query = "CREATE TABLE coupon (
            ctime datetime NOT NULL,
            crand bigint(20) NOT NULL,
            `name` varchar(50) NOT NULL,
            code varchar(30) NOT NULL,
            uses int(11) DEFAULT NULL,
            use_limit int(11) DEFAULT NULL,
            percent_off decimal(3,2) DEFAULT NULL,
            amount_off bigint(20) DEFAULT NULL,
            ref_store_ctime datetime NOT NULL,
            ref_store_crand bigint(20) NOT NULL,
            PRIMARY KEY (ctime,crand),
            UNIQUE KEY ctime (ctime),
            UNIQUE KEY crand (crand),
            UNIQUE KEY code (code),
            KEY idx_coupon_ref_store_ctime_crand (ref_store_ctime,ref_store_crand),
            CONSTRAINT fk_coupon_store_ctime_crand FOREIGN KEY (ref_store_ctime, ref_store_crand) REFERENCES store (ctime, crand)
            )";
        
        Database::executeSqlQuery($query, []);
    }

    private static function createTransactionTable(string $databaseName) : void
    {
        Database::changeDatabase($databaseName);

        $query = "CREATE TABLE `transaction` (
            `ctime` datetime NOT NULL,
            `crand` bigint(20) NOT NULL,
            `amount` bigint(20) NOT NULL,
            `ref_currency_item_ctime` datetime NOT NULL,
            `ref_currency_item_crand` bigint(20) NOT NULL,
            `payed` tinyint(1) NOT NULL,
            `complete` tinyint(1) NOT NULL,
            `void` tinyint(1) NOT NULL,
            `ref_account_ctime` datetime NOT NULL,
            `ref_account_crand` bigint(20) NOT NULL,
            PRIMARY KEY (`ctime`,`crand`),
            UNIQUE KEY `crand` (`crand`),
            KEY `idx_transaction_ref_account_ctime_crand` (`ref_account_ctime`,`ref_account_crand`),
            KEY `idx_transaction_ref_currency_item_ctime_crand` (`ref_currency_item_ctime`,`ref_currency_item_crand`)
            ) ";

        Database::executeSqlQuery($query, []);
    }

    private static function createCartTransactionGroupTable(string $databaseName) : void
    {
        Database::changeDatabase($databaseName);

        $query = "CREATE TABLE cart_transaction_group (
            ctime datetime NOT NULL,
            crand bigint(20) NOT NULL,
            payed tinyint(1) NOT NULL,
            completed tinyint(1) NOT NULL,
            void tinyint(1) NOT NULL,
            ref_cart_ctime datetime NOT NULL,
            ref_cart_crand bigint(20) NOT NULL,
            PRIMARY KEY (ctime,crand),
            KEY idx_cart_transaction_group_ref_cart_ctime_crand (ref_cart_ctime,ref_cart_crand),
            CONSTRAINT fk_cart_transaction_group_cart_ctime_cart FOREIGN KEY (ref_cart_ctime, ref_cart_crand) REFERENCES cart (ctime, crand)
            )";

        Database::executeSqlQuery($query, []);
    }

    private static function createTransactionCartTransactionGroupLinkTable(string $databaseName) : void
    {
        Database::changeDatabase($databaseName);

        $query = "CREATE TABLE transaction_cart_transaction_group_link (
            ctime datetime NOT NULL,
            crand bigint(20) NOT NULL,
            ref_transaction_ctime datetime NOT NULL,
            ref_transaction_crand bigint(20) NOT NULL,
            ref_cart_transaction_group_ctime datetime NOT NULL,
            ref_cart_transaction_group_crand bigint(20) NOT NULL,
            PRIMARY KEY (ctime,crand),
            KEY idx_cart_transaction_group_link_ref_transaction_ctime_crand (ref_transaction_ctime,ref_transaction_crand),
            KEY idx_cart_transaction_group_link_ref_cart_transaction_ctime_crand (ref_cart_transaction_group_ctime,ref_cart_transaction_group_crand),
            CONSTRAINT fk_tx_cart_tx_group_link_cart_tx_group_ctime_crand FOREIGN KEY (ref_cart_transaction_group_ctime, ref_cart_transaction_group_crand) REFERENCES cart_transaction_group (ctime, crand),
            CONSTRAINT fk_tx_cart_tx_group_link_tx_ctime_crand FOREIGN KEY (ref_transaction_ctime, ref_transaction_crand) REFERENCES `transaction` (ctime, crand)
            )";

        Database::executeSqlQuery($query, []);
    }

    private static function createTradeTable(string $databaseName) : void
    {
        Database::changeDatabase($databaseName);

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

    private static function createTransactionLogTable($databaseName) : void
    {
        Database::changeDatabase($databaseName);

        $query ="CREATE TABLE transaction_log (
            ctime datetime NOT NULL,
            crand bigint(20) NOT NULL,
            `description` varchar(500) NOT NULL,
            `json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(json)),
            ref_account_ctime datetime NOT NULL,
            ref_account_crand bigint(20) NOT NULL,
            PRIMARY KEY (ctime,crand),
            UNIQUE KEY ctime (ctime),
            UNIQUE KEY crand (crand),
            KEY idx_transaction_account_ctime_crand (ref_account_ctime,ref_account_crand)
            )";

        Database::executeSqlQuery($query, []);
    }

    //CREATE VIEWS

    private static function createAccountView(string $databaseName) : void
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

    private static function createStoreView(string $databaseName)
    {
        Database::changeDatabase($databaseName);

        $query = "CREATE VIEW 
        v_store AS 
        (
        select 
        s.ctime AS ctime,
        s.crand AS crand,
        s.name AS name,
        s.description AS description,
        s.locator AS locator,
        s.ref_account_ctime AS ref_account_ctime,
        s.ref_account_crand AS ref_account_crand 
        from store s
        )";

        Database::executeSqlQuery($query, []);
    }

    private static function createProductView( string $databaseName) : void
    {
        Database::changeDatabase($databaseName);

        $query = "CREATE VIEW v_product AS (
            select 
            p.ctime AS ctime,
            p.crand AS crand,
            p.name AS name,
            p.locator AS locator,
            i.name AS ref_currency_item_name,
            p.price AS price,
            p.description AS description,
            p.ref_item_ctime AS ref_item_ctime,
            p.ref_item_crand AS ref_item_crand,
            p.ref_currency_item_ctime AS ref_currency_item_ctime,
            p.ref_currency_item_crand AS ref_currency_item_crand,
    		p.ref_store_ctime as ref_store_ctime,
    		p.ref_store_crand as ref_store_crand,
            ms.mediaPath AS ref_small_image_path,
            ml.mediaPath AS ref_large_image_path from (((product p 
            left join item i on(p.ref_item_crand = i.Id)) 
            left join v_media ml on(i.media_id_large = ml.Id)) 
            left join v_media ms on(i.media_id_small = ms.Id)))
        ";

        Database::executeSqlQuery($query, []);
    }

    private static function createMediaView(string $databaseName) : void
    {
        Database::changeDatabase($databaseName);

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

    private static function createCartView(string $databaseName) : void
    {
        Database::changeDatabase($databaseName);

        $query = "CREATE VIEW v_cart AS (
            select 
            c.ctime AS ctime,
            c.crand AS crand,
            c.checked_out AS checked_out,
            c.ref_store_ctime AS ref_store_ctime,
            c.ref_store_crand AS ref_store_crand,
            c.ref_account_ctime AS ref_account_ctime,
            c.ref_account_crand AS ref_account_crand 
            from cart c);";

        Database::executeSqlQuery($query, []);
    }

    private static function createStoreStockView(string $databaseName) : void
    {
        Database::changeDatabase($databaseName);

        $query = "CREATE VIEW v_store_stock AS (select ss.ctime AS ctime,
            ss.crand AS crand,
            pv.name AS `name`,
            ss.price AS price,
            pv.price AS product_price,
            ss.removed AS removed,
            pv.locator AS locator,
            ic.name AS ref_currency_item_name,
            pv.description AS `description`,
            st.name AS store_name,
            ss.ref_product_ctime AS ref_product_ctime,
            ss.ref_product_crand AS ref_product_crand,
            pv.ref_currency_item_ctime AS ref_product_currency_item_ctime,
            pv.ref_currency_item_crand AS ref_product_currency_item_crand,
            ss.ref_store_ctime AS ref_store_ctime,
            ss.ref_store_crand AS ref_store_crand,
            pv.ref_currency_item_ctime AS ref_currency_item_ctime,
            pv.ref_currency_item_crand AS ref_currency_item_crand,
            pv.ref_small_image_path AS ref_small_image_path,
            pv.ref_large_image_path AS ref_large_image_path from 
            (((((
            store_stock ss 
            left join v_product pv on(ss.ref_product_ctime = pv.ctime and ss.ref_product_crand = pv.crand)) 
            left join store st on(st.ctime = ss.ref_store_ctime and st.crand = ss.ref_store_crand)) 
            left join item i on(pv.ref_item_crand = i.Id)) 
            left join item ic on(pv.ref_currency_item_crand = i.Id)) 
            left join media m on(i.media_id_small = m.Id or i.media_id_large = m.Id)))";

        Database::executeSqlQuery($query, []);
    }

    private static function createLootView($databaseName) : void
    {
        Database::changeDatabase($databaseName);

        $query = "CREATE VIEW v_loot_item AS select 
            a.Id AS Id,
            a.opened AS opened,
            a.account_id AS account_id,
            a.item_id AS item_id,
            a.quest_id AS quest_id,
            b.media_id_small AS media_id_small,
            b.media_id_large AS media_id_large,
            b.type AS loot_type,
            b.desc AS `desc`,
            b.rarity AS rarity,
            a.dateObtained AS dateObtained 
            from (loot a join item b on(a.item_id = b.Id))";

        Database::executeSqlQuery($query, []);
    }

    private static function createStoreStockLinkView($databaseName) : void
    {
        Database::changeDatabase($databaseName);

        $query = "CREATE VIEW v_store_stock_cart_link AS (
            select slink.ctime AS ctime,
            slink.crand AS crand,
            vss.name AS name,
            vss.description AS description,
            vss.product_price AS product_price,
            vss.price AS stock_price,
            slink.price AS price,
            i.name AS ref_currency_item_name,
            slink.checked_out AS checked_out,
            slink.removed AS removed,
            slink.ref_cart_ctime AS ref_cart_ctime,
            slink.ref_cart_crand AS ref_cart_crand,
            p.ctime AS ref_product_ctime,
            p.crand AS ref_product_crand,
            slink.ref_coupon_ctime AS ref_coupon_ctime,
            slink.ref_coupon_crand AS ref_coupon_crand,
            slink.ref_transaction_ctime AS ref_transaction_ctime,
            slink.ref_transaction_crand AS ref_transaction_crand,
            slink.ref_currency_item_ctime AS ref_currency_item_ctime,
            slink.ref_currency_item_crand AS ref_currency_item_crand,
            vss.ref_product_currency_item_ctime AS ref_product_currency_item_ctime,
            vss.ref_product_currency_item_crand AS ref_product_currency_item_crand,
            vss.ref_small_image_path AS ref_small_image_path,
            vss.ref_large_image_path AS ref_large_image_path from (((store_stock_cart_link slink 
            left join v_store_stock vss on(slink.ref_store_stock_ctime = vss.ctime and slink.ref_store_stock_crand = vss.crand)) 
            left join item i on(slink.ref_currency_item_crand = i.Id)) 
            left join product p on(p.ctime = vss.ref_product_ctime and p.crand = vss.ref_product_crand)))";

        Database::executeSqlQuery($query, []);
    }

    private static function createStoreStockCartLinkView($databaseName) : void
    {
        Database::changeDatabase($databaseName);

        $query = "CREATE VIEW v_store_stock_cart_link AS (
            select slink.ctime AS ctime,
            slink.crand AS crand,
            vss.name AS name,
            vss.description AS description,
            vss.product_price AS product_price,
            vss.price AS stock_price,
            slink.price AS price,
            i.name AS ref_currency_item_name,
            slink.checked_out AS checked_out,
            slink.removed AS removed,
            slink.ref_cart_ctime AS ref_cart_ctime,
            slink.ref_cart_crand AS ref_cart_crand,
            p.ctime AS ref_product_ctime,
            p.crand AS ref_product_crand,
            slink.ref_coupon_ctime AS ref_coupon_ctime,
            slink.ref_coupon_crand AS ref_coupon_crand,
            slink.ref_transaction_ctime AS ref_transaction_ctime,
            slink.ref_transaction_crand AS ref_transaction_crand,
            slink.ref_currency_item_ctime AS ref_currency_item_ctime,
            slink.ref_currency_item_crand AS ref_currency_item_crand,
            vss.ref_product_currency_item_ctime AS ref_product_currency_item_ctime,
            vss.ref_product_currency_item_crand AS ref_product_currency_item_crand,
            vss.ref_small_image_path AS ref_small_image_path,
            vss.ref_large_image_path AS ref_large_image_path from (((store_stock_cart_link slink 
            left join v_store_stock vss on(slink.ref_store_stock_ctime = vss.ctime and slink.ref_store_stock_crand = vss.crand)) 
            left join item i on(slink.ref_currency_item_crand = i.Id)) 
            left join product p on(p.ctime = vss.ref_product_ctime and p.crand = vss.ref_product_crand)))";

            Database::executeSqlQuery($query, []);
    }

    private static function createCouponView(string $databaseName) : void
    {
        Database::changeDatabase($databaseName);

        $query = "CREATE VIEW v_coupon AS (
                SELECT
                ctime,
                crand,
                name,
                code,
                uses,
                use_limit,
                percent_off,
                amount_off,
                ref_store_ctime,
                ref_store_crand
                FROM coupon                       
            );";

        Database::executeSqlQuery($query, []);
    }

    private static function createTransactionView(string $databaseName) : void
    {
        Database::changeDatabase($databaseName);

        $query = "CREATE VIEW v_transaction AS (select t.ctime AS ctime,
            t.crand AS crand,
            t.amount AS amount,
            t.ref_currency_item_ctime AS ref_currency_item_ctime,
            t.ref_currency_item_crand AS ref_currency_item_crand,
            a.FirstName AS FirstName,
            a.LastName AS LastName,
            t.payed AS payed,
            t.complete AS complete,
            t.void AS void,
            t.ref_account_ctime AS ref_account_ctime,
            t.ref_account_crand AS ref_account_crand from (`transaction` t 
            left join account a on(a.DateCreated = t.ref_account_ctime and a.Id = t.ref_account_crand)))
        ";

        Database::executeSqlQuery($query, []);
    }

    private static function createCartTransactionGroupView(string $databaseName) : void
    {
        Database::changeDatabase($databaseName);

        $query = "CREATE VIEW v_cart_transaction_group AS (select ctg.ctime AS ctime,
            ctg.crand AS crand,
            ctg.payed AS payed,
            ctg.completed AS completed,
            ctg.void AS void,
            ctg.ref_cart_ctime AS ref_cart_ctime,
            ctg.ref_cart_crand AS ref_cart_crand from cart_transaction_group ctg)";

        Database::executeSqlQuery($query ,[]);
    }

    private static function createTransactionCartTransactionGroupLinkView(string $databaseName) : void
    {
        Database::changeDatabase($databaseName);

        $query = "CREATE VIEW v_transaction_cart_transaction_group_link AS (select clink.ctime AS ctime,
            clink.crand AS crand,
            clink.ref_transaction_ctime AS ref_transaction_ctime,
            clink.ref_transaction_crand AS ref_transaction_crand,
            clink.ref_cart_transaction_group_ctime AS ref_cart_transaction_group_ctime,
            clink.ref_cart_transaction_group_crand AS ref_cart_transaction_group_crand from transaction_cart_transaction_group_link clink)";

        Database::executeSqlQuery($query, []);
    }

    private static function createTradeView(string $databaseName) : void
    {
        Database::changeDatabase($databaseName);

        $query = "CREATE VIEW v_trade AS (select trade.id AS id,
            trade.from_account_id AS from_account_id,
            trade.to_account_id AS to_account_id,
            trade.loot_id AS loot_id,
            trade.trade_date AS trade_date,
            trade.from_account_obtain_date AS from_account_obtain_date from trade)";

        Database::executeSqlQuery($query, []);
    }

    private static function createTransactionLogView(string $databaseName) : void
    {
        Database::changeDatabase($databaseName);

        $query = "CREATE VIEW v_transaction_log AS (select log.ctime AS ctime,
            log.crand AS crand,
            act.FirstName AS FirstName,
            act.LastName AS LastName,
            act.Username AS Username,
            log.description AS `description`,
            log.json AS json,
            log.ref_account_ctime AS ref_account_ctime,
            log.ref_account_crand AS ref_account_crand from (transaction_log log left join account act on(act.Id = log.ref_account_crand)))";

        Database::executeSqlQuery($query, []);
    }
}

?>