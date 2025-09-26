<?php
declare(strict_types=1);

namespace Kickback\Services;

use Kickback\Common\Primitives\Arr;
use Kickback\Common\Primitives\Str;
use Kickback\Common\Exceptions\Reporting\Report;
use Kickback\Common\Exceptions\ConfigEntryMissingException;
use Kickback\Common\Exceptions\ExtensionMissingException;
use Kickback\Common\Exceptions\KickbackThrowable;
use Kickback\Common\Exceptions\ValidationException;

use Kickback\Backend\Config\ServiceCredentials;
use Kickback\Backend\Models\Response;

use Kickback\Services\ApiV2\Endpoint;

// TODO: We might want to encode money amounts as strings instead of integers,
// because even 64-bit integers might not have enough range to express our
// needs. (Especially with currencies, like some crypto things, that require
// really large numbers to express the same value as something like the USD.)
// Presumably there will be some non-string internal rep that we will use
// for such numbers, but as of this writing, I'm not exactly sure what that is.
// So where exactly we do this conversion, and to what, is TBD.
// (Ideally, such conversions occur "as early as possible",
// but there may be dependency management concerns.)

/**
* Stripe service implementation.
* - Sets API key and pins API version
* - Exposes a StripeClient for advanced usages
* - Provides publishable key and minimal currency helpers
*
* @phpstan-type  create_checkout_kickback_request_a  array{
*       kk_api_ver              : string,
*       account_id              : string,
*       product_name            : string,
*       unit_amount             : int,
*       currency                : string,
*       quantity                : int,
*       application_fee_amount  : int
*   }
*
*/
class StripeService
{
    use \Kickback\Common\Traits\StaticClassTrait;

    /** Latest Stripe API version (requested). */
    private const STRIPE_API_VERSION = '2025-07-30.basil';

    // NOTE: We don't have a "default currency" because, objectively,
    //     there is no such thing. Clients should always specify their
    //     intended currency exactly, otherwise disastrous
    //     misunderstandings (with money!) might occur.
    //     (Ex: "I thought you were charging me in Reais/Rénmínbì/INR/etc, but you charged in USD!")
    //     So basically we can avoid an entire class
    //     of errors by just not defining this thing.

    // (Not sure if this is needed: if we need fast integer-indexed access, then yes.
    // But if we need "is this in the list?", then the _SET version is better for that.
    // A ClassOfConstantIntegers might be best because it provides both, and these
    // are very definition-oriented/declarative.)
    // /** @var array<string> */
    // private const SUPPORTED_CURRENCIES_LIST = ['USD'];

