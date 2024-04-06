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

// We need `\Kickback\SCRIPT_ROOT` to be defined for the autoloader to find
// the files that declare the classes.
require_once("script_root.php");

function generic_autoload_function(string $class_fqn, string $namespace_to_try, string $root) : bool
{
    //echo "<!-- INFO: generic_autoload_function('$class_fqn','$namespace_to_try','$root') --> \n";

    // This implements a slightly modified version of PSR-4:
    //   https://www.php-fig.org/psr/psr-4/
    //   https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader-examples.md
    //
    // Ours is modified because we look up our files relative to the
    // document root by using the `"$root/"` string.
    //
    // For the `\Kickback` namespace, $root is `\Kickback\SCRIPT_ROOT`.
    // When executed in a server context such that the
    // `$_SERVER["DOCUMENT_ROOT"]` variable is available, this will have
    // the tremendous advantage of ALWAYS working regardless of which script
    // was the entry point (and regardless of which directory that script was
    // within) when the autoloading function is called.

    // Does the class use the namespace prefix?
    // (Optimization: Do this first, so that we can exit quickly
    // if the class isn't in the given $namespace_to_try.)
    $namespace_name_len = strlen($namespace_to_try);
    if (strlen($class_fqn) <= $namespace_name_len+1) {
        // No, move on to the next registered autoloader
        // ($class_fqn isn't long enough to contain both the namespace and a classname.)
        return false;
    }

    if ("\\" !== substr($class_fqn, $namespace_name_len, 1)) {
        // No, move on to the next registered autoloader
        // We know this because:
        // $class_fqn should have a name separator ("\\") right after the
        // its namespace portion. But in this case, it's not at the correct
        // position for its namespace to match $namespace_to_try, so we know
        // that the class is in a different namespace.
        return false;
    }

    if (0 !== strncmp($namespace_to_try, $class_fqn, $namespace_name_len)) {
        // No, move on to the next registered autoloader
        return false;
    }

    // Now we have proven that the $namespace_to_try is actually the namespace
    // mentioned in $class_fqn.
    $namespace_name = $namespace_to_try;

    // Base directory for the namespace prefix
    // Modification:
    // Don't do the below. It breaks if the entry-point script isn't in document root.
    //    $base_dir = __DIR__ . '/src/'; <- Don't do this.
    // Do this instead:
    $base_dir = $root . DIRECTORY_SEPARATOR . $namespace_name . DIRECTORY_SEPARATOR;
    // Also note that we put our namespace prefix in the path.
    // That seems to diverge from PSR-4. (Maybe?)
    // But it makes sense in our file hierarchy.

    // Get the relative class name
    $relative_class_name = substr($class_fqn, $namespace_name_len+1);

    // Replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $class_file_path = $base_dir . str_replace('\\', DIRECTORY_SEPARATOR, $relative_class_name) . '.php';

    // Modification:
    // We diverge from the PSR-4 by possibly triggering an exception/error
    // if the required class file doesn't exist (by using `require` instead of `include`).
    // PSR-4 forbids such things, because other autoloaders are supposed to get
    // a chance to load any classes that don't exist.
    // However, if some other class has the `Kickback\` namespace prefix
    // (and our file didn't load), then we don't _want_ it to autoload:
    // because it's probably not even from our project!
    // And if we error out, we can get more aggressive error reporting if
    // our class files are missing or misplaced.
    require $class_file_path;

    // Original PSR-4 example code:
    // if the file exists, require it
    //if (file_exists($class_file_path)) {
    //    require $class_file_path;
    //}

    return true;
}

function autoload_function(string $class_fqn) : void
{
    // Attempt to load Kickback classes first.
    // These are the highest priority.
    if ( generic_autoload_function($class_fqn, 'Kickback', \Kickback\SCRIPT_ROOT) ) {
        return;
    }

    // If the class wasn't found in the Kickback namespace, then search
    // the `vendor` folder for code that implements the class.
    $vendor_dirs = scandir(\Kickback\SCRIPT_ROOT . "/vendor", SCANDIR_SORT_NONE);
    if ( false === $vendor_dirs ) {
        // Vendor directory not present.
        echo "<!-- WARNING: Could not find ./vendor directory. -->\n";
        return;
    }

    foreach ( $vendor_dirs as $dir )
    {
        // Skip the composer directory. It is not a namespace.
        // Rather, it contains all of the packages installed by composer,
        // and those are autoloaded by composer's autoloader.
        if ( $dir === "composer"
        ||   $dir === ".."
        ||   $dir === "." ) {
            continue;
        }

        // Attempt to autoload the class using this namespace+directory.
        if ( generic_autoload_function($class_fqn, $dir, \Kickback\SCRIPT_ROOT . "/vendor") ) {
            return;
        }
    }

    // If nothing autoloaded in the above namespaces, then PHP's
    // logic should move on to the next autoloader (ex: composer's autoloader).
    // Note that we don't seem to have any guarantee about what order this
    // happens in, so it's possible that composer's autoloader will run
    // FIRST, then ours will run.
    // TODO: The undefined order-of-operations is undesirable. Ideally, we want our autoloader to run first!
}

spl_autoload_register("\Kickback\autoload_function");

?>
