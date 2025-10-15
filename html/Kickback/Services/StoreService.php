<?php
namespace Kickback\Services;

use Exception;
use Kickback\Backend\Controllers\AccountController;
use Kickback\Backend\Controllers\StoreController;
use Kickback\Backend\Models\Enums\CurrencyCode;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vCart;
use Kickback\Backend\Views\vCartItem;
use Kickback\Backend\Views\vItem;
use Kickback\Backend\Views\vMedia;
use Kickback\Backend\Views\vPrice;
use Kickback\Backend\Views\vProduct;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vStore;
use Kickback\Backend\Views\vTransaction;
use Kickback\Common\Primitives\Obj;
use Kickback\Services\ApiV2\Endpoint;

class StoreService
{
    private static bool $initialized = false;
    private static ?StoreService $client = null;

    public static function initialize() : void
    {
        if(self::$initialized) return;

        self::$client = new StoreService();
        self::$initialized = true;
    }

    public static function remove_product_from_cart(string $request_contents_json, ?Response &$resp) : int
    {
        $resp = new Response(false, "Unkown error encountered while removing product from cart");

        if (!Session::isLoggedIn()) {
            $endpoint_name = Endpoint::calculate_endpoint_resource_name();
            $resp = new Response(false, "$endpoint_name: Authentication required");
            return 401;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $endpoint_name = Endpoint::calculate_endpoint_resource_name();
            $resp = new Response(false, "$endpoint_name: Method not allowed");
            return 405;
        }

        if(empty($request_contents_json))
        {
            $resp->message = "Request body cannot be empty"; 
            return 400;
        } 


        $body = json_decode($request_contents_json);

        if(!is_object($body))
        {
            $resp->message = "Invalid request";
            return 400;
        }

        if(empty($body))
        {
            $resp->message = "Product cannot be empty"; 
            return 400;
        }

        StoreService::initialize();

        $product = static::vCartItemFromJson($body);

        $removeProductToCartResp = StoreController::removeProductFromCart($product);

        if(!$removeProductToCartResp->success)
        {
            $resp->success = false;
            $resp->message = "Failed to remove product from cart";
            return 500;
        }

        $resp->success = true;
        $resp->message = "Cart Product removed from cart";
        return 200;
    }

    public static function get_product_by_locator(string $queryString, ?Response &$resp) : int
    {
        $resp = new Response(false, "Unkown error encountered while returning store by locator");

        if (!Session::isLoggedIn()) {
            $endpoint_name = Endpoint::calculate_endpoint_resource_name();
            $resp = new Response(false, "$endpoint_name: Authentication required");
            return 401;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $endpoint_name = Endpoint::calculate_endpoint_resource_name();
            $resp = new Response(false, "$endpoint_name: Method not allowed");
            return 405;
        }

        if(empty($queryString))
        {
            $resp->message = "Query string cannot be empty"; 
            return 400;
        } 

        $paramaters = [];
        parse_str($queryString, $parameters);

        if(!key_exists("locator", $parameters))
        {
            $resp->message = "Query string must contain the key : 'locator'";
            return 400;
        }

        $locator = json_decode($parameters["locator"]);

        if(empty($locator))
        {
            $resp->message = "Locator cannot be empty"; 
            return 400;
        }

        StoreService::initialize();

        $storeResp = StoreController::getProductByLocator($locator);
        if(!$storeResp->success)
        {
            $endpoint_name = Endpoint::calculate_endpoint_resource_name();
            $resp = new Response(false, "$endpoint_name: Failed to Retrieve Product By Locator");
            return 500;
        }

        if(!is_null($storeResp->data))
        {
            $resp->success = true;
            $resp->message = "Returned Product With Locator : $locator";
            $resp->data = $storeResp->data;
            return 200;
        }
        else
        {
            $resp->success = true;
            $resp->message = "No Product Found With Locator : $locator";
            return 200;
        }
    }

