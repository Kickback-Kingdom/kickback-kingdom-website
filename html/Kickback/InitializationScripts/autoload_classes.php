<?php
declare(strict_types=1);

// TODO: Update comment at top of this file. (It is probably redundant with most what's in `common_init.php`)
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

/*
// We need `\Kickback\SCRIPT_ROOT` to be defined for the autoloader to find
// the files that declare the classes.
require_once("script_root.php");
*/
assert(defined('Kickback\SCRIPT_ROOT'),
    "This could happen if `autoload_classes.php` is included/executed directly; ".
    "if that's the case, be sure to include/execute `init.php` instead. ".
    "`init.php` will call all of the bootstrapping code necessary to meet ".
    "the autoloader's needs.\n");

// `trace.php` should already be loaded at this point, but
// with this `require_once` we not only _make sure_ of it,
// we also document the dependency.
require_once("trace.php");

/**
* This constant determines if the autoloader will echo a bunch of trace
* output that shows what it's doing.
*
* This should usually be set to `false`. The only time you want to set it
* to `true` is if something is wrong with the autoloader and it needs
* some very detailed troubleshooting.
*/
define('Kickback\InitializationScripts\AUTOLOAD_DO_DEBUG_ECHO', false);

/**
* This function accesses a constant.
*
* It is useful for circumventing "Strict comparison ... will always evaluate to (true|false)."
* errors that PHPStan emits.
*
* @see \Kickback\InitializationScripts\AUTOLOAD_DO_DEBUG_ECHO
*/
function AUTOLOAD_DO_DEBUG_ECHO() : bool { return \Kickback\InitializationScripts\AUTOLOAD_DO_DEBUG_ECHO; }

/**
* Used to print errors when something goes wrong with autoloading.
*
* Unlike the `autoload_debug_*` printing functions, this one takes a string
* argument and does its printing unconditionally, not just when debug printing is enabled.
*
* This is pretty much just the `trace` function, but given its own name
* to allow us to hook into autoloader tracing specifically..
*
* @see \Kickback\InitializationScripts\autoload_debug_trace
* @see \Kickback\InitializationScripts\trace
*/
function autoload_error(string $msg) : void
{
    trace($msg);
}

/**
* Executes `echo($get_msg())`, but only if `\Kickback\InitializationScripts\AUTOLOAD_DO_DEBUG_ECHO` is true.
*
* Note that the argument is a callback, like with `autoload_debug_trace`.
*
* @see \Kickback\InitializationScripts\autoload_debug_trace
*/
function autoload_debug_echo(callable $get_msg) : void
{
    if ( true === AUTOLOAD_DO_DEBUG_ECHO() ) {
        echo($get_msg());
    }
}

/**
* Calls `\Kickback\InitializationScripts\trace_indent_more()`, but only if `\Kickback\InitializationScripts\AUTOLOAD_DO_DEBUG_ECHO` is true.
*
* @see \Kickback\InitializationScripts\trace_indent_more
*/
function autoload_debug_indent_more() : void
{
    if ( true === AUTOLOAD_DO_DEBUG_ECHO() ) {
        trace_indent_more();
    }
}

/**
* Calls `\Kickback\InitializationScripts\trace_indent_less()`, but only if `\Kickback\InitializationScripts\AUTOLOAD_DO_DEBUG_ECHO` is true.
*
* @see \Kickback\InitializationScripts\trace_indent_less
*/
function autoload_debug_indent_less() : void
{
    if ( true === AUTOLOAD_DO_DEBUG_ECHO() ) {
        trace_indent_less();
    }
}

/**
* Calls `\Kickback\InitializationScripts\trace($get_msg())`, but only if `\Kickback\InitializationScripts\AUTOLOAD_DO_DEBUG_ECHO` is true.
*
* Note that the argument is a CALLBACK and not a string.
*
* This allows the PHP intrepreter to avoid doing string operations when
* the message string is not going to be used.
*
* But it has the downside of requiring slightly more complicated syntax:
* ```
* autoload_debug_trace("x = $x"); // ERROR, doesn't work.
* // vs
* autoload_debug_trace(fn() => "x = $x"); // SUCCESS! It works.
* ```
*
* The underlying `trace` function is like `echo`, but it also indents the message,
* adds HTML comment tags if appropriate, newline if appropriate, and sends
* it to the most appropriate destination that we can figure at this point.
*
* @see \Kickback\InitializationScripts\autoload_error
* @see \Kickback\InitializationScripts\trace
*/
function autoload_debug_trace(callable $get_msg) : void
{
    if ( true === AUTOLOAD_DO_DEBUG_ECHO() ) {
        trace($get_msg());
    }
}


/** Returned by autoloader functions internally to indicate the class was found. */
define('Kickback\InitializationScripts\AUTOLOAD_SUCCESS',    0);

/**
* Returned by autoloader functions internally to indicate that the class was not found,
* but other autoloaders might be still be able to find it. (So the autoloading process should continue.)
*/
define('Kickback\InitializationScripts\AUTOLOAD_INCOMPLETE', 1);

/**
* Returned by autoloader functions internally to indicate that there is something
* wrong with the file that the class should be in. Thus loading has _thoroughly_ failed,
* and no more autoloading attempts should be made.
*/
define('Kickback\InitializationScripts\AUTOLOAD_FAILURE',    2);

