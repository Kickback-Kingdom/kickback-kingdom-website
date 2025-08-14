<?php
/**
 * Lists products on a connected account using the Stripe-Account header.
 *
 * Query string:
 *   ?account_id=acct_...&limit=20
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

$accountId = $_GET['account_id'] ?? '';
if ($accountId === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing account_id parameter']);
    exit;
}

$limit = (int)($_GET['limit'] ?? 20);
if ($limit <= 0 || $limit > 100) { $limit = 20; }

try {
    StripeService::initialize();

    $products = \Stripe\Product::all([
        'limit' => $limit,
        'expand' => ['data.default_price'], // include price for storefront rendering
    ], [
        'stripe_account' => $accountId,
    ]);

    echo json_encode([
        'success' => true,
        'data' => $products,
    ]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to list products: ' . $e->getMessage(),
    ]);
}