    public static function get_store_by_locator(string $queryString, ?Response &$resp) : int
    {
        $resp = new Response(false, "Unkown error encountered while returning store by locator");

        if (!Session::isLoggedIn()) {
            $endpoint_name = Endpoint::calculate_endpoint_resource_name();
            $resp = new Response(false, "$endpoint_name: Authentication required");
            return 401;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $endpoint_name = Endpoint::calculate_endpoint_resource_name();
            $resp = new Response(false, "$endpoint_name: Method not allowed");
            return 405;
        }

        if(empty($queryString))
        {
            $resp->message = "Query string cannot be empty"; 
            return 400;
        } 

        $paramaters = [];
        parse_str($queryString, $parameters);

        if(!key_exists("locator", $parameters))
        {
            $resp->message = "Query string must contain the key : 'locator'";
            return 400;
        }

        $locator = json_decode($parameters["locator"]);

        if(empty($locator))
        {
            $resp->message = "Locator cannot be empty"; 
            return 400;
        }

        StoreService::initialize();

        $storeResp = StoreController::getStoreByLocator($locator);
        if(!$storeResp->success)
        {
            $endpoint_name = Endpoint::calculate_endpoint_resource_name();
            $resp = new Response(false, "$endpoint_name: Failed to Retrieve Store By Locator");
            return 500;
        }

        if(!is_null($storeResp->data))
        {
            $resp->success = true;
            $resp->message = "Returned Store With Locator : $locator";
            $resp->data = $storeResp->data;
            return 200;
        }
        else
        {
            $resp->success = true;
            $resp->message = "No Store Found With Locator : $locator";
            return 200;
        }
    }

    public static function get_cart_for_account(string $queryString, ?Response &$resp) : int
    {
        $resp = new Response(false, "Unkown error encountered while returning cart for account");

        /*if (!Session::isLoggedIn()) {
            $endpoint_name = Endpoint::calculate_endpoint_resource_name();
            $resp = new Response(false, "$endpoint_name: Authentication required");
            return 401;
        }*/

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $endpoint_name = Endpoint::calculate_endpoint_resource_name();
            $resp = new Response(false, "$endpoint_name: Method not allowed");
            return 405;
        }

        if(empty($queryString))
        {
            $resp->message = "Query string cannot be empty"; 
            return 400;
        } 

        $paramaters = [];
        parse_str($queryString, $parameters);

        if(!key_exists("accountId", $parameters))
        {
            $resp->message = "Query string must contain the key : 'accountId'";
            return 400;
        }

        if(!key_exists("storeLocator", $parameters))
        {
            $resp->message = "Query string must contain the key : 'storeLocator'";
            return 400;
        }

        $accountId = json_decode($parameters["accountId"]);
        $storeLocator = json_decode($parameters["storeLocator"]);

        if(empty($accountId))
        {
            $resp->message = "AccountId cannot be empty"; 
            return 400;
        }

        if(empty($storeLocator))
        {
            $resp->message = "StoreLocator cannot be empty"; 
            return 400;
        }

        StoreService::initialize();

        $id = new vRecordId('',$accountId);
        $accountResp = AccountController::getAccountById($id);
        if(!$accountResp->success)
        {
            $endpoint_name = Endpoint::calculate_endpoint_resource_name();
            $resp = new Response(false, "$endpoint_name: Failed to Retrieve Account By Id While Getting Cart For Account");
            return 500;
        }

        $storeResp = StoreController::getStoreByLocator($storeLocator);
        if(!$storeResp->success)
        {
            $endpoint_name = Endpoint::calculate_endpoint_resource_name();
            $resp = new Response(false, "$endpoint_name: Failed to Retrieve Store By Locator While Getting Cart For Account");
            return 500;
        }

        $storeResp = StoreController::getCartForAccount($accountResp->data, $storeResp->data);
        if(!$storeResp->success)
        {
            $endpoint_name = Endpoint::calculate_endpoint_resource_name();
            $resp = new Response(false, "$endpoint_name: Failed to Retrieve Store By Locator");
            return 500;
        }