/**
* Returned by autoloader functions internally to indicate that the given
* symbol (class fqn) matches a special pattern that is used to tell the
* autoloader to NOT attempt autoloading.
*
* Example: if a symbol is ends in `_a`, then it is considered
* to be a PHPStan "local alias" and should NOT be autoloaded.
*/
define('Kickback\InitializationScripts\AUTOLOAD_IGNORED',    3);

/**
* Converts Kickback autoloader return codes into their short (unqualified) names.
*/
function autoload_result_name(int $result) : string
{
    switch($result)
    {
        case \Kickback\InitializationScripts\AUTOLOAD_SUCCESS:    return "AUTOLOAD_SUCCESS";
        case \Kickback\InitializationScripts\AUTOLOAD_INCOMPLETE: return "AUTOLOAD_INCOMPLETE";
        case \Kickback\InitializationScripts\AUTOLOAD_FAILURE:    return "AUTOLOAD_FAILURE";
        case \Kickback\InitializationScripts\AUTOLOAD_IGNORED:    return 'AUTOLOAD_IGNORED';
    }
    return "Invalid value: " . strval($result);
}

function autoloader_check_eponymous_trait_classes(
    string  &$class_unqual_name,
    string  &$class_base_path,
    ?string &$class_file_path,
    bool    &$use_require_once
) : void
{
    // -- Eponymous trait classes. --
    // When looking up class's file (ex: Kickback\Something\ClassName.php),
    // and that file doesn't exist,
    // but a "trait" file exists instead (ex: Kickback\Something\ClassNameTrait.php),
    // then look for the class ClassName in the file ClassNameTrait.php.
    //
    // Normally trait code will get duplicated into every class that the
    // trait is mixed into. However, sometimes we want just a single instance
    // of something related to that trait. Now we can have a class that lives
    // in the same file as that trait, which can then house any single-instance code.
    //
    // Conventional autoloading would require the class to live in a separate
    // file, but having them live in the same file has advantages:
    // * It keeps strongly-related code pieces next to each other.
    // * Interdependency between the trait and the class are not as likely
    //     to cause circular dependency errors for PHP's class loading
    //     algorithm, or for static analysis tools.
    //
    // One very notable use-case for this is unittests:
    // * We can't really put unittests into the trait itself, because then
    //     there would be ambiguity about how they should be called and
    //     which ones would be called. Also, traits tend to be incomplete
    //     by their very nature, and are likely untestable in isolation.
    // * The trait's sibling class becomes a place that unittests can be
    //     placed, and a place where at least one controlled instance
    //     of the trait can be instantiated.
    // * The trait FooBarTrait.php would have it's unittests run by calling
    //     the method FooBar::unittests(), which allows it to follow the
    //     same convention as other classes.
    // * If more classes are needed (e.g. to test different scenarios for
    //     trait expansion), then there is now an "entry point" that
    //     can fan out to those classes.
    //
    // SO although this feature is kinda quirky and non-standard,
    // this just might be promising enough to justify some bespoke
    // modification of the autoloader.
    //
    // (Note: When handed `ClassNameTrait`, we do not look for `ClassName.php`;
    // the point is to find classes that are subordinate to traits, not to
    // find traits that are subordinate to classes.)
    //

    if ( str_ends_with($class_unqual_name, 'Trait') )
    {
        $stem_unqual_name  = substr($class_unqual_name, 0, -strlen('Trait'));
        $stem_base_path    = substr($class_base_path,  0, -strlen('Trait'));
        $trait_unqual_name = $class_unqual_name;
        $trait_base_path   = $class_base_path;
    }
    else // $class_base_path does NOT end in 'Trait'
    {
        $stem_unqual_name  = $class_unqual_name;
        $stem_base_path    = $class_base_path;
        $trait_unqual_name = $class_unqual_name . 'Trait';
        $trait_base_path   = $class_base_path . 'Trait';
    }

    $stem_file_path  = $stem_base_path . '.php';
    $trait_file_path = $trait_base_path . '.php';

    $stem_file_exists  = file_exists($stem_file_path);
    $trait_file_exists = file_exists($trait_file_path);

    $symbol_is_class = ($class_unqual_name === $stem_unqual_name);

    // In the below code, note that we only set `$use_require_once`
    // to `true` when appropriate, but never to `false`.
    // That's because it defaults to `false`, and we don't want to mess with it,
    // because some other feature might set it to `true` before us.
    // And it is always safer to be too aggressive about `require_once`
    // than not aggressive enough.
    if ( $symbol_is_class )
    {
        // Base case:
        // The symbol is a class and there is a file for it.
        // There's also a file for its corresponding trait symbol.
        // So if it autoloads, then it'll require that file
        // instead of this one, so we don't need to `require_once`.
        if ( $trait_file_exists && $stem_file_exists )
        {
            /** @phpstan-ignore booleanOr.rightAlwaysFalse */
            $use_require_once  = $use_require_once || false;
            return;
        }
        else
        // Feature activation case:
        // There is a trait file and not a class file, and
        // the current symbol is the class.
        // In this situation we must look for the class
        // in its corresponding trait file.
        // Since this implies a 2-to-1 relationship of
        // loadable-entities-to-files, we need to use
        // `require_once` to avoid duplicate loads.
        /** @phpstan-ignore booleanNot.alwaysTrue */
        if ( $trait_file_exists && !$stem_file_exists )
        {
            $class_unqual_name = $trait_unqual_name;
            $class_base_path   = $trait_base_path;
            $class_file_path   = $trait_file_path;

            /** @phpstan-ignore booleanOr.rightAlwaysTrue */
            $use_require_once  = $use_require_once || true;
            return;
        }
        else
        // Base case:
        // There's no trait file and the class name doesn't have trait notation.
        // In this situation, we do nothing, and just load the class as usual.
        // This is the "we don't look for traits in class files" logic from
        // the class's point of view:
        // ```
        // When handed `ClassNameTrait`, we do not look for `ClassName.php`;
        // the point is to find classes that are subordinate to traits, not to
        // find traits that are subordinate to classes.
        // ```
        // (e.g. we know there is a 1-to-1 entity-to-file relationship
        // because there is no trait, and if a trait attempts to autoload
        // it should cause an error per the definition of this feature)
        if ( !$trait_file_exists && $stem_file_exists )
        {
            /** @phpstan-ignore booleanOr.rightAlwaysFalse */
            $use_require_once  = $use_require_once || false;
            return;
        }
        else
        // Nothing was found.
        // This will just always be an error,
        // and we can just leave $class_base_path and $class_file_path alone
        // to get later code to emit an error for us.
        if ( !$trait_file_exists && !$stem_file_exists ) {
            return;
        }
    }
    else // symbol is a trait
    {
        // Base case:
        // The symbol is a trait and there is a file for it.
        // There's also a file for its corresponding class symbol.
        // So if it autoloads, then it'll require that file
        // instead of this one, so we don't need to `require_once`.
        if ( $trait_file_exists && $stem_file_exists )
        {
            /** @phpstan-ignore booleanOr.rightAlwaysFalse */
            $use_require_once  = $use_require_once || false;
            return;
        }
        else
        // Cautionary case:
        // IThe symbol is a trait and only a trait file exists.
        // This could be a simple one-to-one, but it could ALSO
        // be that an earlier/later autoload will encounter
        // a class symbol that belongs in the same trait file.
        // Just to be safe, we'll use `require_once` on this.
        /** @phpstan-ignore booleanNot.alwaysTrue */
        if ( $trait_file_exists && !$stem_file_exists )
        {
            /** @phpstan-ignore booleanOr.rightAlwaysTrue */
            $use_require_once  = $use_require_once || true;
            return;
        }
        else
        // Restricted/Error case:
        // As above:
        // When handed `ClassNameTrait`, we do not look for `ClassName.php`;
        // the point is to find classes that are subordinate to traits, not to
        // find traits that are subordinate to classes.
        if ( !$trait_file_exists && $stem_file_exists )
        {
            // We don't need to DO anything because if we leave
            // $class_base_path and $class_file_path alone, then
            // the later autoloader code will just emit an error for us.
            return;
        }
        else
        // Error case:
        // Nothing was found.
        // This will just always be an error,
        // and we can just leave $class_base_path and $class_file_path alone
        // to get later code to emit an error for us.
        if ( !$trait_file_exists && !$stem_file_exists ) {
            return;
        }
    }







    // if (($first_try_exists  && !$second_try_exists)
    // ||  (!$first_try_exists && $second_try_exists))
    // {
    //     $use_require_once = false;
    // }
    //
    // if (!isset($class_file_path)
    // && $enable_eponymous_trait_class
    // &&  !str_ends_with($class_base_path, 'Trait') )
    // {
    //     $basename_stem = $input_basename;
    //     $class_stem_path = $class_base_path;
    //
    //     $trait_base_path = $class_base_path . 'Trait';
    //     $trait_file_path = $trait_base_path . '.php';
    //
    //     if (!file_exists($class_file_path)
    //     &&   file_exists($trait_file_path))
    //     {
    //         $class_base_path = $trait_base_path;
    //     }
    //
    //     // Check for 'ClassName.php' -> 'ClassNameTrait.php'
    //     if ( str_ends_with($class_base_path, 'Trait') ) {
    //         $basename_stem   = substr($input_basename,  0, -strlen('Trait'));
    //         $class_base_path = substr($class_base_path, 0, -strlen('Trait'));
    //     }
    // }
    //
    //
    //
    // if ( str_ends_with($class_base_path, 'Trait') ) {
    //     // NOTE: on this route, if both exist, we must prefer to load the trait.
    //     // Remove the 'Trait' part of the file path.
    //     $class_base_path = substr($class_base_path, 0, -strlen('Trait'));
    //     $first_try_file_path  = $class_base_path . 'Trait.php';
    //     $second_try_file_path = $class_base_path . '.php';
    // } else {
    //     // NOTE: on this route, if both exist, we must prefer to load the class.
    //     $first_try_file_path  = $class_base_path . '.php';
    //     $second_try_file_path = $class_base_path . 'Trait.php';
    // }
    // //autoload_debug_trace(fn() =>
    // //echo(
    // //    "\$class_base_path      = $class_base_path;\n".
    // //    "\$first_try_file_path  = $first_try_file_path;\n".
    // //    "\$second_try_file_path = $second_try_file_path;\n");
    //
    // $first_try_exists  = file_exists($first_try_file_path);
    // $second_try_exists = file_exists($second_try_file_path);
    //
    // if ($first_try_exists && $second_try_exists) {
    //     $use_require_once = false;
    //     $class_file_path = $first_try_file_path;
    // } else
    // if ($first_try_exists && !$second_try_exists) {
    //     $use_require_once = true;
    //     $class_file_path = $first_try_file_path;
    // } else
    // if (!$first_try_exists && $second_try_exists) {
    //     $use_require_once = true;
    //     $class_file_path = $second_try_file_path;
    // }
    // // else {
    // //     Just leave the $class_file_path alone.
    // //     The autoloader was probably destined to emit an error at this point.
    // //     And this way, it will make the error message contain the name
    // //     of the class that was called, and not something else.
    // // }
}

