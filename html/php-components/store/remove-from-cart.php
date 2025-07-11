<?php

declare(strict_types=1);

require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

use \Kickback\Backend\Views\vRecordId;

use \Kickback\Backend\Controllers\CartController;

if(isset($_POST["storeStockCartLinkCtime"]))
{
    if(isset($_POST["storeStockCartLinkCrand"]))
    {
        $storeStockCartLinkCtime = $_POST["storeStockCartLinkCtime"];
        $storeStockCartLinkCrand = (int)$_POST["storeStockCartLinkCrand"];

        $storeStockCartLinkId = new vRecordId($storeStockCartLinkCtime, $storeStockCartLinkCrand);

        $resp = CartController::removeProductFromCart($storeStockCartLinkId);

        if($resp->success)
        {
            echo json_encode(['success' => true, 'message' => "Product removed from cart"]);
        }
        else
        {
            echo json_encode(['success' => false, 'message' => "Failed to remove product from cart"]);
        }
    }
    else
    {
        echo json_encode(['success' => false, 'message' => "storeStockCartLink crand not set"]);
    }
}
else
{
    echo json_encode(['success' => false, 'message' => "storeStockCartLink ctime not set"]);
}



?>