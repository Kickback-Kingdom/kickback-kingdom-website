<?php

declare(strict_types =1);

namespace Kickback\Backend\Controllers;

use DateTime;
use \Kickback\Backend\Models\Account;
use \Kickback\Backend\Models\RecordId;
use \Kickback\Backend\Models\Store;
use \Kickback\Services\Database;

use \Exception;
use Kickback\Backend\Models\Coupon;
use Kickback\Backend\Models\ForeignRecordId;
use Kickback\Backend\Models\Item;
use Kickback\Backend\Models\ItemRarity;
use Kickback\Backend\Models\ItemType;
use Kickback\Backend\Models\Price;
use Kickback\Backend\Models\Product;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vCart;
use Kickback\Backend\Views\vDateTime;
use Kickback\Backend\Views\vItem;
use Kickback\Backend\Views\vMedia;
use Kickback\Backend\Views\vProduct;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vStore;
use Kickback\Backend\Views\vCartItem;
use Kickback\Backend\Views\vCartProductLink;
use Kickback\Backend\Views\vCoupon;

class StoreTester
{
    public static function testStoreController()
    {
        StoreController::runUnitTests();
        
        //static::createBlankTestDatabase();

        //static::temp_populate_DB();
        
        static::test_apply_override_coupon_checkout_cart();
        //static::test_apply_coupon_checkout_cart();
        //static::test_apply_coupon();
        //static::manualtest_getStoreById();
        //static::test_Successful_TransactItems();
        /*static::test_Successful_ReserveNonFungibleProduct();
        static::test_NotEnoughtLootToBuy_ReserveNonFungibleProduct();
        static::testCartLootChecks();
        static::testLinkingFungibleProductsAndLoots();
        static::testLinkingProductsAndLoots();
        static::testCartItemControl();
        static::testCartCreation();
        static::testAddStore();
        static::testProductCreation();*/

        echo "Done!";
    }

    private static function createBlankTestDatabase() : void
    {
        $database = static::createTestEnviroment("blank_");

        try
        {
           
        }
        catch(Exception $e)
        {
            //static::cleanupTestEnviroment($database);
            throw new Exception("Exception while testing product creation : $e");
        }

        //static::cleanupTestEnviroment($database);
    }

    private static function test_apply_override_coupon_checkout_cart() : void
    {
        $database = static::createTestEnviroment("override_coupon_checkout_");

        try
        {
            $mockBuyerAccount = static::createMockBuyerAccount();

            $mockSellerAccount = static::createMockSellerAccount();
            $mockMedia = static::createMockMedia($mockSellerAccount);

            $store = static::createMockStore($mockSellerAccount);

            $mockProduct = static::createMockNonFungibleProduct($store, $mockMedia);

            $mockItem = static::createMockNonFungibleItem($mockMedia);

            $mockLoots = static::createNumberOfTestProductStockLoot(3, $mockProduct, $mockItem);

            $mockPriceItem = static::createMockCouponPriceItem($mockMedia);
            $mockCouponPrices = static::createMockCouponPrices($mockPriceItem, 1);
            if(count($mockCouponPrices) !== 1) throw new Exception("COMPONENT TEST FAILED : expected exaclty one prices object in mock coupon price list");

            $mockOverridePriceItem = static::createMockOverrideCouponPriceItem($mockMedia);
            $mockOverrideCouponPrices = static::createMockCouponPrices($mockOverridePriceItem, 1);
            if(count($mockOverrideCouponPrices) !== 1) throw new Exception("COMPONENT TEST FAILED : expected exaclty one prices object in mock coupon price list");


            $linkLootsResp = StoreController::linkLootsToProductAsStock($mockProduct, $mockLoots);
            if(!$linkLootsResp->success) throw new Exception("COMPONENT TEST FAILED : failed to link loots to product as stock : $linkLootsResp->message");

            $cartResp = StoreController::getCartForAccount($mockBuyerAccount, $store);
            if(!$cartResp->success) throw new Exception("COMPONENT TEST FAILED : failed to get cart for account $cartResp->message");
            $cart = $cartResp->data;

            $addToCartResp = StoreController::addProductToCart($mockProduct, $cart);
            if(!$addToCartResp->success) throw new Exception("COMPONENT TEST FAILED : failed to add product to cart : $addToCartResp->message");

            $cartResp = StoreController::getCartForAccount($mockBuyerAccount, $store);
            if(!$cartResp->success) throw new Exception("COMPONENT TEST FAILED : failed to get cart for account $cartResp->message");
            $cart = $cartResp->data;

            //CREATE COUPONS

                    //OVERRIDE COUPON
            $overrideCoupon = static::createMockInfiniteOverrideCoupon($mockProduct, $mockOverrideCouponPrices);
            if(count($overrideCoupon->price) !== count($mockOverrideCouponPrices)) throw new Exception("COMPONENT TEST FAILED : override coupon does not have all of the mockOverrideCouponPrices. Expected : ".count($mockOverrideCouponPrices)." | Actual : ".count($overrideCoupon->price));
            if(is_null($overrideCoupon)) throw new Exception("COMPONENT TEST FAILED : failed to return mock coupon after creation");

            if(!static::doesCouponHaveLinksToPrices($overrideCoupon, $mockOverrideCouponPrices)) throw new Exception("COMPONENT TEST FAILED : override coupon does not have expected links");
            
                    //COUPON
            $coupon = static::createMockInfiniteCoupon($mockProduct, $mockCouponPrices);
            if(count($coupon->price) !== count($mockCouponPrices)) throw new Exception("COMPONENT TEST FAILED : coupon does not have all of the mockCouponPrices. Expected : ".count($mockCouponPrices)." | Actual : ".count($coupon->price));
            if(is_null($coupon)) throw new Exception("COMPONENT TEST FAILED : failed to return mock coupon after creation");
            
            if(!static::doesCouponHaveLinksToPrices($coupon, $mockCouponPrices)) throw new Exception("COMPONENT TEST FAILED : coupon does not have expected links");

            //GIVE OVERRIDE COUPON PRICE LOOT TO ACCOUNT
            static::giveNumberOfCouponPriceLootsToAccount($mockBuyerAccount, $overrideCoupon, 3);

            //APPLY FIRST COUPON
            $applyCouponResp = StoreController::tryApplyCouponToCart($cart, $coupon);

            if(!$applyCouponResp->success) throw new Exception("COMPONENT TEST FAILED : failed to apply coupon to cart : $applyCouponResp->message");
            if(!$applyCouponResp->data) throw new Exception("COMPONENT TEST FAILED : failed to apply coupon to cart : $applyCouponResp->message");

            $cartResp = StoreController::getCartForAccount($mockBuyerAccount, $store);
            if(!$cartResp->success) throw new Exception("COMPONENT TEST FAILED : failed to get cart for account $cartResp->message");
            $cart = $cartResp->data;

            $doesCartHaveCouponPrices = static::doesCartItemHaveCouponPrices($cart->cartProducts[0], $coupon);
            if(!$doesCartHaveCouponPrices) throw new Exception("COPMONENT TEST FAILED : cart item prices do not match coupon prices");

            //APPLY OVERRIDE COUPON
            $applyCouponResp = StoreController::tryApplyCouponToCart($cart, $overrideCoupon);

            if(!$applyCouponResp->success) throw new Exception("COMPONENT TEST FAILED : failed to apply override coupon to cart : $applyCouponResp->message");
            if(!$applyCouponResp->data) throw new Exception("COMPONENT TEST FAILED : failed to apply override coupon to cart : $applyCouponResp->message");

            $cartResp = StoreController::getCartForAccount($mockBuyerAccount, $store);
            if(!$cartResp->success) throw new Exception("COMPONENT TEST FAILED : failed to get cart for account $cartResp->message");
            $cart = $cartResp->data;

            $doesCartHaveCouponPrices = static::doesCartItemHaveCouponPrices($cart->cartProducts[0], $overrideCoupon);
            if(!$doesCartHaveCouponPrices) throw new Exception("COPMONENT TEST FAILED : cart item prices do not match override coupon prices");

            //CHECKOUT CART
            $reserveResp = StoreController::checkoutCart($cart);
            if(!$reserveResp->success) throw new Exception("COMPONENT TEST FAILED : failed to reserve loots : $reserveResp->message");

            $mockProduct->price = $overrideCoupon->price;
            $doesAccountHaveLootForPrices = static::doesAccountHavePricesOfProduct($mockProduct, $mockSellerAccount);
            if(!$doesAccountHaveLootForPrices) throw new Exception("COMPONENT TEST FAILED : seller did not receive loots for product prices");
            
            $didBuyerRecieveProduct = static::didAccountReceiveProduct($mockBuyerAccount, $mockProduct);
            if(!$didBuyerRecieveProduct) throw new Exception("COMPONENT TEST FAILED : buyer did not receive product");

            $wasAccountCouponUseLogged = static::wasAccountCouponUseLogged($overrideCoupon, $cart);
            if(!$wasAccountCouponUseLogged) throw new Exception("COMPONENT TEST FAILED : account coupon use was not logged");
        }
        catch(Exception $e)
        {
            //static::cleanupTestEnviroment($database);
            throw new Exception("Exception while testing product creation : $e");
        }

        //static::cleanupTestEnviroment($database);
    }

    private static function test_apply_coupon_checkout_cart() : void
    {
        $database = static::createTestEnviroment("coupon_checkout_");

        try
        {
            $mockBuyerAccount = static::createMockBuyerAccount();

            $mockSellerAccount = static::createMockSellerAccount();
            $mockMedia = static::createMockMedia($mockSellerAccount);

            $store = static::createMockStore($mockSellerAccount);

            $mockProduct = static::createMockNonFungibleProduct($store, $mockMedia);

            $mockItem = static::createMockNonFungibleItem($mockMedia);

            $mockLoots = static::createNumberOfTestProductStockLoot(3, $mockProduct, $mockItem);

            $mockPriceItem = static::createMockCouponPriceItem($mockMedia);
            $mockCouponPrices = static::createMockCouponPrices($mockPriceItem, 1);
            if(count($mockCouponPrices) !== 1) throw new Exception("COMPONENT TEST FAILED : expected exaclty one prices object in mock coupon price list");

            $linkLootsResp = StoreController::linkLootsToProductAsStock($mockProduct, $mockLoots);
            if(!$linkLootsResp->success) throw new Exception("COMPONENT TEST FAILED : failed to link loots to product as stock : $linkLootsResp->message");

            

            $cartResp = StoreController::getCartForAccount($mockBuyerAccount, $store);
            if(!$cartResp->success) throw new Exception("COMPONENT TEST FAILED : failed to get cart for account $cartResp->message");
            $cart = $cartResp->data;

            $addToCartResp = StoreController::addProductToCart($mockProduct, $cart);
            if(!$addToCartResp->success) throw new Exception("COMPONENT TEST FAILED : failed to add product to cart : $addToCartResp->message");

            $cartResp = StoreController::getCartForAccount($mockBuyerAccount, $store);
            if(!$cartResp->success) throw new Exception("COMPONENT TEST FAILED : failed to get cart for account $cartResp->message");
            $cart = $cartResp->data;
            
            $coupon = static::createMockInfiniteCoupon($mockProduct, $mockCouponPrices);
            if(count($coupon->price) !== count($mockCouponPrices)) throw new Exception("COMPONENT TEST FAILED : coupon does not have all of the mockCouponPrices. Expected : ".count($mockCouponPrices)." | Actual : ".count($coupon->price));

            if(is_null($coupon)) throw new Exception("COMPONENT TEST FAILED : failed to return mock coupon after creation");

            if(!static::doesCouponHaveLinksToPrices($coupon, $mockCouponPrices)) throw new Exception("COMPONENT TEST FAILED : coupon does not have expected links");

            static::giveNumberOfCouponPriceLootsToAccount($mockBuyerAccount, $coupon, 3);

            $applyCouponResp = StoreController::tryApplyCouponToCart($cart, $coupon);

            if(!$applyCouponResp->success) throw new Exception("COMPONENT TEST FAILED : failed to apply coupon to cart : $applyCouponResp->message");
            if(!$applyCouponResp->data) throw new Exception("COMPONENT TEST FAILED : failed to apply coupon to cart : $applyCouponResp->message");

            $cartResp = StoreController::getCartForAccount($mockBuyerAccount, $store);
            if(!$cartResp->success) throw new Exception("COMPONENT TEST FAILED : failed to get cart for account $cartResp->message");
            $cart = $cartResp->data;

            $doesCartHaveCouponPrices = static::doesCartItemHaveCouponPrices($cart->cartProducts[0], $coupon);
            if(!$doesCartHaveCouponPrices) throw new Exception("COPMONENT TEST FAILED : cart item prices do not match coupon prices");

            $reserveResp = StoreController::checkoutCart($cart);
            if(!$reserveResp->success) throw new Exception("COMPONENT TEST FAILED : failed to reserve loots : $reserveResp->message");

            $mockProduct->price = $coupon->price;
            $doesAccountHaveLootForPrices = static::doesAccountHavePricesOfProduct($mockProduct, $mockSellerAccount);
            if(!$doesAccountHaveLootForPrices) throw new Exception("COMPONENT TEST FAILED : seller did not receive loots for product prices");
            
            $didBuyerRecieveProduct = static::didAccountReceiveProduct($mockBuyerAccount, $mockProduct);
            if(!$didBuyerRecieveProduct) throw new Exception("COMPONENT TEST FAILED : buyer did not receive product");

            $wasAccountCouponUseLogged = static::wasAccountCouponUseLogged($coupon, $cart);
            if(!$wasAccountCouponUseLogged) throw new Exception("COMPONENT TEST FAILED : account coupon use was not logged");
        }
        catch(Exception $e)
        {
            static::cleanupTestEnviroment($database);
            throw new Exception("Exception while testing product creation : $e");
        }

        static::cleanupTestEnviroment($database);
    }

