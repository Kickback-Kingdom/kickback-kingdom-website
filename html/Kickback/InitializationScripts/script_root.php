<?php
declare(strict_types=1);

// Even though this file won't be autoloaded, we still put it in a namespace
// so that it will not pollute the global namespace with any symbols that
// are declared here.
//
// NOTABLE EXCEPTION: Variable declarations are not affected by namespaces.
// Please avoid declaring variables in this file, or at top-level in any
// file for that matter.
// From https://www.php.net/manual/en/language.namespaces.definition.php :
// ```
// Although any valid PHP code can be contained within a namespace,
// only the following types of code are affected by namespaces:
// classes (including abstracts and traits), interfaces, functions and constants.
// ```
//
// If you need to define a globally-visible variable/constant outside of this
// file, then use a class property or class const from within a namespace.
// This makes it globally visible WITHOUT placing it in the global namespace.
// It also has the beneficial side-effect of making these things autoload-able.
// Inside this file... just don't use globally visible variables at all
// (because they will pollute the global namespace, and we can't get around
// it with static class properties because we can't load classes yet!),
// and be careful to specify the namespace everytime `define` is used
// (see `define` comments below).
//
namespace Kickback\InitializationScripts;

// Note that the `define` statement is not aware of the enclosing namespace,
// so the (Fully) Qualified Name (ex: `Kickback\SCRIPT_ROOT` below) must
// be provided as its first argument, not just the constant's
// unqualified name (ex: `SCRIPT_ROOT` below).
//
// Also, we can't specify the _absolute_ qualified name, for... reasons?
// (I honestly don't know why define makes us write `Kickback\SCRIPT_ROOT`
// instead of the more explicit/precise `\Kickback\SCRIPT_ROOT`. But if we
// use the latter, it will make the constant appear as an "undefined"
// constant everywhere and it will become unusable. So while we're more
// explicit about it everywhere else in this file, in THIS spot we'll
// elide the leading `\` just because that's what makes everything work.)
//

// Include guard: Check if the constant is already defined to prevent the 'constant already defined' notice.
if (defined('Kickback\InitializationScripts\SCRIPT_ROOT')) {
    return;
}

// The above include-guard is necessary for reasons that might not be obvious
// when `require_once` is dutifully used everywhere this file is included.
// However, on a server that is set up with more than one copy of the codebase
// (ex: beta version and prod version), then it is possible (likely, even)
// for this file to be included more than once. Then, once \Kickback\SCRIPT_ROOT
// is defined, it will be easy for all other scripts to avoid double-inclusion.
//
// In the case of this file, double-inclusion can happen like so:
// (1) kickback-kingdom-beta/.../something.php executes
//         `require_once($_SERVER[DOCUMENT_ROOT] ... . '/Kickback/InitializationScripts/init.php')`
//         to bootstrap SCRIPT_ROOT and autoloading.
//         Because we don't have a valid SCRIPT_ROOT at this point, it resolves to the prod version of `init.php`.
// (2) kickback-kingdom-prod/.../Kickback/InitializationScripts/init.php is included and executes require_once("script_root.php");
// (3) kickback-kingdom-prod/.../Kickback/InitializationScripts/script_root.php is included and defines \Kickback\SCRIPT_ROOT as kickback-kingdom-beta/html.
// (4) Back in kickback-kingdom-prod/.../Kickback/InitializationScripts/init.php, we now execute require_once(\Kickback\SCRIPT_ROOT . "/Kickback/InitializationScripts/autoload_classes.php");
// (5) kickback-kingdom-beta/.../Kickback/InitializationScripts/autoload_classes.php is included and executes require_once("script_root.php");
// (6) kickback-kingdom-beta/.../Kickback/InitializationScripts/script_root.php is NOT the same script as kickback-kingdom-prod/.../Kickback/InitializationScripts/script_root.php, so the inclusion proceeds unimpeded.
// (7) kickback-kingdom-beta/.../Kickback/InitializationScripts/script_root.php attempts to define \Kickback\SCRIPT_ROOT again, thus failing and emitting the redefinition error.
//
// Retrospective note: the above (5) can no longer happen because `autoload_classes.php`
// no longer attempts to include the `script_root.php` file.
// Instead, it asserts that 'Kickback\SCRIPT_ROOT' is defined and uses
// comments/error-messages to openly state its dependency on `script_root.php`.
// This actually makes the double-inclusion impossible (we'll just run `script_root.php`
// in the incorrect SCRIPT_ROOT once, and then never again). The include guard
// is being left in place, just because it provides an additional safety factor,
// and because it could be useful if things ever need to be arranged such that
// `script_root` might be included more than once.
//

