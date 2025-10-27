<?php
declare(strict_types=1);
// NOTE: The code in this file is intended to ONLY be used by the
//     PHPStanConfig scripts, or any other "meta" scripts.
//     This is NOT to be used on other Kickback projects like
//     the website, backend, etc.
//     IF you need string interpolation in one of those, I would
//     recommend copy-pasting the code to the Kickback\Common\Primitives\Str
//     class. Although this might sound objectionable, these codebases
//     really do have quite different environments. The meta scripts,
//     for instance, do not use autoloading, and do not worry as much
//     about memory allocation concerns (which is much more important
//     on the "data path"). As long as everything is unittested
//     (and this code is, thankfully, quite amenable to unittesting),
//     then it should be easy enough to modify-for-purpose. It would
//     not be necessary to keep such different versions in-sync.
//     And the requirements for the 'meta' version should be very
//     loose in terms of what _functionality_ is needed; what you
//     see below is already tremendously overbuilt.

require_once(__DIR__.'/PHPStanConfig_System.php');

/**
* @param      int<0,max>     $slice_offset
* @param      int<0,max>     $slice_len
* @param      int<0,max>     $start_pos
* @param      ?int           $outer_lo
* @param-out  ?int<0,max>    $outer_lo
* @param      ?int           $inner_lo
* @param-out  ?int<1,max>    $inner_lo
* @param      ?int           $inner_hi
* @param-out  ?int<2,max>    $inner_hi
* @param      ?int           $outer_hi
* @param-out  ?int<2,max>    $outer_hi
* @param      int<0,max>     $depth
* @param-out  int<0,max>     $depth
*
* @phpstan-assert-if-true   =int<0,max>  $outer_lo
* @phpstan-assert-if-true   =int<1,max>  $inner_lo
* @phpstan-assert-if-true   =int<2,max>  $inner_hi
* @phpstan-assert-if-true   =int<2,max>  $outer_hi
*/
function str_tokenize_single_interpolation_pattern(
    string  $text,
    int     $slice_offset,  int $slice_len,
    int     $start_pos,
    ?int    &$outer_lo = null,
    ?int    &$inner_lo = null,
    ?int    &$inner_hi = null,
    ?int    &$outer_hi = null,
    int     &$depth = 0
) : bool
{
    return PHPStanConfig_StringInterpolation::
        tokenize_single_interpolation_pattern(
            $text, $slice_offset, $slice_len, $start_pos,
            $outer_lo, $inner_lo, $inner_hi, $outer_hi, $depth);
}

/**
* @param      array<array-key,mixed>  $env
* @param      string                  $src_text
* @param      int<0,max>              $src_slice_offset
* @param      int<0,max>              $src_slice_len
* @param      int<0,max>              $src_cursor
* @param-out  int<0,max>              $src_cursor
* @param      int<0,max>              $depth
* @param-out  int<0,max>              $depth
* @param      ?string                 $output_text
* @param-out  string                  $output_text
*
* @return bool  `false` if there was no valid interpolation syntax detected.
*/
function str_interpolate_first(
    array    $env,
    string   $src_text,
    int      $src_slice_offset,  int  $src_slice_len,
    int      &$src_cursor = 0,
    ?string  &$output_text = null,
    int      &$depth = 0
) : bool
{
    return PHPStanConfig_StringInterpolation::
        interpolate_first($env, $src_text,
            $src_slice_offset, $src_slice_len,
            $src_cursor, $output_text, $depth);
}

/**
* @param      array<mixed>   $env
* @param      int<0,max>     $slice_offset
* @param      int<0,max>     $slice_len
*/
function str_interpolate(
    array   $env,
    string  $text,
    int     $slice_offset = 0,
    int     $slice_len = \PHP_INT_MAX
) : string
{
    return PHPStanConfig_StringInterpolation::
        interpolate($env, $text, $slice_offset, $slice_len);
}

class PHPStanConfig_StringInterpolation_Instance
{
    public static function instance() : PHPStanConfig_StringInterpolation_Instance {
        return PHPStanConfig_StringInterpolation::instance();
    }

    /**
    * @param      int<0,max>     $slice_offset
    * @param      int<0,max>     $slice_len
    * @param      int<0,max>     $start_pos
    * @param      ?int           $outer_lo
    * @param-out  ?int<0,max>    $outer_lo
    * @param      ?int           $inner_lo
    * @param-out  ?int<1,max>    $inner_lo
    * @param      ?int           $inner_hi
    * @param-out  ?int<2,max>    $inner_hi
    * @param      ?int           $outer_hi
    * @param-out  ?int<2,max>    $outer_hi
    * @param      int<0,max>     $depth
    * @param-out  int<0,max>     $depth
    *
    * @phpstan-assert-if-true   =int<0,max>  $outer_lo
    * @phpstan-assert-if-true   =int<1,max>  $inner_lo
    * @phpstan-assert-if-true   =int<2,max>  $inner_hi
    * @phpstan-assert-if-true   =int<2,max>  $outer_hi
    */
    public function tokenize_single_interpolation_pattern(
        string  $text,
        int     $slice_offset,  int $slice_len,
        int     $start_pos,
        ?int    &$outer_lo = null,
        ?int    &$inner_lo = null,
        ?int    &$inner_hi = null,
        ?int    &$outer_hi = null,
        int     &$depth = 0
    ) : bool
    {
        return PHPStanConfig_StringInterpolation::
            tokenize_single_interpolation_pattern(
                $text, $slice_offset, $slice_len, $start_pos,
                $outer_lo, $inner_lo, $inner_hi, $outer_hi, $depth);
    }

    /**
    * @param      array<array-key,mixed>  $env
    * @param      string                  $src_text
    * @param      int<0,max>              $src_slice_offset
    * @param      int<0,max>              $src_slice_len
    * @param      int<0,max>              $src_cursor
    * @param-out  int<0,max>              $src_cursor
    * @param      ?string                 $output_text
    * @param-out  string                  $output_text
    * @param      int<0,max>              $depth
    * @param-out  int<0,max>              $depth
    *
    * @return bool  `false` if there was no valid interpolation syntax detected.
    */
    public function interpolate_first(
        array    $env,
        string   $src_text,
        int      $src_slice_offset,  int  $src_slice_len,
        int      &$src_cursor = 0,
        ?string  &$output_text = null,
        int      &$depth = 0
    ) : bool
    {
        return PHPStanConfig_StringInterpolation::
            interpolate_first($env, $src_text,
                $src_slice_offset, $src_slice_len,
                $src_cursor, $output_text, $depth);
    }

    /**
    * @param      array<mixed>   $env
    * @param      string         $text
    * @param      int<0,max>     $slice_offset
    * @param      int<0,max>     $slice_len
    */
    public function interpolate(
        array   $env,
        string  $text,
        int     $slice_offset = 0,
        int     $slice_len = \PHP_INT_MAX
    ) : string
    {
        return PHPStanConfig_StringInterpolation::
            interpolate($env, $text, $slice_offset, $slice_len);
    }

    /**
    * Wrapper around the `interpolate` method.
    *
    * This provides a handy way to create shorthand for string interpolation.
    *
    * Without any shorthand, the class names get kinda verbose:
    * ```
    * function my_func1(array $env, string $text) : string
    * {
    *     ... do stuff ...
    *     return PHPStanConfig_StringInterpolation::interpolate($text, $env);
    * }
    *
    * function my_func2(array $env, string $text) : string
    * {
    *     ... do stuff ...
    *     return PHPStanConfig_StringInterpolation::interpolate($text, $env);
    * }
    * ```
    *
    * We can get slightly better by assigning the instance to an object:
    * ```
    * $str15n = PHPStanConfig_StringInterpolation::instance();
    *
    * function my_func1(array $env, string $text) : string
    * {
    *     ... do stuff ...
    *     return $str15n->interpolate($text, $env);
    * }
    *
    * function my_func2(array $env, string $text) : string
    * {
    *     ... do stuff ...
    *     return $str15n->interpolate($text, $env);
    * }
    * ```
    *
    * That works, but trying to choose
    * a good shorthand instance name can be tricky:
    * * '$str' would be ambiguous and might lead the reader to believe it's a general string class. It isn't.
    * * '$str15n' is somewhat cryptic.
    * * '$str_interpolator->interpolate' is just plain redundant.
    *
    * So we can use the __invoke method to do the last of those,
    * but without the unnecessary redundancy of text:
    * ```
    * $str_interpolate = PHPStanConfig_StringInterpolation::instance();
    *
    * function my_func1(array $env, string $text) : string
    * {
    *     ... do stuff ...
    *     return $str_interpolate($env, $text);
    * }
    *
    * function my_func2(array $env, string $text) : string
    * {
    *     ... do stuff ...
    *     return $str_interpolate($env, $text);
    * }
    * ```
    *
    * This, of course, only works if the caller is going to be using
    * the `::interpolate' function, and nothing else.
    *
    * @param  array<array-key,mixed>  $env
    * @param  int<0,max>              $slice_offset
    * @param  int<0,max>              $slice_len
    */
    public function __invoke(
        array   $env,
        string  $text,
        int     $slice_offset = 0,
        int     $slice_len = \PHP_INT_MAX
    ) : string
    {
        return PHPStanConfig_StringInterpolation::
            interpolate($env, $text, $slice_offset, $slice_len);
    }
}

class PHPStanConfig_StringInterpolation
{
    private static function debug_msg(string $s) : bool { return PHPStanConfig_System::debug_msg($s); }
    private static function echo_msg(string $s) : bool { return PHPStanConfig_System::echo_msg($s); }

    public static function instance()
        : PHPStanConfig_StringInterpolation_Instance
    {
        static $instance_ = null;
        if (isset($instance_)) {
            return $instance_;
        }
        $instance_ = new PHPStanConfig_StringInterpolation_Instance();
        return $instance_;
    }

    public const SCAN_STATUS_FALLTHROUGH = 0;
    public const SCAN_STATUS_FOUND_MATCH = 1;
    public const SCAN_STATUS_RESET_SCAN  = 2;

    public const CH_DOLLAR      = 0x24;
    public const CH_PERCENT     = 0x25;
    public const CH_BACKSLASH   = 0x5C;
    public const CH_LOWER_E     = 0x65;
    public const CH_LOWER_F     = 0x66;
    public const CH_LOWER_N     = 0x6E;
    public const CH_LOWER_R     = 0x72;
    public const CH_LOWER_T     = 0x74;
    public const CH_LOWER_U     = 0x75;
    public const CH_LOWER_V     = 0x76;
    public const CH_LOWER_X     = 0x78;
    public const CH_CURLY_OPEN  = 0x7B;
    public const CH_CURLY_CLOSE = 0x7D;
    public const CH_TILDE       = 0x7E;

    private const IDENTIFIER_CHARS = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz';

    private const ENV_FOR_TESTING = [
        'FOO'  => 'x',
        'BAR'  => 'y',
        'BAZ'  => 'z',
        'LONG' => 'abcdefghijklmnop',
        'SAME' => 'same',
        'A'    => 'a',
        'a'    => 'A',
        'n'    => 'en',
        'H'    => 'Hello',
        'W'    => 'world',
        'eych' => 'H',
        'dubya'=> 'W',
        'sa'   => 'SA',
        'me'   => 'ME'
    ];

    /**
    * @return     int<0,max>
    */
    private static function find_start_of_bracketed_expansion(string $working_string) : int
    {
        //$have_invalid_char = false;
        while(true)
        {
            $pos = \strrpos($working_string, '{', -1);
            assert($pos !== false);
            assert($pos > 0);
            // if ($pos === false || $pos === 0) {
            //     // No opening '{' character.
            //     // Or, '{' is not preceded by '$', and with no hope to find a '${' before that.
            //     // This case would probably be an internal error.
            //     // So we don't run code for it, we just assert.
            //     //   (This should never happen: $depth is only >0 when there was a valid '${' sequence earlier in the string.)
            //     //   (This is important because this would imply no depth change,
            //     //   yet the current version of this function assumes that
            //     //   depth has ALREADY changed, so if this calculation happens, it could lead to desync.)
            //     return \PHP_INT_MAX;
            // }

            $pos--;

            // Skip past any invalid lone '{' characters.
            // We don't handle it here, because that will shake out
            // during the subsequent forward-scan.
            $ch = $working_string[$pos];
            if ( $ch !== '$' ) {
                // '{' character is not preceded by '$' character.
                // This suggests a '{' appears within an expansion, like this:
                // '${foo{bar}', which is illegal syntax.
                // We handle this by substituting with the empty string.
                // But first, we must keep searching for the proper '${',
                // otherwise we don't know which region to substitute.
                // (And if we don't find it, then we just treat this all
                // as non-syntax and skip+ignore it, per the earlier check.)
                //$have_invalid_char = true;
                continue;
            }

            // @phpstan-ignore  function.alreadyNarrowedType
            assert($ch === '$');
            return $pos;
        }
    }

    private const ESC_HEX     = 0xFE;
    private const ESC_UNICODE = 0xFF;