function autoloader_require_once_if_eponymous_subclasses_exist(
    string  $class_unqual_name,
    string  $class_base_path,
    ?string $class_file_path,
    bool    &$use_require_once
) : void
{
    $subclass_path_possibilities_glob = $class_base_path . '__*';
    $subclass_paths =
        glob($subclass_path_possibilities_glob, GLOB_NOESCAPE | GLOB_NOSORT);
    if ( $subclass_paths === false || count($subclass_paths) === 0 )
    {
        // There are no subclasses.
        // We can just use `require` as normal.
        // (It defaults to `false`, and we don't want to mess with it,
        // just in case some other feature sets it to `true` before us.)
        /** @phpstan-ignore booleanOr.rightAlwaysFalse */
        $use_require_once = $use_require_once || false;
        return;
    }
    else
    {
        // Subclasses exist.
        // Even though we aren't loading one of them, and we're loading
        // the main class (of the group) instead, we still want to use
        // `require_once`, because the file will also have that called
        // on it before or after this autoloader call.
        /** @phpstan-ignore booleanOr.rightAlwaysTrue */
        $use_require_once = $use_require_once || true;
        return;
    }
}

function autoloader_check_eponymous_subclasses(
    string  &$class_unqual_name,
    string  &$class_base_path,
    ?string &$class_file_path,
    bool    &$use_require_once
) : void
{
    // -- Eponymous Subclasses --
    //
    // Whenever we encounter a class whose file has
    // double underscores in its name, ex: `Kickback\Foo\Bar\MyClass__SubClass.php`,
    // then we also check the lhs of that token: `Kickback\Foo\Bar\MyClass.php`.

    // Trim the class name so that stuff like `__FooBar__Qux` won't
    // confuse the algorithm.
    $trimmed_name = ltrim($class_unqual_name,'_');
    $nchars_trimmed_front = (strlen($class_unqual_name) - strlen($trimmed_name));

    // Suppose ($class_unqual_name === 'FooBar__Qux')
    // Then ($pos_subclass_sep  === 6)
    $pos_subclass_sep = strpos($trimmed_name, '__');
    if ( $pos_subclass_sep === false )
    {
        // Adjust for any `_` characters trimmed from the front.
        $pos_subclass_sep += $nchars_trimmed_front;

        // Base case:
        // The name given was a normal class name with no subclass notation.
        // However, there could still be subclasses, and because subclasses
        // call `require_once`, we too need to call `require_once` IF
        // such subclasses exist. (If they don't, this is all a no-op.)
        autoloader_require_once_if_eponymous_subclasses_exist(
            $class_unqual_name,
            $class_base_path,
            $class_file_path,
            $use_require_once
        );
        return;
    }

    // Suppose ($class_unqual_name === 'FooBar__Qux')
    // Then ($rpos_subclass_sep === 8)
    $rpos_subclass_sep = strlen($class_unqual_name) - ($pos_subclass_sep + 2);

    // Note that we don't use `try_` variables for
    // `$class_unqual_name` and `$class_base_path`.
    // That's because we DID find subclass notation in the input symbol,
    // so we want these variables to reflect that when other
    // features are analyzing them.
    //
    // So, if `$class_unqual_name` started as 'FooBar__Qux',
    // then `autoloader_check_eponymous_trait_classes(...)`
    // (which must be called later in the sequence)
    // should be operating on 'FooBar', not 'FooBar__Qux'.

    // ex: 'FooBar__Qux' -> 'FooBar'
    $class_unqual_name = substr($class_unqual_name, 0, -$rpos_subclass_sep);

    // ex: '${project_dir}/html/Kickback/Something/FooBar__Qux' ->
    //     '${project_dir}/html/Kickback/Something/FooBar'
    $class_base_path   = substr($class_base_path, 0, -$rpos_subclass_sep);

    // ex: '${project_dir}/html/Kickback/Something/FooBar' ->
    //     '${project_dir}/html/Kickback/Something/FooBar.php'
    $try_class_file_path   = $class_base_path . '.php';

    // If we have a hit, actualize it!
    if ( file_exists($try_class_file_path ) ) {
        $class_file_path   = $try_class_file_path;
    }

    /// As there are more than one classes in a single file,
    /// we MUST use `require_once` instead of `require`.
    /** @phpstan-ignore booleanOr.rightAlwaysTrue */
    $use_require_once = $use_require_once || true;

    return;
}

