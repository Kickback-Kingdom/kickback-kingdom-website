<?php
declare(strict_types=1);

namespace Kickback\Common\Primitives;

/**
* Extended functionality for the `string` type.
*
* This class originally existed to silence the PHPStan error about `empty` being not allowed.
*/
final class Str
{
    use \Kickback\Common\Traits\StaticClassTrait;

    /**
    * A type-safe alternative to the `empty` builtin.
    *
    * This can be used to make PHPStan stop complaining about
    * `empty($some_string)` being "not allowed" and telling us
    * to "use a more strict comparison".
    */
    public static function empty(?string $x) : bool
    {
        return !isset($x) || (0 === strlen($x));
    }

    public static function is_longer_than(?string $var, int $minLength) : bool
    {
        return !is_null($var) && strlen($var) >= $minLength;
    }

    private static function unittest_is_longer_than() : void
    {
        echo("  ".__FUNCTION__."()\n");
        // Note that this function works in the sense of
        // "at least as long as",
        // not "longer and not equal to".

        assert(self::is_longer_than(null, 0) === false);
        assert(self::is_longer_than(null, 2) === false);
        assert(self::is_longer_than('',0)    === true);
        assert(self::is_longer_than('',1)    === false);
        assert(self::is_longer_than('a',0)   === true);
        assert(self::is_longer_than('a',1)   === true);
        assert(self::is_longer_than('a',2)   === false);

        assert(self::is_longer_than('foo',0) === true);
        assert(self::is_longer_than('foo',1) === true);
        assert(self::is_longer_than('foo',2) === true);
        assert(self::is_longer_than('foo',3) === true);
        assert(self::is_longer_than('foo',4) === false);
        assert(self::is_longer_than('foo',5) === false);
    }

    /**
    * Normalize a filepath. (Ex: resolving '..')
    *
    * This function will resolving '..' path elements, and by removing
    * extra '.' elements and extraneous slashes.
    *
    * This is similar to the pre-defined function `realpath`, except
    * that this version will not resolve symlinks, and does not
    * do any filesystem I/O.
    *
    * This does NOT do any URL decoding on the provided path.
    */
    public static function normalize_path(string $path) : string
    {
        // credit: https://stackoverflow.com/a/10067975
        // (but modified to handle corner-cases like paths with excess '..', and never return empty string)
        if (0 === strlen($path)) {
            return '.';
        }

        $is_absolute = ($path[0] === '/');
        $root = $is_absolute ? '/' : '';

        // TODO: This could probably be optimized by using `strtok` instead of `explode`.
        $segments = explode('/', trim($path, '/'));
        $ret = array();
        foreach($segments as $segment)
        {
            if (($segment === '.') || strlen($segment) === 0) {
                continue;
            }
            if ($segment === '..' && 0 < count($ret))
            {
                $backtrack_repopulate_count = 0;
                $elem = '';
                while(true)
                {
                    $elem = array_pop($ret);
                    if ( $elem !== '..' ) {
                        break;
                    }
                    // $elem === '..'
                    // (So we'll need to put a '..' back later.)
                    $backtrack_repopulate_count++;
                    if (0 === count($ret)) {
                        break;
                    }
                }

                for ($n = 0; $n < $backtrack_repopulate_count; $n++) {
                    array_push($ret, '..');
                }

                // If $elem were a valid directory name (and not '..' or ''),
                // then it would indicate that we found a named path segment
                // to remove to balance out what's in `$segment`.
                // And everything would be fine.
                //
                // But if it's NOT a valid directory name, then
                // `$segment` did not resolve, so we'll have
                // to add it into our output.
                //
                // Note: PHPStan complained about the `0 === strlen($elem)`
                // expression being a "comparison using === between 0 and int<1, max>"
                // that will always evaluate to false.
                // It kinda makes sense: we filter empty $segment's out as
                // a first item of business in the loop, which is before
                // the can get pushed into `$ret`. SO. It _is_ actually
                // impossible for any of `$ret`'s contents to be empty strings.
                // And since `$elem` is an element of `$ret`, its strlen() type
                // is thus `between 0 and int<1, max>`: a nonzero positive integer.
                if ( $elem === '..' /*|| 0 === strlen($elem)*/ ) {
                    array_push($ret, $segment);
                }
            }
            else
            if ($segment === '..')
            {
                // More '..' elements than preceding names; tricky!
                if ( !$is_absolute ) {
                    // Relative paths: We have to keep it,
                    // because relative paths can navigate to more rooty directories.
                    array_push($ret, $segment);
                }
                // else {
                    // Do nothing!
                    // Linux (Unix?) systems, at the very least,
                    // will simply discard the extra '..' elements
                    // when processing such a path.
                    // So we will do the same thing here.
                // }
            } else {
                array_push($ret, $segment);
            }
        }
        $output = $root . implode('/', $ret);
        if (0 === strlen($output)) {
            $output = '.';
        }
        return $output;
    }

    private static function unittest_normalize_path() : void
    {
        echo("  ".__FUNCTION__."()\n");

        assert(self::normalize_path(''),'.');
        assert(self::normalize_path('/'),'/');

        assert(self::normalize_path('/foo'),'/foo');
        assert(self::normalize_path('/foo/'),'/foo');
        assert(self::normalize_path('/foo/.'),'/foo');
        assert(self::normalize_path('/foo/..'),'/');

        assert(self::normalize_path('foo'),'foo');
        assert(self::normalize_path('foo/'),'foo');
        assert(self::normalize_path('foo/.'),'foo');
        assert(self::normalize_path('foo/..'),'.');

        assert(self::normalize_path('./.'),'.');

        // ---------------------------------------------------------------------
        // Tests from the Stack Overflow post.
        //
        // Source: https://stackoverflow.com/a/18338044
        assert(self::normalize_path('/smth/../smth/../') === '/');
        assert(self::normalize_path('smth/../smth/../') === '.');
        assert(self::normalize_path('/a/b/c/../../../d/e/file.txt') === '/d/e/file.txt');

        // ---------------------------------------------------------------------
        // These tests came from the D standard libary's docs on 2025-07-10.
        //
        // Source: https://dlang.org/library/std/path/as_normalized_path.html
        assert(self::normalize_path('/foo/./bar/..//baz/') === '/foo/baz');
        assert(self::normalize_path('../foo/.') === '../foo');
        assert(self::normalize_path('/foo/./bar/..//baz/') === '/foo/baz'); // @phpstan-ignore  function.alreadyNarrowedType
        assert(self::normalize_path('../foo/.') === '../foo');              // @phpstan-ignore  function.alreadyNarrowedType
        assert(self::normalize_path('/foo/bar/baz/') === '/foo/bar/baz');
        assert(self::normalize_path('/foo/./bar/../../baz') === '/baz');

        // ---------------------------------------------------------------------
        // The below tests were taken from the D standard library on 2025-07-10
        // Some of them might be redundant with the other tests above,
        // but that doesn't hurt anything.
        //
        // Source: https://github.com/dlang/phobos/blob/v2.111.0/std/path.d#L2086
        assert(self::normalize_path('/foo/bar') === '/foo/bar');           // @phpstan-ignore  function.alreadyNarrowedType
        assert(self::normalize_path('foo/bar/baz') === 'foo/bar/baz');
        assert(self::normalize_path('foo/bar/baz') === 'foo/bar/baz');     // @phpstan-ignore  function.alreadyNarrowedType
        assert(self::normalize_path('foo/bar//baz///') === 'foo/bar/baz');
        assert(self::normalize_path('/foo/bar/baz') === '/foo/bar/baz');
        assert(self::normalize_path('/foo/../bar/baz') === '/bar/baz');
        assert(self::normalize_path('/foo/../..//bar/baz') === '/bar/baz');
        assert(self::normalize_path('/foo/bar/../baz') === '/foo/baz');
        assert(self::normalize_path('/foo/bar/../../baz') === '/baz');
        assert(self::normalize_path('/foo/bar/.././/baz/../wee/') === '/foo/wee');
        assert(self::normalize_path('//foo/bar/baz///wee') === '/foo/bar/baz/wee');

        assert(self::normalize_path('foo//bar') === 'foo/bar');
        assert(self::normalize_path('foo/bar') === 'foo/bar');

        // Current dir path
        assert(self::normalize_path('./') === '.');
        assert(self::normalize_path('././') === '.');
        assert(self::normalize_path('./foo/..') === '.');
        assert(self::normalize_path('foo/..') === '.');

        // Trivial
        assert(self::normalize_path('') === '.'); // NOTE: Differs from D's `asNormalizedPath` function!
        assert(self::normalize_path('foo/bar') === 'foo/bar');  // @phpstan-ignore  function.alreadyNarrowedType

        // Correct handling of leading slashes
        assert(self::normalize_path('/') === '/');
        assert(self::normalize_path('///') === '/');
        assert(self::normalize_path('////') === '/');
        assert(self::normalize_path('/foo/bar') === '/foo/bar');  // @phpstan-ignore  function.alreadyNarrowedType
        assert(self::normalize_path('//foo/bar') === '/foo/bar');
        assert(self::normalize_path('///foo/bar') === '/foo/bar');
        assert(self::normalize_path('////foo/bar') === '/foo/bar');

        // Correct handling of single-dot symbol (current directory)
        assert(self::normalize_path('/./foo') === '/foo');
        assert(self::normalize_path('/foo/./bar') === '/foo/bar');

        assert(self::normalize_path('./foo') === 'foo');
        assert(self::normalize_path('././foo') === 'foo');
        assert(self::normalize_path('foo/././bar') === 'foo/bar');

        // Correct handling of double-dot symbol (previous directory)
        assert(self::normalize_path('/foo/../bar') === '/bar');
        assert(self::normalize_path('/foo/../../bar') === '/bar');
        assert(self::normalize_path('/../foo') === '/foo');
        assert(self::normalize_path('/../../foo') === '/foo');
        assert(self::normalize_path('/foo/..') === '/');
        assert(self::normalize_path('/foo/../..') === '/');

        assert(self::normalize_path('foo/../bar') === 'bar');
        assert(self::normalize_path('foo/../../bar') === '../bar');
        assert(self::normalize_path('../foo') === '../foo');
        assert(self::normalize_path('../../foo') === '../../foo');
        assert(self::normalize_path('../foo/../bar') === '../bar');
        assert(self::normalize_path('.././../foo') === '../../foo');
        assert(self::normalize_path('foo/bar/..') === 'foo');
        assert(self::normalize_path('/foo/../..') === '/');  // @phpstan-ignore  function.alreadyNarrowedType

        // The ultimate path
        assert(self::normalize_path('/foo/../bar//./../...///baz//') === '/.../baz');

        // End of D standard library unittests.
    }

