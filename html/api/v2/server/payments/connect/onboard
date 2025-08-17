<?php
/**
 * Creates (or retrieves) a connected account and generates an Account Link for onboarding.
 *
 * This demo purposely avoids any database. In a real app, you should store
 * the mapping between your user and their connected account ID.
 *
 * Steps:
 * 1) Ensure the platform has Stripe keys configured
 * 2) Create connected account using the controller-only properties (no top-level type)
 * 3) Create an account link for onboarding and return URL to the client
 */

require_once(($_SERVER['DOCUMENT_ROOT'] ?: __DIR__ . "/../../../..") . "/Kickback/init.php");

use Kickback\Services\StripeService;
use Kickback\Services\Session;

header('Content-Type: application/json');

// Make sure PHP session is initialized so $_SESSION is populated
Session::ensureSessionStarted();

// Require login for platform users creating their own connected account
if (!Session::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

try {
    // Guard: Ensure Stripe is configured and SDK loaded
    StripeService::initialize();

    // Placeholder: In a real app, look up if this user already has a connected account ID.
    // For demo, we always create a new account (stateless).

    $account = \Stripe\Account::create([
        // IMPORTANT: Use only controller fields. Do NOT set top-level 'type'.
        'controller' => [
            'fees' => [ 'payer' => 'account' ],               // connected account pays fees
            'losses' => [ 'payments' => 'stripe' ],            // Stripe covers payment losses
            'stripe_dashboard' => [ 'type' => 'full' ],        // full dashboard access
        ],
        // Optional: you can set country or business type here if needed
        // 'country' => 'US',
    ]);

    // After account creation, we must send the user through onboarding
    // Use your deployed URL as refresh/return (placeholders included)
    $rootUrl = (isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'https') . '://' . $_SERVER['HTTP_HOST'];

    $accountLink = \Stripe\AccountLink::create([
        'account' => $account->id,
        'refresh_url' => $rootUrl . '/connect-onboarding-refresh', // TODO: replace with a real route that re-creates a new link
        'return_url'  => $rootUrl . '/connect-onboarding-complete', // TODO: landing after onboarding completes
        'type' => 'account_onboarding',
    ]);

    echo json_encode([
        'success' => true,
        'data' => [
            'account_id' => $account->id,
            'onboarding_url' => $accountLink->url,
        ],
    ]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to initiate onboarding: ' . $e->getMessage(),
    ]);
}

