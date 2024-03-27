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
// so the (Fully) Qualified Name (ex: `Kickback\_DOC_ROOT` below) must
// be provided as its first argument, not just the constant's
// unqualified name (ex: `_DOC_ROOT` below).
//
// Also, we can't specify the _absolute_ qualified name, for... reasons?
// (I honestly don't know why define makes us write `Kickback\_DOC_ROOT`
// instead of the more explicit/precise `\Kickback\_DOC_ROOT`. But if we
// use the latter, it will make the constant appear as an "undefined"
// constant everywhere and it will become unusable. So while we're more
// explicit about it everywhere else in this file, in THIS spot we'll
// elide the leading `\` just because that's what makes everything work.)
//
if ( empty($_SERVER["DOCUMENT_ROOT"]) ) {
    define('Kickback\_DOC_ROOT', __DIR__ . DIRECTORY_SEPARATOR . "..");
} else {
    define('Kickback\_DOC_ROOT', $_SERVER["DOCUMENT_ROOT"]);
}

function generic_autoload_function(string $class_name, string $namespace_prefix) : void
{
    // This implements a slightly modified version of PSR-4:
    //   https://www.php-fig.org/psr/psr-4/
    //   https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader-examples.md
    //
    // Ours is modified because we look up our files relative to the
    // document root by using the `$_SERVER["DOCUMENT_ROOT"] . "/"` string.
    // This will only work if PHP is invoked from within a server that provides
    // the `$_SERVER["DOCUMENT_ROOT"]` variable.
    // But, it has the tremendous advantage of ALWAYS working regardless of
    // which script was the entry point (and regardless of which directory
    // that script was within) when the autoloading function is called.

    // Project-specific namespace prefix
    $path_prefix = str_replace('\\', DIRECTORY_SEPARATOR, $namespace_prefix);

    // Base directory for the namespace prefix
    // Modification:
    // Don't do this. It breaks if the entry-point script isn't in document root.
    //    $base_dir = __DIR__ . '/src/'; <- Don't do this. It breaks if the entry-point script isn't in document root.
    // Do this instead:
    $base_dir = \Kickback\_DOC_ROOT . DIRECTORY_SEPARATOR . $path_prefix;
    // Also note that we put our namespace prefix in the path.
    // That seems to diverge from PSR-4. (Maybe?)
    // But it makes sense in our file hierarchy.

    // Does the class use the namespace prefix?
    $len = strlen($namespace_prefix);
    if (strncmp($namespace_prefix, $class_name, $len) !== 0) {
        // No, move on to the next registered autoloader
        return;
    }

    // Get the relative class name
    $relative_class_name = substr($class_name, $len);

    // Replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $class_file_path = $base_dir .  str_replace('\\', DIRECTORY_SEPARATOR, $relative_class_name) . '.php';

    // Modification:
    // We diverge from the PSR-4 by possibly triggering an exception/error
    // if the required class file doesn't exist.
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
}

function autoload_function(string $class_name) : void
{
    generic_autoload_function($class_name, 'Kickback\\');
}

spl_autoload_register("\Kickback\autoload_function");

?>
