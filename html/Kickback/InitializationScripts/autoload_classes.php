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

// Ex: Path = 'Kickback/Foo/Bar/MyClass.php'
define('Kickback\InitializationScripts\AUTOLOAD_IDX_MAIN_CLASS_FQN', 0); // 'Kickback\Foo\Bar\MyClass'
define('Kickback\InitializationScripts\AUTOLOAD_IDX_BASE_PATH',      1); // '${project_dir}/html/Kickback/Foo/Bar/MyClass'
define('Kickback\InitializationScripts\AUTOLOAD_IDX_FILE_PATH',      2); // '${project_dir}/html/Kickback/Foo/Bar/MyClass.php'
define('Kickback\InitializationScripts\AUTOLOAD_IDX_UNQUAL_LEN',     3); // 7

/**
* @see \Kickback\Common\Primitives\Str::unchecked_blit_fwd  for unittests.
*/
function autoloader_str_unchecked_blit_fwd(string &$dst, int $dst_offset, string $src, int $src_offset, int $nchars) : void
{
    // $dostr = \strval($dst_offset);
    // $sostr = \strval($src_offset);
    // $ncstr = \strval($nchars);
    // $src_slice = \substr($src, $src_offset, $nchars);
    // echo "autoloader_str_unchecked_blit_fwd('$dst', $dostr, ...'$src_slice'..., $sostr, $ncstr);\n";

    // It is tempting to use `substr_replace` for this, but apparently
    // it does not optimize for non-shrinking/non-expanding cases:
    // https://github.com/php/php-src/issues/15376
    // https://wiki.php.net/rfc/working_with_substrings
    //
    // So, as terrible as this looks, it might actually be faster to
    // perform individual character setting operations. (Becuase it avoids
    // memory allocations, and those are _expensive_.)
    assert($nchars <= \strlen($src) - $src_offset);
    assert($nchars <= \strlen($dst) - $dst_offset);

    $i      = /* 0         +*/ $dst_offset;
    $offset = $src_offset  -   $dst_offset;
    $endpos = $dst_offset  +   $nchars;
    for (; $i < $endpos; $i++) {
        $dst[$i] = $src[$i+$offset];
    }
}


/**
* @see \Kickback\Common\Primitives\Str::unchecked_blit_rev  for unittests.
*/
function autoloader_str_unchecked_blit_rev(string &$dst, int $dst_offset, string $src, int $src_offset, int $nchars) : void
{
    // It is tempting to use `substr_replace` for this, but apparently
    // it does not optimize for non-shrinking/non-expanding cases:
    // https://github.com/php/php-src/issues/15376
    // https://wiki.php.net/rfc/working_with_substrings
    //
    // So, as terrible as this looks, it might actually be faster to
    // perform individual character setting operations. (Becuase it avoids
    // memory allocations, and those are _expensive_.)
    assert($nchars <= \strlen($src) - $src_offset);
    assert($nchars <= \strlen($dst) - $dst_offset);

    $i      = $dst_offset  +  $nchars  - 1;
    $offset = $src_offset  -  $dst_offset;
    $endpos = $dst_offset;
    for(; $i >= $endpos; $i--) {
        $dst[$i] = $src[$i+$offset];
    }
}

/**
* @see \Kickback\Common\Primitives\Str::unchecked_blit  for unittests.
*/
function autoloader_str_unchecked_blit(string &$dst, int $dst_offset, string $src, int $src_offset, int $nchars) : void
{
    autoloader_str_unchecked_blit_fwd($dst, $dst_offset, $src, $src_offset, $nchars);
}


/**
* @see \Kickback\Common\Primitives\Str::unchecked_shift_by  for unittests.
*/
function autoloader_str_unchecked_shift_by(string &$subject, int $by, int $offset, int $nchars) : void
{
    if ( $by < 0 ) {
        autoloader_str_unchecked_blit_fwd($subject, $offset+$by, $subject, $offset, $nchars);
    } else {
        autoloader_str_unchecked_blit_rev($subject, $offset+$by, $subject, $offset, $nchars);
    }
}

/**
* @see \Kickback\Common\Primitives\Str::substr_replace_inplace  for documentation and unittests.
*/
function autoloader_substr_replace_inplace(string &$subject, string $replacement, int $offset, ?int $length = null) : void
{
    // Adjust negative offset into equivalent positive offset.
    // This is (very indirectly) important for detecting if in-place operation is possible.
    $dst_len = \strlen($subject);
    $dst_offset = $offset;
    if ( $dst_offset < 0 ) {
        // By commutative property of addition,
        // the below is equivalent to
        // $dst_offset = $dst_len + $dst_offset;
        $dst_offset += $dst_len;
    }

    // Fill in length values.
    // This is important for detecting if in-place operation is possible.
    $dst_nchars = $dst_len - $dst_offset;
    if (isset($length)) {
        if ( $length >= 0 ) {
            $dst_nchars = $length;
        } else {
            // This can create a negative maximum
            // if `abs($length) > ($dst_len - $dst_offset)`.
            $dst_nchars += $length;
            // Not sure how \substr_replace handles it, but
            // it won't matter for determining in-place operation:
            // it'll always be a string growth, so we'll just end
            // up calling `\substr_replace` with original args.
            if ( $dst_nchars < 0 ) {
                $dst_nchars = 0;
            }
        }
    }

    // Finally, we can detect if in-place operation is possible.
    $src_nchars = \strlen($replacement);
    // echo 'in autoloader_substr_replace_inplace'."\n";
    // echo "src_nchars = $src_nchars\n";
    // echo "dst_offset = $dst_offset\n";
    // echo "dst_nchars = $dst_nchars\n";
    // echo "dst_len = $dst_len\n";
    if ( $src_nchars <= $dst_nchars ) {
        // Shrinking or length-invariant operation.
        autoloader_str_unchecked_blit($subject, $dst_offset, $replacement, 0, $src_nchars);
        $by = $src_nchars - $dst_nchars;
        if ( $by !== 0 ) {
            assert($by < 0);
            $dst_offset += $dst_nchars;
            autoloader_str_unchecked_shift_by($subject, $by, $dst_offset, $dst_len - $dst_offset);
            $subject = \substr($subject,0,$by);
        }
    } else {
        // Result is increased in length -> allocation required; just use `\substr_replace`.
        $subject = \substr_replace($subject, $replacement, $offset, $length);
    }
}