    /**
    * Removes `$prefix` from the given file system path, `$path`.
    *
    * The returned value will be a relative path, and thus will never start
    * with the `/` character.
    *
    * This function will not do any path normalization, nor will it check
    * that the `$prefix` is actually a prefix of the `$path`. It is the caller's
    * responsibility to ensure that paths are normalized as needed, and that
    * the `$prefix` is actually a prefix of `$path`.
    */
    public static function remove_path_prefix(string $path, string|int $prefix) : string
    {
        if ( is_string($prefix) ) {
            $prefix_len = \strlen($prefix);
            assert(\str_starts_with($path, $prefix));
        } else {
            $prefix_len = $prefix;
            assert($prefix_len <= \strlen($path));
        }
        $relpath    = \substr($path, $prefix_len);
        if ( \str_starts_with($relpath, '/') ) {
            $relpath = \substr($relpath, 1);
        }
        return $relpath;
    }

    private static function unittest_remove_path_prefix() : void
    {
        echo("  ".__FUNCTION__."()\n");

        assert(self::remove_path_prefix('',         '') === '');
        assert(self::remove_path_prefix('/',        '') === '');
        assert(self::remove_path_prefix('/foo',     '') === 'foo');
        assert(self::remove_path_prefix('/foo',     '/foo')  === '');
        assert(self::remove_path_prefix('foo',      'foo')   === '');
        assert(self::remove_path_prefix('/foo/bar', '/foo/') === 'bar');
        assert(self::remove_path_prefix('/foo/bar', '/foo')  === 'bar');
        assert(self::remove_path_prefix('foo/bar',  'foo/')  === 'bar');
        assert(self::remove_path_prefix('foo/bar',  'foo')   === 'bar');
        assert(self::remove_path_prefix('/foo/bar/baz/qux/quux', '')  === 'foo/bar/baz/qux/quux');
        assert(self::remove_path_prefix('/foo/bar/baz/qux/quux', '/') === 'foo/bar/baz/qux/quux');
        assert(self::remove_path_prefix('/foo/bar/baz/qux/quux', '/foo')  === 'bar/baz/qux/quux');
        assert(self::remove_path_prefix('/foo/bar/baz/qux/quux', '/foo/bar')  === 'baz/qux/quux');
        assert(self::remove_path_prefix('/foo/bar/baz/qux/quux', '/foo/bar/baz')  === 'qux/quux');
        assert(self::remove_path_prefix('/foo/bar/baz/qux/quux', '/foo/bar/baz/qux')  === 'quux');
        assert(self::remove_path_prefix('/foo/bar/baz/qux/quux', '/foo/bar/baz/qux/quux') === '');
        assert(self::remove_path_prefix('/foo/bar/../qux/quux', '')  === 'foo/bar/../qux/quux');
        assert(self::remove_path_prefix('/foo/bar/../qux/quux', '/') === 'foo/bar/../qux/quux');
        assert(self::remove_path_prefix('/foo/bar/../qux/quux', '/foo')  === 'bar/../qux/quux');
        assert(self::remove_path_prefix('/foo/bar/../qux/quux', '/foo/bar')  === '../qux/quux');
        assert(self::remove_path_prefix('/foo/bar/../qux/quux', '/foo/bar/..')  === 'qux/quux');
        assert(self::remove_path_prefix('/foo/bar/../qux/quux', '/foo/bar/../qux')  === 'quux');
        assert(self::remove_path_prefix('/foo/bar/../qux/quux', '/foo/bar/../qux/quux') === '');
    }

    /**
    * Scans `$msg` for the first newline sequence at or after `$cursor`.
    *
    * If no newlines are found, both the `$cursor` output and the return value
    * will be set to the length of `$msg`.
    *
    * BUG: Doesn't handle oldschool Mac newlines that are just a '\r' character.
    * Mostly because there isn't a fast way to do that without requiring the
    * PCRE regex module. This method uses PHP built-in string scanning functions
    * like `strpos` to remain performant, so it does not have a loop inside
    * it that can be modified to handle more types of newlines.
    *
    * @param     string  $msg    The string to scan.
    * @param     int     $cursor The position in the string to start at.
    * @param-out int     $cursor The position one character past the end of the newline.
    * @return    int     The position of the first character in the newline sequence.
    */
    public static function next_newline(string $msg, int &$cursor) : int
    {
        $pos = strpos($msg, "\n", $cursor);

        // If there are no newlines in the string,
        // then we return the slice '' at end of string.
        // The empty slice indicates that no newlines were found.
        if ( $pos === false ) {
            $len = strlen($msg);
            $cursor = $len;
            return $len;
        }

        if ( 0 < $pos && substr($msg, $pos-1, 1) === "\r" ) {
            // Windows newlines.
            $cursor = $pos+1;
            return $pos-1;
        } else {
            // Linux/Unix newlines.
            $cursor = $pos+1;
            return $pos;
        }
    }

    //private static function unittest_next_newline(TestRunner $runner) : void
    private static function unittest_next_newline() : void
    {
        //$runner->note("  ".__FUNCTION__."()\n");
        echo ("  ".__FUNCTION__."()\n");

        $assert = function(bool $expr) /*use($runner)*/ : void {
            //$runner->assert_true($expr);
            assert($expr);
        };

        $cursor = 0;
        $msg = "";
        $nlpos = self::next_newline($msg, $cursor);
        $assert($nlpos  === 0);
        $assert($cursor === 0);

        $cursor = 0;
        $msg = " ";
        $nlpos = self::next_newline($msg, $cursor);
        $assert($nlpos  === 1);
        $assert($cursor === 1);

        $cursor = 0;
        $msg = "a\nb\nc";
        $nlpos = self::next_newline($msg, $cursor);
        $assert($nlpos  === 1);
        $assert($cursor === 2);
        $nlpos = self::next_newline($msg, $cursor);
        $assert($nlpos  === 3);
        $assert($cursor === 4);
        $nlpos = self::next_newline($msg, $cursor);
        $assert($nlpos  === 5);
        $assert($cursor === 5);

        $cursor = 0;
        $msg = "hello\nworld\n";
        $nlpos = self::next_newline($msg, $cursor);
        $assert($nlpos  === 5);
        $assert($cursor === 6);
        $nlpos = self::next_newline($msg, $cursor);
        $assert($nlpos  === 11);
        $assert($cursor === 12);
        $nlpos = self::next_newline($msg, $cursor);
        $assert($nlpos  === 12);
        $assert($cursor === 12);

        $cursor = 0;
        $msg = "hello\r\nworld\r\n";
        $nlpos = self::next_newline($msg, $cursor);
        $assert($nlpos  === 5);
        $assert($cursor === 7);
        $nlpos = self::next_newline($msg, $cursor);
        $assert($nlpos  === 12);
        $assert($cursor === 14);
        $nlpos = self::next_newline($msg, $cursor);
        $assert($nlpos  === 14);
        $assert($cursor === 14);

        $cursor = 0;
        $msg = "hello\nworld\r\n";
        $nlpos = self::next_newline($msg, $cursor);
        $assert($nlpos  === 5);
        $assert($cursor === 6);
        $nlpos = self::next_newline($msg, $cursor);
        $assert($nlpos  === 11);
        $assert($cursor === 13);
        $nlpos = self::next_newline($msg, $cursor);
        $assert($nlpos  === 13);
        $assert($cursor === 13);

        $cursor = 0;
        $msg = "\n";
        $nlpos = self::next_newline($msg, $cursor);
        $assert($nlpos  === 0);
        $assert($cursor === 1);
        $nlpos = self::next_newline($msg, $cursor);
        $assert($nlpos  === 1);
        $assert($cursor === 1);

        $cursor = 0;
        $msg = "\r\n";
        $nlpos = self::next_newline($msg, $cursor);
        $assert($nlpos  === 0);
        $assert($cursor === 2);
        $nlpos = self::next_newline($msg, $cursor);
        $assert($nlpos  === 2);
        $assert($cursor === 2);

        $cursor = 0;
        $msg = "\n\n";
        $nlpos = self::next_newline($msg, $cursor);
        $assert($nlpos  === 0);
        $assert($cursor === 1);
        $nlpos = self::next_newline($msg, $cursor);
        $assert($nlpos  === 1);
        $assert($cursor === 2);
        $nlpos = self::next_newline($msg, $cursor);
        $assert($nlpos  === 2);
        $assert($cursor === 2);

        $cursor = 0;
        $msg = "\n\r\n";
        $nlpos = self::next_newline($msg, $cursor);
        $assert($nlpos  === 0);
        $assert($cursor === 1);
        $nlpos = self::next_newline($msg, $cursor);
        $assert($nlpos  === 1);
        $assert($cursor === 3);
        $nlpos = self::next_newline($msg, $cursor);
        $assert($nlpos  === 3);
        $assert($cursor === 3);
    }

    // Kinda contrived function because it both throws and returns true/false.
    // That was intentional because I was going to use it as an example, but
    // it ended up way too long.
    // It should probably just return `false` if the prefix condition isn't true.
    // And at that point... it could probably be made shorter and less complicated.
    //
    // Anyhow, it's kind of an interesting function, so I didn't want to throw
    // it away entirely. But I also don't have a use-case for it, and it's
    // obscure enough that I am going to consider it "dead code" unless
    // we need it.
    //
    // public static function str_starts_with_at_least_n(
    //     string $text, string $prefix, int $n) : bool
    // {
    //     // Only check the first $n characters of $text and $prefix.
    //     $try_prefix = substr($prefix, 0, $n);
    //     $try_text = substr($text, 0, $n);
    //
    //     // Enforce that the first $n characters of the $prefix are a $prefix of $text.
    //     if ( !str_starts_with($try_text, $try_prefix) ) {
    //         throw ValueError(
    //             "str_starts_with_at_least_n expected '$text' ".
    //             "to start with '$prefix', but it didn't.");
    //     }
    //
    //     // We've proven that at least $prefix is a prefix of $text
    //     // (up to $n characters), but we haven't proven that all
    //     // $n characters are present until we compare string lengths.
    //     // So, that's the last check.
    //     if ( strlen($text) < $n || strlen($prefix) < $n ) {
    //         return false; // At least one of them is too short to span the required $n characters.
    //     } else {
    //         return true; // Both are $n chars long, and those $n chars are equal.
    //     }
    // }