    private static function test_apply_coupon() : void
    {
        $database = static::createTestEnviroment("coupon_");

        try
        {
            $mockBuyerAccount = static::createMockBuyerAccount();

            $mockSellerAccount = static::createMockSellerAccount();
            $mockMedia = static::createMockMedia($mockSellerAccount);

            $store = static::createMockStore($mockSellerAccount);

            $mockProduct = static::createMockNonFungibleProduct($store, $mockMedia);

            $mockItem = static::createMockNonFungibleItem($mockMedia);

            $mockLoots = static::createNumberOfTestProductStockLoot(3, $mockProduct, $mockItem);

            $mockPriceItem = static::createMockCouponPriceItem($mockMedia);
            $mockCouponPrices = static::createMockCouponPrices($mockPriceItem, 1);
            if(count($mockCouponPrices) !== 1) throw new Exception("COMPONENT TEST FAILED : expected exaclty one prices object in mock coupon price list");

            $linkLootsResp = StoreController::linkLootsToProductAsStock($mockProduct, $mockLoots);
            if(!$linkLootsResp->success) throw new Exception("COMPONENT TEST FAILED : failed to link loots to product as stock : $linkLootsResp->message");

            static::giveNumberOfRaffleTicketsToAccount($mockBuyerAccount, 3);

            $cartResp = StoreController::getCartForAccount($mockBuyerAccount, $store);
            if(!$cartResp->success) throw new Exception("COMPONENT TEST FAILED : failed to get cart for account $cartResp->message");
            $cart = $cartResp->data;

            $addToCartResp = StoreController::addProductToCart($mockProduct, $cart);
            if(!$addToCartResp->success) throw new Exception("COMPONENT TEST FAILED : failed to add product to cart : $addToCartResp->message");

            $cartResp = StoreController::getCartForAccount($mockBuyerAccount, $store);
            if(!$cartResp->success) throw new Exception("COMPONENTE TEST FAILED : failed to get cart for account $cartResp->message");
            $cart = $cartResp->data;

            
            $coupon = static::createMockInfiniteCoupon($mockProduct, $mockCouponPrices);

            if(is_null($coupon)) throw new Exception("COMPONENT TEST FAILED : failed to return mock coupon after creation");

            if(!static::doesCouponHaveLinksToPrices($coupon, $mockCouponPrices)) throw new Exception("COMPONENT TEST FAILED : coupon does not have expected links");

            $applyCouponResp = StoreController::tryApplyCouponToCart($cart, $coupon);

            if(!$applyCouponResp->success) throw new Exception("COMPONENT TEST FAILED : failed to apply coupon to cart : $applyCouponResp->message");


        }
        catch(Exception $e)
        {
            static::cleanupTestEnviroment($database);
            throw new Exception("Exception while testing product creation : $e");
        }

        static::cleanupTestEnviroment($database);
    }

    private static function manualtest_getStoreById() : void
    {
        $database = static::createTestEnviroment("get_store_by_id_");

        try
        {
            $mockSellerAccount = static::createMockSellerAccount();
            $mockMedia = static::createMockMedia($mockSellerAccount);

            $store = static::createMockStore($mockSellerAccount);

            $mockProduct = static::createMockNonFungibleProduct($store, $mockMedia);
            if(is_null($mockProduct)) throw new Exception("COMPONENT TEST FAIELD : mock product returend as null");

            $getStoreResp = StoreController::getStoreById($store); 

            //throw new Exception(json_encode($getStoreResp->data));
        }
        catch(Exception $e)
        {
            static::cleanupTestEnviroment($database);
            throw new Exception("Exception while testing getStoreById : $e");
        }

        static::cleanupTestEnviroment($database);
    }

    private static function temp_populate_DB() : void
    {
        try
        {
            $mockBuyerAccount = static::returnLastAccountInTable();

            $mockSellerAccount = static::returnFirstAccountInTable();
            $mockMedia = static::returnFirstMedia();

            $storeResp = StoreController::getStoreByLocator("testLocator");

            $mockProduct = static::createMockNonFungibleProduct($storeResp->data, $mockMedia);

            $mockItem = static::createMockNonFungibleItem($mockMedia);

            $mockLoots = static::createNumberOfTestProductStockLoot(3, $mockProduct, $mockItem);

            $linkLootsResp = StoreController::linkLootsToProductAsStock($mockProduct, $mockLoots);
            if(!$linkLootsResp->success) throw new Exception("COMPONENT TEST FAILED : failed to link loots to product as stock : $linkLootsResp->message");

            static::giveNumberOfRaffleTicketsToAccount($mockBuyerAccount, 3);

            $cartResp = StoreController::getCartForAccount($mockBuyerAccount, $storeResp->data);
            if(!$cartResp->success) throw new Exception("COMPONENT TEST FAILED : failed to get cart for account $cartResp->message");
            $cart = $cartResp->data;

            $addToCartResp = StoreController::addProductToCart($mockProduct, $cart);
            if(!$addToCartResp->success) throw new Exception("COMPONENT TEST FAILED : failed to add product to cart : $addToCartResp->message");

            $cartResp = StoreController::getCartForAccount($mockBuyerAccount, $storeResp->data);
            if(!$cartResp->success) throw new Exception("COMPONENTE TEST FAILED : failed to get cart for account $cartResp->message");
            $cart = $cartResp->data;

            $reserveResp = StoreController::checkoutCart($cart);
            if(!$reserveResp->success) throw new Exception("COMPONENT TEST FAILED : failed to reserve loots : $reserveResp->message");

            $doesAccountHaveLootForPrices = static::doesAccountHavePricesOfProduct($mockProduct, $mockSellerAccount);
            if(!$doesAccountHaveLootForPrices) throw new Exception("COMPONENT TEST FAILED : seller did not reseve loots for product prices");
            
            $didBuyerRecieveProduct = static::didAccountReceiveProduct($mockBuyerAccount, $mockProduct);
            if(!$didBuyerRecieveProduct) throw new Exception("COMPONENT TEST FAILED : buyer did not receive product");
        }
        catch(Exception $e)
        {
            //static::cleanupTestEnviroment($database);
            throw new Exception("Exception while testing product creation : $e");
        }
    }   

    private static function test_Successful_TransactItems() : void
    {
        $database = static::createTestEnviroment("transact_cart_success_");

        try
        {
            $mockBuyerAccount = static::createMockBuyerAccount();

            $mockSellerAccount = static::createMockSellerAccount();
            $mockMedia = static::createMockMedia($mockSellerAccount);

            $store = static::createMockStore($mockSellerAccount);

            $mockProduct = static::createMockNonFungibleProduct($store, $mockMedia);

            $mockItem = static::createMockNonFungibleItem($mockMedia);

            $mockLoots = static::createNumberOfTestProductStockLoot(3, $mockProduct, $mockItem);

            $linkLootsResp = StoreController::linkLootsToProductAsStock($mockProduct, $mockLoots);
            if(!$linkLootsResp->success) throw new Exception("COMPONENT TEST FAILED : failed to link loots to product as stock : $linkLootsResp->message");

            static::giveNumberOfRaffleTicketsToAccount($mockBuyerAccount, 3);

            $cartResp = StoreController::getCartForAccount($mockBuyerAccount, $store);
            if(!$cartResp->success) throw new Exception("COMPONENT TEST FAILED : failed to get cart for account $cartResp->message");
            $cart = $cartResp->data;

            $addToCartResp = StoreController::addProductToCart($mockProduct, $cart);
            if(!$addToCartResp->success) throw new Exception("COMPONENT TEST FAILED : failed to add product to cart : $addToCartResp->message");

            $cartResp = StoreController::getCartForAccount($mockBuyerAccount, $store);
            if(!$cartResp->success) throw new Exception("COMPONENTE TEST FAILED : failed to get cart for account $cartResp->message");
            $cart = $cartResp->data;

            $reserveResp = StoreController::checkoutCart($cart);
            if(!$reserveResp->success) throw new Exception("COMPONENT TEST FAILED : failed to reserve loots : $reserveResp->message");

            $doesAccountHaveLootForPrices = static::doesAccountHavePricesOfProduct($mockProduct, $mockSellerAccount);
            if(!$doesAccountHaveLootForPrices) throw new Exception("COMPONENT TEST FAILED : seller did not reseve loots for product prices");
            
            $didBuyerRecieveProduct = static::didAccountReceiveProduct($mockBuyerAccount, $mockProduct);
            if(!$didBuyerRecieveProduct) throw new Exception("COMPONENT TEST FAILED : buyer did not receive product");
        }
        catch(Exception $e)
        {
            //static::cleanupTestEnviroment($database);
            throw new Exception("Exception while testing product creation : $e");
        }

        //static::cleanupTestEnviroment($database);
    }

    private static function returnFirstAccountInTable() : vAccount
    {
        $sql = "select * from v_account_info ORDER BY Id ASC LIMIT 1;";

        $result = Database::executeSqlQuery($sql, []);

        if(!$result) throw new Exception("result returned while returning first account");

        $account = AccountController::row_to_vAccount($result->fetch_assoc());

        return $account;
    }

    private static function returnLastAccountInTable() : vAccount
    {
        $sql = "select * from v_account_info ORDER BY Id DESC LIMIT 1;";

        $result = Database::executeSqlQuery($sql, []);

        if(!$result) throw new Exception("result returned while returning last account");

        $account = AccountController::row_to_vAccount($result->fetch_assoc());

        return $account;
    }

    private static function testAddStore() : void
    {
        $database = static::createTestEnviroment("store_");

        try
        {
            $mockAccount = static::createMockSellerAccount();

            $store = static::returnMockStoreObject($mockAccount);
            StoreController::addStore($store);

            $getStoreResp = StoreController::getStoreById($store);
            if(!$getStoreResp->success) throw new Exception("COMPONENT TEST FAILED : Failed to get store after insertion while testing add store : $getStoreResp->message");
            if($getStoreResp->data === null) throw new Exception("COMPONENT TEST FAILED : MockStore not returned after insertion while testing add store : $getStoreResp->data");
            if(!($getStoreResp->data instanceof vStore)) throw new Exception("COMPONENT TEST FAILED : Returned object from getting store after insertion was not a store : $getStoreResp->message");

            if(!($store->ctime === $getStoreResp->data->ctime 
            && $store->crand === $getStoreResp->data->crand)) 
                throw new Exception("COMPONENT TEST FAILED : Returned store after insertion was not the same store inserted : $getStoreResp->message");
        }
        catch(Exception $e)
        {
            static::cleanupTestEnviroment($database);
            throw new Exception("Exception while testing adding a store : $e");
        }

        static::cleanupTestEnviroment($database);
    }

    private static function testProductCreation() : void
    {
        $database = static::createTestEnviroment("product_");

        try
        {
            $mockAccount = static::createMockSellerAccount();

            $store = static::createMockStore($mockAccount);

            $raffleTicketPrice = static::returnMockPriceObject("raffleticket");

            $foundPriceResp = StoreController::InsertAndSelectPrices([$raffleTicketPrice]);

            if(!$foundPriceResp->success) throw new Exception("COMPONENT TEST FAILED : getting prices failed : $foundPriceResp->message");
            if(count($foundPriceResp->data) !== 1) throw new Exception("COMPONENT TEST FAILED : expected a single price to be returned : $foundPriceResp->message");

            $mockMedia = static::createMockMedia($mockAccount);

            $product = static::returnMockProductObject($store, $foundPriceResp->data, $mockMedia);
            $insertProductResp = StoreController::upsertProduct($product);

            if(!$insertProductResp->success) throw new Exception("COMPONENT TEST FAILED : inserting product failed : $insertProductResp->message");

            $getProductResp = StoreController::getProductById($product);
            if(!$getProductResp->success) throw new Exception("COMPONENT TEST FAILED : failed to get product after insertion : $getProductResp->message");
            if(!($getProductResp->data->getVRecordId()->equals($product->getVRecordId()))) throw new Exception("COMPONENT TEST FAILED : product retreived was not the product inserted : $getProductResp->message");

            $productRemovedResp = StoreController::removeProductById($product);
            if(!$productRemovedResp->success) throw new Exception("COMPONENT TEST FAILED : failed to removed product : $productRemovedResp->message");
            
            $getProductResp = StoreController::getProductById($product);
            if(!$getProductResp->success) throw new Exception("COMPONENT TEST FAILED : getting of product failed after removal : $getProductResp->message");
            if(!$getProductResp->data->equals($product)) throw new Exception("COMPONENT TEST FAILED : product retreived after removal is not the product removed : $getProductResp->message");
            if(!$getProductResp->data->removed === true) throw new Exception("COMPONENT TEST FAILED : product not set as removed after removal : ". json_encode($getProductResp->data));

        }
        catch(Exception $e)
        {
            static::cleanupTestEnviroment($database);
            throw new Exception("Exception while testing cart creation : $e");
        }

        static::cleanupTestEnviroment($database);
    }

