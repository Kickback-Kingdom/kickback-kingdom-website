<?php
declare(strict_types=1);

// -------------------------------------------------------------------------- //
// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! BEWARE !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! //
// ================ READ THE BELOW BEFORE EDITING THIS FILE ================= //
// -------------------------------------------------------------------------- //
//
// While this isn't the earliest initscript in the init process,
// it IS the earliest one that we can feel comfortable editing.
//
// There are still caveats that will persist throughout init scripts:
// * File-scoped variables should not be used.
// * Classes should not be used.
//
// Init scripts are really only for things that absolutely can't be done
// elsewhere (ex: can't be done in classes because the class autoloader
// isn't declared yet). This is a minimal environment that is prone to
// having more pitfalls than normal.
//
// Avoid doing things at root-level init-time, if possible.
//
// About the file-scoped variables:
// File-scoped variables do not get placed into the enclosing namespace,
// but are instead placed into the GLOBAL namespace (no, not $GLOBALS, that's different).
//
// This is different from how every other PHP symbol type interacts with namespaces!
//
// Do not use file-scoped variables. (In init scripts, or anywhere else really.)
//
// Within initscripts, use functions and constants instead.
// Outside of initscripts, classes are the preferred way to do everything.
// In either case, always declare the namespace at the top of every file.
//
// If you need to use variables for miscellaneous computation, then
// declare a function and do the computation inside the function.
//
// If you need a variable that has global state and accessibility
// (kind of like a static property on a class, but without a class declaration),
// then declare it as a static variable inside an accessor function.
// See the `trace_indent_lvl()` function in `Kickback\InitializationScripts\trace.php`
// for an example of this technique.
//
namespace Kickback\InitializationScripts;

// We'll have to wrap this one constant here because it was declared in the
// minimalist contexts of `init*.php` files.
/**
* This function accesses a constant.
*
* It is useful for circumventing "Strict comparison ... will always evaluate to (true|false)."
* errors that PHPStan emits.
*/
function PARENT_PROCESS_TYPE() : string { return \Kickback\InitializationScripts\PARENT_PROCESS_TYPE; }

// Define some simple debug output and tracing mechanims that can be used
// in the autoloader, and (only!) in any other axiomatic initialization mechanisms.
require_once("trace.php");

// Initialize+register the autoloader for \Kickback namespace classes,
// and for any classes that are manually managed with the project
// (ex: things in (\Kickback\SCRIPT_ROOT . "/vendor"))
require_once("autoload_classes.php");

// TODO: Do this from inside a function.
// We don't need composer's autoloader for PHPStan, because we won't have
// PHPStan analyse the 3rd party dependencies.
// Everything else (ex: Web, CLI), however, will need the composer autoloader.
if ( "PHPSTAN" !== PARENT_PROCESS_TYPE() )
{
    // Initialize+register composer's autoloader.
    // We use `include_once` instead of `require_once` so that the site
    // doesn't break if the admin hasn't made composer install anything yet.
    // This is admissible because, as of this writing, any composer modules
    // are optional dependencies, and most site functionality can work without them.
    // Also seems to be important to wrap it in a "file_exists" if-statement
    // because `include_once` can still generate HTML code (for displaying
    // the warning) that may pollute the page (as seen by the user).
    $kk_composer_autoloader_path = \Kickback\SCRIPT_ROOT . "/vendor/composer/autoload.php";
    if ( file_exists($kk_composer_autoloader_path) ) {
        include_once($kk_composer_autoloader_path);
    }
    unset($kk_composer_autoloader_path);
}

?>