    /**
    * @param      ?array<non-empty-string>  $table
    * @param-out  array<non-empty-string>   $table
    */
    private static function populate_backslash_escape_lookup_table(?array &$table) : void
    {
        $table = \array_fill(0,256,'');
        for($i = 0; $i < 256; $i++) {
            $table[$i] = \chr($i);
        }
        $table[\ord('n')] = "\n";
        $table[\ord('r')] = "\r";
        $table[\ord('t')] = "\t";
        $table[\ord('v')] = "\v";
        $table[\ord('e')] = "\e";
        $table[\ord('f')] = "\f";
        $table[\ord('0')] = "\0";
        $table[\ord('1')] = "\1";
        $table[\ord('2')] = "\2";
        $table[\ord('3')] = "\3";
        $table[\ord('4')] = "\4";
        $table[\ord('5')] = "\5";
        $table[\ord('6')] = "\6";
        $table[\ord('7')] = "\7";
        $table[\ord('x')] = \chr(self::ESC_HEX);
        $table[\ord('u')] = \chr(self::ESC_UNICODE);
    }

    /**
    * @return  array<non-empty-string>  $table
    */
    public static function backslash_escape_lookup_table() : array
    {
        static $table = null;
        if (isset($table)) {
            return $table;
        }
        self::populate_backslash_escape_lookup_table($table);
        return $table;
    }

    /**
    * @param      string      $text
    * @param      int<0,max>  $pos
    * @param-out  int<0,max>  $pos
    * @param      int<0,max>  $nchars
    * @param-out  int<0,max>  $nchars
    */
    private static function traverse_identifier(
        string $text,  int &$pos,  int &$nchars
    ) : int
    {
        // We don't do strict identifier checking.
        // After all, things like $0 are valid, so ${0} should also be valid.
        //$ch0 = $text[$pos];
        //if ( 0 < $nchars && !\ctype_alpha($ch0) && $ch0 !== '_' ) {
        //    return 0;
        //}
        $identifier_len = \strspn($text, self::IDENTIFIER_CHARS, $pos, $nchars);
        $pos    += $identifier_len;
        $nchars -= $identifier_len;
        return $identifier_len;
    }

    // Right now these are the exact same, but for future-proofing,
    // different entry-points are called based on the syntactical context.
    /**
    * @param      string      $text
    * @param      int<0,max>  $pos
    * @param-out  int<0,max>  $pos
    * @param      int<0,max>  $nchars
    * @param-out  int<0,max>  $nchars
    */
    private static function traverse_dollar_style_identifier(string $text,  int &$pos,  int &$nchars) : int {
        return self::traverse_identifier($text, $pos, $nchars);
    }

    /**
    * @param      string      $text
    * @param      int<0,max>  $pos
    * @param-out  int<0,max>  $pos
    * @param      int<0,max>  $nchars
    * @param-out  int<0,max>  $nchars
    */
    private static function traverse_percent_style_identifier(string $text,  int &$pos,  int &$nchars) : int {
        return self::traverse_identifier($text, $pos, $nchars);
    }

    // Verifies curly brace contents and content boundaries to include only the identifier.
    //
    // This function should be preceded by finding the '${' and '}' tokens.
    //
    // Before calling, the caller should then ensure that
    // $tmp_inner_lo and $tmp_inner_hi have been set to
    // after the '${' and before the '}', respectively.
    //
    // This function returns whether or not the contents are valid
    // (e.g. if they "matched" the whitespace-identifier-whitespace pattern).
    /**
    * @param      string      $text
    * @param      int<0,max>  $tmp_inner_lo
    * @param-out  int<0,max>  $tmp_inner_lo
    * @param      int<0,max>  $tmp_inner_hi
    * @param-out  int<0,max>  $tmp_inner_hi
    */
    private static function narrow_and_validate_shallow_curly_brace_expansion_contents(
        string $text,
        int    &$tmp_inner_lo,
        int    &$tmp_inner_hi,
    ) : bool
    {
        // Traverse any initial whitespace.
        $nchars_unscanned = $tmp_inner_hi - $tmp_inner_lo;
        $scan_len = \strspn($text, " \r\n\t", $tmp_inner_lo, $nchars_unscanned);
        assert(0 <= $scan_len);
        assert($scan_len <= $nchars_unscanned);
        $tmp_inner_lo     += $scan_len;
        $nchars_unscanned -= $scan_len;
        if ($nchars_unscanned === 0) {
            // We expected to see an identifier, and we did not.
            // So we'll substitute with empty string.
            return false;
        }

        // Verify presence of identifier.
        $tmp_inner_hi = $tmp_inner_lo;
        $scan_len = self::
            traverse_dollar_style_identifier($text, $tmp_inner_hi, $nchars_unscanned);
        assert(0 <= $scan_len);
        if ($scan_len === 0) {
            // There are non-identifier (non-whitespace) characters.
            // Substitute with empty string.
            return false;
        }

        // Verify that the rest is just whitespace.
        $pos = $tmp_inner_hi;
        $nchars_unscanned -= \strspn($text, " \r\n\t", $pos, $nchars_unscanned);
        assert(0 <= $nchars_unscanned);
        if ( $nchars_unscanned !== 0 ) {
            // Expansion contents invalid in some way, ex:
            // `${ foo 4}` or `${ foo bar }` or `${foo%}`
            // So we'll substitute with empty string.
            return false;
        }

        // Valid contents/identifier found.
        return true;
    }

    private static function debug_int_expr(int $foo) : bool {
        return true;
    }

    /**
    * Function that looks for any presence of expansion or escape sequences.
    *
    * It's like `tokenize_single_interpolation_pattern` but much simpler
    * because it doesn't actually find the outline of patterns or manage
    * substitution/event logic.
    *
    * The idea is that `tokenize_curly_brace_expansion` will call this
    * to determine if a curly-brace-expansion is "deep" or "shallow".
    *
    * This returns `true` if there is either a nested expansion
    * or a nested escape sequence (for whitespace). In this case,
    * the caller should not use the `$pos` or `$nchars` output;
    * the intention is that the caller resets to the end of the
    * earlier '${', emits a depth increase event, and returns to
    * the substitution-performing function (ex: `interpolate_first`).
    * When assertions are enabled, `$pos` and `$nchars` will be
    * assigned canary values in this case.
    *
    * This returns `false` if we reach either EOS or '}'. The caller
    * can distinguish these cases by using `pos` and `nchars` outputs.
    *
    * @param      string         $text
    * @param      int<0,max>     $pos
    * @param-out  int<0,max>     $pos
    * @param      int<0,max>     $nchars
    * @param-out  int<0,max>     $nchars
    */
    private static function find_nested_subexpansions(
        string $text,
        int    &$pos,
        int    &$nchars
    ) : bool
    {
        while(true)
        {
            $scan_len = \strcspn($text, '$%\\}', $pos, $nchars);
            assert($scan_len <= $nchars);
            $pos    += $scan_len;
            $nchars -= $scan_len;
            assert(0 <= $nchars);
            if ( $nchars === 0 ) {
                // Invalid bracketed expansion: missing '}'
                // Ignore it as non-syntax.
                // Our scan hitting EOS also suggests
                // that there are no other escape sequences.
                return false;
            }

            $ch0 = $text[$pos];

            // Check for nested sub-expansions.
            if ( $ch0 === '$' || $ch0 === '%' ) {
                assert(self::debug_int_expr($pos    = \PHP_INT_MAX));
                assert(self::debug_int_expr($nchars = \PHP_INT_MAX));
                return true;
            }

            // Check for escape sequences.
            // These are more complicated because:
            // * Valid escape sequences are a subexpansion requiring depth increase.
            // * Invalid escape sequences are not; just skip them.
            // escape sequences
            if ( $ch0 === '\\' ) {
                // These are a special-case within '${...}' expansions.
                // They may only be '\n', '\r', or '\t'.
                // Anything else would yield an invalid character,
                // and the '${...}' expansion would substitute with ''.
                $pos++;
                $nchars--;
                if ( $nchars === 0 ) {
                    // Invalid bracketed expansion: missing '}'
                    // Ignore it as non-syntax.
                    // Like the earlier case, this is effectively
                    // "hitting EOS", because the lone '\' doesn't
                    // change anything: it is treated like the other
                    // characters in the string.
                    return false;
                }

                // Check for valid whitespace sequences.
                // Note that we can't opportunistically tokenize them,
                // because our caller will need to emit a depth increase event.
                $ch1 = $text[$pos];
                if ( $ch1 === 'n' || $ch1 === 'r' || $ch1 === 't' ) {
                    assert(self::debug_int_expr($pos    = \PHP_INT_MAX));
                    assert(self::debug_int_expr($nchars = \PHP_INT_MAX));
                    return true;
                }

                // Not valid. Skip and keep looking for the '}'.
                // Regardless of whether this sequence is valid in other
                // contexts or not, the caller's logic should intrinsically
                // find these characters invalid because they aren't
                // identifier characters, and that error handling will
                // occur at that point (not here; too complicated).
                $pos++;
                $nchars--;
                continue; // Keep scanning with `strcspn`
            }

            assert($ch0 === '}');
            return false;
        }
    }

    /**
    * @param      string         $text
    * @param      int<0,max>     $pos
    * @param      int<0,max>     $nchars
    * @param      int<0,max>     $tmp_outer_lo
    * @param-out  int<0,max>     $tmp_outer_lo
    * @param      int<0,max>     $tmp_inner_lo
    * @param-out  int<0,max>     $tmp_inner_lo
    * @param      int<0,max>     $tmp_inner_hi
    * @param-out  int<0,max>     $tmp_inner_hi
    * @param      int<0,max>     $tmp_outer_hi
    * @param-out  int<0,max>     $tmp_outer_hi
    * @param      int<0,max>     $depth
    * @param-out  int<0,max>     $depth
    */
    private static function tokenize_curly_brace_expansion(
        string $text,
        int    $pos,
        int    $nchars,
        int    &$tmp_outer_lo,
        int    &$tmp_inner_lo,
        int    &$tmp_inner_hi,
        int    &$tmp_outer_hi,
        int    &$depth
    ) : bool
    {
        assert(self::debug_msg(' --- function entered ---'));
        assert($tmp_outer_lo === $pos-2);
        assert($tmp_inner_lo === $pos);

        // Scan for nested subexpansions or escape sequences.
        // If it isn't found, we'll hit '}' instead.
        $have_match = self::find_nested_subexpansions($text, $pos, $nchars);

        assert(self::debug_msg(''));
        if ( $have_match ) {
            // Reset the scanner to just after the '${'.
            // Emit this as a zero-length slice because this is
            // a "depth increase" event and there is no substitution
            // to perform during such an event signal.
            // This allows the caller to know where the depth increase
            // happens. They can call the scanner again to find out
            // where the nested subexpansion is.
            $tmp_outer_lo = $tmp_inner_lo;
            $tmp_outer_hi = $tmp_inner_lo;

            // Record the depth increase.
            $depth++;
            return true;
        }

        assert(self::debug_msg('')); // @phpstan-ignore function.alreadyNarrowedType
        // Case where we hit EOS without encountering '}' (or other syntax)
        if ( $nchars === 0 ) {
            $tmp_outer_lo = $pos; // <- Might not be necessary, but good for paranoia.
            $tmp_outer_hi = $pos; // <- Quite necessary. Tells the caller where EOS is.
            return false;
        }

        assert($text[$pos] === '}');

        // If we've made it this far, then we know there
        // are no nested substitutions to be performed.
        // All that's left is to check for a valid identifier.
        $tmp_inner_hi = $pos;   // '}'
        $tmp_outer_hi = $pos+1; // rest of string (or EOS is valid too)

        // This function will trim the inner slice
        //   {$tmp_inner_lo, $tmp_inner_hi}
        // and also detect invalid characters.
        $have_match =
            self::narrow_and_validate_shallow_curly_brace_expansion_contents(
                $text, $tmp_inner_lo, $tmp_inner_hi);

        assert(self::debug_msg('')); // @phpstan-ignore function.alreadyNarrowedType
        if ( !$have_match ) {
            // We return an outer slice that is the width of the '${...}'
            // sequence, but with a zero-length inner slice. This tells
            // the caller to substitute the '${...}' with the empty string,
            // because the contents of the expansion did not yield a valid
            // identifier from the environment/dictionary.
            // We return `true` because we DID encounter syntax (we lexed
            // an entire '${...}' sequence) and the scanner's caller DOES
            // need to perform a substitution.
            $tmp_inner_lo = $tmp_outer_hi;
            $tmp_inner_hi = $tmp_outer_hi;
            return true;
        }

        assert(self::debug_msg('')); // @phpstan-ignore function.alreadyNarrowedType
        // Done. The caller now has the correct indices for expansion:
        // inner slice -> An identifier to look up in the environment/dictionary.
        // outer slice -> Segment of text to replace with the lookup result.
        return true;
    }