// TODO: Is there some way to get PHPStan to understand this array in terms
// of the constant names, and not just literal integer values?
// (NOTE: We can't use @phpstan-type, both because it might only work
// on class declarations, AND because it invokes the autoloader!)
// (At least, as of this writing on 2025-07-29 with PHPStan 1.11.x)
/**
* Note: This may or may not update `$paths[AUTOLOAD_IDX_FILE_PATH]`;
* use `autoloader_file_path($paths)` to acquire the correct value for that,
* which will, in that process, update the stored path value.
*
* @param array{0: string, 1: string, 2: string, 3: int} $paths
*/
function autoloader_unqual_name(array &$paths, ?string $new_name = null) : string
{
    $main_class_fqn = $paths[AUTOLOAD_IDX_MAIN_CLASS_FQN];
    $unqual_len     = $paths[AUTOLOAD_IDX_UNQUAL_LEN];
    if (!isset($new_name)) {
        return \substr($main_class_fqn, -$unqual_len);
    }

    $file_basepath = $paths[AUTOLOAD_IDX_BASE_PATH];

    // Optimization: Look for it to just be a shortening.
    $new_len = \strlen($new_name);
    if ( $new_len < $unqual_len ) {
        $old_name = \substr($main_class_fqn, -$unqual_len);
        if ( \str_starts_with($old_name, $new_name) ) {
            $shrink_by = ($unqual_len - $new_len);
            $paths[AUTOLOAD_IDX_MAIN_CLASS_FQN] = \substr($main_class_fqn, 0, -$shrink_by);
            $paths[AUTOLOAD_IDX_BASE_PATH]      = \substr($file_basepath,  0, -$shrink_by);
            $paths[AUTOLOAD_IDX_UNQUAL_LEN]     = $new_len;
            // Optimization: AUTOLOAD_IDX_FILE_PATH is NOT updated; it would require a string concatenation.
            // Use the proper accessor `autoloader_file_path($paths)` to get the value as-needed (lazy evaluation).
            return $new_name;
        }
    }

    // Optimization: AUTOLOAD_IDX_FILE_PATH is NOT updated; it would require a string concatenation.
    // Use the proper accessor `autoloader_file_path($paths)` to get the value as-needed (lazy evaluation).
    autoloader_substr_replace_inplace($main_class_fqn, $new_name, -$unqual_len, $unqual_len);
    autoloader_substr_replace_inplace($file_basepath,  $new_name, -$unqual_len, $unqual_len);

    $paths[AUTOLOAD_IDX_MAIN_CLASS_FQN] = $main_class_fqn;
    $paths[AUTOLOAD_IDX_BASE_PATH]      = $file_basepath;
    $paths[AUTOLOAD_IDX_UNQUAL_LEN]     = $new_len;
    return $new_name;
}

/**
* @param array{0: string, 1: string, 2: string, 3: int} $paths
*/
function autoloader_dir_path(array $paths) : string
{
    $file_basepath = $paths[AUTOLOAD_IDX_BASE_PATH];
    $unqual_len    = $paths[AUTOLOAD_IDX_UNQUAL_LEN];
    if ( $unqual_len < \strlen($file_basepath) ) {
        return \substr($file_basepath, -($unqual_len+1));
    } else {
        return $file_basepath;
    }
}

/**
* @param array{0: string, 1: string, 2: string, 3: int} $paths
*/
function autoloader_file_path(array &$paths) : string
{
    $file_path     = $paths[AUTOLOAD_IDX_FILE_PATH];
    $file_basepath = $paths[AUTOLOAD_IDX_BASE_PATH];
    $unqual_len    = $paths[AUTOLOAD_IDX_UNQUAL_LEN];
    if ( \str_starts_with($file_path, $file_basepath)
    &&  (\strlen($file_path) === \strlen($file_basepath)+4)) {
        return $file_path;
    }

    // Update the full path using \substr_replace
    // since it might be able to do some inplace operations
    // whereas naive string concatenation (probably)
    // guarantees memory allocation (unless the interpreter is REALLY clever).
    $new_unqual_name = \substr($file_basepath, -$unqual_len);
    $old_unqual_start = \strrpos($file_path, '/');
    if ($old_unqual_start === false) {
        $old_unqual_start = 0;
    }
    autoloader_substr_replace_inplace($file_path, $new_unqual_name, $old_unqual_start+1, -4);
    $paths[AUTOLOAD_IDX_FILE_PATH] = $file_path;
    return $file_path;
}

//TODO: Move those things into Str:: and Meta_::
//TODO: Replace function arguments with the array thingie; rework functions.

function autoloader_decl_exists(string $decl_fqn) : bool
{
    autoload_debug_trace(fn() => "CALL: autoloader_decl_exists($decl_fqn)");
    autoload_debug_indent_more();

    $ce = class_exists($decl_fqn, false);
    $ie = interface_exists($decl_fqn, false);
    $te = trait_exists($decl_fqn, false);
    $ee = enum_exists($decl_fqn, false);
    $decl_exists = $ce || $ie || $te || $ee;
    if ( true === AUTOLOAD_DO_DEBUG_ECHO() ) {
        $sce = $ce ? 'true' : 'false';
        $sie = $ie ? 'true' : 'false';
        $ste = $te ? 'true' : 'false';
        $see = $ee ? 'true' : 'false';
        $sde = $decl_exists ? 'true' : 'false';
        autoload_debug_trace(fn() => "class_exists?      $sce");
        autoload_debug_trace(fn() => "interface_exists?  $sie");
        autoload_debug_trace(fn() => "trait_exists?      $ste");
        autoload_debug_trace(fn() => "enum_exists?       $see");
        autoload_debug_trace(fn() => "------------------------");
        autoload_debug_trace(fn() => "decl_exists?       $sde");
    }
    autoload_debug_trace(fn() => "CALL: autoloader_decl_exists()");
    autoload_debug_indent_less();
    autoload_debug_trace(fn() => "RETURN: $decl_exists from autoloader_decl_exists()");
    return $decl_exists;
}