    /**
    * Test if a string is a valid identifier.
    *
    * Features:
    * * Does not require PCRE extension to be installed.
    * * Uses native PHP function `strspn` to avoid iterating over every character.
    * * Trims whitespace from the ends of the string before testing it.
    *
    * @return bool `true` if `$text` is ASCII-alphanumeric (with underscores permitted)
    *     and begins with either an underscore or a alphabetic character;
    *     in other words, it is representable by this regular expression:
    *     `[_a-zA-Z][_a-zA-Z0-9]*`. `false` otherwise.
    */
    public static function is_ascii_identifier(string $text, int $offset = 0, ?int $length = null) : bool
    {
        $text = trim(substr($text, $offset, $length));
        if ( 0 === strlen($text) ) {
            return false; // Empty string is not a valid identifier.
        }

        $first = $text[0];
        if (
        ! (('a' <= $first && $first <= 'z')
        || ('A' <= $first && $first <= 'Z')
        || ($first === '_'))
        ) {
            return false;
        }

        // Fast native check for all remaining characters
        return strspn($text, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_') === strlen($text);
    }

    private static function unittest_is_ascii_identifier() : void
    {
        echo ("  ".__FUNCTION__."()\n");

        // Valid identifiers
        assert(self::is_ascii_identifier('a'));
        assert(self::is_ascii_identifier('A'));
        assert(self::is_ascii_identifier('_'));
        assert(self::is_ascii_identifier('_abc'));
        assert(self::is_ascii_identifier('abc123'));
        assert(self::is_ascii_identifier('_123abc'));
        assert(self::is_ascii_identifier('CamelCase'));
        assert(self::is_ascii_identifier('snake_case'));
        assert(self::is_ascii_identifier('A1B2C3'));
        assert(self::is_ascii_identifier(str_repeat('a', 256)));
        assert(self::is_ascii_identifier(' name'));
        assert(self::is_ascii_identifier('name '));
        assert(self::is_ascii_identifier(' name '));

        // Invalid identifiers
        assert(!self::is_ascii_identifier(''));
        assert(!self::is_ascii_identifier('1abc'));
        assert(!self::is_ascii_identifier('9'));
        assert(!self::is_ascii_identifier('!abc'));
        assert(!self::is_ascii_identifier('-name'));
        assert(!self::is_ascii_identifier('.dot'));
        assert(!self::is_ascii_identifier('$dollar'));
        assert(!self::is_ascii_identifier('123'));
        assert(!self::is_ascii_identifier('ab-cd'));
        assert(!self::is_ascii_identifier('ab cd'));
        assert(!self::is_ascii_identifier("tab\tchar"));
        assert(!self::is_ascii_identifier("newline\nchar"));
        assert(!self::is_ascii_identifier('abc.def'));
        assert(!self::is_ascii_identifier('abc@def'));
        assert(!self::is_ascii_identifier("   1var   "));

        // Various cases involving $offset and $length.
        assert(self::is_ascii_identifier('123_abc456', 3, 4));  // '_abc'
        assert(self::is_ascii_identifier('  abc  ', 0));        // 'abc'
        assert(self::is_ascii_identifier('  _x1  ', 2, 3));     // '_x1'
        assert(self::is_ascii_identifier('myVar123', 0, 7));    // 'myVar12'
        assert(self::is_ascii_identifier('myVar123', 0, 2));    // 'my'
        assert(self::is_ascii_identifier('!@#_foo!@#', 3, 4));  // '_foo'
        assert(!self::is_ascii_identifier('!@#_foo!@#', 0, 3)); // '!@#'
        assert(!self::is_ascii_identifier(" \n\t ", 0));        // whitespace-only
        assert(!self::is_ascii_identifier('hello', 5));         // ''
        assert(!self::is_ascii_identifier('hello', 2, 0));      // ''
    }

    public static function unchecked_blit_fwd(string &$dst, int $dst_offset, string $src, int $src_offset, int $nchars) : void
    {
        // This is needed in the autoloader, so rather than duplicate code,
        // we simply forward calls to the autoloader's function.
        \Kickback\InitializationScripts\autoloader_str_unchecked_blit_fwd($dst, $dst_offset, $src, $src_offset, $nchars);
    }

    private static function common_unittest_unchecked_blit(\Closure  $str_unchecked_blit) : void
    {
        assert($str_unchecked_blit('123.456.789',0,'abc.pqr.xyz',0,0) === '123.456.789');
        assert($str_unchecked_blit('123.456.789',4,'abc.pqr.xyz',0,0) === '123.456.789');
        assert($str_unchecked_blit('123.456.789',8,'abc.pqr.xyz',0,0) === '123.456.789');

        assert($str_unchecked_blit('123.456.789',0,'abc.pqr.xyz',0,3) === 'abc.456.789');
        assert($str_unchecked_blit('123.456.789',4,'abc.pqr.xyz',0,3) === '123.abc.789');
        assert($str_unchecked_blit('123.456.789',8,'abc.pqr.xyz',0,3) === '123.456.abc');
        assert($str_unchecked_blit('123.456.789',0,'abc.pqr.xyz',4,3) === 'pqr.456.789');
        assert($str_unchecked_blit('123.456.789',4,'abc.pqr.xyz',4,3) === '123.pqr.789');
        assert($str_unchecked_blit('123.456.789',8,'abc.pqr.xyz',4,3) === '123.456.pqr');
        assert($str_unchecked_blit('123.456.789',0,'abc.pqr.xyz',8,3) === 'xyz.456.789');
        assert($str_unchecked_blit('123.456.789',4,'abc.pqr.xyz',8,3) === '123.xyz.789');
        assert($str_unchecked_blit('123.456.789',8,'abc.pqr.xyz',8,3) === '123.456.xyz');

        assert($str_unchecked_blit('123.456.789',0,'abc.pqr.xyz',0,7) === 'abc.pqr.789');
        assert($str_unchecked_blit('123.456.789',4,'abc.pqr.xyz',0,7) === '123.abc.pqr');
        assert($str_unchecked_blit('123.456.789',0,'abc.pqr.xyz',4,7) === 'pqr.xyz.789');
        assert($str_unchecked_blit('123.456.789',4,'abc.pqr.xyz',4,7) === '123.pqr.xyz');
    }

    private static function unittest_unchecked_blit_fwd() : void
    {
        echo ("  ".__FUNCTION__."()\n");

        $str_unchecked_blit_fwd =
            function(string $dst, int $dst_offset, string $src, int $src_offset, int $nchars) : string
        {
            self::unchecked_blit_fwd($dst, $dst_offset, $src, $src_offset, $nchars);
            return $dst;
        };

        self::common_unittest_unchecked_blit($str_unchecked_blit_fwd);

        $foo = 'abcdef';
        assert($str_unchecked_blit_fwd($foo,0,$foo,6,0) === 'abcdef');
        assert($str_unchecked_blit_fwd($foo,0,$foo,5,1) === 'fbcdef');
        assert($str_unchecked_blit_fwd($foo,0,$foo,4,2) === 'efcdef');
        assert($str_unchecked_blit_fwd($foo,0,$foo,3,3) === 'defdef');
        assert($str_unchecked_blit_fwd($foo,0,$foo,2,4) === 'cdefef');
        assert($str_unchecked_blit_fwd($foo,0,$foo,1,5) === 'bcdeff');
        assert($str_unchecked_blit_fwd($foo,0,$foo,0,6) === 'abcdef');
        assert($str_unchecked_blit_fwd($foo,2,$foo,6,0) === 'abcdef');
        assert($str_unchecked_blit_fwd($foo,2,$foo,5,1) === 'abfdef');
        assert($str_unchecked_blit_fwd($foo,2,$foo,4,2) === 'abefef');
        assert($str_unchecked_blit_fwd($foo,2,$foo,3,3) === 'abdeff');
        assert($str_unchecked_blit_fwd($foo,2,$foo,2,4) === 'abcdef');
    }

    public static function unchecked_blit_rev(string &$dst, int $dst_offset, string $src, int $src_offset, int $nchars) : void
    {
        // This is needed in the autoloader, so rather than duplicate code,
        // we simply forward calls to the autoloader's function.
        \Kickback\InitializationScripts\autoloader_str_unchecked_blit_rev($dst, $dst_offset, $src, $src_offset, $nchars);
    }

    private static function unittest_unchecked_blit_rev() : void
    {
        echo ("  ".__FUNCTION__."()\n");

        $str_unchecked_blit_rev =
            function(string $dst, int $dst_offset, string $src, int $src_offset, int $nchars) : string
        {
            self::unchecked_blit_rev($dst, $dst_offset, $src, $src_offset, $nchars);
            return $dst;
        };

        self::common_unittest_unchecked_blit($str_unchecked_blit_rev);

        $foo = 'abcdef';
        assert($str_unchecked_blit_rev($foo,6,$foo,0,0) === 'abcdef');
        assert($str_unchecked_blit_rev($foo,5,$foo,0,1) === 'abcdea');
        assert($str_unchecked_blit_rev($foo,4,$foo,0,2) === 'abcdab');
        assert($str_unchecked_blit_rev($foo,3,$foo,0,3) === 'abcabc');
        assert($str_unchecked_blit_rev($foo,2,$foo,0,4) === 'ababcd');
        assert($str_unchecked_blit_rev($foo,1,$foo,0,5) === 'aabcde');
        assert($str_unchecked_blit_rev($foo,0,$foo,0,6) === 'abcdef');
        assert($str_unchecked_blit_rev($foo,4,$foo,0,0) === 'abcdef');
        assert($str_unchecked_blit_rev($foo,3,$foo,0,1) === 'abcaef');
        assert($str_unchecked_blit_rev($foo,2,$foo,0,2) === 'ababef');
        assert($str_unchecked_blit_rev($foo,1,$foo,0,3) === 'aabcef');
        assert($str_unchecked_blit_rev($foo,0,$foo,0,4) === 'abcdef');
    }

    public static function unchecked_shift_by(string &$subject, int $by, int $offset, int $nchars) : void
    {
        // This is needed in the autoloader, so rather than duplicate code,
        // we simply forward calls to the autoloader's function.
        \Kickback\InitializationScripts\autoloader_str_unchecked_shift_by($subject, $by, $offset, $nchars);
    }

    public static function unchecked_blit(string &$dst, int $dst_offset, string $src, int $src_offset, int $nchars) : void
    {
        self::unchecked_blit_fwd($dst, $dst_offset, $src, $src_offset, $nchars);
    }

    private static function unittest_unchecked_blit() : void
    {
        $str_unchecked_blit =
            function(string $dst, int $dst_offset, string $src, int $src_offset, int $nchars) : string
        {
            self::unchecked_blit($dst, $dst_offset, $src, $src_offset, $nchars);
            return $dst;
        };

        self::common_unittest_unchecked_blit($str_unchecked_blit);
    }

    public static function blit(string &$dst, int $dst_offset, string $src, int $src_offset, int $limit = PHP_INT_MAX) : int
    {
        $nchars = \strlen($src) - $src_offset;
        if ( $nchars > $limit ) {
            $nchars = $limit;
        }
        $dst_nchars = \strlen($dst) - $dst_offset;
        if ( $nchars > $dst_nchars ) {
            $nchars = $dst_nchars;
        }

        self::unchecked_blit($dst, $dst_offset, $src, $src_offset, $nchars);
        return $nchars;
    }

    /**
    * This is similar to `\substr_replace`, except that it modifies its argument instead of return the result.
    *
    * The semantics of this function (and meanings of parameters) are otherwise
    * almost identical to the PHP pre-defined `\substr_replace` function.
    *
    * When the replacement has a length identical to the `$length` parameter
    * (or its implied value), then `autoloader_str_unchecked_blit` will be
    * used instead of `\substr_replace`, thus avoiding the unnecessary memory
    * allocations that `\substr_replace` must perform.
    *
    * That makes this function potentially much faster whenever the `$subject`
    * string's length is likely to be unchanged by this operation.
    *
    * (In the future, this may also attempt to optimize the case where
    * the `$subject` string is shrinking, since that can also be performed
    * without memory allocation, though it will require potentially many
    * more individual character assignments.)
    */
    public static function substr_replace_inplace(string &$subject, string $replacement, int $offset, ?int $length = null) : void
    {
        // This is needed in the autoloader, so rather than duplicate code,
        // we simply forward calls to the autoloader's function.
        \Kickback\InitializationScripts\autoloader_substr_replace_inplace($subject, $replacement, $offset, $length);
    }

    public static function unittest_substr_replace_inplace() : void
    {
        echo ("  ".__FUNCTION__."()\n");

        $substr_replace_inplace =
            function(string $subject, string $replacement, int $offset, ?int $length) : string
        {
            self::substr_replace_inplace($subject, $replacement, $offset, $length);
            return $subject;
        };

        // Tests taken from the PHP documentation for `\substr_replace`:
        // (This function should mirror the behavior of `\substr_replace`
        // in every regard except for the "in-place" characteristic
        // and its subsequent optimization opportunities.)
        // https://www.php.net/manual/en/function.substr-replace.php
        $var = 'ABCDEFGH:/MNRPQR/';
        assert($substr_replace_inplace($var,'bob',0,null) === 'bob');
        assert($substr_replace_inplace($var,'bob',0,\strlen($var)) === 'bob');
        assert($substr_replace_inplace($var,'bob',0,0) === 'bobABCDEFGH:/MNRPQR/');
        assert($substr_replace_inplace($var,'bob',10,-1) === 'ABCDEFGH:/bob/');
        assert($substr_replace_inplace($var,'bob',-7,-1) === 'ABCDEFGH:/bob/');
        assert($substr_replace_inplace($var,'',10,-1) === 'ABCDEFGH://');

        // From comment by `elloromtz at gmail dot com`
        // https://www.php.net/manual/en/function.substr-replace.php#97401
        // "It's worth noting that when start and length are both negative
        // -and- the length is less than or equal to start, the length
        // will have the effect of being set as 0."
        assert($substr_replace_inplace('eggs','x',-1,-1) === 'eggxs');
        assert($substr_replace_inplace('eggs','x',-1,-2) === 'eggxs');
        assert($substr_replace_inplace('eggs','x',-1, 0) === 'eggxs');
        assert($substr_replace_inplace('huevos','x',-2,-2) === 'huevxos');
        assert($substr_replace_inplace('huevos','x',-2,-3) === 'huevxos');
        assert($substr_replace_inplace('huevos','x',-2, 0) === 'huevxos');

        // "Another note, if length is negative and start offsets
        // the same position as length, length (yet again) will have
        // the effect as being set as 0."
        assert($substr_replace_inplace('abcd', 'x', 0, -4) === 'xabcd');
        assert($substr_replace_inplace('abcd', 'x', 0,  0) === 'xabcd');
        assert($substr_replace_inplace('abcd', 'x', 1, -3) === 'axbcd');
        assert($substr_replace_inplace('abcd', 'x', 1,  0) === 'axbcd');

        // Homegrown tests:
        assert($substr_replace_inplace('aaa bbb ccc',  'xyz',   0,  3) ===  'xyz bbb ccc');
        assert($substr_replace_inplace('aaa bbb ccc',    'x',   0,  3) ===    'x bbb ccc');
        assert($substr_replace_inplace('aaa bbb ccc', 'pqrs',   0,  3) === 'pqrs bbb ccc');
        assert($substr_replace_inplace('aaa bbb ccc',  'xyz', -11,  3) ===  'xyz bbb ccc');
        assert($substr_replace_inplace('aaa bbb ccc',    'x', -11,  3) ===    'x bbb ccc');
        assert($substr_replace_inplace('aaa bbb ccc', 'pqrs', -11,  3) === 'pqrs bbb ccc');
        assert($substr_replace_inplace('aaa bbb ccc',  'xyz',   0, -8) ===  'xyz bbb ccc');
        assert($substr_replace_inplace('aaa bbb ccc',    'x',   0, -8) ===    'x bbb ccc');
        assert($substr_replace_inplace('aaa bbb ccc', 'pqrs',   0, -8) === 'pqrs bbb ccc');
        assert($substr_replace_inplace('aaa bbb ccc',  'xyz', -11, -8) ===  'xyz bbb ccc');
        assert($substr_replace_inplace('aaa bbb ccc',    'x', -11, -8) ===    'x bbb ccc');
        assert($substr_replace_inplace('aaa bbb ccc', 'pqrs', -11, -8) === 'pqrs bbb ccc');

        assert($substr_replace_inplace('aaa bbb ccc',  'xyz',  4,  3) ===  'aaa xyz ccc');
        assert($substr_replace_inplace('aaa bbb ccc',    'x',  4,  3) ===    'aaa x ccc');
        assert($substr_replace_inplace('aaa bbb ccc', 'pqrs',  4,  3) === 'aaa pqrs ccc');
        assert($substr_replace_inplace('aaa bbb ccc',  'xyz', -7,  3) ===  'aaa xyz ccc');
        assert($substr_replace_inplace('aaa bbb ccc',    'x', -7,  3) ===    'aaa x ccc');
        assert($substr_replace_inplace('aaa bbb ccc', 'pqrs', -7,  3) === 'aaa pqrs ccc');
        assert($substr_replace_inplace('aaa bbb ccc',  'xyz',  4, -4) ===  'aaa xyz ccc');
        assert($substr_replace_inplace('aaa bbb ccc',    'x',  4, -4) ===    'aaa x ccc');
        assert($substr_replace_inplace('aaa bbb ccc', 'pqrs',  4, -4) === 'aaa pqrs ccc');
        assert($substr_replace_inplace('aaa bbb ccc',  'xyz', -7, -4) ===  'aaa xyz ccc');
        assert($substr_replace_inplace('aaa bbb ccc',    'x', -7, -4) ===    'aaa x ccc');
        assert($substr_replace_inplace('aaa bbb ccc', 'pqrs', -7, -4) === 'aaa pqrs ccc');

        assert($substr_replace_inplace('aaa bbb ccc',  'xyz',  8,  3) ===  'aaa bbb xyz');
        assert($substr_replace_inplace('aaa bbb ccc',    'x',  8,  3) ===    'aaa bbb x');
        assert($substr_replace_inplace('aaa bbb ccc', 'pqrs',  8,  3) === 'aaa bbb pqrs');
        assert($substr_replace_inplace('aaa bbb ccc',  'xyz', -3,  3) ===  'aaa bbb xyz');
        assert($substr_replace_inplace('aaa bbb ccc',    'x', -3,  3) ===    'aaa bbb x');
        assert($substr_replace_inplace('aaa bbb ccc', 'pqrs', -3,  3) === 'aaa bbb pqrs');

        // The last few cases demonstrate the incongruity of the '0'-length case.
        // Going from length=-1 to length=0 causes the match to go from
        // "possibly as long as the entire string" to "always 0" with one increment.
        assert($substr_replace_inplace('aaa bbb ccc',  'xyz',  8,  0) ===  'aaa bbb xyzccc');
        assert($substr_replace_inplace('aaa bbb ccc',    'x',  8,  0) ===    'aaa bbb xccc');
        assert($substr_replace_inplace('aaa bbb ccc', 'pqrs',  8,  0) === 'aaa bbb pqrsccc');
        assert($substr_replace_inplace('aaa bbb ccc',  'xyz', -3,  0) ===  'aaa bbb xyzccc');
        assert($substr_replace_inplace('aaa bbb ccc',    'x', -3,  0) ===    'aaa bbb xccc');
        assert($substr_replace_inplace('aaa bbb ccc', 'pqrs', -3,  0) === 'aaa bbb pqrsccc');

        // This was causing
        // `Meta::unittest_eponymous_interfaces_transform`
        // to fail an assertion.
        assert($substr_replace_inplace('_IA', '', 1, 1) === '_A');
    }

    private static function fqdn_is_ambiguous(string $fqdn, bool $at_start_of_str) : bool
    {
        return $at_start_of_str &&
            (  $fqdn === 'http' || $fqdn === 'https'
            || $fqdn === 'ftp'  || $fqdn === 'sftp'
            || $fqdn === 'ssh'  || $fqdn === 'smtp'
            || $fqdn === 'file' || $fqdn === 'mailto');
    }

    private static bool $do_fqdn_debug = false;

    private static function fqdn_debug_line_str() : string
    {
        $trace = \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2);
        $frame = $trace[1];
        if ( \array_key_exists('line',$frame) ) {
            $line = $frame['line'];
            $line_str = \strval($line).': ';
        } else {
            $line_str ='';
        }
        return $line_str;
    }