/**
* Call `generic_autoload_function` instead.
*
* @return int  One of {AUTOLOAD_SUCCESS, AUTOLOAD_INCOMPLETE, AUTOLOAD_FAILURE, AUTOLOAD_IGNORED}
* @see \Kickback\InitializationScripts\generic_autoload_function
*/
function generic_autoload_function_impl(string $class_fqn, string $namespace_to_try, string $root_dir) : int
{
    // This implements a slightly modified version of PSR-4:
    //   https://www.php-fig.org/psr/psr-4/
    //   https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader-examples.md
    //
    // Ours is modified because we look up our files relative to the
    // document root by using the `"$root_dir/"` string.
    //
    // For the `\Kickback` namespace, $root_dir is `\Kickback\SCRIPT_ROOT`.
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
        return AUTOLOAD_INCOMPLETE;
    }

    if ("\\" !== substr($class_fqn, $namespace_name_len, 1)) {
        // No, move on to the next registered autoloader
        // We know this because:
        // $class_fqn should have a name separator ("\\") right after the
        // its namespace portion. But in this case, it's not at the correct
        // position for its namespace to match $namespace_to_try, so we know
        // that the class is in a different namespace.
        return AUTOLOAD_INCOMPLETE;
    }

    if (0 !== strncmp($namespace_to_try, $class_fqn, $namespace_name_len)) {
        // No, move on to the next registered autoloader
        return AUTOLOAD_INCOMPLETE;
    }

    // Weird workaround:
    // Sometimes using the `never` return type will cause the autoloader to be invoked.
    // This doesn't make sense though, because `never` is not a class, it's a built-in
    // PHP type that indicates that a function will "never" return.
    // So we'll just... "not do this, but say we did". heh.
    // (It got worse: Apparently PHPStan does this whenever something uses
    // a type defined with @phpstan-type or @phpstan-type-import. Guh!
    // Well, I have no way to predict what those are in a general way. Sadge.)
    if ($class_fqn === 'never' || str_ends_with($class_fqn, '\\never')) {
        return AUTOLOAD_SUCCESS;
    }

    // Now we have proven that the $namespace_to_try is actually the namespace
    // mentioned in $class_fqn.
    $namespace_name = $namespace_to_try;

    // Base directory for the namespace prefix
    // Modification:
    // Don't do the below. It breaks if the entry-point script isn't in document root.
    //    $base_dir = __DIR__ . '/src/'; <- Don't do this.
    // Do this instead:
    $base_dir = $root_dir . DIRECTORY_SEPARATOR . $namespace_name . DIRECTORY_SEPARATOR;
    // Also note that we put our namespace prefix in the path.
    // That seems to diverge from PSR-4. (Maybe?)
    // But it makes sense in our file hierarchy.

    // Get the relative class name
    // ex: namespace === 'Kickback' implies 'Kickback\Something\ClassName' -> 'Something\ClassName'
    $relative_class_name = substr($class_fqn, $namespace_name_len+1);

    // We consider these variables prefixed with `starting_` to be immutable:
    // they won't change during the rest of the lookup calculations, and that's
    // helpful because we can use them in error reporting.
    // (If we can't find any files, even with bespoke lookups, then the
    // best option to report is the one that reflects the name passed into
    // the autoloader, hence the `starting_*` variables.)

    // ex: 'Something\ClassName' -> 'ClassName'
    $starting_class_unqual_name = strrchr($relative_class_name,'\\');
    if ( $starting_class_unqual_name === false ) {
        $starting_class_unqual_name = $relative_class_name;
    }

    // Replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    // ex:
    // if $base_dir is "${project_dir}/html/Kickback/" then:
    // 'Something\ClassName' -> '${project_dir}/html/Kickback/Something/ClassName'
    $starting_class_base_path   = $base_dir . str_replace('\\', DIRECTORY_SEPARATOR, $relative_class_name);

    // ex: '${project_dir}/html/Kickback/Something/ClassName' ->
    //     '${project_dir}/html/Kickback/Something/ClassName.php'
    $starting_class_file_path   = $starting_class_base_path . '.php';

    // Mirror these into mutable variables that may change
    // if the "starting" class doesn't exist and bespoke
    // features find a proper successor.
    $class_unqual_name = $starting_class_unqual_name;
    $class_base_path   = $starting_class_base_path;
    $class_file_path = null;
    if ( file_exists($starting_class_file_path) ) {
        $class_file_path = $starting_class_file_path;
    }

    // We avoid using `require_once` whenever possible:
    //
    // Normally autoloading is deterministic, and so `require` will only be
    // called once anyways.
    //
    // AND, it's possible that `require` will be more performant than `require_once`,
    // because `require_once` requires PHP to maintain a list of `require_once`
    // files that have already been included. Then it would use a lookup
    // (ex: hash) on newly seen symbols to know if they were already included or not.
    // Meanwhile, `require` doesn't need an internal list, and it doesn't
    // do any per-encounter lookups. This optimization might not matter, BUT,
    // it's usually a good idea to avoid using any more memory than we need to.
    //
    // So we want to use `require` as much as possible.
    //
    // But if two different symbols load the same file, then it is possible
    // for that file to be included multiple times, which is probably A Bad Idea.
    //
    // So in situations where duplicate loading _might actually happen_,
    // we will use `require_once` instead of `require`.
    //
    $use_require_once = false;

    // Kickback-specific features:
    if ( $namespace_to_try === 'Kickback' ) {
        $enable_eponymous_subclasses  = true;
        $enable_eponymous_trait_class = true;
    } else {
        $enable_eponymous_subclasses  = false;
        $enable_eponymous_trait_class = false;
    }

    // Note: Order-of-operations matters.
    // The subclass logic might change the basename by chopping
    // off a subclass suffix. At that point, it's a good idea
    // to check the stem of the class name (the part without the subclass)
    // against the trait-class logic.

    if ( $enable_eponymous_subclasses ) {
        autoloader_check_eponymous_subclasses(
            $class_unqual_name,
            $class_base_path,
            $class_file_path,
            $use_require_once
        );
    }

    if ( $enable_eponymous_trait_class ) {
        autoloader_check_eponymous_trait_classes(
            $class_unqual_name,
            $class_base_path,
            $class_file_path,
            $use_require_once
        );
    }

    // If we couldn't find any files to load,
    // then we are destined to produce errors.
    // We want the errors to provide a useful class file path,
    // even if it doesn't exist. So if `$class_file_path`
    // is null (e.g. we didn't find a file), then
    // we fill it with a value that most closely
    // matches the symbol the autoloader was handed:
    // `$starting_class_file_path`.
    //
    if (!isset($class_file_path)) {
        $class_unqual_name = $starting_class_unqual_name;
        $class_base_path   = $starting_class_base_path;
        $class_file_path   = $starting_class_file_path;
    }

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
    //
    // Exception to our exception: If the autoloader is running as part of
    // an analysis tool, like PHPStan, then we shouldn't emit fatal errors.
    // (We still use the `require` statement here, but catch any uncaught
    // exceptions at the autoloader's entry point: autoload_function.)
    //
    if ( "PHPSTAN" === PARENT_PROCESS_TYPE() )
    {
        // Analyser/PHPStan/Tool context.
        if (file_exists($class_file_path))
        {
            if (!$use_require_once) {
                require $class_file_path;
            } else {
                require_once $class_file_path;
            }
        }
        else
        {
            autoload_error(
                "WARNING: Class named '$class_fqn' could not be autoloaded ".
                "because the file '$class_file_path' does not exist.");
            return AUTOLOAD_FAILURE;
        }
    } else {
        // Ex: Web and CLI contexts
        // Attempt to include the conventional class path,
        // otherwise fail.
        if (!$use_require_once) {
            require $class_file_path;
        } else {
            require_once $class_file_path;
        }
    }

    // Original PSR-4 example code:
    // if the file exists, require it
    //if (file_exists($class_file_path)) {
    //    require $class_file_path;
    //}

    return AUTOLOAD_SUCCESS;
}

