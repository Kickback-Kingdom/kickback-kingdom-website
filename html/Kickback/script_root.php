<?php

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
namespace Kickback;

// Note that the `define` statement is not aware of the enclosing namespace,
// so the (Fully) Qualified Name (ex: `Kickback\_SCRIPT_ROOT` below) must
// be provided as its first argument, not just the constant's
// unqualified name (ex: `_SCRIPT_ROOT` below).
//
// Also, we can't specify the _absolute_ qualified name, for... reasons?
// (I honestly don't know why define makes us write `Kickback\_SCRIPT_ROOT`
// instead of the more explicit/precise `\Kickback\_SCRIPT_ROOT`. But if we
// use the latter, it will make the constant appear as an "undefined"
// constant everywhere and it will become unusable. So while we're more
// explicit about it everywhere else in this file, in THIS spot we'll
// elide the leading `\` just because that's what makes everything work.)
//
if ( !empty($_SERVER["KICKBACK_SCRIPT_ROOT"]) ) {
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
    // the `\Kickback\_SCRIPT_ROOT` constant.)
    //
    define('Kickback\_SCRIPT_ROOT', $_SERVER["KICKBACK_SCRIPT_ROOT"]);
}
else
if ( !empty($_SERVER["DOCUMENT_ROOT"]) ) {
    // Same as above, but it's a fallback for if the admin didn't do
    // `SetEnv KICKBACK_SCRIPT_ROOT "/var/blah/blah/blah"`
    // in the HTTP server's (ex: httpd/apache) config file.
    // We'll presume that `$_SERVER["DOCUMENT_ROOT"]` has the correct info.
    //
    define('Kickback\_SCRIPT_ROOT', $_SERVER["DOCUMENT_ROOT"]);
}
else {
    // Branch that is probably executed when PHP runs the sites scripts
    // from the command line, instead of from the HTTP server (ex: Apache/HTTPD).
    define('Kickback\_SCRIPT_ROOT', __DIR__ . DIRECTORY_SEPARATOR . "..");
}

?>
