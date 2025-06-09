<?php

declare(strict_types = 1);

require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

use \Kickback\Backend\Views\vRecordId;

use \Kickback\Backend\Models\Response;

use \Kickback\Backend\Controllers\StoreController;
use \Kickback\Backend\Controllers\ProductController;

$resp = new Response(false, "unkown error in getting products for store", null);

if(isset($_POST["storeCtime"]))
{
    if(isset($_POST["storeCrand"]))
    {
        if(isset($_POST["cartCtime"]))
        {
            if(isset($_POST["cartCrand"]))
            {
                try
                {
                    $store = new vRecordId($_POST["storeCtime"], (int)$_POST["storeCrand"]);
                    $cart = new vRecordId($_POST["cartCtime"], (int)$_POST["cartCrand"]);

                    $productsResp = ProductController::getWhere(["ref_store_ctime"=>$store->ctime, "ref_store_crand"=>$store->crand]);

                    if($productsResp->success)
                    {
                        $products = $productsResp->data;

                        $html = StoreController::renderStoreProductsHtml($cart, $products);

                        $resp->success = true;
                        $resp->message = "Returned Products for Store";
                        $resp->data = $html;
                    }
                    else
                    {
                        $resp->message = "Error in getting store products";
                    }
                }
                catch(Exception $e)
                {
                    $resp->message = "Exception while getting store products";
                }    
            }
            else
            {
                $resp->message = "cartCrand not set";
            }
        }
        else
        {
            $resp->message = "cartCtime not set";
        }
    }
    else
    {
        $resp->message = "StoreCrand not set";
    }

}
else
{
    $resp->message = "StoreCtime not set";
}

echo json_encode($resp);

?>