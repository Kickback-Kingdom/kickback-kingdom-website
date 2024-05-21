<?php
declare(strict_types=1);

namespace Kickback\InitializationScripts;

/**
* Defines what characters to use at every level of trace indentation.
*/
define('Kickback\InitializationScripts\TRACE_INDENT_WITH', "  ");

/**
* This function accesses a constant.
*
* It is useful for circumventing "Strict comparison ... will always evaluate to (true|false)."
* errors that PHPStan emits.
*
* @see \Kickback\InitializationScripts\TRACE_INDENT_WITH
*/
function TRACE_INDENT_WITH() : string { return \Kickback\InitializationScripts\TRACE_INDENT_WITH; }


/**
* Gets or sets the indentation level for trace messages.
*
* To use it as a getter, either omit the argument or pass `null` into it.
* The returned value is the current indentation level.
*
* To use it as a setter, just pass the new value in as an argument.
* The returned value will be the new indentation level.
*/
function trace_indent_lvl(?int $new_level = null) : int
{
    static $_indent_lvl = 0;
    if ( !is_null($new_level) ) {
        $_indent_lvl = $new_level;
    }
    return $_indent_lvl;
}

/**
* Call this to increase the indentation level of traces.
*/
function trace_indent_more() : void
{
    trace_indent_lvl(trace_indent_lvl() + 1);
}

/**
* Call this to decrease the indentation level of traces.
*/
function trace_indent_less() : void
{
    trace_indent_lvl(trace_indent_lvl() - 1);
}

/**
* Implementation detail.
*
* Do not call this from outside of `trace.php`.
*
* @return array<string>
*/
function &trace_indent_str_cache() : array
{
    static $_indent_str_cache = ["", TRACE_INDENT_WITH];
    return $_indent_str_cache;
}

/**
* Implementation detail.
*
* Do not call this from outside of `trace.php`.
*/
function trace_get_indent_str(int $indent_level) : string
{
    if ( $indent_level < 0 ) {
        return "";
    }

    // Memoize this function to avoid allocating strings every time this is called.
    $cache = &trace_indent_str_cache();
    if ( array_key_exists($indent_level, $cache) ) {
        return $cache[$indent_level];
    }

    // Cache miss: use `str_repeat` to fill the gap.
    $indent_with = TRACE_INDENT_WITH();
    $indent_str = str_repeat($indent_with, $indent_level);
    $cache[$indent_level] = $indent_str;
    return $indent_str;
}

/**
* Print function that executes `error_log($msg)` when `(\Kickback\InitializationScripts\PARENT_PROCESS_TYPE === "WEB")`,
* and otherwise uses`echo("$msg\\n");` .
*
* As an `echo` wrapper, it is useful because `echo` technically isn't
* a function. But if we wrap it in this function, then the wrapper function
* can be passed around like any other function.
*
* The switch to logging in "WEB" context is done to minimize the chances that
* sensitive information is leaked to untrusted clients on the wider internet.
* It is an important SECURITY concern.
*/
function trace_echo_or_log(string $msg) : void
{
    if ( "WEB" === PARENT_PROCESS_TYPE() ) {
        error_log($msg);
    } else {
        echo("$msg\n");
    }
}

/**
* Prints the given message, with indentation, using the given `$print_fn` function.
*
* Do not pass `null` to the $print_fn parameter, even though it seems to be
* declared to allow such. It's only nullable because I couldn't find a reliable
* way to make $print_fn default to the \Kickback\InitializationScripts\trace_echo_or_log
* function, so I used the default value of `null` as a canary for when the
* function is called with only a single argument. That may change in the future
* if we find a way to have the default value be a valid function reference
* (and not just a string with the function's name in it).
*/
function trace(string $msg, ?callable $print_fn = null) : void
{
    $indent_str = trace_get_indent_str(trace_indent_lvl());
    if ( is_null($print_fn) ) {
        trace_echo_or_log("$indent_str$msg");
    } else {
        $print_fn("$indent_str$msg");
    }
}

?>
