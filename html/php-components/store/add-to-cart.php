<?php

declare(strict_types=1);

require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

header('Content-Type: application/json');

use \Kickback\Backend\Views\vRecordId;

use \Kickback\Backend\Controllers\CartController;


use \Kickback\Backend\Models\Response;

$resp = new Response(false, "message", null);

if(isset($_POST["productCtime"]))
{
    if(isset($_POST["productCrand"]))
    {
        if(isset($_POST["cartCtime"]))
        {
            if(isset($_POST["cartCrand"]))
            {
                $productCtime = $_POST["productCtime"];
                $productCrand = (int)$_POST["productCrand"];

                $cartCtime = $_POST["cartCtime"];
                $cartCrand = (int)$_POST["cartCrand"];

                $product = new vRecordId($productCtime, $productCrand);
                $cart = new vRecordId($cartCtime, $cartCrand);

                $addResp = CartController::tryAddProductToCart($product, $cart);

                if($addResp->success)
                {
                    if($addResp->data)
                    {
                        $resp->success = true;
                        $resp->message = "No more stock of this Product is available";
                    }
                    else
                    {
                        $resp->message = "No more stock of this Product is available";
                    }    
                }
                else
                {
                    $resp->message = "An error was encountered when adding product to cart";
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
        $resp->message = "productCrand not set";
    }
}
else
{
    $resp->message = "productCtime not set";
}

echo json_encode($resp);

?>