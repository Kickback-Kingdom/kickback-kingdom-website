<?php

declare(strict_types = 1);

namespace Kickback\Backend\Controllers;

use BadFunctionCallException;
use DateTime;
use DomainException;
use \Kickback\Backend\Models\Response;
use \Kickback\Backend\Models\Store;
use Kickback\Backend\Models\Product;

use \Exception;
use InvalidArgumentException;
use Kickback\Backend\Models\Cart;
use Kickback\Backend\Models\CartProductCouponLink;
use Kickback\Backend\Models\CartProductLink;
use Kickback\Backend\Models\CartProductPriceLink;
use Kickback\Backend\Models\Coupon;
use Kickback\Backend\Models\CouponAccountUse;
use Kickback\Backend\Models\LootReservation;
use Kickback\Backend\Models\PriceComponent;
use Kickback\Backend\Models\ProductLootLink;
use Kickback\Backend\Models\ProductReservation;
use Kickback\Backend\Models\RecordId;
use Kickback\Backend\Models\Trade;
use Kickback\Backend\Models\CouponPriceLink;

use Kickback\Backend\Models\Enums\CurrencyCode;
use Kickback\Backend\Models\ProductPriceLink;
use Kickback\Backend\Views\vProductLootLink;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vCart;
use Kickback\Backend\Views\vCartItem;
use Kickback\Backend\Views\vCartProductCouponLink;
use Kickback\Backend\Views\vCartProductLink;
use Kickback\Backend\Views\vCartProductPriceLink;
use Kickback\Backend\Views\vCoupon;
use Kickback\Backend\Views\vCouponAccountUse;
use Kickback\Backend\Views\vItem;
use Kickback\Backend\Views\vLoot;
use Kickback\Backend\Views\vLootReservation;
use Kickback\Backend\Views\vMedia;
use Kickback\Backend\Views\vPriceComponent;
use Kickback\Backend\Views\vProduct;
use Kickback\Backend\Views\vProductReservation;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vStore;
use Kickback\Backend\Views\vTransaction;
use Kickback\Common\Exceptions\Fatal\LogicError;
use Kickback\Services\Database;
use LogicException;
use mysqli_result;
use RuntimeException;

class StoreController
{
    private static int $productReservationTimeInSeconds = 10;

    public static function runUnitTests() : void
    {
        static::unittest_constructWhereClauseForGetPricesByCurrencyAndItemAmount();
        static::unittest_returnWhereClauseBindingArrayForGetPricesByCurrencyAndItemAmount();
        static::unittest_returnWhereClauseForRemovingVoidLinksToProduct();
        static::unittest_returnParamsForInsertionForNonExistingPrices();
        static::unittest_returnWhereClauseBindingArrayForSelectPriceArrayAfterInsertion();
        static::unittest_constructInsertionValueClauseForPrices();
        static::unittest_returnInsertionParamsForLinkingProductToPrices();
        static::unittest_returnParamsForRemovingOldLinksToProduct();
        static::unittest_getNumberOfProductInCartById();
        static::unittest_returnWhereClauseForCanAccountAffordItemPricesInCart();
        static::unittest_returnParamsForCanAccountAffordItemPricesForCart();
        static::unittest_getWhereArrayClauseForGetLootForPricesForCart();
        static::unittest_getParamsForGetLootForPricesForCart();
        static::unittest_getWhereArrayClauseForGetStoreOwnerCartItemsLoot();
        static::unittest_getAreCartItemsAvailableWhereClause();
        static::unittest_getAreCartItemsAvailableParameterArray();
        static::unittest_chunkUnitTestArrayForCreateInsertParamsForLinkLootsToProductAsStock();
        static::unittest_createInsertSqlForLinkLootsToProductAsStock();
        static::unittest_createInsertParamsForLinkLootsToProductAsStock();
        static::unittest_returnValueClauseForReservingProductStock();
        static::unittest_returnParamsForReservingProductStock();
        static::unittest_returnValueClauseForReserveLootForPrices();
        static::unittest_returnWhereClauseForRemoveProductReservations();
    }

    public static function checkoutCart(vCart $cart, string $paymentProccessorPayemntId = null, bool $completeItemTransaction = true) : Response
    {
        $resp = new Response(false, "unkown error in reserving loots", null);

        try
        {
            //Does cart have items to transact
            if(count($cart->cartProducts) <= 0)
            {
                $resp->message = "Cart does not have items to checkout";
                return $resp;
            }

            //Can account afford transactable items
            
            $canAccountAffordItemPricesResp = static::canAccountAffordItemPricesInCart($cart);
            if(!$canAccountAffordItemPricesResp->success) 
            {
                $resp->message = "Error in getting if account can afford cart prices : $canAccountAffordItemPricesResp->message"; 
                return $resp; 
            }

            if(!$canAccountAffordItemPricesResp->data) 
            {
                $resp->message = "Account cannot afford item prices to checkout cart : $canAccountAffordItemPricesResp->message"; 
                return $resp; 
            }

            $reserveProductsResp = static::ReserveProductStock($cart);
            if(!$reserveProductsResp->success)
            {
                $resp->message = "failed to reserve product stock : $reserveProductsResp->message";
                return $resp;
            }

            try
            {
                $reserveLootResp = static::reserveLootForPrices($cart);
            }
            catch(Exception $e)
            {
                $removeReservations = static::removeProductReservations($reserveProductsResp->data);
                if(!$removeReservations->success) throw new Exception("Failed to remove product reservations after loot reservations failed : {\"reservations\":".json_encode($reserveProductsResp->data)."} | Exception : $e"); 
                throw $e;
            }
            
            if(!$reserveLootResp->success)
            {
                $resp->message = "failed to reserve loot for cart, removed product reserverations : $reserveLootResp->message";
                $removeReservations = static::removeProductReservations($reserveProductsResp->data);
                if(!$removeReservations->success) throw new Exception("Failed to remove product reservations after loot reservations failed : {\"reservations\":".json_encode($reserveProductsResp->data)."}");
            }

            //Process lovelace transactions
            $lovelacePriceOfCart = static::returnCartLovelacePrice($cart);
            if($lovelacePriceOfCart < 0) throw new RuntimeException("Returned LoveLace amount in cart is negative");

            if(!$completeItemTransaction)
            {
                $resp->success = true;
                $resp->message = "Successfully Reserved CartProducts however the subsequent transactions were not attempted";
                return $resp;
            }

            if($lovelacePriceOfCart > 0)
            {
                //HAVE STRIPE CONTROLLER MAKE CALL TO STRIPE TO COMPLETE TRANSACTION AND HIT ENDPOINT TO THEN TRANSACT ITEMS IF SUCCESSFUL
                //$stripeResp = StripeController::IsTransactionComplete($stripeTransactionId);
            }
            else
            {
                $transactCartResp = static::transactProductReservations($cart, $reserveProductsResp->data, $reserveLootResp->data); //Transact the successfully made reservations immediately

                if($transactCartResp->success)
                {
                    $resp->success = true;
                    $resp->message = "Checked Out Cart";
                    $resp->data = false; //do we still need to wait for stripe events?
                }
                else
                {
                    $removeLootReservations = static::removeLootReservations($reserveLootResp->data);

                    if(!$removeLootReservations->success)
                    {
                        $cartId = new vRecordId($cart->ctime, $cart->crand);
                        throw new Exception("Failed to remove loot reservations after transacting reservations failed in checkout. CartId : ".json_encode($cartId));
                    }

                    $removeProductReservations = static::removeProductReservations($reserveProductsResp->data);

                    if(!$removeProductReservations->success)
                    {
                        $cartId = new vRecordId($cart->ctime, $cart->crand);
                        throw new Exception("Failed to remove product reservations after transacting reservations failed in checkout. CartId : ".json_encode($cartId));
                    }

                    $resp->message = "Failed to transact reservations during checkout. Reservations have been removed.";
                }
            }
        }
        catch(Exception $e)
        {
            throw new exception("Exception caught while reserving loot : $e");
        }

        return $resp;
    }

    private static function removeLootReservations(array $reservations) : Response
    {
        $resp = new Response(false, "unknown error in removing product reservations", null);

        try
        {
            $whereClause = static::returnWhereClauseForRemoveLootReservations($reservations);
            $sql = "UPDATE loot_reservation SET close_time = NOW() WHERE $whereClause";

            $params = static::returnParamsForRemoveLootReservations($reservations);

            $result = Database::executeSqlQuery($sql, $params);

            if(!$result) throw new Exception("Result returned false attempting to remove product reservations");

            $resp->success = true;
            $resp->message = "Product Reservations Successfully Removed";
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while removing product reservations : $e");
        }

        return $resp;
    }

    private static function returnParamsForRemoveLootReservations(array $reservations) : array
    {
        $params = [];

        foreach($reservations as $reservation)
        {
            array_push($params, $reservation->ctime, $reservation->crand);
        }

        return $params;
    }

    private static function returnWhereClauseForRemoveLootReservations(array $reservations) : string
    {
        $whereClause = "";

        for($i = 0; $i < count($reservations); $i++)
        {
            $whereClause .= "(ctime = ? AND crand = ?)";
            
            if($i !== count($reservations)-1) $whereClause .= " OR ";
        }

        return $whereClause;
    }

    private static function removeProductReservations(array $reservations) : Response
    {
        $resp = new Response(false, "unknown error in removing product reservations", null);

        try
        {
            $whereClause = static::returnWhereClauseForRemoveProductReservations($reservations);
            $sql = "UPDATE product_reservation SET close_time = NOW() WHERE $whereClause";

            $params = static::returnParamsForRemoveProductReservations($reservations);

            $result = Database::executeSqlQuery($sql, $params);

            if(!$result) throw new Exception("Result returned false attempting to remove product reservations");

            $resp->success = true;
            $resp->message = "Product Reservations Successfully Removed";
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while removing product reservations : $e");
        }

        return $resp;
    }

    private static function returnParamsForRemoveProductReservations(array $reservations) : array
    {
        $params = [];

        foreach($reservations as $reservation)
        {
            array_push($params, $reservation->ctime, $reservation->crand);
        }

        return $params;
    }

    private static function returnWhereClauseForRemoveProductReservations(array $reservations) : string
    {
        $whereClause = "";

        for($i = 0; $i < count($reservations); $i++)
        {
            $whereClause .= "(ctime = ? AND crand = ?)";
            
            if($i !== count($reservations)-1) $whereClause .= " OR ";
        }

        return $whereClause;
    }

    private static function unittest_returnWhereClauseForRemoveProductReservations() : void
    {
        $reservations = [0];
        assert("(ctime = ? AND crand = ?)"===static::returnWhereClauseForRemoveProductReservations($reservations), 
        new Exception("UNIT TEST FAILED | expected : '(ctime = ? OR crand = ?)' | actual : ".static::returnWhereClauseForRemoveProductReservations($reservations)));
        $reservations = [0,0];
        assert("(ctime = ? AND crand = ?) OR (ctime = ? AND crand = ?)"===static::returnWhereClauseForRemoveProductReservations($reservations), 
        new Exception("UNIT TEST FAILED | expected : '(ctime = ? AND crand = ?) OR (ctime = ? AND crand = ?)' | actual : ".static::returnWhereClauseForRemoveProductReservations($reservations)));
        $reservations = [0,0,0];
        assert("(ctime = ? AND crand = ?) OR (ctime = ? AND crand = ?) OR (ctime = ? AND crand = ?)"===static::returnWhereClauseForRemoveProductReservations($reservations), 
        new Exception("UNIT TEST FAILED | expected : '(ctime = ? AND crand = ?) OR (ctime = ? AND crand = ?) OR (ctime = ? AND crand = ?)' | actual : ".static::returnWhereClauseForRemoveProductReservations($reservations)));
    }

    public static function transactProductReservations(vCart $cart, array $productReservations, array $priceReservations) : Response
    {
        $resp = new Response(false, "unkown error in transacting items", null);

        $conn = Database::getConnection();

        try
        {
            //Get the actual loot records that will be transacted and reserve them
            $productLootReservations = [];
            $productLootsResp = static::materializeProductReservations($productReservations, $productLootReservations);
            $productLoots = $productLootsResp->data;

            $priceLootsResp = static::materializePriceReservations($priceReservations);
            $priceLoots = $priceLootsResp->data;
 
            $conn->begin_transaction();

            //Product loot
            static::executeQueriesToTransactMaterializedLootsForProducts($cart, $productLoots);
            static::executeQueriesToRemoveProductLootLinkForReservations($productLoots);
            static::executeQueriesToCloseProductReservations($productReservations);

            //Trades
            static::executeQueriesToCreateTradeEntriesForReservations($cart, $productLoots, $priceReservations);

            //Price loot
            static::executeQueriesToTransactMaterializedLootsForPrices($cart, $priceLoots);

            $lootReservations = array_merge($productLootReservations, $priceReservations);
            static::executeQueriesToCloseLootReservations($lootReservations);

            //Cart
            static::markCartAsCheckedOut($cart);
            static::markCartProductsAsCheckedOut($cart);
            static::markCartProductPricesAsCheckedOut($cart);

            //Coupons
            static::markUsesForCartCoupons($cart);
            static::markCouponCartProductLinksAsCheckedOut($cart);

            $conn->commit();

            $resp->success = true;
            $resp->message = "Transacted Cart";
        }
        catch(Exception $e)
        {
            $conn->rollback();

            throw new Exception("Exception caught while attempting to transact product reservations : $e");
        }

        return $resp;
    }

    private static function markCouponCartProductLinksAsCheckedOut(vCart $cart) : void
    {
        $params = [];
        $selectTable = static::createSelectTableForMarkCouponCartProductLinksAsCheckedOut($cart->cartProducts, $params);
        $sql = "UPDATE coupon_cart_product_link ccpl JOIN ($selectTable) st ON st.cart_product_link_ctime = ccpl.ref_cart_product_link_ctime AND st.cart_product_link_crand SET checked_out = 1 WHERE ccpl.removed = 0 AND ccpl.checked_out = 0";
        
        $result = Database::executeSqlQuery($sql, $params);

        if(!$result) throw new Exception("result returned false while marking coupon cart product liks as checked out");
    }

    private static function createSelectTableForMarkCouponCartProductLinksAsCheckedOut(array $cartProducts, array &$params) : string
    {
        $selectTable = "";

        for($i = 0; $i < count($cartProducts); $i++)
        {
            $cartProduct = $cartProducts[$i];

            array_push($params, $cartProduct->ctime, $cartProduct->crand);

            if($i===0)
            {
                $selectTable .= "(SELECT ? as cart_product_link_ctime, ? as cart_product_link_crand)";
                continue;
            }

            $selectTable .= "UNION ALL (SELECT ?, ?)";
        }

        return $selectTable;
    }

    private static function markUsesForCartCoupons(vCart $cart) : void
    {
        $coupons = static::returnAllCouponsInCart($cart);

        if(count($coupons) <= 0) return;

        $aggregatedCoupons = static::aggregateCouponsInCart($coupons);

        if(count($aggregatedCoupons) <= 0) throw new LogicException("If coupon array had at least one element, the aggregate must aswell");

        $existingCouponAccountUses = static::getUpdatedCouponAccountUsesForAggregateCouponArray($aggregatedCoupons, $cart->account);
        $nonExistingCouponAccountUses = static::getNonExistingCouponAccountUses($aggregatedCoupons, $existingCouponAccountUses, $cart->account);

        if(count($existingCouponAccountUses) <= 0 && count($nonExistingCouponAccountUses) <= 0) throw new LogicException("If coupon array had at least one element, at least one element must exist between the existing and non existing coupon use references");

        if(count($existingCouponAccountUses) > 0)static::updateExistingAccountCouponUses($existingCouponAccountUses);
        if(count($nonExistingCouponAccountUses) > 0)static::insertNonExistingAccountCouponUses($nonExistingCouponAccountUses);
    }

    private static function updateExistingAccountCouponUses(array $accountUses) : void
    {
        $params = [];
        $selectTable = static::createSelectTableForUpdateExistingAccountCouponUses($accountUses, $params);
        $sql = "UPDATE coupon_account_use cau JOIN ($selectTable) st ON st.ctime = cau.ctime AND st.crand = cau.crand SET times_used = st.times_used";

        $result = Database::executeSqlQuery($sql, $params);

        if(!$result) throw new Exception("result returned false while updating exsting account coupon uses");
    }

    private static function createSelectTableForUpdateExistingAccountCouponUses(array $accountUses, array &$params) : string
    {
        $selectTable = "";

        for($i = 0; $i < count($accountUses); $i++)
        {
            $accountUse = $accountUses[$i];

            array_push($params, $accountUse->ctime, $accountUse->crand);

            if($i === 0)
            {
                $selectTable .= "(SELECT ? as ctime, ? as crand)";
                continue;
            }

            $selectTable .= "UNION ALL (SELECT ?, ?)";
        }

        return $selectTable;
    }

    private static function insertNonExistingAccountCouponUses(array $accountUses) : void
    {
        $params = [];
        $valueClause = static::createValueClauseForInsertNonExistingAccountCouponUses($accountUses, $params);
        $sql = "INSERT INTO coupon_account_use (ctime, crand, ref_coupon_ctime, ref_coupon_crand, ref_account_ctime, ref_account_crand, times_used) VALUES $valueClause";

        $result = Database::executeSqlQuery($sql, $params);

        if(!$result) throw new Exception("result returned false while inserting non existing account coupon uses");
    }

    private static function createValueClauseForInsertNonExistingAccountCouponUses(array $accountUses, array &$params) : string
    {
        $valueClause = "";

        for($i = 0; $i < count($accountUses); $i++)
        {
            $accountUse = $accountUses[$i];

            array_push($params, $accountUse->ctime, $accountUse->crand, $accountUse->couponId->ctime, $accountUse->couponId->crand, $accountUse->accountId->ctime, $accountUse->accountId->crand, $accountUse->timesUsed);

            if($i === 0)
            {
                $valueClause .= "(?,?,?,?,?,?,?)";
                continue;
            }

            $valueClause .= ", (?,?,?,?,?,?,?)";
        }

        return $valueClause;
    }

    private static function getNonExistingCouponAccountUses(array $aggregatedCoupons, array $existingCouponAccountUses, vAccount $account) : array
    {
        $nonExistingCouponAccountUses = [];

        foreach($aggregatedCoupons as $couponGroup)
        {
            $exists = false;
            for($i = 0; $i < count($existingCouponAccountUses); $i++)
            {
                $couponAccountUse = $existingCouponAccountUses[$i];

                if($couponAccountUse->coupon->ctime === $couponGroup["coupon"]->ctime 
                && $couponAccountUse->$couponGroup->crand === $couponGroup["coupon"]->crand)
                {
                    $exists = true;
                    break;
                }
            }

            if(!$exists)
            {
                $accountUse = new CouponAccountUse();
                $accountUse->timesUsed = $couponGroup["quantity"];
                $accountUse->couponId = $couponGroup["coupon"]->getForeignRecordId();
                $accountUse->accountId = $account->getForeignRecordId();

                array_push($nonExistingCouponAccountUses, $accountUse);
            }
        }

        return $nonExistingCouponAccountUses;
    }
    
    private static function getUpdatedCouponAccountUsesForAggregateCouponArray(array $aggregatedCoupons, vAccount $account) : array
    {
        if(count($aggregatedCoupons) === 0) return [];        

        $params = [];
        $selectTable = static::createSelectTableForGetCouponAccountUsesForCouponArray($aggregatedCoupons, $account, $params);

        $sql = "SELECT 
            vcau.ctime,
            vcau.crand, 
            vcau.coupon_ctime,
            vcau.coupon_crand,
            vcau.code,
            vcau.description,
            vcau.required_quantity_of_product,
            vcau.product_ctime,
            vcau.product_crand,
            vcau.max_times_used_per_account,
            vcau.remaining_uses,
            (vcau.account_times_used + st.quantity) as account_times_used,
            vcau.expiry_time,
            vcau.removed,
            vcau.account_ctime,
            vcau.account_crand
            FROM v_coupon_account_use vcau
            JOIN ($selectTable) st 
            ON st.coupon_ctime = vcau.coupon_ctime 
            AND st.coupon_crand = vcau.coupon_crand 
            AND st.account_ctime = vcau.account_ctime
            AND st.account_crand = vcau.account_crand
        ";

        $result = Database::executeSqlQuery($sql, $params);

        if(!$result) throw new Exception("result returned false while getting coupon account uses for coupon array");
        
        $couponAccountUses = [];

        while($row = $result->fetch_assoc())
        {
            array_push($couponAccountUses, static::rowToVCouponAccountUse($row));
        }

        return $couponAccountUses;
    }

    private static function createSelectTableForGetCouponAccountUsesForCouponArray(array $aggregatedCoupons, vAccount $account, array &$params) : string
    {
        if(count($aggregatedCoupons) <= 0) throw new InvalidArgumentException("\$aggregatedCoupons array must have at least one element");

        $selectTable = "";

        for($i = 0; $i < count($aggregatedCoupons); $i++)
        {
            $coupon = ($aggregatedCoupons[$i])["coupon"];
            $quantity = ($aggregatedCoupons[$i])["quantity"];

            if($i === 0)
            {
                $selectTable .= "(SELECT ? as coupon_ctime, ? as coupon_crand, ? as account_ctime, ? as account_crand, ? as quantity)";
                array_push($params, $coupon->ctime, $coupon->crand, $account->ctime, $account->crand, $quantity);
                continue;
            }

            $selectTable .= "UNION ALL (SELECT ?, ?, ?, ?, ?)";
            array_push($params, $coupon->ctime, $coupon->crand, $account->ctime, $account->crand, $quantity);
        }

        return $selectTable;
    }

    private static function aggregateCouponsInCart(array $coupons) : array
    {
        $couponsWithQuantity = [];

        foreach($coupons as $coupon)
        {
            $foundGroup = false;

            for($i = 0; $i < count($couponsWithQuantity); $i++)
            {
                $group = $couponsWithQuantity[$i];
                $groupCoupon = $group["coupon"];

                if($groupCoupon->ctime == $coupon->ctime
                && $groupCoupon->crand == $coupon->crand)
                {
                    $couponsWithQuantity[$i] = [
                        "coupon"=>$coupon, 
                        "quantity"=>$group["quantity"]++
                    ];

                    $foundGroup = true;

                    break;
                }
            }

            if(!$foundGroup)
            {
                array_push($couponsWithQuantity, ["coupon"=>$coupon, "quantity"=>1]);
            }
        }

        return $couponsWithQuantity;
    }

    private static function returnAllCouponsInCart(vCart $cart) : array
    {
        $coupons = [];
        $groupIds = [];

        foreach($cart->cartProducts as $product)
        {
            if(is_null($product->coupon)) continue;

            $couponGroupAlreadyLogged = false;

            for($i = 0; $i < count($groupIds); $i++)
            {
                $groupId = $groupIds[$i];

                if($groupId->ctime === $product->couponGroupAssignmentId->ctime 
                && $groupId->crand === $product->couponGroupAssignmentId->crand)
                {
                    $couponGroupAlreadyLogged = true;
                    break;
                }
            }

            if(!$couponGroupAlreadyLogged)
            {
                array_push($groupIds, $product->couponGroupAssignmentId);
                array_push($coupons, $product->coupon);
            }
        }

        return $coupons;
    }

    private static function markCartProductPricesAsCheckedOut(vCart $cart) : void
    {
        $params = [];
        $selectTable = static::createSelectTableForMarkCartProductPricesAsCheckedOut($cart->cartProducts, $params);
        $sql = "UPDATE cart_product_price_link cppl
        JOIN ($selectTable) pl ON cppl.ref_cart_product_link_ctime = pl.ctime AND cppl.ref_cart_product_link_crand = pl.crand
        SET checked_out = 1
        WHERE removed = 0 AND checked_out = 0;";


        $result = Database::executeSqlQuery($sql, $params);

        if(!$result) throw new Exception("result returned false while marking cart product price links as checked out");
    }