    public static function testCartCreation() : void
    {
        $database = static::createTestEnviroment("cart_");

        try
        {
            $mockSellerAccount = static::createMockSellerAccount();

            $store = static::createMockStore($mockSellerAccount);

            $mockBuyerAccount = static::createMockBuyerAccount();

            $cartResp = StoreController::getCartForAccount($mockBuyerAccount, $store);

            if(!$cartResp->success) throw new Exception("COMPONENT TEST FAILED : failed to get cart for account : $cartResp->message");
            if($cartResp->data->account->crand !== $mockBuyerAccount->crand) throw new Exception("COMPONENT TEST FAILED : retrieved account did not match account cart was created with : ".json_encode($cartResp->data)." : ".json_encode($mockBuyerAccount));
        }
        catch(Exception $e)
        {
            static::cleanupTestEnviroment($database);
            throw new Exception("Exception while testing product creation : $e");
        }

        static::cleanupTestEnviroment($database);
    }

    private static function testCartLootChecks() : void
    {
        $database = static::createTestEnviroment("cart_loot_checks_");

        try
        {
            $mockBuyerAccount = static::createMockBuyerAccount();

            $mockSellerAccount = static::createMockSellerAccount();
            $mockMedia = static::createMockMedia($mockSellerAccount);

            $store = static::createMockStore($mockSellerAccount);

            $mockProduct = static::createMockNonFungibleProduct($store, $mockMedia);

            $mockItem = static::createMockNonFungibleItem($mockMedia);

            $mockLoots = static::createNumberOfTestProductStockLoot(3, $mockProduct, $mockItem);

            $cartResp = StoreController::getCartForAccount($mockBuyerAccount, $store);
            if(!$cartResp->success) throw new Exception("COMPONENT TEST FAILED : failed to get cart");

            $linkLootsResp = StoreController::linkLootsToProductAsStock($mockProduct, $mockLoots);
            if(!$linkLootsResp->success) throw new Exception("COMPONENT TEST FAILED : failed to link loots to product as stock : $linkLootsResp->message");

            $addProductToCart = StoreController::addProductToCart($mockProduct, $cartResp->data);
            if(!$addProductToCart->success) throw new Exception("COMPONENT TEST FAILED : failed to add product to cart : $addProductToCart->message");

            $cartResp = StoreController::getCartForAccount($mockBuyerAccount, $store);
            if(!$cartResp->success) throw new Exception("COMPONENT TEST FAILED : failed to get cart");

            //Test areCartItemsAvailable
            $unavailableProducts = [];
            $productInStockResp = StoreController::areSufficentCartProductsAvailable($cartResp->data, $unavailableProducts);
            if(!$productInStockResp->success) throw new Exception("COMPONENT TEST FAILED : failed to check cart item availability : $productInStockResp->message");
            if($productInStockResp->data !== true) throw new Exception("COMPONENT TEST FAILED : product returned to not be available when it is expected to be : $productInStockResp->message");

            static::wipeLootStockForProduct($mockProduct);

            $unavailableProducts = [];
            $productInStockResp = StoreController::areSufficentCartProductsAvailable($cartResp->data, $unavailableProducts);
            if(!$productInStockResp->success) throw new Exception("COMPONENT TEST FAILED : failed to check cart item availability : $productInStockResp->message");
            if($productInStockResp->data !== false) throw new Exception("COMPONENT TEST FAILED : product returned to be available when it is expected not to be : $productInStockResp->message");

            //Test canAccountAffordItemPricesInCart
            $linkLootsResp = StoreController::linkLootsToProductAsStock($mockProduct, $mockLoots);
            if(!$linkLootsResp->success) throw new Exception("COMPONENT TEST FAILED : failed to link loots to product as stock : $linkLootsResp->message");

            $canAccountAffordItemPricesResp = StoreController::canAccountAffordItemPricesInCart($cartResp->data);
            if(!$canAccountAffordItemPricesResp->success) throw new Exception("COPMONENT TEST FAILED : failed to check if account could afford cart products");
            if($canAccountAffordItemPricesResp->data !== false) throw new Exception("COMPONET TEST FAILED : account returned to be able to afford cart products when it was expected not to : $canAccountAffordItemPricesResp->message");

            static::giveNumberOfRaffleTicketsToAccount($mockBuyerAccount, 3);

            $canAccountAffordItemPricesResp = StoreController::canAccountAffordItemPricesInCart($cartResp->data);
            if(!$canAccountAffordItemPricesResp->success) throw new Exception("COPMONENT TEST FAILED : failed to check if account could afford cart products");
            if($canAccountAffordItemPricesResp->data !== true) throw new Exception("COMPONET TEST FAILED : account returned to not be able to afford cart products when it was expected to : $canAccountAffordItemPricesResp->message");

            $removeProductFromCart = StoreController::removeProductFromCart($cartResp->data->cartProducts[0]);
            if(!$removeProductFromCart->success) throw new Exception("COMPONENT TEST FAILED : failed to remove product from cart");
            if(!static::isCartProductLinkRemoved($cartResp->data->cartProducts[0])) throw new Exception("product was not marked as removed from cart : ".json_encode($cartResp->data->cartProducts[0]));
            
            //If product is removed, it should be marked as non-available

            $removeProdcutFromStoreResp = StoreController::removeProductById($mockProduct);
            if(!$removeProdcutFromStoreResp->success) throw new Exception("COMPONENT TEST FAILED : failed to remove product from store by id : $removeProdcutFromStoreResp->message");

            $unavailableProducts = [];
            $productInStockResp = StoreController::areSufficentCartProductsAvailable($cartResp->data, $unavailableProducts);
            if(!$productInStockResp->success) throw new Exception("COMPONENT TEST FAILED : failed to check cart item availability : $productInStockResp->message");
            if(count($unavailableProducts) !== 1) throw new Exception("COPMONENT TEST FAILED : expected a single product to be returend as unavailable : ".json_encode($unavailableProducts));
            if($productInStockResp->data !== false) throw new Exception("COMPONENT TEST FAILED : product returned to be available when it is expected not to be : $productInStockResp->message");
            
            
        }
        catch(Exception $e)
        {
            static::cleanupTestEnviroment($database);
            throw new Exception("Exception while testing cart loot checks : $e");
        }

        static::cleanupTestEnviroment($database);
    }

    private static function isCartProductLinkRemoved(vCartItem $cartProduct) : bool
    {
        $sql = "SELECT removed FROM v_cart_product_link WHERE ctime = '$cartProduct->ctime' AND crand = $cartProduct->crand LIMIT 1;";
        $result = Database::executeSqlQuery($sql, []);

        if($result === false) throw new exception("result returned false");

        $row = $result->fetch_assoc();

        return boolval($row["removed"]);
    }


    private static function doesCartItemHaveCouponPrice(vCartItem $cartItem, vCoupon $coupon) : bool
    {
        $sql = "SELECT * FROM v_cart_item WHERE cart_product_link_ctime = '$cartItem->ctime' AND cart_product_link_crand = $cartItem->crand AND removed = 0 AND checked_out = 0";

        $result = Database::executeSqlQuery($sql);

        if(!$result) throw new Exception("result returned false while checking if cart item has coupon prices while getting cartItem info");

        $cartItems = StoreController::cartItemResultToViews($result);

        $cartItem = $cartItems[0];

        for($i = 0; $i < count($cartItem->price); $i++)
        {
            $price = $cartItem->price[$i];

            $priceFound = false;
            foreach($coupon->price as $couponPriceComponent)
            {
                if($price->ctime === $couponPriceComponent->ctime && $price->crand === $couponPriceComponent->crand)
                {
                    $priceFound = true;
                    break;
                }
            }

            if(!$priceFound) throw new Exception("COMPONENT TEST FAILED : Cart item contains price not found in coupon : ".json_encode($price));
        }

        return true;
    }

    private static function wasAccountCouponUseLogged(vCoupon $coupon, vCart $cart) : bool
    {
        $account = $cart->account;
        $sql = "SELECT * FROM coupon_account_use WHERE 
        ref_coupon_ctime = '$coupon->ctime' AND 
        ref_coupon_crand = $coupon->crand AND 
        ref_account_ctime = '$account->ctime' AND
        ref_account_crand = $account->crand";

        $result = Database::executeSqlQuery($sql);

        if(!$result) throw new Exception("COMPONENT TEST FAILED : result returned false while checking if account coupon use was logged");

        if($result->num_rows > 1) throw new Exception("COMPONENT TEST FAILED : more than 1 row returned for a coupon account pair");

        return $result->num_rows === 1;
    }

    private static function isProductRemoved(vProduct $product) : bool
    {
        $sql = "SELECT removed FROM v_product WHERE ctime = $product->ctime AND crand = $product->crand LIMIT 1;";
        $result = Database::executeSqlQuery($sql, []);

        if(!$result === false) throw new exception("result returned false");

        $row = $result->fetch_assoc();

        return boolval($row["removed"]);
    }

    private static function test_NotEnoughtLootToBuy_ReserveNonFungibleProduct() : void
    {
        $database = static::createTestEnviroment("reserve_cart_failed_not_enough_loot_");

        try
        {
            $mockBuyerAccount = static::createMockBuyerAccount();

            $mockSellerAccount = static::createMockSellerAccount();
            $mockMedia = static::createMockMedia($mockSellerAccount);

            $store = static::createMockStore($mockSellerAccount);

            $mockProduct = static::createMockNonFungibleProduct($store, $mockMedia);

            $mockItem = static::createMockNonFungibleItem($mockMedia);

            $mockLoots = static::createNumberOfTestProductStockLoot(3, $mockProduct, $mockItem);

            $linkLootsResp = StoreController::linkLootsToProductAsStock($mockProduct, $mockLoots);
            if(!$linkLootsResp->success) throw new Exception("COMPONENT TEST FAILED : failed to link loots to product as stock : $linkLootsResp->message");

            $cartResp = StoreController::getCartForAccount($mockBuyerAccount, $store);
            if(!$cartResp->success) throw new Exception("COMPONENT TEST FAILED : failed to get cart for account $cartResp->message");
            $cart = $cartResp->data;

            $addToCartResp = StoreController::addProductToCart($mockProduct, $cart);
            if(!$addToCartResp->success) throw new Exception("COMPONENT TEST FAILED : failed to add product to cart : $addToCartResp->message");

            $cartResp = StoreController::getCartForAccount($mockBuyerAccount, $store);
            if(!$cartResp->success) throw new Exception("COMPONENTE TEST FAILED : failed to get cart for account $cartResp->message");
            $cart = $cartResp->data;

            $reserveResp = StoreController::checkoutCart($cart);
            if($reserveResp->success) throw new Exception("COMPONENT TEST FAILED : expected to fail to reserve loots : $reserveResp->message");

            

        }
        catch(Exception $e)
        {
            static::cleanupTestEnviroment($database);
            throw new Exception("Exception while testing product creation : $e");
        }

        static::cleanupTestEnviroment($database);
    }

    private static function test_Successful_ReserveNonFungibleProduct() : void
    {
        $database = static::createTestEnviroment("reserve_cart_success_");

        try
        {
            $mockBuyerAccount = static::createMockBuyerAccount();

            $mockSellerAccount = static::createMockSellerAccount();
            $mockMedia = static::createMockMedia($mockSellerAccount);

            $store = static::createMockStore($mockSellerAccount);

            $mockProduct = static::createMockNonFungibleProduct($store, $mockMedia);

            $mockItem = static::createMockNonFungibleItem($mockMedia);

            $mockLoots = static::createNumberOfTestProductStockLoot(3, $mockProduct, $mockItem);

            $linkLootsResp = StoreController::linkLootsToProductAsStock($mockProduct, $mockLoots);
            if(!$linkLootsResp->success) throw new Exception("COMPONENT TEST FAILED : failed to link loots to product as stock : $linkLootsResp->message");

            static::giveNumberOfRaffleTicketsToAccount($mockBuyerAccount, 3);

            $cartResp = StoreController::getCartForAccount($mockBuyerAccount, $store);
            if(!$cartResp->success) throw new Exception("COMPONENT TEST FAILED : failed to get cart for account $cartResp->message");
            $cart = $cartResp->data;

            $addToCartResp = StoreController::addProductToCart($mockProduct, $cart);
            if(!$addToCartResp->success) throw new Exception("COMPONENT TEST FAILED : failed to add product to cart : $addToCartResp->message");

            $cartResp = StoreController::getCartForAccount($mockBuyerAccount, $store);
            if(!$cartResp->success) throw new Exception("COMPONENT TEST FAILED : failed to get cart for account $cartResp->message");
            $cart = $cartResp->data;

            $reserveResp = StoreController::checkoutCart($cart, null, false); //no stripe transaction and don't go on to try to transact loot
            if(!$reserveResp->success) throw new Exception("COMPONENT TEST FAILED : failed to reserve loots : $reserveResp->message");

        }
        catch(Exception $e)
        {
            static::cleanupTestEnviroment($database);
            throw new Exception("Exception while testing product creation : $e");
        }

        static::cleanupTestEnviroment($database);
    }

