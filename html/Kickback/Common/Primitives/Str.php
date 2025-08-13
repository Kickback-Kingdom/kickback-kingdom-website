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
        self::unittest_next_newline();
        self::unittest_is_ascii_identifier();
        self::unittest_unchecked_blit_fwd();
        self::unittest_unchecked_blit_rev();
        self::unittest_unchecked_blit();
        self::unittest_substr_replace_inplace();
        //self::unittest_to_string($runner);

        // $runner->note("  ... passed.\n\n");
        echo("  ... passed.\n\n");
        //self::to_string("xyz");
    }
}
?>