    private static function createSelectTableForMarkCartProductPricesAsCheckedOut(array $products, array &$params) : string
    {
        $selectTable = "";

        for($i = 0; $i < count($products); $i++)
        {
            $product = $products[$i];
            if($i === 0)
            {
                $selectTable .= "(SELECT ? as `ctime`, ? as `crand`)";
                array_push($params, $product->ctime, $product->crand);
                continue;
            }

            $selectTable .= "UNION ALL (SELECT ?, ?)";
            array_push($params, $product->ctime, $product->crand);
        }

        return $selectTable;
    }

    private static function markCartProductsAsCheckedOut(vCart $cart) : void
    {
        $params = [];
        $selectTable = static::createSelectTableForMarkCartProductsAsCheckedOut($cart->cartProducts, $params);
        $sql = "UPDATE cart_product_link cpl JOIN ($selectTable) cp ON cp.ctime = cpl.ctime AND cp.crand = cpl.crand SET checked_out = 1;";

        $result = database::executeSqlQuery($sql, $params);

        if(!$result) throw new Exception("result returned false while attempting to mark cart products as checked out");
    }

    private static function createSelectTableForMarkCartProductsAsCheckedOut(array $cartProducts, array &$params) : string
    {
        $selectTable = "";

        for($i = 0; $i < count($cartProducts); $i++)
        {
            $cartProduct = $cartProducts[$i];

            if($i === 0)
            {
                $selectTable = "(SELECT ? as ctime, ? as crand)";
                array_push($params, $cartProduct->ctime, $cartProduct->crand);
                continue;
            }

            $selectTable = "UNION ALL (SELECT ?, ?)";
            array_push($params, $cartProduct->ctime, $cartProduct->crand);
        }

        return $selectTable;
    }

    private static function markCartAsCheckedOut(vCart $cart) : void
    {
        $sql = "UPDATE cart SET checked_out = 1 WHERE ctime = ? AND crand = ?";
        $params = [$cart->ctime, $cart->crand];
        $result = Database::executeSqlQuery($sql, $params);

        if(!$result) throw new Exception("result returned false while marking cart as checked out");
    }

    private static function materializePriceReservations(array $priceReservations) : Response
    {
        $resp = new Response(false, "unkowner error in materializing price reservation", null);

        try
        {
            $params = [];
            $selectTable = static::createSelectTableForMaterializePriceReservations($priceReservations, $params);

            $sql = "
                WITH priceReservations (loot_id, quantity) AS(
                    $selectTable
                )

                SELECT 
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
                CAST(pr.quantity AS signed) as quantity,
                vi.name,
                vi.is_fungible
                FROM v_loot_item vli
                JOIN v_item_info vi ON vli.item_id = vi.Id
                JOIN priceReservations pr on pr.loot_id = vli.Id
            ";

            //throw new Exception($sql. " | ".json_encode($params));

            $result = Database::executeSqlQuery($sql, $params);

            if(!$result) throw new Exception("result returned false attempting to materialize price reservations");

            $loots = [];

            while($row = $result->fetch_assoc())
            {
                $loot = LootController::row_to_vLoot($row);
                array_push($loots, $loot);
            }

            $resp->success = true;
            $resp->message = "price reservations materialized";
            $resp->data = $loots;
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while attempting to materialize price reservations : $e");
        }

        return $resp;
    }

    private static function createSelectTableForMaterializePriceReservations(array $priceReservations, array &$params) : string
    {
        $selectTable = "";

        for($i = 0; $i < count($priceReservations); $i ++)
        {
            $reservation = $priceReservations[$i];

            if($i !== 0)
            {
                $selectTable .= " UNION ALL ";
            }

            $selectTable .= "SELECT ?, ?";

            array_push($params, $reservation->lootId->crand, $reservation->quantity);
        }

        return $selectTable;
    }


    /**
     * Gets loot for the provided product reservations, reserving the loot returned
     * 
     * @param array $productReservations the product reservations which will be materialized into vLoots
     * 
     * @return Response the response object whose data will hold the vLoots
     */
    private static function materializeProductReservations(array $productReservations, array &$productLootReservations) : Response
    {
        $resp = new Response(false, "unkown error in materializing product reservations", null);

        try
        {   
            $params = [];
            $valueClause = static::createValueClauseForMaterializeProductReservations($productReservations, $productLootReservations, $params);
            $sql = "INSERT INTO loot_reservation (ctime, crand, ref_loot_ctime, ref_loot_crand, quantity, expiry_time, close_time) $valueClause";

            $result = Database::executeSqlQuery($sql, $params);

            if(!$result) throw new Exception("result returned false while attempting to insert loot reservations from materialized product reservations");

            $materializedLoots = static::getLootFromMaterializedLootReservations($productLootReservations);

            $resp->success = true;
            $resp->message = "returned materialized loots from product reservations";
            $resp->data = $materializedLoots;
        }
        catch(Exception $e)
        {
            throw new Exception("exception caught while materializing product reseravations : $e");
        }

        return $resp;
    }

    private static function getLootFromMaterializedLootReservations(array $lootReservations) : array
    {
        $loots = [];

        $params = [];
        $whereClause = static::createWhereClauseForGetLootFromMaterializedLootReservations($lootReservations, $params);

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
        vli.quantity,
        vi.Id as `item_id`,
        vi.name,
        vi.is_fungible
        FROM v_loot_reservation vlr
        JOIN v_loot_item vli ON vlr.loot_crand = vli.Id
        JOIN v_item_info vi ON vli.item_id = vi.Id
        WHERE $whereClause";

        $result = Database::executeSqlQuery($sql, $params);

        if(!$result) throw new Exception("result returned false while attempting to get materialized loot reservations");

        while($row = $result->fetch_assoc())
        {
            $loot = LootController::row_to_vLoot($row);
            array_push($loots, $loot);
        }

        return $loots;
    }

    private static function createWhereClauseForGetLootFromMaterializedLootReservations(array $reservations, array &$params) : string
    {
        if(count($reservations) < 0) throw new InvalidArgumentException("\$reservations array must contain at least one element");

        $whereClause = "";

        for($i = 0; $i < count($reservations); $i++)
        {
            $reservation = $reservations[$i];

            if($i !== count($reservations)-1) $whereClause .= " OR ";

            $whereClause .= "(vlr.ctime = ? AND vlr.crand = ?)";

            array_push($params, $reservation->ctime, $reservation->crand);
        }

        return $whereClause;
    }

    private static function createValueClauseForMaterializeProductReservations(array $productReservations, array &$lootReservations, array &$params) : string
    {
        $valueClause = "";

        for($i = 0; $i < count($productReservations); $i++)
        {
            $productReservation = $productReservations[$i];

            if($i !== 0) $valueClause .= " UNION ALL ";

            $lootReservation = new lootReservation();

            $expiryTime = new DateTime($lootReservation->ctime);
            $expiryTime->modify("+" . static::$productReservationTimeInSeconds . " seconds");
            $lootReservation->expiryTime = $expiryTime;

            $valueClause .= "(SELECT ? as 'ctime', ? as 'crand', '0000-00-00 00:00:00' as 'ref_loot_ctime', 
            (SELECT l.Id FROM loot l 
            JOIN product_loot_link pll ON pll.ref_loot_crand = l.Id 
            LEFT JOIN v_loot_reservation_total rlt ON rlt.loot_crand = l.Id 
            WHERE (COALESCE(rlt.quantity_available, l.quantity) >= ?  OR rlt.quantity_available IS NULL) AND pll.ref_product_ctime = ? AND pll.ref_product_crand = ? LIMIT 1) as 'ref_loot_crand',
            ? as 'quantity',
            ? as 'expiry_time',
            null as 'close_time')";

            $formattedExpiryTime = $expiryTime->format("Y-m-d H:i:s.u");
            array_push($params,
                $lootReservation->ctime,
                $lootReservation->crand,
                $productReservation->quantity,
                $productReservation->productId->ctime,
                $productReservation->productId->crand,
                $productReservation->quantity,
                $formattedExpiryTime
            );

            array_push($lootReservations, $lootReservation);
        }

