<?php
declare(strict_types=1);

namespace Kickback\Services;

use Kickback\Backend\Config\ServiceCredentials;

/**
 * Centralized Stripe initialization for the whole app.
 * - Sets API key and API version
 * - Provides a simple guard to ensure the PHP SDK is installed
 * - Exposes helpers to read publishable key for front-end
 */
class StripeService
{
    /**
     * Latest Stripe API version requested by user for this sample.
     * If Stripe changes default API behavior, this pin keeps responses consistent.
     */
    private const STRIPE_API_VERSION = '2025-07-30.basil';

    private static bool $initialized = false;

    /**
     * Initialize Stripe once per request lifecycle.
     * Throws a helpful error if keys are missing or the SDK is not installed.
     */
    public static function initialize(): void
    {
        if (self::$initialized) {
            return;
        }

        // Ensure the Stripe PHP SDK is available
        if (!class_exists('Stripe\\Stripe')) {
            // Helpful error: SDK not installed
            throw new \Exception("Stripe PHP SDK not found. Install with: composer require stripe/stripe-php");
        }

        /** @var string|null $secretKey */
        $secretKey = ServiceCredentials::get('stripe_secret_key');
        if (empty($secretKey)) {
            // Placeholder guidance if key is missing
            throw new \Exception("Stripe secret key not configured. Set 'stripe_secret_key' in credentials.ini (e.g., sk_test_...) ");
        }

        // Set API key and API version globally
        \Stripe\Stripe::setApiKey($secretKey);
        \Stripe\Stripe::setApiVersion(self::STRIPE_API_VERSION);

        self::$initialized = true;
    }

    /**
     * Returns publishable key for front-end usage (Stripe.js).
     * Throws if not configured; caller should present a user-friendly error.
     */
    public static function getPublishableKey(): string
    {
        /** @var string|null $pk */
        $pk = ServiceCredentials::get('stripe_publishable_key');
        if (empty($pk)) {
            throw new \Exception("Stripe publishable key not configured. Set 'stripe_publishable_key' in credentials.ini (e.g., pk_test_...) ");
        }
        return $pk;
    }

    /**
     * Quick readiness probe so routes can short-circuit when misconfigured.
     */
    public static function isConfigured(): bool
    {
        return !empty(ServiceCredentials::get('stripe_secret_key'))
            && !empty(ServiceCredentials::get('stripe_publishable_key'))
            && class_exists('Stripe\\Stripe');
    }
}

<?php
declare(strict_types=1);

namespace Kickback\Services;

use Stripe\Stripe;
use Stripe\StripeClient;
use Kickback\Backend\Config\ServiceCredentials;

class StripeService
{
    private static ?StripeClient $client = null;
    private static bool $initialized = false;
    private const DEFAULT_CURRENCY = 'USD';
    /** @var array<string> */
    private const SUPPORTED_CURRENCIES = ['USD'];

    /**
     * Initialize Stripe with API key
     */
    public static function initialize(): void
    {
        if (self::$initialized) {
            return;
        }

        $secretKey = ServiceCredentials::get("stripe_secret_key");
        if (empty($secretKey)) {
            throw new \Exception("Stripe secret key not configured");
        }

        Stripe::setApiKey($secretKey);
        self::$client = new StripeClient($secretKey);
        self::$initialized = true;
    }

    /**
     * Get the Stripe client instance
     */
    public static function getClient(): StripeClient
    {
        if (!self::$initialized) {
            self::initialize();
        }
        return self::$client;
    }

    /**
     * Get the publishable key for frontend
     */
    public static function getPublishableKey(): string
    {
        $publishableKey = ServiceCredentials::get("stripe_publishable_key");
        if (empty($publishableKey)) {
            throw new \Exception("Stripe publishable key not configured");
        }
        return $publishableKey;
    }

    /**
     * Check if Stripe is configured
     */
    public static function isConfigured(): bool
    {
        $secretKey = ServiceCredentials::get("stripe_secret_key");
        $publishableKey = ServiceCredentials::get("stripe_publishable_key");
        return !empty($secretKey) && !empty($publishableKey);
    }

    /**
     * Get the default currency used by the site.
     */
    public static function getDefaultCurrency(): string
    {
        return self::DEFAULT_CURRENCY;
    }

    /**
     * Get the list of supported currencies.
     *
     * @return array<string>
     */
    public static function getSupportedCurrencies(): array
    {
        return self::SUPPORTED_CURRENCIES;
    }

    /**
     * Check if a given currency is supported (case-insensitive).
     */
    public static function isCurrencySupported(string $currency): bool
    {
        $upper = strtoupper($currency);
        foreach (self::SUPPORTED_CURRENCIES as $supported) {
            if ($upper === strtoupper($supported)) {
                return true;
            }
        }
        return false;
    }
}