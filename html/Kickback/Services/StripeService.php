<?php
declare(strict_types=1);

namespace Kickback\Services;

use Kickback\Backend\Config\ServiceCredentials;

/**
 * Stripe service implementation.
 * - Sets API key and pins API version
 * - Exposes a StripeClient for advanced usages
 * - Provides publishable key and minimal currency helpers
 */
class StripeService
{
    /** Latest Stripe API version (requested). */
    private const STRIPE_API_VERSION = '2025-07-30.basil';

    private const DEFAULT_CURRENCY = 'USD';
    /** @var array<string> */
    private const SUPPORTED_CURRENCIES = ['USD'];

    private static bool $initialized = false;
    /** @var null|\Stripe\StripeClient */
    private static ?\Stripe\StripeClient $client = null;

    /**
     * Initialize Stripe once per request lifecycle.
     */
    public static function initialize(): void
    {
        if (self::$initialized) {
            return;
        }

        // Ensure SDK is available
        if (!class_exists('Stripe\\Stripe')) {
            throw new \Exception("Stripe PHP SDK not found. Install with: composer require stripe/stripe-php");
        }

        /** @var string|null $secretKey */
        $secretKey = ServiceCredentials::get('stripe_secret_key');
        if (empty($secretKey)) {
            throw new \Exception("Stripe secret key not configured. Set 'stripe_secret_key' in credentials.ini (e.g., sk_test_...) ");
        }

        // Configure global client
        \Stripe\Stripe::setApiKey($secretKey);
        \Stripe\Stripe::setApiVersion(self::STRIPE_API_VERSION);

        // Optional: instantiate a reusable client with version pin
        self::$client = new \Stripe\StripeClient([
            'api_key' => $secretKey,
            'stripe_version' => self::STRIPE_API_VERSION,
        ]);

        self::$initialized = true;
    }

    /** @return \Stripe\StripeClient */
    public static function getClient(): \Stripe\StripeClient
    {
        if (!self::$initialized) {
            self::initialize();
        }
        \assert(self::$client instanceof \Stripe\StripeClient);
        return self::$client;
    }

    /** Publishable key for client-side (Stripe.js). */
    public static function getPublishableKey(): string
    {
        /** @var string|null $pk */
        $pk = ServiceCredentials::get('stripe_publishable_key');
        if (empty($pk)) {
            throw new \Exception("Stripe publishable key not configured. Set 'stripe_publishable_key' in credentials.ini (e.g., pk_test_...) ");
        }
        return $pk;
    }

    /** Quick readiness probe. */
    public static function isConfigured(): bool
    {
        return !empty(ServiceCredentials::get('stripe_secret_key'))
            && !empty(ServiceCredentials::get('stripe_publishable_key'))
            && class_exists('Stripe\\Stripe');
    }

    public static function getDefaultCurrency(): string
    {
        return self::DEFAULT_CURRENCY;
    }

    /** @return array<string> */
    public static function getSupportedCurrencies(): array
    {
        return self::SUPPORTED_CURRENCIES;
    }

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