        return $valueClause;
    }

    
    private static function executeQueriesToTransactMaterializedLootsForProducts(vCart $cart, array $loots) : void
    {
        $fungibleLoots = [];
        $nonFungibleLoots = [];

        static::seperateLootsByFungiblity($loots, $fungibleLoots, $nonFungibleLoots);

        if(count($fungibleLoots) > 0)static::transactFungibleLoots($cart->store->owner, $cart->account, $fungibleLoots);
        if(count($nonFungibleLoots) > 0)static::transactNonFungibleLoot($cart->account, $nonFungibleLoots);
    }

    private static function transactNonFungibleLoot(vAccount $newOwner, array $loots) : void
    {
        $params = [];
        $valueClause = static::createValueClauseForTransactNonFungibleLoot($newOwner, $loots, $params);
        $sql = "UPDATE loot l JOIN ($valueClause) la ON la.loot_id = l.Id SET l.account_Id = la.account_id";

        $result = Database::executeSqlQuery($sql, $params);

        if(!$result) throw new Exception("result returned false while transacting non fungible loot");
    }

    private static function createValueClauseForTransactNonFungibleLoot(vAccount $newOwner, array $loots, array &$params) : string
    {
        $valueClause = "";

        $params = [];

        for($i = 0; $i < count($loots); $i++)
        {
            $loot = $loots[$i];

            if($i === 0)
            {
                $valueClause .= "(SELECT ? as account_id, ? as loot_id)";
                array_push($params, $newOwner->crand, $loot->crand);
                continue;
            }

            $valueClause .= " UNION ALL (SELECT ?,?)";
            array_push($params, $newOwner->crand, $loot->crand);
        }

        return $valueClause;
    }

    private static function transactFungibleLoots(vAccount $fromAccount, vAccount $toAccount, array $loots) : void
    {
        static::transactFungibleLootsToAccount($loots, $toAccount);

        static::transactFungibleLootsFromAccount($loots, $fromAccount);
    }

    private static function transactFungibleLootsFromAccount(array $loots, vAccount $fromAccount) : void
    {
        $params = [];
        $valueClause = static::createValueClauseForTransactFungibleLootsFromAccount($loots, $fromAccount, $params);

        $sql = "UPDATE loot l JOIN ($valueClause) lu ON l.Id = lu.loot_id SET l.quantity = l.quantity - lu.quantity;";

        $result = Database::executeSqlQuery($sql, $params);
        
        if(!$result) throw new Exception("result returned false while attempting to transact fungible loot from account");
    }

    private static function createValueClauseForTransactFungibleLootsFromAccount(array $loots, vAccount $fromAccount, array &$params) : string
    {
        $valueClause ="";

        for($i = 0; $i < count($loots); $i++)
        {
            $loot = $loots[$i];

            if($i === 0)
            {
                $valueClause .= "SELECT ? as 'loot_id', ? as 'quantity'";

                array_push($params, $loot->crand, $loot->quantity);
                
                continue;
            }

            $valueClause .= "UNION ALL SELECT ?, ?";

            array_push($params, $loot->crand, $loot->quantity);
        }

        return $valueClause;
    }

    private static function transactFungibleLootsToAccount(array $loots, vAccount $toAccount) : void
    {
        $params = [];
        $valueClause = static::createValueClauseForTransactFungibleLootsToAccount($toAccount, $loots, $params);

        $sql = "UPDATE loot AS l
            JOIN ($valueClause)AS i
            ON  l.account_id = i.account_id
            AND l.item_id    = i.item_id
            SET l.quantity = l.quantity + i.quantity;
        ";

        $result = Database::executeSqlQuery($sql, $params);

        if(!$result) throw new Exception("result returned false while attempting to transact fungible loot to account");

        $sql = "INSERT INTO loot
            (Id, `description`, account_id, item_id, quest_id, dateObtained, redeemed, container_loot_id, quantity, Opened)
            SELECT
            i.Id, i.`description`, i.account_id, i.item_id, i.quest_id, NOW(), 1, NULL, i.quantity, 0
            FROM ($valueClause) AS i
            LEFT JOIN loot AS l
            ON  l.account_id = i.account_id
            AND l.item_id    = i.item_id
            WHERE l.account_id IS NULL;
        ";

        $result = Database::executeSqlQuery($sql, $params);

        if(!$result) throw new Exception("result returned false while attempting to transact fungible loot to account");
    }


    private static function createValueClauseForTransactFungibleLootsToAccount(vAccount $account, array $loots, array &$params) : string
    {
        $valueClause = "";

        for($i = 0; $i < count($loots); $i++)
        {
            $loot = $loots[$i];

            $insertId = new RecordId();

            if($i === 0)
            {
                $valueClause .= "(SELECT ? as Id, ? as `description`, ? as account_id, ? as item_id, ? as quest_id, ? as quantity)";

                $questId = is_null($loot->quest) ? null : $loot->quest->crand;
                array_push($params, $insertId->crand, $loot->description, $account->crand, $loot->item->crand, $questId, $loot->quantity);
                continue;
            }

            $valueClause .= " UNION ALL (SELECT ?,?,?,?,?,?)";

            $questId = is_null($loot->quest) ? null : $loot->quest->crand;
            array_push($params, $insertId->crand, $loot->description, $account->crand, $loot->item->crand, $questId, $loot->quantity);
        }

        return $valueClause;
    }

    private static function seperateLootsByFungiblity(array $loots, array &$fungibleLoots, array &$nonFungibleLoots) : void
    {
        foreach($loots as $loot)
        {
            if($loot->item->fungible)
            {
                array_push($fungibleLoots, $loot);
            }
            else
            {
                array_push($nonFungibleLoots, $loot);
            }
        }
    }

    //reassign price loots to seller
    private static function executeQueriesToTransactMaterializedLootsForPrices(vCart $cart, array $priceLoots) : void
    {
        $fungibleLoots = [];
        $nonFungibleLoots = [];

        static::seperateLootsByFungiblity($priceLoots, $fungibleLoots, $nonFungibleLoots);

        if(count($fungibleLoots) > 0)static::transactFungibleLoots($cart->account, $cart->store->owner, $fungibleLoots);
        if(count($nonFungibleLoots) > 0)static::transactNonFungibleLoot($cart->store->owner, $nonFungibleLoots);
    }

    //create trade entries for both price and product reservations
    private static function executeQueriesToCreateTradeEntriesForReservations(vCart $cart, array $productLoots, array $priceLootReservations) : void
    {
        $buyer = $cart->account;
        $seller = $cart->store->owner;

        static::createTradeEntriesForProductReservations($buyer, $seller, $productLoots);
        static::createTradeEntriesForPriceReservations($buyer, $seller, $priceLootReservations);
    } 

    private static function createTradeEntriesForProductReservations(vAccount $buyer, vAccount $seller, array $productLoots) : void
    {
        $params = [];
        $valueClause = static::createValueClauseForCreateTradeEntriesForProductReservations($buyer, $seller, $productLoots, $params);

        $sql = "INSERT INTO trade (id, from_account_id, to_account_id, loot_id, from_account_obtain_date, quantity) $valueClause";

        $result = database::executeSqlQuery($sql, $params);

        if(!$result) throw new Exception("result returned false attempting to create trade enteries for product reservations");
    }

    private static function createValueClauseForCreateTradeEntriesForProductReservations(vAccount $buyer, vAccount $seller, array $productLoots, array &$params) : string
    {
        $valueClause = "";

        for($i = 0; $i < count($productLoots); $i++)
        {
            $loot = $productLoots[$i];

            $trade = new Trade($seller, $buyer, $loot, $loot->quantity);

            if($i === 0)
            {
                $valueClause .= "(SELECT ? AS id, ? AS from_account_id, ? AS to_account_id, ? AS loot_id, ? AS from_account_obtain_date, ? AS quantity)";
                array_push($params, $trade->crand, $trade->fromAccountId->crand, $trade->toAccountId->crand, $loot->crand, $loot->dateObtained->value->format("Y-m-d H:i:s.u"), $loot->quantity);
                continue;
            }

            $valueClause .= "(SELECT ?,?,?,?,?,?)";
            array_push($params, $trade->crand, $trade->fromAccountId->crand, $trade->toAccountId->crand, $loot->crand, $loot->dateObtained->format("Y-m-d H:i:s.u"), $loot->quantity);
        }

        return $valueClause;
    }
    
    private static function createTradeEntriesForPriceReservations(vAccount $buyer, vAccount $seller, array $priceLootReservations) : void
    {
        $params = [];
        $valueClause = static::createValueClauseForCreateTradeEntriesForPriceReservations($buyer, $seller, $priceLootReservations, $params);
        $sql = "INSERT INTO trade (id, from_account_id, to_account_id, loot_id, trade_date, from_account_obtain_date, quantity) $valueClause;";

        $result = Database::executeSqlQuery($sql, $params);

        if(!$result) throw new Exception("Result returned false attempting to create trade entries for price reservations");
    }

    private static function createValueClauseForCreateTradeEntriesForPriceReservations(vAccount $buyer, vAccount $seller, array $priceLootReservations, array &$params) : string
    {
        $valueClause = "";

        for($i = 0; $i < count($priceLootReservations); $i ++)
        {
            $reservation = $priceLootReservations[$i];

            $trade = new trade($buyer, $seller, $reservation->lootId, $reservation->quantity);

            if($i === 0)
            {
                $valueClause .= "(SELECT ? AS id, ? as from_account_id, ? as to_account_id, ? as loot_id, ? as trade_date, (SELECT dateObtained FROM loot WHERE id = ? LIMIT 1) as from_account_obtain_date, ? as quantity)";
                array_push($params, $trade->crand, $trade->fromAccountId->crand, $trade->toAccountId->crand, $trade->lootId->crand, $trade->ctime, $trade->lootId->crand, $trade->quantity);
                continue;
            }

            $valueClause .= "UNION ALL (SELECT ?, ?, ?, ?, ?, (SELECT dateObtained FROM loot WHERE id = ? LIMIT 1), ?)";
            array_push($params, $trade->crand, $trade->fromAccountId->crand, $trade->toAccountId->crand, $trade->lootId->crand, $trade->ctime, $trade->lootId->crand, $trade->quantity);
            continue;
        }

        return $valueClause;
    }

    //remove product loots as linked stock of their products
    private static function executeQueriesToRemoveProductLootLinkForReservations(array $productLoots) : void
    {
        $params = [];
        $valueClause = static::createValueCreateForExecuteQueriesToRemoveProductLootLinkForReservations($productLoots, $params);
        $sql = "UPDATE product_loot_link ppl JOIN ($valueClause) pl ON pl.loot_id = ppl.ref_loot_crand SET removed = 1";

        $result = Database::executeSqlQuery($sql, $params);

        if(!$result) throw new Exception("result returned false while executing queries to remove product loot link for reserverations");
    }

    private static function createValueCreateForExecuteQueriesToRemoveProductLootLinkForReservations(array $productLoots, array &$params) : string
    {
        $valueClause = "";
        $params = [];

        for($i = 0; $i < count($productLoots); $i++)
        {
            $loot = $productLoots[$i];
            if($i === 0)
            {
                $valueClause .= "SELECT ? as loot_id";
                array_push($params, $loot->crand);
                continue;
            }

            $valueClause .= "SELECT ?";
            array_push($params, $loot->crand);
        }

        return $valueClause;
    }

    //close product reservations
    private static function executeQueriesToCloseProductReservations(array $productReservations) : void
    {
        $now = date("Y-m-d H:i:s.u");

        $params = [];
        $valueClause = static::createValueClauseForExecuteQueriesToCloseProductReservations($productReservations, $params);
        $sql = "UPDATE product_reservation pr JOIN ($valueClause) r ON r.ctime = pr.ctime AND r.crand = pr.crand SET close_time = '$now'";

        $result = Database::executeSqlQuery($sql, $params);

        if(!$result) throw new Exception("result returned false while executing queries to close product reservations");
    }

    private static function createValueClauseForExecuteQueriesToCloseProductReservations(array $reservations, array &$params) : string
    {
        $valueClause = "";

        for($i = 0; $i < count($reservations); $i++)
        {
            $reservation = $reservations[$i];

            if($i === 0)
            {
                $valueClause .= "(SELECT ? AS ctime, ? as crand)";
                array_push($params, $reservation->ctime, $reservation->crand);
                continue;
            }

            $valueClause .= "UNION ALL (SELECT ?,?)";
            array_push($params, $reservation->ctime, $reservation->crand);
        }

        return $valueClause;
    }

    //close price loot reservations
    private static function executeQueriesToCloseLootReservations(array $lootReservations) : void
    {
        $params = [];
        $selectTable = static::createSelectTableForExecuteQueriesToCloseLootReservations($lootReservations, $params);
        $sql = "UPDATE loot_reservation lr JOIN ($selectTable) st ON st.ctime = lr.ctime AND st.crand = lr.crand SET close_time = NOW()";

        $result = Database::executeSqlQuery($sql, $params);

        if(!$result) throw new exception("result returned false while executing queries to close loot reservations");
    }

    private static function createSelectTableForExecuteQueriesToCloseLootReservations(array $lootReservations, array &$params) : string
    {
        $selectTable = "";

        for($i = 0; $i < count($lootReservations); $i++)
        {
            $reservation = $lootReservations[$i];

            if($i === 0)
            {
                $selectTable .= "(SELECT ? as ctime, ? as crand)";
                array_push($params, $reservation->ctime, $reservation->crand);
                continue;
            }
            
            $selectTable .= "UNION ALL (SELECT ?, ?)";
            array_push($params, $reservation->ctime, $reservation->crand);
        }

        return $selectTable;
    }

    private static function consolidateLootForCartTotals(array $cartTotals, array $lootForTotals) : array
    {
        $consolidatedLoot = [];

        foreach($cartTotals as $total)
        {
            if(is_null($total->item) || $total->amount === 0) continue;

            $totalAmount = $total->amount;

            for($i = 0; $i < count($lootForTotals); $i++)
            {
                $loot = $lootForTotals[$i];
  
                
                //Add the loot if it matches the prices; adding up to the amount in the total for fungible loots
                if($loot->item->crand !== $total->item->crand)
                {
                    if($i === count($lootForTotals)-1 && $totalAmount !== 0) throw new RuntimeException("Cart owner did not have enough loot to satisfy cart totals");

                    continue; 
                } 

                $lootAmountToAddToConsolidation = $totalAmount < $loot->quantity ? $totalAmount : $loot->quantity;

                $loot->quantity = $lootAmountToAddToConsolidation;

                array_push($consolidatedLoot, $loot);
                $totalAmount -= $lootAmountToAddToConsolidation;

                //Stop for this total if the total is satified
                if($totalAmount === 0) break;

                //Was our maximum differnce operation wrong somehow?
                if($totalAmount < 0) throw new LogicException("More loot than needed was added to the array to satisfy cart totals");

                //Was there enough loot?
                if($i === count($lootForTotals)-1 && $totalAmount !== 0) throw new RuntimeException("Cart owner did not have enough loot to satisfy cart totals");
            }
        }

        return $consolidatedLoot;
    }

    private static function reserveLootForPrices(vCart $cart) : Response
    {
        $resp = new Response(false, "unkowner error in reserving loot for prices", null);

        try
        {   

            $valueClause = static::returnValueClauseForReserveLootForPrices($cart->totals);
            $sql = "INSERT INTO loot_reservation (ctime,
                crand,
                ref_loot_ctime,
                ref_loot_crand,
                quantity,
                expiry_time,
                close_time) 
                VALUES ($valueClause)
            ";

            // Get the loot which matches needed items for prices
            $lootsForPrices = static::getLootForPricesForCart($cart); 

            // Consolidate loot to only the amount needed for the totals
            $consolidatedLootForCartTotals = static::consolidateLootForCartTotals($cart->totals, $lootsForPrices); 

            $params = static::returnParamsForReserveLootForPrices($cart->totals, $consolidatedLootForCartTotals); 

            $result = Database::executeSqlQuery($sql, $params);

            if(!$result) throw new Exception("Result returned false attempting to reserve loots for prices");

            $reservations = static::getLootReservationsAfterInsertion($params);

            $resp->success = true;
            $resp->message = "Loot for prices are reserved";
            $resp->data = $reservations;
            
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while reserving loot for prices : $e");
        }

        return $resp;
    }

    private static function getLootReservationsAfterInsertion(array $insertionParameters) : array
    {
        $reservations = [];

        for($i = 0; $i < count($insertionParameters); $i += 7)
        {
            /**
             * [0] $reservation->ctime
             * [1] $reservation->crand
             * [2] $reservation->lootId->ctime
             * [3] $reservation->lootId->crand
             * [4] $reservation->quantity
             * [5] $formattedExpiryTime
             * [6] null
             */
            $lootId = new vRecordId($insertionParameters[$i+2], $insertionParameters[$i+3]);
            $expiryTime = Datetime::createFromFormat('Y-m-d H:i:s.u',$insertionParameters[$i+5]);

            $reservation = new vLootReservation(
                $insertionParameters[$i],
                $insertionParameters[$i+1],
                $lootId,
                $insertionParameters[$i+4],
                $expiryTime,
                null
            );

            array_push($reservations, $reservation);
        }

        return $reservations;
    }

    private static function returnParamsForReserveLootForPrices(array $cartTotals, array $cartOwnerCartLoot) : array
    {
        $params = [];

        for($i = 0; $i < count($cartTotals); $i++)
        {
            $total = $cartTotals[$i];
            if(is_null($total->item) || $total->amount === 0) continue;

            //Go through the loots, adding their fields as parameters until the cart total is satisfied
            for($i = 0; $i < count($cartOwnerCartLoot); $i++)
            {
                if($total->amount <= 0) break;

                $loot = $cartOwnerCartLoot[$i];

                if($loot->item->crand !== $total->item->crand)
                {
                    if($i === count($cartOwnerCartLoot)-1 && $total->amount > 0) throw new RuntimeException("Buyer did not have loot for total");
                    continue;
                } 

                $amountOfLootToUse = $total->amount < $loot->quantity ? $total->amount : $loot->quantity;

                $reservation = new LootReservation(
                    $loot,
                    $amountOfLootToUse,
                    null,
                    null
                );

                $expiryTime = new DateTime($reservation->ctime);
                $expiryTime->modify("+" . static::$productReservationTimeInSeconds . " seconds");
                $formattedExpiryTime = $expiryTime->format("Y-m-d H:i:s.u");

                array_push($params,
                    $reservation->ctime,
                    $reservation->crand,
                    $reservation->lootId->ctime,
                    $reservation->lootId->crand,
                    $reservation->quantity,
                    $formattedExpiryTime,
                    null
                );

                $total->amount -= $amountOfLootToUse;
                if($i === count($cartOwnerCartLoot)-1 && $total->amount > 0) throw new RuntimeException("Buyer did not have loot for total");
            }

            if($total->amount > 0)throw new LogicException("Total was not satisfied and exception was not thrown");
      
        }

        return $params;
    }

    private static function returnValueClauseForReserveLootForPrices(array $cartPrices) : string
    {
        if(count($cartPrices) === 0) throw new InvalidArgumentException("\$cartPrices array must contain at least one element");

        $valueClause = "";

        for($i = 0; $i < count($cartPrices); $i++)
        {
            $valueClause .= "?,?,?,?,?,?,?";

            if($i != count($cartPrices)-1) $valueClause .= ",";
        }

        return $valueClause;
    }

    private static function unittest_returnValueClauseForReserveLootForPrices() : void
    {
        $cartProducts = [0];
        assert("?,?,?,?,?,?,?" === static::returnValueClauseForReserveLootForPrices($cartProducts), 
        new Exception("UNIT TEST FAILED : Expected '?,?,?,?,?,?,?' | Actual : '".static::returnValueClauseForReserveLootForPrices($cartProducts))."'");
        $cartProducts = [0,0];
        assert("?,?,?,?,?,?,?,?,?,?,?,?,?,?" === static::returnValueClauseForReserveLootForPrices($cartProducts), 
        new Exception("UNIT TEST FAILED : Expected '?,?,?,?,?,?,?,?,?,?,?,?,?,?' | Actual : '".static::returnValueClauseForReserveLootForPrices($cartProducts))."'");
        $cartProducts = [0,0,0];
        assert("?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?" === static::returnValueClauseForReserveLootForPrices($cartProducts), 
        new Exception("UNIT TEST FAILED : Expected '?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?' | Actual : '".static::returnValueClauseForReserveLootForPrices($cartProducts))."'");
    }


    /**
     * Reserves the products in the cart. Creating reservation records in the database, if there are enough available stock of the product to be reserved
     * 
     * @param vCart $cart the cart for which its product items will be reserved
     * 
     * @return Response the response object whose data will hold the records of the reservations made if reserving succeeded; 
     * otherwise, it will hold the unavailable products that were attempted to be reserved
     */
    public static function ReserveProductStock(vCart $cart) : Response
    {
       $resp = new Response(false, "unkown error in reserving stock", null);

        try
        {
            //Are items still in stock
            $unavailableCartProducts = [];
            $areCartItemsInStockResp = static::areSufficentCartProductsAvailable($cart, $unavailableCartProducts);

            if(!$areCartItemsInStockResp->success)
            {
                $resp->message = "Error in checking if cart item are still in-stock : $areCartItemsInStockResp->message";
                return $resp;
            }

            if(!$areCartItemsInStockResp->data)
            {
                $resp->message = "Some items in cart are out-of-stock";
                $resp->data = $unavailableCartProducts;
                return $resp;
            }

            $valueClause = static::returnValueClauseForReservingProductStock($cart->cartProducts);
            $sql = "INSERT INTO product_reservation (
            ctime,
            crand,
            ref_cart_ctime,
            ref_cart_crand,
            ref_product_ctime,
            ref_product_crand,
            quantity,
            expiry_time,
            close_time) 
            VALUES $valueClause"; 

            $params = static::returnParamsForReservingProductStock($cart);

            $result = Database::executeSqlQuery($sql, $params);

            if(!$result) throw new Exception("result returned false");  

            $reservations = static::convertParamsForReservingProductsToViews($params);

            $resp->success = true;
            $resp->message = "Reserved the cartProducts";
            $resp->data = $reservations;
        }
        catch(Exception $e)
        {
            throw new exception("Exception caught while trying to reserve loot : $e");
        }

       return $resp;
    }

    private static function convertParamsForReservingProductsToViews(array $params) : array
    {
        $reservations = [];

        for($i = 0; $i < count($params); $i += 9)
        {
            /**    
            *  Each field of the reservation is inserted in the $params array as follows
            *  [0] - $reservation->crand
            *  [1] - $reservation->crand
            *  [2] - $reservation->cartId->ctime
            *  [3] - $reservation->cartId->crand
            *  [4] - $reservation->productId->ctime
            *  [5] - $reservation->productId->crand
            *  [6] - $reservation->quantity
            *  [7] - $reservation->expiryTime?->format("Y-m-d H:i:s:u")
            *  [8] - $reservation->closeTime?->format("Y-m-d H:i:s:u")
            */

            $cartId = new vRecordId($params[$i+2],$params[$i+3]);
            $productId = new vRecordId($params[$i+4],$params[$i+5]);
            $quantity = $params[$i+6];
            $expiryTime = is_null($params[$i+7]) ? null : DateTime::createFromFormat("Y-m-d H:i:s:u", $params[$i+7]);
            $closeTime = is_null($params[$i+8]) ? null : DateTime::createFromFormat("Y-m-d H:i:s:u", $params[$i+8]);

            $reservation = new vProductReservation(
                $params[$i],
                $params[$i+1],
                $cartId,
                $productId,
                $quantity,
                $expiryTime,
                $closeTime
            );

            array_push($reservations, $reservation);
        }

        return $reservations;
    }

    private static function returnParamsForReservingProductStock(vCart $cart, ?bool $returnSliceTestingArray = false) : array
    {
        $params = [];

        if (count($cart->cartProducts) <= 0) {
            throw new InvalidArgumentException("cartProducts array must have at least one element");
        }

        foreach ($cart->cartProducts as $cartProduct) 
        {

            //throw new Exception(json_encode($cartProduct));
            $reservation = new ProductReservation(
                $cart,                
                $cartProduct->product,
                1,                    
                null,          
                null                  
            );

            $expiryTime = new DateTime($reservation->ctime);
            $expiryTime->modify("+" . static::$productReservationTimeInSeconds . " seconds");
            $reservation->expiryTime = $expiryTime;

            if (!$returnSliceTestingArray) 
            {
                array_push(
                    $params,
                    $reservation->ctime,
                    $reservation->crand,
                    $reservation->cartId->ctime,
                    $reservation->cartId->crand,
                    $reservation->productId->ctime,
                    $reservation->productId->crand,
                    $reservation->quantity,
                    $reservation->expiryTime?->format("Y-m-d H:i:s:u"),
                    $reservation->closeTime?->format("Y-m-d H:i:s:u")
                );
            } 
            else 
            {
                array_push(
                    $params,
                    $reservation->cartId->ctime,
                    $reservation->cartId->crand,
                    $reservation->productId->ctime,
                    $reservation->productId->crand
                );
            }
        }

        return $params;
    }

    private static function unittest_returnParamsForReservingProductStock() : void
    {
        $cart = new vCart();
        $productId = new vProduct('a', -2);
        $otherProductId = new vProduct('b', -3);  
        
        $cartProduct = new vCartItem();
        $cartProduct->product = $productId;
        $otherCartProduct = new vCartItem();
        $otherCartProduct->product = $otherProductId;

        $cart->cartProducts = [$cartProduct];
        assert([$cart->ctime, $cart->crand, $productId->ctime, $productId->crand] === static::returnParamsForReservingProductStock($cart, true), 
        new Exception("UNIT TEST FAILED : returned parameters did not match expected | 
        expected : ".json_encode([$cart->ctime, $cart->crand, $productId->ctime, $productId->crand])."
        actual : ".json_encode(static::returnParamsForReservingProductStock($cart, true))));

        $cart->cartProducts = [$otherCartProduct];
        assert([$cart->ctime, $cart->crand, $otherProductId->ctime, $otherProductId->crand] === static::returnParamsForReservingProductStock($cart, true), 
        new Exception("UNIT TEST FAILED : returned parameters did not match expected | 
        expected : ".json_encode([$cart->ctime, $cart->crand, $otherProductId->ctime, $otherProductId->crand])."
        actual : ".json_encode(static::returnParamsForReservingProductStock($cart, true))));

        $cart->cartProducts = [$cartProduct, $otherCartProduct];
        assert([$cart->ctime, $cart->crand, $productId->ctime, $productId->crand, $cart->ctime, $cart->crand, $otherProductId->ctime, $otherProductId->crand] === static::returnParamsForReservingProductStock($cart, true), 
        new Exception("UNIT TEST FAILED : returned parameters did not match expected | 
        expected : ".json_encode([$cart->ctime, $cart->crand, $productId->ctime, $productId->crand, $cart->ctime, $cart->crand, $otherProductId->ctime, $otherProductId->crand])."
        actual : ".json_encode(static::returnParamsForReservingProductStock($cart, true))));

        $cart->cartProducts = [$cartProduct, $otherCartProduct, $cartProduct];
        assert([$cart->ctime, $cart->crand, $productId->ctime, $productId->crand, $cart->ctime, $cart->crand, $otherProductId->ctime, $otherProductId->crand, $cart->ctime, $cart->crand, $productId->ctime, $productId->crand] === static::returnParamsForReservingProductStock($cart, true), 
        new Exception("UNIT TEST FAILED : returned parameters did not match expected | 
        expected : ".json_encode([$cart->ctime, $cart->crand, $productId->ctime, $productId->crand, $cart->ctime, $cart->crand, $otherProductId->ctime, $otherProductId->crand, $cart->ctime, $cart->crand, $productId->ctime, $productId->crand])."
        actual : ".json_encode(static::returnParamsForReservingProductStock($cart, true))));
    }



    private static function returnValueClauseForReservingProductStock(array $cartProducts) : string
    {
        $valueClause = "";

        for($i = 0; $i < count($cartProducts); $i++)
        {
            $valueClause .= "(?,?,?,?,?,?,?,?,?)";

            if($i !== count($cartProducts)-1) $valueClause .= ", ";
        }

        return $valueClause;
    }

    private static function unittest_returnValueClauseForReservingProductStock() : void
    {
        assert("(?,?,?,?,?,?,?,?,?)" === static::returnValueClauseForReservingProductStock([null]), 
        new Exception("UNIT TEST FAILED | expected : '(?,?,?,?,?,?,?,?,?)' | actual : '".static::returnValueClauseForReservingProductStock([null])."'"));

        assert("(?,?,?,?,?,?,?,?,?), (?,?,?,?,?,?,?,?,?)" === static::returnValueClauseForReservingProductStock([null,null]), 
        new Exception("UNIT TEST FAILED | expected : '(?,?,?,?,?,?,?,?,?), (?,?,?,?,?,?,?,?,?)' | actual : '".static::returnValueClauseForReservingProductStock([null,null])."'"));

        assert("(?,?,?,?,?,?,?,?,?), (?,?,?,?,?,?,?,?,?), (?,?,?,?,?,?,?,?,?)" === static::returnValueClauseForReservingProductStock([null,null,null]), 
        new Exception("UNIT TEST FAILED | expected : '(?,?,?,?,?,?,?,?,?), (?,?,?,?,?,?,?,?,?), (?,?,?,?,?,?,?,?,?)' | actual : '".static::returnValueClauseForReservingProductStock([null,null,null])."'"));
    }

    /**
     * Checks if the items in the cart are still in stock. This method is more akin to "return any out of stock products which are in the provided cart"
     * @param vCart $cart the cart whose items will be checked if they are still in the store owner's inventory
     * @param array $nonAvailableProducts an optional out parameter which will populate with any non available products that are found
     * 
     * @return Response $resp the response object which will return true or false in its data to if the cart products are in stock and available
     */
    public static function areSufficentCartProductsAvailable(vCart $cart, array &$nonAvailableProducts = []) : Response
    {
        $resp = new Response(false, "unknown error in checking if cart items are still in stock", null);

        try
        {
            $areCartItemsAvailableWhereClause = static::getAreCartItemsAvailableWhereClause($cart->cartProducts);

            $sql = "SELECT ".static::$columnsInProductView."
            FROM v_product
            WHERE store_ctime = ? AND store_crand = ? AND ($areCartItemsAvailableWhereClause)";

            $productParams = static::getAreCartItmesAvailableParameterArray($cart->cartProducts);
            $params = array_merge([$cart->store->ctime, $cart->store->crand], $productParams);

            $result = Database::executeSqlQuery($sql, $params);

            if(!$result) throw new Exception("result returned false for checking if cart items are still available");

            $productsInCart = $result->fetch_all(MYSQLI_ASSOC);

            foreach($productsInCart as $product)
            {
                $matchingCartProductAvailability = null;

                foreach($cart->cartProducts as $cartProduct)
                {
                    $amountToBeBought = 0;

                    if($cartProduct->product->ctime === $product["ctime"] &&
                        $cartProduct->product->crand === $product["crand"]
                    )
                    {
                        $amountToBeBought += 1;
                        $matchingCartProductAvailability = $product["amount_available"];

                        if($matchingCartProductAvailability - $amountToBeBought < 0 || 
                            boolval($product["removed"]) === true)
                        {
                            array_push($nonAvailableProducts, static::rowToVProduct($product));
                        }
                    }
                }
            }

            if(empty($nonAvailableProducts))
            {
                $resp->success = true;
                $resp->message = "There are sufficent linked loot avialable for products in cart";
                $resp->data = true;
            }
            else
            {
                $resp->success = true;
                $resp->message = "One or more products are currently out of availability";
                $resp->data = false;
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while checking if cart items are still available : $e");
        }

        return $resp;
    }

    private static function getAreCartItemsAvailableWhereClause(array $cartItems) : string
    {
        $whereClause = '(';

        foreach($cartItems as $cartItem)
        {
            $whereClause .= "(ctime = ? AND crand = ?) OR ";
        }

        //trim off last ' OR '
        $whereClause = substr($whereClause, 0, -4);

        return $whereClause.")";
    }

    private static function unittest_getAreCartItemsAvailableWhereClause() : void
    {
        assert("((ctime = ? AND crand = ?))" === static::getAreCartItemsAvailableWhereClause([null]), new Exception("UNIT TEST FAILED : where clause for checking if cart items are in stock did not match expected"));
        assert("((ctime = ? AND crand = ?) OR (ctime = ? AND crand = ?))" === static::getAreCartItemsAvailableWhereClause([null,null]), new Exception("UNIT TEST FAILED : where clause for checking if cart items are in stock did not match expected"));
        assert("((ctime = ? AND crand = ?) OR (ctime = ? AND crand = ?) OR (ctime = ? AND crand = ?))" === static::getAreCartItemsAvailableWhereClause([null,null,null]), new Exception("UNIT TEST FAILED : where clause for checking if cart items are in stock did not match expected"));
    }

    private static function getAreCartItmesAvailableParameterArray(array $cartItems) : array
    {
        $params = [];

        foreach($cartItems as $cartItem)
        {
            array_push($params, $cartItem->product->ctime, $cartItem->product->crand);
        }

        return $params;
    }

    private static function unittest_getAreCartItemsAvailableParameterArray() : void
    {
        $cartItem = new vCartItem();
        $cartItem->product = new vProduct('x',-1);
        $otherCartItem = new vCartItem();
        $otherCartItem->product = new vProduct('y',1);

        assert(['x',-1] === static::getAreCartItmesAvailableParameterArray([$cartItem]), new Exception("UNIT TEST FAILED : parameter array for checking if cart items are in stock did not match expected"));
        assert(['y',1] === static::getAreCartItmesAvailableParameterArray([$otherCartItem]), new Exception("UNIT TEST FAILED : parameter array for checking if cart items are in stock did not match expected"));
        assert(['y',1,'x',-1] === static::getAreCartItmesAvailableParameterArray([$otherCartItem, $cartItem]), new Exception("UNIT TEST FAILED : parameter array for checking if cart items are in stock did not match expected"));
        assert(['x',-1, 'x', -1, 'x', -1] === static::getAreCartItmesAvailableParameterArray([$cartItem, $cartItem, $cartItem]), new Exception("UNIT TEST FAILED : parameter array for checking if cart items are in stock did not match expected"));
        assert(['x',-1, 'x', -1, 'x', -1, 'y', 1] === static::getAreCartItmesAvailableParameterArray([$cartItem, $cartItem, $cartItem, $otherCartItem]), new Exception("UNIT TEST FAILED : parameter array for checking if cart items are in stock did not match expected"));
    }

    /**
     * Extracts all of the prices for all of the cart items, adding common prices together
     * @param vCart $cart the cart for which the cart items and prices need to be transacted
     * @return Response $resp the response object in which no data is returned however success is indicated
     */
    private static function transactItemsForCart(vCart $cart) : Response
    {
        $resp = new Response(false, "unkown error in pay item prices for cart", null);

        $conn = Database::getConnection();
        mysqli_autocommit($conn, false);

        try
        {
            $allLootsForPricesInCart = static::getLootForPricesForCart($cart);
            $allLootsForCartItemsFromStoreOwner = static::getStoreOwnerCartItemsLoot($cart);



            //have mysql throw exception on errors so we can rollback the transaction if one occurs
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

            $conn->begin_transaction();

            static::payItemPricesForCart($cart, $allLootsForPricesInCart);

            static::addPurchasedCartProductsToInventory($cart, $allLootsForCartItemsFromStoreOwner);

            $conn->commit();

            $resp->success = true;
            $resp->message = "items transacted between buyer and store owner";
        }
        catch(Exception $e)
        {  
            $conn->rollback();
            mysqli_autocommit($conn, true);
            throw new Exception("Exception caught while paying for item prices for cart : $e");
        }

        mysqli_autocommit($conn, true);

        return $resp;
    }

    /**
     * Gets the loot for the items in cart of the store owner's inventory
     * @param vCart $cart the cart for which to reference the store and its owner
     * @return array $loots the loot array of the matching loot items from the store owner of the cart items to be purchased
     */
    private static function getStoreOwnerCartItemsLoot(vCart $cart) : array
    {
        $owner = $cart->store->owner;

        $whereArrayClause = static::getWhereArrayClauseForGetStoreOwnerCartItemsLoot($cart->cartProducts);

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
        JOIN v_item_info vii ON vli.item_id = vii.Id
        WHERE account_id = ? AND item_id IN ($whereArrayClause);";

        $params = static::getParamsForGetStoreOwnerCartItemsLoot($owner, $cart->cartProducts);

        $loots = [];

        try
        {
            $result = Database::executeSqlQuery($sql, $params);

            if(!$result) throw new Exception("Failed to get matching loot items from store owner's inventory that matched the to be purchased cart items");

            while($row = $result->fetch_assoc())
            {
                $loot = LootController::row_to_vLoot($row, true);
                array_push($loots, $loot);
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while getting store owner cart item loots : $e");
        }

        return $loots;
    }

    private static function getParamsForGetStoreOwnerCartItemsLoot(vRecordId $accountId, array $cartItems) : array
    {
        $params = [$accountId->crand];

        foreach($cartItems as $cartItem)
        {
            array_push($params, $cartItem->product->item->crand);
        }

        return $params;
    }


    private static function getWhereArrayClauseForGetStoreOwnerCartItemsLoot(array $cartItems) : string
    {
        if(count($cartItems) <= 0) throw new Exception("CartItem array must contain at least one element");

        $whereArrayClause = "?";

        for($i = 1; $i < count($cartItems); $i++)
        {
            $whereArrayClause .= ",?";
        }

        return $whereArrayClause;
    }

    private static function unittest_getWhereArrayClauseForGetStoreOwnerCartItemsLoot() : void
    {
        assert("?" === static::getWhereArrayClauseForGetStoreOwnerCartItemsLoot([0]), "UNIT TEST FAILED : returned where array clause did not match expected");
        assert("?,?" === static::getWhereArrayClauseForGetStoreOwnerCartItemsLoot([0,0]), "UNIT TEST FAILED : returned where array clause did not match expected");
        assert("?,?,?" === static::getWhereArrayClauseForGetStoreOwnerCartItemsLoot([0,0,0]), "UNIT TEST FAILED : returned where array clause did not match expected");
        assert("?,?,?,?" === static::getWhereArrayClauseForGetStoreOwnerCartItemsLoot([0,0,0,0]), "UNIT TEST FAILED : returned where array clause did not match expected");
        assert("?,?,?,?,?" === static::getWhereArrayClauseForGetStoreOwnerCartItemsLoot([0,0,0,0,0]), "UNIT TEST FAILED : returned where array clause did not match expected");
    }

    /**
     * Adds the purchased items from the store owner to the cart owner's inventory
     * @param vCart $cart the refernced cart whose cart items are being purchased
     * @param array $allLootsForCartItemsFromStoreOwner the loot of the store owner which matches the to be purchased items in the cart
     */
    private static function addPurchasedCartProductsToInventory(vCart $cart, array $allLootsForCartItemsFromStoreOwner) : void
    {
        foreach($cart->cartProducts as $cartItem)
        {
            $foundKey = null;

            // Find the first matching loot item
            foreach ($allLootsForCartItemsFromStoreOwner as $key => $loot) {
                if ($cartItem->product->item->crand === $loot->item->crand) {
                    $foundKey = $key;
                    break;
                }
            }

            //throw new Exception(json_encode($allLootsForCartItemsFromStoreOwner));

            if (is_null($foundKey)) throw new RuntimeException("No loot item found for item crand {$loot->item->crand} | ".json_encode($allLootsForCartItemsFromStoreOwner));

            $lootItem = $allLootsForCartItemsFromStoreOwner[$foundKey];

            static::logTrade($lootItem, $cart->account);
            static::reassignLoot($lootItem, $cart->account);

            //unset the index of the loot that was just transacted as to not re-transact it
            unset($allLootsForCartItemsFromStoreOwner[$foundKey]);
        }
    }

    /**
     * Transacts all of the prices in the cart, adding trade records as needed
     * @param vCart $cart the cart for which to pay the total prices
     * @param array $allLootsForPricesInCart the array of the loot held within the cart owner's inventory that match a price in their cart
     */
    private static function payItemPricesForCart(vCart $cart, array $allLootsForPricesInCart) : void
    {
        foreach($cart->totals as $total)
        {   
            //skip if the total is not an item total
            if(is_null($total->item)) continue;

            //trade loot and log trade for the amount in the total
            for($amount = $total->amount; $amount > 0; $amount--)
            {
                $foundKey = null;

                // Find the first matching loot item
                foreach ($allLootsForPricesInCart as $key => $loot) {
                    if ($loot->item->crand === $total->item->crand) {
                        $foundKey = $key;
                        break;
                    }
                }

                //throw new Exception(json_encode($allLootsForPricesInCart));

                if (is_null($foundKey)) throw new RuntimeException("No loot item found for crand {$total->item->crand}");

                $lootItem = $allLootsForPricesInCart[$foundKey];

                //if item is fungible, the amount is the quantity of the fungible items reassigned which is different from multiple of non-fungible items
                //so we pass the amount which need to be transferred to the appropriate methods and break out of the for loop for this total
                if($lootItem->item->fungible)
                {
                    static::logTrade($lootItem, $cart->store->owner, $amount);
                    static::reassignLoot($lootItem, $cart->store->owner, $amount);
                    unset($allLootsForPricesInCart[$foundKey]);
                    break;
                }

                static::logTrade($lootItem, $cart->store->owner);
                static::reassignLoot($lootItem, $cart->store->owner);

                //unset the index of the loot that was just transacted as to not re-transact it
                unset($allLootsForPricesInCart[$foundKey]);
            }
        }
    }

    /**
     * Gets all the loots that are of the same item as the totals in the cart for later transacting
     * @param vCart $cart the cart to get the loot from
     * @return array the array populated with vLoot of the appropriate loot
     */
    private static function getLootForPricesForCart(vCart $cart) : array
    {
        $whereArrayClause = static::getWhereArrayClauseForGetLootForPricesForCart($cart->totals);

        $sql = "
        SELECT 
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
        vli.quantity,
        vi.Id as `item_id`,
        vi.name,
        vi.is_fungible
        FROM v_loot_item vli
        JOIN v_item_info vi ON vli.item_id = vi.id
        WHERE account_id = ? AND vli.quantity_available > 0 AND item_id IN ($whereArrayClause);";

        $params = static::getParamsForGetLootForPricesForCart($cart->account, $cart->totals);

        $result = Database::executeSqlQuery($sql, $params);

        if(!$result) throw new Exception("result returned false for getting loot for prices for cart");

        $loot = [];

        while($row = $result->fetch_array(MYSQLI_ASSOC))
        {
            $lootRow = LootController::row_to_vLoot($row);
            array_push($loot, $lootRow);
        }

        return $loot;
    }

    private static function getWhereArrayClauseForGetLootForPricesForCart(array $totalsItemIds) : string
    {
        if(count($totalsItemIds) <= 0) throw new InvalidArgumentException("totalsItemIds array must contain at least one element");

        $whereClause = "?";

        for($i = 1; $i < count($totalsItemIds); $i++)
        {
            $whereClause .= ",?";
        }

        return $whereClause;
    }

    private static function unittest_getWhereArrayClauseForGetLootForPricesForCart() : void
    {
        assert("?" === static::getWhereArrayClauseForGetLootForPricesForCart([0]), "UNIT TEST FAILED : returned where clause did match expected");
        assert("?,?" === static::getWhereArrayClauseForGetLootForPricesForCart([0,0]), "UNIT TEST FAILED : returned where clause did match expected");
        assert("?,?,?" === static::getWhereArrayClauseForGetLootForPricesForCart([0,0,0]), "UNIT TEST FAILED : returned where clause did match expected");
        assert("?,?,?,?" === static::getWhereArrayClauseForGetLootForPricesForCart([0,0,0,0]), "UNIT TEST FAILED : returned where clause did match expected");
        assert("?,?,?,?,?" === static::getWhereArrayClauseForGetLootForPricesForCart([0,0,0,0,0]), "UNIT TEST FAILED : returned where clause did match expected");
    }

    private static function getParamsForGetLootForPricesForCart(vRecordId $accountId, array $totals) : array
    {
        $params = [$accountId->crand];

        foreach($totals as $total)
        {
            if(is_null($total->item)) continue;
            array_push($params, $total->item->crand);
        }

        return $params;
    }

    private static function unittest_getParamsForGetLootForPricesForCart() : void
    {
        $mockTotal = new vPriceComponent();
        $mockTotal->item = new vItem('x',1);
        $mockNullTotal = new vPriceComponent();
        $accountId = new vRecordId();
        assert([$accountId->crand, $mockTotal->item->crand] === static::getParamsForGetLootForPricesForCart($accountId, [$mockTotal]), "UNIT TEST FAILED : returned param array did not match expected");
        assert([$accountId->crand, $mockTotal->item->crand, $mockTotal->item->crand] === static::getParamsForGetLootForPricesForCart($accountId, [$mockTotal, $mockTotal]), "UNIT TEST FAILED : returned param array did not match expected");
        assert([$accountId->crand, $mockTotal->item->crand, $mockTotal->item->crand, $mockTotal->item->crand] === static::getParamsForGetLootForPricesForCart($accountId, [$mockTotal, $mockTotal, $mockTotal]), "UNIT TEST FAILED : returned param array did not match expected");
        assert([$accountId->crand] === static::getParamsForGetLootForPricesForCart($accountId, [$mockNullTotal]), "UNIT TEST FAILED : returned param array did not match expected");
        assert([$accountId->crand] === static::getParamsForGetLootForPricesForCart($accountId, [$mockNullTotal, $mockNullTotal]), "UNIT TEST FAILED : returned param array did not match expected");
        assert([$accountId->crand, $mockTotal->item->crand] === static::getParamsForGetLootForPricesForCart($accountId, [$mockNullTotal, $mockTotal]), "UNIT TEST FAILED : returned param array did not match expected");
        assert([$accountId->crand, $mockTotal->item->crand] === static::getParamsForGetLootForPricesForCart($accountId, [$mockNullTotal, $mockNullTotal, $mockTotal]), "UNIT TEST FAILED : returned param array did not match expected");
        assert([$accountId->crand, $mockTotal->item->crand, $mockTotal->item->crand] === static::getParamsForGetLootForPricesForCart($accountId, [$mockTotal, $mockNullTotal, $mockNullTotal, $mockTotal]), "UNIT TEST FAILED : returned param array did not match expected");
        assert([$accountId->crand, $mockTotal->item->crand, $mockTotal->item->crand, $mockTotal->item->crand] === static::getParamsForGetLootForPricesForCart($accountId, [$mockTotal, $mockNullTotal, $mockTotal, $mockNullTotal, $mockTotal]), "UNIT TEST FAILED : returned param array did not match expected");
    }

    /**
     * Reassing loot from the owner's account to another account
     * This does _not_ log a trade
     * @param vLoot $loot the loot to be reassigned
     * @param vRecordId $toAccount the account which the loot is being transfered to
     * @param int $quantity if the loot is fungible, how many of the loot will be transfered
     */
    private static function reassignLoot(vLoot $loot, vRecordId $toAccount, int $quantity = 1) : void
    {
        if($loot->item->fungible)
        {
            if($quantity <= 0) throw new InvalidArgumentException("Quantity must be a non-zero positive integer");
            if($loot->quantity <= 0) throw new RuntimeException("Fungible loot has a quantiy of zero or less and is attempting to be reassigned");

            //how much loot will the original owner still own
            $remainingAmountOfNonReassignedLoot = $loot->quantity - $quantity;
            if($remainingAmountOfNonReassignedLoot < 0) throw new RuntimeException("Cannot reassign more fungible loot than the owner currently owns");

            //reference out of the quantity of loot already owned
            $quantityOfLootAlreadyOwned = 0;
            static::doesAccountOwnLootItem($loot->item, $toAccount, $quantityOfLootAlreadyOwned);

            if($remainingAmountOfNonReassignedLoot === 0)
            {
                if($quantityOfLootAlreadyOwned === 0)
                {
                    //The loot may simply be reassigned since the previous owner will be left with none and the new owner didn't have any
                    $sql = "UPDATE loot SET account_id = ?, dateObtained = NOW() WHERE Id = ?;";
                    $params = [$toAccount->crand, $loot->crand];

                    $result = Database::executeSqlQuery($sql, $params);

                    if(!$result) throw new exception("Failed to update loot to reassign");
                }
                else
                {
                    //Add the quantity to the new account's quantity and remove the original owners loot row since the quantity is now zero
                    static::ReassignFungibleLootWithQuantityAndClearZeroQuantityOldLoot($loot, $toAccount, $quantityOfLootAlreadyOwned + $quantity);
                }
            }
            else
            {
                if($quantityOfLootAlreadyOwned === 0)
                {
                    //Give the new account a row for the newly given loot and update the orignal owners quantity to what is left
                    static::InsertNewFungibleLootAndUpdateQuantityOldFungibleLoot($loot, $toAccount, $quantity, $remainingAmountOfNonReassignedLoot);      
                }
                else
                {
                    //Update the new account's quantity of loot and the old account's quantity
                    static::ReassignFungibleLootWithQuantity($loot, $toAccount, $quantityOfLootAlreadyOwned + $quantity, $remainingAmountOfNonReassignedLoot);
                }     
            }
        }
        else
        {
            $sql = "UPDATE loot SET account_id = ?, dateObtained = NOW() WHERE Id = ?;";
            $params = [$toAccount->crand, $loot->crand];

            $result = Database::executeSqlQuery($sql, $params);

            if(!$result) throw new exception("Failed to update loot to reassign");
        }  
    }

    private static function ReassignFungibleLootWithQuantityAndClearZeroQuantityOldLoot(vLoot $loot, vRecordId $toAccount, int $receivingAccountNewQuantity) : void
    {
        $conn = Database::getConnection();

        $conn->autocommit(false);
        $conn->begin_transaction();
        
        $updateOwnerQuantitySql = "UPDATE loot SET quantity = ?, dateObtained = NOW() WHERE item_id = ? AND account_id = ?;";
        Database::executeSqlQuery($updateOwnerQuantitySql, [$receivingAccountNewQuantity, $loot->item->crand, $toAccount->crand]);

        $removeZeroQuantityLootSql = "DELETE FROM loot WHERE quantity = 0 AND item_id = ? AND account_id = ?;";
        Database::executeSqlQuery($removeZeroQuantityLootSql, [$loot->item->crand, $loot->ownerId->crand]);
        
        $conn->commit();
        $conn->autocommit(true);
    }

    private static function ReassignFungibleLootWithQuantity(vLoot $loot, vRecordId $toAccount, int $receivingAccountNewQuantity, int $remainingAmountOfNonReassignedLoot) : void
    {
        try
        {
            $conn = Database::getConnection();

            $conn->autocommit(false);
            $conn->begin_transaction();

            $updateOwnerQuantitySql = "UPDATE loot SET quantity = ?, dateObtained = NOW() WHERE item_id = ? AND account_id = ?;";
            $result = Database::executeSqlQuery($updateOwnerQuantitySql, [$receivingAccountNewQuantity, $loot->item->crand, $toAccount->crand]);
            if(!$result) throw new Exception("Result returned false when trying to update fungible loot transfer receivers quantity");


            $updateBuyerQuantitySql = "UPDATE loot SET quantity = ? WHERE item_id = ? AND account_id = ?;";
            $result = Database::executeSqlQuery($updateBuyerQuantitySql, [$remainingAmountOfNonReassignedLoot, $loot->item->crand, $loot->ownerId->crand]);
            if(!$result) throw new Exception("Result returned false when trying to update fungible loot transfer receivers quantity");

            $conn->commit();
            $conn->autocommit(true);
        }
        catch(Exception $e)
        {
            $conn->rollback();
            throw new Exception("Transaction failed while updating loot quantities for fungible loot");
        }
        $updateOwnerQuantitySql = "UPDATE loot SET quantity = ?, dateObtained = NOW() WHERE item_id = ? AND account_id = ?;";

        $result = Database::executeSqlQuery($updateOwnerQuantitySql, [$receivingAccountNewQuantity, $loot->item->crand, $toAccount->crand]);

        if(!$result) throw new Exception("Result returned false when trying to update fungible loot transfer receivers quantity");
    }

    private static function InsertNewFungibleLootAndUpdateQuantityOldFungibleLoot(vLoot $loot, vRecordId $toAccount, int $quantity, int $remainingAmountOfNonReassignedLoot) : void
    {
        try
        {
            $conn = Database::getConnection();

            $conn->autocommit(false);
            $conn->begin_transaction();
            
            $Id = new vRecordId();
            $insertOwnerLootSql = "INSERT INTO loot (Id, opened, `description`, account_id, item_id, quest_id, dateObtained, redeemed, container_loot_id, quantity) 
            VALUES(?,?,?,?,?,?,?,?,?,?)";
            $questIdCrand = is_null($loot->quest) ? null : $loot->quest->crand;
            $containerLootCrand = is_null($loot->containerLoot) ? null : $loot->containerLoot->crand;
            $params = [
                $Id->crand, 
                $loot->opened, 
                $loot->description, 
                $toAccount->crand, 
                $loot->item->crand, 
                $questIdCrand, 
                $Id->ctime, 
                $loot->opened, 
                $containerLootCrand, 
                $quantity];

            Database::executeSqlQuery($insertOwnerLootSql, $params);

            $removeZeroQuantityLootSql = "UPDATE loot SET quantity = ? WHERE item_id = ? AND account_id = ?;";
            Database::executeSqlQuery($removeZeroQuantityLootSql, [$remainingAmountOfNonReassignedLoot, $loot->item->crand, $loot->ownerId->crand]);
            
            $conn->commit();
            $conn->autocommit(true);
        }
        catch(Exception $e)
        {
            $conn->rollback();
            throw new Exception("Failed to transfer fungible loot to new owner when previous owner would have zero of that loot left");
        }
    }

    private static function doesAccountOwnLootItem(vRecordId $itemId, vRecordId $accountId, int &$quantity) : bool
    {
        $sql = "SELECT quantity FROM v_loot_item WHERE account_id = ? AND item_id = ? LIMIT 1;";

        $params = [$accountId->crand, $itemId->crand];

        $result = Database::executeSqlQuery($sql, $params);

        if(!$result) throw new Exception("Result returned false when checking if account owns loot item");

        $row = $result->fetch_assoc();
        $quantity = $row["quantity"] ?? 0;

        return $result->num_rows > 0;
    }

    private static function logTrade(vLoot $loot, vRecordId $toAccount, int $quantity = 1) : void
    {
        $sql = "INSERT INTO trade (from_account_id, to_account_id, loot_id, trade_date, from_account_obtain_date, quantity)
        VALUES (?,?,?,?,?,?);";

        $params = [$loot->ownerId->crand, $toAccount->crand, $loot->crand, RecordId::getCTime(), $loot->dateObtained->value->format('Y-m-d H:i:s.u'), $quantity];

        $result = database::executeSqlQuery($sql, $params);
        
        if(!$result) throw new Exception("Error in inserting trade log");
    }


    private static function returnCartLovelacePrice(vCart $cart) : int
    {
        $lovelace = 0;

        foreach($cart->totals as $total)
        {
            if(!is_null($total->currencyCode))
            {
                if($total->currencyCode === CurrencyCode::ADA)
                {
                    $lovelace += $total->amount;
                }  
                elseif($total->currencyCode === CurrencyCode::USD)
                {
                    $lovelace += static::convertUSDToLovelace($total->amount);
                } 
                else
                throw new exception("Unrecognized currency code when returning cart lovelace amount");
            }
        }

         return $lovelace;
    }

    /**
     * Placeholder function for when the currency converter API is implemented
     */
    private static function convertUSDToLovelace(int $cents) : int
    {
        return 0;
    }

    public static function canAccountAffordItemPricesInCart(vCart $cart) : Response
    {
        $resp = new Response(false, "Unkown error in checking if account can afford item prices in cart", null);

        try
        {
            
            $lootForCart = static::returnLootAmountOfPricesInCart($cart);

            $canAccountAffordItems = true;

            foreach($cart->totals as $total)
            {
                $filteredArray = array_filter($lootForCart, function($obj) use ($total) 
                {
                    return $obj["item_id"] == $total->item->crand;
                });

                $row = $filteredArray[0] ?? null;

                if(empty($row) || ($row["amount"] - $total->amount < 0))
                {
                    $canAccountAffordItems = false;
                    break;
                }
            }

            if($canAccountAffordItems)
            {
                $resp->success = true;
                $resp->message = "Account can afford item prices";
                $resp->data = true;
            }
            else
            {
                $resp->success = true;
                $resp->message = "Account cannot afford Item Prices : ".json_encode($lootForCart)." : ".json_encode($cart->totals);
                $resp->data = false;
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while checking if account can afford item prices in cart : $e");
        }

        return $resp;
    }

    /**
     * Returns the loots item_id and amount for each price in the cart
     * @param vCart $cart the cart to reference the prices
     * @return array $lootForCart the array of associative arrays returned with [ {(item_id),(amount)} ]
     */
    private static function returnLootAmountOfPricesInCart(vCart $cart) : array
    {
        $whereClause = static::returnWhereClauseForCanAccountAffordItemPricesInCart($cart->cartProducts);
        $params = static::returnParamsForCanAccountAffordItemPricesForCart($cart);

        $sql = "SELECT item_id, SUM(Quantity) AS `amount` FROM loot WHERE account_id = ? AND opened = 1 AND redeemed = 1$whereClause GROUP BY item_id;";

        $result = Database::executeSqlQuery($sql, $params);

        if(!$result) throw new Exception("Sql error; result returned false");

        $lootForCart = $result->num_rows > 0 ? $result->fetch_all(MYSQLI_ASSOC) : [];

        return $lootForCart;
    }

    private static function returnParamsForCanAccountAffordItemPricesForCart(vCart $cart) : array
    {
        $params = [$cart->account->crand];

        foreach($cart->cartProducts as $cartItem)
        {
            foreach($cartItem->product->price as $priceComponent)
            {
                if(is_null($priceComponent->item)) continue;

                array_push($params, $priceComponent->item->crand);
            }
            
        }

        return $params;
    }

    private static function unittest_returnParamsForCanAccountAffordItemPricesForCart() : void
    {
        $cart = new vCart();
        $cart->account = new vAccount();

        $cartItem = new vCartItem();
        $cartItem->product = new vProduct();
        $priceComponent = new vPriceComponent();
        $priceComponent->item = new vItem('',4);

        $cartItem->product->price = [$priceComponent];
        $cart->cartProducts = [$cartItem];
        assert(static::returnParamsForCanAccountAffordItemPricesForCart($cart) === [-1,4], new Exception("UNIT TEST FAILED : returned params did not match expected for returnParamsForCanAccountAffordItemPricesForCart"));

        $cart->cartProducts = [$cartItem,$cartItem];
        assert(static::returnParamsForCanAccountAffordItemPricesForCart($cart) === [-1,4,4], new Exception("UNIT TEST FAILED : returned params did not match expected for returnParamsForCanAccountAffordItemPricesForCart"));
        
        $cartItem->product->price = [$priceComponent, $priceComponent];
        $cart->cartProducts = [$cartItem];       
        assert(static::returnParamsForCanAccountAffordItemPricesForCart($cart) === [-1,4,4], new Exception("UNIT TEST FAILED : returned params did not match expected for returnParamsForCanAccountAffordItemPricesForCart"));
    }

    private static function returnWhereClauseForCanAccountAffordItemPricesInCart(array $cartItems) : string
    {
        $whereClause = " AND (";

        foreach($cartItems as $cartItem)
        {
            $priceWhereClause = "(";

            foreach($cartItem->product->price as $priceComponent)
            {
                if(is_null($priceComponent->item)) continue;

                $priceWhereClause .= "item_id = ? OR ";
            }

            //trim the last "OR" off
            $priceWhereClause = substr($priceWhereClause, 0, -4);

            $priceWhereClause = $priceWhereClause.")";

            $whereClause = $whereClause.$priceWhereClause." OR ";
            
        }

        //trim the last "OR" off
        $whereClause = substr($whereClause, 0, -4);

        $whereClause .= ")";

        return $whereClause;
    }

    private static function unittest_returnWhereClauseForCanAccountAffordItemPricesInCart() : void
    {
        $cartItem = new vCartItem();
        $cartItem->product = new vProduct();
        $priceComponent = new vPriceComponent();
        $priceComponent->item = new vItem('',4);

        $cartItem->product->price = [$priceComponent];
        $cartItems = [$cartItem];
        assert(static::returnWhereClauseForCanAccountAffordItemPricesInCart($cartItems) === " AND ((item_id = ?))", new Exception("UNIT TEST FAILED : returned where clause for can account afford item prices in cart did not match expected"));

        $cartItem->product->price = [$priceComponent, $priceComponent];
        $cartItems = [$cartItem];
        assert(static::returnWhereClauseForCanAccountAffordItemPricesInCart($cartItems) === " AND ((item_id = ? OR item_id = ?))",  new Exception("UNIT TEST FAILED : returned where clause for can account afford item prices in cart did not match expected"));

        $cartItem->product->price = [$priceComponent, $priceComponent];
        $cartItems = [$cartItem, $cartItem];
        assert(static::returnWhereClauseForCanAccountAffordItemPricesInCart($cartItems) === " AND ((item_id = ? OR item_id = ?) OR (item_id = ? OR item_id = ?))",  new Exception("UNIT TEST FAILED : returned where clause for can account afford item prices in cart did not match expected"));    
    }

    /**
     * Calculates the totals for the prices in the cart
     * @param array $cartItems all of the items in the cart, an array of vCartItems
     * @return array $totalPrices the returned array which contains the prices of the totals in vPrice objects
     */
    public static function calculateCartTotalPrices(array $cartItems) : array
    {
        $totalPrices = [];

        foreach($cartItems as $cartItem)
        {
            $price = $cartItem->product->price;

            foreach($price as $priceComponent)
            {
                $priceAlreadyExists = null;

                foreach($totalPrices as $total)
                {

                    //Does price being checked match an already existing total
                    if(
                        (!is_null($priceComponent->item) && !is_null($total->item) 
                        && $priceComponent->item->ctime == $total->item->ctime && $priceComponent->item->crand == $total->item->crand)
                        ||
                        (!is_null($priceComponent->currencyCode) && !is_null($total->currencyCode) &&
                        $priceComponent->currencyCode == $total->currencyCode)
                    )
                    {
                        $priceAlreadyExists = $total;
                        break;
                    }
                }

                //Add amount of already existing total or create new total
                if(is_null($priceAlreadyExists))
                {
                    array_push($totalPrices, $priceComponent);
                }
                else
                {
                    $priceAlreadyExists->amount = $priceAlreadyExists->amount + $priceComponent->amount;
                }
            }
        }

        return $totalPrices;
    }

    public static string $columnsInCartView = "
                ctime,
                crand,
                account_username,
                store_name, 
                store_locator,
                checked_out,
                void,
                account_ctime,
                account_crand,
                store_owner_ctime,
                store_owner_crand,
                store_ctime,
                store_crand,
                transaction_ctime,
                transaction_crand";

    /**
     * Adds a product to a cart only if there are enough stock left of the product in the store
     * 
     * Returns a success with false data if there was not enough stock present
     * Returns a success with true data if the product was added to cart
     * 
     * @param vRecordId $productId the id of the product being added to cart
     * @param vCart $cart the cart having the product added
     * 
     * @return Response the response object whose data is returned as the following:
     *      true - Product was added to cart successfully (success will be true)
     *      false - Product did not have enough available to add to cart (success will be true)
     *      null - An error occured while attempting to add product to cart (success will be false)
    */
    public static function addProductToCart(vRecordId $productId, vCart $cart) : Response
    {
        $resp = new Response(false, "unkown error in adding product to cart", null);

        try
        {
            $selectProductResp = static::getProductById($productId);

            if(!$selectProductResp->success)
            {
                $resp->message = "error in getting product to add it to cart : $selectProductResp->message";
                return $resp;
            }

            $product = $selectProductResp->data;

            $effectiveStock = $product->amountAvailable - static::getNumberOfProductInCartById($product, $cart);

            if($effectiveStock <= 0)
            {
                $resp->success = true;
                $resp->message = "Not enough available stock to add product to cart";
                $resp->data = false;
                return $resp;
            }

            $linkResp = static::linkProductToCart($product, $cart);

            static::linkBasePricesToCartProduct($linkResp->data);

            if($linkResp->success)
            {
                $resp->success = true;
                $resp->message = "Product Added to cart";
                $resp->data = true;
            }  
            else
            {
                $resp->message = "Failed to link product to cart : $linkResp->message";
            }

        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while adding product to cart : $e");
        }

        return $resp;
    }

    private static function linkBasePricesToCartProduct(vCartProductLink $link) : void
    {
        $productId = new vRecordId($link->product->ctime, $link->product->crand);
        $getBasePricesResp = static::getBasePricesForProduct($productId);

        if(!$getBasePricesResp->success) throw new Exception("Failed to get base prices while attempting to add base prices to cart product");

        $params = [];
        $valueClause = static::createValueClauseForAddBasePriceToCartProduct($link, $getBasePricesResp->data, $params);
        $sql = "INSERT INTO cart_product_price_link (ctime, crand, ref_cart_product_link_ctime, ref_cart_product_link_crand, ref_price_ctime, ref_price_crand, removed, checked_out) VALUES 
        $valueClause";

        $result = Database::executeSqlQuery($sql, $params);

        if(!$result) throw new Exception("result returned false while attempting to link base prices to cart product");
    }

    private static function createValueClauseForAddBasePriceToCartProduct(vCartProductLink $link, array $basePrices, array &$params) : string
    {
        $valueClause = "";

        for($i = 0 ; $i < count($basePrices); $i++)
        {
            $price = $basePrices[$i];

            $cartProductPricelink = new CartProductPriceLink();
            $cartProductPricelink->cartProductLinkId = $link;
            $cartProductPricelink->priceId = $price;

            if($i !== 0) $valueClause .= ", ";

            $valueClause .= "(?,?,?,?,?,?,0,0)";
            array_push($params, $cartProductPricelink->ctime, $cartProductPricelink->crand, $link->ctime, $link->crand, $price->ctime, $price->crand);
        }

        return $valueClause;
    }

    public static function getProductByLocator(string $locator) : Response
    {
        $resp = new Response(false, "Unkown eror in getting product by id", null);

        $sql = "SELECT 
            ".static::$columnsInProductView."
        FROM v_product 
        WHERE locator = ? 
        LIMIT 1;";


        $params = [$locator];

        try
        {
            $result = Database::executeSqlQuery($sql, $params);

            if($result->num_rows > 0)
            {
                $product = static::rowToVProduct($result->fetch_assoc());

                $priceResp = static::getBasePricesForProduct($product);

                if($priceResp->success)
                {
                    $product->price = $priceResp->data;

                    $resp->success = true;
                    $resp->message = "Product found and returned";
                    $resp->data = $product;
                }
                else
                {
                    $resp->message = "failed to get prices for product by locator : $priceResp->message";
                }

                
            }
            else
            {
                $resp->message = "Product not found";
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while getting product by locator : $e");
        }

        return $resp;
    }

    /**
     * Marks a link of a product to a cart as removed
     * The ID of cart items is the same as its product cart link so cart item ID may be passed aswell
     * 
     * @param vRecordId $productCartLink the Id of the cart item or product cart link to be removed
     * 
     * @return Response the response object which will return successful if the link has been successfully marked as removed
     */
    public static function removeProductFromCart(vCartItem $cartProduct) : Response
    {
        $resp = new Response(false, "unkown error in removing product from cart", null);

        try
        {
            $conn = Database::getConnection();
            $conn->begin_transaction();

            $sql = "UPDATE cart_product_link SET removed = 1 WHERE ctime = ? AND crand = ?;";

            $result = Database::executeSqlQuery($sql, [$cartProduct->ctime, $cartProduct->crand]);
            if(!$result) throw new Exception("result returned false while removing product from cart");

            if(!is_null($cartProduct->coupon)) 
            {
                if(is_null($cartProduct->couponGroupAssignmentId)) throw new LogicException("Coupon group assignment id cannot be null if a coupon as been applied to the cart product");
            
                $removeCouponResp = static::removeCouponFromCartByGroupAssignmentId($cartProduct->couponGroupAssignmentId);
                
                if(!$removeCouponResp->success)
                {
                    throw new Exception("Failed to remove coupon while removing cart product with applied coupon");
                }
            }

            $resp->success = true;
            $resp->message = "Product removed from cart";
        }
        catch(Exception $e)
        {
            $conn->rollback();
            throw new Exception("exception caught while removing product from cart : $e");
        }

        return $resp;
    }

    private static function getNumberOfProductInCartById(vRecordId $product, vCart $cart) : int
    {
        $quantity = 0;


        //Increment quantity by 1 for every matching cart item
        foreach($cart->cartProducts as $cartItem)
        {
            if($cartItem->product->ctime == $product->ctime && 
                $cartItem->product->crand == $product->crand) 
                $quantity ++;
        }

        return $quantity;
    }

    private static function unittest_getNumberOfProductInCartById() : void
    {
        $cart = new vCart();
        $cartItem = new vCartItem();
        $product = new RecordId();
        $cartItem->product = new vProduct($product->ctime, $product->crand);

        $otherProduct = new RecordId();
        $otherCartItem = new vCartItem();
        $otherCartItem->product = new vProduct($otherProduct->ctime, $otherProduct->crand);

        $cart->cartProducts = [];
        $actual = static::getNumberOfProductInCartById($product, $cart);
        $expected = 0;
        assert($actual === $expected, new Exception("UNIT TEST FAILED | actual : $actual | expected : $expected"));

        $cart->cartProducts = [$otherCartItem];
        $actual = static::getNumberOfProductInCartById($product, $cart);
        $expected = 0;
        assert($actual === $expected, new Exception("UNIT TEST FAILED | actual : $actual | expected : $expected"));

        $cart->cartProducts = [$otherCartItem,$otherCartItem,$otherCartItem];
        $actual = static::getNumberOfProductInCartById($product, $cart);
        $expected = 0;
        assert($actual === $expected, new Exception("UNIT TEST FAILED | actual : $actual | expected : $expected"));

        $cart->cartProducts = [$cartItem];
        $actual = static::getNumberOfProductInCartById($product, $cart);
        $expected = 1;
        assert($actual === $expected, new Exception("UNIT TEST FAILED | actual : $actual | expected : $expected"));

        $cart->cartProducts = [$cartItem, $otherCartItem, $cartItem];
        $actual = static::getNumberOfProductInCartById($product, $cart);
        $expected = 2;
        assert($actual === $expected, new Exception("UNIT TEST FAILED | actual : $actual | expected : $expected"));

        $cart->cartProducts = [$cartItem, $cartItem];
        $actual = static::getNumberOfProductInCartById($product, $cart);
        $expected = 2;
        assert($actual === $expected, new Exception("UNIT TEST FAILED | actual : $actual | expected : $expected"));  
    }

    private static function linkProductToCart(vRecordId $product, vRecordId $cart) : Response
    {
        $resp = new Response(false, "unkown error in linking product to cart", null);

        try
        {
            $cartProductLink = new CartProductLink();
            $cartProductLink->productId = $product;
            $cartProductLink->cartId = $cart;
            
            $sql = "INSERT INTO cart_product_link (
            ctime, 
            crand, 
            removed, 
            checked_out, 
            ref_cart_ctime, 
            ref_cart_crand, 
            ref_product_ctime, 
            ref_product_crand)
            VALUES 
            (?,?,?,?,?,?,?,?)";

            
            $params = [
                $cartProductLink->ctime, 
                $cartProductLink->crand, 
                $cartProductLink->removed, 
                $cartProductLink->checkedOut, 
                $cartProductLink->cartId->ctime, 
                $cartProductLink->cartId->crand, 
                $cartProductLink->productId->ctime, 
                $cartProductLink->productId->crand
            ];

            $result = Database::executeSqlQuery($sql, $params);


            if($result)
            {
                $resp->success = true;
                $resp->message = "Product Linked to Cart";
                $resp->data = static::CartProductLinkObjectToView($cartProductLink);
            }
            else
            {
                $resp->message = "Sql error in inserting cart product link";
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Execption caught while linking product to cart : $e");
        }

        return $resp;
    }

    private static function CartProductLinkObjectToView(CartProductLink $link) : vCartProductLink
    {
        $view = new vCartProductLink();

        $view->ctime = $link->ctime;
        $view->crand = $link->crand;
        $view->product = new vProduct($link->productId->ctime, $link->productId->crand);
        $view->cart = new vCart($link->cartId->ctime, $link->cartId->crand);
        $view->removed = $link->removed;
        $view->checkedOut = $link->checkedOut;

        return $view;
    }

    public static function cartProductLinkToView(array $row) : vCartProductLink
    {
        $store = new vStore();
        $store->ctime = $row["store_ctime"];
        $store->crand = $row["store_crand"];
        $store->name = $row["store_name"];
        $store->locator = $row["store_locator"];

        $account = new vAccount();
        $account->ctime = $row["account_ctime"];
        $account->crand = $row["account_crand"];
        $account->username = $row["account_username"];

        $product = new vProduct();
        $product->ctime = $row["product_ctime"];
        $product->crand = $row["product_crand"];
        $product->locator = $row["product_locator"];
        $product->name = $row["product_name"];
        $product->description = $row["product_description"];
            $smallMedia = new vMedia();
            $smallMedia->setMediaPath($row["small_media_media_path"]);
            $largeMedia = new vMedia();
            $largeMedia->setMediaPath($row["large_media_media_path"]);
            $backMedia = new vMedia();
            $backMedia->setMediaPath($row["back_media_media_path"]);
        $product->mediaSmall = $smallMedia;
        $product->mediaLarge = $largeMedia;
        $product->mediaBack = $backMedia;

        $product->store = $store;
        
        $cart = new vCart();
        $cart->ctime = $row["cart_ctime"];
        $cart->crand = $row["cart_crand"];
        $cart->store = $store;
        $cart->account = $account;

        $link = new vCartProductLink();
        $link->ctime = $row["ctime"];
        $link->crand = $row["crand"];
        $link->removed = boolval($row["removed"]);
        $link->checkedOut = boolval($row["checked_out"]);
        $link->cart = $cart;
        $link->product = $product;

        return $link;
    }

    /**
     * Gets a cart for an account
     * Either selects an already existing cart or creates a new one
     * 
     * Additionally gets all items in the cart
     * @param vRecordId $accountId the account id to get the cart for
     * @param vRecordId $storeId the store to get the cart for
     * @return Response $resp the response containg the found vCart object in the data field
     */
    public static function getCartForAccount(vRecordId $accountId, vRecordId $storeId) : Response
    {
        $resp = new Response(false, "unkown error in getting cart for account", null);

        $cart = new Cart($accountId->ctime, $accountId->crand, $storeId->ctime, $storeId->crand);

        $sql = "INSERT INTO cart (
            ctime, crand,
            ref_account_ctime, ref_account_crand,
            ref_store_ctime, ref_store_crand
        )
        SELECT ?, ?, ?, ?, ?, ?
        WHERE NOT EXISTS (
            SELECT 1
            FROM cart
            WHERE ref_account_crand = ? AND ref_store_ctime = ? AND ref_store_crand = ?
        );";

        $params = [$cart->ctime, $cart->crand, $accountId->ctime, $accountId->crand, $storeId->ctime, $storeId->crand, $accountId->crand, $storeId->ctime, $storeId->crand];

        
        try
        {
            $result = Database::executeSqlQuery($sql, $params);

            if(!$result)
            {
                $resp->message = "Failed to run duplicate-tolerant insert command for inserting cart";
                return $resp;
            }


            $selectSql = "SELECT
                ".static::$columnsInCartView."
                FROM v_cart
                WHERE account_crand = ? AND store_ctime = ? AND store_crand = ?;
            ";

            $params = [$accountId->crand, $storeId->ctime, $storeId->crand];

            $selectResult = Database::executeSqlQuery($selectSql, $params);

            if(!$selectResult)
            {
                $resp->message = "Failed to select cart for account";
                return $resp;
            } 

            if($selectResult->num_rows <= 0)
            {
                $resp->message = "cart not found after insertion";
                return $resp;
            }

            $row = $selectResult->fetch_assoc();

            $cartView = static::cartToView($row);

            $cartItemsResp = static::getItemsInCart($cartView);

            if(!$cartItemsResp->success)
            {
                $resp->message = "Failed to get cart items : $cartItemsResp->message";
                return $resp;
            }

            $cartView->cartProducts = $cartItemsResp->data;

            $cartView->totals = static::calculateCartTotalPrices($cartView->cartProducts);

            $resp->success = true;
            $resp->message = "Cart returned for account";
            $resp->data = $cartView; 
        }
        catch(Exception $e)
        {
            throw new Exception("exception caught while getting cart for account : $e");
        }

        return $resp;
    }

    public static string $columnsInCartItemView = "
            cart_product_link_ctime, 
            cart_product_link_crand,
            cart_ctime, 
            cart_crand,
            removed,
            checked_out,
            product_ctime,
            product_crand,
            product_name,
            product_description,
            product_locator,
            product_small_media_path,
            product_large_media_path,
            product_back_media_path,
            product_stock,
            price_ctime,
            price_crand,
            price_amount,
            price_currency_code,
            price_item_name,
            price_item_desc,
            price_media_path_small,
            price_media_path_large,
            price_media_path_back,
            price_item_ctime, 
            price_item_crand,
            price_item_is_fungible,
            coupon_ctime,
            coupon_crand,
            coupon_code,
            coupon_description,
            coupon_required_quantity_of_product,
            coupon_times_used,
            coupon_max_times_used,
            coupon_max_times_used_per_account,
            coupon_expiry_time,
            coupon_removed,
            coupon_assignment_group_ctime,
            coupon_assignment_group_crand";

    /**
     * gets all the matching rows from v_cart_item.
     * It then conglomerates rows of the same cart product link into vCartItem objects 
     * since the cart item view lists the cart items as units of the prices of the products in the cart
     */
    public static function getItemsInCart(vRecordId $cart) : Response
    {
        $resp = new Response(false, "unkown error in getting item for cart", null);

        try
        {
            $sql = "SELECT 
            ".static::$columnsInCartItemView."
            FROM v_cart_item WHERE removed = 0 AND checked_out = 0 AND cart_ctime = ? AND cart_crand = ?;";

            $params = [$cart->ctime, $cart->crand];

            $result = Database::executeSqlQuery($sql, $params);

            if($result)
            {
                if($result->num_rows > 0)
                {
                    $resp->success = true;
                    $resp->message = "Items returned";
                    $resp->data = static::cartItemResultToViews($result);
                }
                else
                {
                    $resp->success = true;
                    $resp->message = "no items in cart";
                    $resp->data = [];
                }
            }
            else
            {
                $resp->message = "failed to select cart items";
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while gettig items in cart : $e");
        }

        return $resp;
    }

    public static function cartItemResultToViews(mysqli_result $result) : array
    {
        $cartItems = [];

        while($row = $result->fetch_assoc())
        {
            $cartItemAlreadyProccessed = null;
            
            foreach($cartItems as $cartItem)
            {
                if($row["cart_product_link_ctime"] == $cartItem->ctime && $row["cart_product_link_crand"] == $cartItem->crand)
                {
                    $cartItemAlreadyProccessed = $cartItem;
                    break;
                }
            }

            if(is_null($cartItemAlreadyProccessed))
            {
                array_push($cartItems, static::cartItemToView($row));
            }
            else
            {
                $priceComponent = static::cartItemToPriceComponentView($row);

                array_push($cartItemAlreadyProccessed->price, $priceComponent);
            }
        }

        return $cartItems;
    }

    private static function cartItemToView(array $row) : vCartItem
    {
        $cartItem = new vCartItem();

        $priceComponent = static::cartItemToPriceComponentView($row);

        $product = new vProduct($row["product_ctime"], $row["product_crand"]);
            $product->price = [$priceComponent];
            $product->stock = $row["product_stock"];
            $product->locator = $row["product_locator"];
            $product->name = $row["product_name"];
            $product->description = $row["product_description"];

            $product->mediaSmall = new vMedia();
            $product->mediaSmall->setMediaPath($row["product_small_media_path"]);
            $product->mediaLarge = new vMedia();
            $product->mediaLarge->setMediaPath($row["product_large_media_path"]);
            $product->mediaBack = new vMedia();
            $product->mediaBack->setMediaPath($row["product_back_media_path"]);
        $cartItem->product = $product;

        $cart = new vCart();
            $cart->ctime = $row["cart_ctime"];
            $cart->crand = $row["cart_crand"];
        $cartItem->cart = $cart;

        if(!is_null($row["coupon_ctime"]) && !is_null($row["coupon_crand"]))
        {
            $coupon = new vCoupon($row["coupon_ctime"], $row["coupon_crand"]);
                $coupon->code = $row["coupon_code"];
                $coupon->description = $row["coupon_description"];
                $coupon->requiredQuantityOfProduct = $row["coupon_required_quantity_of_product"];
                $coupon->productId = new vRecordId($row["product_ctime"], $row["product_crand"]);
                $coupon->timesUsed = $row["coupon_times_used"];
                $coupon->maxTimesUsed = $row["coupon_max_times_used"];
                $coupon->maxTimesUsedPerAccount = $row["coupon_max_times_used_per_account"];
                $coupon->expiryTime = is_null($row["coupon_expiry_time"]) ? null : DateTime::createFromFormat('Y-m-d H:i:s.u', $row["expiry_time"]);
                $coupon->removed = boolval($row["coupon_removed"]);

            $cartItem->coupon = $coupon;

            $cartItem->couponGroupAssignmentId = new vRecordId($row["coupon_assignment_group_ctime"], $row["coupon_assignment_group_crand"]);
        }
        
        $cartItem->ctime = $row["cart_product_link_ctime"];
        $cartItem->crand = $row["cart_product_link_crand"];
        $cartItem->removed = boolval($row["removed"]);
        $cartItem->checkedOut = boolval($row["checked_out"]);
        $cartItem->price = [$priceComponent];

        return $cartItem;
    }
    
    private static function cartItemToPriceComponentView(array $row) : vPriceComponent
    {
        $priceComponent = new vPriceComponent();

        if(!is_null($row["price_item_ctime"]) && !is_null($row["price_item_crand"]))
        {
            $item = new vItem();
            $item->ctime = $row["price_item_ctime"];
            $item->crand = $row["price_item_crand"];
            $item->name = $row["price_item_name"];
            $item->description = $row["price_item_desc"]; 
                $smallMedia = new vMedia();
                $smallMedia->setMediaPath($row["price_media_path_small"]);
                $largeMedia = new vMedia();
                $largeMedia->setMediaPath($row["price_media_path_large"]);
                $backMedia = new vMedia();
                if(!empty($row["price_media_path_back"]))$backMedia->setMediaPath($row["price_media_path_back"]);
            $item->iconSmall = $smallMedia;
            $item->iconBig = $largeMedia;
            $item->iconBack = $backMedia;

            $item->fungible = boolval($row["price_item_is_fungible"]);

            $priceComponent->item = $item;
        }
        
        if(!empty($row["price_currency_code"]))
        {
            $priceComponent = CurrencyCode::from($row["price_currency_code"]);
        }

        $priceComponent->ctime = $row["price_ctime"];
        $priceComponent->crand = $row["price_crand"];
        $priceComponent->amount = $row["price_amount"];

        return $priceComponent;
        
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
        $cart = new vCart();

        $cart->account = new vAccount();
        $cart->store = new vStore();
        $cart->transaction = new vTransaction();

        $cart->account->username = $row["account_username"];
        $cart->account->ctime = $row["account_ctime"];
        $cart->account->crand = $row["account_crand"];

        $cart->store->name = $row["store_name"];
        $cart->store->locator = $row["store_locator"];
        $cart->store->ctime = $row["store_ctime"];
        $cart->store->crand = $row["store_crand"];
            $storeOwner = new vAccount($row["store_owner_ctime"], $row["store_owner_crand"]);
        $cart->store->owner = $storeOwner;

        $cart->checkedOut = boolval($row["checked_out"]);
        $cart->void = boolval($row["void"]);

        $cart->ctime = $row["ctime"];
        $cart->crand = $row["crand"];

        return $cart;
    }

    //PRODUCT

    public static string $columnsInProductView = "
            ctime, 
            crand, 
            `name`,
            `description`, 
            locator,
            stock,
            amount_available,
            removed,
            store_name,
            store_locator,
            store_description,
            store_owner_username,
            store_owner_ctime,
            store_owner_crand,
            store_ctime,
            store_crand,
            large_media_media_path,
            small_media_media_path,
            back_media_media_path
    ";

    /**
     * Links a number of loots in an array to a product to be used as stock for that product.
     * 
     * @param vRecordId $productId the Id of the product to which the loots will be linked
     * @param array<vLoot> $loots the array of loots which will be linked to the product. The quantity field in each loot will be how many of that loot will be linked if it is fungible
     * @param int $amount an optional specified amount of the loot to be linked in the case of fungible loots which need to be linked to a product as stock
     * 
     * @return Response the response object returned whose success field will indicate the success of the linking
     */
    public static function linkLootsToProductAsStock(vRecordId $productId, array $loots, int $amount = 1) : Response
    {
        $resp = new Response(false, "unkown error in linking loots to product as stock", null);

        try
        {
            $sql = static::createInsertSqlForLinkLootsToProductAsStock($loots);
            $params = static::createInsertParamsForLinkLootsToProductAsStock($productId, $loots, $amount);

            $result = database::executeSqlQuery($sql, $params);

            if($result !== true) throw new Exception("Result returned not true while linking loots to product as stock");

            $resp->success = true;
            $resp->message = "Loots have been linked to product";
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while linking loots to product as stock : $e");
        }

        return $resp;
    }

    private static function createInsertParamsForLinkLootsToProductAsStock(vRecordId $productId, array $loots, int $amount = 1) : array
    {
        $params = [];

        foreach($loots as $loot)
        {
            $link = new ProductLootLink();
            
            array_push($params, $link->ctime, $link->crand, $productId->ctime, $productId->crand, $loot->ctime, $loot->crand, 0, $amount);
        }

        return $params;
    }

    private static function unittest_createInsertParamsForLinkLootsToProductAsStock() : void
    {
        $productId = new vRecordId('a',0);
        $loot = new vLoot('b', 1); $loot->quantity = 1;
        $otherLoot = new vLoot('c',2); $otherLoot->quantity = 1;

        $lootArray = [$loot];
        assert([$productId->ctime, $productId->crand, $loot->ctime, $loot->crand, 0, $loot->quantity] === static::chunkUnitTestArrayForCreateInsertParamsForLinkLootsToProductAsStock(static::createInsertParamsForLinkLootsToProductAsStock($productId, $lootArray)), 
        new Exception("UNIT TEST FAILED : expected parameter values were not returned : Actual : ".json_encode(static::createInsertParamsForLinkLootsToProductAsStock($productId, $lootArray))." | Expected : ".json_encode([$productId->ctime, $productId->crand, $loot->ctime, $loot->crand, 0, $loot->quantity])));

        $lootArray = [$loot, $otherLoot];
        assert([$productId->ctime, $productId->crand, $loot->ctime, $loot->crand, 0, $loot->quantity, $productId->ctime, $productId->crand, $otherLoot->ctime, $otherLoot->crand, 0, $otherLoot->quantity] === static::chunkUnitTestArrayForCreateInsertParamsForLinkLootsToProductAsStock(static::createInsertParamsForLinkLootsToProductAsStock($productId, $lootArray)), 
        new Exception("UNIT TEST FAILED : expected parameter values were not returned : Actual : ".json_encode(static::createInsertParamsForLinkLootsToProductAsStock($productId, $lootArray))." | Expected : ".json_encode([$productId->ctime, $productId->crand, $loot->ctime, $loot->crand, 0, $loot->quantity, $productId->ctime, $productId->crand, $otherLoot->ctime, $otherLoot->crand, 0, $otherLoot->quantity])));

        $lootArray = [$loot, $otherLoot, $loot];
        assert([$productId->ctime, $productId->crand, $loot->ctime, $loot->crand, 0, $loot->quantity, $productId->ctime, $productId->crand, $otherLoot->ctime, $otherLoot->crand, 0, $otherLoot->quantity, $productId->ctime, $productId->crand, $loot->ctime, $loot->crand, 0, $loot->quantity] === static::chunkUnitTestArrayForCreateInsertParamsForLinkLootsToProductAsStock(static::createInsertParamsForLinkLootsToProductAsStock($productId, $lootArray)), 
        new Exception("UNIT TEST FAILED : expected parameter values were not returned : Actual : ".json_encode(static::createInsertParamsForLinkLootsToProductAsStock($productId, $lootArray))." | Expected : ".json_encode([$productId->ctime, $productId->crand, $loot->ctime, $loot->crand, 0, $loot->quantity, $productId->ctime, $productId->crand, $otherLoot->ctime, $otherLoot->crand, 0, $otherLoot->quantity, $productId->ctime, $productId->crand, $loot->ctime, $loot->crand, 0, $loot->quantity])));

    }

    public static function rowToVProductLootLink(array $row) : vProductLootLink
    {
        $link = new vProductLootLink($row["ctime"], $row["crand"]);

        $link->productId = new vRecordId($row["product_ctime"], $row["product_crand"]);
        $link->lootId = new vRecordId($row["loot_ctime"], $row["loot_crand"]);
        
        $link->removed = boolval($row["removed"]);
        $link->quantity = $row["quantity"];

        return $link;
    }

    /**
     * removes the first 2 elements out of every section of 8 elements to allow the assertion of the elements in the parameters that arn't the ctime and crand of the product link itself
     */
    private static function chunkUnitTestArrayForCreateInsertParamsForLinkLootsToProductAsStock(array $params) : array
    {
        $chunks = array_chunk($params, 8);
        $result = [];

        foreach($chunks as $chunk)
        {
            $result = array_merge($result, array_slice($chunk, 2));
        }

        return $result;
    }

    private static function unittest_chunkUnitTestArrayForCreateInsertParamsForLinkLootsToProductAsStock() : void
    {
        $input = [1,1,0,0,0,0,0,0];
        $expected = [0,0,0,0,0,0];
        assert($expected === static::chunkUnitTestArrayForCreateInsertParamsForLinkLootsToProductAsStock($input), 
        new Exception("UNIT TEST FAILED : chunked array did not match expected. Expected : ".json_encode($expected)." | Actual : ".json_encode(static::chunkUnitTestArrayForCreateInsertParamsForLinkLootsToProductAsStock($input))));

        $input = [1,1,0,0,0,0,0,0,1,1,0,0,0,0,0,0];
        $expected = [0,0,0,0,0,0,0,0,0,0,0,0];
        assert($expected === static::chunkUnitTestArrayForCreateInsertParamsForLinkLootsToProductAsStock($input), 
        new Exception("UNIT TEST FAILED : chunked array did not match expected. Expected : ".json_encode($expected)." | Actual : ".json_encode(static::chunkUnitTestArrayForCreateInsertParamsForLinkLootsToProductAsStock($input))));

        $input = [1,1,0,0,0,0,0,0,1,1,0,0,0,0,0,0,1,1,0,0,0,0,0,0];
        $expected = [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];
        assert($expected === static::chunkUnitTestArrayForCreateInsertParamsForLinkLootsToProductAsStock($input), 
        new Exception("UNIT TEST FAILED : chunked array did not match expected. Expected : ".json_encode($expected)." | Actual : ".json_encode(static::chunkUnitTestArrayForCreateInsertParamsForLinkLootsToProductAsStock($input))));
    }

    private static function createInsertSqlForLinkLootsToProductAsStock(array $loots) : string
    {
        $valueClause = "";

        for($i = 0; $i < count($loots); $i++)
        {
            $valueClause .= "(?,?,?,?,?,?,?,?)";

            if($i < count($loots)-1) $valueClause .= ",";
        }

        $sql = "INSERT INTO product_loot_link (ctime, crand, ref_product_ctime, ref_product_crand, ref_loot_ctime, ref_loot_crand, removed, quantity) VALUES $valueClause";

        return $sql;
    }

    private static function unittest_createInsertSqlForLinkLootsToProductAsStock() : void
    {
        $lootArray = [null];
        assert("INSERT INTO product_loot_link (ctime, crand, ref_product_ctime, ref_product_crand, ref_loot_ctime, ref_loot_crand, removed, quantity) VALUES (?,?,?,?,?,?,?,?)" === static::createInsertSqlForLinkLootsToProductAsStock($lootArray), 
        new Exception("UNIT TEST FAILED : Expected parameterized string did not match actual Actual : ".static::createInsertSqlForLinkLootsToProductAsStock($lootArray))." | Expected : '(?,?,?,?,?,?,?,?)'");

        $lootArray = [null,null];
        assert("INSERT INTO product_loot_link (ctime, crand, ref_product_ctime, ref_product_crand, ref_loot_ctime, ref_loot_crand, removed, quantity) VALUES (?,?,?,?,?,?,?,?),(?,?,?,?,?,?,?,?)" === static::createInsertSqlForLinkLootsToProductAsStock($lootArray), 
        new Exception("UNIT TEST FAILED : Expected parameterized string did not match actual Actual : ".static::createInsertSqlForLinkLootsToProductAsStock($lootArray))." | Expected : '(?,?,?,?,?,?,?,?),(?,?,?,?,?,?,?,?)'");

        $lootArray = [null,null,null];
        assert("INSERT INTO product_loot_link (ctime, crand, ref_product_ctime, ref_product_crand, ref_loot_ctime, ref_loot_crand, removed, quantity) VALUES (?,?,?,?,?,?,?,?),(?,?,?,?,?,?,?,?),(?,?,?,?,?,?,?,?)" === static::createInsertSqlForLinkLootsToProductAsStock($lootArray), 
        new Exception("UNIT TEST FAILED : Expected parameterized string did not match actual Actual : ".static::createInsertSqlForLinkLootsToProductAsStock($lootArray))." | Expected : '(?,?,?,?,?,?,?,?),(?,?,?,?,?,?,?,?),(?,?,?,?,?,?,?,?)'");

    }
    
    /**
     * Version of does product exist by locator that instead checks the boolean "removed" flag
     * This change is to be compatible with keeping the products in the table rather than deleting them
     */
    public static function doesProductExistByLocator(string $locator) : Response
    {
        $resp = new Response(false, "unkown error in checking if product exist", null);

        $sql = "SELECT ctime FROM product WHERE locator = ? AND removed = 0 LIMIT 1;";

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


    /**
     * Version of removeProdutById that doesn't delete rows but instead marks a "removed" flags
     * this change is done to hopefully retain data for future analytics on what products were sold, even if they arn't sold anymore
     */
    public static function removeProductById(vRecordId $productId) : Response
    {
        $resp = new Response(false, "unkown error in removing product", null);

        try
        {
            $linkVoidResp = static::markPriceLinksAsVoid($productId);

            if($linkVoidResp->success)
            {
                $sql = "UPDATE product SET removed = 1 WHERE ctime = ? AND crand = ?;";

                $params = [$productId->ctime, $productId->crand];

                Database::executeSqlQuery($sql, $params);

                $resp->success = true;
                $resp->message = "product deleted";
            }
            else
            {
                $resp->message = "Failed to removed price links to product before deleting product : $linkVoidResp->message";
            }
        }
        catch(Exception $e)
        {
            throw new Exception("exception caught while removing product : $e");
        }

        return $resp;
    }


    /**
     * Version of removePricesLinksToProduct that simply marks a boolean "void" flag instead of removing the product-price-link
     * this change is done to hopefully preserve data for possible analytics of prices for products in the future
     */
    private static function markPriceLinksAsVoid(vRecordId $product) : response
    {
        $resp = new Response(false, "unkown error in marking price links as void", null);

        try
        {
            $sql = "UPDATE product_price_link SET void = 1 WHERE ref_product_ctime = ? AND ref_product_crand = ?;";

            $params = [$product->ctime, $product->crand];

            $result = database::executeSqlQuery($sql, $params);

            if(!$result)
            {
                throw new Exception("Failed to mark price links as void");
            }

            $resp->success = true;
            $resp->message = "voided all price link to product";
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while marking price links as void : $e");
        }

        return $resp;
    }

    public static function upsertProduct(Product $product) : Response
    {
        $resp = new Response(false, "unkown error in updating or inserting product", null);

        try
        {
            $productExistsResp = static::doesProductExist($product);

            if(!$productExistsResp->success)
            {
                $resp->message = "Error in finding if product exists : $productExistsResp->message";
                return $resp;
            }

            //if product exists already, update it; if product does not exist, insert it
            if($productExistsResp->data)
            {//update
                $updateResp = static::updateProduct($product);

                if(!$updateResp->success)
                {
                    $resp->message = "Error in updating product after it was found to exist : $updateResp->message";
                    return $resp;
                }

                $viewProduct = new vProduct($product->ctime, $product->crand);
                $viewProduct->locator = $product->locator;

                $getProduct = static::getProduct($viewProduct);
                if(!$getProduct->success) throw new Exception("Failed to get product for linking prices after update");

                $linkPriceResp = static::linkProductToPrices($getProduct->data, $product->price);

                if($linkPriceResp->success)
                {
                    $resp->success = true;
                    $resp->message = "Product was updated as it already existed";
                }
                else
                {
                    $resp->message = "product was updates however there was an error in linking the updated prices";
                }
                
            }
            else
            {//insert
                $insertResp = static::insertProduct($product);

                if(!$insertResp->success)
                {
                    $resp->message = "Error in inserting product after it was found it did not exist : $insertResp->message";
                    return $resp;
                }

                $linkPriceResp = static::linkProductToPrices($product, $product->price);

                if($linkPriceResp->success)
                {
                    $resp->success = true;
                    $resp->message = "Product was inserted as it did not exist"; 
                }
                else
                {
                    $resp->message = "product was inserted however there was an error with linking the prices";
                }
            }
            
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while updating or inserting product : $e");
        }

        return $resp;
    }

    private static function doesProductExist(Product $product) : Response
    {
        $resp = new Response(false, "unkown error in getting product by id", null);

        $sql = "SELECT ctime, crand FROM product WHERE (ctime = ? AND crand = ?) OR locator = ? limit 1;";

        $params = [$product->ctime, $product->crand, $product->locator];

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
            ".static::$columnsInProductView."
        FROM v_product 
        WHERE ctime = ? AND crand = ? 
        LIMIT 1;";


            $params = [$Id->ctime, $Id->crand];

        try
        {
            $result = Database::executeSqlQuery($sql, $params);

            if($result->num_rows > 0)
            {
                $product = static::rowToVProduct($result->fetch_assoc());

                $priceResp = static::getBasePriceForProduct($product);

                if($priceResp->success)
                {
                    $product->price = $priceResp->data;

                    $resp->success = true;
                    $resp->message = "Product found and returned";
                    $resp->data = $product;
                }
                else
                {
                    $resp->message = "failed to get prices for product by id : $priceResp->message";
                }

                
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

    public static function getProduct(vProduct|Product $product) : Response
    {
        $resp = new Response(false, "Unkown eror in getting product by id", null);

        $sql = "SELECT 
            ".static::$columnsInProductView."
        FROM v_product 
        WHERE (ctime = ? AND crand = ?) OR locator = ?
        LIMIT 1;";


        $params = [$product->ctime, $product->crand, $product->locator];

        try
        {
            $result = Database::executeSqlQuery($sql, $params);

            if($result->num_rows > 0)
            {
                $product = static::rowToVProduct($result->fetch_assoc());

                $priceResp = static::getBasePriceForProduct($product);

                if($priceResp->success)
                {
                    $product->price = $priceResp->data;

                    $resp->success = true;
                    $resp->message = "Product found and returned";
                    $resp->data = $product;
                }
                else
                {
                    $resp->message = "failed to get prices for product by id : $priceResp->message";
                }

                
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


    private static function getBasePricesForProduct(vRecordId $product) : Response
    {
        $resp = new Response(false, "unkown error in getting prices for product");

        try
        {
            $sql = "SELECT 
            vp.ctime, 
            vp.crand, 
            vp.amount, 
            vp.currency_code, 
            vp.item_ctime, 
            vp.item_crand, 
            vp.item_name, 
            vp.item_desc,  
            vp.media_path_small, 
            vp.media_path_large, 
            vp.media_path_back,
            vp.item_is_fungible
            FROM v_price vp 
            JOIN product_price_link ppl ON ppl.ref_price_ctime = vp.ctime AND ppl.ref_price_crand = vp.crand
            WHERE ppl.ref_product_ctime = ? AND ppl.ref_product_crand = ?;";

            $params = [$product->ctime, $product->crand];

            $result = Database::executeSqlQuery($sql, $params);

            if($result)
            {
                if($result->num_rows > 0)
                {
                    $prices = [];

                    while($row = $result->fetch_assoc())
                    {
                        array_push($prices, static::priceToView($row));
                    }

                    $resp->success = true;
                    $resp->message = "prices returned for product";
                    $resp->data = $prices;
                }
                else
                {
                    $resp->message = "No prices found for product";
                }
            }
            else
            {
                $resp->message = "Sql error in getting prices for product";
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while getting prices for product : $e");
        }

        return $resp;
    }

    private static function updateProduct(Product $product) : Response
    {
        $resp = new Response(false, "unkown error in updating product", null);

        $sql = "UPDATE product SET 
        `name` = ?, 
        `description` = ?, 
        removed = ?,
        locator = ?, 
        tag = ?,
        categories = ?,
        ref_store_ctime = ?, 
        ref_store_crand = ?,
        ref_media_id_large = ?,
        ref_media_id_small = ?,
        ref_media_id_back = ?
        WHERE (ctime = ? and crand = ?) OR locator = ? limit 1;";

        $params = [
            $product->name,
            $product->description,
            $product->removed,
            $product->locator,
            $product->tag,
            json_encode($product->categories),
            $product->store->ctime,
            $product->store->crand,
            $product->largeMedia->crand,
            $product->smallMedia->crand,
            $product->backMedia->crand,
            $product->ctime,
            $product->crand,
            $product->locator
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

        $sql = "INSERT INTO product (
            ctime,
            crand,
            `name`,
            `description`,
            locator,
            tag,
            categories,
            ref_store_ctime,
            ref_store_crand,
            ref_media_id_large,
            ref_media_id_small,
            ref_media_id_back
            )values
            (?,?,?,?,?,?,?,?,?,?,?,?)";

        $params = [
            $product->ctime,
            $product->crand,
            $product->name,
            $product->description,
            $product->locator,
            $product->tag,
            json_encode($product->categories),
            $product->store->ctime,
            $product->store->crand,
            $product->largeMedia->crand,
            $product->smallMedia->crand,
            $product->backMedia->crand
        ];

        try
        {
            Database::executeSqlQuery($sql, $params);

            $priceResp = static::InsertAndSelectPrice($product->price);


            if($priceResp->success)
            {
                $price = static::convertPriceViewArrayToModels($priceResp->data);
                $linkResp = static::linkProductToPrice($product, $price);

                if($linkResp->success)
                {
                    $resp->success = true;
                    $resp->message = "Product Inserted";
                }
                else
                {
                    $resp->message = "failed to link prices to product after insertion";
                }
            }
            else
            {
                $resp->message = "Failed to get prices of product during insertion";
            }

            
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while inserting product : $e");
        }

        return $resp;
    }

    private static function convertPriceViewArrayToModels(array $priceViews) : array
    {
        $priceModels = [];

        foreach($priceViews as $view)
        {
            $model = new Price($view->amount, $view->currencyCode, $view->item);
            $model->ctime = $view->ctime;
            $model->crand = $view->crand;

            array_push($priceModels, $model);
        }

        return $priceModels;
    }

     /**
     * Returns an array of vPrices from an array of prices.
     * Inserts any prices that don't already exist.
     * This function should be used when making new products as it will ensure if there is
     * already a duplicate price in the database, the already existing price will be used for the product, saving memory and query time
     * 
     * @param array $prices the array of prices to be inserted
     * @return Response the response object whose data holds the array of vPrices
     */
    public static function InsertAndSelectPrices(array $prices) : Response
    {
        $resp = new Response(false, "unkown error in getting prices", null);

        if(static::validatePriceArray($prices) == false) throw new Exception("prices array must contain only prices");

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
                        $allPrices = array_merge($gotResp->data, $insertResp->data) ;


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

    /**
     * Inserts new linking record between a product and prices
     * Removes old linking records not found in the provided prices array
     * 
     * @param vRecordId $product the product of which the prices will be linked to
     * @param array $prices the prices which will be linked to the product. These must be either Price or vPrice objects
     * 
     * @return Response the response object which will returne true success if the linking was successful
     */
    private static function linkProductToPrices(vRecordId $product, array $prices) : Response
    {
        $resp = new Response(false, "unkown error in linking products to prices", null);

        try
        {
            if(static::validatePriceArray($prices) == false) throw new Exception("Price array must contain only price or vPrice objects");

            $valueClause = static::returnValueClauseForLinkingProductToPrices($prices);

            $sql = "INSERT IGNORE INTO product_price_link (ctime, crand, ref_product_ctime, ref_product_crand, ref_price_ctime, ref_price_crand) VALUES $valueClause";

            $params = static::returnInsertionParamsForLinkingProductToPrices($product, $prices);

            $insertResult = Database::executeSqlQuery($sql, $params);

            if($insertResult)
            {
                $removeOldResp = static::removeOldLinksToProduct($product, $prices);

                if($removeOldResp->success)
                {
                    $resp->success = true;
                    $resp->message = "Product has been linked to prices";
                }
                else
                {
                    $resp->message = "error in removing void product price links";
                }
            }
            else
            {
                $resp->message = "Sql error in inserting product price links";
            }

            
        }
        catch(Exception $e)
        {
            throw new Exception("execption caught while linking products to prices. $e");
        }

        return $resp;
    }

    private static function removeOldLinksToProduct(vRecordId $product, array $prices) : Response
    {
        $resp = new Response(false, "unkown error in clearing orphan prices", null);

        try
        {
            $whereClause = static::returnWhereClauseForRemovingOldLinksToProduct($prices);

            $sql = "DELETE ppl FROM product_price_link ppl WHERE $whereClause";

            $params = static::returnParamsForRemovingOldLinksToProduct($product, $prices);

            $result = Database::executeSqlQuery($sql, $params);

            if(!$result)
            {
                throw new Exception("Delete failed in clearing old price links for product");
            }

            $clearResp = static::ClearOrphanPrices();

            if($clearResp->success)
            {
                $resp->success = true;
                $resp->message = "Old price links cleared";
            }
            else
            {
                $resp->message = "failed to clear orphan prices after clearing links";
            }

            
        }
        catch(exception $e)
        {
            throw new Exception("Exception caught in clearing orphan prices : $e");
        }

        return $resp;
    }

    private static function ClearOrphanPrices() : Response
    {
        $resp = new Response(false, "unkown error in clearing orphan prices", null);

        try
        {
            $sql = "DELETE p FROM price p WHERE NOT EXISTS ( SELECT ctime FROM product_price_link pl WHERE pl.ref_price_ctime = p.ctime AND pl.ref_price_crand = p.crand);";

            Database::executeSqlQuery($sql, []);

            $resp->success = true;
            $resp->message = "Orphan prices cleared";
        }
        catch(Exception $e)
        {
            throw new Exception("exception caught while clearing orphan prices : $e");
        }

        return $resp;
    }

    private static function returnParamsForRemovingOldLinksToProduct(vRecordId $product, array $prices) : array
    {
        $params = [$product->ctime, $product->crand];

        foreach($prices as $price)
        {
            array_push($params, $price->ctime, $price->crand);
        }

        return $params;
    }

    private static function unittest_returnParamsForRemovingOldLinksToProduct() : void
    {
        $product = new RecordId();
        $price = new Price();
        $prices = [$price];

        $actual = json_encode(static::returnParamsForRemovingOldLinksToProduct($product, $prices));
        $expected = json_encode([$product->ctime, $product->crand, $price->ctime, $price->crand]);
        assert($actual === $expected, "UNIT TEST FAILED | actual : $actual | expected : $expected");

        $prices = [$price,$price];

        $actual = json_encode(static::returnParamsForRemovingOldLinksToProduct($product, $prices));
        $expected = json_encode([$product->ctime, $product->crand, $price->ctime, $price->crand, $price->ctime, $price->crand]);
        assert($actual === $expected, "UNIT TEST FAILED | actual : $actual | expected : $expected");

        $prices = [$price,$price,$price];

        $actual = json_encode(static::returnParamsForRemovingOldLinksToProduct($product, $prices));
        $expected = json_encode([$product->ctime, $product->crand, $price->ctime, $price->crand, $price->ctime, $price->crand, $price->ctime, $price->crand]);
        assert($actual === $expected, "UNIT TEST FAILED | actual : $actual | expected : $expected");
    }

    private static function returnWhereClauseForRemovingOldLinksToProduct(array $prices) : string
    {
        $whereClause = "(ref_product_ctime = ? AND ref_product_crand = ?)";

        foreach($prices as $price)
        {
            $whereClause .= " AND (ref_price_ctime <> ? AND ref_price_crand <> ?)";
        }

        $whereClause .= ";";

        return $whereClause;
    }

    private static function unittest_returnWhereClauseForRemovingVoidLinksToProduct() : void
    {
        $prices = [1];

        $clause = static::returnWhereClauseForRemovingOldLinksToProduct($prices);
        $expected = "(ref_product_ctime = ? AND ref_product_crand = ?) AND (ref_price_ctime <> ? AND ref_price_crand <> ?);";

        assert($clause ===  $expected, new Exception("UNIT TEST FAILED | Actual : '".$clause."' | Expected : '".$expected."'))"));
    }

    private static function returnValueClauseForLinkingProductToPrices(array $prices) : string
    {
        if(empty($prices)) throw new InvalidArgumentException("Prices array cannot be empty");

        $valueClause = "(?,?,?,?,?,?)";

        for($i = 1; $i > count($prices); $i++)
        {
            $valueClause .= ", (?,?,?,?,?,?)";
        }

        return $valueClause;
    }

    private static function returnInsertionParamsForLinkingProductToPrices(vRecordId $productId, array $prices, bool $testing = false) : array
    {
        if(empty($prices)) throw new InvalidArgumentException("Prices array cannot be empty");

        $bindingArray = [];

        foreach($prices as $price)
        {
            $link = new ProductPriceLink();
            
            if($testing) //exclude the link ctime and crand as it's hard to 'test' those
            {
                array_push($bindingArray, $productId->ctime, $productId->crand, $price->ctime, $price->crand);
                continue;  
            }

            array_push($bindingArray, $link->ctime, $link->crand, $productId->ctime, $productId->crand, $price->ctime, $price->crand);
        }

        return $bindingArray;
    }

    private static function unittest_returnInsertionParamsForLinkingProductToPrices() : void
    {
        $productId = new RecordId();
        $price = new Price();
        $prices = [$price];

        $actual = json_encode(static::returnInsertionParamsForLinkingProductToPrices($productId, $prices, true));
        $expected = json_encode([$productId->ctime, $productId->crand, $price->ctime, $price->crand]);
        assert($actual === $expected, "UNIT TEST FAILED | actual : $actual | expected : $expected");

        $prices = [$price, $price];

        $actual = json_encode(static::returnInsertionParamsForLinkingProductToPrices($productId, $prices, true));
        $expected = json_encode([$productId->ctime, $productId->crand, $price->ctime, $price->crand, $productId->ctime, $productId->crand, $price->ctime, $price->crand]);
        assert($actual === $expected, "UNIT TEST FAILED | actual : $actual | expected : $expected");
    }

    private static function rowToVProduct($row): vProduct
    {
        $owner = new vAccount($row['store_owner_ctime'],(int)$row['store_owner_crand']);
        $owner->username = $row['store_owner_username'];

        $smallIcon = new vMedia();
        if(!is_null($row['small_media_media_path']))$smallIcon->setMediaPath($row['small_media_media_path']);
        $largeIcon = new vMedia();
        if(!is_null($row['large_media_media_path']))$largeIcon->setMediaPath($row['large_media_media_path']);
        $backIcon = new vMedia();
        if(!is_null($row['back_media_media_path']))$backIcon->setMediaPath($row['back_media_media_path']);

        $store = new vStore($row['store_ctime'], (int)$row['store_crand']);
        $store->name = $row['store_name'];
        $store->description = $row['store_description'];
        $store->locator = $row['store_locator'];

        $product= new vProduct($row['ctime'],(int)$row['crand']);
            $product->locator = $row["locator"];
            $product->tag = $row["tag"];
            $product->categories = json_decode($row["categories"]);
            $product->name = $row["name"];
            $product->description = $row["description"];
            $product->stock = $row["stock"];
            $product->amountAvailable = $row["amount_available"];
            $product->removed = boolval($row["removed"]);
            $product->owner = $owner;
            $product->store = $store;
            $product->mediaSmall = $smallIcon;
            $product->mediaLarge = $largeIcon;
            $product->mediaBack = $backIcon;

        return $product;
    }

    //PRICE

    public static string $allViewPriceColumns = "
        ctime, 
        crand, 
        amount, 
        currency_code, 
        item_ctime, 
        item_crand, 
        item_name, 
        item_desc,  
        media_path_small, 
        media_path_large, 
        media_path_back,
        item_is_fungible";

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
            $key = $price->ctime."#".(string)$price->crand;

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

        if(!Static::validatePriceArray($prices)) throw new InvalidArgumentException("Price array must be of either Price or vPrice objects");

        $whereClause = static::constructWhereClauseForGetPricesByCurrencyAndItemAmount($prices);

        $sql = "SELECT 
        ".static::$allViewPriceColumns." 
        FROM v_price 
        WHERE $whereClause";

        $params = static::returnWhereClauseBindingArrayForGetPricesByCurrencyAndItemAmount($prices);

        try
        {
            $result = Database::executeSqlQuery($sql, $params);

            $gotPrices = [];
            
            while($row = $result->fetch_assoc())
            {
                array_push($gotPrices, static::priceToView($row));    
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
        if(!static::validatePriceArray($prices)) throw new InvalidArgumentException("price array must be of Price or vPrice objects");

        $bindingArray = [];

        foreach($prices as $price)
        {
            $item = $price instanceof price ? $price->itemId : $price->item;

            [$refItemCtime, $refItemCrand] = is_null($item) ? [null, null] : [$item->ctime, $item->crand];

            array_push($bindingArray, $price->amount, $price->currencyCode, $refItemCtime, $refItemCrand);
        }

        return $bindingArray;
    }

    private static function unittest_returnWhereClauseBindingArrayForGetPricesByCurrencyAndItemAmount() : void
    {
        $bindingArray = static::returnWhereClauseBindingArrayForGetPricesByCurrencyAndItemAmount([new Price()]);
        $expected = [0,null,null,null];

        $expectedJson = json_encode($expected);
        $actualJson = json_encode($bindingArray);
        assert($bindingArray === $expected, "unit test failed : return where cluase array did not match expected. Excepted : '$expectedJson' | Actual : '$actualJson'");
    }

    private static function constructWhereClauseForGetPricesByCurrencyAndItemAmount(array $prices) : string
    {
        if(empty($prices)) throw new InvalidArgumentException("Prices array cannot be empty");

        $whereClause = "(amount = ? AND (currency_code = ? OR (item_ctime = ? AND item_crand = ?)))";

        for($i = 1; $i < count($prices); $i++)
        {
            $whereClause .= " OR (amount = ? AND (currency_code = ? OR (item_ctime = ? AND item_crand = ?)))";
        }

        return $whereClause;
    }

    private static function unittest_constructWhereClauseForGetPricesByCurrencyAndItemAmount() : void
    {
        $whereClause = static::constructWhereClauseForGetPricesByCurrencyAndItemAmount([new Price()]);
        $expected = "(amount = ? AND (currency_code = ? OR (item_ctime = ? AND item_crand = ?)))";
        assert($whereClause === $expected, "unit test failed : return where cluase did not match expected. Excepted : '$expected' | Actual : '$whereClause'");

        $whereClause = static::constructWhereClauseForGetPricesByCurrencyAndItemAmount([new Price(), new Price()]);
        $expected = "(amount = ? AND (currency_code = ? OR (item_ctime = ? AND item_crand = ?))) OR (amount = ? AND (currency_code = ? OR (item_ctime = ? AND item_crand = ?)))";
        assert($whereClause === $expected, "unit test failed : return where cluase did not match expected. Excepted : '$expected' | Actual : '$whereClause'");

        $whereClause = static::constructWhereClauseForGetPricesByCurrencyAndItemAmount([new Price(), new Price(), new Price()]);
        $expected = "(amount = ? AND (currency_code = ? OR (item_ctime = ? AND item_crand = ?))) OR (amount = ? AND (currency_code = ? OR (item_ctime = ? AND item_crand = ?))) OR (amount = ? AND (currency_code = ? OR (item_ctime = ? AND item_crand = ?)))";
        assert($whereClause === $expected, "unit test failed : return where cluase did not match expected. Excepted : '$expected' | Actual : '$whereClause'");
    }

    private static function insertPriceArrayAndReturnInsertion(array $prices) : Response
    {
        $resp = new Response(false, "Unkown error in inserting price array", null);
        
        try
        {
            if(static::validatePriceArray($prices) == false) throw new InvalidArgumentException("prices array must contain only prices");

            $insertionValueClause = static::constructInsertionValueClauseForPrices($prices);

            $insertSql = "INSERT INTO price (ctime, crand, amount, currency_code, ref_item_ctime, ref_item_crand) VALUES $insertionValueClause;";
            
            $insertParams = static::returnParamsForInsertionForNonExistingPrices($prices);

        

            $insertResult = Database::executeSqlQuery($insertSql, $insertParams);

            if($insertResult != false)
            {
                $whereClause = static::constructWhereClauseForGetPricesByCurrencyAndItemAmount($prices);    

                $selectSql = "SELECT ".static::$allViewPriceColumns." FROM v_price WHERE $whereClause;";
                $selectParams = static::returnWhereClauseBindingArrayForSelectPriceArrayAfterInsertion($prices);

                $selectResult = Database::executeSqlQuery($selectSql, $selectParams);

                if($selectResult->num_rows > 0)
                {
                    $gotPrices = [];
                    while($row = $selectResult->fetch_assoc())
                    {
                        $gotPrice = static::priceToView($row);
                        array_push($gotPrices, $gotPrice);
                    }

                    $resp->success = true;
                    $resp->message = "Prices inserted and returned";
                    $resp->data = $gotPrices;
                }
                else
                {
                    $resp->message = "Prices were not found after insertion";
                }
            }
            else
            {
                $resp->message = "Prices insertion failed";
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while inserting and returning price array : $e");
        }

        return $resp;
    }

    private static function constructInsertionValueClauseForPrices(array $prices) : string
    {
        $insertionValueClause = "(?,?,?,?,?,?)";

        for($i = 1; $i < count($prices); $i++)
        {
            $insertionValueClause .= ",(?,?,?,?,?,?)";
        }

        return $insertionValueClause;
    }

    private static function unittest_constructInsertionValueClauseForPrices() : void
    {
        $price = new Price();
        $prices = [$price];

        $actual = static::constructInsertionValueClauseForPrices($prices);
        $expected = "(?,?,?,?,?,?)";
        assert($actual === $expected, new Exception("UNIT TEST FAILED | actual : $actual | expected : $expected"));

        $price = new Price();
        $prices = [$price,$price];

        $actual = static::constructInsertionValueClauseForPrices($prices);
        $expected = "(?,?,?,?,?,?),(?,?,?,?,?,?)";
        assert($actual === $expected, new Exception("UNIT TEST FAILED | actual : $actual | expected : $expected"));

        $price = new Price();
        $prices = [$price, $price, $price];

        $actual = static::constructInsertionValueClauseForPrices($prices);
        $expected = "(?,?,?,?,?,?),(?,?,?,?,?,?),(?,?,?,?,?,?)";
        assert($actual === $expected, new Exception("UNIT TEST FAILED | actual : $actual | expected : $expected"));

    }

    private static function returnWhereClauseBindingArrayForSelectPriceArrayAfterInsertion(array $prices) : array
    {
        if(empty($prices)) throw new InvalidArgumentException("Prices array cannot be empty");

        $bindingArray = [];

        foreach($prices as $price)
        {
            [$refItemCtime, $refItemCrand] = is_null($price->itemId) ? [null, null] : [$price->itemId->ctime, $price->itemId->crand];

            array_push($bindingArray, $price->amount, $price->currencyCode, $refItemCtime, $refItemCrand);
        }

        return $bindingArray;
    }

    private static function unittest_returnWhereClauseBindingArrayForSelectPriceArrayAfterInsertion() : void
    {
        $price = new Price();
        $prices = [$price];

        $actual = json_encode(static::returnWhereClauseBindingArrayForSelectPriceArrayAfterInsertion($prices));
        $expected = json_encode([0,null,null,null]);

        assert($actual === $expected, new Exception("UNIT TEST FAIELD | actual : $actual | expected : $expected"));
    }

    private static function returnParamsForInsertionForNonExistingPrices(array $prices) : array
    {
        $params = [];

        foreach($prices as $price)
        {
            [$refItemCtime, $refItemCrand] = is_null($price->itemId) ? [null, null] : [$price->itemId->ctime, $price->itemId->crand];

            array_push($params, $price->ctime, $price->crand, $price->amount, (string)$price->currencyCode, $refItemCtime, $refItemCrand);
        }

        return $params;
    }

    private static function unittest_returnParamsForInsertionForNonExistingPrices() : void
    {
        $price = new Price();
        $prices = [$price];

        $actual = json_encode(static::returnParamsForInsertionForNonExistingPrices($prices));
        $expected = json_encode([$price->ctime, $price->crand, 0, "", null, null]);

        assert($actual === $expected, new exception("UNIT TEST FAILED | actual : $actual | expected : $expected"));
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
            if(!($price instanceof Price || $price instanceof vPrice))
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
        if(!empty($row["media_path_back"]))$iconBack->setMediaPath($row["media_path_back"]);

        $item = new vItem($row["item_ctime"], $row["item_crand"]);
            $item->name = $row["item_name"];
            $item->description = $row["item_desc"];
            $item->iconSmall = $iconSmall;
            $item->iconBig = $iconLarge;
            $item->iconBack = $iconBack;
            $item->fungible = boolval($row["item_is_fungible"]);

        $currencyCode = $row["currency_code"] != null ? CurrencyCode::from($row["currency_code"]) : null;

        return new vPriceComponent(
            $row["ctime"], 
            $row["crand"],
            $row["amount"],
            $item,
            $currencyCode
        );
    }

    //STORE

    private static string $columnsInStoreTable = "ctime, crand, name, locator, description, ref_owner_ctime, ref_owner_crand";
    private static string $columnsInStoreView = "ctime, crand, name, locator, description, owner_username, owner_ctime, owner_crand";


    public static function getStoreByAccountId(vRecordId $accountId) : Response
    {
      $resp = new Response(false, "Unkown error in getting store by Account Id", null);

        try
        {
            $sql = "SELECT ".static::$columnsInStoreView." FROM v_store WHERE owner_crand = ? LIMIT 1;";

            $params = [$accountId->crand];

            $result = Database::executeSqlQuery($sql, $params);

            if(!$result) throw new Exception("Result returned false when selecting for store");
            
            if($result->num_rows > 0)
            {
                $store = static::rowToVStore($result->fetch_assoc());
                $products = static::getStoreProductsById($store);
                
                if(count($products) > 0)static::populateProductsWithBasePrices($products);

                $store->products = $products;

                $resp->success = true;
                $resp->message = "Store Returned";
                $resp->data = $store;
            }
            else
            {
                $resp->success = true;
                $resp->message = "Store Not Found";
            }
            
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while getting store by account Id : $e");
        }

        return $resp;  
    }

    public static function getStoreByLocator(string $locator) : Response
    {
        $resp = new Response(false, "Unkown error in getting store by Id", null);

        try
        {
            $sql = "SELECT ".static::$columnsInStoreView." FROM v_store WHERE locator = ? LIMIT 1;";

            $params = [$locator];

            $result = Database::executeSqlQuery($sql, $params);

            if(!$result) throw new Exception("Result returned false when selecting for store");
            
            if($result->num_rows > 0)
            {
                $store = static::rowToVStore($result->fetch_assoc());
                $products = static::getStoreProductsById($store);
                
                if(count($products) > 0)static::populateProductsWithBasePrices($products);

                $store->products = $products;

                $resp->success = true;
                $resp->message = "Store Returned";
                $resp->data = $store;
            }
            else
            {
                $resp->success = true;
                $resp->message = "Store Not Found";
                $resp->data = false;
            }
            
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while getting store by Id : $e");
        }

        return $resp;
    }

    public static function getStoreById(vRecordId $storeId) : Response
    {
        $resp = new Response(false, "Unkown error in getting store by Id", null);

        try
        {
            $sql = "SELECT ".static::$columnsInStoreView." FROM v_store WHERE ctime = ? AND crand = ? LIMIT 1;";

            $params = [$storeId->ctime, $storeId->crand];

            $result = Database::executeSqlQuery($sql, $params);

            if(!$result) throw new Exception("Result returned false when selecting for store");
            
            if($result->num_rows > 0)
            {
                $store = static::rowToVStore($result->fetch_assoc());
                $products = static::getStoreProductsById($storeId);
                
                if(count($products) > 0)static::populateProductsWithBasePrices($products);

                $store->products = $products;

                $resp->success = true;
                $resp->message = "Store Returned";
                $resp->data = $store;
            }
            else
            {
                $resp->message = "Store Not Found";
            }
            
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while getting store by Id : $e");
        }

        return $resp;
    }

    private static function getStoreProductsById(vRecordId $storeId) : array
    {
        try
        {
            $sql = "SELECT ctime,
                crand,
                `name`,
                `description`,
                locator,
                tag,
                categories,
                stock,
                amount_available,
                removed,
                store_name,
                store_locator,
                store_description,
                store_owner_username,
                store_owner_ctime,
                store_owner_crand,
                store_ctime,
                store_crand,
                large_media_media_path,
                small_media_media_path,
                back_media_media_path
                FROM v_product where store_ctime = ? AND store_crand = ?;
            ";

            $params = [$storeId->ctime, $storeId->crand];

            $result = database::executeSqlQuery($sql, $params);

            if(!$result) throw new Exception("Result returned false while getting store products by store id");

            $products = [];

            if($result->num_rows > 0)
            {
                while($row = $result->fetch_assoc())
                {
                    $product = static::rowToVProduct($row);
                    array_push($products, $product);
                }
            }

            return $products;
        }
        catch(Exception $e)
        {
            throw new Exception("Exception while getting store products by id : $e");
        }

        return [];
    }

    private static function populateProductsWithBasePrices(array &$products) : void
    {
        $params = [];
        $selectTable = static::createSelectTableForPopulateProductsWithBasePrices($products, $params);
        $sql = "SELECT 
        vppl.product_ctime,
        vppl.product_crand,
        vppl.price_ctime as `ctime`,
        vppl.price_crand as `crand`,
        vppl.amount,
        vppl.currency_code,
        vppl.item_ctime,
        vppl.item_crand,
        vppl.item_name,
        vppl.item_desc,
        vppl.media_path_small,
        vppl.media_path_large,
        vppl.media_path_back
        FROM v_product_price_link vppl
        JOIN ($selectTable) pt
        WHERE pt.product_ctime = vppl.product_ctime AND pt.product_crand = vppl.product_crand";

        $result = Database::executeSqlQuery($sql, $params);

        if(!$result) throw new Exception("result returned false attempting to populate cart products with base prices");

        $priceArray = static::createAssocProductIdToPricesArrayForPopulateProductsWithBasePrices($result);

        for($i = 0; $i < count($products); $i++)
        {
            $product = $products[$i];

            $foundPriceArray = null;

            //find matching set of prices for the product
            foreach($priceArray as $priceComponent)
            {
                if($priceComponent["productId"]->ctime !== $product->ctime || $priceComponent["productId"]->crand !== $product->crand) continue;
                $foundPriceArray = $priceComponent["prices"];
                break;
            }

            if(is_null($foundPriceArray)) throw new RuntimeException("no price array was found for product while populating prodcuts with base prices");

            $product->price = $foundPriceArray;

            $products[$i] = $product;
        }  
    }

    private static function createAssocProductIdToPricesArrayForPopulateProductsWithBasePrices(mysqli_result $result) : array
    {
        $prices = [];

        while($row = $result->fetch_assoc())
        {
            $vPrice = static::rowTovPriceComponent($row);
            $productId = new vRecordId($row["product_ctime"], $row["product_crand"]);

            $alreadyExistingPricedProduct = null;

            for($i = 0; $i < count($prices); $i++)
            {
                $price = $prices[$i];
                if($price["productId"]->ctime !== $productId->ctime || $price["productId"]->crand !== $productId->crand) 
                {
                    continue;
                }

                array_push($price["prices"], $price); 
                $alreadyExistingPricedProduct = $i; 
                break;
            }

            if($alreadyExistingPricedProduct === null)
            {
                array_push($prices, ["productId"=>$productId, "prices"=>[$vPrice]]);
            }
        }

        return $prices;
    }

    private static function rowTovPriceComponent(array $row) : vPriceComponent
    {
        $price = new vPriceComponent($row["ctime"], $row["crand"]);

        $price->amount = $row["amount"];
        $price->currencyCode = empty($row["currency_code"]) ? null : CurrencyCode::from($row["currency_code"]);
       
            $item = new vItem($row["item_ctime"], $row["item_crand"]);
            $item->name = $row["item_name"];
            $item->description = $row["item_desc"];
                $iconSmall = new vMedia();
                $iconSmall->setMediaPath($row["media_path_small"]);
                $iconBig = new vMedia();
                $iconBig->setMediaPath($row["media_path_large"]);
                $iconBack = new vMedia();
                if(!empty($row["media_path_back"]))$iconBack->setMediaPath($row["media_path_back"]);
            $item->iconSmall = $iconSmall;
            $item->iconBig = $iconBig;
            $item->iconBack = $iconBack;
        $price->item = $item;
        
        return $price;
    }

    private static function createSelectTableForPopulateProductsWithBasePrices(array $products, array &$params) : string
    {
        if(count($products) <= 0) throw new InvalidArgumentException("\$products array must contain at least one element");
        
        $selectTable = "";

        for($i = 0; $i < count($products); $i++)
        {
            $product = $products[$i];

            if($i === 0)
            {
                $selectTable .= "(Select ? as `product_ctime`, ? as `product_crand`)";
                array_push($params, $product->ctime, $product->crand);
                continue;
            }

            $selectTable .= "UNION ALL (Select ?, ?)";
            array_push($params, $product->ctime, $product->crand);
        }

        return $selectTable;
    }

    /**
     * Converts a row from the view v_store in the DB into a vStore object
     * 
     * @param array $row the assosiative array which represents the row from the database
     * @return vStore $store the store object returned from the retreived row
     */
    public static function rowToVStore(array $row) : vStore
    {
        $store = new vStore($row["ctime"], $row["crand"]);

        $store->name = $row["name"];
        $store->locator = $row["locator"];
        $store->description = $row["description"];
        $store->ownerUsername = $row["owner_username"];
        $store->owner = new vAccount($row["owner_ctime"], $row["owner_crand"]);

        return $store;
    }

    public static function addStore(Store $store) : Response
    {
        $sql = "INSERT INTO Store (ctime, crand, `name`, locator, `description`, ref_owner_ctime, ref_owner_crand)VALUES(?,?,?,?,?,?,?)";

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

    private static function linkBasePricesToArrayOfCartProducts(array $cartProducts) : void
    {

        $params = [];
        $selectTable = static::createSelectTableForPopulateBasePricesToArrayOfCartProducts($cartProducts, $params);
        $sql = "SELECT
            vp.ctime, 
            vp.crand, 
            vp.amount, 
            vp.currency_code, 
            vp.item_ctime, 
            vp.item_crand, 
            vp.item_name, 
            vp.item_desc,  
            vp.media_path_small, 
            vp.media_path_large, 
            vp.media_path_back,
            vp.item_is_fungible,
            st.cart_product_link_ctime,
            st.cart_product_link_crand
        FROM v_price vp
        JOIN product_price_link ppl ON ppl.ref_price_ctime = vp.ctime AND ppl.ref_price_crand = vp.crand
        JOIN ($selectTable) st ON st.product_ctime = ppl.ref_product_ctime AND st.product_crand = ppl.ref_product_crand
        WHERE ppl.void = 0;
        ";

        $result = Database::executeSqlQuery($sql, $params);

        if(!$result) throw new Exception("result returned false while populating base prices to array of cart products");

        $cartProductPricePairs = [];

        while($row = $result->fetch_assoc())
        {
            $price = static::rowTovPriceComponent($row);
            $cartProduct = new vRecordId($row["cart_product_link_ctime"], $row["cart_product_link_crand"]);

            array_push($cartProductPricePairs, ["price"=>$price, "cartProductId"=>$cartProduct]);
        }

        static::linkCartProductPricePairs($cartProductPricePairs);
    }

    private static function linkCartProductPricePairs(array $cartProductPricePairs) : void
    {
        $params = [];
        $valueClause = static::createValueClauseForLinkCartProductPricePairs($cartProductPricePairs, $params);
        $sql = "INSERT INTO cart_product_price_link (ctime, crand, ref_cart_product_link_ctime, ref_cart_product_link_crand, ref_price_ctime, ref_price_crand, removed, checked_out) VALUES $valueClause";

        $result = database::executeSqlQuery($sql, $params);

        if(!$result) throw new Exception("result returned false while linking cart product price pairs");
    }

    private static function createValueClauseForLinkCartProductPricePairs(array $cartProductPricePairs, &$params) : string
    {
        $valueClause = "";

        for($i = 0; $i < count($cartProductPricePairs); $i++)
        {
            $pair = $cartProductPricePairs[$i];
            $cartProductId = $pair["cartProductId"];
            $price = $pair["price"];

            $link = new recordId();

            array_push($params, $link->ctime, $link->crand, $cartProductId->ctime, $cartProductId->crand, $price->ctime, $price->crand);

            if($i === 0)
            {
                $valueClause .= "(?,?,?,?,?,?,0,0)";
                continue;
            }

            $valueClause .= ", (?,?,?,?,?,?,0,0)";
        }

        return $valueClause;
    }

    private static function createSelectTableForPopulateBasePricesToArrayOfCartProducts(array $cartProducts, array &$params) : string
    {
        $selectTable = "";

        for($i = 0; $i < count($cartProducts); $i++)
        {
            $cartProduct = $cartProducts[$i];

            array_push($params, $cartProduct->ctime, $cartProduct->crand, $cartProduct->product->ctime, $cartProduct->product->crand);

            if($i===0)
            {
                $selectTable .= "(SELECT ? as cart_product_link_ctime, ? as cart_product_link_Crand, ? as product_ctime, ? as product_crand)";
                continue;
            }

            $selectTable .= "UNION ALL (SELECT ?, ?, ?, ?)"; 
        }

        return $selectTable;
    }

    public static function removeCouponFromCartByGroupAssignmentId(vRecordId $couponGroupId) : Response
    {
        $resp = new Response(false, "unkown error in removing coupon from cart by group assignment id");

        try
        {
            $cartProducts = static::getCartProductsByCouponGroupAssignmentId($couponGroupId);

            $sql = "UPDATE coupon_cart_product_link SET removed = 1 WHERE coupon_assignment_group_ctime = ? AND coupon_assignment_group_crand = ?;";
            $params = [$couponGroupId->ctime, $couponGroupId->crand];

            $result = Database::executeSqlQuery($sql, $params);

            if($result) throw new Exception("result returned false while removing coupon from cart by group assignment id");

            static::linkBasePricesToArrayOfCartProducts($cartProducts);

            $resp->success = true;
            $resp->message = "Removed coupon from cart";
        }
        catch(Exception $e)
        {
            throw new Exception("exception caught while removing coupon from cart by group assignment id");
        }

        return $resp;
    }

    private static function getCartProductsByCouponGroupAssignmentId(vRecordId $couponGroupId) : array
    {
        $sql = "SELECT 
            ".static::$columnsInCartItemView."
            FROM v_cart_item WHERE coupon_assignment_group_ctime = ? AND coupon_assignment_group_crand = ? AND removed = 0 AND checked_out = 0)
        ";

        $result = Database::executeSqlQuery($sql, [$couponGroupId->ctime, $couponGroupId->crand]);

        if(!$result) throw new Exception("result returned false while getting cart prodcut by coupon group assignment id");

        $cartItems = static::cartItemResultToViews($result);

        return $cartItems;
    }

    public static function clearCouponsFromCart(vCart $cart) : Response
    {
        $resp = new Response(false, "unkown error in clearing coupons from cart");

        try
        {

        }
        catch(Exception $e)
        {
            throw new Exception("exception caught while clearing coupons from cart : $e");
        }

        return $resp;
    }

    public static function removeCouponByAssignedCartProduct(vCartItem $cartProduct) : Response
    {
        $resp = new Response(false, "Unkown error in removing coupon by assigned cart produt");

        try
        {
            if(is_null($cartProduct->couponGroupAssignmentId) || is_null($cartProduct->coupon))
            {
                $resp->message = "CartProduct does not have an assigned coupon";
                return $resp;
            }

            $removeCoupon = static::removeCouponFromCartByGroupAssignmentId($cartProduct->couponGroupAssignmentId);

            if(!$removeCoupon->success) throw new Exception("Failed to remove coupon by assigned cart products group assignment id");

            $resp->success = true;
            $resp->message = "Removed Coupon by assigned cart product";
        }
        catch(Exception $e)
        {
            throw new Exception("Exception cuaght removing coupon by assigned cart product : $e");
        }

        return $resp;
    }

    /**
     * Tries to apply the given coupon to cart 
     * @param vCart $cart the cart to which the coupon will try to be applied
     * @param vCoupon $counon the coupon that will try to be applied
     * 
     * @return Response the response in which the success indicate the precense of an error. 
     * The data of the resopnse will indicate whether the coupon was actually applied
     */
    public static function tryApplyCouponToCart(vCart $cart, vCoupon $coupon) : Response
    {
        $resp = new Response(false, "unkown error in attempting to apply coupon to cart");

        try
        {
            if($coupon->removed)
            {
                $resp->message = "Coupon is removed";
                return $resp;
            }

            if(!is_null($coupon->timesUsed) && !is_null($coupon->maxTimesUsed) && $coupon->timesUsed >= $coupon->maxTimesUsed)
            {
                $resp->success = true;
                $resp->message = "Coupon is out of uses";
                $resp->data = false;
                return $resp;
            }

            $now = new DateTime();
            if(!is_null($coupon->expiryTime) && $now > $coupon->expiryTime)
            {
                $resp->success = true;
                $resp->message = "Coupon is expired";
                $resp->data = false;
                return $resp;
            }

            if(!static::doesCartMeetCouponProductRequirements($cart, $coupon)) 
            {
                $resp->success = true;
                $resp->message = "Cart does not have the required products";
                $resp->data = false;
                return $resp;
            };

            $accountCouponUseResp = static::getAccountCouponUse($cart->account, $coupon);

            if(!$accountCouponUseResp->success)
            {
                $resp->message = "failed to get account coupon use : $accountCouponUseResp->message";
                return $resp;
            }

            if($accountCouponUseResp->data !== false && $accountCouponUseResp->data->remainingUses <= 0)
            {
                $resp->success = true;
                $resp->message = "Account is out of individual uses for this coupon";
                $resp->data = false;
                return $resp;
            }


            //Coupon is avialable to be used by this account

            $applyResp = static::applyCouponToCart($cart, $coupon);

            if(!$applyResp->success)
            {
                $resp->message = "Failed to apply validated and available coupon to cart";
                return $resp;
            }

            $resp->success = true;
            $resp->message = "Applied coupon to cart";
            $resp->data = true;
        }   
        catch(Exception $e)
        {
            throw new Exception("Exception caught while attempting to apply coupon : $e");
        }

        return $resp;
    }

    public static function applyCouponToCart(vCart $cart, vCoupon $coupon) : Response
    {
        $resp = new Response(false, "unknown error in applying coupon to cart");

        try
        {
        
            $conn = Database::getConnection();

            $conn->begin_transaction();

            $couponedProducts = static::getCartProductsToApplyCoupon($cart, $coupon);

            $couponCartProductLinks = [];
            static::executeQueriesToRemoveCurrentCouponCartProductLinksForCoupon($couponedProducts);
            static::executeQueriesToLinkCartProductsToCoupon($couponedProducts, $coupon, $couponCartProductLinks);
            static::executeQueriesToRemoveCurrentPricesFromCartProducts($couponedProducts);
            static::executeQueriesToAddCouponPricesToProducts($couponedProducts, $coupon);

            if(!$conn->commit()) throw new exception("Transaction returned false while attempting to apply coupon to cart");

            $resp->success = true;
            $resp->message = "applied coupon to cart";
        }
        catch(Exception $e)
        {
            $conn->rollback();
            throw new Exception("Exception caught while applying coupon to cart : $e");
        }

        return $resp;
    }

    private static function executeQueriesToRemoveCurrentCouponCartProductLinksForCoupon(array $couponedProducts) : void
    {
        if(count($couponedProducts) <= 0) return;
        
        $params = [];
        $selectTable = static::createSelectTableForExecuteQueriesToRemoveCurrentCouponCartProductLinksForCoupon($couponedProducts, $params);
        $sql = "UPDATE coupon_cart_product_link ccpl JOIN ($selectTable) st ON st.cart_product_link_ctime = ccpl.ref_cart_product_link_ctime AND st.cart_product_link_crand = ccpl.ref_cart_product_link_crand SET removed = 1";

        $result = Database::executeSqlQuery($sql, $params);

        if(!$result) throw new Exception("result returned false while executing queries to remove current coupon cart product links for coupon");
    }

    private static function createSelectTableForExecuteQueriesToRemoveCurrentCouponCartProductLinksForCoupon(array $couponedProducts, array &$params) : string
    {
        if(count($couponedProducts) <= 0) throw new InvalidArgumentException("\$couponedProducts array must contain at least one element");

        $selectTable = "";

        for($i = 0; $i < count($couponedProducts); $i++)
        {
            $cartProduct = $couponedProducts[$i];

            array_push($params, $cartProduct->ctime, $cartProduct->crand);
            
            if($i===0)
            {
                $selectTable .= "(SELECT ? as cart_product_link_ctime, ? as cart_product_link_crand)";
                continue;
            }

            $selectTable .= "UNION ALL (SELECT ?, ?)";
        }

        return $selectTable;
    }

    private static function executeQueriesToAddCouponPricesToProducts(array $cartProductLinksToApplyCouponTo, vCoupon $coupon) : void
    {
        $params = [];
        $valueClause = static::createValueClauseForExecuteQueriesToAddCouponPricesToProducts($cartProductLinksToApplyCouponTo, $coupon, $params);
        $sql = "INSERT INTO cart_product_price_link (ctime, crand, ref_cart_product_link_ctime, ref_cart_product_link_crand, ref_price_ctime, ref_price_crand, removed, checked_out) ($valueClause)";

        $result = Database::executeSqlQuery($sql, $params);

        if(!$result) throw new Exception("result returned false while executing query to add coupon prices to cart products");
    }

    private static function createValueClauseForExecuteQueriesToAddCouponPricesToProducts(array $cartProductLinksToApplyCouponTo, vCoupon $coupon, array &$params) : string
    {
        $valueClause = "";

        for($i = 0; $i < count($cartProductLinksToApplyCouponTo); $i++)
        {
            $cartProductLink = $cartProductLinksToApplyCouponTo[$i];

            $link = new CartProductPriceLink();

            array_push($params, $link->ctime, $link->crand, $cartProductLink->ctime, $cartProductLink->crand, $coupon->ctime, $coupon->crand);

            if($i === 0)
            {
                $valueClause .= "(SELECT ? as ctime, ? as crand, ? as ref_cart_product_link_ctime, ? as ref_cart_product_link_crand, cpl.ref_price_ctime, cpl.ref_price_crand, 0 as removed, 0 as checked_out FROM coupon_price_link cpl WHERE cpl.ref_coupon_ctime = ? AND cpl.ref_coupon_crand = ?)";
                continue;
            }

            $valueClause .= "UNION ALL (SELECT ?, ?, ?, ?, cpl.ref_price_ctime, cpl.ref_price_crand, 0, 0 FROM coupon_price_link cpl WHERE cpl.ref_coupon_ctime = ? AND cpl.ref_coupon_crand = ?)";
        }

        return $valueClause;
    }

    private static function getCartProductsToApplyCoupon(vCart $cart, vCoupon $coupon) : array
    {
        $limit = $coupon->requiredQuantityOfProduct;
        if(!is_int($limit) || $limit < 0) throw new InvalidArgumentException("Limit must be a positive integer or zero. Type : ".gettype($limit));
        //limit is now specifically validated to prevent SQL injection as parameterization with LIMIT clauses is not supported

        $sql = "SELECT 
            cpl.ctime,
            cpl.ctime,
            cpl.crand,
            cpl.crand,
            cpl.product_name,
            cpl.product_name,
            cpl.product_description,
            cpl.product_description,
            cpl.product_locator,
            cpl.product_locator,
            cpl.removed,
            cpl.removed,
            cpl.checked_out,
            cpl.checked_out,
            cpl.account_username,
            cpl.account_username,
            cpl.store_name,
            cpl.store_name,
            cpl.store_locator,
            cpl.store_locator,
            cpl.cart_ctime,
            cpl.cart_ctime,
            cpl.cart_crand,
            cpl.cart_crand,
            cpl.product_ctime,
            cpl.product_ctime,
            cpl.product_crand,
            cpl.product_crand,
            cpl.account_ctime,
            cpl.account_ctime,
            cpl.account_crand,
            cpl.account_crand,
            cpl.store_ctime,
            cpl.store_ctime,
            cpl.store_crand,
            cpl.store_crand,
            cpl.large_media_media_path,
            cpl.large_media_media_path,
            cpl.small_media_media_path,
            cpl.small_media_media_path,
            cpl.back_media_media_path,
            cpl.back_media_media_path,
            cpl.coupon_ctime,
            cpl.coupon_ctime,
            cpl.coupon_crand,
            cpl.coupon_crand,
            cpl.coupon_code,
            cpl.coupon_code,
            cpl.coupon_description,
            cpl.coupon_description,
            cpl.coupon_required_quantity_of_product,
            cpl.coupon_required_quantity_of_product,
            cpl.coupon_times_used,
            cpl.coupon_times_used,
            cpl.coupon_max_times_used,
            cpl.coupon_max_times_used,
            cpl.coupon_max_times_used_per_account,
            cpl.coupon_max_times_used_per_account,
            cpl.coupon_expiry_time,
            cpl.coupon_expiry_time,
            cpl.coupon_removed,
            cpl.coupon_removed 
        FROM v_cart_product_link cpl 
        JOIN v_coupon c ON c.product_ctime = cpl.product_ctime AND c.product_crand = cpl.product_crand 
        WHERE c.ctime = ? AND c.crand = ? LIMIT $limit;";

        $params = [$coupon->ctime, $coupon->crand];

        $result = Database::executeSqlQuery($sql, $params);

        if(!$result) throw new Exception("result returned false returning products to apply coupon");

        if($result->num_rows !== $coupon->requiredQuantityOfProduct) throw new Exception("Select query returned incorrect number of rows. Expected : $limit | Actual : $result->num_rows");

        $productLinks = [];

        while($row = $result->fetch_assoc())
        {
            array_push($productLinks, static::rowToVCartProductLink($row));
        }

        return $productLinks;
    }

    private static function rowToVCartProductLink($row) : vCartProductLink
    {
        $link = new vCartProductLink($row["ctime"], $row["crand"]);

        $link->removed = boolval($row["removed"]);
        $link->checkedOut = boolval($row["checked_out"]);

        $product = new vProduct($row["product_ctime"], $row["product_crand"]);
            $product->name = $row["product_name"];
            $product->description = $row["product_description"];
            $product->locator = $row["product_locator"];

                $store = new vStore($row["store_ctime"], $row["store_crand"]);
                $store->name = $row["store_name"];
            $product->store = $store;
        $link->product = $product;

        if(!is_null($row["coupon_ctime"]))
        {
            $coupon = new vCoupon($row["coupon_ctime"], $row["coupon_crand"]);
                $coupon->code = $row["coupon_code"];
                $coupon->description = $row["coupon_description"];
                $coupon->requiredQuantityOfProduct = $row["coupon_required_quantity_of_product"];
                $coupon->productId = new vRecordId($row["product_ctime"], $row["product_crand"]);
                $coupon->timesUsed = $row["coupon_times_used"];
                $coupon->maxTimesUsed = $row["coupon_max_times_used"];
                $coupon->maxTimesUsedPerAccount = $row["coupon_max_times_used_per_account"];

                $coupon->expiryTime = is_null($row["coupon_expiry_time"]) ? null :  DateTime::createFromFormat('Y-m-d H:i:s.u', $row["coupon_expiry_time"]);
                $coupon->removed = boolval($row["coupon_removed"]);

            $link->coupon = $coupon;
        }

        return $link;
    }

    private static function executeQueriesToLinkCartProductsToCoupon(array $cartProductToApplyCouponTo, vCoupon $coupon, array &$couponCartProductLinks) : void
    {
        $params = [];
        $valueClause = static::createValueCluaseForExecuteQueriesToLinkCartProductsToCoupon($cartProductToApplyCouponTo, $coupon, $params, $couponCartProductLinks);
        $sql = "INSERT INTO coupon_cart_product_link (
            ctime,
            crand,
            ref_coupon_ctime,
            ref_coupon_crand,
            ref_cart_product_link_ctime,
            ref_cart_product_link_crand,
            coupon_assignment_group_ctime,
            coupon_assignment_group_crand,
            removed,
            checked_out
            ) VALUES $valueClause";

        $result = Database::executeSqlQuery($sql, $params);

        if(!$result) throw new exception("result returend false when inserting cartProductCouponLinks");
    }

    private static function createValueCluaseForExecuteQueriesToLinkCartProductsToCoupon(array $cartProductsToApplyCouponTo, vCoupon $coupon, array &$params, array &$couponCartProductLinks) : string
    {
        $valueClause = "";

        $groupId = new RecordId();

        for($i = 0; $i < $coupon->requiredQuantityOfProduct; $i++)
        {
            $link = new CartProductCouponLink();

            $cartProduct = $cartProductsToApplyCouponTo[$i];

            $linkView = new vCartProductCouponLink($link->ctime, $link->crand);
            $linkView->couponId = new vRecordId($coupon->ctime, $coupon->crand);
            $linkView->cartProductId = new vRecordId($cartProduct->ctime, $cartProduct->crand);
            array_push($couponCartProductLinks, $linkView);

            if($i !== 0)
            {
                $valueClause .= ", ";
            }

            $valueClause .= "(?, ?, ?, ?, ?, ?, ?, ?, 0, 0)";
            array_push($params,
                $link->ctime,
                $link->crand,
                $coupon->ctime,
                $coupon->crand,
                $cartProduct->ctime,
                $cartProduct->crand,
                $groupId->ctime,
                $groupId->crand,
            );
        }

        return $valueClause;
    }

    private static function executeQueriesToRemoveCurrentPricesFromCartProducts(array $couponedProducts) : void
    {
        $params = [];
        $selectTable = static::createSelectTableForExecuteQueriesToRemoveCurrentPricesFromCartProducts($couponedProducts, $params);
        $sql = "UPDATE cart_product_price_link cpl JOIN ($selectTable) st ON st.ctime = cpl.ctime AND st.crand = cpl.crand SET cpl.removed = 1;";

        $result = Database::executeSqlQuery($sql, $params);

        if(!$result) throw new Exception("result returned false while executing queries to remove current prices from cart products");
    }

    private static function createSelectTableForExecuteQueriesToRemoveCurrentPricesFromCartProducts(array $couponedProducts, array &$params) : string
    {
        $selectTable = "";

        for($i = 0; $i < count($couponedProducts); $i++)
        {
            $product = $couponedProducts[$i];
            array_push($params, $product->ctime, $product->crand);

            if($i === 0)
            {
                $selectTable .= "(SELECT ctime, crand FROM cart_product_price_link WHERE ref_cart_product_link_ctime = ? AND ref_cart_product_link_crand = ?)";
                continue;
            }

            $selectTable .= " UNION ALL (SELECT ctime, crand FROM cart_product_price_link WHERE ref_cart_product_link_ctime = ? AND ref_cart_product_link_crand = ?)";
        }

        return $selectTable;
    }

    public static function getAccountCouponUse(vAccount $account, vCoupon $coupon) : Response
    {
        $resp = new Response(false, "unkown error in getting account coupon use");

        try
        {
            $sql = "SELECT
            ctime,
            crand, 
            coupon_ctime,
            coupon_crand,
            code,
            `description`,
            required_quantity_of_product,
            product_ctime,
            product_crand,
            max_times_used_per_account,
            remaining_uses,
            account_times_used,
            expiry_time,
            removed,
            account_ctime,
            account_crand
            FROM v_coupon_account_use
            WHERE account_crand = ? 
            AND coupon_ctime = ? AND coupon_crand = ?
            LIMIT 1;";

            $params = [$account->crand, $coupon->ctime, $coupon->crand];

            $result = Database::executeSqlQuery($sql, $params);

            if(!$result) throw new Exception("result returned false while getting account coupon use");

            if($result->num_rows === 0)
            {
                $resp->success = true;
                $resp->message = "Account has not used this coupon";
                $resp->data = false;
            }
            else
            {
                $couponAccountUse = static::rowToVCouponAccountUse($result->fetch_assoc());

                $resp->success = true;
                $resp->message = "Returned Account Coupon Use";
                $resp->data = $couponAccountUse;
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while account coupon use : $e");
        }

        return $resp;
    }

    private static function rowToVCouponAccountUse(array $row) : vCouponAccountUse
    {
        $use = new vCouponAccountUse($row["ctime"], $row["crand"]);

        $use->coupon =  static::rowToVCoupon($row);

        $use->accountId = new vRecordId($row["account_ctime"], $row["account_crand"]);

        $use->remainingUses = $row["remaining_uses"];
        $use->accountTimesUsed = $row["account_times_used"];

        return $use;
    }

    private static function doesCartMeetCouponProductRequirements(vCart $cart, vCoupon $coupon) : bool
    {
        $productId = $coupon->productId;

        $numOfProductInCart = 0;

        foreach($cart->cartProducts as $cartProduct)
        {
            $product = $cartProduct->product;
            if($product->ctime !== $productId->ctime || $product->crand !== $productId->crand) continue;

            $numOfProductInCart++;
        }

        return ($numOfProductInCart >= $coupon->requiredQuantityOfProduct);
    }

    
    public static function addCoupon(Coupon $coupon) : Response
    {
        $resp = new Response(false, "unkown error in adding coupon");

        try
        {
            $sql = "INSERT INTO coupon (
                ctime,
                crand,
                code,
                `description`,
                required_quantity_of_product,
                ref_product_ctime,
                ref_product_crand,
                times_used,
                max_times_used,
                max_times_used_per_account,
                expiry_time,
                removed) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
            ";

            $expiryTime = is_null($coupon->expiryTime) ? null : $coupon->expiryTime->format('Y-m-d H:i:s.u');
            
            $params = [$coupon->ctime,
                $coupon->crand,
                $coupon->code,
                $coupon->description,
                $coupon->requiredQuantityOfProduct,
                $coupon->productId->ctime,
                $coupon->productId->crand,
                $coupon->timesUsed,
                $coupon->maxTimesUsed,
                $coupon->maxTimesUsedPerAccount,
                $expiryTime,
                $coupon->removed
            ];

            $conn = Database::getConnection();
            $conn->begin_transaction();

            Database::executeSqlQuery($sql, $params);

            if(count($coupon->price) > 0)static::linkPriceToCouponInsertionTransaction($coupon);

            $success = $conn->commit();

            if(!$success) throw new Exception("transaction returned false while adding coupon");

            $resp->success = true;
            $resp->message = "Coupon Added";
        }
        catch(Exception $e)
        {
            $conn->rollback();
            throw new Exception("Exception caught while adding coupon : $e");
        }

        return $resp;
    }

    public static function linkPricesToCouponInsertionTransaction(Coupon $coupon) : void
    {
        $params = [];
        $valueClause = static::createValueClauseForLinkPriceToCouponInsertionTransaction($coupon, $params);
        $sql = "INSERT INTO coupon_price_link (ctime, crand, ref_coupon_ctime, ref_coupon_crand, ref_price_ctime, ref_price_crand) VALUES $valueClause";

        $result = Database::executeSqlQuery($sql, $params);

        if(!$result) throw new exception("result returned false while inserting coupon price links");
    }

    public static function createValueClauseForLinkPriceToCouponInsertionTransaction(Coupon $coupon, array &$params) : string
    {
        $price = $coupon->price;

        if(count($price) <= 0) throw new InvalidArgumentException("counpon prices must have at least one element");

        $valueClause = "";

        for($i = 0; $i < count($price); $i++)
        {
            $priceComponent = $price[$i];
            $link = new CouponPriceLink();

            array_push($params, $link->ctime, $link->crand, $coupon->ctime, $coupon->crand, $priceComponent->ctime, $priceComponent->crand);

            if($i===0)
            {
                $valueClause .= "(?,?,?,?,?,?)";
                continue;
            }

            $valueClause .= ", (?,?,?,?,?,?)";
        }

        return $valueClause;
    }

    public static function getCouponByCode(string $code) : Response
    {
        $resp = new Response(false, "unkown error in getting coupon by code");

        try
        {
            $sql = "SELECT 
            ctime,
            crand,
            code,
            `description`,
            required_quantity_of_product,
            product_ctime,
            product_crand,
            times_used,
            max_times_used,
            max_times_used_per_account,
            expiry_time,
            removed
            FROM v_coupon
            WHERE code = ? AND removed = 0 AND (expiry_time IS NULL OR expiry_time > NOW()) 
            LIMIT 1;";

            $params = [$code];

            $result = Database::executeSqlQuery($sql, $params);

            if(!$result) throw new Exception("result returned false while getting coupon by code");

            if($result->num_rows < 1)
            {
                $resp->message = "Coupon not found";
                return $resp;
            }

            $coupon = static::rowToVCoupon($result->fetch_assoc());

            $coupon->price = static::getCouponPrice($coupon);

            $resp->success = true;
            $resp->message = "Coupon returned";
            $resp->data = $coupon;
        }
        catch(Exception $e)
        {   
            throw new Exception("Exception caught while getting coupon by code : $e");
        }

        return $resp;
    }

    private static function getCouponPrices(vCoupon $coupon) : array
    {
        $sql = "SELECT
            vp.ctime, 
            vp.crand, 
            vp.amount, 
            vp.currency_code, 
            vp.item_ctime, 
            vp.item_crand, 
            vp.item_name, 
            vp.item_desc,  
            vp.media_path_small, 
            vp.media_path_large, 
            vp.media_path_back,
            vp.item_is_fungible
        FROM v_price vp
        JOIN coupon_price_link cpl ON cpl.ref_price_ctime = vp.ctime AND cpl.ref_price_crand 
        WHERE cpl.ref_coupon_ctime = ? AND cpl.ref_coupon_crand = ?";

        $result = Database::executeSqlQuery($sql, [$coupon->ctime, $coupon->crand]);

        if(!$result) throw new Exception("result returned false while getting coupon prices");

        $prices = [];
        
        while($row = $result->fetch_assoc())
        {
            array_push($prices, static::rowTovPriceComponent($row));
        }

        return $prices;
    }

    private static function rowToVCoupon(array $row) : vCoupon
    {
        $coupon = new vCoupon($row["ctime"], $row["crand"]);

        $coupon->code = $row["code"];
        $coupon->description = $row["description"];
        $coupon->requiredQuantityOfProduct = $row["required_quantity_of_product"];
        $coupon->productId = new vRecordId($row["product_ctime"], $row["product_crand"]);
        $coupon->timesUsed = $row["times_used"];
        $coupon->maxTimesUsed = $row["max_times_used"];
        $coupon->maxTimesUsedPerAccount = $row["max_times_used_per_account"];
        $coupon->expiryTime = is_null($row["expiry_time"]) ? null : DateTime::createFromFormat('Y-m-d H:i:s.u', $row["expiry_time"]);
        $coupon->removed = boolval($row["removed"]);

        return $coupon;
    }
}