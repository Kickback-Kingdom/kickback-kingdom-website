<?php

declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use \Kickback\Services\Database;

use \Kickback\Backend\Models\Cart;
use \Kickback\Backend\Models\Response;
use \Kickback\Backend\Models\StoreStock;
use \Kickback\Backend\Models\StoreStockCartLink;
use \Kickback\Backend\Models\Transaction;

use \Kickback\Backend\Views\vCart;
use \Kickback\Backend\Views\vAccount;
use \Kickback\Backend\Views\vRecordId;
use \Kickback\Backend\Views\vStoreStock;
use \Kickback\Backend\Views\vStoreStockCartLink;
use \Kickback\Backend\Views\vProduct;


use Exception;
use Kickback\Backend\Models\CartTransactionGroup;
use Kickback\Backend\Models\Enums\CurrencyCode;
use Kickback\Backend\Models\Product;
use Kickback\Backend\Models\TransactionCartTransactionGroupLink;
use mysqli_result;

class CartController extends DatabaseController
{
    protected static ?CartController $instance_ = null;
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
        checked_out, 
        ref_store_ctime, 
        ref_store_crand, 
        ref_account_ctime, 
        ref_account_crand
        ";
    }

    protected function allTableColumns() : string
    {
        return "
        ctime, 
        crand, 
        checked_out, 
        ref_store_ctime, 
        ref_store_crand, 
        ref_account_ctime, 
        ref_account_crand
        ";
    }

    protected function rowToView(array $row) : object  
    {
        $store = new vRecordId($row["ref_store_ctime"], $row["ref_store_crand"]);
        $account = new vRecordId($row["ref_account_ctime"], $row["ref_account_crand"]);

        return new vCart(
            $row["ctime"], 
            $row["crand"],
            (bool)$row["checked_out"],
            $store,
            $account
        );
    }

    protected function valuesToInsert(object $cart) :  array
    {
        return [
            $cart->ctime, 
            $cart->crand,
            $cart->checkedOut,
            $cart->storeId->ctime,
            $cart->storeId->crand,
            $cart->accountId->ctime, 
            $cart->accountId->crand
        ];
    }

    protected function tableName() : string
    {
        return "cart";
    }

    public static function instance() : object
    {
        if(is_null(static::$instance_))
        {
            static::$instance_ = new static();
        }

        return static::$instance_;
    }

    public static function renderCartItemsHtml(array $items): string
    {
        ob_start();
        ?>
        <ul id="cart-list" class="list-group list-group-flush">
            <?php foreach ($items as $item) { ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <span class="fw-semibold"><?= htmlspecialchars($item->name) ?></span>
                        <span class="text-muted ms-2">
                            <?php if(is_null($item->ctime))
                            { 
                                echo "$"; 
                                echo $item->price->returnPriceIn(CurrencyCode::ADA);
                                echo " ADA";
                            }
                            else
                            {
                                echo $item->price." ";
                                echo $item->currencyItemName;
                            }
                            
                            ?>
                        </span>
                    </div>

                    <form class="m-0 remove-item-in-cart">
                        <input type="hidden" name="storeStockCartLinkCtime" value="<?= $item->ctime ?>">
                        <input type="hidden" name="storeStockCartLinkCrand" value="<?= $item->crand ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </form>
                </li>
            <?php }?>
        </ul>
        <?php
        return ob_get_clean();
    }

    public static function renderCartTotalsHtml(array $totals) : string
    {
        ob_start();

        ?>
        <ul class="list-group list-group-flush">
            <?php foreach ($totals as $total){ ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">

                <?php
                    if($total["currencyName"] == "ADA")
                    {
                    ?>
                        <div>
                            <span class="fw-semibold"><?= htmlspecialchars($total["currencyName"]) ?></span>
                        </div>
                        <span><?= $total["price"]->returnPriceWithSymbol() ?></span>

                    <?php
                    }
                    else
                    {
                    ?>
                        <div>
                            <span class="fw-semibold"><?= htmlspecialchars($total["currencyName"]) ?></span>
                        </div>
                        <span><?=$total["inventoryAmount"]?> / <?= $total["price"]?></span>

                    <?php
                    }
                
                ?>
                    
                </li>
            <?php }?>
        </ul>
        <?php

        return ob_get_clean();
    }

    /**
     * Attempts to add a store stock of a product to a cart if the store stock exists. 
     * Checks if the cart has more of the product than store stocks available
     * Returns a boolean of if the store stock of the product was added
     */
    public static function tryAddProductToCart(vRecordId $productId, vRecordId $cart) : Response
    {
        $resp = new Response(false, "unkown error in adding store stock of product to cart", null);

        try
        {
            $productExistsResp = ProductController::exists($productId);

            if($productExistsResp->success)
            {
                if($productExistsResp->data)
                {

                    $storeStockResp = StoreStockController::getNumberOfStoreStocksNotAddToCart($productId, $cart);

                    if($storeStockResp->success)
                    {
                        if($storeStockResp->data > 0)
                        {
                            $getAvailableStoreStock = StoreStockController::getStoreStockOfProductForCart($productId, $cart);

                            if($getAvailableStoreStock->success)
                            {
                                $addStoreStockResp = CartController::addStoreStock($getAvailableStoreStock->data, $cart);

                                if($addStoreStockResp->success)
                                {
                                    $resp->success = true;
                                    $resp->message = "Successfully added store stock of product to cart";
                                    $resp->data = true;
                                }
                                else
                                {
                                    $resp->message = "error in adding store stock of product to cart : $addStoreStockResp->message";
                                }
                            }
                            else
                            {
                                $resp->message = "error in getting an available store stock for cart : $getAvailableStoreStock->message";
                            }

                            
                        }
                        else
                        {
                            $resp->success = true;
                            $resp->message = "no more available store stocks for product";
                            $resp->data = false;
                        }
                    }
                    else
                    {
                        $resp->message = "error in getting store stock : $storeStockResp->message";
                    }
                }
                else
                {
                    $resp->message = "product does not exist : $productExistsResp->message";
                }
            }
            else
            {
                $resp->message = "error in checking if product exists : $productExistsResp->message";
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while trying to add store stock of product to cart : $e");
        }

        return $resp;
    }

    public static function getAllItemsInCart(vRecordId $cart) : Response
    {
        $resp = new Response(false, "unkown error in getting all items for cart", null);

        try
        {
            $resp = StoreStockCartLinkController::getAllActiveStoreStockCartLinksForCart($cart);
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while getting all items in cart : $e");
        }

        return $resp;
    }

    /**
     * Returns an array of all the items associated with a provided account grouped by store
     * [{"storeCtime"=>{store ctime}, "storeCrand"=>{store crand}, "items"=>[{array of StoreStockCartLinks}]} ]
     */
    public static function getAllItemsForAccountGroupedByStore(vRecordId $account) : Response
    {
        $resp = new Response(false, "unkown error in geting all items for account grouped by cart", null);

        try
        {
            $getAllItemsWithStoreIdResp = self::executeQuery("SELECT ss.*, c.ref_store_ctime, c.ref_store_crand 
            FROM v_store_stock_cart_link ss 
            LEFT JOIN v_cart c ON ss.ref_cart_ctime = c.ctime 
            AND ss.ref_cart_crand = c.crand
            WHERE c.ref_account_ctime = ? AND c.ref_account_crand = ?
            AND ss.removed = 0 AND ss.checked_out = 0;"
            , [$account->ctime, $account->crand]);


            if($getAllItemsWithStoreIdResp->success)
            {
                $items = $getAllItemsWithStoreIdResp->data;

                $groupedStoreStockCartLinks = [];

                foreach($items as $item)
                {

                    $storeId = new vRecordId($item["ref_store_ctime"], $item["ref_store_crand"]);

                    $convertResp = StoreStockCartLinkController::ViewRowToObject($item);

                    if($convertResp->success)
                    {
                        $storeGroupExists = false;

                        foreach($groupedStoreStockCartLinks as &$group)
                        {
                            $storeCtime = $group["storeCtime"];
                            $storeCrand = $group["storeCrand"];

                            if($storeCtime == $storeId->ctime && $storeCrand == $storeId->crand)
                            {
                                $storeGroupExists = true;
                                
                                array_push($group["items"],$convertResp->data);

                                break;
                            }
                        }

                        if($storeGroupExists == false)
                        {
                            $groupedStoreStockCartLinks[] = ["storeCtime"=>$storeId->ctime, "storeCrand"=>$storeId->crand, "items"=>[$convertResp->data]];
                        }
                        
                    }
                    else
                    {
                        $resp->message = "Error in converting view row to object : $convertResp->message";

                        return $resp;
                    }
                }

                $resp->success = true;
                $resp->message = "returned all items for accounts, grouped by store";
                $resp->data = $groupedStoreStockCartLinks;
            }
            else
            {
                $resp->message = "error in getting all items for acccount with store info : $getAllItemsWithStoreIdResp->message";
            }
        }
        catch(Exception $e)
        {
            throw new Exception("exception caught while getting all items for account grouped by store : $e");
        }

        return $resp;
    }

    public static function getAccountStoreCart(vRecordId $accountId, vRecordId $storeId) : Response
    {
        $resp = new Response(false, "Unkown error in getting Account cart", null);

        try
        {
            $getWhereResp = CartController::getWhere(["ref_account_ctime"=>$accountId->ctime, "ref_account_crand"=>$accountId->crand, "ref_store_ctime"=>$storeId->ctime, "ref_store_crand"=>$storeId->crand, "checked_out"=>0]);

            if($getWhereResp->success)
            {
                if($getWhereResp->data != null)
                {
                    $resp->data = $getWhereResp->data[0];
                    $resp->success = true;
                    $resp->message = "Returned already existing Account cart";
                }
                else
                {
                    $cart = new Cart($accountId, $storeId);

                    $insertResp = CartController::insert($cart);

                    if($insertResp->success)
                    {
                        $getResp = CartController::get($cart);

                        if($getResp->success)
                        {
                            $resp->success = true;
                            $resp->message = "Succesfully created and returned new cart";
                            $resp->data = $getResp->data;
                        }
                        else
                        {
                            $resp->message = "Failed to get cart after inserting : ".$getResp->message;
                        }     
                    }
                    else
                    {
                        $resp->message = "Error in inserting new cart : ".$insertResp->message;
                    }
                }
            }
            else
            {
                $resp->message = "Error in getting open cart for account : ".$getWhereResp->message;
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while getting Account cart : ".$e);
        }

        return $resp;
    }

    public static function getAllCartsForAccount(vRecordId $account) : Response
    {
        $resp = new Response(false, "unkown error in getting all carts for account", null);

        try
        {
            $getCartsResp = CartController::getWhere(["ref_account_ctime"=>$account->ctime, "ref_account_crand"=>$account->crand]);

            if($getCartsResp->success)
            {
                $carts = $getCartsResp->data;

                $resp->success = true;
                $resp->data = $carts;

                if(count($carts) > 0)
                {
                    $resp->message = "returned all carts associtaed with account";
                }
                else
                {
                    $resp->message = "no carts accosiated with account";
                }
            }
            else
            {
                $resp->message = "error in getting all carts for account : $getCartsResp->message";
            }
        }
        catch(Exception $e)
        {
            throw new Exception("exception caught while getting all carts for account ".self::printIdDebugInfo(["account"=>$account])." : $e");
        }

        return $resp;
    }

    public static function getAllItemTotalsForAccount(vRecordId $account, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "unkown error in getting all item totals for account", null);

        try
        {   
            $priceTotalsArrayResp = StoreStockCartLinkController::getGroupedItemPriceArrayForAccount($account, $databaseName);

            if($priceTotalsArrayResp->success)
            {
                $resp->success = true;
                $resp->message = "successfully returned account total";
                $resp->data = $priceTotalsArrayResp->data;
            }
            else
            {
                $resp->message = "error in getting total of cart store stock cart links : ".$priceTotalsArrayResp->message;
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while trying to get total of account with message : ".$resp->message);
        }


        return $resp;
    }

    public static function doesAccountHaveADATotals(vRecordId $accountId, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unkown error checking if account has ADA totals", null);

        try
        {
            if($databaseName != null)
            {
                Database::changeDatabase($databaseName);
            }

            $query = "
                SELECT ctime 
                FROM ".TransactionController::getTableName()." 
                WHERE ref_currency_item_ctime IS NULL AND ref_currency_item_crand 
                AND payed = 0 AND complete = 0 AND void = 0 
                AND ref_account_ctime = ? AND ref_account_crand 
                LIMIT 1;";

            $executeResp = DatabaseController::executeQuery($query);

            

            if($executeResp->data->num_rows > 0)
            {
                $resp->message = "account has ADA totals";
                $resp->data = true;
            }
            else
            {
                $resp->message = "account does not have ADA totals";
                $resp->data = false;
            }

            $resp->success = true;
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while checking if account has ADA totals : ".$e);
        }

        return $resp;
    }

    public static function getAccountAdaTotal(vRecordId $accountId, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "unkown error in getting ada totals for account", null);

        try
        {
            
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while getting ada totals for account ".json_encode($accountId)." : $e" );
        }

        return $resp;
    }

    public static function getCartAdaTotal(vRecordId $accountId, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unkown error in getting the ADA totals for cart", null);

        try
        {
            $AdaCurrencyLinksResp = StoreStockCartLinkController::getWhere(["ref_currency_item_ctime"=>null, "ref_currency_item_crand"=>null]);

            $AdaLinks = $AdaCurrencyLinksResp->data;

            $lovelace = 0;

            foreach($AdaLinks as $link)
            {
                $lovelace = $link->price;
            }

            $resp->success = true;
            $resp->message = "Returned ADA totals in Lovelace";
            $resp->data = $lovelace;
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while getting cart ADA totals : $e");
        }

        return $resp;
    }

    /**
     * Publically exposed polymophic methods
     */

    public static function generateTransactions(vCart|vAccount $target, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Switch Statement Failed class : ".strval(get_class($target)), null);

        switch(get_class($target))
        {
            case vCart::class:
                return self::cart_generateTransactions($target, $databaseName);
            case vAccount::class:
                return self::account_generateTransactions($target, $databaseName);

        }

        return $resp;
    }

    public static function getItemTotals(vCart|vAccount $target, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Switch Statement Failed class : ".strval(get_class($target)), null);

        switch(get_class($target))
        {
            case vCart::class:
                return self::cart_getItemTotals($target, $databaseName);
            case vAccount::class:
                return self::account_getItemTotals($target, $databaseName);

        }

        return $resp;
    }

    public static function payTotals(vCart|vAccount $target, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Switch Statement Failed class : ".strval(get_class($target)), null);

        switch(get_class($target))
        {
            case vCart::class:
                return self::cart_payTotals($target, $databaseName);
            case vAccount::class:
                return self::account_payTotals($target, $databaseName);

        }

        return $resp;
    }

    public static function checkout(vCart|vAccount $target, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Switch Statement Failed class : ".strval(get_class($target)), null);

        switch(get_class($target))
        {
            case vCart::class:
                return self::cart_checkout($target, $databaseName);
            case vAccount::class:
                return self::account_checkout($target, $databaseName);

        }

        return $resp;
    }

    public static function removeProductFromCart(vRecordId $storeStockCartLinkId, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "unknown error in removeing product from cart", null);

        try
        {
            $removeResp = StoreStockCartLinkController::markStoreStockCartLinkAsRemoved($storeStockCartLinkId, $databaseName);

            if($removeResp->success)
            {
                $resp->success = true;
                $resp->message = "Removed product from cart";
            }
            else
            {
                $resp->message = "failed to remove stores stock cart link from cart : $removeResp->message";
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while removing product from cart : $e");
        }

        return $resp;
    }


    /*
    // Prepares the cart for checking out by generating the associated transactions
    // This should be execute when the user arrives at the cart_checkout page, before payment
    // voids all previous open transactions
    // generates transactions for item totals in cart and their associated links
    */
    public static function cart_generateTransactions(vCart $cart, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unkown error in generating transcations for cart", null);

        try
        {
            $voidTransactions = CartController::cart_voidAllPreviousTransactions($cart, $databaseName); 

            if($voidTransactions->success)
            {
                $resp = CartController::cart_createAllTransactions($cart, $databaseName);
            }
            else
            {
                $resp->message = "Error in voiding previous tranactions : ".$voidTransactions->message;
            } 
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while generating transactions for cart : ".$e);
        }

        return $resp;
    }

    private static function cart_voidAllPreviousTransactions(vCart $cart, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unkown error in voiding previous transcations for cart", null);

        try
        {
            $voidTransactions = CartController::cart_voidPreviousUnpayedUncompletedTransactions($cart,$databaseName);

            if($voidTransactions->success)
            {
                $voidTransactionGroup = CartController::cart_voidPreviousUnpayedUncompletedTransactionGroups($cart,$databaseName);

                if($voidTransactionGroup->success)
                {
                    $resp->success = true;
                    $resp->message = "successfully voided all preivous transactions for cart";
                }
                else
                {
                    $resp->message = "Error in voiding previosly unpayed or uncomplete transaction groups : ".$voidTransactionGroup->message;
                }
            }
            else
            {
                $resp->message = "Error in voiding previously unayed or uncomplete tranactions : ".$voidTransactions->message;
            } 
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while voiding previous transactions for cart : ".$e);
        }

        return $resp;
    }

    private static function cart_createAllTransactions(vCart $cart, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "unkown error in creating all transactions for cart", null);

        $totalsArrayResp = CartController::cart_getItemTotals($cart, $databaseName);

        try
        {
            if($totalsArrayResp->success)
            {
                
                $createResp = CartController::cart_createTransactions($cart->accountId, $totalsArrayResp->data, $databaseName);
    
                if($createResp->success)
                {
                    
                    $createGroupResp = CartController::cart_createCartTransactionGroup($cart, $databaseName);
    
                    if($createGroupResp->success)
                    {
                        $linkResp = CartController::cart_linkTransactionsToTransactionGroup($createGroupResp->data, $createResp->data, $databaseName);
    
                        if($linkResp->success)
                        {
                            $resp->success = true;
                            $resp->message = "succesfully generated transactions for cart";
                            $resp->data = $createGroupResp->data;
                        }
                        else
                        {
                            $resp->message = "Error in linking transactions to cart transaction group : ".$linkResp->message;
                        }
                    }
                    else
                    {
                        $resp->message = "Error in creating cart transaction group with message : ".$createGroupResp->message;
                    }
                }
                else
                {
                    $resp->message = "Error in creating transacitons for cart with message : ".$createResp->message;
                }
            }
            else
            {
                $resp->message = "Error in getting cart total to generate transactions for cart";
            }
        }
        catch(Exception $e)
        {
            $resp->message = "Exception caught while creating all transactions for cart : ".$e;
        }

        return $resp;
    }

    private static function cart_createTransactions(vRecordId $accountId, array $cartTotals, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unkown error in creating transations for cart", null);

        $transactions = [];
        
        foreach($cartTotals as $totalGroup)
        {
            $totalPrice = $totalGroup["price"];

            $transaction = new Transaction($totalPrice->smallCurrencyUnit, $totalPrice->currencyItemId, $accountId);

            
            $prepResp = TransactionController::prepInsertForBatch($transaction, $databaseName);
            
            $transactions[] = $transaction;
        }
        
        $executeResp = TransactionController::executeBatchInsertion();
        
        if($executeResp->success)
        {
            $resp->success = true;

            $resp->message = "successfully created transactions for cart cart_checkout. ";
            $resp->data = $transactions;
        }
        else
        {
            $resp->message = "Error in executing batch insertion with message : ".$prepResp->message;
        }

        return $resp;
    }

    private static function cart_createCartTransactionGroup(vCart $cart, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unkown error in creating cart transaction group", null);

        try
        {
            $cartTransactionGroup = new CartTransactionGroup($cart);

            $insertResp = CartTransactionGroupController::insert($cartTransactionGroup, $databaseName);

            if($insertResp->success)
            {
                $resp->success = true;
                $resp->message = "successfully created cart transaction group";
                $resp->data = $cartTransactionGroup;
            }
            else
            {
                $resp->message = "Error in inserting cart transaction group with message : ".$insertResp->message;
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while creating cart transaction group ".$e);
        }

        return $resp;   
    }

    private static function cart_linkTransactionsToTransactionGroup(vRecordId $cartTransactionGroupId, array $transactions, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unkown error in linking transactions to transaction group", null);

        try
        {
            foreach($transactions as $transaction)
            {
                $transactionCartTransactionGroupLink = new TransactionCartTransactionGroupLink($transaction, $cartTransactionGroupId);
                
                TransactionCartTransactionGroupLinkController::prepInsertForBatch($transactionCartTransactionGroupLink);
            }
            
            $executeResp = TransactionCartTransactionGroupLinkController::executeBatchInsertion($databaseName);

            if($executeResp->success)
            {
                $resp->success = true;
                $resp->message = "Successfully linked transactions to transaction group";
            }
            else
            {
                $resp->message = "Error in executing batch insertion of transaction cart transaction group link : ".$executeResp->message;
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while linking transactions to transaction group : ".$e);
        }

        return $resp;
    }

    private static function cart_voidPreviousUnpayedUncompletedTransactions(vRecordId $cartId, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unkown error in voiding previous transactions that are both unpayed and uncompleted", null);

        try
        {

            if($databaseName != null)
            {
                Database::changeDatabase($databaseName);
            }

            $query = 
            "UPDATE transaction tx 
            LEFT JOIN transaction_cart_transaction_group_link txgrouplink 
            ON txgrouplink.ref_transaction_ctime = tx.ctime AND txgroupLink.ref_transaction_crand = tx.crand 
            LEFT JOIN cart_transaction_group carttx 
            ON carttx.ctime = txgrouplink.ref_cart_transaction_group_ctime AND carttx.crand = txgrouplink.ref_cart_transaction_group_crand
            SET tx.void = 1 
            WHERE carttx.ref_cart_ctime = ? AND carttx.ref_cart_crand = ?";

            $params = [$cartId->ctime, $cartId->crand];

            $executeResp = CartController::executeQuery($query, $params);

            if($executeResp->success)
            {
                $resp->success = true;
                $resp->message = "successfully voided preivous unpayed and uncomplete transactions";
            }
            else
            {
                $resp->message = "error in executing query to void previous unpayed and uncomplete transactions : ".$executeResp->message;
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while voiding previous unpayed and uncomplete : ".$e);
        }

        return $resp;
    }

    private static function cart_voidPreviousUnpayedUncompletedTransactionGroups(vRecordId $cartId, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "unkown error in voiding previously unpayed or uncomplete transaction groups", null);

        try
        {
            if($databaseName != null)
            {
                Database::changeDatabase($databaseName);
            }

            $stmt = "UPDATE cart_transaction_group carttx
            SET void = 1 WHERE ref_cart_ctime = ? AND ref_cart_crand = ?;
            ";

            $params = [$cartId->ctime, $cartId->crand];

            $executeResp = CartController::executeQuery($stmt, $params);

            if($executeResp->success)
            {
                $resp->success = true;
                $resp->message = "successfully voided previously unpyaed or uncomplete transaction group for cart : ".$executeResp->message;
            }
            else
            {
                $resp->message = "failed to execute query to void previously unpayed or uncomplete transaction groups for cart : ".$executeResp->message;
            }

        }
        catch(Exception $e)
        {
            $resp->message = "exception caught while voiding previously unpayed or uncomplete transaction groups : ".$e;
        }

        return $resp;
    }

    public static function cart_getItemTotals(vCart $cart, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unknown error in getting cart total", null);

        try
        {   
            $priceTotalsArrayResp = StoreStockCartLinkController::getGroupedItemPriceArrayForCart($cart, $databaseName);

            if($priceTotalsArrayResp->success)
            {
                $resp->success = true;
                $resp->message = "successfully returned cart total";
                $resp->data = $priceTotalsArrayResp->data;
            }
            else
            {
                $resp->message = "error in getting total of cart store stock cart links : ".$priceTotalsArrayResp->message;
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while trying to get total of cart with message : ".$resp->message);
        }

        return $resp;
    }

    /*
    //Attempts to pay totals for cart
    //Whatever method is being used for ADA payment must provide a function
    //
    //Callback function is to attempt to pay ada totals
    //This must return a \Kickback\Backend\Models\Response object.
    //The success feild of this response must indicate the transaction's success as a boolean
    //true = success | false = failed
    */
    public static function cart_payTotals(vCart $cart, ?callable $payAdaTotalCallback = null, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unkown error in paying totals", null);

        if(CartController::cart_doesCartHaveADATotals($cart, $databaseName)->data)
        {
            if($payAdaTotalCallback == null)
            {
                throw new Exception("Cart has ADA totals although no ADA total callback was provided");
            }
        }
        else
        {    
            if($payAdaTotalCallback == null)
            {
                $payAdaTotalCallback = function(){ return new Response(true, "No ADA Toatals In Cart : Any Callback ignored | Callback not provided", true); };  
            }
            else
            {
                $payAdaTotalCallback = function(){ return new Response(true, "No ADA Toatals In Cart : Any Callback ignored | Callback provided", true); };
            }
        }

        try
        {
            $canAccountPayTotalsResp = CartController::cart_canAccountPayItemTotals($cart, $databaseName);

            if($canAccountPayTotalsResp->success)
            {

                $cartItemTotalsPaidResp = CartController::cart_payItemTotals($cart); 

                if($cartItemTotalsPaidResp->success)
                {
                    $adaPaidResp = call_user_func($payAdaTotalCallback);

                    if($adaPaidResp->success)
                    {
                        $markAsPaidResp = CartController::cart_markCartAsPaid($cart, $databaseName);

                        if($markAsPaidResp->success)
                        {
                            $resp->success = true;
                            $resp->message = "Cart Successfully Payed For";
                        }
                        else
                        {
                            $resp->message = "Cart was successfully payed for although an error occured when marking cart as payed : ".$markAsPaidResp->message;
                        }
                    }
                    else
                    {
                        $refundResp = CartController::cart_refundItemTotals($cart, $cartItemTotalsPaidResp->data);

                        if($refundResp->success)
                        {
                            $resp->message = "Failed to pay ADA total, Item totals refunded to user";
                        }
                        else
                        {
                            $resp->message = "Failed to pay ADA total : FAILED TO REFUND ITEMS BACK TO USER | CART INFO : ".json_encode($cart). " | ITEM TOTAL INFO : ".json_encode($cartItemTotalsPaidResp->data);
                        }
                    }
                }
                else
                {
                    $resp->message = "Error paying cart totals : ".$cartItemTotalsPaidResp->message;
                }
            }
            else
            {
                $resp->message = "Error in checking if account can pay totals of cart : ".$canAccountPayTotalsResp->message;
            }

            
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while paying totals : ".$e);
        }
        
        return $resp;
    }

    /*Checks if account is able to pay item totals
    //It's good to check if the account is able to pay the item totals before attempting to process any ADA transcations
    //This will ensure the process will only rely on the ADA transaction going through and can be furhter prevented if it fails
    */
    public static function cart_canAccountPayItemTotals(vCart $cart, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unkown error in checking if account can pay totals", null);

        try
        {
            $defecitArrayResp = CartController::cart_getAccountItemTotalsDefecitArray($cart, $databaseName);

            if($defecitArrayResp->success)
            {
                $defecits = $defecitArrayResp->data;

                $canPay = true;

                foreach($defecits as $defecit)
                {
                    if($defecit > 0)
                    {
                        $canPay = false;
                        break;
                    }
                }

                $resp->success = true;

                if($canPay)
                {
                    $resp->message = "Account Can Pay Cart Totals";
                    $resp->data = true;
                }
                else
                {   
                    $resp->message = "Account Cannot Pay Cart Totals";
                    $resp->data = false;
                }
            }
            else
            {
                $resp->message = "Error in getting array of defeict of totals of cart : ".$defecitArrayResp->message;
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while checking if account can pay totals : ".$e);
        }

        return $resp;
    }

    private static function cart_doesCartHaveADATotals(vCart $cart, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unkown error checking if cart has ADA totals", null);

        try
        {
            if($databaseName != null)
            {
                Database::changeDatabase($databaseName);
            }

            $query = "
                SELECT ctime 
                FROM ".TransactionController::getTableName()." 
                WHERE ref_currency_item_ctime IS NULL AND ref_currency_item_crand 
                AND payed = 0 AND complete = 0 AND void = 0 IS NULL 
                LIMIT 1;";

            $executeResp = DatabaseController::executeQuery($query);

            if($executeResp->data->num_rows > 0)
            {
                $resp->message = "Cart has ADA totals";
                $resp->data = true;
            }
            else
            {
                $resp->message = "Cart does not have ADA totals";
                $resp->data = false;
            }

            $resp->success = true;
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while checking if cart has ADA totals : ".$e);
        }

        return $resp;
    }

    private static function cart_refundItemTotals(vCart $cart, array $itemIdArray) : Response
    {
        $resp = new Response(false, "Unkown error in refunding items", null);

        try
        {

            $refundTradeResp = TradeController::tradeArray($itemIdArray, TradeController::returnKickbackAccountId(), $cart->accountId);

                if($refundTradeResp->success)
                {
                    $resp->success = true;
                    $resp->message = "Successfully refunded item totals";  
                }
                else
                {
                    throw new Exception("Exception caught in refunding item totals to kickback kingdom");
                }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while refunding items : ".$e);
        }  

        return $resp;
    }

    private static function cart_payItemTotals(vCart $cart, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unkown error in paying item totals", null);

        try
        {
            $getTotalsResp = CartController::cart_getItemTotals($cart, $databaseName);

            if($getTotalsResp->success)
            {
                $itemIdArray = CartController::cart_individualizeItemTotalArrayIntoItemIdArray($getTotalsResp->data);

                $tradeToKickbackResp = TradeController::tradeArray($itemIdArray, TradeController::returnKickbackAccountId(), $cart->accountId, $databaseName);

                if($tradeToKickbackResp->success)
                {
                    $resp->success = true;
                    $resp->message = "Successfully payed item totals";   
                    $resp->data = $itemIdArray;
                }
                else
                {
                    throw new Exception("Exception caught in trading item totals to kickback kingdom : $tradeToKickbackResp->message");
                }
            }
            else
            {
                $resp->message = "Error in getting item totals to pay cart item totals : ".$getTotalsResp->message;
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught paying item totals : ".$e);
        }

        return $resp;
    }

    private static function cart_individualizeItemTotalArrayIntoItemIdArray(array $itemTotalArray) : Array
    {
        $expandedItemTotalArray = [];

        foreach($itemTotalArray as $itemTotal)
        {

            for($i = 0; $i < $itemTotal["price"]->smallCurrencyUnit; $i++)
            {
                $expandedItemTotalArray[] = $itemTotal["price"]->currencyItemId;
            }
        }

        return $expandedItemTotalArray;
    }
    
    private static function cart_markCartAsPaid(vRecordId $cart, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unkown error in marking cart as paid", null);

        try
        {

            $groupIdResp = CartTransactionGroupController::getWhere([
                "ref_cart_ctime"=>$cart->ctime, 
                "ref_cart_crand"=>$cart->crand, 
                "void"=>0, 
                "completed"=>0, 
                "payed"=>0
            ], $databaseName);

            if($groupIdResp->success)
            {
                $markResp = CartTransactionGroupController::markAssociatedTransactionAsPaid($groupIdResp->data[0], $databaseName);

                if($markResp->success)
                {
                    $resp->success = true;
                    $resp->message = "successfully marked cart as paid";
                }
                else
                {
                    $resp->message = "Error in marking cart transactions as paid : ".$markResp->message;
                }
            }
            else
            {
                $resp->message = "Error in getting group transaction id : ".$groupIdResp->message;
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught marking cart as paid : ".$e);
        }

        return $resp;
    }   

    private static function cart_getAccountItemTotalsDefecitArray(vCart $cart, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unkown error in getting defecit array for account", null);

        try
        {
            $totalsArrayResp = CartController::cart_getItemTotals($cart, $databaseName);

            if($totalsArrayResp->success)
            {
                $totalsItemIdArray = CartController::cart_returnTotalsItemIdArray($totalsArrayResp->data);

                $lootOfTotalsArrayResp = LootController::getTotalsInLoot($totalsItemIdArray, $cart->accountId, $databaseName);

                if($lootOfTotalsArrayResp->success)
                {
                    $lootTotals = CartController::cart_returnTotalsLootIdArray($lootOfTotalsArrayResp->data);

                    $defecitArray = CartController::cart_calculateLootTotalsDefecit($lootTotals, $totalsItemIdArray);

                    $resp->success = true;
                    $resp->message = "Successfully calculated defecit array";
                    $resp->data = $defecitArray;
                }
                else
                {
                    $resp->message = "Error in getting loot from account of totals : ".$lootOfTotalsArrayResp->message;
                }
            }
            else
            {
                $resp->message = "Error in getting totals for cart to calculate defecit array for account : ".$totalsArrayResp->message;
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while trying to get totals defecit array for account : ".$e);
        }

        return $resp;
    }

    private static function cart_calculateLootTotalsDefecit(array $lootOfTotalsArray, array $totalsArray) : Array
    {
        $sortFunction = function($firstPrice, $secondPrice) { return CartController::cart_alphabetizePriceArrayObjects($firstPrice, $secondPrice); };

        usort($lootOfTotalsArray, $sortFunction);
        usort($totalsArray, $sortFunction);

        $lootTotalsDefecitArray = array_map(
            function($totalAmount, $lootAmount)
            { return max(($totalAmount["Amount"] - $lootAmount["Amount"]), 0); }, 
            $totalsArray, $lootOfTotalsArray);

        return $lootTotalsDefecitArray;  
    }

    private static function cart_alphabetizePriceArrayObjects($firstPriceArrayItem, $secondPriceArrayItem) : float
    {
        return strcmp($firstPriceArrayItem[0], $secondPriceArrayItem[0]);
    }

    private static function cart_returnTotalsLootIdArray(mysqli_result $totalsArray) : Array
    {
        $lootIdArray = [];

        while($total = $totalsArray->fetch_assoc())
        {

            $lootIdArray[] = ["Id"=>$total["Id"], "Amount"=>$total["Amount"]];
        }

        return $lootIdArray;
    }

    private static function cart_returnTotalsItemIdArray(array $totalsArray) : Array
    {
        $itemIdArray = [];

        foreach($totalsArray as $total)
        {
            $itemIdArray[] = ["Id"=>$total["price"]->currencyItemId->crand, "Amount"=>$total["price"]->smallCurrencyUnit];
        }

        return $itemIdArray;
    } 

    /*
    // Checks Cart Out
    // Marks store stock links as checked out and transactions as complete
    // Checks if cart is payed for before executing
    // Transfers store stocks from store to player's loot inventory
    */
    public static function cart_checkout(vCart $cart, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unknown error in checking out cart", null);

        try
        {
            $paidResp = CartController::cart_isPaidFor($cart, $databaseName);

            if($paidResp->success)
            {
                if($paidResp->data)
                {
                    $transferResp = CartController::cart_transferCartToInventory($cart, $databaseName);

                    if($transferResp->success)
                    {
                        $markAsCompleteResp = CartController::cart_markCartTransactionsAsComplete($cart, $databaseName);

                        if($markAsCompleteResp->success)
                        {
                            $markLinksAsCheckedOutResp = CartController::cart_markLinksAsCheckedOut($cart, $databaseName);

                            if($markLinksAsCheckedOutResp->success)
                            {
                                $markCartAsCheckedOutResp = CartController::cart_markCartAsCheckedOut($cart, $databaseName);

                                if($markCartAsCheckedOutResp->success)
                                {
                                    $logTransactionResp = TransactionLogController::logTransaction("Cart Checked Out", json_encode(["Cart"=>$cart]), $cart->accountId, $databaseName);

                                    if($logTransactionResp->success)
                                    {
                                        $resp->success = true;
                                        $resp->message = "Cart successfully checked out | Check out logged";   
                                    }
                                    else
                                    {
                                        $resp->message = "Failed to log cart cart_checkout : ".$logTransactionResp->message;
                                    }
                                }
                                else
                                {
                                    $resp->message = "Error in marking cart as checked out : ".$markCartAsCheckedOutResp->message;
                                }
                            }
                            else
                            {
                                $resp->message = "Error in marking previous links as checked out : ".$markLinksAsCheckedOutResp->message;
                            }                      
                        }
                        else
                        {
                            $resp->message = "Error in marking cart transactions as complete : ".$markAsCompleteResp->message;
                        }
                    }
                    else
                    {
                        $resp->message = "Error in transferring items to cart : ".$transferResp->message;
                    }
                }
                else
                {
                    $resp->message = "Cart is not paid for and cannot check out : ".$paidResp->message;
                }
            }
            else
            {
                $resp->message = "failed to check if cart is paid for : ".$paidResp->message;
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while trying to check out cart : ".$e);
        }

        return $resp;
    }

    private static function cart_markCartAsCheckedOut(vRecordId $cartId, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unkown error in marking cart as checked out", null);

        try
        {
            if($databaseName != null)
            {
                Database::changeDatabase($databaseName);
            }

            $query = "UPDATE ".CartController::getTableName()." SET checked_out = 1 WHERE ctime = ? AND crand = ?;";

            $params = [$cartId->ctime, $cartId->crand];

            $executeResp = CartController::executeQuery($query, $params);

            if($executeResp->success)
            {
                $resp->success = true;
                $resp->message = "Successfully marked cart as checked out";
            }
            else
            {
                $resp->message = "Error in executing query to mark cart as checked out : ".$executeResp->message;
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while marking cart as checked out : ".$e);
        }

        return $resp;
    }

    private static function cart_markLinksAsCheckedOut(vRecordId $cart, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unkown error in marking links in cart as checked out", null);

        $query = "UPDATE ".StoreStockCartLinkController::getTableName()." 
        SET checked_out = 1 
        WHERE ref_cart_ctime = ? AND ref_cart_crand = ? 
        AND checked_out = 0 AND removed = 0;";

        $params = [$cart->ctime, $cart->crand];

        try
        {
            if($databaseName != null)
            {
                Database::changeDatabase($databaseName);
            }

            $result = Database::executeSqlQuery($query, $params);

            $resp->success = true;
            $resp->message = "Marked links in cart as checked out";
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while marking links in cart as checked out : ".$e);
        }

        return $resp;
    }

    private static function cart_markCartTransactionsAsComplete(vRecordId $cartId, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unknown error in marking cart transaction group as complete", null);

        try
        {
            $groupTransactionIdResp = CartTransactionGroupController::getWhere([
                "ref_cart_ctime"=>$cartId->ctime,
                "ref_cart_crand"=>$cartId->crand,
                "payed"=>1,
                "completed"=>0,
                "void"=>0
            ], $databaseName);

            if($groupTransactionIdResp->success)
            {
                $groupTransactionId = $groupTransactionIdResp->data[0];

                $markResp = CartTransactionGroupController::markAssociatedTransactionAsComplete($groupTransactionId, $databaseName);

                if($markResp->success)
                {
                    $resp->success = true;
                    $resp->message = "Successfully marked cart transactions as complete";
                }
                else
                {
                    $resp->message = "Error in marking associated transactions as complete for cart : ".$markResp->message;
                }
            }
            else
            {
                $resp->message = "Error in getting group transaction id to mark cart group transaction as complete : ".$groupTransactionIdResp->message;
            }
        }
        catch(Exception $e)
        {
            $resp->message = "Exception caught marking cart transaction group as complete : ".$e;
        }

        return $resp;
    }

    private static function cart_transferCartToInventory(vCart $cart, ?string $databaseName) : Response
    {
        $resp = new Response(false, "unkown error in transfering cart to inventory", null);

        try
        {
            $itemIdArrayResp = CartController::cart_getCartItemIdArray($cart, $databaseName);

            if($itemIdArrayResp->success)
            {
                $itemIdArray = $itemIdArrayResp->data;

                $giveLootResp = LootController::giveLootArray($cart->accountId, $itemIdArray, $databaseName);

                if($giveLootResp->success)
                {
                    $resp->success = true;
                    $resp->message = "successfully transfered items to inventory";
                    $resp->data = $itemIdArray;
                }
                else
                {
                    $resp->message = "Error in giving item array from cart : ".$giveLootResp->message;
                }
            }
            else
            {
                $resp->message = "error in getting cart item id array : ".$itemIdArrayResp->message;
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while transfering cart to inventory : ".$e);
        }

        return $resp;
    }

    private static function cart_getCartItemIdArray(vRecordId $cartId, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unkown error in getting cart item array", null);

        $itemIdArray = [];

        try
        {
            $getResp = CartController::cart_getItemIdsOfTransactableStoreStocksInCart($cartId, $databaseName);

            if($getResp->success)
            {
                $linkedStoreStockIds = $getResp->data;

                foreach($linkedStoreStockIds as $linkId)
                {
                    $itemIdArray[] = ["Id"=>$linkId, "DateObtained"=>null];
                }

                $resp->success = true;
                $resp->message = "successfully returned item id array";
                $resp->data = $itemIdArray;
            }
            else
            {
                $resp->message = "error in getting all linked store stocks for cart : ".$getResp->message;
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while trying to get cart item id array : ".$e);
        }

        return $resp;   
    }

    private static function cart_getItemIdsOfTransactableStoreStocksInCart(vRecordId $cartId, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unkown error in getting transactalbe store stocks in cart", null);

        $query = "SELECT  p.ref_item_ctime AS ctime, p.ref_item_crand AS crand
        FROM store_stock_cart_link sslink
        LEFT JOIN store_stock ss ON ss.ctime = sslink.ref_store_stock_ctime AND ss.crand = sslink.ref_store_stock_crand
        LEFT JOIN product p ON p.ctime = ss.ref_product_ctime AND p.crand = ss.ref_product_crand
        WHERE sslink.ref_cart_ctime = ? AND sslink.ref_cart_crand = ? 
        AND sslink.removed = 0 AND sslink.checked_out = 0;";

        $params = [$cartId->ctime, $cartId->crand];

        try
        {
            if($databaseName != null)
            {
                Database::changeDatabase($databaseName);
            }
            
            $result = Database::executeSqlQuery($query, $params);

            if($result->num_rows > 0)
            {
                $storeStockIds = [];

                while($row = $result->fetch_assoc())
                {
                    $storeStockIds[] = new vRecordId($row["ctime"], $row["crand"]);
                }

                $resp->success = true;
                $resp->message = "Returned Transactable Store Stock Found In Cart";
                $resp->data = $storeStockIds;
            }
            else
            {
                $resp->success = true;
                $resp->message = "No Transactable Store Stocks Found In Cart";
                $resp->data = [];
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while getting transactable store stocks in cart : ".$e);
        }

        return $resp;
    }

    private static function cart_isPaidFor(vRecordId $cartId, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unkown error in checking if cart is paid for", null);

        $tableName = CartTransactionGroupController::getTableName();

        $query = "SELECT payed FROM ".$tableName." WHERE ref_cart_ctime = ? AND ref_cart_crand = ?;";

        $params = [$cartId->ctime, $cartId->crand];

        try
        {
            if($databaseName != null)
            {
                Database::changeDatabase($databaseName);
            }

            $executeResp = CartController::executeQuery($query, $params);

            if($executeResp->success)
            {
                $resp->success = true;

                $result = $executeResp->data;
                if($result->num_rows > 0)
                {
                    if($result->fetch_assoc()["payed"])
                    {
                        $resp->message = "Cart is paid for";
                        $resp->data = true;
                    }
                    else
                    {
                        $resp->message = "Cart is not paid for";
                    }
                }
                else
                {
                    $resp->message = "Could not find matching cart for provided id : ".$executeResp->message;
                }
            }
            else
            {
                $resp->message = "Failed to execute query to check if cart is paid for : ".$executeResp->message;
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while checking if cart is paid for : ".$e);
        }

        return $resp;
    }

    /*ACCOUNT LEVEL cart_checkout*/////////////////////////////////////////////////////////////////////

    /*
    //Attempts to pay totals for account
    //Whatever method is being used for ADA payment must provide a function
    //
    //Callback function is to attempt to pay ada totals
    //This must return a \Kickback\Backend\Models\Response object.
    //The success feild of this response must indicate the transaction's success as a boolean
    //true = success | false = failed
    */

     /*
    // Prepares the cart for checking out by generating the associated transactions
    // This should be execute when the user arrives at the checkout page, before payment
    // voids all previous open transactions
    // generates transactions for item totals in cart and their associated links
    */
    public static function account_generateTransactions(vAccount $cart, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unkown error in generating transcations for cart", null);

        try
        {
            $voidTransactions = CartController::account_voidAllPreviousTransactions($cart, $databaseName); 

            if($voidTransactions->success)
            {
                $resp = CartController::account_createAllTransactions($cart, $databaseName);
            }
            else
            {
                $resp->message = "Error in voiding previous tranactions : ".$voidTransactions->message;
            } 
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while generating transactions for cart : ".$e);
        }

        return $resp;
    }

    private static function account_voidAllPreviousTransactions(vAccount $cart, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unkown error in voiding previous transcations for cart", null);

        try
        {
            $voidTransactions = CartController::account_voidPreviousUnpayedUncompletedTransactions($cart,$databaseName);

            if($voidTransactions->success)
            {
                $voidTransactionGroup = CartController::account_voidPreviousUnpayedUncompletedTransactionGroups($cart,$databaseName);

                if($voidTransactionGroup->success)
                {
                    $resp->success = true;
                    $resp->message = "successfully voided all preivous transactions for cart";
                }
                else
                {
                    $resp->message = "Error in voiding previosly unpayed or uncomplete transaction groups : ".$voidTransactionGroup->message;
                }
            }
            else
            {
                $resp->message = "Error in voiding previously unayed or uncomplete tranactions : ".$voidTransactions->message;
            } 
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while voiding previous transactions for cart : ".$e);
        }

        return $resp;
    }

    private static function account_createAllTransactions(vRecordId $account, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "unkown error in creating all transactions for cart", null);

        

        try
        {
            $cartsResp = CartController::getWhere(["ref_account_ctime"=>$account->ctime, "ref_account_crand"=>$account->crand]);

            if($cartsResp->success)
            {
                $carts = $cartsResp->data;

                $totalsArrayResp = CartController::account_getItemTotals($carts, $databaseName);

                if($totalsArrayResp->success)
                {
                    
                    $createResp = CartController::account_createTransactions($account, $totalsArrayResp->data, $databaseName);
        
                    if($createResp->success)
                    {
                        
                        $createGroupResp = CartController::account_createCartTransactionGroups($account, $databaseName);
        
                        if($createGroupResp->success)
                        {
                            $linkResp = CartController::account_linkTransactionsToTransactionGroup($createGroupResp->data, $createResp->data, $databaseName);
        
                            if($linkResp->success)
                            {
                                $resp->success = true;
                                $resp->message = "succesfully generated transactions for cart";
                                $resp->data = $createGroupResp->data;
                            }
                            else
                            {
                                $resp->message = "Error in linking transactions to cart transaction group : ".$linkResp->message;
                            }
                        }
                        else
                        {
                            $resp->message = "Error in creating cart transaction group with message : ".$createGroupResp->message;
                        }
                    }
                    else
                    {
                        $resp->message = "Error in creating transacitons for cart with message : ".$createResp->message;
                    }
                }
                else
                {
                    $resp->message = "Error in getting cart total to generate transactions for cart";
                }
            }
            else
            {
                $resp->message = "Error in getting all carts for account : $cartsResp->message";
            }  
        }
        catch(Exception $e)
        {
            $resp->message = "Exception caught while creating all transactions for cart : ".$e;
        }

        return $resp;
    }

    private static function account_createTransactions(vRecordId $accountId, array $cartTotals, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unkown error in creating transations for cart", null);

        $transactions = [];
        
        foreach($cartTotals as $totalGroup)
        {
            $totalPrice = $totalGroup["price"];

            $transaction = new Transaction($totalPrice->smallCurrencyUnit, $totalPrice->currencyItemId, $accountId);

            $prepResp = TransactionController::prepInsertForBatch($transaction, $databaseName);
            
            $transactions[] = $transaction;
        }
        
        $executeResp = TransactionController::executeBatchInsertion();
        
        if($executeResp->success)
        {
            $resp->success = true;

            $resp->message = "successfully created transactions for cart account_checkout. ";
            $resp->data = $transactions;
        }
        else
        {
            $resp->message = "Error in executing batch insertion with message : ".$prepResp->message;
        }

        return $resp;
    }

    private static function account_createCartTransactionGroups(vRecordId $accountId, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unkown error in creating cart transaction group", null);

        try
        {
            $cartsResp = CartController::getWhere(["ref_account_ctime"=>$accountId->ctime, "ref_account_crand"=>$accountId->crand]);

            if($cartsResp->success)
            {
                $carts = $cartsResp->data;
                $cartTransationGroupIds = [];

                if(count($carts) > 0)
                {
                    foreach($carts as $cart)
                    {
                        $cartTransactionGroup = new CartTransactionGroup($cart);
                        
                        array_push($cartTransationGroupIds, $cartTransactionGroup->getVRecordId());

                        $insertResp = CartTransactionGroupController::prepInsertForBatch($cartTransactionGroup);
                    }
    
                    $insertResp = CartTransactionGroupController::executeBatchInsertion($databaseName);
        
                    if($insertResp->success)
                    {
                        $resp->success = true;
                        $resp->message = "successfully created cart transaction group";
                        $resp->data = $cartTransationGroupIds;
                    }
                    else
                    {
                        $resp->message = "Error in inserting cart transaction group with message : ".$insertResp->message;
                    }
                }
                else
                {  
                    $resp->success = true;
                    $resp->message = "No carts for account to create transaction groups for : $cartsResp->message";
                    $resp->data = $cartTransationGroupIds;
                }
            }
            else
            {
                $resp->message = "Error in getting all carts for account : $cartsResp->message";
            }

            
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while creating cart transaction group ".$e);
        }

        return $resp;   
    }

    private static function account_linkTransactionsToTransactionGroup(array $cartTransactionGroupIds, array $transactions, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unkown error in linking transactions to transaction group", null);

        try
        {
            foreach($transactions as $transaction)
            {
                $transactionCartTransactionGroupLink = new TransactionCartTransactionGroupLink($transaction, $cartTransactionGroupId);
                
                TransactionCartTransactionGroupLinkController::prepInsertForBatch($transactionCartTransactionGroupLink);
            }
            
            $executeResp = TransactionCartTransactionGroupLinkController::executeBatchInsertion($databaseName);

            if($executeResp->success)
            {
                $resp->success = true;
                $resp->message = "Successfully linked transactions to transaction group";
            }
            else
            {
                $resp->message = "Error in executing batch insertion of transaction cart transaction group link : ".$executeResp->message;
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while linking transactions to transaction group : ".$e);
        }

        return $resp;
    }

    private static function account_voidPreviousUnpayedUncompletedTransactions(vRecordId $accountId, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unkown error in voiding previous transactions that are both unpayed and uncompleted", null);

        try
        {

            if($databaseName != null)
            {
                Database::changeDatabase($databaseName);
            }

            $query = 
            "UPDATE transaction tx 
            LEFT JOIN transaction_cart_transaction_group_link txgrouplink 
            ON txgrouplink.ref_transaction_ctime = tx.ctime AND txgroupLink.ref_transaction_crand = tx.crand 
            LEFT JOIN cart_transaction_group carttx 
            ON carttx.ctime = txgrouplink.ref_cart_transaction_group_ctime AND carttx.crand = txgrouplink.ref_cart_transaction_group_crand
            LEFT JOIN cart cart ON carttx.ctime = cart.ctime AND carttx.crand = cart.crand
            SET tx.void = 1 
            WHERE cart.ref_account_ctime = ? AND cart.ref_account_crand = ?";

            $params = [$accountId->ctime, $accountId->crand];

            $executeResp = CartController::executeQuery($query, $params);

            if($executeResp->success)
            {
                $resp->success = true;
                $resp->message = "successfully voided preivous unpayed and uncomplete transactions";
            }
            else
            {
                $resp->message = "error in executing query to void previous unpayed and uncomplete transactions : ".$executeResp->message;
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while voiding previous unpayed and uncomplete : ".$e);
        }

        return $resp;
    }

    private static function account_voidPreviousUnpayedUncompletedTransactionGroups(vRecordId $cartId, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "unkown error in voiding previously unpayed or uncomplete transaction groups", null);

        try
        {
            if($databaseName != null)
            {
                Database::changeDatabase($databaseName);
            }

            $stmt = "UPDATE cart_transaction_group carttx LEFT JOIN cart c ON carttx.ref_cart_ctime = c.ctime AND carttx.ref_cart_crand = c.crand
            SET carttx.void = 1
            WHERE c.ref_account_ctime = ? AND c.ref_account_crand = ?;
            ";

            $params = [$cartId->ctime, $cartId->crand];

            $executeResp = CartController::executeQuery($stmt, $params);

            if($executeResp->success)
            {
                $resp->success = true;
                $resp->message = "successfully voided previously unpyaed or uncomplete transaction group for cart : ".$executeResp->message;
            }
            else
            {
                $resp->message = "failed to execute query to void previously unpayed or uncomplete transaction groups for cart : ".$executeResp->message;
            }

        }
        catch(Exception $e)
        {
            $resp->message = "exception caught while voiding previously unpayed or uncomplete transaction groups : ".$e;
        }

        return $resp;
    }

    public static function account_getItemTotals(vRecordId $cart, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unknown error in getting cart total", null);

        try
        {   
            $priceTotalsArrayResp = StoreStockCartLinkController::getGroupedItemPriceArrayForCart($cart, $databaseName);

            if($priceTotalsArrayResp->success)
            {
                $resp->success = true;
                $resp->message = "successfully returned cart total";
                $resp->data = $priceTotalsArrayResp->data;
            }
            else
            {
                $resp->message = "error in getting total of cart store stock cart links : ".$priceTotalsArrayResp->message;
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while trying to get total of cart with message : ".$resp->message);
        }

        return $resp;
    }

    /*
    //Attempts to pay totals for cart
    //Whatever method is being used for ADA payment must provide a function
    //
    //Callback function is to attempt to pay ada totals
    //This must return a \Kickback\Backend\Models\Response object.
    //The success feild of this response must indicate the transaction's success as a boolean
    //true = success | false = failed
    */
    public static function account_payTotals(vCart $cart, ?callable $payAdaTotalCallback = null, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unkown error in paying totals", null);

        if(CartController::account_doesCartHaveADATotals($cart, $databaseName)->data)
        {
            if($payAdaTotalCallback == null)
            {
                throw new Exception("Cart has ADA totals although no ADA total callback was provided");
            }
        }
        else
        {    
            if($payAdaTotalCallback == null)
            {
                $payAdaTotalCallback = function(){ return new Response(true, "No ADA Toatals In Cart : Any Callback ignored | Callback not provided", true); };  
            }
            else
            {
                $payAdaTotalCallback = function(){ return new Response(true, "No ADA Toatals In Cart : Any Callback ignored | Callback provided", true); };
            }
        }

        try
        {
            $canAccountPayTotalsResp = CartController::account_canAccountPayItemTotals($cart, $databaseName);

            if($canAccountPayTotalsResp->success)
            {

                $cartItemTotalsPaidResp = CartController::account_payItemTotals($cart); 

                if($cartItemTotalsPaidResp->success)
                {
                    $adaPaidResp = call_user_func($payAdaTotalCallback);

                    if($adaPaidResp->success)
                    {
                        $markAsPaidResp = CartController::account_markCartAsPaid($cart, $databaseName);

                        if($markAsPaidResp->success)
                        {
                            $resp->success = true;
                            $resp->message = "Cart Successfully Payed For";
                        }
                        else
                        {
                            $resp->message = "Cart was successfully payed for although an error occured when marking cart as payed : ".$markAsPaidResp->message;
                        }
                    }
                    else
                    {
                        $refundResp = CartController::account_refundItemTotals($cart, $cartItemTotalsPaidResp->data);

                        if($refundResp->success)
                        {
                            $resp->message = "Failed to pay ADA total, Item totals refunded to user";
                        }
                        else
                        {
                            $resp->message = "Failed to pay ADA total : FAILED TO REFUND ITEMS BACK TO USER | CART INFO : ".json_encode($cart). " | ITEM TOTAL INFO : ".json_encode($cartItemTotalsPaidResp->data);
                        }
                    }
                }
                else
                {
                    $resp->message = "Error paying cart totals : ".$cartItemTotalsPaidResp->message;
                }
            }
            else
            {
                $resp->message = "Error in checking if account can pay totals of cart : ".$canAccountPayTotalsResp->message;
            }

            
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while paying totals : ".$e);
        }
        
        return $resp;
    }

    /*Checks if account is able to pay item totals
    //It's good to check if the account is able to pay the item totals before attempting to process any ADA transcations
    //This will ensure the process will only rely on the ADA transaction going through and can be furhter prevented if it fails
    */
    public static function account_canAccountPayItemTotals(vCart $cart, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unkown error in checking if account can pay totals", null);

        try
        {
            $defecitArrayResp = CartController::account_getAccountItemTotalsDefecitArray($cart, $databaseName);

            if($defecitArrayResp->success)
            {
                $defecits = $defecitArrayResp->data;

                $canPay = true;

                foreach($defecits as $defecit)
                {
                    if($defecit > 0)
                    {
                        $canPay = false;
                        break;
                    }
                }

                $resp->success = true;

                if($canPay)
                {
                    $resp->message = "Account Can Pay Cart Totals";
                    $resp->data = true;
                }
                else
                {   
                    $resp->message = "Account Cannot Pay Cart Totals";
                    $resp->data = false;
                }
            }
            else
            {
                $resp->message = "Error in getting array of defeict of totals of cart : ".$defecitArrayResp->message;
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while checking if account can pay totals : ".$e);
        }

        return $resp;
    }

    private static function account_doesCartHaveADATotals(vCart $cart, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unkown error checking if cart has ADA totals", null);

        try
        {
            if($databaseName != null)
            {
                Database::changeDatabase($databaseName);
            }

            $query = "
                SELECT ctime 
                FROM ".TransactionController::getTableName()." 
                WHERE ref_currency_item_ctime IS NULL AND ref_currency_item_crand 
                AND payed = 0 AND complete = 0 AND void = 0 IS NULL 
                LIMIT 1;";

            $executeResp = DatabaseController::executeQuery($query);

            

            if($executeResp->data->num_rows > 0)
            {
                $resp->message = "Cart has ADA totals";
                $resp->data = true;
            }
            else
            {
                $resp->message = "Cart does not have ADA totals";
                $resp->data = false;
            }

            $resp->success = true;
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while checking if cart has ADA totals : ".$e);
        }

        return $resp;
    }

    private static function account_refundItemTotals(vCart $cart, array $itemIdArray) : Response
    {
        $resp = new Response(false, "Unkown error in refunding items", null);

        try
        {

            $refundTradeResp = TradeController::tradeArray($itemIdArray, TradeController::returnKickbackAccountId(), $cart->accountId);

                if($refundTradeResp->success)
                {
                    $resp->success = true;
                    $resp->message = "Successfully refunded item totals";  
                }
                else
                {
                    throw new Exception("Exception caught in refunding item totals to kickback kingdom");
                }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while refunding items : ".$e);
        }  

        return $resp;
    }

    private static function account_payItemTotals(vCart $cart, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unkown error in paying item totals", null);

        try
        {
            $getTotalsResp = CartController::account_getItemTotals($cart, $databaseName);

            if($getTotalsResp->success)
            {
                $itemIdArray = CartController::account_individualizeItemTotalArrayIntoItemIdArray($getTotalsResp->data);

                $tradeToKickbackResp = TradeController::tradeArray($itemIdArray, TradeController::returnKickbackAccountId(), $cart->accountId, $databaseName);

                if($tradeToKickbackResp->success)
                {
                    $resp->success = true;
                    $resp->message = "Successfully payed item totals";   
                    $resp->data = $itemIdArray;
                }
                else
                {
                    throw new Exception("Exception caught in trading item totals to kickback kingdom : $tradeToKickbackResp->message");
                }
            }
            else
            {
                $resp->message = "Error in getting item totals to pay cart item totals : ".$getTotalsResp->message;
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught paying item totals : ".$e);
        }

        return $resp;
    }

    private static function account_individualizeItemTotalArrayIntoItemIdArray(array $itemTotalArray) : Array
    {
        $expandedItemTotalArray = [];

        foreach($itemTotalArray as $itemTotal)
        {

            for($i = 0; $i < $itemTotal["price"]->smallCurrencyUnit; $i++)
            {
                $expandedItemTotalArray[] = $itemTotal["price"]->currencyItemId;
            }
        }

        return $expandedItemTotalArray;
    }
    
    private static function account_markCartAsPaid(vRecordId $cart, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unkown error in marking cart as paid", null);

        try
        {

            $groupIdResp = CartTransactionGroupController::getWhere([
                "ref_account_ctime"=>$cart->ctime, 
                "ref_account_crand"=>$cart->crand, 
                "void"=>0, 
                "completed"=>0, 
                "payed"=>0
            ], $databaseName);

            if($groupIdResp->success)
            {
                $markResp = CartTransactionGroupController::markAssociatedTransactionAsPaid($groupIdResp->data[0], $databaseName);

                if($markResp->success)
                {
                    $resp->success = true;
                    $resp->message = "successfully marked cart as paid";
                }
                else
                {
                    $resp->message = "Error in marking cart transactions as paid : ".$markResp->message;
                }
            }
            else
            {
                $resp->message = "Error in getting group transaction id : ".$groupIdResp->message;
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught marking cart as paid : ".$e);
        }

        return $resp;
    }   

    private static function account_getAccountItemTotalsDefecitArray(vCart $cart, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unkown error in getting defecit array for account", null);

        try
        {
            $totalsArrayResp = CartController::account_getItemTotals($cart, $databaseName);

            if($totalsArrayResp->success)
            {
                $totalsItemIdArray = CartController::account_returnTotalsItemIdArray($totalsArrayResp->data);

                $lootOfTotalsArrayResp = LootController::getTotalsInLoot($totalsItemIdArray, $cart->accountId, $databaseName);

                if($lootOfTotalsArrayResp->success)
                {
                    $lootTotals = CartController::account_returnTotalsLootIdArray($lootOfTotalsArrayResp->data);

                    $defecitArray = CartController::account_calculateLootTotalsDefecit($lootTotals, $totalsItemIdArray);

                    $resp->success = true;
                    $resp->message = "Successfully calculated defecit array";
                    $resp->data = $defecitArray;
                }
                else
                {
                    $resp->message = "Error in getting loot from account of totals : ".$lootOfTotalsArrayResp->message;
                }
            }
            else
            {
                $resp->message = "Error in getting totals for cart to calculate defecit array for account : ".$totalsArrayResp->message;
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while trying to get totals defecit array for account : ".$e);
        }

        return $resp;
    }

    private static function account_calculateLootTotalsDefecit(array $lootOfTotalsArray, array $totalsArray) : Array
    {
        $sortFunction = function($firstPrice, $secondPrice) { return CartController::account_alphabetizePriceArrayObjects($firstPrice, $secondPrice); };

        usort($lootOfTotalsArray, $sortFunction);
        usort($totalsArray, $sortFunction);

        $lootTotalsDefecitArray = array_map(
            function($totalAmount, $lootAmount)
            { return max(($totalAmount["Amount"] - $lootAmount["Amount"]), 0); }, 
            $totalsArray, $lootOfTotalsArray);

        return $lootTotalsDefecitArray;  
    }

    private static function account_alphabetizePriceArrayObjects($firstPriceArrayItem, $secondPriceArrayItem) : float
    {
        return strcmp($firstPriceArrayItem[0], $secondPriceArrayItem[0]);
    }

    private static function account_returnTotalsLootIdArray(mysqli_result $totalsArray) : Array
    {
        $lootIdArray = [];

        while($total = $totalsArray->fetch_assoc())
        {

            $lootIdArray[] = ["Id"=>$total["Id"], "Amount"=>$total["Amount"]];
        }

        return $lootIdArray;
    }

    private static function account_returnTotalsItemIdArray(array $totalsArray) : Array
    {
        $itemIdArray = [];

        foreach($totalsArray as $total)
        {
            $itemIdArray[] = ["Id"=>$total["price"]->currencyItemId->crand, "Amount"=>$total["price"]->smallCurrencyUnit];
        }

        return $itemIdArray;
    } 

    /*
    // Checks Cart Out
    // Marks store stock links as checked out and transactions as complete
    // Checks if cart is payed for before executing
    // Transfers store stocks from store to player's loot inventory
    */
    public static function account_checkout(vCart $cart, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unknown error in checking out cart", null);

        try
        {
            $paidResp = CartController::account_isPaidFor($cart, $databaseName);

            if($paidResp->success)
            {
                if($paidResp->data)
                {
                    $transferResp = CartController::account_transferCartToInventory($cart, $databaseName);

                    if($transferResp->success)
                    {
                        $markAsCompleteResp = CartController::account_markCartTransactionsAsComplete($cart, $databaseName);

                        if($markAsCompleteResp->success)
                        {
                            $markLinksAsCheckedOutResp = CartController::account_markLinksAsCheckedOut($cart, $databaseName);

                            if($markLinksAsCheckedOutResp->success)
                            {
                                $markCartAsCheckedOutResp = CartController::account_markCartAsCheckedOut($cart, $databaseName);

                                if($markCartAsCheckedOutResp->success)
                                {
                                    $logTransactionResp = TransactionLogController::logTransaction("Cart Checked Out", json_encode(["Cart"=>$cart]), $cart->accountId, $databaseName);

                                    if($logTransactionResp->success)
                                    {
                                        $resp->success = true;
                                        $resp->message = "Cart successfully checked out | Check out logged";   
                                    }
                                    else
                                    {
                                        $resp->message = "Failed to log cart account_checkout : ".$logTransactionResp->message;
                                    }
                                }
                                else
                                {
                                    $resp->message = "Error in marking cart as checked out : ".$markCartAsCheckedOutResp->message;
                                }
                            }
                            else
                            {
                                $resp->message = "Error in marking previous links as checked out : ".$markLinksAsCheckedOutResp->message;
                            }                      
                        }
                        else
                        {
                            $resp->message = "Error in marking cart transactions as complete : ".$markAsCompleteResp->message;
                        }
                    }
                    else
                    {
                        $resp->message = "Error in transferring items to cart : ".$transferResp->message;
                    }
                }
                else
                {
                    $resp->message = "Cart is not paid for and cannot check out : ".$paidResp->message;
                }
            }
            else
            {
                $resp->message = "failed to check if cart is paid for : ".$paidResp->message;
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while trying to check out cart : ".$e);
        }

        return $resp;
    }

    private static function account_markCartAsCheckedOut(vRecordId $cartId, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unkown error in marking cart as checked out", null);

        try
        {
            if($databaseName != null)
            {
                Database::changeDatabase($databaseName);
            }

            $query = "UPDATE ".CartController::getTableName()." SET checked_out = 1 WHERE ctime = ? AND crand = ?;";

            $params = [$cartId->ctime, $cartId->crand];

            $executeResp = CartController::executeQuery($query, $params);

            if($executeResp->success)
            {
                $resp->success = true;
                $resp->message = "Successfully marked cart as checked out";
            }
            else
            {
                $resp->message = "Error in executing query to mark cart as checked out : ".$executeResp->message;
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while marking cart as checked out : ".$e);
        }

        return $resp;
    }

    private static function account_markLinksAsCheckedOut(vRecordId $cart, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unkown error in marking links in cart as checked out", null);

        $query = "UPDATE ".StoreStockCartLinkController::getTableName()." 
        SET checked_out = 1 
        WHERE ref_account_ctime = ? AND ref_account_crand = ? 
        AND checked_out = 0 AND removed = 0;";

        $params = [$cart->ctime, $cart->crand];

        try
        {
            if($databaseName != null)
            {
                Database::changeDatabase($databaseName);
            }

            $result = Database::executeSqlQuery($query, $params);

            $resp->success = true;
            $resp->message = "Marked links in cart as checked out";
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while marking links in cart as checked out : ".$e);
        }

        return $resp;
    }

    private static function account_markCartTransactionsAsComplete(vRecordId $cartId, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unknown error in marking cart transaction group as complete", null);

        try
        {
            $groupTransactionIdResp = CartTransactionGroupController::getWhere([
                "ref_account_ctime"=>$cartId->ctime,
                "ref_account_crand"=>$cartId->crand,
                "payed"=>1,
                "completed"=>0,
                "void"=>0
            ], $databaseName);

            if($groupTransactionIdResp->success)
            {
                $groupTransactionId = $groupTransactionIdResp->data[0];

                $markResp = CartTransactionGroupController::markAssociatedTransactionAsComplete($groupTransactionId, $databaseName);

                if($markResp->success)
                {
                    $resp->success = true;
                    $resp->message = "Successfully marked cart transactions as complete";
                }
                else
                {
                    $resp->message = "Error in marking associated transactions as complete for cart : ".$markResp->message;
                }
            }
            else
            {
                $resp->message = "Error in getting group transaction id to mark cart group transaction as complete : ".$groupTransactionIdResp->message;
            }
        }
        catch(Exception $e)
        {
            $resp->message = "Exception caught marking cart transaction group as complete : ".$e;
        }

        return $resp;
    }

    private static function account_transferCartToInventory(vCart $cart, ?string $databaseName) : Response
    {
        $resp = new Response(false, "unkown error in transfering cart to inventory", null);

        try
        {
            $itemIdArrayResp = CartController::account_getCartItemIdArray($cart, $databaseName);

            if($itemIdArrayResp->success)
            {
                $itemIdArray = $itemIdArrayResp->data;

                $giveLootResp = LootController::giveLootArray($cart->accountId, $itemIdArray, $databaseName);

                if($giveLootResp->success)
                {
                    $resp->success = true;
                    $resp->message = "successfully transfered items to inventory";
                    $resp->data = $itemIdArray;
                }
                else
                {
                    $resp->message = "Error in giving item array from cart : ".$giveLootResp->message;
                }
            }
            else
            {
                $resp->message = "error in getting cart item id array : ".$itemIdArrayResp->message;
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while transfering cart to inventory : ".$e);
        }

        return $resp;
    }

    private static function account_getCartItemIdArray(vRecordId $cartId, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unkown error in getting cart item array", null);

        $itemIdArray = [];

        try
        {
            $getResp = CartController::account_getItemIdsOfTransactableStoreStocksInCart($cartId, $databaseName);

            if($getResp->success)
            {
                $linkedStoreStockIds = $getResp->data;

                foreach($linkedStoreStockIds as $linkId)
                {
                    $itemIdArray[] = ["Id"=>$linkId, "DateObtained"=>null];
                }

                $resp->success = true;
                $resp->message = "successfully returned item id array";
                $resp->data = $itemIdArray;
            }
            else
            {
                $resp->message = "error in getting all linked store stocks for cart : ".$getResp->message;
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while trying to get cart item id array : ".$e);
        }

        return $resp;   
    }

    private static function account_getItemIdsOfTransactableStoreStocksInCart(vRecordId $cartId, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unkown error in getting transactalbe store stocks in cart", null);

        $query = "SELECT  p.ref_item_ctime AS ctime, p.ref_item_crand AS crand
        FROM store_stock_account_link sslink
        LEFT JOIN store_stock ss ON ss.ctime = sslink.ref_store_stock_ctime AND ss.crand = sslink.ref_store_stock_crand
        LEFT JOIN product p ON p.ctime = ss.ref_product_ctime AND p.crand = ss.ref_product_crand
        WHERE sslink.ref_account_ctime = ? AND sslink.ref_account_crand = ? 
        AND sslink.removed = 0 AND sslink.checked_out = 0;";

        $params = [$cartId->ctime, $cartId->crand];

        try
        {
            if($databaseName != null)
            {
                Database::changeDatabase($databaseName);
            }
            
            $result = Database::executeSqlQuery($query, $params);

            if($result->num_rows > 0)
            {
                $storeStockIds = [];

                while($row = $result->fetch_assoc())
                {
                    $storeStockIds[] = new vRecordId($row["ctime"], $row["crand"]);
                }

                $resp->success = true;
                $resp->message = "Returned Transactable Store Stock Found In Cart";
                $resp->data = $storeStockIds;
            }
            else
            {
                $resp->success = true;
                $resp->message = "No Transactable Store Stocks Found In Cart";
                $resp->data = [];
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while getting transactable store stocks in cart : ".$e);
        }

        return $resp;
    }

    private static function account_isPaidFor(vRecordId $cartId, ?string $databaseName = null) : Response
    {
        $resp = new Response(false, "Unkown error in checking if cart is paid for", null);

        $tableName = CartTransactionGroupController::getTableName();

        $query = "SELECT payed FROM ".$tableName." WHERE ref_account_ctime = ? AND ref_account_crand = ?;";

        $params = [$cartId->ctime, $cartId->crand];

        try
        {
            if($databaseName != null)
            {
                Database::changeDatabase($databaseName);
            }

            $executeResp = CartController::executeQuery($query, $params);

            if($executeResp->success)
            {
                $resp->success = true;

                $result = $executeResp->data;
                if($result->num_rows > 0)
                {
                    if($result->fetch_assoc()["payed"])
                    {
                        $resp->message = "Cart is paid for";
                        $resp->data = true;
                    }
                    else
                    {
                        $resp->message = "Cart is not paid for";
                    }
                }
                else
                {
                    $resp->message = "Could not find matching cart for provided id : ".$executeResp->message;
                }
            }
            else
            {
                $resp->message = "Failed to execute query to check if cart is paid for : ".$executeResp->message;
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while checking if cart is paid for : ".$e);
        }

        return $resp;
    }
 
    /*
    // Methods to add a store stock to a cart
    */
    public static function addStoreStock(vStoreStock $stock, vRecordId $cart) : Response
    {
        $resp = new Response(false, "Unkown error occured in attempting to add stock to cart", null);

        try
        {
            $isStockAvailableResp = StoreStockController::isStockAvailable($stock);

            if($isStockAvailableResp->success)
            {
                if($isStockAvailableResp->data)
                {
                    $linkResp = CartController::linkStoreStock($stock, $cart);

                    if($linkResp->success)
                    {
                        $resp->success = true;
                        $resp->message = "Successfully linked stock to cart";
                        $resp->data = $linkResp->data;
                    }
                    else
                    {
                        $resp->message = "Failed to link store stock to cart with message : ".$linkResp->message;
                    }
                }
                else
                {
                    $resp->success = true;
                    $resp->message = "Stock is not available to add to cart : ".$isStockAvailableResp->message;
                }
            }
            else
            {
                $resp->message = "Failed to check if stock is available to add to cart with message : ".$isStockAvailableResp->message;
            }
        }
        catch(Exception $e)
        {
            $resp->message = "Exception caught while adding stock to cart : ".$e;
        }

        return $resp;
    }

    private static function linkStoreStock(vStoreStock $stock, vRecordId $cart) : Response
    {
        $resp = new Response(false, "Unkown error in linking store stock to cart", null);

        try
        {
            $storeStockCartLink = new StoreStockCartLink($stock->price, false, false, $stock, $cart, $stock->currencyItemId, null, null);

            $linkInsertResp = StoreStockCartLinkController::insert($storeStockCartLink);

            if($linkInsertResp->success)
            {
                $resp->success = true;
                $resp->message = "successfully linked stock to cart";
            }
            else
            {
                $resp->message =  "Failed to insert link record with message : ".$linkInsertResp->message;
            }
        }
        catch(Exception $e)
        {
            $resp->message = "Exception caught while attempting to link store stock to cart : ".$e;
        }

        return $resp;
    }

}    
?>