// Define the \Kickback\InitializationScripts\SCRIPT_ROOT constant.
// TODO: Doc comments for this constant. At time of writing, it is unclear which branch will be used for doc gen.
// @phpstan-ignore isset.variable, function.impossibleType, booleanAnd.alwaysTrue (Ignore "always set"|"never null"|"always true" errors, because they may only be true in the current environment.)
if ( isset($_SERVER) && !is_null($_SERVER) && !(0 === count($_SERVER)) )
{
    if( (array_key_exists("KICKBACK_SCRIPT_ROOT", $_SERVER))
    &&  !is_null($_SERVER["KICKBACK_SCRIPT_ROOT"])
    &&  !(0 === strlen($_SERVER["KICKBACK_SCRIPT_ROOT"])) )
    {
        // Branch that is executed when the script is being executed as part
        // of an HTTP query on a web server (including local dev machines
        // running an HTTP server).
        //
        // In this case, we look for the KICKBACK_SCRIPT_ROOT server definition.
        // This allows the HTTP server's configuration (ex: httpd.conf) to
        // define a `KICKBACK_SCRIPT_ROOT` environment variable that tells us
        // which root to use for scripts, specifically. This is especially
        // important for ensuring that the beta version of the site is
        // able to pull the correct scripts, instead of accidentally pulling
        // production scripts. (At least, this line below makes it work
        // for autoloading scripts and anything that uses
        // the `\Kickback\SCRIPT_ROOT` constant.)
        //
        define('Kickback\InitializationScripts\SCRIPT_ROOT', $_SERVER["KICKBACK_SCRIPT_ROOT"]);
    }
    else
    if( (array_key_exists("DOCUMENT_ROOT", $_SERVER))
    &&  !is_null($_SERVER["DOCUMENT_ROOT"])
    &&  !(0 === strlen($_SERVER["DOCUMENT_ROOT"])) )
    {
        // Same as above, but it's a fallback for if the admin didn't do
        // `SetEnv KICKBACK_SCRIPT_ROOT "/var/blah/blah/blah"`
        // in the HTTP server's (ex: httpd/apache) config file.
        // We'll presume that `$_SERVER["DOCUMENT_ROOT"]` has the correct info.
        //
        define('Kickback\InitializationScripts\SCRIPT_ROOT', $_SERVER["DOCUMENT_ROOT"]);
    }
    // else { leave it undefined so that the catch-all clause below will pick it up; }
}
// (Don't use `else`, because the above `if` isn't exhaustive.)

// Catch-all for situations where the above definitions didn't take.
// @phpstan-ignore booleanNot.alwaysTrue
if (!defined('Kickback\InitializationScripts\SCRIPT_ROOT'))
{
    // Branch that is probably executed when PHP runs the sites scripts
    // from the command line, instead of from the HTTP server (ex: Apache/HTTPD).
    define('Kickback\InitializationScripts\SCRIPT_ROOT', __DIR__ . DIRECTORY_SEPARATOR . "../..");
}

/**
* This function accesses a constant.
*
* It is useful for circumventing "Strict comparison ... will always evaluate to (true|false)."
* errors that PHPStan emits.
*
* @see \Kickback\InitializationScripts\SCRIPT_ROOT
*/
function SCRIPT_ROOT() : string { return \Kickback\InitializationScripts\SCRIPT_ROOT; }

/**
* This is the shorthand version of \Kickback\InitializationScripts\SCRIPT_ROOT.
*
* Admittedly, this IS a little confusing, given that `script_root.php`
* isn't at the path that `Kickback\SCRIPT_ROOT` would suggest.
* However, this constant is going to be used in many `require_once` statements,
* so having a not-terribly-long name for it is very helpful.
*
* @see \Kickback\InitializationScripts\SCRIPT_ROOT
*/
define('Kickback\SCRIPT_ROOT', \Kickback\InitializationScripts\SCRIPT_ROOT);

?>
