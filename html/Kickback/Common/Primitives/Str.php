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
        //self::unittest_to_string($runner);

        // $runner->note("  ... passed.\n\n");
        echo("  ... passed.\n\n");
        //self::to_string("xyz");
    }
}
?>