    /**
    * @param      int<1,max>     $pos
    * @param      int<1,max>     $nchars
    * @param      int<0,255>     $ch0
    * @param      int<0,255>     $ch1
    * @param      int<0,max>     $tmp_outer_lo
    * @param-out  int<0,max>     $tmp_outer_lo
    * @param      int<0,max>     $tmp_inner_lo
    * @param-out  int<0,max>     $tmp_inner_lo
    * @param      int<0,max>     $tmp_inner_hi
    * @param-out  int<0,max>     $tmp_inner_hi
    * @param      int<0,max>     $tmp_outer_hi
    * @param-out  int<0,max>     $tmp_outer_hi
    */
    private static function tokenize_nested_escape_sequence(
        string $text,
        int    $pos,
        int    $nchars,
        int    $ch0,
        int    $ch1,
        int    &$tmp_outer_lo,
        int    &$tmp_inner_lo,
        int    &$tmp_inner_hi,
        int    &$tmp_outer_hi
    ) : bool
    {
        assert($tmp_outer_lo === $pos-1);

        // Only whitespace-yielding escape sequences like
        // `\n`, `\r`, `\r`, and `\v` are allowed within outer expansions,
        // as any other escape sequences would result in invalid strings
        // being generated within the outer expansion.
        //
        // One exception would be the equivalent octal/hex code
        // escape sequences for the above, as well as octal/hex codes
        // for all valid identifier characters (and the space character).
        // Given that those would also yield characters that are valid
        // within an expansion, they could potentially be allowed, but
        // this is currently unimplemented.
        $tmp_inner_lo = $pos;
        $pos++;

        if ($ch0 !== self::CH_BACKSLASH) {
            // Things like '$$' and '%%' won't work here because
            // they yield '$' and '%', which are not whitespace or
            // valid identifier characters.
            return false;
        }


        if ($ch1 === self::CH_LOWER_N
        ||  $ch1 === self::CH_LOWER_R
        ||  $ch1 === self::CH_LOWER_T
        ||  $ch1 === self::CH_LOWER_V)
        {
            $tmp_inner_hi = $pos;
            $tmp_outer_hi = $pos;
            return true;
        }

        $tmp_outer_lo = $pos;
        $tmp_outer_hi = $pos;
        return false;
    }

    /**
    * @param      int<1,max>     $pos
    * @param      int<1,max>     $nchars
    * @param      int<0,255>     $ch0
    * @param      int<0,255>     $ch1
    * @param      int<0,max>     $tmp_outer_lo
    * @param-out  int<0,max>     $tmp_outer_lo
    * @param      int<0,max>     $tmp_inner_lo
    * @param-out  int<0,max>     $tmp_inner_lo
    * @param      int<0,max>     $tmp_inner_hi
    * @param-out  int<0,max>     $tmp_inner_hi
    * @param      int<0,max>     $tmp_outer_hi
    * @param-out  int<0,max>     $tmp_outer_hi
    */
    private static function tokenize_backslash_escape_sequence(
        string $text,
        int    $pos,
        int    $nchars,
        int    $ch0,
        int    $ch1,
        int    &$tmp_outer_lo,
        int    &$tmp_inner_lo,
        int    &$tmp_inner_hi,
        int    &$tmp_outer_hi
    ) : bool
    {
        assert($tmp_outer_lo === $pos-1);

        if ($ch0 !== self::CH_BACKSLASH) {
            // Not a backslash sequence.
            return false;
        }

        if ($ch1 === self::CH_DOLLAR
        ||  $ch1 === self::CH_PERCENT
        ||  $ch1 === self::CH_BACKSLASH)
        {
            // Sequences like '\\', '\$', and '\%' are not handled here.
            return false;
        }

        assert(self::debug_msg(" pos=$pos"));

        // Check for '\'-escape-sequences that should not be substituted.
        $slash_lookup = self::backslash_escape_lookup_table();
        $code_to = \ord($slash_lookup[$ch1]);
        if ( $ch1 === $code_to ) {
            // Non-substituting sequences are things like "\Q",
            // because there is no meaning for such escape sequences.
            // PHP does not remove the leading slash in these cases,
            // so the resulting string is the same as "\\Q".
            // In our context, this means that we can just skip
            // the whole thing. We don't need to stop and attempt
            // substitution, instead we avoid unnecessary string
            // copy/concatenation by just moving ahead.
            $tmp_outer_hi = $pos+1;
            return false;
        }

        // This condition is just a negation of the conditions
        // further on for detecting octal and hex/unicode sequences.
        if ( 8 <= $code_to && $code_to < 0x80 ) {
            // Matched, but not as "tightly" as subsequent branches.
            // These are escape sequences like `\n`, `\r`, `\t`, etc.
            // These are valid, but we're going to
            // fall through to the same code that handles
            // things like '$$' and '%%', since it's all
            // just the same case of "substitute with the 2nd character".
            return false; // No match + no $pos/$tmp_outer_hi movement = fallthrough
        }

        //if ( $code_to < 8 || $code_to > 0x80 ) ...
        // Numeric sequences, ex: \0, \x00, \u{...}
        // $code_to < 8    is to detect octal sequences
        // $code_to > 0x80 is to detect \xNN and \u{NNNN} sequences.
        // These need to be special-cased because their
        // width can be (or is) wider than other escape sequences.
        assert(self::debug_msg(''));
        $match_found = false;
        if ( $code_to < 8 )
        {
            // Octal case
            $match_found = self::tokenize_octal(
                $text, $code_to, $pos, $nchars, $tmp_inner_lo, $tmp_inner_hi, $tmp_outer_hi);
            assert(self::debug_msg('')); // @phpstan-ignore function.alreadyNarrowedType
        } else { // Hex and Unicode Hex cases
            // Skip the 'x' or 'u'
            $pos++;
            $nchars--;
            if ( $nchars === 0 ) { // EOS
                $tmp_outer_hi = $pos;
                return false;
            }

            // Register advancement to the caller.
            // This is (possibly) important if the below checks fail,
            // and we want to know what character to restart scanning at.
            $tmp_outer_hi = $pos;

            // Check for basic validity and precise bounds
            if ( $code_to === self::ESC_HEX ) {
                $match_found = self::tokenize_hex(
                    $text, $pos, $nchars, $tmp_inner_lo, $tmp_inner_hi, $tmp_outer_hi);
            } else { // $code_to === self::ESC_UNICODE; Unicode tokenizing.
                $match_found = self::tokenize_ucodehex(
                    $text, $pos, $nchars, $tmp_inner_lo, $tmp_inner_hi, $tmp_outer_hi);
            }
        }

        assert(self::debug_msg('')); // @phpstan-ignore function.alreadyNarrowedType
        return $match_found;

        // // !$match_found
        // if ( 0 < $nchars ) {
        //     // Invalid syntax -> no match + continue scanning.
        //     continue;
        // } else {
        //     // Invalid syntax + EOS -> no match + end scanning
        //     return false;
        // }
    }

    /**
    * @param      int<1,max>     $pos
    * @param      int<1,max>     $nchars
    * @param      int<0,255>     $ch0
    * @param      int<0,255>     $ch1
    * @param      int<0,max>     $tmp_outer_lo
    * @param-out  int<0,max>     $tmp_outer_lo
    * @param      int<0,max>     $tmp_inner_lo
    * @param-out  int<0,max>     $tmp_inner_lo
    * @param      int<0,max>     $tmp_inner_hi
    * @param-out  int<0,max>     $tmp_inner_hi
    * @param      int<0,max>     $tmp_outer_hi
    * @param-out  int<0,max>     $tmp_outer_hi
    */
    private static function tokenize_escape_sequence(
        string $text,
        int    $pos,
        int    $nchars,
        int    $ch0,
        int    $ch1,
        int    &$tmp_outer_lo,
        int    &$tmp_inner_lo,
        int    &$tmp_inner_hi,
        int    &$tmp_outer_hi
    ) : bool
    {
        assert($tmp_outer_lo === $pos-1, "text='$text', tmp_outer_lo=$tmp_outer_lo, pos=$pos");

        if(self::tokenize_backslash_escape_sequence(
            $text, $pos, $nchars, $ch0, $ch1,
            $tmp_outer_lo, $tmp_inner_lo, $tmp_inner_hi, $tmp_outer_hi))
        {
            // Match found.
            return true;
        }

        // `tokenize_backslash_escape_sequence` can two different `false` outcomes:
        // * It is _confident_ that the sequence is invalid.
        // * It can't handle the sequence, but it might be valid still.
        //
        // In the former case, it will move $tmp_outer_hi. We catch that here.
        // In this case, we just abort right away. Just have the caller keep scanning.
        //
        // In the latter case, it will not move $tmp_outer_hi, in which
        // case we keep going and see if we can handle it.
        if ($pos < $tmp_outer_hi) {
            // We are no longer positioned where we used to be. ($tmp_outer_hi was moved)
            // Restart scanning from the new position.
            return false;
        }

        assert(self::debug_msg(''));
        $tmp_inner_lo = $pos;
        $pos++;

        if (($ch0 === self::CH_BACKSLASH)
        ||  ($ch0 === self::CH_PERCENT  && $ch1 === self::CH_PERCENT)
        ||  ($ch0 === self::CH_DOLLAR   && $ch1 === self::CH_DOLLAR))
        {
            // The caller will need to disambiguate this
            // from the "we pointed at an identifier" possibility.
            // But it's quite doable.
            //$tmp_outer_lo = $pos-1; (already set by caller)
            $tmp_inner_hi = $pos;
            $tmp_outer_hi = $pos;
            return true;
        }

        // Otherwise, it might just be the start of an expansion sequence.
        return false;
    }

    /**
    * @param      string         $text
    * @param      int<0,7>       $code_to
    * @param      int<0,max>     $pos
    * @param      int<0,max>     $nchars
    * @param      int<0,max>     $tmp_inner_lo
    * @param-out  int<0,max>     $tmp_inner_lo
    * @param      int<0,max>     $tmp_inner_hi
    * @param-out  int<0,max>     $tmp_inner_hi
    * @param      int<0,max>     $tmp_outer_hi
    * @param-out  int<0,max>     $tmp_outer_hi
    */
    private static function tokenize_octal(
        string $text,
        int    $code_to,
        int    $pos,
        int    $nchars,
        int    &$tmp_inner_lo,
        int    &$tmp_inner_hi,
        int    &$tmp_outer_hi
    ) : bool
    {
        // Octal tokenizing.
        $n_digits = \strspn($text, '01234567', $pos, $nchars);

        // The max octal is \377
        // If we have more than that many digits, then
        // the remaining digits aren't part of the sequence.
        if ( 3 < $n_digits ) {
            $n_digits = 3;
        }

        // Starting with \400, we are above the max.
        // However, \40 < \377, so we will assume
        // that the string is "\40" followed by "0".
        if ( 4 <= $code_to && 2 < $n_digits ) {
            $n_digits = 2;
        }

        $tmp_inner_lo = $pos;
        $tmp_inner_hi = $pos + $n_digits;
        $tmp_outer_hi = $tmp_inner_hi;
        return true;
    }

    /**
    * @param      string         $text
    * @param      int<0,max>     $pos
    * @param      int<0,max>     $nchars
    * @param      int<0,max>     $tmp_inner_lo
    * @param-out  int<0,max>     $tmp_inner_lo
    * @param      int<0,max>     $tmp_inner_hi
    * @param-out  int<0,max>     $tmp_inner_hi
    * @param      int<0,max>     $tmp_outer_hi
    * @param-out  int<0,max>     $tmp_outer_hi
    */
    private static function tokenize_hex(
        string $text,
        int    $pos,
        int    $nchars,
        int    &$tmp_inner_lo,
        int    &$tmp_inner_hi,
        int    &$tmp_outer_hi
    ) : bool
    {
        $n_digits = \strspn($text, '0123456789ABCDEF', $pos, $nchars);
        if ( $n_digits === 0 ) {
            // Not a valid hex escape.
            // There is nothing to substitute.
            // Keep scanning for substitutions.
            return false;
        }

        // Max hex is \xFF.
        // Any hex digits beyond that are not part of the sequence.
        if ( 2 < $n_digits ) {
            $n_digits = 2;
        }

        $tmp_inner_lo = $pos;
        $tmp_inner_hi = $pos + $n_digits;
        $tmp_outer_hi = $tmp_inner_hi;
        return true;
    }