/**
* @param array{0: string, 1: string, 2: string, 3: int} $paths
* @param callable&string $autoloader_check_eponymous_fn
*/
function autoloader_check_eponymous_something(
    callable  $autoloader_check_eponymous_fn,
    array     &$paths
) : bool
{
    if ( true === AUTOLOAD_DO_DEBUG_ECHO() ) {
        $main_class_fqn = $paths[AUTOLOAD_IDX_MAIN_CLASS_FQN];
        $base_path      = $paths[AUTOLOAD_IDX_BASE_PATH];
        $file_path      = $paths[AUTOLOAD_IDX_FILE_PATH];
        $unqual_len     = $paths[AUTOLOAD_IDX_UNQUAL_LEN];
        $unqual_name    = autoloader_unqual_name($paths);
        autoload_debug_trace(fn() => "CALL: $autoloader_check_eponymous_fn(");
        autoload_debug_trace(fn() => "CALL:     \$paths[AUTOLOAD_IDX_MAIN_CLASS_FQN]:  '$main_class_fqn',");
        autoload_debug_trace(fn() => "CALL:     \$paths[AUTOLOAD_IDX_BASE_PATH]:       '$base_path',");
        autoload_debug_trace(fn() => "CALL:     \$paths[AUTOLOAD_IDX_FILE_PATH]:       '$file_path',");
        autoload_debug_trace(fn() => "CALL:     \$paths[AUTOLOAD_IDX_UNQUAL_LEN]:      '".\strval($unqual_len)."',");
        autoload_debug_trace(fn() => "CALL:     class unqualified name:                '$unqual_name'");
        autoload_debug_trace(fn() => "CALL: )");
        autoload_debug_indent_more();
    }

    $result = false;
    $exc = null;
    try {
        $result = \call_user_func_array($autoloader_check_eponymous_fn, [&$paths]);
        assert(is_bool($result));
    } catch( \Throwable $oops ) {
        $exc = $oops;
    }
    assert(is_bool($result));

    autoload_debug_indent_less();
    if ( true === AUTOLOAD_DO_DEBUG_ECHO() ) {
        $main_class_fqn = $paths[AUTOLOAD_IDX_MAIN_CLASS_FQN];
        $base_path      = $paths[AUTOLOAD_IDX_BASE_PATH];
        $file_path      = $paths[AUTOLOAD_IDX_FILE_PATH];
        $unqual_len     = $paths[AUTOLOAD_IDX_UNQUAL_LEN];
        $unqual_name    = autoloader_unqual_name($paths);
        $result_str = ($result ? 'true' : 'false');
        if ( !isset($exc) ) {
            autoload_debug_trace(fn() => "RETURN: $autoloader_check_eponymous_fn(");
            autoload_debug_trace(fn() => "RETURN:     \$paths[AUTOLOAD_IDX_MAIN_CLASS_FQN]:  '$main_class_fqn',");
            autoload_debug_trace(fn() => "RETURN:     \$paths[AUTOLOAD_IDX_BASE_PATH]:       '$base_path',");
            autoload_debug_trace(fn() => "RETURN:     \$paths[AUTOLOAD_IDX_FILE_PATH]:       '$file_path',");
            autoload_debug_trace(fn() => "RETURN:     \$paths[AUTOLOAD_IDX_UNQUAL_LEN]:      '".\strval($unqual_len)."',");
            autoload_debug_trace(fn() => "RETURN:     class unqualified name:                '$unqual_name'");
            autoload_debug_trace(fn() => "RETURN: ) returns: $result_str");
        } else {
            autoload_debug_trace(fn() => "THROW: an exception was thrown from");
            autoload_debug_trace(fn() => "THROW:  $autoloader_check_eponymous_fn(");
            autoload_debug_trace(fn() => "THROW:      \$paths[AUTOLOAD_IDX_MAIN_CLASS_FQN]:  '$main_class_fqn',");
            autoload_debug_trace(fn() => "THROW:      \$paths[AUTOLOAD_IDX_BASE_PATH]:       '$base_path',");
            autoload_debug_trace(fn() => "THROW:      \$paths[AUTOLOAD_IDX_FILE_PATH]:       '$file_path',");
            autoload_debug_trace(fn() => "THROW:      \$paths[AUTOLOAD_IDX_UNQUAL_LEN]:      '".\strval($unqual_len)."',");
            autoload_debug_trace(fn() => "THROW:      class unqualified name:                '$unqual_name'");
            autoload_debug_trace(fn() => "THROW:  )");
        }
    }

    if (isset($exc)) {
        throw $exc;
    }

    return $result;
}

/**
* @param array{0: string, 1: string, 2: string, 3: int} $paths
*/
function autoloader_check_eponymous_trait_classes_impl(array &$paths) : bool
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

    $class_unqual_name = autoloader_unqual_name($paths);
    $stem_paths  = $paths;
    $trait_paths = $paths;
    if ( \str_ends_with($class_unqual_name, 'Trait') ) {
        autoloader_unqual_name($stem_paths,  \substr($class_unqual_name, 0, -strlen('Trait')));
    }
    else { // $class_base_path does NOT end in 'Trait'
        autoloader_unqual_name($trait_paths,  $class_unqual_name . 'Trait');
    }

    $stem_file_path  = autoloader_file_path($stem_paths);
    $trait_file_path = autoloader_file_path($trait_paths);

    $stem_file_exists  = \file_exists($stem_file_path);
    $trait_file_exists = \file_exists($trait_file_path);

    $stem_unqual_name = autoloader_unqual_name($stem_paths);
    $symbol_is_class = ($class_unqual_name === $stem_unqual_name);

    if ( $symbol_is_class && !$stem_file_exists && $trait_file_exists ) {
        $paths = $trait_paths;
        return true;
    }
    return false;
}

/**
* @param array{0: string, 1: string, 2: string, 3: int} $paths
*/
function autoloader_check_eponymous_trait_classes(array &$paths) : bool
{
    return autoloader_check_eponymous_something(
        'Kickback\InitializationScripts\autoloader_check_eponymous_trait_classes_impl',
            $paths);
}

        // TODO: delete
        // $pos_subclass_sep_begin = \strlen($class_unqual_name);
        //
        // // How many characters do we chop off to arrive at
        // // the same location in our other pathy strings?
        // // Suppose ($class_unqual_name === 'FooBar_p67q9Baz__56a9vQux')
        // // Then on 1st pass: ($rpos_subclass_sep_begin === 10)
        // // And on 2nd pass:  ($rpos_subclass_sep_begin === 9) (Starts from 'FooBar_p67q9Baz')
        // $rpos_subclass_sep_begin = $len - $pos_subclass_sep_begin;
        //
        // // Find the corresponding begin-of-run
        // // in the `$try_file_main_decl_fqn` string.
        // // Suppose ($class_unqual_name === '\Kickback\FooBar_p67q9Baz__56a9vQux')
        // // Then on 1st pass: ($path_pos_subclass_sep_begin === 25)
        // // And on 2nd pass:  ($path_pos_subclass_sep_begin === 16)
        // $path_pos_subclass_sep_begin = \strlen($try_file_main_decl_fqn) - $rpos_subclass_sep_begin;
        //
        // // Trim the class declaration fqn.
        // $try_file_main_decl_fqn = \substr($try_file_main_decl_fqn,0,$path_pos_subclass_sep_begin);



