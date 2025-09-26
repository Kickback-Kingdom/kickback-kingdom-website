<?php
declare(strict_types=1);

namespace Kickback\Services;

use Kickback\Backend\Config\ServiceCredentials;

use Kickback\Common\Primitives\Arr;
use Kickback\Common\Primitives\Str;
use Kickback\Common\Exceptions\Reporting\Report;
use Kickback\Common\Exceptions\ConfigEntryMissingException;
use Kickback\Common\Exceptions\ExtensionMissingException;
use Kickback\Common\Exceptions\JSON_DecodeException;
use Kickback\Common\Exceptions\KickbackThrowable;
use Kickback\Common\Exceptions\UnexpectedEmptyException;
use Kickback\Common\Exceptions\UnexpectedTypeException;
use Kickback\Common\Exceptions\ValidationException;

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

    // TODO: DElete below when git commit or things are workign
    //
        // As of 2025-08-28 with PHPStan v1.11.1 and PHP 8.2, it reports
        // "Variable $record in isset() always exists and is always null."
        // "    isset.variable"
        // ... which is a lie.
        // \json_decode returns `mixed`, which can be `null`, and the PHP
        // documentation specifically mentions which conditions will cause
        // this function to return `null`. So we must handle it!
        // (And it's not ALWAYS `null` either, because then the function
        // would be completely useless. And the documentation also talks
        // about non-null values of course. So PHPStan is misinformed somehow.)
        //
        // phpstan-ignore isset.variable

        // It thinks this code is unreachable because it (mistakenly) thinks
        // that the return value of \json_decode is always null.
        // phpstan-ignore deadCode.unreachable

    /**
    * Like \json_decode, but throws exceptions instead of returning false.
    *
    * It also checks for empty/blank strings before attempting
    * decoding, to improve the quality of error messages
    * if thrown.
    *
    * If this throws, then the `IKickbackThrowable::code()` field
    * of the exception will contain an HTTP code for the error.
    * (It will _probably_ be `400: Bad Request`, for example.)
    *
    * @return array<string,mixed>
    * @throws \Kickback\Common\Exceptions\UnexpectedEmptyException,
    *     \Kickback\Common\Exceptions\JSON_DecodeException,
    *     \Kickback\Common\Exceptions\UnexpectedTypeException
    */
    public static function decode_json_record(string $json, string $resource_or_caller_name) : array
    {
        $json = \trim($json);
        if ( 0 === \strlen($json) ) {
            throw new UnexpectedEmptyException(
                "$resource_or_caller_name: Request contents were blank or empty.".
                    " Expected JSON-encoded request.", 400);  // Code: (HTTP) Bad Request
        }

        $record = \json_decode($json, true);

        if (!isset($record)) {
            // From `https://www.php.net/manual/en/function.json-decode.php`:
            // "null is returned if the json cannot be decoded or if the encoded data is deeper than the nesting limit."
            throw new JSON_DecodeException(
                "$resource_or_caller_name: JSON decoding failed.", 400); // Code: (HTTP) Bad Request
        }

        if (!is_array($record)) {
            // From `https://www.php.net/manual/en/function.json-decode.php`:
            // "Returns the value encoded in json as an appropriate PHP type."
            // So it's possible that the caller may attempt to decode
            // something else, like a PHP object or individual variable.
            // This is not the intent of the function (this is for
            // request/response handling, not serialization/deserialization),
            // so we will error if that ever happens.
            $typestr = \get_debug_type($record);
            throw new UnexpectedTypeException(
                "$resource_or_caller_name: ".
                "Expected JSON to decode to `array`, instead got `$typestr`.",
                400); // Code: (HTTP) Bad Request
        }

        return $record;
    }

    /**
    * Calculate the endpoint's path relative to the API's parent directory.
    *
    * The (seemingly) natural alternative to handling an endpoint's local
    * file path would be to get the URI using `$_SERVER['REQUEST_URI']`.
    *
    * `$_SERVER['REQUEST_URI']` is quite undesirable however,
    * because it can supposedly be set by the client:
    * https://stackoverflow.com/questions/6768793/get-the-full-url-in-php
    *
    * We don't want to trust the client with this.
    *
    * The value of `__FILE__`, however, does not come from the client.
    * Its contents will be more predictable (relatively speaking!),
    * though we will need to ensure that we remove any parts of
    * the path that lie outside of the document root.
    *
    * This is exactly what the `api_relative_path` does:
    * it takes a file path (presumably obtained via `__FILE__`)
    * and removes the prefix that correlates with directories
    * that are outside of the document root.
    *
    * If possible, this will return the path of the endpoint's "file"
    * relative to the API's parent directory. For example, if the
    * document root is `/var/www/project/html` and the `$endpoint_local_file_path`
    * contents are `/var/www/project/html/api/v2/server/endpoint`, then
    * the "path relative to the API's parent directory" would be
    * `api/v2/server/endpoint`.
    */
    private static function api_relative_path(string $endpoint_local_file_path) : string
    {
        // TODO: Eventually this should end up in a more appropriately-named
        //         class that is visible to more backend/API code.

        // These dirnames tend to be things that are OUTSIDE of the URI
        // and contain information about the server's file layout,
        // which is possibly undesirable to share with the world.
        //
        // We don't want the list to be TOO big, because this WILL cause
        // problems if one of the paths within the API tree has a directory
        // in it with one of these names.
        static $docroot = \Kickback\InitializationScripts\SCRIPT_ROOT;
        static $docroot_basename = null;
        static $stop_paths = [
            'html'   => 'html',
            'public' => 'public',
            'web'    => 'web',
            'www'    => 'www',
            'src'    => 'src'];

        // Low-risk option:
        // The path just beings with SCRIPT_ROOT, which would clearly
        // indicate what part of the string we need to remove.
        // So we do that.
        if ( \str_starts_with($endpoint_local_file_path, $docroot) ) {
            return Str::remove_path_prefix($endpoint_local_file_path, $docroot);
        }

        // Higher-risk option:
        // We have to rely on the `$stop_paths` array to tell us
        // where we need to start truncating the endpoint's path.
        // This is higher risk because if one of those `$stop_paths`
        // is also the name of a valid directory segment in the API tree,
        // then it will cause this function to return too-short paths,
        // and that could cause service failures.

        // `$docroot_basename` isn't a "valid constant expression"
        // (it really isn't), so we have to dynamically ensure that
        // it is created and added to `$stop_paths`.
        if (!isset($docroot_basename)) {
            $docroot_basename = \basename($docroot);
            $stop_paths[$docroot_basename] = $docroot_basename;
        }

        $len = \strlen($endpoint_local_file_path);
        $pos = 0;
        $prefix_len = 0;
        while($pos < $len)
        {
            $dirname_start = $pos;
            $dirname_len = \strcspn($endpoint_local_file_path,'/',$pos);
            $pos += $dirname_len;
            $pos++; // Consume the '/'.
            if (0 === $dirname_len) {
                continue;
            }

            $dirname = \substr($endpoint_local_file_path, $dirname_start, $dirname_len);
            if (\array_key_exists($dirname, $stop_paths)) {
                $prefix_len = $pos;
            }
        }
        return Str::remove_path_prefix($endpoint_local_file_path, $prefix_len);
    }

    private static function unittest_api_relative_path() : void
    {
        echo("  ".__FUNCTION__."()\n");
        assert(self::api_relative_path('') === '');
        assert(self::api_relative_path('/') === '');
        assert(self::api_relative_path('endpoint') === 'endpoint');
        assert(self::api_relative_path('/endpoint') === 'endpoint');
        assert(self::api_relative_path('endpoint/') === 'endpoint/');
        assert(self::api_relative_path('/foo/bar/html/api/v2/server/endpoint')    === 'api/v2/server/endpoint');
        assert(self::api_relative_path('html/api/v2/server/endpoint')             === 'api/v2/server/endpoint');
        assert(self::api_relative_path('/x/api/v2/y/html/api/v2/server/endpoint') === 'api/v2/server/endpoint');
        assert(self::api_relative_path('html/api/v2/server/api/v2/endpoint')      === 'api/v2/server/api/v2/endpoint');
        assert(self::api_relative_path('api/v2/server/endpoint')                  === 'api/v2/server/endpoint');
        assert(self::api_relative_path('v2/server/endpoint')                      === 'v2/server/endpoint');
        assert(self::api_relative_path('html/v2/server/endpoint')                 === 'v2/server/endpoint');
        assert(self::api_relative_path('html/server/endpoint')                    === 'server/endpoint');
        assert(self::api_relative_path('/var/www/project/html/api/v2/server/endpoint') === 'api/v2/server/endpoint');
    }

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
        &&  is_string($request_contents['currenct']))
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
    * @param      string       $endpoint_local_file_path
    * @param      string       $request_contents_json
    * @param      ?Response    $response
    * @param-out  Response     $response
    * @return int<0,max>
    */
    public static function api_create_checkout(
        string     $endpoint_local_file_path,
        string     $request_contents_json,
        ?Response  &$response) : int
    {
        $endpoint_name = self::api_relative_path($endpoint_local_file_path);
        Session::ensureSessionStarted();

        if (!Session::isLoggedIn()) {
            $response = new Response(false, "$endpoint_name: Authentication required");
            return 401;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $response = new Response(false, "$endpoint_name: Method not allowed");
            return 405;
        }

        StripeService::initialize();

        $request_contents =
            self::decode_json_record($request_contents_json, $endpoint_name);

        $report = ValidationException::class;
        if(!self::validate_create_checkout_request_array($request_contents, $report))
        {
            $n_errors = $report->count();
            $exc = $report->generate_exception(null,
                "Validation of '$endpoint_name' request failed.\n$n_errors error(s).");
            $exc->code(400); // Bad Request
            throw $exc;
        }

        $accountId  = $request_contents['account_id'];
        $name       = \trim($request_contents['product_name']);
        $amount     = $request_contents['unit_amount'];
        $currency   = \strtoupper($request_contents['currency']);
        $quantity   = $request_contents['quantity'];
        $appFee     = $request_contents['application_fee_amount'];

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

        $response = new Response(true, '', [
            'id' => $session->id,
            'url' => $session->url
        ]);
        return 0; // Success / don't change HTTP response code.
    }

    public static function unittests() : void
    {
        $class_fqn = self::class;
        echo("Running `$class_fqn::unittests()`\n");

        self::unittest_api_relative_path();

        echo("  ... passed.\n\n");
    }
}