/**
* This is not intended to be called from outside of the `autoload_classes.php` initscript.
*
* This autoloader is primarily used to look in the ./Kickback folder for classes
* that are in the `Kickback` namespace. If the class's Fully Qualified Name (FQN)
* does not start with `Kickback`, then this will return AUTOLOAD_INCOMPLETE.
*
* Of course, it is parameterized so that it can work with any other namespace,
* but it can only check one namespace. (To check more namespaces, it has
* to be called repeatedly, or (not implemented) if the caller has enough
* information, they can use associative lookup to narrow valid namespaces
* until they have one to pass to this function.)
*
* @return int  One of {AUTOLOAD_SUCCESS, AUTOLOAD_INCOMPLETE, AUTOLOAD_FAILURE, AUTOLOAD_IGNORED}
*
* @see \Kickback\InitializationScripts\autoload_function
*/
function generic_autoload_function(string $class_fqn, string $namespace_to_try, string $root_dir) : int
{
    autoload_debug_trace(fn() => "CALL: generic_autoload_function('$class_fqn','$namespace_to_try','$root_dir')");
    autoload_debug_indent_more();

    $result = AUTOLOAD_FAILURE;
    $exc = null;
    try {
        $result = generic_autoload_function_impl($class_fqn, $namespace_to_try, $root_dir);
    } catch( \Throwable $oops ) {
        $exc = $oops;
    }

    autoload_debug_indent_less();
    autoload_debug_trace(
    function() use($result, $exc, $class_fqn, $namespace_to_try, $root_dir) {
        if ( is_null($exc) ) {
            $result_str = autoload_result_name($result);
            return "RETURN: `$result_str` from generic_autoload_function('$class_fqn','$namespace_to_try','$root_dir')";
        } else {
            return "THROW: an exception was thrown from generic_autoload_function('$class_fqn','$namespace_to_try','$root_dir')";
        }
    });

    if (!is_null($exc)) {
        throw $exc;
    }
    return $result;
}