    /**
    * @param      string         $text
    * @param      int<0,max>     $pos
    * @param      int<0,max>     $nchars
    * @param      int<0,max>     $tmp_inner_lo
    * @param-out  int<0,max>     $tmp_inner_lo
    * @param      int<0,max>     $tmp_inner_hi
    * @param-out  int<0,max>     $tmp_inner_hi
    * @param      int<0,max>     $tmp_outer_hi
    * @param-out  int<0,max>     $tmp_outer_hi
    */
    private static function tokenize_ucodehex(
        string $text,
        int    $pos,
        int    $nchars,
        int    &$tmp_inner_lo,
        int    &$tmp_inner_hi,
        int    &$tmp_outer_hi
    ) : bool
    {
        // Unicode tokenizing.
        // This could be VERY fiddly,
        // so we'll do a simple implementation
        // and accept a very narrow grammar.
        // (Notably, putting spaces inside the `{}` part
        // causes PHP to error/crash! That is... surprising.
        // This is effectively `nothrow` code, so we
        // will just non-substitute (ignore) such sequences.)

        if ( $nchars <= 1 ) {
            // Not enough room to finish the unicode seq,
            // and ALSO not enough room to find another expansion.
            // So we exit, confident that there are no substitutions to make.
            // Note: ($nchars===0) is how the caller distinguishes between
            // "no match + we hit EOS" and "there just wasn't a match",
            // so update $pos and $nchars to reflect the "we hit EOS" state.
            $tmp_outer_lo = $pos+$nchars;
            $tmp_outer_hi = $tmp_outer_lo;
            return false;
        }

        $ch0 = $text[$pos];
        assert(self::debug_msg("pos=$pos"));
        assert(self::debug_msg("ch0='$ch0'"));
        if ( $ch0 !== '{' ) {
            // Invalid unicode escape sequence: {NNNN} part of \u{NNNN} notation is required.
            // Substitute nothing. Continue scanning.
            return false;
        }

        // Skip the '{'
        $pos++;
        $nchars--;

        $tmp_inner_lo = $pos;
        $have_match = self::traverse_ucodehex_inner($text, $pos, $nchars);
        if (!$have_match) {
            $tmp_inner_lo = $pos;
            $tmp_outer_lo = $pos;
            $tmp_outer_hi = $pos;
            return false;
        }

        $tmp_inner_hi = $pos;

        assert(0 < $nchars);
        $ch1 = $text[$pos];
        $tmp_outer_hi = $pos+1;
        if ( $ch1 !== '}' ) {
            // Invalid unicode escape sequence: expected '}', got something else.
            // Substitute nothing. Continue scanning.
            $tmp_outer_lo = $tmp_outer_hi; // Empty string.
            return false;
        }

        return true;
    }

    /**
    * @param      string         $text
    * @param      int<0,max>     $pos
    * @param-out  int<0,max>     $pos
    * @param      int<0,max>     $nchars
    * @param-out  int<0,max>     $nchars
    */
    private static function traverse_ucodehex_inner(
        string $text,
        int    &$pos,
        int    &$nchars
    ) : bool
    {
        assert(self::debug_msg(''));
        $n_digits = \strspn($text, '0123456789ABCDEF', $pos, $nchars);
        if ( $n_digits === 0 ) {
            // Invalid unicode escape sequence: no hex digits present.
            // Substitute nothing. Continue scanning.
            return false;
        }

        assert(self::debug_msg('')); // @phpstan-ignore function.alreadyNarrowedType
        // Highest code point is U+10FFFF.
        // So we can't have more than 6 digits.
        if ( 6 < $n_digits ) {
            // Invalid unicode escape sequence: codepoint out of range.
            // Substitute nothing. Continue scanning.
            return false;
        }

        assert(self::debug_msg('')); // @phpstan-ignore function.alreadyNarrowedType
        $pos    += $n_digits;
        $nchars -= $n_digits;
        if ( $nchars === 0 ) {
            // Invalid unicode escape sequence: '}'
            //   (and possibly more) cut off by end-of-string.
            // That means we're done, and didn't find anything.
            return false;
        }

        return true;
    }

    /**
    * @param      string         $text
    * @param      int<0,max>     $pos
    * @param      int<0,max>     $nchars
    * @param      int<0,255>     $ch0
    * @param      int<0,255>     $ch1
    * @param      int<0,max>     $tmp_outer_lo
    * @param-out  int<0,max>     $tmp_outer_lo
    * @param      int<0,max>     $tmp_inner_lo
    * @param-out  int<0,max>     $tmp_inner_lo
    * @param      int<0,max>     $tmp_inner_hi
    * @param-out  int<0,max>     $tmp_inner_hi
    * @param      int<0,max>     $tmp_outer_hi
    * @param-out  int<0,max>     $tmp_outer_hi
    */
    private static function tokenize_simple_dollar_expansion_contents(
        string  $text,
        int     $pos,
        int     $nchars,
        int     $ch0,
        int     $ch1,
        int     &$tmp_outer_lo,
        int     &$tmp_inner_lo,
        int     &$tmp_inner_hi,
        int     &$tmp_outer_hi
    ) : bool
    {
        // We don't do strict identifier checking.
        // After all, things like $0 are valid, so ${0} should also be valid.
        //$ch0 = $text[$pos];
        //if ( !\ctype_alpha($ch0) && $ch0 !== '_' ) {
        //    // Not a valid start character for an identifier.
        //    // Invalid syntax -> skip it.
        //    $tmp_outer_hi = $pos;
        //    return false;
        //}
        $identifier_len = self::traverse_dollar_style_identifier($text, $pos, $nchars);
        if ( $identifier_len === 0 ) {
            // Invalid sequence: no identifier following the '$'.
            // Keep scanning.
            $tmp_outer_hi = $pos;
            return false;
        }

        $tmp_inner_hi = $pos;
        $tmp_outer_hi = $pos;

        // Identifier GET!
        return true;
    }

    /**
    * @param      string         $text
    * @param      int<0,max>     $pos
    * @param      int<0,max>     $nchars
    * @param      int<0,255>     $ch0
    * @param      int<0,255>     $ch1
    * @param      int<0,max>     $tmp_outer_lo
    * @param-out  int<0,max>     $tmp_outer_lo
    * @param      int<0,max>     $tmp_inner_lo
    * @param-out  int<0,max>     $tmp_inner_lo
    * @param      int<0,max>     $tmp_inner_hi
    * @param-out  int<0,max>     $tmp_inner_hi
    * @param      int<0,max>     $tmp_outer_hi
    * @param-out  int<0,max>     $tmp_outer_hi
    */
    private static function tokenize_simple_percent_expansion_contents(
        string  $text,
        int     $pos,
        int     $nchars,
        int     $ch0,
        int     $ch1,
        int     &$tmp_outer_lo,
        int     &$tmp_inner_lo,
        int     &$tmp_inner_hi,
        int     &$tmp_outer_hi
    ) : bool
    {
        // DOS has this syntax for command line arguments:
        // %~dpnxN or %~fsN
        // Where
        //   '~' = some syntax that heralds the rest
        //   'd' = drive letter
        //   'p' = parent folder/directory (with trailing backslash)
        //   'n' = basename
        //   'x' = extension (probably with the dot, I think?)
        //   'f' = "full path to the folder of the first command line argument"
        //   's' = the DOS style-8.3 "short name" with an 8 char basename and 3 char extension
        //   'N' = 0-9 some parameter number; 0 is the path for the script itself.
        //
        // Reference: https://steve-jansen.github.io/guides/windows-batch-scripting/part-2-variables.html
        // So, uh, let's parse it. Why not.
        $is_tilde_expression = false;
        if ($ch1 === self::CH_TILDE) {
            $is_tilde_expression = true;

            // Advance past the tilde,
            // but don't remove it from the inner match.
            // It is helpful syntax for a dictionary to use.
            $pos++;
            $nchars--;

            // The rest of it is just alphanumeric.
            // And the scanner really isn't going to get
            // nit-picky about which characters a valid or not.
            // So we just parse as normal, at least until
            // we are looking for the closing '%' character:
            // we know we don't need it now.
        }

        // We don't do strict identifier checking.
        // After all, things like $0 are valid, so ${0} should also be valid.
        //$ch0 = $text[$pos];
        //if ( !\ctype_alpha($ch0) && $ch0 !== '_' ) {
        //    // Not a valid start character for an identifier.
        //    // Invalid syntax -> skip it.
        //    $tmp_outer_hi = $pos;
        //    return false;
        //}
        $identifier_len = self::traverse_percent_style_identifier($text, $pos, $nchars);
        if ( $identifier_len === 0 ) {
            // Invalid sequence: no identifier following the '$'.
            // Keep scanning.
            $tmp_outer_hi = $pos;
            return false;
        }

        // TODO: If we get '%~dpnx0Hello', does that parse as '%[~dpnx0]Hello' or '%[~dpnx0Hello]'?
        // (Right now it will do the latter. Both seem scuffed!)
        $dont_need_closing_percent = $is_tilde_expression
            || \ctype_digit(\substr($text, $tmp_inner_lo, $identifier_len));
        $need_closing_percent = !$dont_need_closing_percent;

        if ($nchars === 0 && $need_closing_percent) {
            // Could not find closing '%' AND we reached end-of-stream.
            $tmp_outer_hi = $pos;
            return false;
        }

        $have_percent = ($text[$pos] === '%');
        if ($need_closing_percent && !$have_percent) {
            // Could not find closing '%', got something else instead.
            // Invalid syntax -> Ignore it and keep scanning.
            $tmp_outer_hi = $pos;
            return false;
        }

        // Consume the '%' during substitution.
        $tmp_inner_hi = $pos;
        $tmp_outer_hi = $tmp_inner_hi;
        if ( $have_percent ) {
            $tmp_outer_hi++;
        }

        // Identifier GET!
        return true;
    }

    /**
    * @param      string         $text
    * @param      int<0,max>     $pos
    * @param      int<0,max>     $nchars
    * @param      int<0,255>     $ch0
    * @param      int<0,255>     $ch1
    * @param      int<0,max>     $tmp_outer_lo
    * @param-out  int<0,max>     $tmp_outer_lo
    * @param      int<0,max>     $tmp_inner_lo
    * @param-out  int<0,max>     $tmp_inner_lo
    * @param      int<0,max>     $tmp_inner_hi
    * @param-out  int<0,max>     $tmp_inner_hi
    * @param      int<0,max>     $tmp_outer_hi
    * @param-out  int<0,max>     $tmp_outer_hi
    */
    private static function tokenize_simple_expansion(
        string  $text,
        int     $pos,
        int     $nchars,
        int     $ch0,
        int     $ch1,
        int     &$tmp_outer_lo,
        int     &$tmp_inner_lo,
        int     &$tmp_inner_hi,
        int     &$tmp_outer_hi
    ) : bool
    {
        if ($ch0 === self::CH_DOLLAR) {
            return self::tokenize_simple_dollar_expansion_contents(
                $text, $pos, $nchars, $ch0, $ch1,
                $tmp_outer_lo, $tmp_inner_lo, $tmp_inner_hi, $tmp_outer_hi);
        }

        assert(self::debug_msg(''));
        if ($ch0 === self::CH_PERCENT) {
            return self::tokenize_simple_percent_expansion_contents(
                $text, $pos, $nchars, $ch0, $ch1,
                $tmp_outer_lo, $tmp_inner_lo, $tmp_inner_hi, $tmp_outer_hi);
        }

        $tmp_outer_hi = $pos;
        return false;
    }