    private static function testLinkingFungibleProductsAndLoots() : void
    {
        $database = static::createTestEnviroment("link_fungible_loots_to_products_");

        try
        {
            $mockSellerAccount = static::createMockSellerAccount();
            $mockMedia = static::createMockMedia($mockSellerAccount);

            $store = static::createMockStore($mockSellerAccount);

            $mockProduct = static::createMockFungibleProduct($store, $mockMedia, 4);
            
            $mockItem = static::createMockFungibleItem($mockMedia);

            $mockLoots = static::createNumberOfTestProductStockLoot(3, $mockProduct, $mockItem);

            
            $linkLootsResp = StoreController::linkLootsToProductAsStock($mockProduct, $mockLoots, 3);
            if(!$linkLootsResp->success) throw new Exception("COMPONENT TEST FAILED : failed to link loots to product as stock : $linkLootsResp->message");
            
            $lootLinks = static::getProductLootLinks($mockProduct, $mockLoots);
            if(count($lootLinks) !== 1) throw new Exception("COMPONENT TEST FAILED : expected 1 loot links to product to be created ".count($lootLinks)." links found");

            foreach($mockLoots as $loot)
            {
                $found = false;

                foreach($lootLinks as $link)
                {
                    if($link->productId->ctime === $mockProduct->ctime 
                    && $link->productId->crand === $mockProduct->crand
                    && $link->lootId->crand === $loot->crand)
                    {
                        $found = true;
                        break;
                    }
                }  

                if($found !== true) throw new Exception("COMPONENT TEST FAILED : expected loot link not found : ". json_encode(["productId"=>$mockProduct, "lootId"=>$loot]));
            }

            if($lootLinks[0]->quantity !== 3) throw new Exception("COMPONENT TEST FAILED : expeceted linked amount did not match actual | Expected : 3 | Actual : ".$lootLinks[0]->quantity);
            
        }
        catch(Exception $e)
        {
            static::cleanupTestEnviroment($database);
            throw new Exception("Exception while testing product creation : $e");
        }

        static::cleanupTestEnviroment($database);
    }

    private static function testLinkingProductsAndLoots() : void
    {
        $database = static::createTestEnviroment("link_loots_to_products_");

        try
        {
            $mockSellerAccount = static::createMockSellerAccount();
            $mockMedia = static::createMockMedia($mockSellerAccount);

            $store = static::createMockStore($mockSellerAccount);

            $mockProduct = static::createMockNonFungibleProduct($store, $mockMedia);

            
            $mockItem = static::createMockNonFungibleItem($mockMedia);

            $mockLoots = static::createNumberOfTestProductStockLoot(3, $mockProduct, $mockItem);

            
            $linkLootsResp = StoreController::linkLootsToProductAsStock($mockProduct, $mockLoots);
            if(!$linkLootsResp->success) throw new Exception("COMPONENT TEST FAILED : failed to link loots to product as stock : $linkLootsResp->message");
            
            $lootLinks = static::getProductLootLinks($mockProduct, $mockLoots);
            if(count($lootLinks) !== 3) throw new Exception("COMPONENT TEST FAILED : expected 3 loot links to product to be created ".count($lootLinks)." links found");

            foreach($mockLoots as $loot)
            {
                $found = false;

                foreach($lootLinks as $link)
                {
                    if($link->productId->ctime === $mockProduct->ctime 
                    && $link->productId->crand === $mockProduct->crand
                    && $link->lootId->crand === $loot->crand)
                    {
                        $found = true;
                        break;
                    }
                }  

                if($found !== true) throw new Exception("COMPONENT TEST FAILED : expected loot link not found : ". json_encode(["productId"=>$mockProduct, "lootId"=>$loot]));
            }
            
        }
        catch(Exception $e)
        {
            static::cleanupTestEnviroment($database);
            throw new Exception("Exception while testing product creation : $e");
        }

        static::cleanupTestEnviroment($database);
    }

    private static function testCartItemControl() : void
    {
        $database = static::createTestEnviroment("add_product_to_cart_");

        try
        {
            $mockSellerAccount = static::createMockSellerAccount();

            $store = static::createMockStore($mockSellerAccount);

            $mockBuyerAccount = static::createMockBuyerAccount();
            $mockProductLootItemMedia = static::createMockMedia($mockBuyerAccount);

            $cartResp = StoreController::getCartForAccount($mockBuyerAccount, $store);

            if(!$cartResp->success) throw new Exception("COMPONENT TEST FAILED : failed to get cart for account : $cartResp->message");
            if($cartResp->data->account->crand !== $mockBuyerAccount->crand) throw new Exception("COMPONENT TEST FAILED : retrieved account did not match account cart was created with : ".json_encode($cartResp->data)." : ".json_encode($mockBuyerAccount));

            $mockProduct = static::createMockNonFungibleProduct($store, $mockProductLootItemMedia);

           
            $mockProductLootItem = static::createMockNonFungibleItem($mockProductLootItemMedia);
            $mockLoots = static::createNumberOfTestProductStockLoot(1, $mockProduct, $mockProductLootItem);

            static::createProductLootLinks($mockProduct, $mockLoots);

            $addProductToCartResp = StoreController::addProductToCart($mockProduct, $cartResp->data);

            if(!$addProductToCartResp->success) throw new Exception("COMPONENT TEST FAILED : failed to add product to cart : $addProductToCartResp->message");
            if($addProductToCartResp->data === false) throw new Exception("COMPONENT TEST FAILED : product did not have stock to add to cart : $addProductToCartResp->message");

            $cartResp = StoreController::getCartForAccount($mockBuyerAccount, $store);
            if(!$cartResp->success) throw new Exception("COMPONENT TEST FAILED : failed to get cart for account : $cartResp->message");
            if($cartResp->data->account->crand !== $mockBuyerAccount->crand) throw new Exception("COMPONENT TEST FAILED : retrieved account did not match account cart was created with : ".json_encode($cartResp->data)." : ".json_encode($mockBuyerAccount));

            $cartWithMockItems = $cartResp->data;

            if(count($cartWithMockItems->cartProducts) !== 1) throw new Exception("COMPONENT TEST FAILED : cart returned without only a single item : ".json_encode($cartWithMockItems));
            
            /** @var vCartItem $cartItem */
            $cartItem = $cartWithMockItems->cartProducts[0];
            if($cartItem->removed !== false) throw new Exception("COMPONENT TEST FAILED : cart item marked as removed after insertion into cart");
            if($cartItem->checkedOut !== false) throw new Exception("COMPONENT TEST FAILED : cart item marked as checked out after insertion into cart");
            if(!$cartItem->product->equals($mockProduct)) throw new Exception("COMPONENT TEST FAILED : cart item did not match product that was inserted into cart as cart itme : ". json_encode($cartItem)." | ". json_encode($mockProduct));
            if(!$cartItem->cart->equals($cartWithMockItems)) throw new Exception("COMPONENT TEST FAILED : cart which cart itme belongs to different cart than what the product was inserted into". json_encode($cartWithMockItems));

            $removedItemFromCartResp = StoreController::removeProductFromCart($cartItem);

            if(!$removedItemFromCartResp->success) throw new Exception("COMPONENT TEST FAILED : failed to mark cart item as removed : $removedItemFromCartResp->message");
            
            $cartResp = StoreController::getCartForAccount($mockBuyerAccount, $store);
            if(!$cartResp->success) throw new Exception("COMPONENT TEST FAILED : failed to get cart for account : $cartResp->message");
            if($cartResp->data->account->crand !== $mockBuyerAccount->crand) throw new Exception("COMPONENT TEST FAILED : retrieved account did not match account cart was created with : ".json_encode($cartResp->data)." : ".json_encode($mockBuyerAccount));

            $cartWithMockItems = $cartResp->data;
            if(count($cartWithMockItems->cartProducts) !== 0) throw new Exception("COMPONENT TEST FAILED : cart still has items in it after removal : ".json_encode($cartResp->data));
        }
        catch(Exception $e)
        {
            //static::cleanupTestEnviroment($database);
            throw new Exception("Exception while testing product creation : $e");
        }

        static::cleanupTestEnviroment($database);
    }

    private static function storeUsername() : string
    {
        return "store";
    }

    private static function buyerAccountUsername() : string
    {
        return "JoeDoe";
    }

    public static function createTestEnviroment(string $uniqueName = '') : string
    {
        
        $randomId = new RecordId;
        $database = "TEST_".$uniqueName."DATABASE_$randomId->crand";

        $query = "CREATE DATABASE $database";
        $result = Database::executeSqlQuery($query, []); 
        if(!$result) throw new Exception("Result returned false while creating test enviroment");

        Database::changeDatabase($database);


        //Account
        static::createAccountTable();
        static::createAccountView();

        //$buyer = static::createMockBuyerAccount();

        //Media
        static::createMediaTable();
        static::createMediaView();
        //static::insertRaffleTicketMedia();
        //static::insertTableTopSimluatorMedia();

        //Item
        static::createItemTable();
        static::insertRaffleTicketItem();
        static::insertTableTopSimluatorItem();
        static::createItemView();

        //Store
        static::createStoreTable();
        static::createStoreView();

        //Loot Table
        static::createLootTable();
        
        //static::insertRaffleTicketsForTestAccount(5, $buyer);

        //Price
        static::createPriceTable();
        static::createPriceView();

        //Cart
        static::createCartTable();
        static::createCartView();

        //Product Table
        static::createProductTable();

        //Product Loot Link
        static::createProductLootLinkTable();
        static::createProductLootLinkView();

        //Loot Reservation
        static::createLootReservationTable();
        static::createLootReservationView();
        
        //Reservation
        static::createReservationTable();
        static::createReservationView();
        static::createReservationTotalsView();

        //Product View
        static::createProductView();

        //Loot View
        static::createLootView();

        //LootReservationTotal
        static::createLootReservationTotalView();

        //ProductPriceLink
        static::createProductPriceLinkTable();
        static::createProductPriceLinkView();

        //Coupons
        static::createCouponTable();
        static::createCouponView();

        //CartProductLink Table
        static::createProductCartLinkTable();

        //Coupon Cart Product Link
        static::createCouponCartProductLinkTable();

        //CartProductLink View
        static::createProductCartLinkView();

        //CartProductPriceLink
        static::createCartProductPriceLinkTable();
        static::createCartProductPriceLinkView();

        //CartItem
        static::createCartItemView();

        //Trade
        static::createTradeTable();
        //static::createTradeView();

        //Coupon Account Use
        static::createCouponAccountUseTable();
        static::createCouponAccountUseView();

        //Coupon Price Link
        static::createCouponPriceLinkTable();   
        
        
        return $database;
    }

    public static function cleanupTestEnviroment(string $database) : void
    {
        $sql = "DROP DATABASE $database";

        Database::changeDatabase($database);
        Database::executeSqlQuery($sql, []);
    }

    private static function wipeLootStockForProduct(vProduct $product) : void
    {
        $sql = "UPDATE product_loot_link pll SET removed = 1
        WHERE pll.ref_product_ctime = '$product->ctime' AND pll.ref_product_crand = '$product->crand'";

        $result = Database::executeSqlQuery($sql, []);

        if(!$result) throw new Exception("FAILED TO WIPE STOCK FOR PRODUCT : result returned false");
    }

    //TEST HELPER FUNCTIONS

    private static function doesCouponHaveLinksToPrices(vRecordId $coupon, array $prices) : bool
    {
        $priceValue = "";

        for($i = 0; $i < count($prices); $i++)
        {
            $price = $prices[$i];

            if($i !== 0) $priceValue .= " UNION ALL ";

            $priceValue .= "(SELECT '$price->ctime' AS priceCtime, $price->crand as priceCrand)";

        }

        $sql = "SELECT cpl.* FROM coupon_price_link cpl JOIN ($priceValue) pv WHERE 
        cpl.ref_coupon_ctime = '$coupon->ctime' AND
        cpl.ref_coupon_crand = $coupon->crand AND
        pv.priceCtime = cpl.ref_price_ctime AND
        pv.priceCrand = cpl.ref_price_crand";


        $result = Database::executeSqlQuery($sql);

        return $result->num_rows === count($prices);
    }

