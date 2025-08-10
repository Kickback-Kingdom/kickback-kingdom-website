<?php
/**
 * Retrieves the live status of a connected account directly from Stripe.
 *
 * This demo expects the connected account ID to be provided in the query string
 * as `?account_id=acct_...`. In production, do NOT expose raw Stripe account IDs
 * in URLs—use your own stable identifiers and map them to account IDs on the server.
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

$accountId = $_GET['account_id'] ?? '';
if ($accountId === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing account_id parameter']);
    exit;
}

try {
    StripeService::initialize();

    $acct = \Stripe\Account::retrieve($accountId);

    // For demo: expose capabilities and requirements to help the user see what is missing
    echo json_encode([
        'success' => true,
        'data' => [
            'id' => $acct->id,
            'charges_enabled' => $acct->charges_enabled,
            'payouts_enabled' => $acct->payouts_enabled,
            'requirements' => $acct->requirements, // contains currently_due, past_due, etc.
            'capabilities' => $acct->capabilities,
        ],
    ]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to retrieve account status: ' . $e->getMessage(),
    ]);
}

