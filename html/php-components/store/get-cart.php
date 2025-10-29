<?php

declare(strict_types = 1);

require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

use \Kickback\Backend\Views\vRecordId;

use \Kickback\Backend\Controllers\CartController;

use \Kickback\Backend\Models\Response;

$resp = new Response(false, "Unknown error in getting information for cart page");

if(isset($_POST["cartCtime"]))
{
  if(isset($_POST["cartCrand"]))
  {
    $cartId = new vRecordId($_POST["cartCtime"], (int)$_POST["cartCrand"]);

    try
    {
      $getCartResp = CartController::get($cartId);
      $cart = $getCartResp->data;

      if($getCartResp->success)
      {
        $itemsResp = CartController::getAllItemsInCart($cart);

        if($itemsResp->success)
        {
            $items = $itemsResp->data;
        }
        else
        {
            throw new Exception("Error in getting items by store : $itemsByStoreResp->message");
        }

        $totalsResp = CartController::getItemTotals($cart);



        if($totalsResp->success)
        {
            $totals = $totalsResp->data;
        }
        else
        {
            throw new Exception("Error in getting totals for account : $accountTotalsResp->message");
        }

        $cartHtml = CartController::renderCartItemsHtml($items);
        $totalsHtml = CartController::renderCartTotalsHtml($totals);

        $resp->success = true;
        $resp->message = "Returned information for cart page";
        $resp->data = $resp->data = [
        'cartHtml'   => $cartHtml,
        'totalsHtml' => $totalsHtml
        ];
      }
      else
      {
        $resp->message = "Error in getting cart : $getCartResp->message";
      }
    }
    catch(Exception $e)
    {
      $resp->message = "Exception caught while getting information for cart page : $e";
    }
  }
  else
  {
    $resp->message = "account crand not set in POST";
  }
}
else
{
  $resp->message = "account ctime not set in POST";
}

echo json_encode($resp);

?>
