<?php
/**
 * Creates a Stripe Checkout Session on a connected account (Direct Charge)
 * with an application fee.
 *
 * Request body (JSON):
 *   {
 *     "account_id": "acct_...",      // Connected account ID
 *     "product_name": "Sword of Dawn",
 *     "unit_amount": 1999,            // cents
 *     "currency": "USD",
 *     "quantity": 1,
 *     "application_fee_amount": 123   // cents (platform fee)
 *   }
 */

require_once(($_SERVER['DOCUMENT_ROOT'] ?: __DIR__ . "/../../../..") . "/Kickback/init.php");

use Kickback\Services\StripeService;
use Kickback\Services\Session;

header('Content-Type: application/json');

if (!Session::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    StripeService::initialize();

    $input = json_decode(file_get_contents('php://input'), true) ?: [];

    $accountId  = $input['account_id'] ?? '';
    $name       = trim((string)($input['product_name'] ?? ''));
    $amount     = (int)($input['unit_amount'] ?? 0);
    $currency   = strtoupper((string)($input['currency'] ?? 'USD'));
    $quantity   = max(1, (int)($input['quantity'] ?? 1));
    $appFee     = (int)($input['application_fee_amount'] ?? 0);

    if ($accountId === '' || $name === '' || $amount <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing or invalid fields (account_id, product_name, unit_amount).']);
        exit;
    }

    // Root URL for redirects (replace with your public domain in production)
    $rootUrl = (isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'https') . '://' . $_SERVER['HTTP_HOST'];

    // Create hosted Checkout Session as a direct charge on the connected account
    $session = \Stripe\Checkout\Session::create([
        'mode' => 'payment',
        'line_items' => [[
            'quantity' => $quantity,
            'price_data' => [
                'currency' => $currency,
                'unit_amount' => $amount,
                'product_data' => [
                    'name' => $name,
                ],
            ],
        ]],
        'payment_intent_data' => [
            'application_fee_amount' => $appFee, // platform monetization
        ],
        'success_url' => $rootUrl . '/success?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url'  => $rootUrl . '/cancelled',
    ], [
        // Request is executed on the connected account
        'stripe_account' => $accountId,
    ]);

    echo json_encode([
        'success' => true,
        'data' => [
            'id' => $session->id,
            'url' => $session->url,
        ],
    ]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create checkout session: ' . $e->getMessage(),
    ]);
}