/**
* Internal function that should only be called from `autoload_try_vendor_folder`.
*
* @return int  One of {AUTOLOAD_SUCCESS, AUTOLOAD_INCOMPLETE, AUTOLOAD_FAILURE, AUTOLOAD_IGNORED}
*/
function autoload_try_vendor_folder_impl(string $class_fqn) : int
{
    $this_func_name = __FUNCTION__;
    $vendor_dir_path = \Kickback\SCRIPT_ROOT . "/vendor";

    $try_realpath = realpath($vendor_dir_path);
    if ( !($try_realpath === false) ) {
        $vendor_dir_path = $try_realpath;
    }

    // In principle, the `scandir` function (that we call a bit later) already
    // checks for things like file existence and directory-ness.
    // Unfortunately, `scandir` might also have bugs that can create
    // OOM conditions, like in this run of PHPStan:
    // ```
    // /var/www/localhost/kickback-kingdom-website/html/Kickback/Common/Utility/FormToken.php
    //
    // mmap() failed: [12] Cannot allocate memory
    //
    // mmap() failed: [12] Cannot allocate memory
    // PHP Fatal error:  Out of memory (allocated 158267867136 bytes) (tried to allocate 4096 bytes) in /var/www/localhost/kickback-kingdom-website/html/Kickback/autoload_classes.php on line 159
    // Fatal error: Out of memory (allocated 158267867136 bytes) (tried to allocate 4096 bytes) in /var/www/localhost/kickback-kingdom-website/html/Kickback/autoload_classes.php on line 159
    // ```
    // In above error message, line 159 of `autoload_classes.php` was from
    // an earlier version of this file, and that line was where `scandir` was called.
    //
    // So, at the very least, we'll try to peel away all of the things that
    // might present a challenge to `scandir`, and only have it handle
    // input that is going to cause successful return.
    //
    // Edit: The above memory exhaustion error was probably caused by `scandir`
    // trying to enumerate an infinite (or near-infinite) list of file names
    // that are zero-length strings. This became clear when replacing `scandir`
    // with `readdir` and seeing what it was iterating over each time it
    // returned a new dir entry: `$file_name` would be set to "" and it
    // would do that over and over forever. In `scandir`'s case, it was
    // probably trying to allocate an immense array to fit all of those
    // return values!
    //
    // Using `readdir` now allows us to check for the empty file name and
    // terminate when it's encountered. It so far seems to work well if we
    // treat that bizarre value as a "end of listing" token. This is what
    // solved the problem! As a nice bonus, this approach does not require
    // allocating an array to transit the directory listing. (Though the
    // disk I/O probably dwarfs the allocation cost for all but the most
    // massive directory listings.)
    //
    if ( !file_exists($vendor_dir_path) ) {
        autoload_error("WARNING: Could not find ./vendor directory (full path: '$vendor_dir_path').");
        return AUTOLOAD_INCOMPLETE;
    }

    if ( !is_readable($vendor_dir_path) ) {
        autoload_error("WARNING: ./vendor directory is not readable (full path: '$vendor_dir_path').");
        return AUTOLOAD_INCOMPLETE;
    }

    if ( !is_dir($vendor_dir_path) ) {
        autoload_error("WARNING: ./vendor directory is not a directory (full path: '$vendor_dir_path').");
        return AUTOLOAD_INCOMPLETE;
    }

    // This used to be a call to `scandir` that would return an array.
    // However, that would return the aforementioned OOM error mentioning `mmap`.
    // So instead, we use `opendir+readdir+closedir` to avoid whatever internal
    // mishap was encountered by `scandir`.
    // (It was probably infinite-looping on an empty entry; see below "End of list" check.)
    $vendor_dir_handle = opendir($vendor_dir_path);
    if ( false === $vendor_dir_handle ) {
        autoload_error("WARNING: `opendir('$vendor_dir_path',...)` returned false!");
        trace_indent_more();
            autoload_error("Logic before this step already verified that the directory exists, is readable, and is _actually_ a directory.");
            autoload_error("So this error is caused by some file I/O issue that is not one of those things.");
        trace_indent_less();
        return AUTOLOAD_INCOMPLETE;
    }

    autoload_debug_trace(fn() => "Reading ./vendor directory entries.");
    autoload_debug_indent_more();
    $final_result = AUTOLOAD_INCOMPLETE;
    while(true)
    {
        $file_name = readdir($vendor_dir_handle);
        // @phpstan-ignore identical.alwaysFalse (PHPStan reports this 0-length clause as unnecessary because it's "always false". Actually, it will cause HORRIBLE INFINITE LOOPING AND/OR OOM when it does eventually evaluate to true. For shame! ;p)
        if ( false === $file_name || (0 === strlen($file_name)) ) {
            break; // End of list.
            // Note the check for 0 length!
            // This was probably what caused `scandir` to crash.
            // (It would have tried to allocate an infinite array of empty filenames.)
            // Why do we get empty filename entries?! I don't know.
            // But it happened, and it's VERY IMPORTANT that we check
            // for that case, because it causes infinite loops or OOM if we don't!
            // This, notably, occurred in the context of PHPStan using the
            // autoloader to locate class definitions.
        }

        autoload_debug_trace(fn() => "Checking ./vendor/$file_name");

        // Skip the composer directory. It is not a namespace.
        // Rather, it contains all of the packages installed by composer,
        // and those are autoloaded by composer's autoloader.
        if( $file_name === "composer"
        ||  $file_name === ".."
        ||  $file_name === "."
        ||  str_starts_with($file_name, ".git")
        ) {
            autoload_debug_indent_more();
                autoload_debug_trace(fn() => "Ignoring this directory because it is definitely NOT a namespace path element.");
            autoload_debug_indent_less();
            continue;
        }

        $file_path = $vendor_dir_path . "/" . $file_name;

        // If this fails... something is quite wrong, because `readdir`
        //   shouldn't return entries that don't exist.
        // This check could be useful as a check against the logic within this
        // function, because programmer errors like using a file's name
        // instead of its _path_ can make this function fail.
        // (Incidentally, the above is what prompted this check to exist.)
        if ( !file_exists($file_path) ) {
            autoload_error("./vendor/$file_name is does not exist (full path: '$file_path')");
            continue;
        }

        // If this fails, the file perms on the host system are probably messed up.
        if ( !is_readable($file_path) ) {
            autoload_error("./vendor/$file_name is not readable (full path: '$file_path')");
            continue;
        }

        // Skip anything that's not a directory.
        // Only directories could contain .php classes.
        if ( !is_dir($file_path) ) {
            autoload_debug_indent_more();
                autoload_debug_trace(fn() => "Ignoring this entry because it isn't a directory.");
            autoload_debug_indent_less();

            // This lacks a call to `autoload_error` because there is no harm
            // in having non-directories inside the `./vendor` directory.
            // We just need to be sure to skip them.
            continue;
        }
        $dir_name = $file_name;

        // Attempt to autoload the class using this namespace+directory.
        $try_result = generic_autoload_function($class_fqn, $dir_name, $vendor_dir_path);
        if(AUTOLOAD_INCOMPLETE !== $try_result)
        {
            $final_result = $try_result;
            break;
        }
    }
    autoload_debug_indent_less();
    autoload_debug_trace(fn() => "End of ./vendor directory entries.");

    closedir($vendor_dir_handle);

    return $final_result;
}

