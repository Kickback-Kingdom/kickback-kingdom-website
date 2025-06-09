<?php

declare(strict_types=1);

require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

use \Kickback\Backend\Config\ServiceCredentials;

use \Stripe\StripeClient;

$stripe = new StripeClient(ServiceCredentials::get("stripe_private_key"));

$intent = $stripe->paymentIntents->create([
  'amount' => 1099,
  'currency' => 'usd',
  'automatic_payment_methods' => ['enabled' => true],
]);

header('Content-Type: application/json');
echo json_encode(['client_secret'=>$intent->client_secret]);

?>