    private static function doesAccountHavePricesOfProduct(vProduct $product, vAccount $account) : bool
    {
        $prices = $product->prices;

        $accountLootAmount = static::returnAmountsOfPricesLootInAccountInventory($prices, $account);

        if(count($prices) > 0 && count($accountLootAmount) == 0) return false;

        for($priceComponentIndex = 0; $priceComponentIndex < count($prices); $priceComponentIndex++)
        {
            $price = $prices[$priceComponentIndex];

            for($i = 0; $i < count($accountLootAmount); $i++)
            {
                $lootAmountRow = $accountLootAmount[$i];

                $matchingPrice = null;

                if($lootAmountRow["item_id"] === $price->item->crand)
                {
                    $matchingPrice = $price;
                }

                if(is_null($matchingPrice))
                {
                    throw new Exception("got here");
                    if($priceComponentIndex === count($prices)-1) return false;
                    continue;
                }

                $difference = $lootAmountRow["amount"] - $matchingPrice->amount;

                if($difference >= 0)
                {
                    $accountLootAmount[$i] = ["item_id"=>$lootAmountRow["item_id"], "amount"=>$difference];
                    break;
                }
                else
                {
                    return false;
                }
            }
        }

        return true;
    }

    private static function returnAmountsOfPricesLootInAccountInventory(array $prices, vAccount $account) : array
    {
        $whereClause = static::createWhereClauseForReturnAmountsOfPricesLootInAccountInventory($prices);

        $sql = "SELECT item_id, SUM(quantity) as 'amount' FROM v_loot_item WHERE account_id = $account->crand AND ($whereClause) GROUP BY item_id";

        $result = Database::executeSqlQuery($sql, []);

        if(!$result) throw new Exception("result returned false");

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    private static function createWhereClauseForReturnAmountsOfPricesLootInAccountInventory(array $prices) : string
    {
        $whereClause = "";

        for($i = 0; $i < count($prices); $i++)
        {
            $whereClause .= "item_id = ".$prices[$i]->item->crand;

            if($i != count($prices) - 1) $whereClause .= " OR ";
        }

        return $whereClause;
    }

    private static function returnAccountLoot(vAccount $account) : array
    {

        $sql = "SELECT 
            vli.Id,
            vli.opened,
            vli.account_id,
            vli.item_id,
            vli.quest_id,
            vli.media_id_small,
            vli.media_id_large,
            vli.media_id_back,
            vli.loot_type,
            vli.`desc`,
            vli.rarity,
            vli.dateObtained,
            vli.container_loot_id,
            vii.name,
            vii.Id as 'item_id',
            vli.quantity,
            vii.is_fungible
        FROM v_loot_item vli
        JOIN v_item_info vii ON vli.item_id = vii.Id WHERE account_id = $account->crand";
        $result = Database::executeSqlQuery($sql, []);

        if(!$result) throw new Exception("result returned false");

        $loots = [];

        while($row = $result->fetch_assoc())
        {
            array_push($loots, LootController::row_to_vLoot($row, true));
        }

       return $loots;
    }

    private static function didAccountReceiveProduct(vAccount $account, vProduct $product) : bool
    {
        $sql = "SELECT vll.ctime FROM v_product_loot_link vll 
        JOIN v_loot_item vli ON vll.loot_crand = vli.Id
        WHERE vli.account_id = $account->crand AND 
        vll.product_ctime = '$product->ctime' AND 
        vll.product_crand = $product->crand AND
        vll.removed = 1";

        $result = Database::executeSqlQuery($sql, []);

        if(!$result) throw new Exception("result returend false");

        if($result->num_rows > 0) return true;
        return false;
    }

    //MOCKS

    public static function returnMockInfiniteOverrideCouponObject(vProduct $product, array $prices) : Coupon
    {
        $coupon = new Coupon();

            $coupon->code = 'OverCode';
            $coupon->description = "OverrideCouponDescription";
            $coupon->requiredQuantityOfProduct = 1;
            $coupon->productId = $product->getForeignRecordId();
            $coupon->timesUsed = 0;
            $coupon->removed = false;

            $coupon->prices = $prices;

        return $coupon;
    }

    public static function returnMockInfiniteCouponObject(vProduct $product, array $prices) : Coupon
    {
        $coupon = new Coupon();

            $coupon->code = 'TestCode';
            $coupon->description = "CouponDescription";
            $coupon->requiredQuantityOfProduct = 1;
            $coupon->productId = $product->getForeignRecordId();
            $coupon->timesUsed = 0;
            $coupon->removed = false;

            $coupon->prices = $prices;

        return $coupon;
    }

    public static function returnMockProductObject(vStore $store, array $prices, vMedia $media) : Product
    {

        $testProduct = new Product(
            "CheapTestProduct", 
            "Test-Product-Cheap", 
            false,
            "Test product locator", 
            ["popular"],
            ["lich"],
            $store,  
            $prices,
            $media,
            $media,
            $media
        );

        return $testProduct;
    }

    public static function returnMockNonFungibleProductStockItem(vMedia $media) : Item
    {
        $item = new Item();  
        
        $item->mediaLarge = new ForeignRecordId($media->ctime, $media->crand);
        $item->mediaSmall = new ForeignRecordId($media->ctime, $media->crand);
        $item->mediaBack = new ForeignRecordId($media->ctime, $media->crand);

        $item->name = "nonFungible";
        $item->desc = "non-fungible mock item for product";

        $item->type = ItemType::Standard;
        $item->rarity = ItemRarity::Common;

        $item->equipable = false;
        $item->redeemable = true;

        $item->isContainer = false;
        $item->containerSize = -1;
        $item->fungible = false;

        return $item;
    }

    public static function returnMockFungibleProductStockItem(vMedia $media) : Item
    {
        $item = new Item();  
        
        $item->mediaLarge = new ForeignRecordId($media->ctime, $media->crand);
        $item->mediaSmall = new ForeignRecordId($media->ctime, $media->crand);
        $item->mediaBack = new ForeignRecordId($media->ctime, $media->crand);

        $item->name = "FungibleItem";
        $item->desc = "Fungible mock item for product";

        $item->type = ItemType::Standard;
        $item->rarity = ItemRarity::Common;

        $item->equipable = false;
        $item->redeemable = true;

        $item->isContainer = false;
        $item->containerSize = -1;
        $item->fungible = true;

        return $item;
    }

    public static function returnMockStoreObject(vRecordId $mockAccountId) : Store
    {

        $store = new Store("testStore", "testLocator", "testDescription", $mockAccountId);

        return $store;
    }

    public static function returnMockBuyerAccountObject() : Account
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

    public static function returnMockSellerAccountObject() : Account
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

    public static function returnMockPriceObject(string $priceObjectName, int $amount = 1) : price
    {
        switch(strtolower($priceObjectName))
        {
            case "raffleticket":
                $id = new vRecordId('', 4);
                break;
            case "fungibleCoins":
                $id = new vRecordId('', 400);
                break;
            default :
                $id = new vRecordId('', 4);
                break;
        }

        $price = new Price($amount, null, $id);

        return $price;
    }

    private static function returnMockPriceCouponObject(vItem $priceItem, int $amount = 1) : price
    {
        $price = new price();

        $price->amount = $amount;
        $price->itemId = $priceItem->getVRecordId();

        return $price;
    }

    public static function returnMockNonFungibleCouponPriceItem(vMedia $media) : Item
    {
        $item = new Item();  
        
        $item->mediaLarge = new ForeignRecordId($media->ctime, $media->crand);
        $item->mediaSmall = new ForeignRecordId($media->ctime, $media->crand);
        $item->mediaBack = new ForeignRecordId($media->ctime, $media->crand);

        $item->name = "CouponPriceItem";
        $item->desc = "Non-fungible Item to be used for replacment of prices via coupons";

        $item->type = ItemType::Standard;
        $item->rarity = ItemRarity::Common;

        $item->equipable = false;
        $item->redeemable = true;

        $item->isContainer = false;
        $item->containerSize = -1;
        $item->fungible = false;

        return $item;
    }

    public static function returnMockNonFungibleOverrideCouponPriceItem(vMedia $media) : Item
    {
        $item = new Item();  
        
        $item->mediaLarge = new ForeignRecordId($media->ctime, $media->crand);
        $item->mediaSmall = new ForeignRecordId($media->ctime, $media->crand);
        $item->mediaBack = new ForeignRecordId($media->ctime, $media->crand);

        $item->name = "OverrideCouponPriceItem";
        $item->desc = "Non-fungible Item to be used for replacment of prices via coupons and overriding other coupons";

        $item->type = ItemType::Standard;
        $item->rarity = ItemRarity::Common;

        $item->equipable = false;
        $item->redeemable = true;

        $item->isContainer = false;
        $item->containerSize = -1;
        $item->fungible = false;

        return $item;
    }

    public static function createMockNonFungibleProduct(vStore $store, vMedia $media) : vProduct
    {
        $raffleTicketPrice = static::returnMockPriceObject("raffleticket");

        $foundPriceResp = StoreController::InsertAndSelectPrices([$raffleTicketPrice]);

        if(!$foundPriceResp->success) throw new Exception("COMPONENT TEST FAILED : getting prices failed : $foundPriceResp->message");
        if(count($foundPriceResp->data) !== 1) throw new Exception("COMPONENT TEST FAILED : expected a single price to be returned : $foundPriceResp->message");

        $product = static::returnMockProductObject($store, $foundPriceResp->data, $media);
        $insertProductResp = StoreController::upsertProduct($product);
        if(!$insertProductResp->success) throw new Exception("COMPONENT TEST FAILED : inserting product failed : $insertProductResp->message");
        
        $getProductResp = StoreController::getProduct($product);
        if(!$getProductResp->success) throw new Exception("COMPONENT TEST FAILED : failed to get product after insertion : $getProductResp->message");

        return $getProductResp->data;
    }

    public static function createMockInfiniteOverrideCoupon(vProduct $product, array $prices) : vCoupon
    {
        $coupon = static::returnMockInfiniteOverrideCouponObject($product, $prices);

        $insertResp = storeController::addCoupon($coupon);

        if(!$insertResp->success) throw new Exception("failed to insert mock infinite override coupon : $insertResp->message");

        $getResp = StoreController::getCouponByCode($coupon->code);

        if(!$getResp->success) throw new Exception("failed to get infinite override coupon after insertion : $getResp->message");

        return $getResp->data;
    }

    public static function createMockInfiniteCoupon(vProduct $product, array $prices) : vCoupon
    {
        $coupon = static::returnMockInfiniteCouponObject($product, $prices);

        $insertResp = storeController::addCoupon($coupon);

        if(!$insertResp->success) throw new Exception("failed to insert mock infinite coupon : $insertResp->message");

        $getResp = StoreController::getCouponByCode($coupon->code);

        if(!$getResp->success) throw new Exception("failed to get infinite coupon after insertion : $getResp->message");

        return $getResp->data;
    }

    public static function createMockFungibleProduct(vStore $store, vMedia $media, int $amount) : vProduct
    {
        $coinsTicketPrice = static::returnMockPriceObject("fungibleCoins", $amount);

        $foundPriceResp = StoreController::InsertAndSelectPrices([$coinsTicketPrice]);

        if(!$foundPriceResp->success) throw new Exception("COMPONENT TEST FAILED : getting prices failed : $foundPriceResp->message");
        if(count($foundPriceResp->data) !== 1) throw new Exception("COMPONENT TEST FAILED : expected a single price to be returned : $foundPriceResp->message");
        if($foundPriceResp->data[0]->amount !== $amount) throw new Exception("COMPONENT TEST FAILED : inserted price did not match the amount expected | Expected : $amount | Actual : ".$foundPriceResp->data->amount);

        $product = static::returnMockProductObject($store, $foundPriceResp->data, $media);
        $insertProductResp = StoreController::upsertProduct($product);
        if(!$insertProductResp->success) throw new Exception("COMPONENT TEST FAILED : inserting product failed : $insertProductResp->message");

        $getProductResp = StoreController::getProductById($product);
        if(!$getProductResp->success) throw new Exception("COMPONENT TEST FAILED : failed to get product after insertion : $getProductResp->message");

        return $getProductResp->data;
    }

    public static function createMockCouponPrices(vItem $item, int $amount = 1) : array
    {
        $price = static::returnMockPriceCouponObject($item, $amount);
        $foundPriceResp = StoreController::InsertAndSelectPrices([$price]);

        if(!$foundPriceResp->success) throw new Exception("COMPONENT TEST FAILED : getting prices failed : $foundPriceResp->message");
        if(count($foundPriceResp->data) !== 1) throw new Exception("COMPONENT TEST FAILED : expected a single price to be returned : $foundPriceResp->message");

        return $foundPriceResp->data;
    }

    public static function createMockOverrideCouponPriceItem(vMedia $itemMedia) : vItem
    {
        $item = static::returnMockNonFungibleOverrideCouponPriceItem($itemMedia);

        $insertItemResp = ItemController::insertItem($item);

        if(!$insertItemResp->success) throw new Exception("FAILED TO INSERT MOCK ITEM : $insertItemResp->message");

        $getItemResp = ItemController::getItemsByName($item->name);

        if(!$getItemResp->success) throw new Exception("FAILED TO RETRIEVE MOCK ITEM : $getItemResp->message : ".json_encode($item));

        $item = $getItemResp->data[0];

        if($item->crand === -1) throw new Exception("FAILED TO RETRIEVE MOCK ITEM : Item id is -1");

        return $item ;
    }


    public static function createMockCouponPriceItem(vMedia $itemMedia) : vItem
    {
        $item = static::returnMockNonFungibleCouponPriceItem($itemMedia);

        $insertItemResp = ItemController::insertItem($item);

        if(!$insertItemResp->success) throw new Exception("FAILED TO INSERT MOCK ITEM : $insertItemResp->message");

        $getItemResp = ItemController::getItemsByName($item->name);

        if(!$getItemResp->success) throw new Exception("FAILED TO RETRIEVE MOCK ITEM : $getItemResp->message : ".json_encode($item));

        $item = $getItemResp->data[0];

        if($item->crand === -1) throw new Exception("FAILED TO RETRIEVE MOCK ITEM : Item id is -1");

        return $item ;
    }

    public static function createMockFungibleItem(vMedia $itemMedia) : vItem
    {
        $item = static::returnMockFungibleProductStockItem($itemMedia);

        $insertItemResp = ItemController::insertItem($item);

        if(!$insertItemResp->success) throw new Exception("FAILED TO INSERT MOCK ITEM : $insertItemResp->message");

        $getItemResp = ItemController::getItemsByName($item->name);

        if(!$getItemResp->success) throw new Exception("FAILED TO RETRIEVE MOCK ITEM : $getItemResp->message : ".json_encode($item));

        $item = $getItemResp->data[0];

        if($item->crand === -1) throw new Exception("FAILED TO RETRIEVE MOCK ITEM : Item id is -1");

        return $item ;
    }

    public static function createMockNonFungibleItem(vMedia $itemMedia) : vItem
    {
        $item = static::returnMockNonFungibleProductStockItem($itemMedia);

        $insertItemResp = ItemController::insertItem($item);

        if(!$insertItemResp->success) throw new Exception("FAILED TO INSERT MOCK ITEM : $insertItemResp->message");

        $getItemResp = ItemController::getItemsByName($item->name);

        if(!$getItemResp->success) throw new Exception("FAILED TO RETRIEVE MOCK ITEM : $getItemResp->message : ".json_encode($item));

        $item = $getItemResp->data[0];

        if($item->crand === -1) throw new Exception("FAILED TO RETRIEVE MOCK ITEM : Item id is -1");

        return $item ;
    }

    private static function createProductLootLinks(vProduct $product, array $loots) : array
    {
        $linkLootsResp = StoreController::linkLootsToProductAsStock($product, $loots);

        if(!$linkLootsResp->success) throw new Exception("COMPONENT TEST FAILED : failed to crate product loot links : $linkLootsResp->message");

        $lootLinks = static::getProductLootLinks($product, $loots);

        return $lootLinks;
    }

    private static function getProductLootLinks(vProduct $product, array $loots) : array
    {
        $whereClause = static::createWhereClauseForGetProductLootLinks($loots);

        $sql = "SELECT * FROM v_product_loot_link WHERE product_ctime = '$product->ctime' AND product_crand = $product->crand AND ($whereClause)";

        $result = Database::executeSqlQuery($sql, []);

        $lootLinks = [];

        while($row = $result->fetch_assoc())
        {
            array_push($lootLinks, StoreController::rowToVProductLootLink($row));
        }

        return $lootLinks;
    }

    private static function createWhereClauseForGetProductLootLinks(array $loots) : string
    {
        $whereClause = "";

        for($i = 0; $i < count($loots); $i++)
        {
            $loot = $loots[$i];
            $whereClause .= "(loot_ctime = '$loot->ctime' AND loot_crand = $loot->crand)";

            if($i != count($loots)-1)
            {
                $whereClause .= " OR ";
            }
        }

        return $whereClause;
    }
    
    private static function createNumberOfTestProductStockLoot(int $num, vProduct $product, vItem $item) : array
    {
        if($item->fungible)
        {
            $lootId = new RecordId();

            $sql = "INSERT INTO loot (Id, opened, nickname, `description`, account_id, item_id, quest_id, dateObtained, redeemed, container_loot_id, quantity) 
            VALUES (?,?,?,?,?,?,?,NOW(),?,?,?)";

            $params = [$lootId->crand, 1, $item->name, $item->description, $product->owner->crand, $item->crand, -1, 1, null, $num];

            $result = Database::executeSqlQuery($sql, $params);

            if(!$result) throw new Exception("Result returned false");

            $sql = "SELECT vl.*,
                vi.type,
                vi.rarity,
                vi.media_id_large,
                vi.media_id_small,
                vi.desc,
                vi.name,
                vi.nominated_by_id,
                vi.collection_id,
                vi.equipable,
                vi.equipment_slot,
                vi.redeemable,
                vi.useable,
                vi.is_fungible,
                vi.large_image,
                vi.small_image,
                vi.artist,
                vi.artist_id,
                vi.nominator,
                vi.nominator_id,
                vi.DateCreated
                FROM v_loot_item vl JOIN v_item_info vi ON vl.item_id = vi.Id WHERE vl.Id = ?
            ";
            $params = [$lootId->crand];

            $result = Database::executeSqlQuery($sql, $params);
            if(!$result) throw new Exception("Result returned false");

            $loots = [LootController::row_to_vLoot($result->fetch_assoc())];
        }
        else
        {
            $lootIds = [];
            $sql = static::createInsertQueryToCreateNumberOfTestProductStockLoot($num, $product, $item, $lootIds );

            Database::executeSqlQuery($sql, []);

            $sql = static::createSelectQueryForCreateNumberOfTestProductStockLoot($lootIds);
            $result = Database::executeSqlQuery($sql, []);

            $loots = [];

            while($row = $result->fetch_assoc())
            {
                $loot = LootController::row_to_vLoot($row);
                array_push($loots, $loot);
            }
        }
        
        return $loots;
    }

    private static function createSelectQueryForCreateNumberOfTestProductStockLoot(array $lootIds) : string
    {
        $whereClause = "";

        for($i = 0; $i < count($lootIds); $i++)
        {
            $whereClause .= "vl.Id = ".$lootIds[$i]->crand;

            if($i != count($lootIds)-1)
            {
                $whereClause .= " OR ";
            }
        }

        $whereClause = "(".$whereClause.")";

        $sql = "SELECT vl.*,
            vi.type,
            vi.rarity,
            vi.media_id_large,
            vi.media_id_small,
            vi.desc,
            vi.name,
            vi.nominated_by_id,
            vi.collection_id,
            vi.equipable,
            vi.equipment_slot,
            vi.redeemable,
            vi.useable,
            vi.is_fungible,
            vi.large_image,
            vi.small_image,
            vi.artist,
            vi.artist_id,
            vi.nominator,
            vi.nominator_id,
            vi.DateCreated
        FROM v_loot_item vl JOIN v_item_info vi ON vl.item_id = vi.Id WHERE $whereClause";

        return $sql;
    }

    private static function createInsertQueryToCreateNumberOfTestProductStockLoot(int $num, vProduct $product, vItem $item, array &$lootIds) : string
    {  
        $valueClause = "";

        for($i = 0; $i < $num; $i++)
        {
            $id = new RecordId();
            $valueClause .= "($id->crand, 1, '$item->name', '$item->description', ".$product->owner->crand.", $item->crand, -1, NOW(), 1, null, 1)";

            array_push($lootIds, $id);

            if($i != $num - 1)
            {
                $valueClause .= ",";
            }
        }

        $sql = "INSERT INTO loot (Id, opened, nickname, `description`, account_id, item_id, quest_id, dateObtained, redeemed, container_loot_id, quantity) VALUES $valueClause";

        return $sql;
    }

    public static function returnFirstMedia() : vMedia
    {
        $sql = "Select * FROM v_media limit 1;";

        $result = Database::executeSqlQuery($sql, []);

        if(!$result) throw new Exception("result returned false while returning frist media");

        $row = $result->fetch_assoc();

        $media = new vMedia('', $row["Id"]);

        $media->name = $row["name"];
        $media->desc = $row["desc"];
        $media->author = new vAccount('', $row["author_id"]);
        $media->dateCreated = new vDateTime($row["DateCreated"]);
        $media->extension = $row["extension"];
        $media->directory = $row["directory"];
        $media->setMediaPath($row["mediaPath"]);

        return $media;
    }

    public static function createMockMedia(vAccount $author) : vMedia
    {
        $sql = "INSERT INTO media(Id, ServiceKey, `name`, `desc`, author_id, DateCreated, extension, Directory)
        VALUES(7, 'ffffffffffffff', 'prestige +1 big', 'prestige +1', $author->crand, NOW(), 'png','items');";
        Database::executeSqlQuery($sql, []);

        $sql = "SELECT * FROM v_media WHERE Id = 7;";
        $result = Database::executeSqlQuery($sql, []);

        $row = $result->fetch_assoc();

        $media = new vMedia('', $row["Id"]);

        $media->name = $row["name"];
        $media->desc = $row["desc"];
        $media->author = new vAccount('', $row["author_id"]);
        $media->dateCreated = new vDateTime($row["DateCreated"]);
        $media->extension = $row["extension"];
        $media->directory = $row["directory"];
        $media->setMediaPath($row["mediaPath"]);

        return $media;
    }

    public static function createMockStore(vAccount $mockStoreAccount) : vStore
    {
        $mockStoreObject = static::returnMockStoreObject($mockStoreAccount);

        $addMockStoreResp = StoreController::addStore($mockStoreObject);
        if(!$addMockStoreResp->success) throw new Exception("Error in inserting mock store : $addMockStoreResp->message");

        $getMockStoreResp = StoreController::getStoreById($mockStoreObject);
        if(!$getMockStoreResp->success) throw new Exception("Error in getting mock store : $getMockStoreResp->message");

        return $getMockStoreResp->data;
    }

    public static function createMockBuyerAccount() : vAccount
    {
        $mockBuyerObject = static::returnMockBuyerAccountObject();

        static::insertMockAccount($mockBuyerObject);

        $accountResp = AccountController::getAccountByUsername($mockBuyerObject->username);

        if(!$accountResp->success) throw new Exception("Error in create mock buyer account : $accountResp->message");

        return $accountResp->data;
    }

    public static function createMockSellerAccount() : vAccount
    {
        $mockSellerObject = static::returnMockSellerAccountObject();

        static::insertMockAccount($mockSellerObject);

        $accountResp = AccountController::getAccountByUsername($mockSellerObject->username);

        if(!$accountResp->success) throw new Exception("Error in create mock seller account : $accountResp->message");

        return $accountResp->data;
    }

    public static function insertMockAccount(Account $mockAccountObject) : void
    {
        $randId = new RecordId();

        $query = "INSERT INTO account 
        (id, email, `password`, firstName, lastName, DateCreated, Username, Banned, pass_reset, passage_id) VALUES 
        ($mockAccountObject->crand, '$mockAccountObject->email', 'somepasswordhash', '$mockAccountObject->firstName', '$mockAccountObject->lastName', '$mockAccountObject->ctime', '$mockAccountObject->username', 0, 0, $randId->crand);";

        Database::executeSqlQuery($query, []);
    }

    private static function giveNumberOfCouponPriceLootsToAccount(vAccount $account, vCoupon $coupon, int $amount) : void
    {
        $valueClause = static::createValueClauseForGiveNumberOfCouponPriceLootsToAccount($account, $coupon, $amount);
        $sql = "INSERT INTO loot (Id, opened, nickname, `description`, account_id, item_id, quest_id, dateObtained, redeemed, container_loot_id, quantity) VALUES $valueClause";

        $result = Database::executeSqlQuery($sql);

        if(!$result) throw new Exception("result returned false while giving number of coupon price loots to account");
    }

    private static function createValueClauseForGiveNumberOfCouponPriceLootsToAccount(vAccount $account, vCoupon $coupon, int $amount) : string
    {
        $valueClause = "";

        for($i = 0; $i < $amount; $i++)
        {
            for($p = 0; $p < count($coupon->prices); $p++)
            {
                $id = new RecordId();

                $price = $coupon->prices[$p];
                $priceItem = $price->item;

                if($i === 0 && $p === 0)
                {
                    $valueClause .= "($id->crand, 1, 'couponPriceLoot', 'couponPriceLootDescription', $account->crand, $priceItem->crand, -1, '$id->ctime', 1, null, $price->amount)";
                    continue;
                }

                $valueClause .= ", ($id->crand, 1, 'couponPriceLoot', 'couponPriceLootDescription', $account->crand, $priceItem->crand, -1, '$id->ctime', 1, null, $price->amount)";
            }

        }

        return $valueClause;
    }

    public static function giveNumberOfRaffleTicketsToAccount(vAccount $account, int $amount) : void
    {
        $valueClause = "";
        for($i = 0; $i < $amount; $i++)
        {
            $id = new recordId();

            $raffleTicketId = static::returnMockPriceObject("raffleticket")->itemId->getVRecordId();

            $valueClause .= "($id->crand, 1, 'raffle ticket', 'raffle ticket', $account->crand, $raffleTicketId->crand, -1, NOW(), 1, NULL, 1)";

            if($i !== $amount-1)
            {
                $valueClause .= ", ";
            }
        }   

        $sql = "INSERT INTO loot (Id, opened, nickname, `description`, account_id, item_id, quest_id, dateObtained, redeemed, container_loot_id, quantity)
        VALUES $valueClause";

        $result = Database::executeSqlQuery($sql, []);

        if(!$result) throw new Exception("result returned false");
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
        $query = "CREATE OR REPLACE VIEW v_cart_product_link AS (
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
                vp.back_media_media_path,
                vcu.ctime as `coupon_ctime`,
                vcu.crand as `coupon_crand`,
                vcu.code as `coupon_code`,
                vcu.description as `coupon_description`,
                vcu.required_quantity_of_product as `coupon_required_quantity_of_product`,
                vcu.times_used as `coupon_times_used`,
                vcu.max_times_used as `coupon_max_times_used`,
                vcu.max_times_used_per_account as `coupon_max_times_used_per_account`,
                vcu.expiry_time as `coupon_expiry_time`,
                vcu.removed as 'coupon_removed'
            FROM cart_product_link cplink
                JOIN v_product vp on cplink.ref_product_ctime = vp.ctime and cplink.ref_product_crand = vp.crand
                JOIN v_cart vc on cplink.ref_cart_ctime = vc.ctime and cplink.ref_cart_crand = vc.crand
                LEFT JOIN coupon_cart_product_link ccpl ON ccpl.ref_cart_product_link_ctime = cplink.ctime AND ccpl.ref_cart_product_link_crand = cplink.crand 
                LEFT JOIN v_coupon vcu ON vcu.ctime = ccpl.ref_coupon_ctime AND vcu.crand = ccpl.ref_coupon_crand
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
            a.username as `account_username`,
            s.name as `store_name`,
            s.locator as `store_locator`,
            c.checked_out,
            c.void,
            a.DateCreated AS `account_ctime`,
            a.Id as `account_crand`,
            s.ctime as `store_ctime`,
            s.crand as `store_crand`,
        s.ref_owner_ctime as `store_owner_ctime`,
            s.ref_owner_crand as `store_owner_crand`,
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
        CREATE OR REPLACE VIEW v_cart_item AS (
        SELECT
            cplink.ctime as 'cart_product_link_ctime',
            cplink.crand as 'cart_product_link_crand',
            cplink.ref_cart_ctime as 'cart_ctime',
            cplink.ref_cart_crand as 'cart_crand',
            pplink.removed,
            pplink.checked_out,
            vprod.ctime as 'product_ctime',
            vprod.crand as 'product_crand',
            vprod.name as 'product_name',
            vprod.description as 'product_description',
            vprod.locator as 'product_locator',
            vprod.small_media_media_path as 'product_small_media_path',
            vprod.large_media_media_path as 'product_large_media_path',
            vprod.back_media_media_path as 'product_back_media_path',
            vprod.stock as 'product_stock',
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
            vprice.item_crand as 'price_item_crand',
            vprice.item_is_fungible as 'price_item_is_fungible',
            vcu.ctime as `coupon_ctime`,
            vcu.crand as `coupon_crand`,
            vcu.code as `coupon_code`,
            vcu.description as `coupon_description`,
            vcu.required_quantity_of_product as `coupon_required_quantity_of_product`,
            vcu.times_used as `coupon_times_used`,
            vcu.max_times_used as `coupon_max_times_used`,
            vcu.max_times_used_per_account as `coupon_max_times_used_per_account`,
            vcu.expiry_time as `coupon_expiry_time`,
            vcu.removed as 'coupon_removed',
            ccpl.coupon_assignment_group_ctime,
            ccpl.coupon_assignment_group_crand
        FROM cart_product_link cplink
            JOIN cart c ON cplink.ref_cart_ctime = c.ctime AND cplink.ref_cart_crand = c.crand
            JOIN v_product vprod ON cplink.ref_product_ctime = vprod.ctime AND cplink.ref_product_crand = vprod.crand
            JOIN cart_product_price_link pplink ON cplink.ctime = pplink.ref_cart_product_link_ctime AND cplink.crand = pplink.ref_cart_product_link_crand
            JOIN v_price vprice ON pplink.ref_price_ctime = vprice.ctime AND pplink.ref_price_crand = vprice.crand
            LEFT JOIN (SELECT ref_coupon_ctime, ref_coupon_crand, ref_cart_product_link_ctime, ref_cart_product_link_crand, coupon_assignment_group_ctime, coupon_assignment_group_crand FROM coupon_cart_product_link WHERE removed = 0 AND checked_out = 0)ccpl ON ccpl.ref_cart_product_link_ctime = cplink.ctime AND ccpl.ref_cart_product_link_crand = cplink.crand 
            LEFT JOIN v_coupon vcu ON vcu.ctime = ccpl.ref_coupon_ctime AND vcu.crand = ccpl.ref_coupon_crand
        );";

        Database::executeSqlQuery($sql, []);
    }

    //PRODUCT

    private static function createProductTable() : void
    {
        $query = "CREATE TABLE IF NOT EXISTS product
        (
            ctime datetime(6) not null,
            crand bigint not null,
            `name` varchar(50) not null,
            `description` varchar(200),
            `removed` tinyint not null DEFAULT(0),
            locator varchar(50) not null,
            tags JSON not null,
            banners JSON not null,
            ref_store_ctime datetime(6) not null,
            ref_store_crand bigint not null,
            ref_media_id_large INT,
            ref_media_id_small INT,
            ref_media_id_back INT,
            
            PRIMARY KEY (ctime, crand),
            
            CONSTRAINT fk_product_ref_store_ctime_crand_store_ctime_crand FOREIGN KEY (ref_store_ctime, ref_store_crand) REFERENCES store(ctime, crand),
            CONSTRAINT fk_product_ref_media_id_large_media_id FOREIGN KEY (ref_media_id_large) REFERENCES media(id),
            CONSTRAINT fk_product_ref_media_id_small_media_id FOREIGN KEY (ref_media_id_small) REFERENCES media(id),
            CONSTRAINT fk_product_ref_media_id_back_media_id FOREIGN KEY (ref_media_id_back) REFERENCES media(id)
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
                    CAST(
                        COALESCE(
                            (SELECT SUM(pl.quantity)
                            FROM v_product_loot_link pl
                            WHERE pl.product_ctime = p.ctime 
                            AND pl.product_crand = p.crand 
                            AND pl.removed = 0), 0
                        ) AS SIGNED
                    ) AS stock,
                    CAST((COALESCE(
                            (SELECT SUM(pl.quantity)
                            FROM v_product_loot_link pl
                            WHERE pl.product_ctime = p.ctime 
                            AND pl.product_crand = p.crand 
                            AND pl.removed = 0), 0
                        ) - COALESCE(prt.amount_reserved, 0)) AS SIGNED) AS amount_available,
                    p.removed,
                    s.name AS `store_name`,
                    s.locator AS `store_locator`,
                    p.tags,
                    p.banners,
                    s.description AS `store_description`,
                    s.owner_username AS `store_owner_username`,
                    s.owner_ctime AS `store_owner_ctime`,
                    s.owner_crand AS `store_owner_crand`,
                    s.ctime as `store_ctime`,
                    s.crand as `store_crand`,
                    mlarge.mediaPath AS `large_media_media_path`,
                    msmall.mediaPath AS `small_media_media_path`,
                    mback.mediaPath AS `back_media_media_path`
                FROM product p 
    			LEFT JOIN v_product_reservation_total prt ON prt.product_ctime = p.ctime AND prt.product_crand = p.crand
                LEFT JOIN v_store s ON s.ctime = p.ref_store_ctime AND s.crand = p.ref_store_crand
                LEFT JOIN v_media mback ON mback.id = p.ref_media_id_back
                LEFT JOIN v_media mlarge ON mlarge.id = p.ref_media_id_large
                LEFT JOIN v_media msmall ON msmall.id = p.ref_media_id_small
            );";

        database::executeSqlQuery($query, []);
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
            CONCAT(vmback.directory, '/', vmback.Id, '.', vmback.extension) as `media_path_back`,
            i.is_fungible as `item_is_fungible`
        FROM price p
            LEFT JOIN v_item_info vi on vi.Id = p.ref_item_crand
            LEFT JOIN item i on i.id = p.ref_item_crand
            LEFT JOIN v_media vmback on vmback.Id = i.media_id_back
        );
            ";
        
        database::executeSqlQuery($sql, []);
    }