    /**
    * This function attempts to find the "outline" of an expansion or escape
    * sequence at this position in the string `$text`.
    *
    * The caller shall set $tmp_outer_lo and $tmp_outer_hi equal to $pos.
    * (This is mostly to avoid redundant assignments within this function.
    * $tmp_outer_lo should always _start_ at $pos, because the caller
    * encountered a syntax character so that is where the syntax starts.
    * (We can advance it within this function, but it is an unlikely case.)
    * $tmp_outer_hi should be set to $pos because it allows us to determine
    * if our sub-functions have recognized a pattern or not.)
    *
    * If we return true, the calling scanner will do one of two things:
    * * If `$tmp_outer_lo  <  $tmp_outer_hi`, the caller shall request substitution.
    * * If `$tmp_outer_lo === $tmp_outer_hi`, the caller shall advance to `$tmp_outer_hi`
    *     and emit an event. (This case is used to convey depth increase/decrease.)
    *
    * If we return false, the calling scanner will advance by
    * `$tmp_outer_hi - $pos` characters, using the value of `$pos` _before_
    * the call to this function. This is used to skip invalid syntax that
    * should remain in the input string (ex: invalid escape sequences
    * like '\\K').
    *
    * @param      string         $text
    * @param      int<0,max>     $pos
    * @param      int<0,max>     $nchars
    * @param      int<0,max>     $tmp_outer_lo
    * @param-out  int<0,max>     $tmp_outer_lo
    * @param      int<0,max>     $tmp_inner_lo
    * @param-out  int<0,max>     $tmp_inner_lo
    * @param      int<0,max>     $tmp_inner_hi
    * @param-out  int<0,max>     $tmp_inner_hi
    * @param      int<0,max>     $tmp_outer_hi
    * @param-out  int<0,max>     $tmp_outer_hi
    * @param      int<0,max>     $depth
    * @param-out  int<0,max>     $depth
    *
    * @return  bool  `true` if we matched _something_ (including depth events). `false` if the scanner should just advance to $tmp_outer_hi.
    */
    private static function try_interpolation_pattern_at(
        string  $text,
        int     $pos,
        int     $nchars,
        int     &$tmp_outer_lo,
        int     &$tmp_inner_lo,
        int     &$tmp_inner_hi,
        int     &$tmp_outer_hi,
        int     &$depth
    ) : bool
    {
        // The presence of syntax implies at least one remaining character.
        assert(0 < $nchars);
        assert($tmp_outer_lo === $pos);
        assert($tmp_outer_hi === $pos);
        // The inner slice values are not read,
        // and this function shall make no assumption
        // about the incoming values in those arguments.

        $ch0 = \ord($text[$pos]);
        $pos++;
        $nchars--;

        if ($ch0 === self::CH_CURLY_CLOSE)
        {
            // We encountered a closing '}' bracket.
            // This is a special-case meaning such:
            //   We completed a substitution WITHIN a ${...} expansion.
            //
            // Assumption:
            //   All preceding substitutions within the expansion
            //   have already been performed. This is what
            //   `interpolate`+`interpolate_first` will do,
            //   and it saves us from having to do bracket-matching/counting.
            //
            // Notably, the caller is responsible for calling `find_start_of_bracketed_expansion`
            // (Because the caller will want to call it on their _working_ string,
            // not this function's `$text` argument.)
            // Then the caller should perform the outer substitution on their working string.
            //
            // We will signal to the caller that they must call `find_start_of_bracketed_expansion`
            // by returning a zero-width slice with a different `depth`
            // value than before. This is the conventional way to emit
            // a "depth decrease" event.
            $depth--;

            // We position to just after the '}' and ensure a zero-width match,
            // because this will allow the caller to use the same codepath
            // to handle building the rest of the `${...}` as they use
            // for appending sections of string in-between symbols/expansions.
            $tmp_outer_lo = $pos;
            //$tmp_inner_lo = $pos; // Unnecessary; caller should not do a substitution (yet).
            //$tmp_inner_hi = $pos; // ditto
            $tmp_outer_hi = $pos;
            return true;
        }

        assert(self::debug_msg(''));
        // Exit if we're so close to the end-of-string that
        // we can't possibly do any substitution/expansion/interpolation.
        if ($nchars === 0) {
            $tmp_outer_lo = $pos;
            $tmp_outer_hi = $pos;
            return false;
        }

        // Acquire/peek the 2nd character.
        $ch1 = \ord($text[$pos]);

        // Tentative guess.
        $tmp_inner_lo = $pos;

        assert(self::debug_msg("pos=$pos"));
        // ===== Match escape-sequences. ======
        if ($ch0 === self::CH_DOLLAR
        ||  $ch0 === self::CH_PERCENT
        ||  $ch0 === self::CH_BACKSLASH)
        {
            if($depth === 0) {
                if(self::tokenize_escape_sequence(
                    $text, $pos, $nchars, $ch0, $ch1,
                    $tmp_outer_lo, $tmp_inner_lo, $tmp_inner_hi, $tmp_outer_hi))
                {
                    // Match found
                    return true;
                }
            } else {
                if(self::tokenize_nested_escape_sequence(
                    $text, $pos, $nchars, $ch0, $ch1,
                    $tmp_outer_lo, $tmp_inner_lo, $tmp_inner_hi, $tmp_outer_hi))
                {
                    return true;
                }
            }

            if ($pos < $tmp_outer_hi) {
                // No match, but we advanced -> Start scan over again from this point.
                return false;
            }
        }

        assert(self::debug_msg('')); // @phpstan-ignore function.alreadyNarrowedType
        // Check for an immediate identifier.
        // This is a very common case, it's things like:
        // * `FOO='world'` -> `Hello $FOO!`  -> `Hello world!`
        // * `FOO='world'` -> `Hello %FOO%!` -> `Hello world!`
        $skip_len = \strspn($text, self::IDENTIFIER_CHARS, $pos, $nchars);
        if ($skip_len !== 0) {
            $pos    += $skip_len;
            $nchars -= $skip_len;
            $tmp_inner_hi = $pos;
            $tmp_outer_hi = $tmp_inner_hi;
            if ($ch0 === self::CH_PERCENT) {
                if ( $nchars === 0 ) {
                    // Invalid DOS-style expansion, missing '%'.
                    // Treat it as non-syntax.
                    // We also know that there are no '$' expansions
                    // in the remaining text because it's all identifier chars.
                    return false;
                }
                $ch1 = \ord($text[$pos]);
                if ( $ch1 !== self::CH_PERCENT ) {
                    // Invalid DOS-style expansion:
                    // other characters before '%',
                    // or also a missing '%'
                    // Treat it as non-syntax.
                    // There are non-identifier characters in the remaining
                    // text, so we'll reset the scanner and that point
                    // and have it look for other things (ex: $-expansions).
                    return false;
                }
                $tmp_outer_hi++; // The outer '%'.
            }
            assert($tmp_inner_lo - $tmp_outer_lo === 1);
            assert($tmp_inner_lo < $tmp_inner_hi);
            assert(($tmp_inner_hi === $tmp_outer_hi) || ($tmp_outer_hi - $tmp_inner_hi === 1));
            return true;
        }

        assert(self::debug_msg('')); // @phpstan-ignore function.alreadyNarrowedType
        if (!($ch0 === self::CH_DOLLAR && $ch1 === self::CH_CURLY_OPEN)) {
            if(self::tokenize_simple_expansion(
                    $text, $pos, $nchars, $ch0, $ch1,
                    $tmp_outer_lo, $tmp_inner_lo, $tmp_inner_hi, $tmp_outer_hi))
            {
                // Match found
                return true;
            }

            if ($pos < $tmp_outer_hi) {
                // No match, but we advanced -> Start scan over again from this point.
                return false;
            }
        }

        assert(self::debug_msg('')); // @phpstan-ignore function.alreadyNarrowedType

        // Handle bracketed expansion.
        if ($ch0 === self::CH_DOLLAR && $ch1 === self::CH_CURLY_OPEN)
        {
            // Notable constraints:
            // * The try-er MUST advance before returning.
            //     This is a convenient and unambiguous (and probably efficient)
            //     way to tell the scanner "hey I skipped some stuff"
            //     so that it either returns a match or keeps scanning
            //     from the new position.
            //     This also means that, when we encounter nested expansions,
            //     we can't send our "depth increase" event before the '${'
            //     token, we have to send it _after_ that token instead.
            // * The caller, for example `interpolate_first`, would do
            //     infinite looping if it kept ending up at the start of
            //     a curly-brace expansion _even when it's shallow_. The
            //     shallow expansion is a kind of "base case" that allows
            //     inner-expanded formerly-nested expansions to actually
            //     end up expanded/substituted themselves. Either that,
            //     or the caller would need a bunch of extra logic to
            //     figure out that "hey, we've been here already, let's
            //     _make an assumption_", and that, IMO, really is not
            //     a great way to do this!
            //
            // So we ensure that every attempt to expand a curly-brace
            // pattern will not require caller intervention if the
            // pattern is a shallow expansion.

            // Advance past the '{'
            $pos++;
            $nchars--;

            // $tmp_outer_lo = $pos-2; // Already set.
            $tmp_inner_lo = $pos;
            $tmp_inner_hi = $pos;
            $tmp_outer_hi = $pos;
            $res = self::tokenize_curly_brace_expansion(
                $text, $pos, $nchars,
                $tmp_outer_lo, $tmp_inner_lo, $tmp_inner_hi, $tmp_outer_hi,
                $depth);
            return $res;
        }

        assert(self::debug_msg('')); // @phpstan-ignore  function.alreadyNarrowedType
        // Invalid sequence.
        // Skip it. (Scan past $ch1)
        // Keep scanning for valid sequences.
        $pos++;
        //$nchars--; // Unnecessary because we are exiting.
        $tmp_outer_hi = $pos;
        return false;
    }