    /** @var array<string,string> */
    private const SUPPORTED_CURRENCIES_SET  = ['USD' => 'USD'];

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
            throw new ExtensionMissingException(
                'Stripe PHP SDK not found. Install with: `composer require stripe/stripe-php`');
        }

        /** @var string|null $secretKey */
        $secretKey = ServiceCredentials::get_stripe_secret_key();
        if (!isset($secretKey) || 0 === \strlen($secretKey)) {
            throw new ConfigEntryMissingException(
                "Stripe secret key not configured.\n".
                "Set 'stripe_secret_key' in one of these files:\n".
                "    ".\implode("    ",ServiceCredentials::SERVICE_CREDENTIAL_SOURCES));
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
    public static function client(): \Stripe\StripeClient
    {
        if (!self::$initialized) {
            self::initialize();
        }
        \assert(self::$client instanceof \Stripe\StripeClient);
        return self::$client;
    }

    /** Publishable key for client-side (Stripe.js). */
    public static function publishableKey(): string
    {
        /** @var string|null $pk */
        $pk = ServiceCredentials::get_stripe_publishable_key();
        if (!isset($pk) || 0 === \strlen($pk)) {
            throw new ConfigEntryMissingException(
                "Stripe publishable key not configured.\n".
                "Set 'stripe_publishable_key' in one of these files:\n".
                "    ".\implode("    ",ServiceCredentials::SERVICE_CREDENTIAL_SOURCES));
        }
        return $pk;
    }

    /** Quick readiness probe. */
    public static function redirect_uri(): string
    {
        /** @var string|null $uri */
        $uri = ServiceCredentials::get_stripe_redirect_uri();
        if (!isset($uri) || 0 === \strlen($uri)) {
            throw new ConfigEntryMissingException(
                "Stripe redirect URI not configured.\n".
                "Set 'stripe_redirect_uri' in one of these files:\n".
                "    ".\implode("    ",ServiceCredentials::SERVICE_CREDENTIAL_SOURCES));
        }
        return $uri;
    }

    /** Quick readiness probe. */
    public static function configured(): bool
    {
        return !Str::empty(ServiceCredentials::get_stripe_secret_key())
            && !Str::empty(ServiceCredentials::get_stripe_publishable_key())
            && \class_exists('Stripe\\Stripe');
    }

    // If it's a constant array, just access it directly.
    // TODO: It might make the most sense to create a class that defines
    //   the currencies using the ClassOfConstantIntegersTrait, which
    //   provides associative lookups in addition to integer lookups
    //   and constant-value-access. The only catch is that I'm not sure
    //   if any of the strings will ever be not-valid-PHP-identifiers,
    //   in which case it'd require implementing some new features
    //   for ClassOfConstants-type trait-y things. (e.g. Provide some
    //   syntax for attribute-based definition classification and a way
    //   to define aliases that allow things to be accessed by non-identifier
    //   aliases, which would also naturally lend itself to providing
    //   identifier-based aliases via commutativity, which is nice for
    //   explicit call-outs in general, though maybe not super important
    //   right now. ANYHOW. It's extra work that I don't wanna do right now.
    //   Maybe. But also good precedent. ¯\_(ツ)_/¯)
    //
    // /** @return array<string> */
    // public static function supportedCurrencies(): array
    // {
    //     return self::SUPPORTED_CURRENCIES;
    // }
    //
    // public static function currencySupported(string $currency): bool
    // {
    //     $upper = \strtoupper($currency);
    //     foreach (self::SUPPORTED_CURRENCIES as $supported) {
    //         if ($upper === \strtoupper($supported)) {
    //             return true;
    //         }
    //     }
    //     return false;
    // }


    private const CHECKOUT_REQUEST_FIELDS = [
        'kk_api_ver'   => '',
        'account_id'   => '',
        'product_name' => '',
        'unit_amount'  => 0,
        'currency'     => '',
        'quantity'     => 0,
        'application_fee_amount' => 0
    ];

    /**
    * @param  array<mixed>|false                          $request_contents
    * @param  (string|class-string<KickbackThrowable>)&   $report
    *
    * @phpstan-assert-if-true   =create_checkout_kickback_request_a  $request_contents
    * @phpstan-assert-if-false  =Report                              $report
    */
    public static function validate_create_checkout_request_array(
        array|false    $request_contents,
        Report|string  &$report
    ) : bool
    {
        if(!Report::enforce($report,
            ($request_contents !== false),
            'HTTP Request contents missing or empty.')) {
            return false;
        }
        // Guaranteed by call to `Report::enforce` above.
        assert($request_contents !== false);

        Arr::validate_key_exists($request_contents, 'kk_api_ver',   $report);
        Arr::validate_key_exists($request_contents, 'account_id',   $report);
        Arr::validate_key_exists($request_contents, 'product_name', $report);
        Arr::validate_key_exists($request_contents, 'unit_amount',  $report);
        Arr::validate_key_exists($request_contents, 'currency',     $report);
        Arr::validate_key_exists($request_contents, 'quantity',     $report);
        Arr::validate_key_exists($request_contents, 'application_fee_amount', $report);

        Arr::validate_is_string($request_contents, 'kk_api_ver',   $report);
        Arr::validate_is_string($request_contents, 'account_id',   $report);
        Arr::validate_is_string($request_contents, 'product_name', $report);
        Arr::validate_is_int   ($request_contents, 'unit_amount',  $report);
        Arr::validate_is_string($request_contents, 'currency',     $report);
        Arr::validate_is_int   ($request_contents, 'quantity',     $report);
        Arr::validate_is_int   ($request_contents, 'application_fee_amount', $report);

        Arr::validate_is_nonblank($request_contents, 'account_id',   $report);
        Arr::validate_is_nonblank($request_contents, 'product_name', $report);
        Arr::validate_is_nonblank($request_contents, 'currency',     $report);
        Arr::validate_is_int_and_pos_nonzero($request_contents, 'unit_amount',  $report);
        Arr::validate_is_int_and_pos_nonzero($request_contents, 'quantity',     $report);
        Arr::validate_is_int_and_pos_nonzero($request_contents, 'application_fee_amount', $report);

        if (\array_key_exists('currency',$request_contents)
        &&  is_string($request_contents['currency']))
        {
            $currency = \strtoupper($request_contents['currency']);
            Report::enforce($report,
                \array_key_exists($currency, self::SUPPORTED_CURRENCIES_SET),
                "The currency '$currency' is not currently supported.");
        }

        // TODO: Other sanity checks:
        // * Is the kk_api_ver a valid semver? (once we have API versions and semver validation)
        // * Is the account_id syntax valid?
        // * Is the product_name syntax valid? (Is there even a syntax for this?)
        // * Is the currency on the list of possible currencies?
        // * Limits on quantities or currency?

        // If the client sent additional things, then they might expect
        // us to process that data. We do not, however, know what that
        // data IS, so something has definitely gone wrong (if we find
        // unexpected fields).
        foreach($request_contents as $field => $value) {
            if (\array_key_exists($field, self::CHECKOUT_REQUEST_FIELDS)) {
                continue;
            }
            $valstr = is_string($value) ?:
                (is_int($value) ? \strval($value) : \json_encode($value));
            Report::enforce($report, false, "Unexpected field: $field => '$valstr'");
        }

        // Return false if we had validation failures.
        return (!($report instanceof Report) || 0 === $report->count());
    }

    /**
    * Creates a Stripe Checkout Session on a connected account (Direct Charge)
    * with an application fee.
    *
    * Request body (JSON):
    *   {
    *     "kk_api_ver": "1.0.0",
    *     "account_id": "acct_...",      // Connected account ID
    *     "product_name": "Sword of Dawn",
    *     "unit_amount": 1999,            // cents
    *     "currency": "USD",
    *     "quantity": 1,
    *     "application_fee_amount": 123   // cents (platform fee)
    *   }
    *
    * @param      string        $request_contents_json
    * @param      ?Response     $response
    * @param-out  Response      $response
    * @return int<0,max>
    */
    public static function api_create_checkout(
        string        $request_contents_json,
        ?Response     &$response) : int
    {
        Session::ensureSessionStarted();

        if (!Session::isLoggedIn()) {
            $endpoint_name = Endpoint::calculate_endpoint_resource_name();
            $response = new Response(false, "$endpoint_name: Authentication required");
            return 401;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $endpoint_name = Endpoint::calculate_endpoint_resource_name();
            $response = new Response(false, "$endpoint_name: Method not allowed");
            return 405;
        }

        StripeService::initialize();

        $request_contents =
            Endpoint::decode_json_record($request_contents_json);

        $report = ValidationException::class;
        if(!self::validate_create_checkout_request_array($request_contents, $report))
        {
            $endpoint_name = Endpoint::calculate_endpoint_resource_name();
            $n_errors = $report->count();
            $exc = $report->generate_exception(null,
                "Validation of '$endpoint_name' request failed.\n$n_errors error(s).");
            $exc->code(400); // Bad Request
            throw $exc;
        }

        $stripe_account_id = $request_contents['account_id'];
        $product_name      = \trim($request_contents['product_name']);
        $amount            = $request_contents['unit_amount'];
        $currency          = \strtoupper($request_contents['currency']);
        $quantity          = $request_contents['quantity'];
        $appFee            = $request_contents['application_fee_amount'];

        // Root URL for redirects
        $root_url = self::redirect_uri();

        // Create hosted Checkout Session as a direct charge on the connected account
        $session = self::send_stripe_checkout_request(
            $root_url, [
            // TODO: Somehow ensure that the Stripe API's PHPDoc/PHPStan
            //   annotations are checking the shape of this array.
            // (As of this writing, my version of the Stripe client doesn't
            // have thorough PHPDoc annotations for this function, but
            // newer versions SHOULD have very precise annotations,
            // as evidenced by Stripe's code found in github)
            'mode' => 'payment',
            'line_items' => [[
                'quantity' => $quantity,
                'price_data' => [
                    'currency' => $currency,
                    'unit_amount' => $amount,
                    'product_data' => [
                        'name' => $product_name,
                    ],
                ],
            ]],
            'payment_intent_data' => [
                'application_fee_amount' => $appFee, // platform monetization
            ],
            // TODO: Implement these endpoints. They should probably be in the API tree, not at root.
            'success_url' => $root_url . '/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => $root_url . '/cancelled',
        ], [
            // Request is executed on the connected account
            'stripe_account' => $stripe_account_id,
        ]);

        $response = new Response(true, '', [
            'id' => $session->id,
            'url' => $session->url
        ]);
        return 0; // Success / don't change HTTP response code.
    }

    /**
    * @param  null|array<mixed>          $params
    * @param  null|string|array<mixed>   $options
    */
    private static function send_stripe_checkout_request(
        string             $root_url,
        null|array         $params,
        null|string|array  $options
    ) : \Stripe\ApiResource
    {
        // Use mock call if on localhost, otherwise query Stripe (beta/prod).
        $fqdn = Str::fqdn_from_url($root_url);
        if ( $fqdn === 'localhost' || $fqdn === '127.0.0.2' ) {
            // Mock/testing action
            return self::mock_stripe_checkout_create($params, $options);
        } else {
            // Live action
            return \Stripe\Checkout\Session::create($params, $options);
        }
    }

    /**
    * Function that simply prints its arguments and returns a dummy Stripe Session object.
    *
    * Useful for testing our side of the `api_create_checkout` endpoint.
    *
    * @param  null|array<mixed>          $params
    * @param  null|string|array<mixed>   $options
    */
    private static function mock_stripe_checkout_create(
        null|array         $params = null,
        null|array|string  $options = null
    ) : \Stripe\ApiResource
    {
        ob_start();
        echo "mock_stripe_checkout_create(\n";
        var_dump($params);
        var_dump($options);
        echo ")\n";
        \error_log(ob_get_clean());

        $session = new \Stripe\Checkout\Session();

        // These seem hidden behind a `__set` function in the ApiResource class.
        // It is an error to set them in a simple way, as below.
        // I do not know how Stripe itself would set these,
        //   and have not spent much time investigating it.
        //$session->id  = "TestStripeAccountId0";
        //$session->url = 'http://locahost';

        return $session;
    }

    private static function unittest_mock_stripe_checkout_create() : void
    {
        echo("  ".__FUNCTION__."()\n");

        // TODO: Better mocking somehow?
        assert(self::mock_stripe_checkout_create(null,null) instanceof \Stripe\Checkout\Session);
    }

    public static function unittests() : void
    {
        $class_fqn = self::class;
        echo("Running `$class_fqn::unittests()`\n");

        self::unittest_mock_stripe_checkout_create();

        echo("  ... passed.\n\n");
    }
}