    /**
    * @param  non-empty-string  $url
    * @return bool
    */
    private static function fqdn_debug(string $prefix, string $url, int $pos, int $end_pos) : bool
    {
        if (!self::$do_fqdn_debug) {
            return true;
        }

        $line_str = self::fqdn_debug_line_str();
        $slice = \substr($url, $pos, $end_pos-$pos);
        echo "$line_str{$prefix}pos=$pos;  {$prefix}end=$end_pos;  slice=$slice\n";
        return true;
    }

    /**
    * @return bool
    */
    private static function fqdn_debug_print(string $msg) : bool
    {
        if (!self::$do_fqdn_debug) {
            return true;
        }
        $line_str = self::fqdn_debug_line_str();
        echo $line_str;
        echo $msg;
        echo "\n";
        return true;
    }

    /**
    * @param  non-empty-string  $name
    * @return bool
    */
    private static function fqdn_debug_bool(string $name, bool $value) : bool
    {
        if (!self::$do_fqdn_debug) {
            return true;
        }
        $line_str = self::fqdn_debug_line_str();
        $str = $value ? 'true' : 'false';
        echo ("$line_str$name = $str\n");
        return true;
    }

    /**
    * Scans the URL for the Fully Qualified Domain Name (FQDN) and returns its integer position and length.
    *
    * This is a lower-level version of `Str::fqdn_from_url` which is useful
    * if string processing is required beyond simply extracting the FQDN.
    *
    * The `$fqdn_offset` and `$fqdn_length` parameters will only be modified
    * when the function successfully identifies an FQDN
    * (and, if `$validate_result === true`, only when it finds a _valid_ FQDN).
    *
    * If this function does not find an FQDN, then
    * `$fqdn_offset` and `fqdn_length` will not be modified.
    *
    * This function has the same memory and portability guarantees
    * as `Str::fqdn_from_url`, since that function is implemented
    * using this function.
    *
    * @see fqdn_from_url
    *
    * @param      int         $fqdn_offset  Scanning will begin at the position passed into this parameter.
    * @param-out  int<0,max>  $fqdn_offset
    * @param      int<0,max>  $fqdn_length  As an input: only `$fqdn_length` characters will be scanned. As an output: This is the length of the FQDN found.
    * @param-out  int<0,max>  $fqdn_length
    *
    * @return bool  `true` if a FQDN was found; or `false` if it wasn't, or if `$validate_result === true` and the FQDN was invalid.
    *
    * @phpstan-pure
    * @throws void
    */
    public static function fqdn_bounds_from_url(
        string  $url,
        int     &$fqdn_offset  = 0,
        int     &$fqdn_length  = \PHP_INT_MAX,
        bool    $validate_result = true
    ) : bool
    {
        assert(self::fqdn_debug_print("fqdn_bounds_from_url(url='$url', fqdn_offset=$fqdn_offset, fqdn_length=$fqdn_length, ...)"));

        // Calculation that doesn't require `$fqdn_offset` or `$fqdn_length`.
        $url_length = \strlen($url);
        if ( $url_length === 0 ) {
            return false;
        }
        assert(0 < \strlen($url)); // To make PHPStan happy.

        // Mostly-atomic read of caller's state.
        $len     = $fqdn_length;
        $pos     = $fqdn_offset;

        // Input clipping
        assert(self::fqdn_debug_print("pos=$pos, len=$len"));
        if ( $pos < 0 ) {
            $pos = (-$pos) % $url_length; // Handle wrapping.
            if ( $pos !== 0 ) {
                $pos = $url_length - $pos;
            }
        }
        assert(self::fqdn_debug_print("pos=$pos, len=$len"));
        // We do `$url_length - $len < $pos` instead of `$url_length < $pos + $len`
        // because $len can (and often will) be \PHP_INT_MAX, which would
        // cause $pos+$len might overflow.
        if ( $url_length - $len < $pos ) {
            $len = $url_length - $pos;
            $end_pos = $url_length;
        } else {
            $end_pos = $pos + $len;
        }
        assert(self::fqdn_debug_print("pos=$pos, len=$len, end=$end_pos"));
        if ( $len === 0 || $end_pos <= $pos ) {
            return false;
        }
        $end_pos = $pos + $len;

        // Parsing logic.
        assert(self::fqdn_debug('in.',$url, $pos, $end_pos));
        $success = self::fqdn_bounds_from_url_parse(
            $url, $pos, $end_pos);
        if ( !$success ) {
            assert(self::fqdn_debug_print("returning `false`"));
            return false;
        }

        // Varying degrees of validation logic.
        $len = $end_pos - $pos;
        assert(self::fqdn_debug('out.',$url, $pos, $end_pos));

        $fqdn = \substr($url, $pos, $len);
        $at_start_of_str = ($fqdn_offset === $pos);
        if ( self::fqdn_is_ambiguous($fqdn, $at_start_of_str) ) {
            assert(self::fqdn_debug_print("returning `false`"));
            return false;
        }

        if ( !$validate_result ) {
            assert(self::fqdn_debug_print("returning `true`; pos=$pos, len=$len"));
            $fqdn_offset = $pos;
            $fqdn_length = $len;
            return true;
        }

        $valid_fqdn = \filter_var($fqdn, FILTER_VALIDATE_DOMAIN, FILTER_NULL_ON_FAILURE);
        if (isset($valid_fqdn)) {
            assert(self::fqdn_debug_print("returning `true`; pos=$pos, len=$len"));
            $fqdn_offset = $pos;
            $fqdn_length = $len;
            return true;
        }
        assert(self::fqdn_debug_print("returning `false`"));
        return false;
    }