/**
* @see \Kickback\Common\Primitives\Meta::generate_numbered_subclass_mask  for unittests.
*/
function autoloader_generate_numbered_subclass_mask(string $class_unqual_name): string
{
    static $STATE_REGULAR    = 0;
    static $STATE_UNDERSCORE = 1;
    static $STATE_SPLITTABLE = 2;
    static $ZEROES = '0000000000000000000000000000000000000000000000000000000000000000';
    static $ONES   = '1111111111111111111111111111111111111111111111111111111111111111';

    $len = \strlen($class_unqual_name);
    $zlen = \strlen($ZEROES);
    if ( $zlen < $len ) {
        $times = \intdiv($len,$zlen)+1;
        $ZEROES = \str_repeat($ZEROES,$times);
        $ONES   = \str_repeat($ONES,  $times);
    }

    $class_unqual_mask = $class_unqual_name;
    $state = $STATE_REGULAR;
    for($i = 0; $i < $len;)
    {
        // echo "class_unqual_name =\n";
        // echo "'$class_unqual_name'\n";
        // echo str_repeat(' ',$i+1)."^\n";
        // echo 'i = '.\strval($i)."\n";
        if( $state === $STATE_REGULAR )
        {
            // echo "STATE_REGULAR\n";
            // We use \strcspn instead of \strpos because \strcspn doesn't return `false`,
            // so we can save cycles and simplify code by not needing to check for `false`.
            $nchars = \strcspn($class_unqual_name,'_',$i);
            if ( $nchars > 0 ) {
                autoloader_str_unchecked_blit($class_unqual_mask, $i, $ZEROES, 0, $nchars-1);
                //echo 'unqual = '.$class_unqual_name[$i + $nchars-1]."\n";
                $class_unqual_mask[$i + $nchars-1] = '1';
            }
            // $subst_zeroes = \substr($ZEROES,$nchars-1);
            // $class_unqual_mask = \substr_replace(
            //     $class_unqual_mask, $subst_zeroes, $i, $nchars-1);

            $i += $nchars;
            if ( $i >= $len ) {
                break;
            }

            $state = $STATE_UNDERSCORE;
            continue;
        }
        else
        if ( $state === $STATE_UNDERSCORE )
        {
            // echo "STATE_UNDERSCORE\n";
            $nchars = \strspn($class_unqual_name,'_',$i);
            autoloader_str_unchecked_blit($class_unqual_mask, $i, $ZEROES, 0, $nchars);
            // $subst_zeroes = \substr($ZEROES,$nchars);
            // $class_unqual_mask = \substr_replace(
            //     $class_unqual_mask, $subst_zeroes, $i, $nchars);
            $i += $nchars;
            if ( $i >= $len ) {
                break;
            }

            if ( \ctype_upper($class_unqual_name[$i]) ) {
                $state = $STATE_REGULAR;
            } else { // lowercase letters, numbers
                $state = $STATE_SPLITTABLE;
            }
            continue;
        }
        else
        if ( $state === $STATE_SPLITTABLE )
        {
            // echo "STATE_SPLITTABLE\n";
            $nchars = \strspn($class_unqual_name,'abcdefghijklmnopqrstuvwxyz0123456789',$i);
            autoloader_str_unchecked_blit($class_unqual_mask, $i, $ONES, 0, $nchars);
            // $subst_ones = \substr($ONES,$nchars);
            // $class_unqual_mask = \substr_replace(
            //     $class_unqual_mask, $subst_ones, $i, $nchars);
            $i += $nchars;
            if ( $i >= $len ) {
                break;
            }

            if ( \ctype_upper($class_unqual_name[$i]) ) {
                $state = $STATE_REGULAR;
            } else {
                $state = $STATE_UNDERSCORE;
            }
            continue;
        }
    }
    return $class_unqual_mask;
}


