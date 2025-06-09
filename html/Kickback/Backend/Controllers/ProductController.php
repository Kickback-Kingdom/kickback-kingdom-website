<?php

declare(strict_types=1);

namespace Kickback\Backend\Controllers;
use Kickback\Services\Database;

use Exception;

use \Kickback\Backend\Models\Response;
use \Kickback\Backend\Models\Product;
use \Kickback\Backend\Models\ForeignRecordId;
use \Kickback\Backend\Models\CurrencyCode;
use \Kickback\Backend\Models\Item;

use \Kickback\Backend\Views\vRecordId;
use \Kickback\Backend\Views\vProduct;
use \Kickback\Backend\Views\vPrice;

class ProductController extends DatabaseController
{

    protected static ?ProductController $instance_ = null;

    protected array $batchInsertionParams;

    private function __construct()
    {
        $this->batchInsertionParams = [];
    }

    public static function instance()
    {
        if(is_null(static::$instance_))
        {
            static::$instance_ = new static();
        }

        return static::$instance_;
    }


    protected function allViewColumns() : string
    {
        return '
        ctime, 
        crand,
        name, 
        locator, 
        ref_currency_item_name, 
        price, 
        description,
        ref_item_ctime,
        ref_item_crand,
        ref_currency_item_crand, 
        ref_currency_item_ctime, 
        ref_small_image_path, 
        ref_large_image_path,
        ref_store_ctime, 
        ref_store_crand
        ';
    }

    protected function allTableColumns() : string
    {
        return '
        ctime, 
        crand, 
        name, 
        description,
        price, 
        locator, 
        ref_item_ctime,
        ref_item_crand,
        ref_currency_item_ctime, 
        ref_currency_item_crand, 
        ref_store_ctime,
        ref_store_crand
        ';
    }

    protected function tableName() : string
    {
        return 'product';
    }

    protected function rowToView(array $row) : vProduct
    {
        return ProductController::rowToVProduct($row);
    }

    protected function valuesToInsert(object $product) : array
    {
        $currencyItemCtime = null;
        $currencyItemCrand = null;

        if(is_null($product->currencyItem))
        {  
            $currencyItemCtime = '';
            $currencyItemCrand = -1;
        }
        else
        {
            $currencyItemCtime = $product->currencyItem->ctime;
            $currencyItemCrand = $product->currencyItem->crand;
        }

        return [
            $product->ctime, 
            $product->crand,     
            $product->name,  
            $product->description,
            $product->price->smallCurrencyUnit, 
            $product->locator, 
            $product->itemId->ctime,
            $product->itemId->crand,
            $currencyItemCtime,
            $currencyItemCrand,
            $product->storeId->ctime,
            $product->storeId->crand
        ];
    }

    public static function rowToVProduct(array $row) : vProduct
    {
        $price = new vPrice($row["price"]);

        $storeId = new vRecordId($row["ref_store_ctime"], $row["ref_store_crand"]);
        $currencyItem = null;

        if(array_key_exists("ref_currency_item_ctime", $row) && array_key_exists("ref_currency_item_crand", $row))
        {
            if(!is_null($row["ref_currency_item_crand"]))
            {
                $currencyItem = new vRecordId(/*$row["ref_currency_item_ctime"]*/'', $row["ref_currency_item_crand"]);
            }
        }
        

        $product = new vProduct(
            $row["ctime"], 
            $row["crand"], 
            $row["name"], 
            $row["locator"],
            $row["ref_currency_item_name"],
            $price, 
            $row["description"], 
            $currencyItem,
            $row["ref_small_image_path"],
            $row["ref_large_image_path"],
            $storeId     
        );

        return $product;
    } 
}

?>