    // Ideally it's like this:
    // param  non-empty-string  $url
    // param  int<0,max>        $pos
    // param  int<$pos+1,max>   $end_pos
    // (But PHPStan doesn't allow `$pos` as an int range parameter.)

    /**
    * @param  non-empty-string  $url
    * @param  int<0,max>        $pos
    * @param  int<1,max>        $end_pos
    */
    private static function fqdn_bounds_from_url_parse(
        string  $url,
        int     &$pos,
        int     &$end_pos
    ) : bool
    {
        assert(self::fqdn_debug('in.',$url, $pos, $end_pos));
        $peek_pos = $pos + \strcspn($url, ':/@', $pos, $end_pos-$pos);

        // Early return if there's no syntax to parse, and it's just an FQDN.
        if ( $peek_pos === $end_pos ) {
            return true;
        }
        assert(self::fqdn_debug('',$url, $peek_pos, $end_pos));

        // Otherwise, $url[$peek_pos] exists, and we have more parsing to do.
        $ch = $url[$peek_pos];

        // Check for user@host possibility.
        // This happens FIRST because '@' is the least-ambiguous
        // syntax token in the URL. Both ':' and '/' can mean different
        // things depending on where they appear relative to other
        // tokens, but '@' can only mean one thing. (Though it CAN appear
        // in the resource identifier portion of the URL, but we've
        // excluded that possibility by allowing our scanner to stop
        // at '/' first, and it didn't, so this @ genuinely comes first.)
        if ( $ch === '@' ) {
            $pos = $peek_pos + 1;
            return self::fqdn_from_start_of_url($url, $pos, $end_pos);
        }

        // Now we check for things like 'fqdn/path' and 'fqdn/path?foo=bar'
        // Note that stuff like 'fqdn/path@foo' is allowed, but we don't
        // have to do anything special for it, because the '@' is part
        // of the URI path in that/this situation.
        if ( $ch === '/' ) {
            if ( $pos === $peek_pos ) {
                // Zero-length FQDN = invalid.
                return false;
            }
            $end_pos = $peek_pos;
            return true;
        }

        // Why just this one, phpstan??
        // @phpstan-ignore  function.alreadyNarrowedType
        assert(self::fqdn_debug('',$url, $peek_pos, $end_pos));

        // Now things get slightly more complicated, because ':' can mean
        // a bunch of different things, depending on where it appears
        // and what comes after it.
        assert($ch === ':');
        if ( \str_starts_with(\substr($url, $peek_pos, $end_pos-$peek_pos), '://') ) {
            // Confirmed that there's a schema.
            $peek_pos += 3;
            assert(self::fqdn_debug('',$url, $peek_pos, $end_pos));

            if ( $peek_pos < $end_pos && $url[$peek_pos] === '/'
            &&  \str_starts_with(\substr($url, $pos, $end_pos-$pos), 'file:///') ) {
                // Special exception for the `file:///` scheme where
                // triple-slashes are allowed.
                $peek_pos++;
            }

            // If there's also login info, we can skip it and the schema.
            // (Note that our scanner stops at '/' also, because
            // 'foo/bar@baz' has a hostname 'foo' with NO user/login info.)
            $pos = $peek_pos;
            $peek_pos = $pos + \strcspn($url, '/@', $pos, $end_pos-$pos);
            if ( $peek_pos < $end_pos && $url[$peek_pos] === '@' ) {
                $pos = $peek_pos + 1;
            }

            // Parse the rest.
            assert(self::fqdn_debug('',$url, $pos, $end_pos));
            return self::fqdn_from_start_of_url($url, $pos, $end_pos);
        }

        // Non-schema things.
        // We've found a ':', which is either the delimiter for
        // a 'username:password' string, or the delimiter for a
        // 'fqdn:port' string, (or something invalid)
        // 'and we have to determine which.
        $start_pos = $pos;
        $pos = $peek_pos;
        assert(self::fqdn_debug('',$url, $pos, $end_pos));
        $peek_pos++; // Skip the ':'
        $peek_pos += \strcspn($url, '/@', $peek_pos, $end_pos-$peek_pos);
        if ( $peek_pos === $end_pos ) {
            // EOS.
            // Saying it's '/' is a lie. But it's the correct lie.
            // Because the implications for '/' and EOS are the same.
            $ch = '/';
        } else {
            $ch = $url[$peek_pos];
        }

        // If the next character is '/' or we're at EOS, then
        // it's a string like `fqdn:port/path` or `fqdn:port`.
        // (No @ past the ':' implies it's not a `user:pass` string.)
        if ( $ch === '/' ) {
            // We can treat the stuff before ':' as a hostname/fqdn.
            if ( $start_pos === $pos ) { // Zero-length FQDN.
                return false;
            }
            // Move our {$pos,$end_pos} back to before the ':'.
            // (Beware: Order of operations.)
            $end_pos = $pos;
            $pos = $start_pos;
            return true;
        }

        // This case is `username:password@fqdn:pORt/whatever`.
        // Which is easy enough because we've identified where the start
        // of the FQDN would be. We just need to truncate the '@' and
        // everything before it from the result.
        assert($ch === '@');
        $pos = $peek_pos;
        $pos++; // skip the '@'

        // Parse the rest.
        assert(self::fqdn_debug('',$url, $pos, $end_pos));
        return self::fqdn_from_start_of_url($url, $pos, $end_pos);
    }