/**
* @param array{0: string, 1: string, 2: string, 3: int} $paths
* @return bool  `true` if file-to-load is found, `false` if not found
*/
function autoloader_check_eponymous_numbered_subclasses_impl(array &$paths) : bool
{
    // -- Eponymous Numbered Classes --
    //
    // Whenever we encounter a class whose file has
    // one or more underscores in its name, followed by a sequence of
    // numbers and alphabetic separators (which must start with a lowercase
    // letter each time), then we will check for any files that are a prefix
    // of the classname that we are looking for (as long as that prefix
    // is at least as long as the name up to the first underscore).
    // Examples:
    //
    // If we are looking for this class `Kickback\Foo\Bar\MyClass_0x0y`,
    // then we check in these files:
    // * `Kickback/Foo/Bar/MyClass_0x0y.php`
    // * `Kickback/Foo/Bar/MyClass_0x0.php`
    // * `Kickback/Foo/Bar/MyClass_0x.php`
    // * `Kickback/Foo/Bar/MyClass_0.php`
    // * `Kickback/Foo/Bar/MyClass.php`
    //
    // If we are looking for this class `Kickback\Foo\Bar\MyClass_a2bC5`,
    // then we check in these files:
    // * `Kickback/Foo/Bar/MyClass_a2bC5.php`
    // * `Kickback/Foo/Bar/MyClass_a2bC.php`
    // * `Kickback/Foo/Bar/MyClass_a2b.php`
    // * `Kickback/Foo/Bar/MyClass_a2.php`
    // * `Kickback/Foo/Bar/MyClass_a.php`
    // * `Kickback/Foo/Bar/MyClass.php`
    //
    // Also, sequences that start with uppercase letters will signal
    // an end to numeric distinctions:
    // If we are looking for this class `Kickback\Foo\Bar\MyClass_5t9SomeThing`,
    // then we check in these files:
    // * `Kickback/Foo/Bar/MyClass_5t9SomeThing.php`
    // * `Kickback/Foo/Bar/MyClass_5t9.php`
    // * `Kickback/Foo/Bar/MyClass_5t.php`
    // * `Kickback/Foo/Bar/MyClass_5.php`
    // * `Kickback/Foo/Bar/MyClass.php`
    //
    // (Note that we do NOT check for things like
    // `Kickback/Foo/Bar/MyClass_5t9SomeTh.php` or
    // `Kickback/Foo/Bar/MyClass_5t9Some.php`)

    // Note that unlike with double-underscore subclass notation,
    // it DOES make sense to check classnames that begin with
    // underscores. So stuff like `Kickback\Foo\Bar\_p67q9`
    // should also look for:
    // * `Kickback/Foo/Bar/_p67q9.php`
    // * `Kickback/Foo/Bar/_p67q.php`
    // * `Kickback/Foo/Bar/_p67.php`
    // * `Kickback/Foo/Bar/_p6.php`
    // * `Kickback/Foo/Bar/_p.php`
    // * (But then avoid looking for `_.php` or `.php`
    //   because those don't really make sense.)

    $spot_check = autoloader_file_path($paths);
    if ( \file_exists($spot_check) ) {
        return true;
    }

    $try_paths = $paths;
    $class_unqual_name = autoloader_unqual_name($paths);

    // Generate a mask for valid split-points
    // Suppose ($class_unqual_name === 'FooBar_p67q9Baz__56a9vQux')
    // then    ($class_unqual_mask === '0000010111110010011111001')
    $class_unqual_mask = autoloader_generate_numbered_subclass_mask($class_unqual_name);

    // TODO: I feel like this whole while-loop could be moved into a separate
    // function and subjected to unittesting. It would just need to accept
    // a generic "glob" function that could be replaced with a mock object
    // during testing. Perhaps the mock object could be given a list of
    // "file" names that are effectively test data, then it would just
    // "glob" by chopping off the * and scanning through the list by
    // applying \str_starts_with repeated. Simple. It'd probably make
    // sense to use \glob as a way to implement a "return all entries with prefix"
    // function, since globbing is more complicated in general, and we
    // don't need to test that additional complexity.
    while(true)
    {
        // Suppose ($class_unqual_name === 'FooBar_p67q9Baz__56a9vQux')
        // On 1st pass:     ($len === 25)
        // And on 2nd pass: ($len === 15)
        // And on 3rd pass: ($len === 0)
        $len = \strlen($class_unqual_name);

        // Find right-most run of underscores.
        // Suppose ($class_unqual_name === 'FooBar_p67q9Baz__56a9vQux')
        // Then on 1st pass: ($pos_subclass_sep_end === 16)
        // And on 2nd pass:  ($pos_subclass_sep_end === 6)
        // And on 3rd pass:  ($pos_subclass_sep_end === false)
        $pos_subclass_sep_end = \strrpos($class_unqual_name, '_');
        if ( $pos_subclass_sep_end === false ) {
            // Base case:
            // The name given was a normal class name with no numbered/subclass notation.
            return false;
        }

        // Mark the right-side (end) of the run.
        // Suppose ($class_unqual_name === 'FooBar_p67q9Baz__56a9vQux')
        // Then on 1st pass: ($pos_subclass_sep_end === 16+1 === 17)
        // And on 2nd pass:  ($pos_subclass_sep_end === 6+1 === 7)
        $pos_subclass_sep_end++;

        // Mark the left-side (begin) of the run.
        // Suppose ($class_unqual_name === 'FooBar_p67q9Baz__56a9vQux')
        // Then on 1st pass: ($pos_subclass_sep_begin === 15)
        // And on 2nd pass:  ($pos_subclass_sep_begin === 6)
        $class_unqual_name = \rtrim(\substr($class_unqual_name,0,$pos_subclass_sep_end),'_');

        // Update our exploratory paths object.
        autoloader_unqual_name($try_paths, $class_unqual_name);

        // Check for lack of numbering to the right of the underscore.
        //
        // This can technically be detected earlier when we finish
        // calculating `$pos_subclass_sep_end`.
        //
        // However, we can't recover from it until we've figured out
        // the `$pos_subclass_sep_begin` value or trimmed classname
        // AND have sychronized the `$try_file_main_decl_fqn` string
        // with `$try_class_unqual_name` to ensure that `$rpos`
        // values will mean the same thing on the next pass.
        if ( $pos_subclass_sep_end === $len ) {
            // The class name ended with underscores.
            // It's either:
            // * Not numbered
            // * The numbering is further to the left.
            //
            // We can check the 2nd case by trimming the string, then
            // retrying with another pass. This will also indirectly
            // test the 1st case, because the trimmed string will
            // fail the "has an underscore" test (above) if the
            // rest of it contains no numbering notation.
            continue;
        }

        $found_path = null;
        $file_path_prefix = $try_paths[AUTOLOAD_IDX_BASE_PATH];
        $candidate_file_list = \glob($file_path_prefix . '*.php');
        if ( $candidate_file_list === false ) {
            continue;
        }

        $file_basepath = $paths[AUTOLOAD_IDX_BASE_PATH];
        foreach($candidate_file_list as $candidate_file_path)
        {
            // 4 because it must be long enough to have `.php` extension.
            // (and also be non-empty)
            if (\strlen($candidate_file_path) <= 4) {
                continue;
            }

            // chop off the .php
            $filename_sans_ext = \substr($candidate_file_path,0,-4);

            // check for it to be a true prefix.
            // It might not be, ex:
            // Starting with 'FooBar_p67q9Baz__56a9vQux'
            // \glob('FooBar_p67q9Baz*.php')
            // then yields 'FooBar_p67q9Baz__12a.php' -> 'FooBar_p67q9Baz__12a'
            // But 'FooBar_p67q9Baz__12a' is not a prefix of 'FooBar_p67q9Baz__56a9vQux'
            // It is something like a sibling file, but we aren't looking for those.
            if (!\str_starts_with($file_basepath, $filename_sans_ext)) {
                continue;
            }

            // This shouldn't really happen, but if it does,
            // we should ditch these.
            if ( !\is_file($candidate_file_path) ) {
                continue;
            }

            // Test to ensure the file is at a sensible breaking point.
            //
            // Examples for 'FooBar_p67q9Baz__56a9vQux' (1st pass):
            //   'FooBar_p67q9Baz__56a9vQux'-> OK (but shouldn't happen b/c spot-check at start)
            //   'FooBar_p67q9Baz__56a9vQu' -> Not OK
            //   'FooBar_p67q9Baz__56a9v'   -> OK
            //   'FooBar_p67q9Baz__56a'     -> OK
            //   'FooBar_p67q9Baz__56'      -> OK
            //   'FooBar_p67q9Baz__'        -> Not OK
            //   'FooBar_p67q9Baz'          -> OK
            //
            // Examples for 'FooBar_p67q9Baz' (2nd pass):
            //   'FooBar_p67q9Baz' -> OK
            //   'FooBar_p67q9Ba'  -> Not OK
            //   'FooBar_p67q9'    -> OK
            //   'FooBar_p67q'     -> OK
            //   'FooBar_p'        -> OK
            //   'FooBar_'         -> Not OK
            //   'FooBar'          -> OK
            //
            // Can we split the path here?
            // If not, move on to the next file.
            $suffix_len = \strlen($file_basepath) - \strlen($filename_sans_ext);
            $len = \strlen($class_unqual_name);
            $pos = $len - $suffix_len;
            if (!(0 < $pos && $class_unqual_mask[$pos-1] === '1')) {
                continue;
            }

            // Make sure it's the LONGEST possibility
            // (check this AFTER ensuring it's valid...
            // we don't want to exclude the valid path because
            // we were looking at the longest invalid one!)
            if ( !isset($found_path)
            ||  \strlen($found_path) < \strlen($filename_sans_ext) ) {
                $found_path = $filename_sans_ext;
            }
        }

        if (!isset($found_path)) {
            // If we didn't find anything, keep searching
            // for more notations further to the left in the classname.
            continue;
        }

        // Update $try_paths with this.
        $pos = \strrpos($found_path, '/');
        if ( $pos === false ) {
            $pos = 0;
        } else {
            $pos++;
        }
        $unqual_prefix = \substr($found_path,$pos);
        autoloader_unqual_name($try_paths,$unqual_prefix);

        // We found something to load from.
        // Commit.
        $paths = $try_paths;
        return true;
    }
}

