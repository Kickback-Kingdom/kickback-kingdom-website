<?php
declare(strict_types=1);

// -------------------------------------------------------------------------- //
// !!!!!!!!!!!!!!!!!!!!!!!!!! DO NOT EDIT THIS FILE !!!!!!!!!!!!!!!!!!!!!!!!! //
// -------------------------------------------------------------------------- //
//
// This file may be executed from within an incorrect script root,
// which introduces pitfalls for file inclusion (correct use of `require_once`)
// as well as pitfalls for version control and change management.
// Hence, it is best to just avoid editing this file, unless you must.
//
// As of this writing, the only foreseeable reason to edit this file would
// be if one is redesigning the init system for the entire Kickback namespace.
//
// If you just want to add initialization code, then it should probably
// be included from `Kickback\InitializationScripts\common_init.php`.
// That script will be executed from a more sane context: after changing script root.
//
// If you need to add a special entry point, to indicate that we are not
// in the ordinary web/cli context, then use `Kickback\InitializationScripts\init_for_phpstan.php`
// as an example and keep it simple.
//
// See `change_root.php` for further explanation for why `init*.php`
// (and change_root.php) should not be edited and should be kept VERY simple.

// -------------------------------------------------------------------------- //
// Bugfix:
//
// When executing `php` from the command line (CLI) I observed that
// `array_key_exists("DOCUMENT_ROOT", $_SERVER)` was `true`.
//
// This was very problematic for the previous logic that used
// `array_key_exists("DOCUMENT_ROOT", $_SERVER)` as a way to detect web context!
//
// After some research, it was revealed that the `$_SERVER['DOCUMENT_ROOT']`
// is NOT a reliable way to do this.
//
// Better is to use the pre-defined constant `PHP_SAPI` or
// the pre-defined function `php_sapi_name()`.
//
// BUT. Those can still fail for various reasons, with a common one being
// "I'm in a CLI context but `php` was ran in a CGI context for non-web
// reaasons and now `PHP_SAPI !== 'cli'` but instead `PHP_SAPI === `cgi`".
//
// Anyhow, let's declare a function to handle some non-trivial logic
// for CLI detection.
function init__is_running_in_cli_context() : bool
{
    // These are very explicitly CLI or CLI-like things.
    // If they appear, we immediately have proof-positive.
    if ( PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg' ) {
        return true;
    }

    // Having STDIN likely means that we are in a CLI environ.
    // However, not having it doesn't mean we aren't.
    //
    // BinaryTides had this to say about it:
    // ```
    // Now the php command itself points to a php binary which can be either
    // the php-cli binary or php-cgi binary. If it points to a php-cgi binary,
    // like it happens on some hosting servers then the STDIN check
    // will always be false. So the STDIN check method is not fully reliable.
    // ```
    // Source: https://www.binarytides.com/php-check-running-cli/
    //
    // But at least we can be pretty confident if it IS defined.
    if ( defined('STDIN') ) {
        return true;
    }

    // Sidenote: BinaryTides offers more ways to check for web-ness
    // based on $_SERVER values like REMOTE_ADDR, HTTP_USER_AGENT,
    // and 'argv'. Two of those are fairly unwise: REMOTE_ADDR can potentially
    // be spoofed (relying on TCP:IP stack to be truthful is dangerous),
    // and HTTP_USER_AGENT can be directly manipulated by the client.
    //
    // The 'argv' check seems like a good idea (at least for proof-positive),
    // but it has a BIG caveat:
    // It can be populated by GET requests under some conditions:
    // https://stackoverflow.com/q/9270030
    // https://stackoverflow.com/a/9270118
    //
    // The last post suggests that if the INI value of `register_argc_argv`
    // is OFF/disabled, then 'argv' won't get populated with the query.
    // However, reading the documentation for it suggests that it
    // might affect `argv` _in general_, so it might not actually
    // tell us if `argv` contents could come from a GET request or not.
    // It's all really confusing, so we're just going to steer clear.
    //
    //
    // ...
    //
    //
    // Past this point, our options get ambiguous.
    // Sometimes `php` is invoked from a CGI context (`php-cgi`) for... REASONS.
    //
    // Ex: https://www.php.net/manual/en/function.php-sapi-name.php#89858
    // ```
    // Note, that the php-cgi binary can be called from the command line,
    // from a shell script or as a cron job as well!
    // If so, the php_sapi_name() will always return the same value
    // (i.e. "cgi-fcgi") instead of "cli" which you could expect.
    // ```
    //
    // Here is another example of someone getting `php-cgi` when their
    // PHP script is being run from a cron job:
    // https://stackoverflow.com/questions/10886539/why-does-php-sapi-not-equal-cli-when-called-from-a-cron-job
    //
    // (And cron jobs, while not technically interactive like the term 'CLI'
    // would suggest, are still much more CLI-like than they are WEB-like.)
    //
    // CGI is _usually_ supposed to be used by a web server, but if there's
    // no REQUEST_METHOD, then it's probably from CLI instead.
    //
    // At this point, we notice that even outside of CGI, REQUEST_METHOD
    // might be a really good way to proof-positive a web interaction!
    //
    // So we look for the REQUEST_METHOD field.
    // No web server means no REQUEST_METHOD
    // Contrapositively, "yes REQUEST_METHOD" means "yes web server".
    // @phpstan-ignore isset.variable
    if (isset($_SERVER) && array_key_exists('REQUEST_METHOD', $_SERVER)
    &&  isset($_SERVER['REQUEST_METHOD']) && 0 < strlen($_SERVER['REQUEST_METHOD'])) {
        return false;
    }

    // Of course, we still haven't proven that there isn't a web server,
    // just that there isn't a REQUEST_METHOD.
    //
    // But in the context of `cgi` and `cgi-fcgi` SAPIs, which are the
    // likely suspects for CLI operation that looks like web operation,
    // we can be a lot more confident that it's a CLI-like context.
    if ( PHP_SAPI === 'cgi' || PHP_SAPI === 'cgi-fcgi' ) {
        return true;
    }

    // If we're out of clues, we just assume it's a web context.
    // (It's the most likely choice if we want to keep a/the site running!)
    return false;
}

// Communicate to the other startup scripts that we are running
// on a webserver ("WEB") or from a command line ("CLI").
if (!defined('Kickback\InitializationScripts\PARENT_PROCESS_TYPE'))
{
    if ( init__is_running_in_cli_context() ) {
        define('Kickback\InitializationScripts\PARENT_PROCESS_TYPE', "CLI");
    } else {
        define('Kickback\InitializationScripts\PARENT_PROCESS_TYPE', "WEB");
    }
}

// The change_root script will handle initialization from here.
// (It defines SCRIPT_ROOT and then calls the `common_init.php` in the correct SCRIPT_ROOT.)
require_once("change_root.php");

?>