    // Ideally it's like this:
    // param  non-empty-string  $url
    // param  int<0,max>        $pos
    // param  int<$pos,max>     $end_pos
    // (But PHPStan doesn't allow `$pos` as an int range parameter.)
    // (Also, we use `int<1,max>` because $end_pos actually shouldn't be 0,
    // though if it's non-zero, then it CAN be equal to $pos.)

    /**
    * @param  non-empty-string  $url
    * @param  int<0,max>        $pos
    * @param  int<1,max>        $end_pos
    */
    private static function fqdn_from_start_of_url(
        string  $url,
        int     &$pos,
        int     &$end_pos
    ) : bool
    {
        $peek_pos = $pos + \strcspn($url, ':/', $pos, $end_pos-$pos);
        if ( $pos === $peek_pos ) { // Zero-length FQDN.
            return false;
        }

        assert(self::fqdn_debug('',$url, $pos, $peek_pos));
        if ( $peek_pos === $end_pos ) {
            // EOS.
            // Once again, the implications for '/' and EOS are the same.
            $ch = '/';
        } else {
            $ch = $url[$peek_pos];
        }

        // If the next character is '/' or we're at EOS, then
        // it's a string like `fqdn/path` or `fqdn`.
        if ( $ch === '/' ) {
            // $pos = $pos
            $end_pos = $peek_pos;
            return true;
        }

        assert($ch === ':');
        if ( \str_starts_with(\substr($url, $peek_pos, $end_pos), '://') ) {
            $peek_pos += 3;
            if ( $peek_pos < $end_pos && $url[$peek_pos] !== '/' ) {
                // Reject invalid: this function shall not be called with
                // a URL contain a schema. (In the broader context, this means
                // that a schema separator token is appearing in a place
                // where it shouldn't. And we consider the schema separator
                // to be unambiguous enough that it isn't just a missing
                // port followed by a couple missing URI path segments.)
                return false;
            }
        }

        // We've excluded all of the other possibilities now.
        // Everything up to the ':' is the FQDN.
        // $pos = $pos
        $end_pos = $peek_pos;
        assert(self::fqdn_debug('',$url, $pos, $end_pos));
        return true;
    }

    private static int  $total_permutations = 0;
    private static int  $n_permutations_passed = 0;
    private static bool $do_tests = true;

    /**
    * @template T of scalar
    * @param \Closure(string,int,int<0,max>,bool):T  $fqdn_func
    * @param \Closure(string):T  $fqdn_to_return_value
    */
    private static function print_fqdn_func_test_details(
        string   $func_name,
        \Closure $fqdn_func,
        \Closure $fqdn_to_return_value,
        bool   $expect_pass,
        string $text,
        string $url,
        int    $offset,
        int    $length,
        string $prefix, // Part of string before the URL to ignore
        string $suffix, // Part of string after the URL to ignore
        string $schema, // AKA protocol, including '://'
        string $user,   // Username, including ':' if providing password, otherwise including '@'
        string $pass,   // Password, including '@'
        string $fqdn,   // The Fully Qualified Domain Name
        string $port,   // Port, with preceding ':'
        string $rpath,  // Resource path; with preceding '/'
        bool   $validate
    ) : string
    {
        $total_tests  = \strval(self::$total_permutations);
        $n_passed_str = \strval(self::$n_permutations_passed);
        $percent_pass = \intdiv(self::$n_permutations_passed * 1000, self::$total_permutations);
        $percent_pass_str = \strval(\intdiv($percent_pass,10)) .'.'. \strval($percent_pass % 10);
        $validate_str = $validate ? 'true' : 'false';
        $offset_str = \strval($offset);
        $length_str = ($length === \PHP_INT_MAX) ? '\PHP_INT_MAX' : \strval($length);
        return "\n".
            "  details:\n".
            "    text:   '$text'\n".
            "    url:    '$url'\n".
            "    offset: $offset_str\n".
            "    length: $length_str\n".
            "    prefix: '$prefix'\n".
            "    suffix: '$suffix'\n".
            "    schema: '$schema'\n".
            "    user:   '$user'\n".
            "    pass:   '$pass'\n".
            "    fqdn:   '$fqdn'\n".
            "    port:   '$port'\n".
            "    rpath:  '$rpath'\n".
            "    validate: $validate_str\n".
            "\n".
            "  testing progress for $func_name:\n".
            "    $n_passed_str other parametric assertions passed.\n".
            "    $total_tests total parametric assertions possible for this function.\n".
            "    Percentage passed: $percent_pass_str%\n".
            "\n";
    }

    /**
    * @template T of scalar
    * @param \Closure(string,int,int<0,max>,bool):T  $fqdn_func
    * @param \Closure(string):T  $fqdn_to_return_value
    */
    private static function test_fqdn_func_with_params(
        string   $func_name,
        \Closure $fqdn_func,
        \Closure $fqdn_to_return_value,
        bool   $valid_schema,
        string $prefix, // Part of string before the URL to ignore
        string $suffix, // Part of string after the URL to ignore
        string $schema, // AKA protocol, including '://'
        string $user,   // Username, including ':' if providing password, otherwise including '@'
        string $pass,   // Password, including '@'
        string $fqdn,   // The Fully Qualified Domain Name
        string $port,   // Port, with preceding ':'
        string $rpath,  // Resource path; with preceding '/'
        bool   $validate
    ) : bool
    {
        if (!self::$do_tests) {
            self::$n_permutations_passed++;
            return true;
        }

        // Unfortunately, the various URL components can combine
        // to form URLs that have entirely different components.
        // Example:
        //   schema='http:' + user='user@' + pass='' + fqdn='foo'
        // creates the url 'http:user@foo', which is ambiguous with
        //   schema='' + user='http:' + pass='user@' + fqdn='foo'
        // (Which is valid, albeit a bit weird.)
        //
        // To work around these things, we must do some calculations
        // to figure out _what actually SHOULD pass_, as well as what
        // the actual FQDN in the tested URL would look like to the
        // parser.
        $expect_fqdn = $fqdn;

        if (\str_ends_with($schema,':/') && 0 < \strlen($rpath)
        &&  0 === \strlen($user) &&  0 === \strlen($pass)
        &&  0 === \strlen($fqdn) &&  0 === \strlen($port) ) {
            // This set of circumstances causes false-pos.
            // Because it results in things like url='http://foo',
            // which is valid as a whole, but did not have a valid schema ('http:/').
            $valid_schema = true;
            $expect_fqdn = \substr($rpath, 1);
        }

        // The '@' character easily dictates where the FQDN/hostname are
        // in a URL. If it's present, then we _know_ that what follows it
        // is the FQDN.
        $have_at = ($valid_schema || \str_ends_with($schema, ':')) && (\str_ends_with($user,'@') || \str_ends_with($pass,'@'));
        assert(self::fqdn_debug_bool('have_at', $have_at));

        // These can disambiguate an ambiguous FQDN, so we need to know if they exist.
        $at_start_of_str = (0 === \strlen($schema) && 0 === \strlen($user) && 0 === \strlen($pass));
        assert(self::fqdn_debug_bool('at_start_of_str', $at_start_of_str));

        // Ambiguity is important for testing FQDNs.
        $fqdn_is_ambiguous = (!$valid_schema || 0 === \strlen($schema)) && !$have_at && self::fqdn_is_ambiguous($expect_fqdn, $at_start_of_str);
        assert(self::fqdn_debug_bool('fqdn_is_ambiguous', $fqdn_is_ambiguous));

        // Most basic passing condition: have an unambiguous FQDN.
        $expect_pass = (0 < \strlen($expect_fqdn)) && !$fqdn_is_ambiguous;
        assert(self::fqdn_debug_bool('expect_pass', $expect_pass));
        assert(self::fqdn_debug_bool('valid_schema', $valid_schema));

        // If we have a nonzero-length schema like the "foo" in `foo:`, then that
        // can be interpreted as an incomplete URL (or malformed URL) where
        // the port is missing or incorrect. (We also check $have_at because
        // it would supercede anything involving the schema.)
        if ( !$have_at && !$valid_schema ) {
            // This works because both ':' and '/' can come after a hostname/FQDN:
            // 'foo:' is 'host:port' notation, and 'foo/bar' is a host with a resource.
            $tmp_fqdn_endpos = \strcspn($schema,':/');
            if ( 0 === $tmp_fqdn_endpos ) {
                $expect_pass = false; // If it's unambiguously a borked schema, then don't pass it.
            } else {
                $tmp_fqdn = \substr($schema, 0, $tmp_fqdn_endpos);
                $expect_pass = !self::fqdn_is_ambiguous($tmp_fqdn, true);
                if ($expect_pass) {
                    $expect_fqdn = $tmp_fqdn;
                }
            }
        }

        assert(self::fqdn_debug_bool('expect_pass', $expect_pass));
        if (!$expect_pass) {
            $expect_fqdn = '';
        }

        // Now that we know what to expect, we can begin the test.
        $prefix_len = \strlen($prefix);
        $suffix_len = \strlen($suffix);
        $url  = $schema . $user . $pass . $fqdn . $port . $rpath;
        $text = $prefix . $url . $suffix;
        $offset = $prefix_len;
        $length = \strlen($url);
        $pos    = $offset;
        $nchars = $length;

        $result = $fqdn_func($text, $pos, $nchars, $validate);
        $result_str = is_string($result) ? "'".$result."'"
            : (is_bool($result) ? ($result ? '`true`' : '`false`')
            : \strval($result));

        if ( $expect_pass ) {
            $expected = $fqdn_to_return_value($expect_fqdn);
            $expected_str = is_string($expected) ? "'".$expected."'"
                : (is_bool($expected) ? ($expected ? '`true`' : '`false`')
                : \strval($expected));

            assert($result === $expected, "\n".
                "  $func_name('$text',...)\n".
                "    returned $result_str\n".
                "    expected $expected_str\n".
                self::print_fqdn_func_test_details(
                    $func_name, $fqdn_func, $fqdn_to_return_value, $expect_pass, $text, $url, $offset, $length,
                    $prefix, $suffix, $schema, $user, $pass, $fqdn, $port, $rpath, $validate)
            );
        } else {
            assert($result === false || $result === '', "\n".
                "  $func_name('$text',...)\n".
                "    returned $result_str\n".
                "    expected `false` or empty string\n".
                self::print_fqdn_func_test_details(
                    $func_name, $fqdn_func, $fqdn_to_return_value, $expect_pass, $text, $url, $offset, $length,
                    $prefix, $suffix, $schema, $user, $pass, $fqdn, $port, $rpath, $validate)
                );
        }

        self::$n_permutations_passed++;

        // Return bool just so that we can call this from inside an arrow function.
        return true;
    }