/**
* @param array{0: string, 1: string, 2: string, 3: int} $paths
* @return bool  `true` if file-to-load is found, `false` if not found
*/
function autoloader_check_eponymous_numbered_subclasses(array &$paths) : bool
{
    return autoloader_check_eponymous_something(
        'Kickback\InitializationScripts\autoloader_check_eponymous_numbered_subclasses_impl',
            $paths);
}

/**
* @param array{0: string, 1: string, 2: string, 3: int} $paths
*/
function autoloader_check_eponymous_subclasses_impl(array &$paths) : bool
{
    // -- Eponymous Subclasses --
    //
    // Whenever we encounter a class whose file has
    // double underscores in its name, ex: `Kickback\Foo\Bar\MyClass__SubClass.php`,
    // then we also check the lhs of that token: `Kickback\Foo\Bar\MyClass.php`.

    $class_unqual_name = autoloader_unqual_name($paths);

    // Trim the class name so that stuff like `__FooBar__Qux` won't
    // confuse the algorithm.
    $trimmed_name = \ltrim($class_unqual_name,'_');
    $nchars_trimmed_front = (\strlen($class_unqual_name) - \strlen($trimmed_name));

    // Suppose ($class_unqual_name === 'FooBar__Qux')
    // Then ($pos_subclass_sep  === 6)
    $pos_subclass_sep = \strpos($trimmed_name, '__');
    if ( $pos_subclass_sep === false )
    {
        // Base case:
        // The name given was a normal class name with no subclass notation.
        return false;
    }

    // Suppose ($class_unqual_name === '__FooBar__Qux__'),
    // Then ($trimmed_name === 'FooBar__Qux__')  (strlen('FooBar__Qux__') === 13)
    // And  ($pos_subclass_sep === 6)
    // And  ($rpos_subclass_sep === (13 - 6) ===  7  === strlen('__Qux__'))
    $rpos_subclass_sep = \strlen($trimmed_name) - ($pos_subclass_sep);

    // Note that we unconditionally change the `$paths` variable.
    // That's because we DID find subclass notation in the input symbol,
    // so we want these variables to reflect that when other
    // features are analyzing them.
    //
    // So, if `$class_unqual_name` started as 'FooBar__Qux',
    // then `autoloader_check_eponymous_trait_classes(...)`
    // (which must be called later in the sequence)
    // should be operating on 'FooBar', not 'FooBar__Qux'.

    // ex: 'FooBar__Qux' -> 'FooBar'
    $class_unqual_name = \substr($class_unqual_name, 0, -$rpos_subclass_sep);

    // ex:
    //   base_path:       '${project_dir}/html/Kickback/Something/FooBar__Qux' ->
    //                        '${project_dir}/html/Kickback/Something/FooBar'
    //   main_class_fqn:  'Kickback\Something\FooBar__Qux' -> 'Kickback\Something\FooBar'
    //   file_path:       '${project_dir}/html/Kickback/Something/FooBar__Qux.php' ->
    //                        '${project_dir}/html/Kickback/Something/FooBar.php'
    autoloader_unqual_name($paths, $class_unqual_name);

    // If we have a hit, actualize it!
    if ( \file_exists(autoloader_file_path($paths)) ) {
        return true;
    }

    return false;
}

/**
* @param array{0: string, 1: string, 2: string, 3: int} $paths
*/
function autoloader_check_eponymous_subclasses(array &$paths) : bool
{
    return autoloader_check_eponymous_something(
        'Kickback\InitializationScripts\autoloader_check_eponymous_subclasses_impl',
            $paths);
}

// If successful:
//   Returns the index that the interface notation was located at.
//   (Which will also be where an `I` was removed from the string.)
//
// If unsuccessful:
//   Returns \PHP_INT_MAX
//
function autoloader_eponymous_interfaces_transform(string &$class_unqual_name, int $start_at = 0) : int
{
    $pivot = $start_at;
    $len = \strlen($class_unqual_name);
    if ( $len < 2 ) {
        return \PHP_INT_MAX;
    }

    while(true)
    {
        // There must be at least 2 characters left
        // in the name for an eponymous interface
        // to even be technically possible.
        if( $len <= ($pivot + 1) ) {
            $pivot = \PHP_INT_MAX;
            break;
        }

        $first_ch  = $class_unqual_name[$pivot + 0];
        $second_ch = $class_unqual_name[$pivot + 1];

        if ( $first_ch === 'I'
        &&  !('a' <= $second_ch && $second_ch <= 'z')) {
            // We found it.
            break;
        }

        // Try the next underscore.

        // We use \strcspn and \strspn instead of \strpos because
        // \strcspn and \strspn don't return `false`.
        // So we can save cycles and simplify code
        // by not needing to check for `false`.
        $pivot += \strcspn($class_unqual_name,'_',$pivot);
        $pivot += \strspn($class_unqual_name,'_',$pivot);
    }

    if ( $pivot === \PHP_INT_MAX ) {
        // Base case:
        // The name given was a normal class name with no interface notation.
        return \PHP_INT_MAX;
    }

    if ( $pivot === 0 ) {
        // Optimization: Just use \substr for the easy case, ($pivot === 0).
        // ex: 'IFooBar' -> 'FooBar'
        $class_unqual_name = \substr($class_unqual_name, 1);
    } else {
        // ex: 'My_IClass' -> 'My_Class' (where $pivot === 3)
        autoloader_substr_replace_inplace($class_unqual_name, '', $pivot, 1);
    }

    return $pivot;
}

