<?php

declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use \Kickback\Backend\Config\ServiceCredentials; 

use \Stripe\StripeClient;
use \Stripe\PaymentIntent;

use \Kickback\Backend\Models\CurrencyCode;
use \Kickback\Backend\Models\StripePayment;
use \Kickback\Backend\Models\Response;

use \Kickback\Backend\Views\vPrice;
use \Kickback\Backend\Views\vStripePayment;

use \Kickback\Backend\Controllers\CurrencyConverter;

use Exception;

class StripeController
{
    public static ?StripeController $instance_ = null;

    public static function instance()
    {
        if(StripeController::$instance_ == null)
        {
            StripeController::$instance_ = new StripeController();
        }

        return StripeController::$instance_;
    }

    private static function secretKey()
    {
        return ServiceCredentials::get("stripe_private_key");
    }

    public static function publicKey()
    {
        return ServiceCredentials::get("stripe_public_key");
    }

    public static function didPaymentStatusSucceed(StripeClient $stripe, $paymentIntentId) : Response
    {
        $resp = new Response(false, "unkown error in checking if payment succeeded", null);

        try
        {
            $paymentIntent = $stripe->paymentIntents->retrieve($paymentIntentId);

            if ($paymentIntent->status === 'succeeded') 
            {
                $resp->data = true;
                $resp->message = "Payment Intent succeeded";
        
            } 
            else 
            {
                $resp->data = false;
                $resp->message = "Payment Intent did not succeed";
            }

            $resp->success = true;
            
        }
        catch(Exception $e)
        {
            $resp->message = "Exception caught while checking if payment intent succeeded : $e";
        }

        return $resp;
        
    }

    
    public static function createPaymentIntent(StripePayment $paymentIntent) : PaymentIntent
    {

        $stripe = new StripeClient(StripeController::secretKey());

        $paymentIntent = $stripe->paymentIntents->create([
            'amount' => $paymentIntent->amount->smallUnitValue,
            'currency' => 'usd',
        ]);

        return $paymentIntent;
    }

    public static function createTestPaymentIntent() : PaymentIntent
    {
        $instance = StripeController::instance();

        $amount = new vPrice(10000000);
        $payment = new StripePayment($amount->returnPriceIn(CurrencyCode::USD), CurrencyCode::USD);

        $intent = StripeController::createPaymentIntent($payment);

        return $intent;
    }

}

?>