/**
* This is not intended to be called from outside of the `autoload_classes.php` initscript.
*
* It is an autoloader that checks the ./vendor directory.
*
* @return int  One of {AUTOLOAD_SUCCESS, AUTOLOAD_INCOMPLETE, AUTOLOAD_FAILURE, AUTOLOAD_IGNORED}
*/
function autoload_try_vendor_folder(string $class_fqn) : int
{
    autoload_debug_trace(fn() => "CALL: autoload_try_vendor_folder('$class_fqn')");
    autoload_debug_indent_more();

    $result = AUTOLOAD_FAILURE;
    $exc = null;
    try {
        $result = autoload_try_vendor_folder_impl($class_fqn);
    } catch( \Throwable $oops ) {
        $exc = $oops;
    }

    autoload_debug_indent_less();
    autoload_debug_trace(
    function() use($result, $exc, $class_fqn) {
        if ( is_null($exc) ) {
            $result_str = autoload_result_name($result);
            return "RETURN: `$result_str` from autoload_try_vendor_folder('$class_fqn')";
        } else {
            return "THROW: an exception was thrown from autoload_try_vendor_folder('$class_fqn')";
        }
    });

    if (!is_null($exc)) {
        throw $exc;
    }
    return $result;
}

/**
* Internal function that should only be called from `autoload_function`.
*
* @return int  One of {AUTOLOAD_SUCCESS, AUTOLOAD_INCOMPLETE, AUTOLOAD_FAILURE, AUTOLOAD_IGNORED}
*/
function autoload_function_impl(string $class_fqn) : int
{
    // Ignore symbols that tell us to exclude them from autoloading.
    // Right now, this is anything in the Kickback namespace that ends
    // with the suffix `_a`. This can be used to designate PHPStan local aliases.
    if (str_ends_with($class_fqn,'_a')
    &&  (  str_starts_with($class_fqn,'Kickback')
        || str_starts_with($class_fqn,'\\Kickback')))
    {
        return AUTOLOAD_IGNORED;
    }

    // Attempt to load Kickback classes first.
    // These are the highest priority.
    $result = generic_autoload_function($class_fqn, 'Kickback', \Kickback\SCRIPT_ROOT);
    if ( AUTOLOAD_INCOMPLETE !== $result ) {
        autoload_debug_trace(fn() => "Kickback namespace issued halt for autoload of class $class_fqn");
        return $result;
    }

    // Attempt to load classes from various namespaces in the ./vendor directory.
    $result = autoload_try_vendor_folder($class_fqn);
    if ( AUTOLOAD_INCOMPLETE !== $result ) {
        autoload_debug_trace(fn() => "./vendor folder issued halt for autoload of class $class_fqn");
        return $result;
    }

    // If nothing autoloaded in the above namespaces, then PHP's
    // logic should move on to the next autoloader (ex: composer's autoloader).
    // Note that we don't seem to have any guarantee about what order this
    // happens in, so it's possible that composer's autoloader will run
    // FIRST, then ours will run.
    // TODO: The undefined order-of-operations is undesirable. Ideally, we want our autoloader to run first!
    return AUTOLOAD_INCOMPLETE;
}