    //CART PRODUCT PRICE LINK
    private static function createCartProductPriceLinkTable() : void
    {
        $sql = "CREATE TABLE cart_product_price_link (
            ctime DATETIME(6) NOT NULL,
            crand BIGINT NOT NULL,  
            ref_cart_product_link_ctime DATETIME(6) NOT NULL,
            ref_cart_product_link_crand BIGINT NOT NULL,
            ref_price_ctime DATETIME (6) NOT NULL,
            ref_price_crand BIGINT NOT NULL,
            removed TINYINT NOT NULL DEFAULT 0,
            checked_out TINYINT NOT NULL DEFAULT 0,
            
            PRIMARY KEY(ctime, crand),
            
            CONSTRAINT fk_cart_product_price_link_cart_product_link_ctime_crand FOREIGN KEY (ref_cart_product_link_ctime, ref_cart_product_link_crand) REFERENCES cart_product_link(ctime, crand),
            CONSTRAINT fk_cart_product_price_link_price_citme_crand FOREIGN KEY (ref_price_ctime, ref_price_crand) REFERENCES price(ctime, crand)
        );";

        database::executeSqlQuery($sql, []);
    }

    private static function createCartProductPriceLinkView() : void
    {
        $sql = "CREATE VIEW v_cart_product_price_link AS
            SELECT
            ctime,
            crand,
            ref_cart_product_link_ctime as `cart_product_link_ctime`,
            ref_cart_product_link_crand as `cart_product_link_crand`,
            ref_price_ctime as `price_ctime`,
            ref_price_crand as `price_crand`,
            removed,
            checked_out
            FROM cart_product_price_link;
        ";

        database::executeSqlQuery($sql, []);
    }


    //PRODUCT PRICE LINK
    private static function createProductPriceLinkTable() : void
    {
        $sql = "create table product_price_link
        (
            ctime DATETIME(6) NOT NULL,
            crand BIGINT NOT NULL,
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

    private static function createProductPriceLinkView() : void
    {
        $sql = "CREATE VIEW v_product_price_link AS (
            SELECT 
                ppl.ctime,
                ppl.crand,
                ppl.ref_product_ctime as `product_ctime`,
                ppl.ref_product_crand as `product_crand`,
                vp.ctime as `price_ctime`,
                vp.crand as `price_crand`,
                vp.amount,
                vp.currency_code,
                vp.item_ctime,
                vp.item_crand,
                vp.item_name, 
                vp.item_desc,
                vp.media_path_small,
                vp.media_path_large,
                vp.media_path_back
            FROM product_price_link ppl
            JOIN v_price vp ON vp.ctime = ppl.ref_price_ctime AND vp.crand = ppl.ref_price_crand
            );
        ";

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
            ref_owner_ctime datetime(6) not null,
            ref_owner_crand int not null,
            
            PRIMARY KEY (ctime, crand),
            
            CONSTRAINT fk_store_ref_account_ctime_crand_account_ctime_crand FOREIGN KEY (ref_owner_crand) references account(Id) 
        );
        ";

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
            a.Username as `owner_username`,
            a.DateCreated as `owner_ctime`,
            a.Id as `owner_crand`
        FROM store s
            LEFT JOIN account a ON s.ref_owner_crand = a.Id
        );
        ";

        Database::executeSqlQuery($query, []);
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
            is_fungible tinyint NOT NULL DEFAULT 0,
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
                i.is_fungible,
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
        $query = "INSERT INTO media (Id, ServiceKey, `name`, `desc`, author_id, DateCreated, extension, directory)VALUES(282, 'tttttttt', 'tabletop simulator logo', 'tabletop simulator logo', 46, NOW(), 'png', 'games')";

        Database::executeSqlQuery($query, []);

        $query = "INSERT INTO item (Id, `type`, rarity, media_id_large, media_id_small, `desc`, `name`, nominated_by_id, collection_id, equipable, equipment_slot, redeemable, useable, is_container, container_size, container_item_category, item_category, media_id_back) 
        VALUES(4, 2, 0, 21, 21, 'Can be used in Raffle Events to win rewards!', 'Raffle Ticket', NULL, NULL, 0, NULL, 0, 0, 0, -1, null, null, 21)";

        Database::executeSqlQuery($query, []);
    }

    private static function insertTableTopSimluatorItem() : void
    {
        $sql = "INSERT INTO media (Id, ServiceKey, `name`, `desc`, author_id, DateCreated, extension, directory)VALUES(21, 'fffffff', 'Raffle Ticket', 'Raffle Ticket', 2, NOW(), 'png', 'items')";

        Database::executeSqlQuery($sql, []);

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

        $query = "CREATE OR REPLACE VIEW v_loot_item AS
        SELECT
        lt.Id                AS Id,
        lt.opened            AS opened,
        lt.account_id        AS account_id,
        lt.item_id           AS item_id,
        lt.quest_id          AS quest_id,
        it.media_id_small    AS media_id_small,
        it.media_id_large    AS media_id_large,
        it.media_id_back     AS media_id_back,
        it.type              AS loot_type,
        it.`desc`            AS `desc`,
        it.rarity            AS rarity,
        lt.dateObtained      AS dateObtained,
        lt.container_loot_id AS container_loot_id,
        lt.quantity          AS quantity,
        GREATEST(
            0,
            lt.quantity
            - COALESCE((
                SELECT SUM(pll.quantity)
                FROM v_product_loot_link AS pll
                JOIN v_product AS vp
                    ON vp.ctime = pll.product_ctime
                AND vp.crand = pll.product_crand
                WHERE vp.store_owner_crand = lt.account_id
                    AND pll.loot_crand = lt.Id
                    AND pll.removed = 0
                ), 0)
            - COALESCE((
                SELECT SUM(vlr.quantity)
                FROM v_loot_reservation AS vlr
                WHERE vlr.loot_crand = lt.Id
                ), 0)
        ) AS quantity_available
        FROM loot AS lt
        JOIN item AS it
        ON it.Id = lt.item_id;
        ";

        Database::executeSqlQuery($query, []);
    }

    //LOOT RESERVATION
    private static function createLootReservationTable() : void
    {

        $query = "CREATE TABLE loot_reservation (
                ctime DATETIME(6) NOT NULL,
                crand BIGINT(11) NOT NULL,
                ref_loot_ctime DATETIME NOT NULL,
                ref_loot_crand INT NOT NULL,
                quantity BIGINT NOT NULL DEFAULT 1,
                expiry_time DATETIME,
                close_time DATETIME,
                
                PRIMARY KEY (ctime, crand),
                CONSTRAINT fk_loot_reservation_loot_ctime_crand FOREIGN KEY (ref_loot_crand) REFERENCES loot(id)
            );
        ";

        Database::executeSqlQuery($query, []);
    }

    private static function createLootReservationView() : void
    {

        $query = "CREATE VIEW v_loot_reservation AS (
                SELECT
                ctime,
                crand,
                ref_loot_ctime as `loot_ctime`,
                ref_loot_crand as `loot_crand`,
                quantity,
                expiry_time,
                close_time
                FROM loot_reservation
            );
        ";

        Database::executeSqlQuery($query, []);
    }

    private static function createLootReservationTotalView() : void
    {

        $query = "CREATE VIEW v_loot_reservation_total AS (
                SELECT
                    lr.ref_loot_ctime as 'loot_ctime',
                    lr.ref_loot_crand as 'loot_crand',
                    SUM(lr.quantity) as 'quantity_reserved',
                    (SUM(vli.quantity)- SUM(lr.quantity)) as 'quantity_available'
                FROM loot_reservation lr JOIN v_loot_item vli ON lr.ref_loot_crand = vli.Id
                WHERE lr.close_time IS NULL AND expiry_time > NOW()
                GROUP BY lr.ref_loot_crand, lr.ref_loot_crand
            );
        ";

        Database::executeSqlQuery($query, []);
    }
    

    //Product Loot Link (stock)

    private static function createProductLootLinkTable() : void
    {
        $sql = "CREATE TABLE product_loot_link (
                    ctime DATETIME(6) NOT NULL,
                    crand BIGINT NOT NULL,
                    ref_product_ctime DATETIME(6) NOT NULL,
                    ref_product_crand BIGINT NOT NULL,
                    ref_loot_ctime DATETIME(6) NOT NULL,
                    ref_loot_crand INT NOT NULL, ##INT because the loot table's ID field is not BIGINT
                    removed TINYINT NOT NULL DEFAULT 0,
                    quantity BIGINT NOT NULL DEFAULT 1,
                    
                    PRIMARY KEY (ctime, crand),
                    
                    CONSTRAINT fk_product_loot_link_product_ctime_crand FOREIGN KEY (ref_product_ctime, ref_product_crand) REFERENCES product(ctime, crand),
                    CONSTRAINT fk_product_loot_link_loot_ctime_crand FOREIGN KEY (ref_loot_crand) REFERENCES loot(Id) ##Cannot constrain to dateObtained as ctime since (id, dateobtained) is not an indexed key
                );";

        Database::executeSqlQuery($sql, []);
    }

    private static function createProductLootLinkView() : void
    {
        $sql = "CREATE VIEW v_product_loot_link AS (
                SELECT 
                ctime,
                crand,
                ref_product_ctime AS `product_ctime`,
                ref_product_crand AS `product_crand`,
                ref_loot_crand AS `loot_crand`,
                ref_loot_ctime AS `loot_ctime`,
                removed AS `removed`,
                quantity as `quantity`
                FROM product_loot_link
            );";

        Database::executeSqlQuery($sql, []);
    }

    //RESERVATION

    private static function createReservationTable() : void
    {
        $sql = "CREATE TABLE product_reservation (
                ctime DATETIME(6) NOT NULL,
                crand BIGINT NOT NULL,
                ref_cart_ctime DATETIME(6) NOT NULL,
                ref_cart_crand BIGINT NOT NULL,
                ref_product_ctime DATETIME(6) NOT NULL,
                ref_product_crand BIGINT NOT NULL,
                quantity BIGINT NOT NULL DEFAULT 1,
                expiry_time DATETIME,
                close_time DATETIME,

                PRIMARY KEY(ctime, crand),

                CONSTRAINT fk_loot_reservation_cart_ctime_crand FOREIGN KEY (ref_cart_ctime, ref_cart_crand) REFERENCES cart(ctime, crand),
                CONSTRAINT fk_loot_reservation_product_ctime_crand FOREIGN KEY (ref_product_ctime, ref_product_crand) REFERENCES product(ctime, crand)  
            );
            ";

        Database::executeSqlQuery($sql, []);
    }

    private static function createReservationView() : void
    {
        $sql = "CREATE VIEW v_product_reservation AS
        (SELECT 
            pr.ctime,
            pr.crand,
            pr.ref_cart_ctime,
            pr.ref_cart_crand,
            pr.ref_product_ctime,
            pr.ref_product_crand,
            pr.quantity,
            pr.expiry_time,
            pr.close_time
        FROM product_reservation pr
        )";

        Database::executeSqlQuery($sql, []);
    }

    private static function createReservationTotalsView() : void
    {
        $sql = "CREATE VIEW v_product_reservation_total AS
            (
                SELECT
                pr.ref_product_ctime AS `product_ctime`,
                pr.ref_product_crand AS `product_crand`,
                SUM(pr.quantity)     AS `amount_reserved`,
                COALESCE((
                    SELECT SUM(pll.quantity)
                    FROM product_loot_link AS pll
                    WHERE pll.ref_product_ctime = pr.ref_product_ctime
                        AND pll.ref_product_crand = pr.ref_product_crand
                        AND pll.removed = 0
                ), 0) - SUM(pr.quantity) AS `amount_available`
                FROM product_reservation pr
                GROUP BY
                pr.ref_product_ctime,
                pr.ref_product_crand
            );
            ";

        Database::executeSqlQuery($sql,[]);
    }

    //COUPONS
    private static function createCouponTable() : void
    {

        $query = "CREATE TABLE IF NOT EXISTS coupon(
                ctime DATETIME(6) NOT NULL,
                crand BIGINT NOT NULL,
                `code` VARCHAR(8),
                description VARCHAR(300),
                required_quantity_of_product BIGINT NOT NULL DEFAULT 1,
                ref_product_ctime DATETIME(6) NOT NULL,
                ref_product_crand BIGINT NOT NULL,
                times_used INT NOT NULL DEFAULT 0,
                max_times_used INT,
                max_times_used_per_account INT,
                expiry_time DATETIME(6),
                removed TINYINT NOT NULL DEFAULT 0,
                
                PRIMARY KEY(ctime, crand),
                
                UNIQUE INDEX idx_coupon_code (`code`),
                
                CONSTRAINT fk_coupon_product_ctime_crand FOREIGN KEY (ref_product_ctime, ref_product_crand) REFERENCES product(ctime, crand)
            );";

        Database::executeSqlQuery($query, []);
    }

    private static function createCouponView() : void
    {

        $query = "CREATE OR REPLACE VIEW v_coupon AS(
            SELECT
                ctime,
                crand,
                `code`,
                description,
                required_quantity_of_product,
                ref_product_ctime as `product_ctime`,
                ref_product_crand as `product_crand`,
                times_used,
                max_times_used,
                max_times_used_per_account,
                expiry_time,
                removed
            FROM coupon
            );";

        Database::executeSqlQuery($query, []);
    }

    //COUPON PRICE LINK

    private static function createCouponPriceLinkTable() : void
    {

        $query = "CREATE TABLE IF NOT EXISTS coupon_price_link(
            ctime DATETIME(6) NOT NULL,
            crand BIGINT NOT NULL,
            ref_coupon_ctime DATETIME(6) NOT NULL,
            ref_coupon_crand BIGINT NOT NULL,
            ref_price_ctime DATETIME(6) NOT NULL,
            ref_price_crand BIGINT NOT NULL,
            
            PRIMARY KEY (ctime, crand),
            CONSTRAINT fk_coupon_price_link_coupon_ctime_crand FOREIGN KEY coupon(ref_coupon_ctime, ref_coupon_crand) REFERENCES coupon(ctime, crand),
            CONSTRAINT fk_coupon_price_link_price_ctime_crand FOREIGN KEY price(ref_price_ctime, ref_price_crand) REFERENCES price(ctime, crand)
        );";

        Database::executeSqlQuery($query, []);
    }

    //COUPON ACCOUNT USE

    private static function createCouponAccountUseTable() : void
    {

        $query = "CREATE TABLE IF NOT EXISTS coupon_account_use(
            ctime DATETIME(6) NOT NULL,
            crand BIGINT NOT NULL,
            ref_coupon_ctime DATETIME(6) NOT NULL,
            ref_coupon_crand BIGINT NOT NULL,
            ref_account_ctime DATETIME(6) NOT NULL,
            ref_account_crand INT NOT NULL,
            times_used INT NOT NULL DEFAULT 0,
            
            PRIMARY KEY(ctime, crand),
            CONSTRAINT fk_coupon_account_use_coupon_ctime_crand FOREIGN KEY (ref_coupon_ctime, ref_coupon_crand) REFERENCES coupon(ctime, crand),
            CONSTRAINT fk_coupon_account_use_account_ctime_crand FOREIGN KEY (ref_account_crand) REFERENCES account(Id)
        );";

        Database::executeSqlQuery($query, []);
    }

    private static function createCouponAccountUseView() : void
    {

        $query = "CREATE OR REPLACE VIEW v_coupon_account_use AS(
            SELECT 
                cu.ctime,
                cu.crand,
                c.ctime as `coupon_ctime`,
                c.crand as `coupon_crand`,
                c.code,
                c.description,
                c.required_quantity_of_product,
                c.ref_product_ctime as `product_ctime`,
                c.ref_product_crand as `product_crand`,
                c.times_used,
                c.max_times_used_per_account,
                (c.max_times_used_per_account - cu.times_used) as `remaining_uses`,
                cu.times_used as `account_times_used`,
                c.expiry_time,
                c.removed,
                cu.ref_account_ctime as `account_ctime`,
                cu.ref_account_crand as `account_crand`
                FROM coupon_account_use cu
                JOIN coupon c ON cu.ref_coupon_ctime = c.ctime AND cu.ref_coupon_crand = c.crand
            );";

        Database::executeSqlQuery($query, []);
    }

    //COUPON CART PRODUCT LINK

    private static function createCouponCartProductLinkTable() : void
    {

        $query = "CREATE TABLE IF NOT EXISTS coupon_cart_product_link(
            ctime DATETIME(6) NOT NULL,
            crand BIGINT NOT NULL,
            ref_coupon_ctime DATETIME(6) NOT NULL,
            ref_coupon_crand BIGINT NOT NULL,
            ref_cart_product_link_ctime DATETIME(6) NOT NULL,
            ref_cart_product_link_crand BIGINT NOT NULL,
            coupon_assignment_group_ctime DATETIME(6) NOT NULL,
            coupon_assignment_group_crand BIGINT NOT NULL,
            removed TINYINT NOT NULL DEFAULT 0,
            checked_out TINYINT NOT NULL DEFAULT 0,
            
            PRIMARY KEY(ctime, crand),

            UNIQUE INDEX idx_coupon_cart_product_link_coupon_ctime_crand_group_id (coupon_assignment_group_ctime, coupon_assignment_group_crand),

            CONSTRAINT fk_coupon_cart_product_link_coupon_ctime_crand FOREIGN KEY (ref_coupon_ctime, ref_coupon_crand) REFERENCES coupon(ctime, crand),
            CONSTRAINT fk_coupoin_cart_product_link_cart_product_link_ctime_crand FOREIGN KEY (ref_cart_product_link_ctime, ref_cart_product_link_crand) REFERENCES cart_product_link(ctime, crand)
        );";

        Database::executeSqlQuery($query, []);
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
            quantity bigint DEFAULT 1 NOT NULL,
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