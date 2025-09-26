<?php
declare(strict_types=1);

namespace Kickback\Services\ApiV2;

use Kickback\Common\Primitives\Str;
use Kickback\Common\Exceptions\JSON_DecodeException;
use Kickback\Common\Exceptions\KickbackException;
use Kickback\Common\Exceptions\UnexpectedEmptyException;
use Kickback\Common\Exceptions\UnexpectedTypeException;

/**
* Miscellaneous API-v2 related functions.
*/
class Endpoint
{
    use \Kickback\Common\Traits\StaticClassTrait;

    private static bool $have_buffering_ = false;

    /**
    * Called at the beginning of endpoint execution.
    */
    public static function begin() : void
    {
        self::$have_buffering_ = \ob_start();
    }

    /**
    * Called at the conclusion of endpoint execution.
    */
    public static function end() : void
    {
        // This captures any output from "echo" commands during execution.
        // Nothing should output to `stdout/stderr`, so any such text
        // can be considered unintended, and appropriate for passing to \error_log.
        // (And such output will mess up the response's JSON if we don't redirect it.)
        if ( !self::$have_buffering_ ) {
            return;
        }

        $output = \ob_get_clean();
        if ( $output === false || 0 === \strlen($output) ) {
            return;
        }

        // Limit output to 1kB.
        // This could matter in a worst-case scenario where there are
        // unsolved "echo from within logic" problems that emit a lot
        // of text, and the endpoint(s) get hit frequently.
        if ( 1024 < \strlen($output) ) {
            $output = \substr($output, 0, 1024);
        }

        \error_log($output);
    }

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
    public static function decode_json_record(string $json) : array
    {
        $json = \trim($json);
        if ( 0 === \strlen($json) ) {
            $endpoint_name = self::calculate_endpoint_resource_name();
            throw new UnexpectedEmptyException(
                "$endpoint_name: Request contents were blank or empty.".
                    " Expected JSON-encoded request.", 400);  // Code: (HTTP) Bad Request
        }

        $record = \json_decode($json, true);

        if (!isset($record)) {
            // From `https://www.php.net/manual/en/function.json-decode.php`:
            // "null is returned if the json cannot be decoded or if the encoded data is deeper than the nesting limit."
            $endpoint_name = self::calculate_endpoint_resource_name();
            throw new JSON_DecodeException(
                "$endpoint_name: JSON decoding failed.", 400); // Code: (HTTP) Bad Request
        }

        if (!is_array($record)) {
            // From `https://www.php.net/manual/en/function.json-decode.php`:
            // "Returns the value encoded in json as an appropriate PHP type."
            // So it's possible that the caller may attempt to decode
            // something else, like a PHP object or individual variable.
            // This is not the intent of the function (this is for
            // request/response handling, not serialization/deserialization),
            // so we will error if that ever happens.
            $endpoint_name = self::calculate_endpoint_resource_name();
            $typestr = \get_debug_type($record);
            throw new UnexpectedTypeException(
                "$endpoint_name: ".
                "Expected JSON to decode to `array`, instead got `$typestr`.",
                400); // Code: (HTTP) Bad Request
        }

        return $record;
    }

    // TODO: Throw a more specific exception once we have I/O exceptions defined.
    /**
    * Same as `\file_get_contents`, but it throws on errors instead of returning `false`.
    *
    * Throws a `\Kickback\Common\Exceptions\KickbackException`
    * if the file couldn't be read.
    *
    * @param  ?resource    $context
    * @param  ?int<0,max>  $length
    * @throws KickbackException
    */
    public static function file_get_contents(
        string  $filename,
        bool    $use_include_path = false,
        mixed   $context = null,
        int     $offset  = 0,
        ?int    $length  = null
    ) : string
    {
        $json = \file_get_contents(
            $filename, $use_include_path, $context, $offset, $length);

        if ( $json !== false ) {
            return $json;
        }

        $endpoint_name = self::calculate_endpoint_resource_name();
        // As of this writing, $filename will typically be `php://input`.
        $uip_str = $use_include_path ? 'true' : 'false';
        $context_type = \get_debug_type($context);
        $offset_str = \strval($offset);
        $length_str = isset($length) ? \strval($length) : "\'null\' (default)";
        throw new KickbackException(
            "$endpoint_name: I/O read failed:\n  \\file_get_contents(".
                "filename: '$filename', use_include_path: $uip_str, ".
                "context: $context_type, offset: $offset_str, ".
                "length: $length_str)\n  returned `false`.\n".
                "  Unable to receive request.", 500);  // Code: (HTTP) Internal Server Error
    }

    // Cached value for `calculate_endpoint_resource_name`.
    private static string $endpoint_name_ = '';

    /**
    * Use \debug_backtrace and path sanitization to determine the endpoint's resource name/path.
    *
    * The path returned by this function will have been sanitized by passing
    * it through the `Endpoint::api_relative_path` function to remove
    * segments that wouldn't be part of a URL.
    */
    public static function calculate_endpoint_resource_name() : string
    {
        // Cache/memoize, because this should never change during execution.
        if ( 0 < \strlen(self::$endpoint_name_) ) {
            return self::$endpoint_name_;
        }

        // If it's not set, then there is more complicated logic to do to acquire it.
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $len = \count($trace);
        if ( 0 === $len ) {
            return "{unknown endpoint path}";
        }
        $frame = $trace[$len-1];
        // @phpstan-ignore  isset.offset
        if ( !\array_key_exists('file', $frame) || !isset($frame['file']) ) {
            return "{unknown endpoint path}";
        }
        $file_path = $frame['file'];
        self::$endpoint_name_ = self::api_relative_path($file_path);
        return self::$endpoint_name_;
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
    * The value of `__FILE__` (or equivalent backtrace frame), however,
    * does not come from the client. Its contents will be more predictable
    * (relatively speaking!), though we will need to ensure that
    * (we remove any parts of he path that lie outside of the document root.
    *
    * This is exactly what the `api_relative_path` does:
    * it takes a file path (presumably obtained via `__FILE__` or
    * `\debug_backtrace`) and removes the prefix that correlates
    * with directories that are outside of the document root.
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

    public static function unittests() : void
    {
        $class_fqn = self::class;
        echo("Running `$class_fqn::unittests()`\n");

        self::unittest_api_relative_path();

        echo("  ... passed.\n\n");
    }
}
?>