/**
* This is the autoload function that gets registered with `spl_autoload_register(...)`
*/
function autoload_function(string $class_fqn) : void
{
    $indent_lvl_save = trace_indent_lvl();
    autoload_debug_trace(fn() => "Begin autoload of class $class_fqn");
    autoload_debug_trace(fn() => "CALL: autoload_function('$class_fqn')");
    autoload_debug_indent_more();

    $result = AUTOLOAD_FAILURE;
    $exc = null;
    try {
        $result = autoload_function_impl($class_fqn);
    } catch( \Throwable $oops ) {
        $exc = $oops;
    }

    autoload_debug_indent_less();
    autoload_debug_trace(
    function() use($result, $exc, $class_fqn) {
        if ( is_null($exc) ) {
            $result_str = autoload_result_name($result);
            return "RETURN: `$result_str` from autoload_function('$class_fqn')";
        } else {
            return "THROW: an exception was thrown from autoload_function('$class_fqn')";
        }
    });
    autoload_debug_trace(
    function() use($result, $class_fqn) {
        $found_str = "";
        switch($result)
        {
            case AUTOLOAD_SUCCESS:    $found_str = " (success! class found.)"; break;
            case AUTOLOAD_INCOMPLETE: $found_str = " (not found)"; break;
            case AUTOLOAD_FAILURE:    $found_str = " (class file found, but had loading errors)"; break;
            case AUTOLOAD_IGNORED:    $found_str = " (symbol excluded from autoloading)"; break;
        }
        return "End autoload of class $class_fqn$found_str";
    });
    autoload_debug_trace(fn() => "");
    trace_indent_lvl($indent_lvl_save); // Just in case we messed up our nesting.

    if (!is_null($exc)) {
        if ( "PHPSTAN" === PARENT_PROCESS_TYPE() )
        {
            // Having PHPStan die because there's a typo in a `use` statement,
            // class name, `namespace` statement, filename, etc
            // can feel bonecrushing, especially if there are slow-to-process
            // files in the project. And it's supposed to just report errors,
            // not stop on them, so throwing would cause these errors to have
            // a different kind of behavior than everything else PHPStan reports.
            // So we try to ensure that no (uncaught) exceptions will be thrown
            // while PHPStan is operating.
            autoload_error($exc->getMessage());
        }
        else {
            // The more normal, strict, path:
            // Fail fast if there is something very wrong.
            throw $exc;
        }
    }
}

spl_autoload_register("\Kickback\InitializationScripts\autoload_function");

?>