        if(!is_null($storeResp->data))
        {
            $resp->success = true;
            $resp->message = "Returned Cart For Account : ".$accountResp->data->username;
            $resp->data = $storeResp->data;
            return 200;
        }
        else
        {
            $resp->success = true;
            $resp->message = "Cart Returned Null";
            return 500;
        }
    }

    public static function get_store_by_account(string $queryString, ?Response &$resp) : int
    {
        $resp = new Response(false, "Unkown error encountered while returning store by locator");

        if (!Session::isLoggedIn()) {
            $endpoint_name = Endpoint::calculate_endpoint_resource_name();
            $resp = new Response(false, "$endpoint_name: Authentication required");
            return 401;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $endpoint_name = Endpoint::calculate_endpoint_resource_name();
            $resp = new Response(false, "$endpoint_name: Method not allowed");
            return 405;
        }

        if(empty($queryString))
        {
            $resp->message = "Query string cannot be empty"; 
            return 400;
        } 

        $paramaters = [];
        parse_str($queryString, $parameters);

        if(!key_exists("accountId", $parameters))
        {
            $resp->message = "Query string must contain the key : 'accountId'";
            return 400;
        }

        $accountId = json_decode($parameters["accountId"]);

        if(empty($accountId))
        {
            $resp->message = "accountId cannot be empty"; 
            return 400;
        }

        StoreService::initialize();

        $accountId = new vRecordId('', $accountId);

        $storeResp = StoreController::getStoreByAccountId($accountId);
        if(!$storeResp->success)
        {
            $endpoint_name = Endpoint::calculate_endpoint_resource_name();
            $resp = new Response(false, "$endpoint_name: Failed to Retrieve Store By Account Id");
            return 500;
        }

        if(!is_null($storeResp->data))
        {
            $resp->success = true;
            $resp->message = "Returned Store For Account : $accountId->crand";
            $resp->data = $storeResp->data;
            return 200;
        }
        else
        {
            $resp->success = true;
            $resp->message = "No Store Found For Accout : $accountId->crand";
            return 200;
        }
    }

    public static function add_product_to_cart_by_locator(string $request_contents_json, ?Response &$resp) : int
    {
        $resp = new Response(false, "Unkown error encountered while adding product to cart");

        if (!Session::isLoggedIn()) {
            $endpoint_name = Endpoint::calculate_endpoint_resource_name();
            $resp = new Response(false, "$endpoint_name: Authentication required");
            return 401;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $endpoint_name = Endpoint::calculate_endpoint_resource_name();
            $resp = new Response(false, "$endpoint_name: Method not allowed");
            return 405;
        }

        if(empty($request_contents_json))
        {
            $resp->message = "Request body cannot be empty"; 
            return 400;
        } 


        $body = json_decode($request_contents_json, true);

        if(!key_exists("cart", $body))
        {
            $resp->message = "Request body must contain the key : 'cart'";
            return 400;
        }

        if(!key_exists("productId", $body))
        {
            $resp->message = "Request body must contain the key : 'productId'";
            return 400;
        }

        if(empty($body["cart"]))
        {
            $resp->message = "Cart cannot be empty"; 
            return 400;
        }

        if(empty($body["productLocator"]))
        {
            $resp->message = "ProductLocator cannot be empty"; 
            return 400;
        }

        $cart = (object)$body["cart"];
        $productLocator = $body["productLocator"];


        StoreService::initialize();

        $cart = static::vCartFromJson($cart);

        $productResp = StoreController::getProductByLocator($productLocator);

        if(!$productResp->success)
        {
            $resp->message = "Product not found";
            return 400;
        }

        $addProductToCartResp = StoreController::addProductToCart($productResp->data, $cart);

        if(!$addProductToCartResp->success)
        {
            $resp->message = "Failed to add product to cart";
            return 500;
        }

        if($addProductToCartResp->data)
        {
            $resp->success = true;
            $resp->message = "Product added to cart";
            return 200;
        }
        else
        {
            $resp->message = "Not enough available stock to add product to cart";
            return 200;
        }
    }

    public static function add_product_to_cart_by_id(string $request_contents_json, ?Response &$resp) : int
    {
        $resp = new Response(false, "Unkown error encountered while adding product to cart");

        /*if (!Session::isLoggedIn()) {
            $endpoint_name = Endpoint::calculate_endpoint_resource_name();
            $resp = new Response(false, "$endpoint_name: Authentication required");
            return 401;
        }*/

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $endpoint_name = Endpoint::calculate_endpoint_resource_name();
            $resp = new Response(false, "$endpoint_name: Method not allowed");
            return 405;
        }

        if(empty($request_contents_json))
        {
            $resp->message = "Request body cannot be empty"; 
            return 400;
        } 


        $body = json_decode($request_contents_json, true);

        if(!key_exists("cart", $body))
        {
            $resp->message = "Request body must contain the key : 'cart'";
            return 400;
        }

        if(!key_exists("productId", $body))
        {
            $resp->message = "Request body must contain the key : 'productId'";
            return 400;
        }

        if(empty($body["cart"]))
        {
            $resp->message = "Cart cannot be empty"; 
            return 400;
        }

        if(empty($body["productId"]))
        {
            $resp->message = "ProductId cannot be empty"; 
            return 400;
        }

        $cart = (object)$body["cart"];
        $productId = (object)$body["productId"];


        StoreService::initialize();

        $product = new vRecordId($productId->ctime, $productId->crand);

        $cart = static::vCartFromJson($cart);

        $addProductToCartResp = StoreController::addProductToCart($product, $cart);

        if(!$addProductToCartResp->success)
        {
            $productExists = StoreController::getProductById($product);

            if($productExists->success)
            {
                $resp->message = "Failed to add product to cart";
                return 500;
            }
            else
            {
                $resp->message = "Product not found";
                return 400;
            }
        }

        if($addProductToCartResp->data)
        {
            $resp->success = true;
            $resp->message = "Product added to cart";
            return 200;
        }
        else
        {
            $resp->message = "Not enough available stock to add product to cart";
            return 200;
        }
    }

    public static function remove_coupon_from_cart_product(string $request_contents_json, ?Response &$resp) : int
    {
        $resp = new Response(false, "Unkown error encountered while removing coupon from cart product");

        if (!Session::isLoggedIn()) {
            $endpoint_name = Endpoint::calculate_endpoint_resource_name();
            $resp = new Response(false, "$endpoint_name: Authentication required");
            return 401;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $endpoint_name = Endpoint::calculate_endpoint_resource_name();
            $resp = new Response(false, "$endpoint_name : Method not allowed");
            return 405;
        }

        if(empty($request_contents_json))
        {
            $resp->message = "Request body cannot be empty"; 
            return 400;
        } 


        $body = json_decode($request_contents_json, true);

        if(!key_exists("cartProduct", $body))
        {
            $resp->message = "Request body must contain the key : 'cartProduct'";
            return 400;
        }

        if(empty($body["cartProduct"]))
        {
            $resp->message = "cartProduct cannot be empty"; 
            return 400;
        }


        $cartProduct = (object)$body["cartProduct"];

        StoreService::initialize();


        $vCartProduct = static::vCartItemFromJson($cartProduct);

        if(is_null($vCartProduct->couponGroupAssignmentId) || is_null($vCartProduct->coupon))
        {
            $resp->message = "CartProduct does not have an assigned coupon to remove";
            return 422;
        }

        $removeCouponResp = StoreController::removeCouponByAssignedCartProduct($vCartProduct);

        if(!$removeCouponResp->success)
        {
            $resp->message = "Failed to remove coupon from cart product";
            return 500;
        }

        $resp->success = true;
        $resp->message = "Coupon removed";
        return 200;
    }

    public static function apply_coupon(string $request_contents_json, ?Response &$resp) : int
    {
        $resp = new Response(false, "Unkown error encountered while applying coupon");

        if (!Session::isLoggedIn()) {
            $endpoint_name = Endpoint::calculate_endpoint_resource_name();
            $resp = new Response(false, "$endpoint_name: Authentication required");
            return 401;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $endpoint_name = Endpoint::calculate_endpoint_resource_name();
            $resp = new Response(false, "$endpoint_name : Method not allowed");
            return 405;
        }

        if(empty($request_contents_json))
        {
            $resp->message = "Request body cannot be empty"; 
            return 400;
        } 


        $body = json_decode($request_contents_json, true);

        if(!key_exists("cart", $body))
        {
            $resp->message = "Request body must contain the key : 'cart'";
            return 400;
        }

        if(empty($body["cart"]))
        {
            $resp->message = "Cart cannot be empty"; 
            return 400;
        }

        if(!key_exists("couponCode", $body))
        {
            $resp->message = "Request body must contain the key : 'couponCode'";
            return 400;
        }

        if(empty($body["couponCode"]))
        {
            $resp->message = "couponCode cannot be empty"; 
            return 400;
        }


        $cart = (object)$body["cart"];
        $coupnCode = $body["couponCode"];

        StoreService::initialize();

        $couponResp = StoreController::getCouponByCode($coupnCode);

        if(!$couponResp->success)
        {
            $resp->message = "Coupon Code Not Found";
            return 400;
        }

        $vCart = static::vCartFromJson($cart);

        $applyCouponResp = StoreController::tryApplyCouponToCart($vCart, $couponResp->data);

        if(!$applyCouponResp->success)
        {
            $resp->message = "Failed to apply coupon to cart";
            return 500;
        }

        /**
         * Coupon isnt valid becuase:
         *  - it's expired
         *  - there arn't the required products in the cart
         *  - the account doesn't have any uses left
         *  - the coupon is out of global uses
         */
        if(!$applyCouponResp->data)
        {
            $resp->message = $applyCouponResp->message;
            return 422;
        }

        $resp->success = true;
        $resp->message = "Coupon applied";
        return 200;
    }

    public static function checkout_cart(string $request_contents_json, ?Response &$resp) : int
    {
        $resp = new Response(false, "Unkown error encountered while checking out cart");

        if (!Session::isLoggedIn()) {
            $endpoint_name = Endpoint::calculate_endpoint_resource_name();
            $resp = new Response(false, "$endpoint_name: Authentication required");
            return 401;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $endpoint_name = Endpoint::calculate_endpoint_resource_name();
            $resp = new Response(false, "$endpoint_name : Method not allowed");
            return 405;
        }

        if(empty($request_contents_json))
        {
            $resp->message = "Request body cannot be empty"; 
            return 400;
        } 


        $body = json_decode($request_contents_json, true);

        if(!key_exists("cart", $body))
        {
            $resp->message = "Request body must contain the key : 'cart'";
            return 400;
        }

        if(empty($body["cart"]))
        {
            $resp->message = "Cart cannot be empty"; 
            return 400;
        }

        $cart = (object)$body["cart"];

        StoreService::initialize();

        $vCart = static::vCartFromJson($cart);

        $checkoutCartResp = StoreController::checkoutCart($vCart);

        if(!$checkoutCartResp->success)
        {
            $resp->message = "Failed to checkout cart";
            return 500;
        }

        $resp->success = true;
        $resp->message = "Cart Checked Out";
        return 200;
    }

    //HYDRATION FUNCTIONS



    private static function vCartFromJson(object $cart) : vCart
    {
        $vCart = new vCart();

        $vCart->account = new vAccount();
        $vCart->store = new vStore();
        $vCart->transaction = new vTransaction();

        $account = (object)$cart->account;
        $vCart->account->username = $account->username;
        $vCart->account->ctime = $account->ctime;
        $vCart->account->crand = $account->crand;

        $store = (object)$cart->store;
        $vCart->store->name = $store->name;
        $vCart->store->locator = $store->locator;
        $vCart->store->ctime = $store->ctime;
        $vCart->store->crand = $store->crand;
            $owner = (object)$store->owner;
            $storeOwner = new vAccount($owner->ctime, $owner->crand);
        $vCart->store->owner = $storeOwner;

        $vCart->checkedOut = $cart->checkedOut;
        $vCart->void = $cart->void;

        $vCart->ctime = $cart->ctime;
        $vCart->crand = $cart->crand;

        $transaction = (object)$cart->transaction;
        $vTransaction = new vTransaction();
            $vTransaction->description = $transaction->description;
            $vTransaction->complete = $transaction->complete;
            $vTransaction->void = $transaction->void;
            $vTransaction->prices = $transaction->prices;
        $vCart->transaction = $vTransaction;

        $vCart->cartProducts = [];

        foreach($cart->cartProducts as $product)
        {
            array_push($vCart->cartProducts, static::vCartItemFromJson((object)$product));
        }

        return $vCart;
    }

    private static function vCartItemFromJson(object $cartItem) : vCartItem
    {
        $vCartItem = new vCartItem();

            $product = (object)$cartItem->product;

            $prices = static::vPriceArrayFromJson($product->prices);

                $vProduct = new vProduct($cartItem->product->ctime, $cartItem->product->crand);
                $vProduct->prices = $prices;
                $vProduct->stock = $cartItem->product->stock;
                $vProduct->locator = $cartItem->product->locator;
                $vProduct->name = $cartItem->product->name;
                $vProduct->description = $cartItem->product->description;

                $mediaSmall = (object)$product->mediaSmall;
                $vProduct->mediaSmall = static::vMediaFromJson_FullURL($mediaSmall->url);
                $mediaLarge = (object)$product->mediaLarge;
                $vProduct->mediaLarge = static::vMediaFromJson_FullURL($mediaLarge->url);
                $mediaBack = (object)$product->mediaBack;
                $vProduct->mediaBack = static::vMediaFromJson_FullURL($mediaBack->url);
            $vCartItem->product = $vProduct;

                $cart = new vCart();
                $cart->ctime = $cartItem->cart->ctime;
                $cart->crand = $cartItem->crand;
            $vCartItem->cart = $cart;
            
            $vCartItem->ctime = $cartItem->ctime;
            $vCartItem->crand = $cartItem->crand;
            $vCartItem->removed = $cartItem->removed;
            $vCartItem->checkedOut = $cartItem->checkedOut;

        return $vCartItem;
    }

    private static function vProductFromJson(object $product) : vProduct
    {
        $vProduct = new vProduct($product->ctime, $product->crand);

        $vProduct = new vProduct($product->ctime, $product->crand);
            $vProduct->prices = static::vPriceArrayFromJson($product->prices);
            $vProduct->stock = $product->stock;
            $vProduct->locator = $product->locator;
            $vProduct->name = $product->name;
            $vProduct->description = $product->description;

            $vProduct->mediaSmall = static::vMediaFromJson($product->mediaSmall->mediaPath);
            $vProduct->mediaLarge = static::vMediaFromJson($product->mediaLarge->mediaPath);;
            $vProduct->mediaBack = static::vMediaFromJson($product->mediaBack->mediaPath);

        return $vProduct;
    }

    private static function vPriceArrayFromJson(array $prices) : array
    {
        $vPrices = [];

        foreach($prices as $price)
        {
            array_push($vPrices, static::vPriceFromJson($price));
        }

        return $vPrices;
    }

    private static function vPriceFromJson(object $price) : vPrice
    {
        $vPrice = new vPrice();

        $vPrice->amount = $price->amount;
        $vPrice->item = is_null($price->item) ? null : static::vItemFromJson($price->item);
        $vPrice->currencyCode = is_null($price->currencyCode) ? null : CurrencyCode::from($price->currencyCode);

        return $vPrice;
    }

    private static function vItemFromJson(object $item) : vItem
    {
        $vItem = new vItem($item->ctime, $item->crand);

        $vItem->name = $item->name;
        $vItem->description = $item->description;

        $vItem->iconSmall = static::vMediaFromJson_FullURL($item->iconSmall->url);
        $vItem->iconBig = static::vMediaFromJson_FullURL($item->iconBig->url);
        $vItem->iconBack = static::vMediaFromJson_FullURL($item->iconBack->url);
        $vItem->fungible = $item->fungible;

        return $vItem;
    }

    private static function vMediaFromJson_FullURL(string $url) : vMedia
    {
        $media = new vMedia();
        $media->setFullPath($url);

        return $media;
    }

    private static function vMediaFromJson(string $path) : vMedia
    {
        $media = new vMedia();
        $media->setMediaPath($path);

        return $media;
    }
}

?>