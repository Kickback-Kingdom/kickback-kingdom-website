<?php

// An important special (and tricky) property of this file:
//
// It may be running from the `prod` SCRIPT_ROOT while the site is being
// accessed in `beta` mode. Normally, if the site is in `beta` mode,
// we would be in a different SCRIPT_ROOT for `beta`.
//
// It might be running from `prod` while in `beta` mode because the scripts
// including (`require`'ing) this file do not have \Kickback\SCRIPT_ROOT
// defined yet, and must rely on using something like `$_SERVER['DOCUMENT_ROOT']`
// to locate the `init.php` file. And it's that use of `$_SERVER['DOCUMENT_ROOT']`
// that can place us at the incorrect script root.
//
// One important implication of the above is that we should not `require_once`
// on relative paths from this file, except for `script_root.php` itself.
// We need to use a relative path for `script_root.php` because it's what
// computes+defines \Kickback\SCRIPT_ROOT, and we have no better options
// for locating scripts until that is done. AFTER that, we should reference
// scripts using ONLY \Kickback\SCRIPT_ROOT (while in this file),
// thus restoring inclusion to the correct script root.

// File that declares \Kickback\SCRIPT_ROOT.
// Do this first, so that we can use it to locate the correct script files.
require_once("script_root.php");

// Initialize+register the autoloader for \Kickback namespace classes,
// and for any classes that are manually managed with the project
// (ex: things in (\Kickback\SCRIPT_ROOT . "/vendor"))
require_once(\Kickback\SCRIPT_ROOT . "/Kickback/autoload_classes.php");

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

?>