    /**
    * Scans part of `$text` for "expansions" and finds lexical boundaries.
    *
    * This function is essentially a lexer for interpolate-able strings.
    * It is a very low-level function, and in most cases `interpolate`
    * or `interpolate_first` will be preferred and much easier to use.
    *
    * The outputs are as follows:
    * * Return value: whether interpolation syntax was encountered or not.
    * * `$outer_lo` and `$outer_hi`:
    *     The starting and ending positions, respectively, of text to be substituted.
    * * `$inner_lo` and `$inner_hi`:
    *     The starting and ending positions, respectively, of the substring
    *     that determines what the expansion is replaced with.
    * * `$depth`: Is for nested expansions. It is also an input, and it
    *     tracks when substitutions are being performed _within_ an outer expansion.
    *
    * The output values `$outer_lo`, `$inner_lo`, `$inner_hi`, and `$outer_hi`
    * are ordered and will always satisfy this mathematical relationship:
    * `outer_lo ≤ inner_lo ≤ inner_hi ≤ outer_hi`.
    *
    * Note that a return value of `true` does not necessarily mean that there
    * is a substitution to perform. It can also signal an "event" to the caller,
    * such as when depth increases or decreases. In such cases, `$outer_lo === $outer_hi`.
    *
    * In the case that `true` _does_ indicate a substitution to be performed,
    * then it still isn't necessarily as simple as looking up
    * `\substr($text,$inner_lo,$inner_hi-$inner_lo)` in an environment table
    * or dictionary to find the substitution text, because it might be
    * an escape sequence instead.
    *
    * To explain these aspects in more detail, we note that certain combinations
    * of the outputs of this function have distinct and important meanings.
    *
    * This can be quite non-trivial, hence why it is recommended to call
    * `interpolate` instead of `tokenize_single_interpolation_pattern` in most
    * cases (or `interpolate_first` if iterative substitution is truly required).
    *
    * When `true` is returned, it can mean one of these things:
    * * If `$depth` is unchanged and `$text[$outer_lo] === '\\'`:
    *     * If `$text[$outer_lo+1]` is 'u' or 'x' or '0-7' (inclusive), then:
    *         * `$inner_lo` will be set to the start of an octal or hex digit substring.
    *         * `$inner_hi` will be set to the end of that octal or hex digit substring.
    *         * The caller must use the value of `$text[$outer_lo+1]` to determine
    *             if the substring should be parsed as octal or as hexadecimal.
    *         * The integer value derived from parsing the octal or hexadecimal
    *             substring (ex: using `\hexdec` or `\octdec`) should then
    *             be encoded as a character (PHP string with one character)
    *             using `\chr`, or `\mb_chr` for unicode codepoints
    *             (`$text[$outer_lo+1] === 'u'`).
    *         * That character is the substitute for the matching portion of `$text`
    *     * Otherwise:
    *         * Acquire the backslash lookup table:
    *             `$lookup = backslash_escape_lookup_table();`
    *         * Determine the substitution string using a lookup:
    *             `$ch = $lookup[\ord($text[$inner_lo])];`
    *     * There is no need to check for invalid escape sequences.
    *         Any invalid backslash-escape-sequences will
    *         have already been skipped by the scanner's logic.
    * * If `$depth` is unchanged and `$ch = \substr($text,$outer_lo,$inner_lo-$outer_lo)` equals '$' or '%':
    *     * If `\substr($text,$inner_lo,$outer_hi-$inner_lo)` equals `$ch`:
    *         * The string to substitute is just `$ch`.
    *     * Otherwise:
    *         * `$name = \substr($text,$inner_lo,$inner_hi-$inner_lo)` is the
    *             name of an environment variable or dictionary entry that
    *             the caller is expected to use to look up the intended
    *             replacement text for `\substr($text,$outer_lo,$outer_hi-$outer_lo)`.
    * * If `$depth` increased: The '${' token was encountered.
    *     The returned `$outer_lo` and `$outer_hi` will be equal (zero-length match),
    *     and they will be set to the position just past the '${' token.
    *     The caller is should then do this:
    *     * Append `\substr($text, $start_pos, $outer_hi - $start_pos)`
    *         to the caller's working string (here called `$working_str`),
    *     * Do not perform any substitution, but instead optionally
    *         return or otherwise indicate to the caller (e.g. the caller's caller)
    *         that the iteration is complete and there is more text to be scanned.
    *     * The next iteration proceeds as ordinary. (If depth continues
    *         increasing, then we just keep appending. The logic neatly
    *         folds the descent into the ordinary shallow-case logic.)
    *     Alternatively, the caller may do this:
    *     * Remember the original start position. We'll call it `$original_start`.
    *     * Immediately (before returning) call `tokenize_single_interpolation_pattern`
    *         on `$text` passing `$outer_hi` into the `$start_pos` parameter.
    *     * Proceed with the ordinary "`$depth` unchanged" procedure,
    *         BUT, use `$original_start` instead of `$start_pos` when appending
    *         then next non-substituted portion of `$text` onto `$working_str`.
    *     * To handle multiple consecutive descents, this version of the
    *         procedure must be able to call `tokenize_single_interpolation_pattern`
    *         repeated as long as `$depth` is increasing, and always remember
    *         the very first `$original_start`, and use that `$original_start`
    *         once it finally hits either unchanged depth, or decreased depth.
    *     The latter approach can be more efficient because it only does one
    *     string append/concatenation instead of two, but it is slightly
    *     more complicated and makes it impossible to forward depth-increase
    *     events to the caller's caller.
    * * If `$depth` decreased: The '}' token was encountered.
    *     The returned `$outer_lo` and `$outer_hi` will be equal (zero-length match),
    *     and they will be set to the position just past the '}' token.
    *     The caller must perform these steps:
    *     * Append `\substr($text, $start_pos, $outer_lo - $start_pos)`
    *         to the caller's working string (here called `$working_str`).
    *     * Call `find_start_of_bracketed_expansion($working_str)`
    *         to find the corresponding '${' token in the working string.
    *         We'll call this position `$replace_at`.
    *     * Call `tokenize_single_interpolation_pattern` on `$working_str` with
    *         `slice_offset` and `start_pos` set to the value returned
    *         by `find_start_of_bracketed_expansion`, and `slice_len` set
    *         such that `$slice_offset + $slice_len === \strlen($working_string)`.
    *     * Do the usual lookup in the caller's environment variable list
    *         or dictionary to get the replacement string for this segment.
    *         We'll call the replacement string `$substitute`.
    *     * Truncate `$working_str`: `$working_str = \substr($working_str, 0, $replace_at);`
    *     * Append `$substitute`: `$working_str .= `$substitute`.
    *     As an optimization: rather than the truncate-and-append approach,
    *     it might even be arguably better to reuse the portion of `$working_str`
    *     past `$replace_at` by copying the `$substitute` and any subsequent
    *     text into that portion, and only append once that portion is exhausted.
    *     This _might_ make it easier for the PHP interpreter to recognize
    *     that it doesn't need to make a new copy of `$working_str` at every
    *     outer-expansion substitution, but it would also be fairly complicated
    *     to implement, especially the case where `$substitute` is _shorter_
    *     than the region it is replacing (and the caller must then keep
    *     track of how much "buffer" it has in subsequent calls, and make
    *     its own caller aware that part of `$working_str` is now a buffer).
    *
    * This function does not perform any explicit memory allocation.
    * (The `interpolate` and `interpolate_first` functions, which are
    * implemented using this function, DO perform explicit memory allocation,
    * so this is the one notable difference in guarantees provided by
    * these functions. That said, this function only does _part_ of the
    * string interpolation process, and the other part of the process
    * is what tends to necessitate allocations, so constructing an
    * interpolated string will likely require memory allocation regardless.)
    *
    * @see  self::interpolate
    * @see  self::interpolate_first
    *
    * @param      int<0,max>     $slice_offset
    * @param      int<0,max>     $slice_len
    * @param      int<0,max>     $start_pos
    * @param      ?int           $outer_lo
    * @param-out  ?int<0,max>    $outer_lo
    * @param      ?int           $inner_lo
    * @param-out  ?int<1,max>    $inner_lo
    * @param      ?int           $inner_hi
    * @param-out  ?int<2,max>    $inner_hi
    * @param      ?int           $outer_hi
    * @param-out  ?int<2,max>    $outer_hi
    * @param      int<0,max>     $depth
    *
    * @throws void
    *
    * @phpstan-assert-if-true   =int<0,max>  $outer_lo
    * @phpstan-assert-if-true   =int<1,max>  $inner_lo
    * @phpstan-assert-if-true   =int<2,max>  $inner_hi
    * @phpstan-assert-if-true   =int<2,max>  $outer_hi
    */
    public static function tokenize_single_interpolation_pattern(
        string  $text,
        int     $slice_offset,  int $slice_len,
        int     $start_pos,
        ?int    &$outer_lo = null,
        ?int    &$inner_lo = null,
        ?int    &$inner_hi = null,
        ?int    &$outer_hi = null,
        int     &$depth = 0
    ) : bool
    {
        // TODO: Set depth to 0 when returning false due to EOS?
        //   (We currently don't need that, so maybe just document that the value of $depth is undefined this function returns `false`.)
        assert(self::debug_msg(" --- function entered ---"));
        // Nothing to do on empty strings.
        if ( $slice_len === 0 ) {
            return false;
        }
        $end = $slice_offset + $slice_len;

        assert($slice_offset <= $start_pos);
        assert($start_pos <= $end);
        assert($end <= \strlen($text));

        $pos = $start_pos;
        $nchars = $end - $start_pos;
        assert($slice_offset <= $pos);
        assert($pos <= $end);
        assert($nchars <= $slice_len);
        assert(0 <= $nchars);

        // Define these so that they are available for `try_interpolation_pattern_at`
        // and also available as default values for matching outcomes.
        $tmp_outer_lo = $pos;
        $tmp_inner_lo = $pos;
        $tmp_inner_hi = $pos;
        $tmp_outer_hi = $pos;

        // The first scan may need to repeat for various reasions:
        // * We encountered errant '}' characters repeatedly.
        // * We are recursing into '${' expansions repeatedly.
        // * Invalid escape sequences (ex: '$&').
        // (And it's better to do this with a while-loop
        // than to do it with function recursion.)
        while (true)
        {
            assert(self::debug_msg(''));
            // Scan to first $, %, \, or }
            if ( 0 === $depth ) {
                $skip_len = \strcspn($text, '$%\\', $pos, $nchars);
            } else {
                $skip_len = \strcspn($text, '$%\\}', $pos, $nchars);
            }
            assert(0 <= $skip_len);
            assert($skip_len <= $nchars);

            if ( $skip_len === $nchars ) {
                return false; // No syntax = do nothing
            }

            assert(self::debug_msg('')); // @phpstan-ignore  function.alreadyNarrowedType
            // Advance.
            $pos    += $skip_len;
            $nchars -= $skip_len;
            assert(0 <= $pos);
            assert(0 <= $nchars);

            // If we match something, it will always (or at least almost-always)
            // start at this position, though the other indices will vary.
            $tmp_outer_lo = $pos;

            // The other really crucial value is this one, because it's
            // used inside `try_interpolation_pattern_at` to determine
            // if a sub-pattern consumed any syntax or not.
            $tmp_outer_hi = $pos;

            // We've hit a character that's in our grammar,
            // so now it's worthwhile to do a function call
            // and check all of the possibilities.
            $success =
                self::try_interpolation_pattern_at(
                    $text, $pos, $nchars,
                    $tmp_outer_lo, $tmp_inner_lo, $tmp_inner_hi, $tmp_outer_hi,
                    $depth);

            $skip_len = $tmp_outer_hi - $pos;
            $pos    += $skip_len;
            $nchars -= $skip_len;

            // The `try` function must always advance.
            // Otherwise we could infinite-loop.
            // (And we entered it because we hit _something_,
            // so it should at least skip the initial character.)
            assert($skip_len > 0);

            if ($success) {
                // Match -> Send outputs and exit.
                break; // return true after setting outputs.
            }

            if ( $nchars !== 0 ) {
                // No match and we have text left -> Keep parsing
                continue;
            }

            // No match and we reached EOS -> Exit
            // In debug mode, we assign $outer_* and $inner_* to canary
            // values to ensure that the caller isn't making assumptions
            // about the contents of these things. A false return
            // means that there is no substitution and no signal/event,
            // so there is no need for the caller to inspect these values.
            // In production mode, these assignments should disappear due
            // to the elision of `assert` statements, thus producing
            // optimal code that does not perform unnecessary assignments.
            assert(\PHP_INT_MAX === ($outer_lo = \PHP_INT_MAX));
            assert(\PHP_INT_MAX === ($inner_lo = \PHP_INT_MAX));
            assert(\PHP_INT_MAX === ($inner_hi = \PHP_INT_MAX));
            assert(\PHP_INT_MAX === ($outer_hi = \PHP_INT_MAX));
            return false;
        }

        assert(self::debug_msg("outer_lo = $tmp_outer_lo"));
        assert(self::debug_msg("inner_lo = $tmp_inner_lo"));
        assert(self::debug_msg("inner_hi = $tmp_inner_hi"));
        assert(self::debug_msg("outer_hi = $tmp_outer_hi"));
        $outer_lo = $tmp_outer_lo;
        $inner_lo = $tmp_inner_lo;
        $inner_hi = $tmp_inner_hi;
        $outer_hi = $tmp_outer_hi;
        return true;
    }

    private static function interpolator_val_to_string(mixed  $val) : string
    {
        if (!isset($val)) {
            return 'null';
        }

        if ( is_string($val) ) {
            return $val;
        }

        if ( is_int($val) || is_float($val) ) {
            return \strval($val);
        }

        if ( is_bool($val) ) {
            return $val ? 'true' : 'false';
        }

        if ( is_array($val) ) {
            $len = \count($val);
            if ( 0 === $len ) {
                return '[]';
            }
            if ( \array_is_list($val) ) {
                $res = '[' . $val[0];
                for($i = 1; $i < $len; $i++) {
                    $res .= ', '.self::interpolator_val_to_string($val[$i]);
                }
                $res .= ']';
                return $res;
            }
            $res = '[';
            foreach($val as $key => $elem) {
                $key_str = is_string($key) ? "'$key'" : '['.\strval($key).']';
                $elem_str = self::interpolator_val_to_string($elem);
                $elem_str = is_string($elem) ? "'$elem_str'" : $elem_str;
                if ( $res !== '[' ) {
                    $res .= ', ';
                }
                $res .= "$key_str => $elem_str";
            }
            $res .= ']';
            return $res;
        }

        if ( $val instanceof \DateTime ) {
            return $val->format('Y-m-d H:i:s');
        }

        if ( \is_object($val)
        && (\method_exists($val, '__toString') || \method_exists($val, '__call')) )
        {
            try {
                // PHPStan isn't picking up on the \method_exists calls,
                //   or it doesn't know that __call might implement __toString.
                // @phpstan-ignore method.notFound
                $valstr = $val->__toString();
                if ( is_string($valstr) ) {
                    return $valstr;
                }
                return '';
            } catch (\Throwable $e) {
                return '';
            }
        }

        // Failure mode: It's some type we don't understand.
        return '';
    }


    /**
    * @see  self::interpolate
    *
    * @param      array<array-key,mixed>  $env
    * @param      string                  $src_text
    * @param      int<0,max>              $src_pos
    * @param-out  int<0,max>              $src_pos
    * @param      ?string                 $dst_text
    * @param-out  string                  $dst_text
    * @param      int<0,max>              $copy_len
    * @param      int<0,max>              $outer_lo
    * @param      int<0,max>              $inner_lo
    * @param      int<0,max>              $inner_hi
    * @param      int<0,max>              $outer_hi
    *
    * @throws void
    */
    private static function do_substitution(
        array    $env,
        string   $src_text,
        int      &$src_pos,
        ?string  &$dst_text,
        int      $copy_len,
        int      $outer_lo,
        int      $inner_lo,
        int      $inner_hi,
        int      $outer_hi
    ) : void
    {
        // Handle the text preceding the substitution.
        // Note the assymetry:
        // If the caller gives us `null` $dst_text, then we copy
        // everything from $src_text up to `$outer_lo`,
        // INCLUDING everything that precedes `$slice_offset`.
        // Meanwhile, if we have a non-null $dst_text, then
        // we are simply appending lengths of size `$copy_len`,
        // because the copy of `\substr($src_text, $slice_offset, $copy_len[0])`
        // already happened by this point (or the caller wants us
        // to append this stuff to a different string).
        if ( !isset($dst_text) ) {
            if ( 0 < $outer_lo ) {
                // This is equivalent to:
                // $dst_text =  \substr($src_text, 0, $slice_offset);
                // $dst_text .= \substr($src_text, $slice_offset, $copy_len);
                $dst_text = \substr($src_text, 0, $outer_lo);
            }
        } else
        if ( 0 < $copy_len ) {
            $dst_text .= \substr($src_text, $src_pos, $copy_len);
        }

        // // (This is how we would advance to the start of the match.)
        // // (But right now it's not needed.)
        // $src_pos = $outer_lo;

        // The zero-length slice is a signal to tell us
        // that we should substitute the whole thing
        // with the empty string. This is most likely
        // to happen with `${...}` expansions that are
        // invalid due to their contents.
        if ($inner_lo === $inner_hi) {
            // Insert blank string = Append nothing to $dst_text.
            $src_pos = $outer_hi;
            if (!isset($dst_text)) $dst_text = '';
            return;
        }

        // '\'-initiated escape sequences.
        assert(self::debug_msg(''));
        $ch0 = $src_text[$outer_lo];
        if ( $ch0 === '\\' ) {
            assert(self::debug_msg('')); // @phpstan-ignore function.alreadyNarrowedType
            $ch1 = $src_text[$outer_lo+1];
            $src_pos = $outer_hi;
            if ( $ch1 === 'x' || $ch1 === 'u' ) {
                $n_digits = $inner_hi - $inner_lo;
                assert(self::debug_msg('')); // @phpstan-ignore function.alreadyNarrowedType
                $char_code = \hexdec(\substr($src_text, $inner_lo, $n_digits));
                // \hexdec returns `float` if string it out of range for `int`.
                // In this case, it won't be.
                assert(\is_int($char_code));
                if ( $ch1 === 'x' ) {
                    self::str_append($dst_text, \chr($char_code));
                } else {
                    $uchar = \mb_chr($char_code);
                    assert(0 < \strlen($uchar)); // Not sure why PHPStan thinks this ever returns 0-length strings.
                    self::str_append($dst_text, $uchar);
                }
                return;
            }
            if ( '0' <= $ch1 && $ch1 <= '7' ) {
                $n_digits = $inner_hi - $inner_lo;
                assert(self::debug_msg('')); // @phpstan-ignore function.alreadyNarrowedType
                $char_code = \octdec(\substr($src_text, $inner_lo, $n_digits));
                // \octdec returns `float` if string it out of range for `int`.
                // In this case, it won't be.
                assert(\is_int($char_code));
                self::str_append($dst_text, \chr($char_code));
                return;
            }
            assert(self::debug_msg('')); // @phpstan-ignore function.alreadyNarrowedType
            $slash_lookup = self::backslash_escape_lookup_table();
            self::str_append($dst_text, $slash_lookup[\ord($ch1)]);
            return;
        }

        assert(self::debug_msg('')); // @phpstan-ignore function.alreadyNarrowedType
        // Escape sequence by doubling, e.g.: '$$' and '%%'.
        if (($outer_lo + 1 === $inner_lo)
        &&  ($inner_lo + 1 === $inner_hi)
        &&  ($inner_hi === $outer_hi)) {
            $ch1 = $src_text[$inner_lo];
            if ( $ch0 === $ch1 ) {
                self::str_append($dst_text, $ch1);
                $src_pos = $outer_hi;
                return;
            }
            // Else:
            // Stuff like '$a', which is
            // actually a named substitution,
            // not an escape-sequence.
        }

        // Now we know we have a valid identifier/key to substitute.
        // (But we don't know if it's in the environment, yet.)
        $name = \substr($src_text, $inner_lo, $inner_hi-$inner_lo);
        assert($inner_lo < $inner_hi);

        assert(self::debug_msg("Looking up '$name' in environment."));
        if (\array_key_exists($name, $env)) {
            // All clear to perform a substitution!
            $val = self::interpolator_val_to_string($env[$name]);
            assert(self::debug_msg("Found: '$val'"));
            self::str_append_as_needed($dst_text, $val);
            $src_pos = $outer_hi;
            return;
        }
        assert(self::debug_msg("Not Found."));

        // Then $name isn't in the environment/dictionary.
        // So we'll substitute it with an empty string,
        // as per typical behavior for these things.
        // Insert blank string = Append nothing to $dst_text.
        $src_pos = $outer_hi;
        if (!isset($dst_text)) $dst_text = '';
        return;
    }