    /**
    * @template T of scalar
    * @param \Closure(string,int,int<0,max>,bool):T  $fqdn_func
    * @param \Closure(string):T  $fqdn_to_return_value
    */
    private static function test_fqdn_func_with_fqdn_as(
        string   $func_name,
        \Closure $fqdn_func,
        \Closure $fqdn_to_return_value,
        bool   $valid_schema,
        string $prefix, // Part of string before the URL to ignore
        string $suffix, // Part of string after the URL to ignore
        string $schema, // AKA protocol, including '://'
        string $user,   // Username, including ':' if providing password, otherwise including '@'
        string $pass,   // Password, including '@'
        string $fqdn,   // The Fully Qualified Domain Name
        string $port,   // Port, with preceding ':'
        string $rpath,  // Resource path; with preceding '/'
        bool   $validate
    ) : bool
    {
        try
        {
            return self::test_fqdn_func_with_params(
                $func_name, $fqdn_func, $fqdn_to_return_value, $valid_schema,
                $prefix, $suffix, $schema, $user, $pass, $fqdn, $port, $rpath, $validate);
        }
        catch(\Throwable $e)
        {
            self::$do_fqdn_debug = true;
            return self::test_fqdn_func_with_params(
                $func_name, $fqdn_func, $fqdn_to_return_value, $valid_schema,
                $prefix, $suffix, $schema, $user, $pass, $fqdn, $port, $rpath, $validate);
        }
    }

    /**
    * @template T of scalar
    * @param \Closure(string,int,int<0,max>,bool):T  $fqdn_func
    * @param \Closure(string):T  $fqdn_to_return_value
    */
    private static function test_fqdn_func_with_affixes_as(
        string   $func_name,
        \Closure $fqdn_func,
        \Closure $fqdn_to_return_value,
        bool   $valid_schema,
        string $prefix, // Part of string before the URL to ignore
        string $suffix, // Part of string after the URL to ignore
        string $schema, // AKA protocol, including '://'
        string $user,   // Username, including ':' if providing password, otherwise including '@'
        string $pass,   // Password, including '@'
        string $port,   // Port, with preceding ':'
        string $rpath,  // Resource path; with preceding '/'
        bool   $validate
    ) : bool
    {
        $test = fn(string $fqdn):bool =>
            self::test_fqdn_func_with_fqdn_as(
                $func_name, $fqdn_func, $fqdn_to_return_value, $valid_schema,
                $prefix, $suffix, $schema, $user, $pass, $fqdn, $port, $rpath, $validate);

        $test('foo')          ;
        $test('foo.bar')      ;
        $test('foo.bar.com')  ;
        $test('bar')          ;
        $test('bar.baz')      ;
        $test('bar.baz.com')  ;
        $test('localhost')    ;
        $test('localhost.com');
        $test('127.0.0.1')    ;

        $test('http');
        $test('https');

        $test('');

        // Return bool just so that we can call this from inside an arrow function.
        return true;
    }


    /**
    * @template T of scalar
    * @param \Closure(string,int,int<0,max>,bool):T  $fqdn_func
    * @param \Closure(string):T  $fqdn_to_return_value
    */
    private static function test_fqdn_func_with_schema_as(
        string   $func_name,
        \Closure $fqdn_func,
        \Closure $fqdn_to_return_value,
        bool   $valid_schema,
        string $schema,
        string $user,
        string $pass,
        string $port,
        string $rpath,
        bool $validate) : void
    {
        $test = fn(string $prefix, string $suffix):bool =>
            self::test_fqdn_func_with_affixes_as(
                $func_name, $fqdn_func, $fqdn_to_return_value, $valid_schema,
                $prefix, $suffix, $schema, $user, $pass, $port, $rpath, $validate);

        $test(   '',   '');
        $test(   '','foo');
        $test('foo',   '');
        $test('abc','def');

        $test(   '','/foo');
        $test('abc','/def');
        $test(   '','/foo/bar');
        $test('abc','/def/ghi');

        $test(       '','http://');
        $test('http://',       '');
        $test('http://','http://');

        $test(                   '','http://example.com');
        $test('http://example.com/',                  '');
        $test('http://example.com/','http://example.com');
    }


    /**
    * @template T of scalar
    * @param \Closure(string,int,int<0,max>,bool):T  $fqdn_func
    * @param \Closure(string):T  $fqdn_to_return_value
    */
    private static function test_fqdn_func_with_fqdn_params_as(
        string   $func_name,
        \Closure $fqdn_func,
        \Closure $fqdn_to_return_value,
        string $user,
        string $pass,
        string $port,
        string $rpath,
        bool $validate) : void
    {
        $ftrv = $fqdn_to_return_value;
        //                                                              valid_schema,  schema,  user,  pass,  port,  rpath,  validate
        self::test_fqdn_func_with_schema_as($func_name, $fqdn_func, $ftrv,    true,        '', $user, $pass, $port, $rpath, $validate);
        self::test_fqdn_func_with_schema_as($func_name, $fqdn_func, $ftrv,   false,       ':', $user, $pass, $port, $rpath, $validate);
        self::test_fqdn_func_with_schema_as($func_name, $fqdn_func, $ftrv,   false,       '/', $user, $pass, $port, $rpath, $validate);
        self::test_fqdn_func_with_schema_as($func_name, $fqdn_func, $ftrv,   false,      '//', $user, $pass, $port, $rpath, $validate);
        self::test_fqdn_func_with_schema_as($func_name, $fqdn_func, $ftrv,    true,     '://', $user, $pass, $port, $rpath, $validate);
        self::test_fqdn_func_with_schema_as($func_name, $fqdn_func, $ftrv,   false,   'http:', $user, $pass, $port, $rpath, $validate);
        self::test_fqdn_func_with_schema_as($func_name, $fqdn_func, $ftrv,   false,  'http:/', $user, $pass, $port, $rpath, $validate);
        self::test_fqdn_func_with_schema_as($func_name, $fqdn_func, $ftrv,    true, 'http://', $user, $pass, $port, $rpath, $validate);
        self::test_fqdn_func_with_schema_as($func_name, $fqdn_func, $ftrv,   false,    'foo:', $user, $pass, $port, $rpath, $validate);
        self::test_fqdn_func_with_schema_as($func_name, $fqdn_func, $ftrv,   false,   'foo:/', $user, $pass, $port, $rpath, $validate);
        self::test_fqdn_func_with_schema_as($func_name, $fqdn_func, $ftrv,    true,  'foo://', $user, $pass, $port, $rpath, $validate);
    }


    /**
    * @template T of scalar
    * @param \Closure(string,int,int<0,max>,bool):T  $fqdn_func
    * @param \Closure(string):T  $fqdn_to_return_value
    */
    private static function test_fqdn_func_with_validate_as(
        string   $func_name,
        \Closure $fqdn_func,
        \Closure $fqdn_to_return_value,
        bool $validate
    ) : void
    {
        $ftrv = $fqdn_to_return_value;
        //                                                                         user,   password,  port,  rpath, validate
        self::test_fqdn_func_with_fqdn_params_as($func_name, $fqdn_func, $ftrv,      '',         '',    '',     '', $validate);
        self::test_fqdn_func_with_fqdn_params_as($func_name, $fqdn_func, $ftrv,      '',         '',    '', '/foo', $validate);
        self::test_fqdn_func_with_fqdn_params_as($func_name, $fqdn_func, $ftrv,      '',         '', ':80',     '', $validate);
        self::test_fqdn_func_with_fqdn_params_as($func_name, $fqdn_func, $ftrv,      '',         '', ':80', '/foo', $validate);
        self::test_fqdn_func_with_fqdn_params_as($func_name, $fqdn_func, $ftrv, 'user@',         '',    '',     '', $validate);
        self::test_fqdn_func_with_fqdn_params_as($func_name, $fqdn_func, $ftrv, 'user@',         '',    '', '/foo', $validate);
        self::test_fqdn_func_with_fqdn_params_as($func_name, $fqdn_func, $ftrv, 'user@',         '', ':80',     '', $validate);
        self::test_fqdn_func_with_fqdn_params_as($func_name, $fqdn_func, $ftrv, 'user@',         '', ':80', '/foo', $validate);
        self::test_fqdn_func_with_fqdn_params_as($func_name, $fqdn_func, $ftrv, 'user:', 'hunter2@',    '',     '', $validate);
        self::test_fqdn_func_with_fqdn_params_as($func_name, $fqdn_func, $ftrv, 'user:', 'hunter2@',    '', '/foo', $validate);
        self::test_fqdn_func_with_fqdn_params_as($func_name, $fqdn_func, $ftrv, 'user:', 'hunter2@', ':80',     '', $validate);
        self::test_fqdn_func_with_fqdn_params_as($func_name, $fqdn_func, $ftrv, 'user:', 'hunter2@', ':80', '/foo', $validate);
    }

