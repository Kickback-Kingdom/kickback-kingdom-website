<?php
/**
 * Creates a Product and Price on a connected account using the Stripe-Account header.
 *
 * Request body (JSON):
 *   {
 *     "account_id": "acct_...",      // Connected account ID (demo: sent from client)
 *     "name": "Sword of Dawn",
 *     "description": "Legendary blade",
 *     "price": 1999,                  // amount in cents
 *     "currency": "USD"              // ISO currency
 *   }
 *
 * NOTE: In production, do NOT accept raw Stripe IDs from clients. Map your own
 * merchant ID to a Stripe account ID server-side instead.
 */

require_once(($_SERVER['DOCUMENT_ROOT'] ?: __DIR__ . "/../../../..") . "/Kickback/init.php");

use Kickback\Services\StripeService;
use Kickback\Services\Session;

header('Content-Type: application/json');

Session::ensureSessionStarted();

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
    $accountId   = $input['account_id']   ?? '';
    $name        = trim((string)($input['name'] ?? ''));
    $description = trim((string)($input['description'] ?? ''));
    $price       = (int)($input['price'] ?? 0);
    $currency    = strtoupper((string)($input['currency'] ?? 'USD'));

    if ($accountId === '' || $name === '' || $price <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing or invalid fields (account_id, name, price).']);
        exit;
    }

    // Create Product and default Price on the connected account
    $product = \Stripe\Product::create([
        'name' => $name,
        'description' => $description,
        'default_price_data' => [
            'unit_amount' => $price,
            'currency' => $currency,
        ],
    ], [
        // Stripe-Account header; this routes the call to the connected account
        'stripe_account' => $accountId,
    ]);

    echo json_encode([
        'success' => true,
        'data' => $product,
    ]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create product: ' . $e->getMessage(),
    ]);
}