    /**
    * @param  non-empty-string  $suffix
    *
    * @throws void
    */
    private static function str_append(?string &$working_string, string $suffix) : void
    {
        if (isset($working_string)) {
            $working_string .= $suffix;
            return;
        }
        $working_string = $suffix;
    }

    /**
    * @param  string  $suffix
    *
    * @throws void
    */
    private static function str_append_as_needed(?string &$working_string, string $suffix) : void
    {
        if (isset($working_string)) {
            if ( \strlen($suffix) !== 0 ) {
                $working_string .= $suffix;
            }
            return;
        }
        $working_string = $suffix;
    }

    /**
    * Interpolates only the first expansion or escape sequence found in `$text`.
    *
    * This function is used as the basis for the `interpolate` function.
    * That function calls this one repeatedly until a string has been
    * completely interpolated, but this function allows only a single
    * expansion to be performed, if an iterative approach is desired.
    *
    * @see  self::interpolate
    *
    * @param      array<array-key,mixed>  $env
    * @param      string                  $src_text
    * @param      int<0,max>              $src_slice_offset
    * @param      int<0,max>              $src_slice_len
    * @param      int<0,max>              $src_cursor
    * @param-out  int<0,max>              $src_cursor
    * @param      ?string                 $output_text
    * @param-out  string                  $output_text
    * @param      int<0,max>              $depth
    * @param-out  int<0,max>              $depth
    *
    * @throws void
    *
    * @return bool  `false` if there was no valid interpolation syntax detected.
    */
    public static function interpolate_first(
        array    $env,
        string   $src_text,
        int      $src_slice_offset,  int  $src_slice_len,
        int      &$src_cursor = 0,
        ?string  &$output_text = null,
        int      &$depth = 0
    ) : bool
    {
        assert(self::debug_msg(' --- function entered ---'));
        assert(self::debug_int_expr($iter=0));
        $copy_len = 0;
        $src_start_pos = $src_cursor;
        while(true)
        {
            assert(self::debug_int_expr($iter++));
            assert(self::debug_msg(" (Iteration #$iter)"));
            assert(self::debug_msg(isset($output_text) ? "output_text='$output_text'" : 'output_text=null'));
            $prev_depth = $depth;
            $have_interpolation =
                self::tokenize_single_interpolation_pattern(
                    $src_text, $src_slice_offset, $src_slice_len, $src_cursor,
                    $outer_lo, $inner_lo, $inner_hi, $outer_hi, $depth);

            assert(self::debug_msg('have_interpolation='.($have_interpolation ? 'true' : 'false')));
            // No syntax === Leave early
            if (!$have_interpolation) {
                if (!isset($output_text)) {
                    // Avoid allocating a new string when there are no substitutions at all.
                    $output_text = $src_text;
                } else {
                    // Append the rest of $src_text onto whatever we had before.
                    $output_text .= \substr($src_text, $src_cursor);
                }
                $src_cursor = $src_slice_offset + $src_slice_len;
                return false;
            }

            assert(self::debug_msg(''));
            // Common case: simple/shallow expansion
            // OR: Depth decrease -> Exiting nested expansion
            // The initial steps for the latter are the same as the former.
            // But the latter case will require more complicated subsequent steps.
            if ( $prev_depth === $depth || $prev_depth > $depth )
            {
                // $copy_len =
                //   How much non-substituted text we need to copy from
                //   `$src_text` to `$output_text` before we reach
                //   something that should actually change.
                $copy_len += ($outer_lo-$src_cursor);

                // PHPStan requires this assertions to help
                // it track the integer range propagation.
                assert(0 <= $copy_len);

                // Figure out what we need to replace by looking
                // at `$src_text`, then append the replacement
                // to `$output_text`.
                // Note that we use `$src_start_pos` instead of `$src_cursor`;
                // this is because we might be copying multiple pre-substitution
                // slices at once (ex: when receiving depth decrease events),
                // and in such cases `$src_cursor` will point at the latest
                // slice identified, not the entire sequence of them.
                self::do_substitution(
                    $env, $src_text, $src_start_pos, $output_text, $copy_len,
                    $outer_lo, $inner_lo, $inner_hi, $outer_hi);

                // Update $src_cursor to right after the pattern that
                // was just matched in $src_text.
                $src_cursor = $outer_hi;

                // Shallow expansions get to exit right away, they are done.
                if ( $prev_depth === $depth ) {
                    // We are done expanding one expansion pattern.
                    return true;
                }
            }

            assert(self::debug_msg('')); // @phpstan-ignore function.alreadyNarrowedType
            // Depth increase -> Entering nested expansion
            if ( $prev_depth < $depth )
            {
                // Depth-change events are zero-width in terms
                // of how much text is _substituted_.
                assert($outer_lo === $outer_hi);

                // But it still has the "in-between" text
                // that needs to be directly copied from
                // `$src_text` to `$output_text`.
                // (Just like the shallow case, but with
                // the added complication that it can
                // happen multiple times per call to
                // `interpolate_first`.)
                $copy_len += ($outer_hi-$src_cursor);

                // Update $src_cursor to right after the pattern that
                // was just matched in $src_text.
                // Again, this is like the shallow depth case,
                // except that it happens in the same call
                // to `interpolate_first`.
                $src_cursor = $outer_hi;

                // Call `tokenize_single_interpolation_pattern` again.
                continue;
            }

            assert(self::debug_msg('')); // @phpstan-ignore function.alreadyNarrowedType
            // Depth decrease -> Exiting nested expansion (rescan and outer substitution is necessary)
            //if ( $prev_depth > $depth ) ...

            // Assert that we've copied everything up to and including the '}'
            assert($src_cursor === $outer_hi);

            // We wouldn't be here if it were null.
            assert(isset($output_text));

            // Setup for rescan.
            $working_string = $output_text;
            $working_strlen = \strlen($working_string);
            $rescan_pos = self::find_start_of_bracketed_expansion($working_string);
            $buffer_offset = $rescan_pos;
            $buffer_len    = $working_strlen - $rescan_pos;
            $output_text   = \substr($working_string, 0, $rescan_pos);

            // Basic check that `find_start_of_bracketed_expansion`
            // did what we think it should do.
            assert($working_string[$rescan_pos+0] === '$');
            assert($working_string[$rescan_pos+1] === '{');
            assert($working_string[$working_strlen-1] === '}');

            // PHPStan requires this assertions to help
            // it track the integer range propagation.
            assert(0 <= $buffer_len);

            // Rescan.
            $prev_depth = $depth;
            $have_interpolation =
                self::tokenize_single_interpolation_pattern(
                    $working_string, $buffer_offset, $buffer_len, $rescan_pos,
                    $outer_lo, $inner_lo, $inner_hi, $outer_hi, $depth);

            // It was there before, and it should not have gone anywhere!
            assert($have_interpolation);

            // Everything was already interpolated _inside_ the expansion,
            // so there should be no need to do any of that again.
            // (So no depth up/down enter/exit.)
            assert($prev_depth === $depth);

            // Like with the shallow case, except that
            // we are using a bunch of different inputs
            // because we are operating on
            //   `$working_string` and `$output_text`
            // instead of
            //   `$src_text` and `$output_text`
            self::do_substitution(
                $env, $working_string, $buffer_offset, $output_text, 0,
                $outer_lo, $inner_lo, $inner_hi, $outer_hi);

            assert(self::debug_msg('')); // @phpstan-ignore function.alreadyNarrowedType
            // The recursive call should have handled it.
            return true;
        }

    }

    /**
    * Performs string interpolation on `$text` using `$env` as the environment/dictionary.
    *
    * For example:
    * ```
    * $env = [
    *     'FOO' => 'Hello',
    *     'BAR' => 'world',
    *     'FO'  => 'Hell',
    *     'BA'  => 'wo',
    *     'SPC' => ' '
    * ];
    *
    * // Unix/shell/PHP style interpolation:
    * $interpolated_string =
    *     PHPStanConfig_StringInterpolation::
    *         interpolate(, '$FOO $BAR!');
    * assert($interpolated_string === 'Hello world!');
    *
    * // While the Unix/shell/PHP interpolation syntax is limited,
    * // curly-brace expansions are supported:
    * $interpolated_string =
    *     PHPStanConfig_StringInterpolation::
    *         interpolate(, '${FO}o ${BA}rld!');
    * assert($interpolated_string === 'Hello world!');
    *
    * // Limited DOS-style interpolation is also supported:
    * $interpolated_string =
    *     PHPStanConfig_StringInterpolation::
    *         interpolate(, '%FOO% %BAR%!');
    * assert($interpolated_string === 'Hello world!');
    *
    * $interpolated_string =
    *     PHPStanConfig_StringInterpolation::
    *         interpolate(, '%FOO%%SPC%%BAR%!');
    * assert($interpolated_string === 'Hello world!');
    * ```
    *
    * This function can be very helpful when writing system tools
    * and automation in PHP, such as for building, linting, testing,
    * version control management and other release engineering tasks.
    *
    * Security concerns:
    *
    * This has all of the usual caveats about "Do not use this to insert
    * user-provided parameters into executable code such as SQL queries".
    * Given an adversarial user (or a user who is adversarial by proxy,
    * ex: by being compromised), there is no way to ensure that
    * the resulting interpolated string won't contain malicious code.
    *
    * Also be aware that some sources of the `$env` argument may enable
    * disclosure vulnerabilities. If `$env` is acquired from a system
    * with broader context, such as from the `\getenv` function, it may
    * contain secrets or sensitive information about host system state.
    * Then, if the `interpolate` function is called with such an `$env`,
    * and the resulting string is somehow passed back to the user
    * (beware that error messages can sometimes betray this), then
    * the user will potentially be able to access that sensitive information.
    *
    * @param      array<array-key,mixed>  $env
    * @param      string                  $text
    * @param      int<0,max>              $slice_offset
    * @param      int<0,max>              $slice_len
    *
    * @throws void
    */
    public static function interpolate(
        array   $env,
        string  $text,
        int     $slice_offset = 0,
        int     $slice_len = \PHP_INT_MAX
    ) : string
    {
        assert($slice_offset <= \strlen($text));
        if ( $slice_len === \PHP_INT_MAX ) {
            $slice_len = \strlen($text) - $slice_offset;
            assert(0 <= $slice_len);
        }
        $src_cursor    = $slice_offset;
        $output_text = null;
        $depth = 0;
        while(self::interpolate_first($env, $text, $slice_offset, $slice_len, $src_cursor, $output_text, $depth)) {
            assert(self::debug_msg("output_text='$output_text'"));
        }
        assert(self::debug_msg("output_text='$output_text'"));

        // Weird, PHPStan needs this assertion, even though `interpolate_first`
        // has its `$output_text` parameter marked as outputting non-null.
        assert(isset($output_text));

        return $output_text;
    }