    /**
    * @template T of scalar
    * @param \Closure(string,int,int<0,max>,bool):T  $fqdn_func
    * @param \Closure(string):T  $fqdn_to_return_value
    */
    private static function test_fqdn_func_parametrically(
        string   $func_name,
        \Closure $fqdn_func,
        \Closure $fqdn_to_return_value
    ) : void
    {
        $ftrv = $fqdn_to_return_value;

        // First pass: get total permutations.
        self::$total_permutations = 0;
        self::$n_permutations_passed = 0;
        self::$do_tests = false;

        self::test_fqdn_func_with_validate_as($func_name, $fqdn_func, $ftrv, true);
        self::test_fqdn_func_with_validate_as($func_name, $fqdn_func, $ftrv, false);

        // Second pass: run the tests.
        self::$total_permutations = self::$n_permutations_passed;
        self::$n_permutations_passed = 0;
        self::$do_tests = true;

        self::test_fqdn_func_with_validate_as($func_name, $fqdn_func, $ftrv, true);
        self::test_fqdn_func_with_validate_as($func_name, $fqdn_func, $ftrv, false);

        //$n_passed = self::$n_permutations_passed;
        //echo "$func_name passed $n_passed parametric tests\n";
    }

    private static function test_fqdn_bounds_from_url(bool $validate) : void
    {
        $does_fqdn_parse =
            function(string $url)
            use($validate)
                :bool
        {
            $dummy_fqdn_offset = 0;
            $dummy_fqdn_length = \PHP_INT_MAX;
            return
                self::fqdn_bounds_from_url(
                    $url, $dummy_fqdn_offset, $dummy_fqdn_length, $validate);
        };

        $xfail =
            function(string $url)
            use($validate, $does_fqdn_parse)
                :void
        {
            $validate_str = $validate ? 'true' : 'false';
            assert(!$does_fqdn_parse($url),
                "fqdn_bounds_from_url('$url', 0, \PHP_INT_MAX, $validate_str) returned `true`; expected `false`.");
        };

        $xfail('');
        $xfail('https');
        $xfail('https:');
        $xfail('https:/');
        $xfail('https://');
        $xfail('https:///');
        $xfail('https:///foo');
        $xfail('/localhost');
        $xfail('//localhost');
        //$xfail('localhost:'); // somewhat valid: it's just a hostname with a missing port.
        $xfail('/localhost:');
        $xfail('//localhost::');
        $xfail('/localhost://');
        //$xfail('foo:bar'); // regretably valid: the function does not validate URLs ('foo' is the hostname, 'bar' is an invalid port)
    }

    private static function unittest_fqdn_bounds_from_url() : void
    {
        echo ("  ".__FUNCTION__."()\n");

        self::test_fqdn_bounds_from_url(true);
        self::test_fqdn_bounds_from_url(false);

        self::test_fqdn_func_parametrically(
            'fqdn_bounds_from_url',
            self::fqdn_bounds_from_url(...),
            fn(string $fqdn):bool => 0 < \strlen($fqdn));
    }

    /**
    * Extracts the Fully Qualified Domain Name component of the URL.
    *
    * In cases where the fqdn is also the hostname (ex: localhost), this
    * can serve as a way to extract the hostname:
    * ```
    * assert(Str::fqdn_from_url('https://localhost/foo') === 'localhost');
    * ```
    *
    * Note that in more complicated scenarios, it will return more than
    * just the hostname, in accordance with the definition of a domain:
    * ```
    * assert(Str::fqdn_from_url('https://foo.bar.com') === 'foo.bar.com');
    * // (The hostname itself would be considered 'foo'.)
    * ```
    *
    * Returns an empty string if no valid fqdn was found.
    *
    * If `$validate_result` is set to `false`, this may return part of the
    * `$url` string instead of an empty string for some invalid fqdns/hostnames.
    *
    * Features:
    * * Performs no explicit memory allocation.
    * * Does not depend on PCRE extension (portability).
    * * Works on partial, incomplete, or malformed URLs (as long as it's unambiguous).
    * * Thread-safe and reentrant.
    * * Handles URLs with unicode characters (when `$validate_result = false`).
    *
    * This function avoids any explicit memory allocation.
    * (Caveat: Setting $validate_result to `true` will cause `\filter_var`
    * to be called with `FILTER_VALIDATE_DOMAIN`, and it is unknown if that
    * allocates. Although unlikely, it may even perform I/O. We don't know.)
    *
    * The avoidance of explicit memory allocation is why this function
    * might be preferred over the built-in `parse_url` function.
    *
    * Caveat: This might not be directly _faster_ than `parse_url`, because
    * this function is not implemented directly in the Zend engine.
    * Microbenchmarks _might_ prefer `parse_url`. (It's easily possible.)
    * However, because this function does not require memory allocations,
    * it will allow a process to behave better "under pressure" and reduces
    * cache misses in other areas of code, thus potentially making
    * "whole program" performance considerable better.
    *
    * This function also does NOT depend on the PCRE extension, which makes
    * it portable to environments without that. (It is also likely that
    * the PCRE extension would need to allocate memory, if it were to be used.
    * And if no other PHP code uses it, then we can avoid loading the
    * entire .so/extension into memory.)
    *
    * The `$offset` and `$length` parameters determine which part of the
    * string will be scanned.
    *
    *
    * @param  int<0,max>  $length
    *
    * @phpstan-pure
    * @throws void
    */
    public static function fqdn_from_url(
        string  $url,
        bool    $validate_result = true,
        int     $offset          = 0,
        int     $length          = \PHP_INT_MAX
    ) : string
    {
        $fqdn_pos = $offset;
        $fqdn_len = $length;
        $success = self::fqdn_bounds_from_url(
            $url, $fqdn_pos, $fqdn_len, $validate_result);
        if ($success) {
            assert(self::fqdn_debug_print("returning `true`; fqdn_pos=$fqdn_pos, fqdn_len=$fqdn_len"));
            return \substr($url, $fqdn_pos, $fqdn_len);
        }
        assert(self::fqdn_debug_print("returning `false`"));
        return '';
    }

    private static function unittest_fqdn_from_url() : void
    {
        echo ("  ".__FUNCTION__."()\n");

        //self::$do_fqdn_debug = true;

        // Fundamentals.
        assert(self::fqdn_from_url('',           false) === '');
        assert(self::fqdn_from_url('foo',        false) === 'foo');
        assert(self::fqdn_from_url('http://foo', false) === 'foo');

        // Positive offsets. No wrap-around allowed.
        assert(self::fqdn_from_url('foo', false, 0)  === 'foo');
        assert(self::fqdn_from_url('foo', false, 3)  === '');
        assert(self::fqdn_from_url('foo', false, 4)  === '');
        assert(self::fqdn_from_url('foo', false, 6)  === '');

        // Negative offsets and wrap-around behavior.
        assert(self::fqdn_from_url('foo', false, -3) === 'foo');
        assert(self::fqdn_from_url('foo', false, -6) === 'foo');
        assert(self::fqdn_from_url('foo', false, -9) === 'foo');
        assert(self::fqdn_from_url('foo', false, -1) === 'o');
        assert(self::fqdn_from_url('foo', false, -4) === 'o');
        assert(self::fqdn_from_url('foo', false, -7) === 'o');
        assert(self::fqdn_from_url('foo', false, -2) === 'oo');
        assert(self::fqdn_from_url('foo', false, -5) === 'oo');
        assert(self::fqdn_from_url('foo', false, -8) === 'oo');

        // Length bounding.
        assert(self::fqdn_from_url('abc', false, 0, 0)  === '');
        assert(self::fqdn_from_url('abc', false, 0, 1)  === 'a');
        assert(self::fqdn_from_url('abc', false, 0, 2)  === 'ab');
        assert(self::fqdn_from_url('abc', false, 0, 3)  === 'abc');
        assert(self::fqdn_from_url('abc', false, 0, 4)  === 'abc');
        assert(self::fqdn_from_url('abc', false, 0, 6)  === 'abc');
        assert(self::fqdn_from_url('abc', false, 0, \PHP_INT_MAX)  === 'abc');

        // Length bounding + offsets
        assert(self::fqdn_from_url('abc', false, 1, 0)  === '');
        assert(self::fqdn_from_url('abc', false, 1, 1)  === 'b');
        assert(self::fqdn_from_url('abc', false, 1, 2)  === 'bc');
        assert(self::fqdn_from_url('abc', false, 1, 3)  === 'bc');
        assert(self::fqdn_from_url('abc', false, 1, 4)  === 'bc');
        assert(self::fqdn_from_url('abc', false, 1, 6)  === 'bc');
        assert(self::fqdn_from_url('abc', false, 1, \PHP_INT_MAX)  === 'bc');

        assert(self::fqdn_from_url('abc', false, -1, 0)  === '');
        assert(self::fqdn_from_url('abc', false, -1, 1)  === 'c');
        assert(self::fqdn_from_url('abc', false, -1, 2)  === 'c');
        assert(self::fqdn_from_url('abc', false, -1, 3)  === 'c');
        assert(self::fqdn_from_url('abc', false, -1, 4)  === 'c');
        assert(self::fqdn_from_url('abc', false, -1, 6)  === 'c');
        assert(self::fqdn_from_url('abc', false, -1, \PHP_INT_MAX)  === 'c');

        // Corner cases
        assert(self::fqdn_from_url('foo:bar',  false) === 'foo');
        assert(self::fqdn_from_url('foo:bar',  true)  === 'foo');

        // File schema is a special-case that allows triple-slashes.
        assert(self::fqdn_from_url('file:///foo', false) === 'foo');
        assert(self::fqdn_from_url('http:///foo', false) === '');

        // Parametric testing to be very thorough.
        self::test_fqdn_func_parametrically(
            'fqdn_from_url',
            fn(string  $url, int $offset, int $length, $validate)
                => self::fqdn_from_url($url, $validate, $offset, $length),
            fn(string $fqdn):string => $fqdn);
    }



    /**
    * Runs all unittests defined in the Str class.
    */
    //public static function unittest(TestRunner $runner) : void
    public static function unittests() : void
    {
        // $runner->note("Running `$class_fqn::unittests()`\n");
        $class_fqn = self::class;
        echo("Running `$class_fqn::unittests()`\n");

        self::unittest_is_longer_than();
        self::unittest_normalize_path();
        self::unittest_remove_path_prefix();
        self::unittest_next_newline();
        self::unittest_is_ascii_identifier();
        self::unittest_unchecked_blit_fwd();
        self::unittest_unchecked_blit_rev();
        self::unittest_unchecked_blit();
        self::unittest_substr_replace_inplace();
        self::unittest_fqdn_bounds_from_url();
        self::unittest_fqdn_from_url();
        //self::unittest_to_string($runner);

        // $runner->note("  ... passed.\n\n");
        echo("  ... passed.\n\n");
        //self::to_string("xyz");
    }
}
?>
