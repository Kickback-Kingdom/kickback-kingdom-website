<?php

declare(strict_types = 1);

namespace Kickback\Backend\Controllers;

use BadFunctionCallException;
use DateTime;
use \Kickback\Backend\Models\Response;
use \Kickback\Backend\Models\Store;
use Kickback\Backend\Models\Product;

use \Exception;
use InvalidArgumentException;
use Kickback\Backend\Models\Cart;
use Kickback\Backend\Models\CartProductLink;
use Kickback\Backend\Models\Enums\CurrencyCode;
use Kickback\Backend\Models\ItemCategory;
use Kickback\Backend\Models\Price;
use Kickback\Backend\Models\RecordId;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vCart;
use Kickback\Backend\Views\vCartItem;
use Kickback\Backend\Views\vCartProductLink;
use Kickback\Backend\Views\vDecimal;
use Kickback\Backend\Views\vItem;
use Kickback\Backend\Views\vLoot;
use Kickback\Backend\Views\vMedia;
use Kickback\Backend\Views\vPrice;
use Kickback\Backend\Views\vProduct;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vStore;
use Kickback\Backend\Views\vTransaction;
use Kickback\Common\Unittesting\AssertException;
use Kickback\Services\Database;
use RuntimeException;

class StoreController
{
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
    }

    //CART

    //check if cart has any products to check out
    //check if account has the items needed to by it
    //attempt stripe transaction
    //transact items
    //log transaction
    //update product stock
    public static function checkoutCart(vCart $cart, ?callable $moneyMerchantIsTransactionCompleteMethod = null) : Response 
    {
        $resp = new Response(false, "Unkown error checking out cart", null);

        try
        {

            //Does cart have items to transact
            if(count($cart->cartItems) < 0)
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
                
            //Process lovelace transactions
            $lovelacePriceOfCart = static::returnCartLovelacePrice($cart);

            $stripeResp = new Response(false, "", null);
            if($lovelacePriceOfCart > 0)
            {
                $stripeResp = new Response(true, "Succeeded with the stripe transaction", null);
                //$stripeResp = StripeController::IsTransactionComplete($stripeTransactionId);
            }
            else
            {
                //this is an item only transaction. Stripe isn't needed
                $stripeResp->success = true;
            }

            if(!$stripeResp->success)
            {
                $resp->message = "Failed to complete stripe transaction : $stripeResp->message";
                return $resp;
            }

            $payItemsForCartResp = static::transactItemsForCart($cart);

            if(!$payItemsForCartResp->success)
            {
                $stripeTransactionId = $stripeTransactionId ?? "null";
                $resp->message = "Failed to complete paying for items in cart. StripeTransactionId = $stripeTransactionId";
                return $resp;
            }

                
        }
        catch(Exception $e)
        {
            throw new Exception();
        }

        return $resp;
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
        }
        catch(Exception $e)
        {  
            $conn->rollback();
            mysqli_autocommit($conn, true);
            throw new Exception("Excpetion caught while paying for item prices for cart : $e");
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

        $whereArrayClause = static::getWhereArrayClauseForGetStoreOwnerCartItemsLoot($cart->cartItems);

        $sql = "SELECT Id, opened, account_id, item_id, quest_id, media_id_small, media_id_large, media_id_back, loot_type, desc, rarity, dateObtained, container_loot_id FROM v_loot 
        WHERE account_id = ? AND item_id IN ($whereArrayClause);";

        $params = static::getParamsForGetStoreOwnerCartItemsLoot($owner, $cart->cartItems);

        $loots = [];

        try
        {
            $result = Database::executeSqlQuery($sql, $params);

            if(!$result) throw new Exception("Failed to get matching loot items from store owner's inventory that matched the to be purchased cart items");

            while($row = $result->fetch_assoc())
            {
                $loot = LootController::row_to_vLoot($row);
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
        $params = [$accountId];

        foreach($cartItems as $item)
        {
            array_push($params, $item->product->crand);
        }

        return $params;
    }

    private static function unittest_getParamsForGetStoreOwnerCartItemsLoot() : void
    {
        $mockCartItem = new vCartItem();
        $mockCartItem->product = new vProduct();
        $mockCartItem->product->item = new vItem();
        $expectedCrand = $mockCartItem->product->crand;
        assert([new vRecordId(), $expectedCrand] === static::getParamsForGetStoreOwnerCartItemsLoot(new vRecordId(), [$mockCartItem]), "UNIT TEST FAILED : returned param array did not match expected");
        assert([new vRecordId(), $expectedCrand, $expectedCrand] === static::getParamsForGetStoreOwnerCartItemsLoot(new vRecordId(), [$mockCartItem, $mockCartItem]), "UNIT TEST FAILED : returned param array did not match expected");
        assert([new vRecordId(), $expectedCrand, $expectedCrand, $expectedCrand] === static::getParamsForGetStoreOwnerCartItemsLoot(new vRecordId(), [$mockCartItem, $mockCartItem, $mockCartItem]), "UNIT TEST FAILED : returned param array did not match expected");
        assert([new vRecordId(), $expectedCrand, $expectedCrand, $expectedCrand, $expectedCrand] === static::getParamsForGetStoreOwnerCartItemsLoot(new vRecordId(), [$mockCartItem, $mockCartItem, $mockCartItem, $mockCartItem]), "UNIT TEST FAILED : returned param array did not match expected");
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
        foreach($cart->cartItems as $item)
        {
            $foundKey = null;

            // Find the first matching loot item
            foreach ($allLootsForCartItemsFromStoreOwner as $key => $item) {
                if ($item->crand === $item->crand) {
                    $foundKey = $key;
                    break;
                }
            }

            if (is_null($foundKey)) throw new RuntimeException("No loot item found for crand {$item->crand}");

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
                foreach ($allLootsForPricesInCart as $key => $item) {
                    if ($item->crand === $total->item->crand) {
                        $foundKey = $key;
                        break;
                    }
                }

                if (is_null($foundKey)) throw new RuntimeException("No loot item found for crand {$total->item->crand}");

                $lootItem = $allLootsForPricesInCart[$foundKey];

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

        $sql = "SELECT Id, opened, account_id, item_id, quest_id, media_id_small, media_id_large, media_id_back, loot_type, desc, rarity, dateObtained, container_loot_id FROM v_loot 
        WHERE account_id = ? AND item_id IN ($whereArrayClause);";

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
        $params = [$accountId];

        foreach($totals as $total)
        {
            if(is_null($total->item)) continue;
            array_push($params, $total->item->crand);
        }

        return $params;
    }

    private static function unittest_getParamsForGetLootForPricesForCart() : void
    {
        $mockTotal = new vPrice();
        $mockTotal->item = new vRecordId('x',1);
        $mockNullTotal = new vPrice();
        assert([new vRecordId(), $mockTotal] === static::getParamsForGetLootForPricesForCart(new vRecordId(), [$mockTotal]), "UNIT TEST FAILED : returned param array did not match expected");
        assert([new vRecordId(), $mockTotal, $mockTotal] === static::getParamsForGetLootForPricesForCart(new vRecordId(), [$mockTotal, $mockTotal]), "UNIT TEST FAILED : returned param array did not match expected");
        assert([new vRecordId(), $mockTotal, $mockTotal, $mockTotal] === static::getParamsForGetLootForPricesForCart(new vRecordId(), [$mockTotal, $mockTotal, $mockTotal]), "UNIT TEST FAILED : returned param array did not match expected");
        assert([new vRecordId()] === static::getParamsForGetLootForPricesForCart(new vRecordId(), [$mockNullTotal]), "UNIT TEST FAILED : returned param array did not match expected");
        assert([new vRecordId()] === static::getParamsForGetLootForPricesForCart(new vRecordId(), [$mockNullTotal, $mockNullTotal]), "UNIT TEST FAILED : returned param array did not match expected");
        assert([new vRecordId(), $mockTotal] === static::getParamsForGetLootForPricesForCart(new vRecordId(), [$mockNullTotal, $mockTotal]), "UNIT TEST FAILED : returned param array did not match expected");
        assert([new vRecordId(), $mockTotal] === static::getParamsForGetLootForPricesForCart(new vRecordId(), [$mockNullTotal, $mockNullTotal, $mockTotal]), "UNIT TEST FAILED : returned param array did not match expected");
        assert([new vRecordId(), $mockTotal, $mockTotal] === static::getParamsForGetLootForPricesForCart(new vRecordId(), [$mockTotal, $mockNullTotal, $mockNullTotal, $mockTotal]), "UNIT TEST FAILED : returned param array did not match expected");
        assert([new vRecordId(), $mockTotal, $mockTotal, $mockTotal] === static::getParamsForGetLootForPricesForCart(new vRecordId(), [$mockTotal, $mockNullTotal, $mockTotal, $mockNullTotal, $mockTotal]), "UNIT TEST FAILED : returned param array did not match expected");
    }

    private static function reassignLoot(vRecordId $lootId, vRecordId $toAccount) : void
    {
        $sql = "UPDATE loot SET account_id = ? AND dateObtained = GETDATE() WHERE Id = ?;";
        $params = [$toAccount, $lootId->crand];

        $result = Database::executeSqlQuery($sql, $params);

        if(!$result) throw new exception("Failed to update loot to reassign");
    }

    private static function logTrade(vLoot $loot, vRecordId $toAccount) : void
    {
        $sql = "INSERT INTO trade (from_account_id, to_account_id, loot_id, trade_date, from_account_obtain_date)
        VALUES (?,?,?,?,?);";

        $params = [$loot->crand, $loot->ownerId->crand, $loot->crand, RecordId::getCTime(), $loot->dateObtained->value->format('Y-m-d H:i:s.u')];

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

    private static function canAccountAffordItemPricesInCart(vCart $cart) : Response
    {
        $resp = new Response(false, "Unkown error in checking if account can afford item prices in cart", null);

        try
        {
            $lootForCart = static::returnLootAmountOfPricesInCart($cart);

            $canAccountAffordItems = true;

            foreach($cart->totals as $total)
            {
                $row = array_filter($lootForCart, function($obj) use ($total) 
                {
                    return $obj["item_id"] == $total->ctime;
                });

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
                $resp->message = "Account cannot afford Item Prices";
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
        $whereClause = static::returnWhereClauseForCanAccountAffordItemPricesInCart($cart->cartItems);
        $params = static::returnParamsForCanAccountAffordItemPricesForCart($cart);

        $sql = "SELECT item_id, COUNT(Id) AS `amount` FROM loot WHERE account_id = ? AND opened = 1 AND redeemed = 1$whereClause GROUP BY item_id;";

        $result = Database::executeSqlQuery($sql, $params);

        if(!$result) throw new Exception("Sql error; result returned false");

        $lootForCart = $result->num_rows > 0 ? $result->fetch_all(MYSQLI_ASSOC) : [];

        return $lootForCart;
    }

    private static function returnParamsForCanAccountAffordItemPricesForCart(vCart $cart) : array
    {
        $params = [$cart->account->crand];

        foreach($cart->cartItems as $item)
        {
            array_push($params, $item->crand);
        }

        return $params;
    }

    private static function unittest_returnParamsForCanAccountAffordItemPricesForCart() : void
    {
        $cart = new vCart();
        $cart->account = new vAccount();
        
        $cart->cartItems = [new vRecordId('x', 1)];
        assert(static::returnParamsForCanAccountAffordItemPricesForCart($cart) === ['',-1,'x',1], new Exception("UNIT TEST FAILED : returned params did not match expected for returnParamsForCanAccountAffordItemPricesForCart"));
        $cart->cartItems = [new vRecordId('x', 1), new vRecordId('x', 1)];
        assert(static::returnParamsForCanAccountAffordItemPricesForCart($cart) === ['',-1,'x',1,'x',1], new Exception("UNIT TEST FAILED : returned params did not match expected for returnParamsForCanAccountAffordItemPricesForCart"));
        $cart->cartItems = [new vRecordId('x', 1), new vRecordId('x', 1), new vRecordId('x', 1)];        
        assert(static::returnParamsForCanAccountAffordItemPricesForCart($cart) === ['',-1,'x',1,'x',1,'x',1], new Exception("UNIT TEST FAILED : returned params did not match expected for returnParamsForCanAccountAffordItemPricesForCart"));
        $cart->cartItems = [new vRecordId('x', 1), new vRecordId('x', 1), new vRecordId('x', 1), new vRecordId('x', 1)];
        assert(static::returnParamsForCanAccountAffordItemPricesForCart($cart) === ['',-1,'x',1,'x',1,'x',1,'x',1], new Exception("UNIT TEST FAILED : returned params did not match expected for returnParamsForCanAccountAffordItemPricesForCart"));   
    }

    private static function returnWhereClauseForCanAccountAffordItemPricesInCart(array $cartItems) : string
    {
        $whereClause = " AND (item_id = ?";

        for($i = 1; $i < count($cartItems); $i++)
        {
            $whereClause .= " OR item_id = ?";
        }

        $whereClause .= ")";

        return $whereClause;
    }

    private static function unittest_returnWhereClauseForCanAccountAffordItemPricesInCart() : void
    {
        assert(static::returnWhereClauseForCanAccountAffordItemPricesInCart([null]) === " AND (item_id = ?)", new Exception("UNIT TEST FAILED : returned where clause for can account afford item prices in cart did not match expected"));
        assert(static::returnWhereClauseForCanAccountAffordItemPricesInCart([null, null]) === " AND (item_id = ? OR item_id = ?)",  new Exception("UNIT TEST FAILED : returned where clause for can account afford item prices in cart did not match expected"));
        assert(static::returnWhereClauseForCanAccountAffordItemPricesInCart([null, null, null]) === " AND (item_id = ? OR item_id = ?) OR item_id = ?)",  new Exception("UNIT TEST FAILED : returned where clause for can account afford item prices in cart did not match expected"));
        assert(static::returnWhereClauseForCanAccountAffordItemPricesInCart([null, null. null, null]) === " AND (item_id = ? OR item_id = ?) OR item_id = ?) OR item_id = ?)",  new Exception("UNIT TEST FAILED : returned where clause for can account afford item prices in cart did not match expected"));
    }

    /**
     * Calculates the totals for the prices in the cart
     * @param array $cartItems all of the items in the cart, an array of vCartItems
     * @return array $totalPrices the returned array which contains the prices of the totals in vPrice objects
     */
    public static function calculateCartTotalPrices(array $cartItems) : array
    {
        $totalPrices = [];

        foreach($cartItems as $item)
        {
            $prices = $item->product->prices;

            foreach($prices as $price)
            {
                $priceAlreadyExists = null;

                foreach($totalPrices as $total)
                {

                    //Does price being checked match an already existing total
                    if(
                        (!is_null($price->item) && !is_null($total->item) 
                        && $price->item->ctime == $total->item->ctime && $price->item->crand == $total->item->crand)
                        ||
                        (!is_null($price->currencyCode) && !is_null($total->currencyCode) &&
                        $price->currencyCode == $total->currencyCode)
                    )
                    {
                        $priceAlreadyExists == $total;
                        break;
                    }
                }

                //Add amount of already existing total or create new total
                if(is_null($priceAlreadyExists))
                {
                    array_push($totalPrices, $price);
                }
                else
                {
                    $priceAlreadyExists->amount = $priceAlreadyExists->amount + $price->amount;
                }
            }
        }

        return $totalPrices;
    }

    /**
     * Adds a product to a cart only if there are enough stock left of the product in the store
     * 
     * Returns a success with false data if there was not enough stock present
     * Returns a success with true data if the product was added to cart
    */
    public static function addProductToCart(vRecordId $product, vCart $cart) : Response
    {
        $resp = new Response(false, "unkown error in adding product to cart", null);

        try
        {
            $selectProductResp = static::getProductById($product);

            if($selectProductResp->message)
            {
                $product = $selectProductResp->data;

                $effectiveStock = $product->stock - static::getNumberOfProductInCartById($product, $cart);

                if($effectiveStock > 0)
                {
                    $linkResp = static::linkProductToCart($product, $cart);

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
                else
                {
                    $resp->success = true;
                    $resp->message = "Not enough available stock to add product to cart";
                }
            }
            else
            {
                $resp->message = "error in getting product to add it to cart : $selectProductResp->message";
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while adding product to cart : $e");
        }

        return $resp;
    }

    public static function removeProductFromCart(vRecordId $productCartLink) : Response
    {
        $resp = new Response(false, "unkown error in removing product from cart", null);

        try
        {
            $sql = "UPDATE cart_product_link SET removed = 1 WHERE ctime = ? AND crand = ?;";

            $result = Database::executeSqlQuery($sql, [$productCartLink->ctime, $productCartLink->crand]);

            if($result)
            {
                $resp->success = true;
                $resp->message = "Product removed from cart";
            }
            else
            {
                $resp->message = "Sql error in removing product from cart";
            }
        }
        catch(Exception $e)
        {
            throw new Exception("exception caguth while removing product from cart : $e");
        }

        return $resp;
    }

    private static function getNumberOfProductInCartById(vRecordId $product, vCart $cart) : int
    {
        $quantity = 0;

        foreach($cart->cartItems as $item)
        {
            if($item->product->ctime == $product->ctime && $item->product->crand == $product->crand) 
                $quantity++;
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

        $cart->cartItems = [];
        $actual = static::getNumberOfProductInCartById($product, $cart);
        $expected = 0;
        assert($actual === $expected, new Exception("UNIT TEST FAILED | actual : $actual | expected : $expected"));

        $cart->cartItems = [$otherCartItem];
        $actual = static::getNumberOfProductInCartById($product, $cart);
        $expected = 0;
        assert($actual === $expected, new Exception("UNIT TEST FAILED | actual : $actual | expected : $expected"));

        $cart->cartItems = [$otherCartItem,$otherCartItem,$otherCartItem];
        $actual = static::getNumberOfProductInCartById($product, $cart);
        $expected = 0;
        assert($actual === $expected, new Exception("UNIT TEST FAILED | actual : $actual | expected : $expected"));

        $cart->cartItems = [$cartItem];
        $actual = static::getNumberOfProductInCartById($product, $cart);
        $expected = 1;
        assert($actual === $expected, new Exception("UNIT TEST FAILED | actual : $actual | expected : $expected"));

        $cart->cartItems = [$cartItem, $otherCartItem, $cartItem];
        $actual = static::getNumberOfProductInCartById($product, $cart);
        $expected = 2;
        assert($actual === $expected, new Exception("UNIT TEST FAILED | actual : $actual | expected : $expected"));

        $cart->cartItems = [$cartItem, $cartItem];
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
            $item = new vItem();
            $item->name = $row["product_name"];
            $item->description = $row["product_description"];
                $smallMedia = new vMedia();
                $smallMedia->setMediaPath($row["small_media_media_path"]);
                $largeMedia = new vMedia();
                $largeMedia->setMediaPath($row["large_media_media_path"]);
                $backMedia = new vMedia();
                $backMedia->setMediaPath($row["back_media_media_path"]);
            $item->iconSmall = $smallMedia;
            $item->iconBig = $largeMedia;
            $item->iconBack = $backMedia;
        $product->item = $item;
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
                ctime,
                crand,
                ref_account_ctime, 
                ref_account_crand,
                ref_store_ctime, 
                ref_store_crand
                )VALUES(
                ?,?,?,?,?,?
                )ON DUPLICATE KEY UPDATE ref_account_ctime = VALUES(ref_account_ctime);";

        $params = [$cart->ctime, $cart->crand, $accountId->ctime, $accountId->crand, $storeId->ctime, $storeId->crand];

        try
        {
            $result = Database::executeSqlQuery($sql, $params);

            if(!$result)
            {
                $resp->message = "Failed to run duplicate-tolerant insert command for inserting cart";
                return $resp;
            }

            $selectSql = "SELECT
                ctime,
                crand,
                account_username,
                store_name, 
                store_locator,
                checked_out,
                void,
                account_ctime,
                account_crand,
                store_ctime,
                store_crand,
                transaction_ctime,
                transaction_crand
                FROM v_cart
                WHERE ctime = ? AND crand = ?;
            ";

            $params = [$cart->ctime, $cart->crand];

            $selectResult = Database::executeSqlQuery($selectSql, $params);

            if(!$selectResult)
            {
                $resp->message = "Failed to select cart for account";
                return $resp;
            } 

            if($selectResult->num_rows > 0)
            {
                $resp->message = "cart not found after insertion";
                return $resp;
            }

            $row = $selectResult->fetch_assoc();

            $cartView = static::cartToView($row);

            $cartItemsResp = static::getItemsInCart($cartView);

            if(!$cartItemsResp->success)
            {
                $resp->message = "Failed to get cart items";
                return $resp;
            }

            $cartView->cartItems = $cartItemsResp->data;
            $cartView->totals = static::calculateCartTotalPrices($cartView->cartItems);

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

    /**
     * gets all the matching rows from v_cart_item.
     * It then conglomerates rows of the same cart product link into vCartItem objects 
     * since the cart item view lists the cart items as units of the prices of the products in the cart
     */
    private static function getItemsInCart(vRecordId $cart) : Response
    {
        $resp = new Response(false, "unkown error in getting item for cart", null);

        try
        {
            $sql = "SELECT 
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
            price_item_crand
            FROM v_cart_item WHERE removed = 0 AND checked_out = 0 AND cart_ctime = ? AND cart_crand = ?;";

            $params = [$cart->ctime, $cart->crand];

            $result = Database::executeSqlQuery($sql, $params);

            if($result)
            {
                $cartItems = []; 

                if($result->num_rows > 0)
                {
                    while($row = $result->fetch_row())
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

                        if(!is_null($cartItemAlreadyProccessed))
                        {
                            $price = static::cartItemToPriceView($row);

                            array_push($cartItemAlreadyProccessed->prices, $price);
                        }
                        else
                        {
                            array_push($cartItems, static::cartItemToView($row));
                        }
                    }
                }
                else
                {
                    $resp->success = true;
                    $resp->message = "no items in cart";
                    $resp->data = $cartItems;
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

    private static function cartItemToView(array $row) : vCartItem
    {
        $cartItem = new vCartItem();

        $price = static::cartItemToPriceView($row);

        $product = new vProduct();
        $product->prices = [$price];
        $product->locator = $row["product_locator"];
            $item = new vItem();
            $item->name = $row["product_name"];
            $item->description = $row["product_description"];
                $smallMedia = new vMedia();
                $smallMedia->setMediaPath($row["product_small_media_path"]);
                $largeMedia = new vMedia();
                $largeMedia->setMediaPath($row["product_large_media_path"]);
                $backMedia = new vMedia();
                $backMedia->setMediaPath($row["product_back_media_path"]);
            $item->iconSmall = $smallMedia;
            $item->iconBig = $largeMedia;
            $item->iconBack = $backMedia;
        $product->item = $item;

        $cart = new vCart();
        $cart->ctime = $row["cart_ctime"];
        $cart->crand = $row["cart_crand"];
        $cartItem->cart = $cart;
        
        $cartItem->ctime = $row["cart_product_link_ctime"];
        $cartItem->crand = $row["cart_product_link_crand"];
        $cartItem->removed = $row["removed"];
        $cartItem->checkedOut = $row["checked_out"];

        return $cartItem;
    }
    
    private static function cartItemToPriceView(array $row) : vPrice
    {
        $price = new vPrice();

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
                $backMedia->setMediaPath($row["price_media_path_back"]);
            $item->iconSmall = $smallMedia;
            $item->iconBig = $largeMedia;
            $item->iconBack = $backMedia;

            $price->item = $item;
        }
        
        if(!is_null($row["price_currency_code"]))
        {
            $price = CurrencyCode::from($row["price_currency_code"]);
        }

        $price->ctime = $row["price_ctime"];
        $price->crand = $row["price_crand"];
        $price->amount = $row["price_amount"];

        return $price;
        
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

        $cart->checkedOut = boolval($row["checked_out"]);
        $cart->void = boolval($row["void"]);

        $cart->ctime = $row["ctime"];
        $cart->crand = $row["crand"];

        return $cart;
    }

    //PRODUCT
    
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

    /*
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
    }*/

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

    /*
    public static function removeProductById(vRecordId $productId) : Response
    {
        $resp = new Response(false, "unkown error in removing product", null);

        try
        {
            $linkDeleteResp = static::removePricesLinksToProduct($productId);

            if($linkDeleteResp->success)
            {
                $sql = "DELETE FROM product WHERE ctime = ? AND crand = ?;";

                $params = [$productId->ctime, $productId->crand];

                Database::executeSqlQuery($sql, $params);

                $resp->success = true;
                $resp->message = "product deleted";
            }
            else
            {
                $resp->message = "Failed to removed price links to product before deleting product : $linkDeleteResp->message";
            }
        }
        catch(Exception $e)
        {
            throw new Exception("exception caught while removing product : $e");
        }

        return $resp;
    }*/

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

    /*private static function removePricesLinksToProduct(vRecordId $product) : response
    {
        $resp = new Response(false, "unkown error in removing price links to product", null);

        try
        {
            $sql = "DELETE FROM product_price_link WHERE ref_product_ctime = ? AND ref_product_crand = ?;";

            $params = [$product->ctime, $product->crand];

            $result = database::executeSqlQuery($sql, $params);

            if(!$result)
            {
                throw new Exception("Failed to delete price links to product");
            }

            $resp->success = true;
            $resp->message = "deleted all price links to product";
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while removing price links to product : $e");
        }

        return $resp;
    }*/


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
                        $linkPricesResp = static::linkProductToPrices($product, $product->prices);

                        if($linkPricesResp->success)
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
                    {
                        $resp->message = "Error in updating product after it was found to exist : $updateResp->message";
                    }
                }
                else
                {
                    $insertResp = static::insertProduct($product);

                    if($insertResp->success)
                    {
                        $linkPricesResp = static::linkProductToPrices($product, $product->prices);

                        if($linkPricesResp->success)
                        {
                            $resp->success = true;
                            $resp->message = "Product was inserted as it did not exist"; 
                        }
                        else
                        {
                            $resp->message = "product was inserted however there was an error with linking the prices";
                        }
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
            stock,
            store_name,
            store_locator,
            store_description,
            store_owner_username,
            store_owner_ctime,
            store_owner_crand,
            store_ctime,
            store_crand,
            item_ctime,
            item_crand,
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

            if($result->num_rows > 0)
            {
                $product = static::productToView($result->fetch_assoc());

                $pricesResp = static::getPricesForProduct($product);

                if($pricesResp->success)
                {
                    $product->prices = $pricesResp->data;

                    $resp->success = true;
                    $resp->message = "Product found and returned";
                    $resp->data = $product;
                }
                else
                {
                    $resp->message = "failed to get prices for product by id : $pricesResp->message";
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

    private static function getPricesForProduct(vRecordId $product) : Response
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
            vp.media_path_back
            FROM v_price vp JOIN product_price_link ppl ON ppl.ref_price_ctime = vp.ctime AND ppl.ref_price_crand = vp.crand
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
        locator = ?, 
        stock = ?,
        ref_store_ctime = ?, 
        ref_store_crand = ?,
        ref_item_ctime = ?, 
        ref_item_crand = ? 
        WHERE ctime = ? and crand = ? limit 1;";

        $params = [
            $product->name,
            $product->description,
            $product->locator,
            $product->stock,
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

        $sql = "INSERT INTO product (ctime, crand, `name`, `description`, locator, stock, ref_store_ctime, ref_store_crand, ref_item_ctime, ref_item_crand)values(?,?,?,?,?,?,?,?,?,?)";

        $params = [$product->ctime, $product->crand, $product->name, $product->description, $product->locator, $product->stock, $product->ref_store_ctime, $product->ref_store_crand, $product->ref_item_ctime, $product->ref_item_crand];

        try
        {
            Database::executeSqlQuery($sql, $params);

            $pricesResp = static::selectOrInsertPrices($product->prices);


            if($pricesResp->success)
            {
                $prices = static::convertPriceViewArrayToModels($pricesResp->data);
                $linkResp = static::linkProductToPrices($product, $prices);

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
     * Selects an existing price or creates a new one, returning the found/created price
     */
    public static function selectOrInsertPrices(array $prices) : Response
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
     */
    private static function linkProductToPrices(vRecordId $product, array $prices) : Response
    {
        $resp = new Response(false, "unkown error in linking products to prices", null);

        try
        {
            if(static::validatePriceArray($prices) == false) throw new Exception("Price array must contain only price objects");

            $valueClause = static::returnValueClauseForLinkingProductToPrices($prices);

            $sql = "INSERT IGNORE INTO product_price_link (ref_product_ctime, ref_product_crand, ref_price_ctime, ref_price_crand) VALUES $valueClause";

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
            array_push($bindingArray, $productId->ctime, $productId->crand, $price->ctime, $price->crand);
        }

        return $bindingArray;
    }

    private static function unittest_returnInsertionParamsForLinkingProductToPrices() : void
    {
        $productId = new RecordId();
        $price = new Price();
        $prices = [$price];

        $actual = json_encode(static::returnInsertionParamsForLinkingProductToPrices($productId, $prices));
        $expected = json_encode([$productId->ctime, $productId->crand, $price->ctime, $price->crand]);
        assert($actual === $expected, "UNIT TEST FAILED | actual : $actual | expected : $expected");

        $prices = [$price, $price];

        $actual = json_encode(static::returnInsertionParamsForLinkingProductToPrices($productId, $prices));
        $expected = json_encode([$productId->ctime, $productId->crand, $price->ctime, $price->crand, $productId->ctime, $productId->crand, $price->ctime, $price->crand]);
        assert($actual === $expected, "UNIT TEST FAILED | actual : $actual | expected : $expected");
    }

    private static function productToView($row): vProduct
    {
        $owner = new vAccount($row['store_owner_ctime'],(int)$row['store_owner_crand']);
        $owner->username = $row['store_owner_username'];

        $smallIcon = new vMedia();
        $smallIcon->setMediaPath($row['small_media_media_path']);
        $largeIcon = new vMedia();
        $largeIcon->setMediaPath($row['large_media_media_path']);
        $backIcon = new vMedia();
        $backIcon->setMediaPath($row['back_media_media_path']);

        $item = new vItem($row['item_ctime'], (int)$row['item_crand']);
        $item->name = $row['name'];
        $item->description = $row['description'];
        $item->equipable = (bool)$row['equipable'];
        $item->isContainer = (bool)$row['is_container'];
        $item->containerSize = (int)$row['container_size'];
        $item->containerItemCategory = $row['container_item_category'] != null ? ItemCategory::from($row['container_item_category']) : null;

        $store = new vStore($row['item_ctime'], (int)$row['item_crand']);
        $store->name = $row['store_name'];
        $store->description = $row['store_description'];
        $store->locator = $row['store_locator'];

        return new vProduct(
            $row['ctime'],
            (int)$row['crand'],
            $row['locator'],
            $row['stock']
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

        $whereClause = static::constructWhereClauseForGetPricesByCurrencyAndItemAmount($prices);

        $sql = "SELECT 
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

        $bindingArray = [];

        foreach($prices as $price)
        {
            [$refItemCtime, $refItemCrand] = is_null($price->itemId) ? [null, null] : [$price->itemId->ctime, $price->itemId->crand];

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

                $selectSql = "SELECT ctime, crand, amount, currency_code, item_ctime, item_crand, item_name, item_desc,  media_path_small, media_path_large, media_path_back FROM v_price WHERE $whereClause;";
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
            if(!$price instanceof Price)
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

        $item = new vItem($row["item_ctime"], $row["item_crand"]);
        $item->name = $row["item_name"];
        $item->description = $row["item_desc"];
        $item->iconSmall = $iconSmall;
        $item->iconBig = $iconLarge;
        $item->iconBack = $iconBack;

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