    private static function test_interpolate(string $prefix, string $suffix) : void
    {
        $env = self::ENV_FOR_TESTING;
        //assert($env['FOO' ] === 'x');
        //assert($env['BAR' ] === 'y');
        //assert($env['BAZ' ] === 'z');
        //assert($env['LONG'] === 'abcdefghijklmnop');
        //assert($env['SAME'] === 'same');
        //assert($env['A' ]   === 'a');
        //assert($env['H' ]   === 'Hello');
        //assert($env['W' ]   === 'world');

        $interpolate =
            function(string $text)
            use(&$env, &$prefix, &$suffix) : string
        {
            $slice_offset = \strlen($prefix);
            if ( 0 < \strlen($suffix) ) {
                $slice_length = \strlen($text);
            } else {
                $slice_length = \PHP_INT_MAX;
            }
            $wide_text = $prefix . $text . $suffix;
            return self::interpolate($env, $wide_text, $slice_offset, $slice_length);
        };

        $e = fn(string $s) : string => $prefix . $s . $suffix;

        // No substitution needed, and no syntax at all.
        assert($interpolate('') === $e(''));
        assert($interpolate('a') === $e('a'));
        assert($interpolate('Hello world!') === $e('Hello world!'));
        assert($interpolate("Hello\nworld!") === $e("Hello\nworld!"));

        // Simplest syntax.
        assert($interpolate('$SAME') === $e('same'));
        assert($interpolate('$FOO' ) === $e('x'));
        assert($interpolate('$LONG') === $e('abcdefghijklmnop'));
        assert($interpolate('$A'   ) === $e('a'));
        assert($interpolate('$n'   ) === $e('en')); // Ensure '$n' is not confused with '\n'
        assert($interpolate('$SAME ' ) === $e('same '));
        assert($interpolate(' $SAME' ) === $e(' same'));
        assert($interpolate(' $SAME ') === $e(' same '));
        assert($interpolate('Xx$SAME') === $e('Xxsame'));
        assert($interpolate('$SAME:123')   === $e('same:123'));
        assert($interpolate('Xx$SAME:123') === $e('Xxsame:123'));
        assert($interpolate('Hello $W!')   === $e('Hello world!'));

        // More complicated, but more expressive, syntax.
        assert($interpolate('${SAME}') === $e('same'));
        assert($interpolate('${FOO}' ) === $e('x'));
        assert($interpolate('${LONG}') === $e('abcdefghijklmnop'));
        assert($interpolate('${A}'   ) === $e('a'));
        assert($interpolate('${n}'   ) === $e('en'));
        assert($interpolate('${SAME} ' ) === $e('same '));
        assert($interpolate(' ${SAME}' ) === $e(' same'));
        assert($interpolate(' ${SAME} ') === $e(' same '));
        assert($interpolate('Xx${SAME}') === $e('Xxsame'));
        assert($interpolate('Xx{$SAME}') === $e('Xx{same}'));
        assert($interpolate('${SAME}:123')   === $e('same:123'));
        assert($interpolate('Xx${SAME}:123') === $e('Xxsame:123'));
        assert($interpolate('Hello ${W}!')   === $e('Hello world!'));

        // Ensure that whitespace is allowed inside ${...} expansions.
        assert($interpolate('${ SAME}')     === $e('same'));
        assert($interpolate('${SAME }')     === $e('same'));
        assert($interpolate("\${\nSAME\n}") === $e('same'));
        assert($interpolate("\${\nSAME}")   === $e('same'));
        assert($interpolate("\${SAME\n}")   === $e('same'));
        assert($interpolate("\${\rSAME\r}") === $e('same'));
        assert($interpolate("\${\rSAME}")   === $e('same'));
        assert($interpolate("\${SAME\r}")   === $e('same'));
        assert($interpolate("\${\tSAME\t}") === $e('same'));
        assert($interpolate("\${\tSAME}")   === $e('same'));
        assert($interpolate("\${SAME\t}")   === $e('same'));
        assert($interpolate('${  SAME  }')  === $e('same'));
        assert($interpolate('${  SAME}')    === $e('same'));
        assert($interpolate('${SAME  }')    === $e('same'));

        // Failure modes where the result is an empty string substitution.
        assert($interpolate('${SAME#}') === $e('')); // error in expansion; substitute with empty string.
        assert($interpolate('Xx${SAME#}:123') === $e('Xx:123')); // error in expansion; substitute with empty string.
        assert($interpolate('${ SAME }') === $e('same')); // spaces are OK, though.
        assert($interpolate('$EGGS') === $e('')); // dictionary/environment does not have that symbol

        // DOS style syntax (like what's found on Windows).
        assert($interpolate('%SAME%') === $e('same'));
        assert($interpolate('%FOO%' ) === $e('x'));
        assert($interpolate('%LONG%') === $e('abcdefghijklmnop'));
        assert($interpolate('%A%'   ) === $e('a'));
        assert($interpolate('%n%'   ) === $e('en'));
        assert($interpolate('%SAME% ' ) === $e('same '));
        assert($interpolate(' %SAME%' ) === $e(' same'));
        assert($interpolate(' %SAME% ') === $e(' same '));
        assert($interpolate('Xx%SAME%') === $e('Xxsame'));
        assert($interpolate('%SAME%:123')   === $e('same:123'));
        assert($interpolate('Xx%SAME%:123') === $e('Xxsame:123'));
        assert($interpolate('Hello %W%!')   === $e('Hello world!'));

        // Ordinary escape sequences
        assert($interpolate('\\n')    === $e("\n"));
        assert($interpolate('\\r')    === $e("\r"));
        assert($interpolate('\\t')    === $e("\t"));
        assert($interpolate('\\v')    === $e("\v"));
        assert($interpolate('\\e')    === $e("\e"));
        assert($interpolate('\\f')    === $e("\f"));
        assert($interpolate('\\\\')   === $e('\\'));
        assert($interpolate('\\$')    === $e('$'));
        assert($interpolate('\\%')    === $e('%'));
        //assert($interpolate('\\"')    === $e('"')); Currently doesn't pass. But _should_ it? (notes below)
        assert($interpolate('\\0')    === $e("\0"));
        assert($interpolate('\\1')    === $e("\1"));
        assert($interpolate('\\2')    === $e("\2"));
        assert($interpolate('\\3')    === $e("\3"));
        assert($interpolate('\\4')    === $e("\4"));
        assert($interpolate('\\5')    === $e("\5"));
        assert($interpolate('\\6')    === $e("\6"));
        assert($interpolate('\\7')    === $e("\7"));
        assert($interpolate('\\11')    === $e("\11"));
        assert($interpolate('\\101')   === $e("A"));
        assert($interpolate('\\377')   === $e("\xFF"));
        assert($interpolate('\\400')   === $e("\x200"));
        assert($interpolate('\\x41')   === $e("A"));
        assert($interpolate('\\xFF')   === $e("\xFF"));
        assert($interpolate('\\u{41}') === $e("A"));
        assert($interpolate('\\u{FFFF}') === $e("\u{FFFF}"));

        // Should assert($interpolate('\\"') === '"'); pass?
        // This escape sequence tends to assume that it's being used
        // within a double-quoted string. But that's not necessarily
        // true. The initial use-case for this code is to provide
        // textual substitution in config files; so there is no
        // enclosing character to escape! If this were used to interpolate
        // other kinds of strings, then it would need to be able to
        // change what character it's looking for. (Oh joy, API changes.)

        // Strange case(s), but PHP does it this way.
        assert($interpolate('\\x0')   === $e("\x0"));
        assert($interpolate('\\x9')   === $e("\x9"));
        assert($interpolate('\\xA')   === $e("\xA"));
        assert($interpolate('\\xF')   === $e("\xF"));

        // Per PHP behavior, escaping any other character should
        // just result in the backslash being output next to the character.
        // Source: https://www.php.net/manual/en/language.types.string.php
        assert($interpolate('\\Q') === $e('\\Q'));
        assert($interpolate('\\M') === $e('\\M'));
        assert($interpolate('\\&') === $e('\\&'));
        assert($interpolate('\\.') === $e('\\.'));
        assert($interpolate('\\_') === $e('\\_'));
        assert($interpolate('\\8') === $e('\\8'));
        assert($interpolate('\\9') === $e('\\9'));
        assert($interpolate("\\\0")  === $e("\\\0"));
        assert($interpolate("\\\n")  === $e("\\\n"));
        assert($interpolate("\\\r")  === $e("\\\r"));
        assert($interpolate("\\\t")  === $e("\\\t"));
        assert($interpolate("\\\v")  === $e("\\\v"));
        assert($interpolate("\\\e")  === $e("\\\e"));
        assert($interpolate("\\\f")  === $e("\\\f"));
        assert($interpolate('\\xG')  === $e('\\xG'));
        assert($interpolate('\\u')   === $e('\\u'));
        assert($interpolate('\\uF')  === $e('\\uF'));
        assert($interpolate('\\u{')  === $e('\\u{'));
        assert($interpolate('\\u{}') === $e('\\u{}'));
        assert($interpolate('\\u{F') === $e('\\u{F'));
        assert($interpolate('\\u{ F}')  === $e('\\u{ F}'));
        assert($interpolate('\\u{F }')  === $e('\\u{F }'));
        assert($interpolate('\\u{ F }') === $e('\\u{ F }'));

        // Failure modes where unrecognized syntax is left in the string.
        assert($interpolate('%SAME'   ) === $e('%SAME'));
        assert($interpolate('%SAME %' ) === $e('%SAME %'));
        assert($interpolate('${SAME'  ) === $e('${SAME'));
        assert($interpolate('$SAME%'  ) === $e('same%'));
        assert($interpolate('$SAME%%' ) === $e('same%'));
        assert($interpolate('$SAME%&' ) === $e('same%&'));
        assert($interpolate('$SAME%&%') === $e('same%&%'));

        // More than one substitution.
        assert($interpolate('$H $W!')     === $e('Hello world!'));
        assert($interpolate('${H} ${W}!') === $e('Hello world!'));
        assert($interpolate('%H% %W%!')   === $e('Hello world!'));

        assert($interpolate('$H'."\n".'$W!')     === $e("Hello\nworld!"));
        assert($interpolate('${H}'."\n".'${W}!') === $e("Hello\nworld!"));
        assert($interpolate('%H%'."\n".'%W%!')   === $e("Hello\nworld!"));

        assert($interpolate('$H$W!')     === $e('Helloworld!'));
        assert($interpolate('${H}${W}!') === $e('Helloworld!'));
        assert($interpolate('%H%%W%!')   === $e('Helloworld!'));

        assert($interpolate('Xx$H $W!')      === $e('XxHello world!'));
        assert($interpolate('Xx${H} ${W}xX') === $e('XxHello worldxX'));
        assert($interpolate('Xx%H% %W%xX')   === $e('XxHello worldxX'));

        // More failure modes.
        assert($interpolate('Xx%H%$%W%xX') === $e('XxHello$worldxX'));
        assert($interpolate('X$%H% %W%xX') === $e('X$Hello worldxX'));
        assert($interpolate('X$%H%$%W%xX') === $e('X$Hello$worldxX'));

        // Nesting substitution.
        assert($interpolate('${$eych}')  === $e('Hello'));
        assert($interpolate('${$dubya}') === $e('world'));
        assert($interpolate('${$eych} ${$dubya}!')   === $e('Hello world!'));
        assert($interpolate('Xx${$eych}${$dubya}xX') === $e('XxHelloworldxX'));
        assert($interpolate('${$sa$me}')   === $e('same'));
        assert($interpolate('${SA$me}')    === $e('same'));
        assert($interpolate('${${sa}ME}')  === $e('same'));
        assert($interpolate('${%sa%%me%}') === $e('same'));

        // Arbitrary depth test!
        // '${${$a}}' -> '${${A}}' -> '${a}' -> 'A'
        assert($interpolate('${${$a}}')     === $e('A'));
        assert($interpolate('${ ${ $a } }') === $e('A'));

        // Handling of incomplete (truncated) expansions
        assert($interpolate('${ ${ $a }') === $e('${ a'));
        assert($interpolate('${ $a } }')  === $e('a }'));

        // And another failure mode.
        assert($interpolate('${${#$a}}')  === $e(''));
        assert($interpolate('${#${$a}}')  === $e(''));
        assert($interpolate('${#${#$a}}') === $e(''));
    }

    private static function unittest_interpolate() : void
    {
        self::echo_msg("  ".__FUNCTION__."()\n");
        self::test_interpolate('','');
        self::test_interpolate('##','');
        self::test_interpolate('','##');
        self::test_interpolate('##','##');
        self::test_interpolate('$A','');
        self::test_interpolate('','$A');
        self::test_interpolate('$A','$A');
        self::test_interpolate('$A','##');
        self::test_interpolate('##','$A');
    }

    public static function unittests() : void
    {
        if ( !PHPStanConfig_System::$do_unittesting ) {
            return;
        }

        $class_fqn = self::class;
        self::echo_msg("Running `$class_fqn::unittests()`\n");
        self::unittest_interpolate();
        self::echo_msg("  ... passed.\n\n");
    }
}

PHPStanConfig_StringInterpolation::unittests();