/**
* @param array{0: string, 1: string, 2: string, 3: int} $paths
*/
function autoloader_check_eponymous_interfaces_impl(array &$paths) : bool
{
    // -- Eponymous Subclasses --
    //
    // Whenever we encounter a class/declaration whose name is prefixed
    // with 'I' and followed by an uppercase letter, ex: `Kickback\Foo\Bar\IMyClass`,
    // then we also check the not-interface version: `Kickback/Foo/Bar/MyClass.php`.
    //
    // As of 2025-08-03, this is also generalized
    // to accept 'I' following an underscore:
    // If the file name is like so: `Kickback\Foo\Bar\My_IClass`,
    // Then we check both of these files:
    // * `Kickback/Foo/Bar/My_IClass.php` (first)
    // * `Kickback/Foo/Bar/My_Class.php`  (second)
    //
    // This is only done with the first '_I' encountered in the classname.
    // If the file name is like so: `Kickback\Foo\Bar\My_ISilly_IClass`,
    // Then we check both of these files:
    // * `Kickback/Foo/Bar/My_ISilly_IClass.php` (first)
    // * `Kickback/Foo/Bar/My_Silly_IClass.php`  (second)
    //
    // In the above example, we would NOT check
    // for `My_ISilly_Class.php` or `My_Silly_Class.php`.
    //

    $starting_class_unqual_name = autoloader_unqual_name($paths);
    $start_scan_at = 0;
    $len = \strlen($starting_class_unqual_name);
    while (true) {
        $class_unqual_name = $starting_class_unqual_name;
        $idx = autoloader_eponymous_interfaces_transform($class_unqual_name, $start_scan_at);
        if ( $idx >= $len ) {
            // Base case:
            // The name given was a normal class name with no interface notation.
            return false;
        }

        // Experimentally construct the full file path for
        // what we think the class file's name might be.
        // ex:
        //   base_path:       '${project_dir}/html/Kickback/Something/IFooBar' ->
        //                        '${project_dir}/html/Kickback/Something/FooBar'
        //   main_class_fqn:  'Kickback\Something\IFooBar' -> 'Kickback\Something\FooBar'
        //   file_path:       '${project_dir}/html/Kickback/Something/IFooBar.php' ->
        //                        '${project_dir}/html/Kickback/Something/FooBar.php'
        $try_paths = $paths;
        autoloader_unqual_name($try_paths, $class_unqual_name);

        // Check that experimental full path for existence.
        if ( \file_exists(autoloader_file_path($try_paths) ) )
        {
            // If we have a hit, actualize it!
            autoloader_unqual_name($paths, $class_unqual_name);
            return true;
        }

        // File didn't exist.
        // We'll keep scanning the original class name,
        // but further in, just to see if there's interface
        // notation elsewhere that makes a file.
        $start_scan_at = $idx+1;
    }

    // Normally we would unconditionally change the `$paths` variable,
    // At least if we DID find interface notation in the input symbol.
    // We want these variables to reflect that, when other
    // features are analyzing them.
    //
    // So, if `$class_unqual_name` started as 'IFooBar',
    // then `autoloader_check_eponymous_trait_classes(...)`
    // (which must be called later in the sequence)
    // should be operating on 'FooBar', not 'IFooBar'.
    //
    // Unfortuantely, it's not that simple, because interface-finding
    // is an iterative process.
    //
    // For example:  IO_IException
    //
    // If one isn't thinking about autoloader syntax, then clearly "IO"
    // is one morpheme, the 'I' another, and 'Exception' after that.
    //
    // However, to the autoloader, IO is also an "interface" on "O".
    //
    // So it's ambiguous, and there are two ways to parse it:
    // * (I)O_IException -> look in O_IException.php
    // * IO_(I)Exception -> look in IO_Exception.php
    //
    // Notably, without any iteration, it would pick the first one,
    // and that would be disappointing to most of us.
    //
    // That's why this function accepts a "start_at" parameter and
    // returns an integer: it allows the caller to iterate with this
    // function while also keeping file I/O separate from this function
    // (because the caller can check for file existence).
    //
    // This also means that if we find interface notation, and we find
    // it more than once, then we don't know _which_ notation to set
    // (speculatively) for other autoloader features to compose with.
    //
    // Ergo, we don't bother. It might become frustrating that the
    // interface feature can't compose with others as easily, but
    // this behavior is important to have because it's _predictable_.
    //
    // After all, it would be fairly despair inducing if one were to
    // write something like `IO_IException__Subclass`, only to find
    // that it's looking for it in `O_IException.php` instead of
    // `IO_Exception.php`, and _there's nothing you can do about it_.
    //
    // (Ideally, we'd check BOTH possibilities, but that would require
    // us to check all of the other features against every interface
    // notation possibility. That _could_ be computationally intense,
    // but it might not be too bad in practice. The bigger problem is
    // that it would require significant changes to the autoloader to
    // integrate the eponymous-class/interface/etc features more
    // extensively, and it's already fairly complicated. So as of this
    // writing, it's really a "maybe someday" kind of thing.)
}

