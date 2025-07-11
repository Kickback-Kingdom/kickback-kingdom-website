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

        echo("  ".__FUNCTION__."()\n");
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
                if ( $elem === '..' || 0 === strlen($elem) ) {
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
        assert(self::normalize_path('/foo/./bar/..//baz/') === '/foo/baz');
        assert(self::normalize_path('../foo/.') === '../foo');
        assert(self::normalize_path('/foo/bar/baz/') === '/foo/bar/baz');
        assert(self::normalize_path('/foo/./bar/../../baz') === '/baz');

        // ---------------------------------------------------------------------
        // The below tests were taken from the D standard library on 2025-07-10
        // Some of them might be redundant with the other tests above,
        // but that doesn't hurt anything.
        //
        // Source: https://github.com/dlang/phobos/blob/v2.111.0/std/path.d#L2086
        assert(self::normalize_path('/foo/bar') === '/foo/bar');
        assert(self::normalize_path('foo/bar/baz') === 'foo/bar/baz');
        assert(self::normalize_path('foo/bar/baz') === 'foo/bar/baz');
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
        assert(self::normalize_path('foo/bar') === 'foo/bar');

        // Correct handling of leading slashes
        assert(self::normalize_path('/') === '/');
        assert(self::normalize_path('///') === '/');
        assert(self::normalize_path('////') === '/');
        assert(self::normalize_path('/foo/bar') === '/foo/bar');
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
        assert(self::normalize_path('/foo/../..') === '/');

        // The ultimate path
        assert(self::normalize_path('/foo/../bar//./../...///baz//') === '/.../baz');

        // End of D standard library unittests.

        echo("  ".__FUNCTION__."()\n");
    }

    public static function unittests() : void
    {
        $class_fqn = self::class;
        echo("Running `$class_fqn::unittests()`\n");

        self::unittest_is_longer_than();
        self::unittest_normalize_path();

        echo("  ... passed.\n\n");
    }
}
?>