/**
* @param array{0: string, 1: string, 2: string, 3: int} $paths
*/
function autoloader_check_eponymous_interfaces(array &$paths) : bool
{
    return autoloader_check_eponymous_something(
        'Kickback\InitializationScripts\autoloader_check_eponymous_interfaces_impl',
            $paths);
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
    $namespace_name_len = \strlen($namespace_to_try);
    if (strlen($class_fqn) <= $namespace_name_len+1) {
        // No, move on to the next registered autoloader
        // ($class_fqn isn't long enough to contain both the namespace and a classname.)
        return AUTOLOAD_INCOMPLETE;
    }

    if ("\\" !== \substr($class_fqn, $namespace_name_len, 1)) {
        // No, move on to the next registered autoloader
        // We know this because:
        // $class_fqn should have a name separator ("\\") right after the
        // its namespace portion. But in this case, it's not at the correct
        // position for its namespace to match $namespace_to_try, so we know
        // that the class is in a different namespace.
        return AUTOLOAD_INCOMPLETE;
    }

    if (0 !== \strncmp($namespace_to_try, $class_fqn, $namespace_name_len)) {
        // No, move on to the next registered autoloader
        return AUTOLOAD_INCOMPLETE;
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
    $relative_class_name = \substr($class_fqn, $namespace_name_len+1);

    // We consider these variables prefixed with `starting_` to be immutable:
    // they won't change during the rest of the lookup calculations, and that's
    // helpful because we can use them in error reporting.
    // (If we can't find any files, even with bespoke lookups, then the
    // best option to report is the one that reflects the name passed into
    // the autoloader, hence the `starting_*` variables.)

    // ex: 'Something\ClassName' -> 'ClassName'
    $unqual_pos = \strrpos($relative_class_name,'\\');
    if ( $unqual_pos === false ) {
        $unqual_pos = 0;
    } else {
        $unqual_pos++; // remove the leading '\'
    }
    $starting_class_unqual_name = \substr($relative_class_name, $unqual_pos);
    if ( 0 === \strlen($starting_class_unqual_name) ) {
        $starting_class_unqual_name = $relative_class_name;
    }

    // This will normally be the class's Fully Qualified Name.
    // BUT.
    // If one of the special loading features (ex: eponymous trait classes)
    // causes a file to be loaded that isn't a direct 1-to-1 translation
    // of the class's FQN, then this might differ.
    //
    // This is helpful for knowing if our target file has been loaded
    // already or not, which allows us to avoid using `require_once`
    // by instead using the current environment's declaration state.
    //
    // (We don't want to use `require_once` because it might use extra
    // memory to remember what was "require'd" or not. And we already
    // have that information effectively stored in our declaration state.)
    $starting_file_main_decl_fqn = $class_fqn;

    // Replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    // ex:
    // if $base_dir is "${project_dir}/html/Kickback/" then:
    // 'Something\ClassName' -> '${project_dir}/html/Kickback/Something/ClassName'
    $starting_class_base_path   = $base_dir . \str_replace('\\', DIRECTORY_SEPARATOR, $relative_class_name);

    // ex: '${project_dir}/html/Kickback/Something/ClassName' ->
    //     '${project_dir}/html/Kickback/Something/ClassName.php'
    $starting_class_file_path   = $starting_class_base_path . '.php';

    // Mirror these into mutable variables that may change
    // if the "starting" class doesn't exist and bespoke
    // features find a proper successor.
    //$class_unqual_name  = $starting_class_unqual_name;
    //$file_main_decl_fqn = $starting_file_main_decl_fqn;
    //$class_base_path    = $starting_class_base_path;
    //$class_file_path = null;
    //if ( file_exists($starting_class_file_path) ) {
    //    $class_file_path = $starting_class_file_path;
    //}

    // @var array{0: string, 1: string, 2: string, 3: int}
    $paths  = [
        AUTOLOAD_IDX_MAIN_CLASS_FQN => $starting_file_main_decl_fqn,
        AUTOLOAD_IDX_BASE_PATH      => $starting_class_base_path,
        AUTOLOAD_IDX_FILE_PATH      => $starting_class_file_path,
        AUTOLOAD_IDX_UNQUAL_LEN     => \strlen($starting_class_unqual_name)
    ];

    // Kickback-specific features:
    if ( $namespace_to_try === 'Kickback' ) {
        $enable_eponymous_numbered_subclasses = true;
        $enable_eponymous_subclasses          = true;
        $enable_eponymous_interfaces          = true;
        $enable_eponymous_trait_class         = true;
    } else {
        $enable_eponymous_numbered_subclasses = false;
        $enable_eponymous_subclasses          = false;
        $enable_eponymous_interfaces          = false;
        $enable_eponymous_trait_class         = false;
    }

    // Note: Order-of-operations matters.
    // The subclass logic might change the basename by chopping
    // off a subclass suffix. At that point, it's a good idea
    // to check the stem of the class name (the part without the subclass)
    // against the trait-class logic.

    $found = false;

    // @phpstan-ignore booleanNot.alwaysTrue
    if ( !$found && $enable_eponymous_numbered_subclasses ) {
        $found = autoloader_check_eponymous_numbered_subclasses($paths);
    }

    if ( !$found && $enable_eponymous_subclasses ) {
        $found = autoloader_check_eponymous_subclasses($paths);
    }

    if ( !$found && $enable_eponymous_interfaces ) {
        $found = autoloader_check_eponymous_interfaces($paths);
    }

    if ( !$found && $enable_eponymous_trait_class ) {
        $found = autoloader_check_eponymous_trait_classes($paths);
    }

    if ( $found ) {
        $class_file_path    = autoloader_file_path($paths);
        $file_main_decl_fqn = $paths[AUTOLOAD_IDX_MAIN_CLASS_FQN];
    } else {
        // If we couldn't find any files to load,
        // then we are destined to produce errors.
        // We want the errors to provide a useful class file path.
        // None of the subclass-y file paths worked, so we don't
        // want to show those (contents of `$path`).
        // Instead, it is generally more helpful to
        // display the path that the autoloader was
        // given, since that tells us exactly which
        // class initiated the hypothetical failure.
        // That path is found in `$starting_class_file_path`.
        $class_file_path    = $starting_class_file_path;
        $file_main_decl_fqn = $starting_file_main_decl_fqn;
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
        if (\file_exists($class_file_path))
        {
            // Checking autoloader_decl_exists allows us to avoid calling `require`
            // if the file containing our class/interface/trait/whatever has
            // already been pulled in by a different class/interface/trait/whatever.
            //
            // The logic goes: If the main class/interface/etc in that file
            // is already declared ("exists"), then we have already
            // loaded that file.
            //
            // That said, it might not be necessary.
            // Testing has suggested this behavior:
            // * Code needs declaration of class FooBar__Qux.
            // * Autoloader called to load FooBar__Qux.
            // * It finds file FooBar.php and uses `require 'FooBar.php'`
            // * `require 'FooBar.php'` declares both `FooBar` and `FooBar__Qux`.
            // * Code needs declaration of class FooBar.
            // * Autoloader is NOT called. (Like, we don't even get to the below check).
            //
            // This suggests that PHP is already checking for the existence
            // of `FooBar` before it even calls the autoloader, and because it
            // exists, it doesn't have a reason to call the autoloader in the
            // first place.
            //
            // So we could probably just use `require $class_file_path;`
            // below without `if (!autoloader_decl_exists($file_main_decl_fqn)) {...}`
            // and everything would be fine.
            //
            // Right now, the if-statement is just being left in for paranoia reasons.
            //
            // (Ditto for the other `require` operator in
            // the "Web and CLI contexts" branch of the logic.)
            //
            if (!autoloader_decl_exists($file_main_decl_fqn)) {
                autoload_debug_trace(fn() => "Begin `require $class_file_path`");
                require $class_file_path;
                autoload_debug_trace(fn() => "End `require $class_file_path`");
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
        if (!autoloader_decl_exists($file_main_decl_fqn)) {
            autoload_debug_trace(fn() => "Begin `require $class_file_path`");
            require $class_file_path;
            autoload_debug_trace(fn() => "End `require $class_file_path`");
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
    // Weird workaround:
    // Sometimes using the `never` return type will cause the autoloader to be invoked.
    // This doesn't make sense though, because `never` is not a class, it's a built-in
    // PHP type that indicates that a function will "never" return.
    // So we'll just... "not do this, but say we did". heh.
    // (It got worse: Apparently PHPStan does this whenever something uses
    // a type defined with @phpstan-type or @phpstan-type-import. Guh!
    // Well, I have no way to predict what those are in a general way. Sadge.)
    // (Update 2025-07-09: @phpstan-type workaroud was implemented using '_a' suffix.)
    // (Update 2025-07-10: PHPStan also does this with the `scalar` type.)
    if ($class_fqn === 'never'  || \str_ends_with($class_fqn, '\\never')
    ||  $class_fqn === 'scalar' || \str_ends_with($class_fqn, '\\scalar'))
    {
        return AUTOLOAD_IGNORED;
    }

    // Ignore symbols that tell us to exclude them from autoloading.
    // Right now, this is anything in the Kickback namespace that ends
    // with the suffix `_a`. This can be used to designate PHPStan local aliases.
    if (\str_ends_with($class_fqn,'_a')
    &&  (  \str_starts_with($class_fqn,'Kickback')
        || \str_starts_with($class_fqn,'\\Kickback')))
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
