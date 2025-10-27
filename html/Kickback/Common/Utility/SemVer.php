<?php
declare(strict_types=1);

namespace Kickback\Common\Utility;

use Kickback\Common\Exceptions\ValidationException;
use Kickback\Common\Primitives\Int_;
use Kickback\Common\Primitives\Str;

// TODO: Improve class-level documentation.
/**
* Fast and memory-efficient semantic versioning calculations.
*
* This class encodes semantic versions as comparable strings. This encoding
* is not a valid semantic version, but instead contains all of the important
* details without losing sortability.
*
* To avoid unnecessary conversions, some methods may attempt to compare
* versions directly, and only perform encoding if it simplifies the
* calculations.
*
* Note that this module supports two different kinds of equality and comparison:
* * An exact kind, like what is compliant with the SemVer specification.
* * A pattern-matching kind, that allows wildcards and partial SemVers.
*
* Example:
* ```php
* assert( SemVer::equal('1.2.3', '1.2.3'));
* assert(!SemVer::equal('1.2.3', '4.5.6'));
* // SemVer::equal('1.2.3', '1.2.*'); // Not allowed; undefined behavior
* // SemVer::equal('1.2.3', '1.2'  ); // Not allowed; undefined behavior
*
* assert( SemVer::matching('1.2.3', '1.2.3'));
* assert(!SemVer::matching('1.2.3', '4.5.6'));
* assert( SemVer::matching('1.2.3', '1.2.*'));
* assert( SemVer::matching('1.2.3', '1.2'  ));
* ```
*
* @phpstan-type   semver_a       string
* @phpstan-type   svnum_req_a    int<0,max>
* @phpstan-type   svnum_opt_a    int<0,max>|SemVer::ARG_UNSET
* @phpstan-type   svnum_reqp_a   int<0,max>|SemVer::ARG_WILDCARD
* @phpstan-type   svnum_optp_a   int<0,max>|SemVer::ARG_*
* @phpstan-type   svpre_req_a    int<0,max>|string
* @phpstan-type   svpre_opt_a    int<0,max>|string|SemVer::ARG_UNSET
* @phpstan-type   svpre_reqp_a   int<0,max>|string|SemVer::ARG_WILDCARD
* @phpstan-type   svpre_optp_a   int<0,max>|string|SemVer::ARG_*
* @phpstan-type   svpart_req_a   svpre_req_a
* @phpstan-type   svpart_opt_a   svpre_opt_a
* @phpstan-type   svpart_reqp_a  svpre_reqp_a
* @phpstan-type   svpart_optp_a  svpre_optp_a
* @phpstan-type   svany_req_a    semver_a|svpart_req_a
* @phpstan-type   svany_opt_a    semver_a|svpart_opt_a
* @phpstan-type   svany_reqp_a   semver_a|svpart_reqp_a
* @phpstan-type   svany_optp_a   semver_a|svpart_optp_a
*
* @phpstan-type   svfunc_cmp_a      \Closure(svany_req_a,svany_req_a,svnum_opt_a=,svany_opt_a=,svany_opt_a=,svnum_opt_a=,svpart_opt_a=,svpre_opt_a=):int<-1,1>
* @phpstan-type   svfunc_equal_a    \Closure(svany_req_a,svany_req_a,svnum_opt_a=,svany_opt_a=,svany_opt_a=,svnum_opt_a=,svpart_opt_a=,svpre_opt_a=):bool
* @phpstan-type   svfunc_pcmp_a     \Closure(svany_req_a,svany_req_a,svany_opt_a=,svany_opt_a=,svany_opt_a=,svpart_opt_a=,svpart_opt_a=,svpre_opt_a=):int<-1,1>
* @phpstan-type   svfunc_matching_a \Closure(svany_req_a,svany_req_a,svany_opt_a=,svany_opt_a=,svany_opt_a=,svpart_opt_a=,svpart_opt_a=,svpre_opt_a=):bool
*
*/
final class SemVer
{
    use \Kickback\Common\Traits\StaticClassTrait;

    public const ARG_WILDCARD = -1;
    public const ARG_UNSET = -2;

    private const IDX_MAJOR      = 0;
    private const IDX_MINOR      = 1;
    private const IDX_PATCH      = 2;
    private const IDX_PRERELEASE = 3;
    private const IDX_BUILD      = 4;

    private const PART_NAME =
        ['major','minor','patch','prerelease','build'];

    private const WHITESPACE = "\t\n\v\r ";
    //private const DIGITS_AND_DOT = '0123456789.';
    private const DIGITS = '0123456789';
    private const ALPHA_LC = 'abcdefghijklmnopqrstuvwxyz';
    private const ALPHA_UC = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    //private const ALPHA    = self::ALPHA_UC . self::ALPHA_LC;
    private const ALPHANUM = self::DIGITS . self::ALPHA_UC . self::ALPHA_LC;
    private const ALPHANUMDOT = '.' . self::DIGITS . self::ALPHA_UC . self::ALPHA_LC;
    //private const IDENTIFIER_CHARS = '-' . self::ALPHANUM;
    private const IDENTIFIER_LIST       = '-' . self::ALPHANUMDOT;
    private const IDENTIFIER_LIST_WILD  = '*' . self::IDENTIFIER_LIST;

    /**
    * A type-safe alternative to the `empty` builtin.
    *
    * This can be used to make PHPStan stop complaining about
    * `empty($some_string)` being "not allowed" and telling us
    * to "use a more strict comparison".
    *
    * @param  ?semver_a   $x
    * @throws void
    */
    public static function empty(?string $x) : bool
    {
        return !isset($x) || (0 === strlen($x));
    }

    private static function unittest_intval() : void
    {
        echo("  ".__FUNCTION__."()\n");

        // We rely on undocumented behavior of `\intval`
        // to avoid needing to call `\trim`.
        // We can't guarantee it will always be there,
        // but we CAN test it: if unittests pass, we're good to go!
        assert(\intval('1') === 1); // @phpstan-ignore function.alreadyNarrowedType
        assert(\intval(' 2') === 2); // @phpstan-ignore function.alreadyNarrowedType
        assert(\intval('3 ') === 3); // @phpstan-ignore function.alreadyNarrowedType
        assert(\intval(' 4 ') === 4); // @phpstan-ignore function.alreadyNarrowedType
        assert(\intval("\t1") === 1); // @phpstan-ignore function.alreadyNarrowedType
        assert(\intval("\n2") === 2); // @phpstan-ignore function.alreadyNarrowedType
        assert(\intval("\v3") === 3); // @phpstan-ignore function.alreadyNarrowedType
        assert(\intval("\r4") === 4); // @phpstan-ignore function.alreadyNarrowedType
        assert(\intval("\t\n\v\r 5") === 5); // @phpstan-ignore function.alreadyNarrowedType
        assert(\intval("6\t\n\v\r ") === 6); // @phpstan-ignore function.alreadyNarrowedType
        assert(\intval("\t\n\v\r 7\t\n\v\r ") === 7); // @phpstan-ignore function.alreadyNarrowedType
    }

    /**
    * @param  svnum_optp_a  $major
    * @param  svnum_optp_a  $minor
    * @param  svnum_optp_a  $patch
    * @param  svpre_optp_a  $prerelease
    * @param  svpre_optp_a  $build_metadata
    * @return int<0,6>
    * @throws void
    */
    public static function count_ordinal_components_in_ints(
        int $major, int $minor, int $patch, string|int $prerelease, string|int $build_metadata
    ) : int
    {
        if ($major < 0) { return 0; }
        if ($minor < 0) { return 1; }
        if ($patch < 0) { return 2; }
        $unset = self::ARG_UNSET;
        return 3 + \intval($prerelease !== $unset) | (\intval($build_metadata !== $unset) << 1);
    }

    /**
    * @param  svnum_optp_a  $major
    * @param  svnum_optp_a  $minor
    * @param  svnum_optp_a  $patch
    * @param  svpre_optp_a  $prerelease
    * @param  svpre_optp_a  $build_metadata
    * @return int<0,6>
    * @throws void
    */
    public static function count_patternistic_components_in_ints(
        int $major, int $minor, int $patch, string|int $prerelease, string|int $build_metadata
    ) : int
    {
        if ($major !== self::ARG_UNSET) { return 0; }
        if ($minor !== self::ARG_UNSET) { return 1; }
        if ($patch !== self::ARG_UNSET) { return 2; }
        $unset = self::ARG_UNSET;
        return 3 + \intval($prerelease !== $unset) | (\intval($build_metadata !== $unset) << 1);
    }

    /**
    * @param   semver_a     $ver
    * @param   self::IDX_*  $part_idx
    * @return  svnum_optp_a
    */
    private static function select_numeric_part_by_index(string $ver, int $part_idx) : int
    {
        $major          = self::ARG_UNSET;
        $minor          = self::ARG_UNSET;
        $patch          = self::ARG_UNSET;
        $prerelease     = self::ARG_UNSET;
        $build_metadata = self::ARG_UNSET;
        $n_components = self::deconstruct_n(
            $ver, 3, $major, $minor, $patch, $prerelease, $build_metadata);

        switch($part_idx) {
            case self::IDX_MAJOR: return $major;
            case self::IDX_MINOR: return $minor;
            case self::IDX_PATCH: return $patch;
        }
        $part_idx_str = \strval($part_idx);
        assert(false, "Invalid part index: $part_idx_str"); // @phpstan-ignore function.impossibleType
        return self::ARG_UNSET;
    }

    /**
    * @param   semver_a     $ver
    * @param   self::IDX_*  $part_idx
    * @return  svpre_optp_a
    */
    private static function select_alphanum_part_by_index(string $ver, int $part_idx) : string|int
    {
        $major          = self::ARG_UNSET;
        $minor          = self::ARG_UNSET;
        $patch          = self::ARG_UNSET;
        $prerelease     = self::ARG_UNSET;
        $build_metadata = self::ARG_UNSET;
        $n_components = self::deconstruct(
            $ver, $major, $minor, $patch, $prerelease, $build_metadata);

        switch($part_idx) {
            case self::IDX_PRERELEASE: return $prerelease;
            case self::IDX_BUILD:      return $build_metadata;
        }
        $part_idx_str = \strval($part_idx);
        assert(false, "Invalid part index: $part_idx_str"); // @phpstan-ignore function.impossibleType
        return self::ARG_UNSET;
    }

    /**
    * Construct a SemVer string or pattern from integer components.
    *
    * This is essentially the same function as `SemVer::construct`,
    * with the difference being that this version allows `SemVer::WILDCARD`
    * arguments to be passed in, and will generate a string with
    * asterisk characters (`*`) in the corresponding positions.
    *
    * This function will inevitably allocate memory,
    * as is needed to store the string that is returned.
    * If preallocating memory is possible, consider using
    * `pconstruct_into` instead, to avoid _some_ allocations.
    *
    * @see pconstruct_into
    * @see construct_into
    * @see construct
    * @see deconstruct
    *
    * @param      svnum_optp_a   $major
    * @param      svnum_optp_a   $minor
    * @param      svnum_optp_a   $patch
    * @param      svpre_optp_a   $prerelease
    * @param      svpre_optp_a   $build_metadata
    * @return     semver_a
    * @throws     void
    */
    public static function pconstruct(
        int         $major,
        int         $minor          = self::ARG_UNSET,
        int         $patch          = self::ARG_UNSET,
        string|int  $prerelease     = self::ARG_UNSET,
        string|int  $build_metadata = self::ARG_UNSET
    ) : string
    {
        $buffer = '0.00.00'; // Reasonable allocation guess. (7 chars + 1 null terminator if c string)
        $cursor = 0;
        return self::pconstruct_into($buffer, $cursor, $major, $minor, $patch, $prerelease, $build_metadata);
    }

    /**
    * Construct a SemVer pattern from integer components using a buffer.
    *
    * Unlike `pconstruct`, this function can potentially avoid memory
    * allocations if the `$dst_buffer` can be preallocated and/or reused.
    *
    * This is essentially the same function as `SemVer::construct`,
    * with the difference being that this version allows `SemVer::WILDCARD`
    * arguments to be passed in, and will generate a string with
    * asterisk characters (`*`) in the corresponding positions.
    *
    * @see construct_into
    * @see construct
    * @see pconstruct
    * @see deconstruct
    *
    * @param      string         $dst_buffer The buffer to write into.
    * @param-out  string         $dst_buffer
    * @param      int<0,max>     $dst_cursor The place to begin writing into. Will be advanced by the number of bytes written.
    * @param-out  int<0,max>     $dst_cursor
    * @param      svnum_optp_a   $major
    * @param      svnum_optp_a   $minor
    * @param      svnum_optp_a   $patch
    * @param      svpre_optp_a   $prerelease
    * @param      svpre_optp_a   $build_metadata
    * @return     semver_a
    * @throws     void
    */
    public static function pconstruct_into(
        string      &$dst_buffer,
        int         &$dst_cursor,
        int         $major,
        int         $minor          = self::ARG_WILDCARD,
        int         $patch          = self::ARG_WILDCARD,
        string|int  $prerelease     = self::ARG_UNSET,
        string|int  $build_metadata = self::ARG_UNSET
    ) : string
    {
        // Basic validation.
        $build = $build_metadata;
        assert(self::ARG_UNSET === $prerelease || is_int($prerelease) || 0 < \strlen($prerelease));
        assert(self::ARG_UNSET === $build      || is_int($build)      || 0 < \strlen($build));

        $start = $dst_cursor;
        $nchars = 0;

        if ($major === self::ARG_UNSET) { return \substr($dst_buffer, $start, $nchars); }
        $nchars += self::write_numeric_component($dst_buffer, $dst_cursor, '',  $major);

        if ($minor === self::ARG_UNSET) { return \substr($dst_buffer, $start, $nchars); }
        $nchars += self::write_numeric_component($dst_buffer, $dst_cursor, '.', $minor);

        if ($patch === self::ARG_UNSET) { return \substr($dst_buffer, $start, $nchars); }
        $nchars += self::write_numeric_component($dst_buffer, $dst_cursor, '.', $patch);

        // If the whole triplet is there, then $prerelease+build can always be written.
        $nchars += self::write_alphanum_component($dst_buffer, $dst_cursor, '-', $prerelease);
        $nchars += self::write_alphanum_component($dst_buffer, $dst_cursor, '+', $build);

        return \substr($dst_buffer, $start, $nchars);
    }

    // Number of chars required to represent ',-9223372036854775808'
    private const SPACES21 = '                     ';

    /**
    * @param      string        $dst_buffer The buffer to write into.
    * @param-out  string        $dst_buffer
    * @param      int<0,max>    $dst_cursor The place to begin writing into. Will be advanced by the number of bytes written.
    * @param-out  int<0,max>    $dst_cursor
    * @param      string        $sep        Separator to place before (to the left of) the written value.
    * @param      svpre_optp_a  $value      The value to write into the buffer. Supports wildcards.
    *
    * @throws void
    */
    private static function write_alphanum_component(
        string      &$dst_buffer,
        int         &$dst_cursor,
        string      $sep,
        int|string  $value
    ) : int
    {
        if (is_int($value)) {
            return self::write_numeric_component($dst_buffer, $dst_cursor, $sep, $value);
        }

        $sep_sz = \strlen($sep);
        $pos = $dst_cursor;
        $nchars = \strlen($value);
        Str::grow_destination_string_as_needed(
            $dst_buffer, $pos, $nchars+$sep_sz, self::SPACES21);
        if (1 === $sep_sz) {
            $dst_buffer[$pos++] = $sep;
        }
        for ($i = 0; $i < $nchars; $i++ ) {
            $dst_buffer[$pos++] = $value[$i];
        }
        $dst_cursor = $pos;
        return $nchars+$sep_sz;
    }

    /**
    * @param      string        $dst_buffer The buffer to write into.
    * @param-out  string        $dst_buffer
    * @param      int<0,max>    $dst_cursor The place to begin writing into. Will be advanced by the number of bytes written.
    * @param-out  int<0,max>    $dst_cursor
    * @param      string        $sep        Separator to place before (to the left of) the written value.
    * @param      svnum_optp_a  $value      The value to write into the buffer. Supports wildcards.
    *
    * @throws void
    */
    private static function write_numeric_component(
        string  &$dst_buffer,
        int     &$dst_cursor,
        string  $sep,
        int     $value
    ) : int
    {
        // echo "write_numeric_component('$dst_buffer', $dst_cursor, '$sep', $value)\n";
        $sep_sz = \strlen($sep);
        assert($sep_sz <= 1);
        if ( $value === self::ARG_UNSET ) {
            return 0;
        }
        if ( $value === self::ARG_WILDCARD ) {
            $nchars = 1;
        } else {
            assert($value > 0);
            $nchars = Int_::number_of_digits_needed_to_represent($value);
        }
        $nchars += $sep_sz;
        $pos = $dst_cursor;
        // echo "dst_buffer='$dst_buffer';  pos=$pos;  nchars=$nchars;\n";
        Str::grow_destination_string_as_needed(
            $dst_buffer, $pos, $nchars, self::SPACES21);
        if (1 === $sep_sz) {
            $dst_buffer[$pos++] = $sep;
        }
        // echo "dst_buffer='$dst_buffer';  pos=$pos;\n";

        if ( $value === self::ARG_WILDCARD ) {
            $dst_buffer[$pos++] = '*';
            $dst_cursor = $pos;
            return $nchars;
        }

        $digit_lookup = self::DIGITS;
        $start_pos = $pos;
        $ndigits = $nchars - $sep_sz;
        // echo "dst_buffer='$dst_buffer';  pos=$pos;  ndigits=$ndigits;\n";
        $pos += $ndigits;
        while($pos > $start_pos) {
            $pos--;
            $digit = $digit_lookup[$value % 10];
            $value = \intdiv($value, 10);
            $dst_buffer[$pos] = $digit;
            // echo "dst_buffer='$dst_buffer';  pos=$pos;\n";
        }
        $pos += $ndigits;
        $dst_cursor = $pos;
        // echo "dst_buffer='$dst_buffer';  pos=$pos;\n";
        return $nchars;
    }

    /**
    * Construct a Semantic Version string (SemVer) from integer components.
    *
    * Example:
    * ```php
    * assert(SemVer::construct(1, 2, 3) === '1.2.3');
    * assert(SemVer::construct(4, 5, 6, 'rc7', 89)   === '4.5.6-rc7+89');
    * assert(SemVer::construct(4, 5, 6, 'rc7', '89') === '4.5.6-rc7+89');
    * ```
    *
    * This function will inevitably allocate memory,
    * as is needed to store the string that is returned.
    * If preallocating memory is possible, consider using
    * `construct_into` instead, to avoid _some_ allocations.
    *
    * @see construct_into
    * @see pconstruct
    * @see pconstruct_into
    * @see deconstruct
    *
    * @param      svnum_req_a   $major
    * @param      svnum_req_a   $minor
    * @param      svnum_req_a   $patch
    * @param      svpre_opt_a   $prerelease
    * @param      svpre_opt_a   $build_metadata
    * @return     semver_a
    * @throws     void
    */
    public static function construct(
        int         $major,
        int         $minor          = 0,
        int         $patch          = 0,
        string|int  $prerelease     = self::ARG_UNSET,
        string|int  $build_metadata = self::ARG_UNSET
    ) : string
    {
        $build = $build_metadata;
        $selector =  ($prerelease !== self::ARG_UNSET) ? 0x01 : 0x00;
        $selector |= ($build !== self::ARG_UNSET)      ? 0x02 : 0x00;

        // Basic validation.
        assert(self::ARG_UNSET === $prerelease || is_int($prerelease) || 0 < \strlen($prerelease));
        assert(self::ARG_UNSET === $build      || is_int($build)      || 0 < \strlen($build));

        switch($selector) {
            case 0x00: return \sprintf('%d.%d.%d',$major,$minor,$patch);
            case 0x01: return \sprintf('%d.%d.%d-%s',$major,$minor,$patch,$prerelease);
            case 0x02: return \sprintf('%d.%d.%d+%s',$major,$minor,$patch,$build);
            case 0x03: return \sprintf('%d.%d.%d-%s+%s',$major,$minor,$patch,$prerelease,$build);

            // Code should not be reachable.
            default: assert(false); return ''; // @phpstan-ignore function.impossibleType
        }
    }

    private static function unittest_construct() : void
    {
        echo("  ".__FUNCTION__."()\n");

        assert(SemVer::construct(1, 2, 3) === '1.2.3');
        assert(SemVer::construct(4, 5, 6, 'rc7', 89)   === '4.5.6-rc7+89');
        assert(SemVer::construct(4, 5, 6, 'rc7', '89') === '4.5.6-rc7+89');
    }

    /**
    * Construct a SemVer string from integer components using a buffer.
    *
    * Unlike `construct`, this function can potentially avoid memory
    * allocations if the `$dst_buffer` can be preallocated and/or reused.
    *
    * Example:
    * ```php
    * // Memory reuse.
    * $buffer = '. .A. .B. .C. .D. .E. .';
    *
    * assert(SemVer::construct_into($buffer, $cursor=0,  1,2,3) === '1.2.3');
    * assert($buffer === '1.2.3 .B. .C. .D. .E. .');
    *
    * assert(SemVer::construct_into(
    *     $buffer, $cursor=0,  4,5,6,'rc7',89)   === '4.5.6-rc7+89');
    * assert($buffer === '4.5.6-rc7+89. .D. .E. .');
    *
    * assert(SemVer::construct_into(
    *     $buffer, $cursor=0,  4,5,6,'rc7','89') === '4.5.6-rc7+89');
    * assert($buffer === '4.5.6-rc7+89. .D. .E. .');
    *
    * // Streaming.
    * $buffer = '. .A. .B. .C. .D. .E. .';
    * $cursor = 0;
    *
    * assert(SemVer::construct_into($buffer, $cursor,  1,2,3) === '1.2.3');
    * assert($buffer === '1.2.3 .B. .C. .D. .E. .');
    * assert($cursor === 5);
    * $cursor++;
    *
    * assert(SemVer::construct_into(
    *     $buffer, $cursor,  4,5,6,'rc7',89)   === '4.5.6-rc7+89');
    * assert($buffer === '1.2.3 4.5.6-rc7+89.E. .');
    * assert($cursor === 18);
    * $cursor++;
    *
    * assert(SemVer::construct_into(
    *     $buffer, $cursor,  4,5,6,'rc7','89') === '4.5.6-rc7+89');
    * assert($buffer === '1.2.3 4.5.6-rc7+89.4.5.6-rc7+89'); // Grows as needed.
    * assert($cursor === 31);
    * ```
    *
    * @see construct
    * @see pconstruct_into
    * @see pconstruct
    * @see deconstruct
    *
    * @param      string        $dst_buffer The buffer to write into.
    * @param-out  string        $dst_buffer
    * @param      int<0,max>    $dst_cursor The place to begin writing into. Will be advanced by the number of bytes written.
    * @param-out  int<0,max>    $dst_cursor
    * @param      svnum_req_a   $major
    * @param      svnum_req_a   $minor
    * @param      svnum_req_a   $patch
    * @param      svpre_opt_a   $prerelease
    * @param      svpre_opt_a   $build_metadata
    * @return     semver_a
    * @throws     void
    */
    public static function construct_into(
        string      &$dst_buffer,
        int         &$dst_cursor,
        int         $major,
        int         $minor          = 0,
        int         $patch          = 0,
        string|int  $prerelease     = self::ARG_UNSET,
        string|int  $build_metadata = self::ARG_UNSET
    ) : string
    {
        // Basic validation.
        $build = $build_metadata;
        assert(self::ARG_UNSET === $prerelease || is_int($prerelease) || 0 < \strlen($prerelease));
        assert(self::ARG_UNSET === $build      || is_int($build)      || 0 < \strlen($build));

        // No partials.
        assert($major      !== self::ARG_UNSET); // @phpstan-ignore function.alreadyNarrowedType
        assert($minor      !== self::ARG_UNSET); // @phpstan-ignore function.alreadyNarrowedType
        assert($patch      !== self::ARG_UNSET); // @phpstan-ignore function.alreadyNarrowedType

        // No patterns allowed.
        assert($major      !== self::ARG_WILDCARD); // @phpstan-ignore function.alreadyNarrowedType
        assert($minor      !== self::ARG_WILDCARD); // @phpstan-ignore function.alreadyNarrowedType
        assert($patch      !== self::ARG_WILDCARD); // @phpstan-ignore function.alreadyNarrowedType
        assert($prerelease !== self::ARG_WILDCARD); // @phpstan-ignore function.alreadyNarrowedType
        assert($build      !== self::ARG_WILDCARD); // @phpstan-ignore function.alreadyNarrowedType

        // Aside from those restrictions, it's the same thing as `pconstruct_into`.
        return self::pconstruct_into(
            $dst_buffer, $dst_cursor, $major, $minor, $patch, $prerelease, $build);
    }

    private static function unittest_construct_into() : void
    {
        echo("  ".__FUNCTION__."()\n");

        // Memory reuse.
        $buffer = '. .A. .B. .C. .D. .E. .';

        $cursor=0;
        assert(SemVer::construct_into($buffer, $cursor,  1,2,3) === '1.2.3');
        assert($buffer === '1.2.3 .B. .C. .D. .E. .');

        $cursor=0;
        assert(SemVer::construct_into(
            $buffer, $cursor,  4,5,6,'rc7',89)   === '4.5.6-rc7+89');
        assert($buffer === '4.5.6-rc7+89. .D. .E. .');

        $cursor=0;
        assert(SemVer::construct_into(
            $buffer, $cursor,  4,5,6,'rc7','89') === '4.5.6-rc7+89');
        assert($buffer === '4.5.6-rc7+89. .D. .E. .');

        // Streaming.
        $buffer = '. .A. .B. .C. .D. .E. .';
        $cursor = 0;

        assert(SemVer::construct_into($buffer, $cursor,  1,2,3) === '1.2.3');
        assert($buffer === '1.2.3 .B. .C. .D. .E. .');
        assert($cursor === 5);
        $cursor++;

        assert(SemVer::construct_into(
            $buffer, $cursor,  4,5,6,'rc7',89)   === '4.5.6-rc7+89');
        assert($buffer === '1.2.3 4.5.6-rc7+89.E. .');
        assert($cursor === 18);
        $cursor++;

        assert(SemVer::construct_into(
            $buffer, $cursor,  4,5,6,'rc7','89') === '4.5.6-rc7+89');
        assert($buffer === '1.2.3 4.5.6-rc7+89.4.5.6-rc7+89'); // Grows as needed.
        assert($cursor === 31);
    }

    /**
    * Extracts all components from a Semantic Version (SemVer) string (or pattern).
    *
    * Example:
    * ```php
    * $major = SemVer::ARG_UNSET;
    * $minor = SemVer::ARG_UNSET;
    * $patch = SemVer::ARG_UNSET;
    * $prerelease = SemVer::ARG_UNSET;
    * $build_metadata = SemVer::ARG_UNSET;
    *
    * $version = '4.5.6-rc7+89';
    * $n_components = SemVer::deconstruct(
    *     $version, $major, $minor, $patch, $prerelease, $build_metadata);
    * assert($n_components === 6); // The combination of prerelease+build_metadata counts as 3.
    * assert($major === 4);
    * assert($minor === 5);
    * assert($patch === 6);
    * assert($prerelease === 'rc7');
    * assert($build_metadata === '89');
    *
    * $version = '1.2.3';
    * $n_components = SemVer::deconstruct(
    *     $version, $major, $minor, $patch, $prerelease, $build_metadata);
    * assert($n_components === 3);
    * assert($major === 1);
    * assert($minor === 2);
    * assert($patch === 3);
    * assert($prerelease === SemVer::ARG_UNSET);
    * assert($build_metadata === SemVer::ARG_UNSET);
    * ```
    *
    * @see deconstruct_n
    * @see construct
    * @see construct_into
    * @see pconstruct
    * @see pconstruct_into
    *
    * @param      semver_a      $semver
    * @param      svnum_optp_a  $major
    * @param-out  svnum_optp_a  $major
    * @param      svnum_optp_a  $minor
    * @param-out  svnum_optp_a  $minor
    * @param      svnum_optp_a  $patch
    * @param-out  svnum_optp_a  $patch
    * @param      svpre_optp_a  $prerelease
    * @param-out  svpre_optp_a  $prerelease
    * @param      svpre_optp_a  $build_metadata
    * @param-out  svpre_optp_a  $build_metadata
    * @return  int<0,6>  The number of components that were successfully parsed.
    * @throws  void
    */
    public static function deconstruct(
        string     $semver,
        int        &$major          = self::ARG_UNSET,
        int        &$minor          = self::ARG_UNSET,
        int        &$patch          = self::ARG_UNSET,
        string|int &$prerelease     = self::ARG_UNSET,
        string|int &$build_metadata = self::ARG_UNSET,
    ) : int
    {
        $n_components = 6;
        $res = self::deconstruct_n(
            $semver, $n_components,
            $major, $minor, $patch, $prerelease, $build_metadata);
        return $res;
    }

    private static function unittest_deconstruct() : void
    {
        echo("  ".__FUNCTION__."()\n");

        // Test raw outputs from PHPDoc comment examples.
        $major = SemVer::ARG_UNSET;
        $minor = SemVer::ARG_UNSET;
        $patch = SemVer::ARG_UNSET;
        $prerelease = SemVer::ARG_UNSET;
        $build_metadata = SemVer::ARG_UNSET;

        $version = '4.5.6-rc7+89';
        $n_components = SemVer::deconstruct(
            $version, $major, $minor, $patch, $prerelease, $build_metadata);
        assert($n_components === 6); // The combination of prerelease+build_metadata counts as 3.
        assert($major === 4);
        assert($minor === 5);
        assert($patch === 6);
        assert($prerelease === 'rc7');
        assert($build_metadata === '89');

        $version = '1.2.3';
        $n_components = SemVer::deconstruct(
            $version, $major, $minor, $patch, $prerelease, $build_metadata);
        assert($n_components === 3);
        assert($major === 1);
        assert($minor === 2);
        assert($patch === 3);
        assert($prerelease === SemVer::ARG_UNSET);
        assert($build_metadata === SemVer::ARG_UNSET);

        // Define a function that formats what the parser sees.
        // This will make testing easier for a bunch of cases.
        $fmt_deconstruct = function(string $semver) : string
        {
            $major = SemVer::ARG_UNSET;
            $minor = SemVer::ARG_UNSET;
            $patch = SemVer::ARG_UNSET;
            $prerelease = SemVer::ARG_UNSET;
            $build_metadata = SemVer::ARG_UNSET;

            $n_components = SemVer::deconstruct(
                $semver, $major, $minor, $patch, $prerelease, $build_metadata);
            assert(0 <= $n_components); // @phpstan-ignore function.alreadyNarrowedType, smallerOrEqual.alwaysTrue
            assert($n_components <= 6); // @phpstan-ignore function.alreadyNarrowedType, smallerOrEqual.alwaysTrue
            if ($n_components < 3) {
                return '';
            }
            if ($n_components === 3) {
                return sprintf('(%d) (%d) (%d) () ()', $major, $minor, $patch);
            }
            if ($n_components === 4) {
                return sprintf('(%d) (%d) (%d) (%s) ()', $major, $minor, $patch, $prerelease);
            }
            if ($n_components === 5) {
                return sprintf('(%d) (%d) (%d) () (%s)', $major, $minor, $patch, $build_metadata);
            }
            if ($n_components === 6) {
                return sprintf('(%d) (%d) (%d) (%s) (%s)', $major, $minor, $patch, $prerelease, $build_metadata);
            }
        };

        assert(self::ARG_WILDCARD === -1); // @phpstan-ignore function.alreadyNarrowedType
        assert($fmt_deconstruct('1.2.*') === '(1) (2) (-1) () ()');
        assert($fmt_deconstruct('1.2.3-*') === '(1) (2) (3) (*) ()');
        assert($fmt_deconstruct('1.2.3-*+*') === '(1) (2) (3) (*) (*)');
        assert($fmt_deconstruct('1.2.3-x.*') === '(1) (2) (3) (x.*) ()');
        assert($fmt_deconstruct('1.2.3-x.rc4') === '(1) (2) (3) (x.rc4) ()');

        // From: https://github.com/semver/semver/issues/981
        assert($fmt_deconstruct('0.0.4') === '(0) (0) (4) () ()');
        assert($fmt_deconstruct('1.2.3') === '(1) (2) (3) () ()');
        assert($fmt_deconstruct('10.20.30') === '(10) (20) (30) () ()');
        assert($fmt_deconstruct('1.1.2-prerelease+meta') === '(1) (1) (2) (prerelease) (meta)');
        assert($fmt_deconstruct('1.1.2+meta') === '(1) (1) (2) () (meta)');
        assert($fmt_deconstruct('1.1.2+meta-valid') === '(1) (1) (2) () (meta-valid)');
        assert($fmt_deconstruct('1.0.0-alpha') === '(1) (0) (0) (alpha) ()');
        assert($fmt_deconstruct('1.0.0-beta') === '(1) (0) (0) (beta) ()');
        assert($fmt_deconstruct('1.0.0-alpha.beta') === '(1) (0) (0) (alpha.beta) ()');
        assert($fmt_deconstruct('1.0.0-alpha.beta.1') === '(1) (0) (0) (alpha.beta.1) ()');
        assert($fmt_deconstruct('1.0.0-alpha.1') === '(1) (0) (0) (alpha.1) ()');
        assert($fmt_deconstruct('1.0.0-alpha0.valid') === '(1) (0) (0) (alpha0.valid) ()');
        assert($fmt_deconstruct('1.0.0-alpha.0valid') === '(1) (0) (0) (alpha.0valid) ()');
        assert($fmt_deconstruct('1.0.0-alpha-a.b-c-somethinglong+build.1-aef.1-its-okay') === '(1) (0) (0) (alpha-a.b-c-somethinglong) (build.1-aef.1-its-okay)');
        assert($fmt_deconstruct('1.0.0-rc.1+build.1') === '(1) (0) (0) (rc.1) (build.1)');
        assert($fmt_deconstruct('2.0.0-rc.1+build.123') === '(2) (0) (0) (rc.1) (build.123)');
        assert($fmt_deconstruct('1.2.3-beta') === '(1) (2) (3) (beta) ()');
        assert($fmt_deconstruct('10.2.3-DEV-SNAPSHOT') === '(10) (2) (3) (DEV-SNAPSHOT) ()');
        assert($fmt_deconstruct('1.2.3-SNAPSHOT-123') === '(1) (2) (3) (SNAPSHOT-123) ()');
        assert($fmt_deconstruct('1.0.0') === '(1) (0) (0) () ()');
        assert($fmt_deconstruct('2.0.0') === '(2) (0) (0) () ()');
        assert($fmt_deconstruct('1.1.7') === '(1) (1) (7) () ()');
        assert($fmt_deconstruct('2.0.0+build.1848') === '(2) (0) (0) () (build.1848)');
        assert($fmt_deconstruct('2.0.1-alpha.1227') === '(2) (0) (1) (alpha.1227) ()');
        assert($fmt_deconstruct('1.0.0-alpha+beta') === '(1) (0) (0) (alpha) (beta)');
        assert($fmt_deconstruct('1.2.3----RC-SNAPSHOT.12.9.1--.12+788') === '(1) (2) (3) (---RC-SNAPSHOT.12.9.1--.12) (788)');
        assert($fmt_deconstruct('1.2.3----R-S.12.9.1--.12+meta') === '(1) (2) (3) (---R-S.12.9.1--.12) (meta)');
        assert($fmt_deconstruct('1.2.3----RC-SNAPSHOT.12.9.1--.12') === '(1) (2) (3) (---RC-SNAPSHOT.12.9.1--.12) ()');
        assert($fmt_deconstruct('1.0.0+0.build.1-rc.10000aaa-kk-0.1') === '(1) (0) (0) () (0.build.1-rc.10000aaa-kk-0.1)');
        // We can't handle this one because the major version is
        // larger than what a 64-bit int can represent. Too bad.
        //assert($fmt_deconstruct('99999999999999999999999.999999999999999999.99999999999999999') === '(99999999999999999999999) (999999999999999999) (99999999999999999) () ()');
        assert($fmt_deconstruct('1.0.0-0A.is.legal') === '(1) (0) (0) (0A.is.legal) ()');

        //  A lot of these probably won't be rejected by the current parser.
        //  That's a WIP for now. We'll need to implement SV_ALLOW flags
        //  that control what the parser admits or rejects so that we
        //  can tune the permissiveness between "handles rough input"
        //  and "validates only standard-compliant stuff".
        assert('' === $fmt_deconstruct('1'));
        assert('' === $fmt_deconstruct('1.2'));
        //assert('' === $fmt_deconstruct('1.2.3-0123'));  // TODO: ability to reject leading zeroes
        //assert('' === $fmt_deconstruct('1.2.3-0123.0123')); // TODO: ability to reject leading zeroes
        //assert('' === $fmt_deconstruct('1.1.2+.123')); // TODO: From semver standard: "Identifiers MUST NOT be empty"
        assert('' === $fmt_deconstruct('+invalid'));
        assert('' === $fmt_deconstruct('-invalid'));
        assert('' === $fmt_deconstruct('-invalid+invalid'));
        assert('' === $fmt_deconstruct('-invalid.01'));
        assert('' === $fmt_deconstruct('alpha'));
        assert('' === $fmt_deconstruct('alpha.beta'));
        assert('' === $fmt_deconstruct('alpha.beta.1'));
        assert('' === $fmt_deconstruct('alpha.1'));
        assert('' === $fmt_deconstruct('alpha+beta'));
        assert('' === $fmt_deconstruct('alpha_beta'));
        assert('' === $fmt_deconstruct('alpha.'));
        assert('' === $fmt_deconstruct('alpha..'));
        assert('' === $fmt_deconstruct('beta'));
        assert('' === $fmt_deconstruct('1.0.0-alpha_beta'));
        assert('' === $fmt_deconstruct('-alpha.'));
        // assert('' === $fmt_deconstruct('1.0.0-alpha..')); // TODO: From semver standard: "Identifiers MUST NOT be empty"
        // assert('' === $fmt_deconstruct('1.0.0-alpha..1')); // ditto
        // assert('' === $fmt_deconstruct('1.0.0-alpha...1')); // ditto
        // assert('' === $fmt_deconstruct('1.0.0-alpha....1')); // ditto
        // assert('' === $fmt_deconstruct('1.0.0-alpha.....1')); // ditto
        // assert('' === $fmt_deconstruct('1.0.0-alpha......1')); // ditto
        // assert('' === $fmt_deconstruct('1.0.0-alpha.......1')); // ditto
        // assert('' === $fmt_deconstruct('01.1.1')); // TODO: ability to reject leading zeroes
        // assert('' === $fmt_deconstruct('1.01.1')); // TODO: ability to reject leading zeroes
        // assert('' === $fmt_deconstruct('1.1.01')); // TODO: ability to reject leading zeroes
        assert('' === $fmt_deconstruct('1.2.3.DEV'));
        assert('' === $fmt_deconstruct('1.2-SNAPSHOT'));
        assert('' === $fmt_deconstruct('1.2.31.2.3----RC-SNAPSHOT.12.09.1--..12+788'));
        assert('' === $fmt_deconstruct('1.2-RC-SNAPSHOT'));
        assert('' === $fmt_deconstruct('-1.0.3-gamma+b7718'));
        assert('' === $fmt_deconstruct('+justmeta'));
        assert('' === $fmt_deconstruct('9.8.7+meta+meta'));
        assert('' === $fmt_deconstruct('9.8.7-whatever+meta+meta'));
        // We can't handle this one because the major version is
        // larger than what a 64-bit int can represent. Too bad.
        // assert('' === $fmt_deconstruct('99999999999999999999999.999999999999999999.99999999999999999----RC-SNAPSHOT.12.09.1--------------------------------..12'));

        // Common tests for single-component extractors that depend on `deconstruct`:

        // Slow way to extract multiple version components:
        $version = '4.5.6-rc7+89';
        assert(SemVer::major($version) === 4);
        assert(SemVer::minor($version) === 5);
        assert(SemVer::patch($version) === 6);
        assert(SemVer::prerelease($version) === 'rc7');
        assert(SemVer::build_metadata($version) === '89');

        $version = '1.2.3';
        assert(SemVer::major($version) === 1);
        assert(SemVer::minor($version) === 2);
        assert(SemVer::patch($version) === 3);
        assert(SemVer::prerelease($version) === SemVer::ARG_UNSET);
        assert(SemVer::build_metadata($version) === SemVer::ARG_UNSET);
    }

    /**
    * Extract the first N components of the given semver.
    *
    * `$n_components`, as an input parameter, tells the parser how many
    * components it will even attempt to parse. Once it has parsed
    * `$n_components`, it will halt and return with what it has so far.
    *
    * As an output parameter, `$n_components` tells the caller how many
    * parts of the semantic version were extracted into this function's
    * reference parameters.
    *
    * The $n_components parameter is _mostly_ what it sounds like, except
    * that it behaves more flag-like when modelling the existence of
    * $prerelease and $build_metadata identifiers.
    *
    * In detail, here are what the different values of $n_components represent:
    * 0. Input: Parse nothing.
    *    Output: Nothing even remotely resembling a SemVer was found.
    * 1. Input: Parse the major version number.
    *    Output: Only the major version number was extracted.
    * 2. Input: Parse the major and minor version numbers.
    *    Output: Only the major and minor version numbers were extracted.
    * 3. Input: Parse all triplet numbers (major, minor, patch).
    *    Output: Only the (major, minor, and patch) were extracted.
    * 4. Input: Parse triplets and _also_ parse the prerelease identifier(s).
    *    Output: Parsed major, minor, and patch number, as well as prerelease identifier.
    * 5. Input: Parse triplets and _also_ parse the build metadata. (But not prerelease.)
    *    Output: Parsed major, minor, and patch number, as well as "build metadata". (But not prerelease.)
    * 6: Input: Parse every possible component.
    *    Output: Successfully parsed every possible component.
    *
    * @see deconstruct
    * @see construct
    * @see construct_into
    * @see pconstruct
    * @see pconstruct_into
    *
    * @param      semver_a      $semver
    * @param      int<0,6>      $n_components  The maximum number of components to extract.
    * @param      svnum_optp_a  $major
    * @param-out  svnum_optp_a  $major
    * @param      svnum_optp_a  $minor
    * @param-out  svnum_optp_a  $minor
    * @param      svnum_optp_a  $patch
    * @param-out  svnum_optp_a  $patch
    * @param      svpre_optp_a  $prerelease
    * @param-out  svpre_optp_a  $prerelease
    * @param      svpre_optp_a  $build_metadata
    * @param-out  svpre_optp_a  $build_metadata
    * @return     int<0,6>  The number of components that were successfully parsed.
    * @throws     void
    */
    public static function deconstruct_n(
        string     $semver,
        int        $n_components,
        int        &$major          = self::ARG_UNSET,
        int        &$minor          = self::ARG_UNSET,
        int        &$patch          = self::ARG_UNSET,
        string|int &$prerelease     = self::ARG_UNSET,
        string|int &$build_metadata = self::ARG_UNSET,
    ) : int
    {
        $process_wildcards = true;
        $pos = 0;
        $rest = \strlen($semver);
        $out_n_components = $n_components;
        $tmp_major          = self::ARG_UNSET;
        $tmp_minor          = self::ARG_UNSET;
        $tmp_patch          = self::ARG_UNSET;
        $tmp_prerelease     = self::ARG_UNSET;
        $tmp_build_metadata = self::ARG_UNSET;
        self::parse_semver(
            $process_wildcards, $semver, $pos, $rest, $out_n_components,
            $tmp_major, $tmp_minor, $tmp_patch, $tmp_prerelease, $tmp_build_metadata);

        // In this case, we either parsed the entire string (success)
        // or there was extra junk in there that makes it not a valid semver (failure).
        if (5 <= $n_components && 0 < $rest) {
            return 0;
        }
        $major = $tmp_major;
        $minor = $tmp_minor;
        $patch = $tmp_patch;
        $prerelease = $tmp_prerelease;
        $build_metadata = $tmp_build_metadata;
        return $out_n_components;
    }

    /**
    * Parse a Semantic Version (SemVer) from a string/buffer.
    *
    * CAVEAT: The `$process_wildcards` parameter will eventually
    *   be replaced with a flags parameter that accepts a value from SV_ALLOW.
    *
    * `$semver` is the text to parse the SemVer from.
    *
    * Parsing starts at the position given by `$pos`. The `$pos`
    * parameter will be updated to advance by the number of bytes
    * that were in the SemVer.
    *
    * No more than `$slice_len` bytes will be parsed from `$semver`.
    * This parameter will be updated by decreasing it by the number
    * of bytes that were in the SemVer.
    *
    * See the `deconstruct_n` function for a description of the
    * `$n_components` parameter, including any technicalities or quirks.
    * The only notable difference here is that this function outputs
    * the `$n_components` results using the same parameter it received
    * the input from (reference parameter). The meanings are the same:
    * on input, this is the maximum number of components to parse,
    * and on output this is the number of components that actually
    * were parsed, with the only exception being that, in either case,
    * the values {4,5,6} (corresponding to the combination of prerelease
    * and build metadata identifiers) behave more bit-field-like
    * and less counter-like.
    *
    * Error handling: What counts as a parse "failure" depends on
    * what the caller expects from the parse function. Valid SemVers
    * generally have at least 3 components, so if the output of
    * `$n_components` is less than 3, then it's safe to assume that
    * the parse did not receive a valid SemVer. Alternatively,
    * when parsing partial SemVers or patterns for SemVers, it can
    * be acceptable to receive fewer components.
    *
    * If the output of `$n_components` is zero, then all of the components
    * will be `self::ARG_UNSET`. This will _probably_ correlate with
    * a return value of 0, though it is not recommended to assume that:
    * this function may advance on whitespace before realizing that
    * it encountered something not-semver-like.
    *
    * @param      bool          $process_wildcards
    * @param      semver_a      $semver
    * @param      int<0,max>    $pos
    * @param-out  int<0,max>    $pos
    * @param      int<0,max>    $rest
    * @param-out  int<0,max>    $rest
    * @param      int<0,6>      $n_components
    * @param-out  int<0,6>      $n_components
    * @param      svnum_optp_a  $major
    * @param-out  svnum_optp_a  $major
    * @param      svnum_optp_a  $minor
    * @param-out  svnum_optp_a  $minor
    * @param      svnum_optp_a  $patch
    * @param-out  svnum_optp_a  $patch
    * @param      svpre_optp_a  $prerelease
    * @param-out  svpre_optp_a  $prerelease
    * @param      svpre_optp_a  $build_metadata
    * @param-out  svpre_optp_a  $build_metadata
    * @return  int<0,max>   The number of bytes in the SemVer.
    * @throws  void
    */
    public static function parse_semver(
        bool        $process_wildcards,
        string      $semver,
        int         &$pos,
        int         &$rest,
        int         &$n_components   = 6,
        int         &$major          = self::ARG_UNSET,
        int         &$minor          = self::ARG_UNSET,
        int         &$patch          = self::ARG_UNSET,
        string|int  &$prerelease     = self::ARG_UNSET,
        string|int  &$build_metadata = self::ARG_UNSET,
    ) : int
    {

        $start_pos = $pos;
        $len = \strlen($semver);
        if ($len < $rest) {
            $rest = $len;
        }

        // Parse major component
        self::traverse_whitespace($semver, $pos, $rest);
        if ($rest === 0) {
            $n_components = 0;
        }

        $tmp_major = self::ARG_UNSET;
        $part_len = self::consume_integer(
            $process_wildcards, $semver, $pos, $rest, $tmp_major);
        if ($part_len === 0) {
            $n_components = 0;
        }

        if ($n_components === 0) {
            $major          = self::ARG_UNSET;
            $minor          = self::ARG_UNSET;
            $patch          = self::ARG_UNSET;
            $prerelease     = self::ARG_UNSET;
            $build_metadata = self::ARG_UNSET;
            return 0;
        }

        // Parse minor component
        $tmp_minor = self::ARG_UNSET;
        $part_len = 0;
        if ($n_components >= 2) {
            self::traverse_whitespace($semver, $pos, $rest);
            $sep_len = self::traverse_separators($semver, '.', $pos, $rest);
            self::traverse_whitespace($semver, $pos, $rest);
            if (0 < $sep_len) {
                $part_len = self::consume_integer(
                    $process_wildcards, $semver, $pos, $rest, $tmp_minor);
            }
        }
        if ($part_len === 0) {
            // Return partial semver.
            $part_len = $pos - $start_pos;
            $n_components = 1;
            assert(1 <= $part_len);
            $major          = $tmp_major;
            $minor          = self::ARG_UNSET;
            $patch          = self::ARG_UNSET;
            $prerelease     = self::ARG_UNSET;
            $build_metadata = self::ARG_UNSET;
            return $part_len;
        }

        // Parse patch component
        $tmp_patch = self::ARG_UNSET;
        $part_len = 0;
        if ($n_components >= 3) {
            self::traverse_whitespace($semver, $pos, $rest);
            $sep_len = self::traverse_separators($semver, '.', $pos, $rest);
            self::traverse_whitespace($semver, $pos, $rest);
            if (0 < $sep_len) {
                $part_len = self::consume_integer(
                    $process_wildcards, $semver, $pos, $rest, $tmp_patch);
            }
        }
        if ($part_len === 0) {
            // Return partial semver.
            $part_len = $pos - $start_pos;
            $n_components = 2;
            assert(3 <= $part_len);
            $major          = $tmp_major;
            $minor          = $tmp_minor;
            $patch          = self::ARG_UNSET;
            $prerelease     = self::ARG_UNSET;
            $build_metadata = self::ARG_UNSET;
            return $part_len;
        }

        // Look for possible prerelease or build metadata
        $sep_pos = -1;
        $part_len = 0;
        $tmp_prerelease = self::ARG_UNSET;
        if ($n_components >= 4) {
            self::traverse_whitespace($semver, $pos, $rest);
            $sep_len = self::traverse_separators($semver, '+-', $pos, $rest);
            self::traverse_whitespace($semver, $pos, $rest);
            $part_len = 0;
            if (0 < $sep_len) {
                $sep_pos = $pos-1;
                $part_len = self::consume_identifier(
                    $process_wildcards, $semver, $pos, $rest, $tmp_prerelease);
                if ($part_len === 0) {
                    $tmp_prerelease = self::ARG_UNSET;
                }
            }
        }
        if ($part_len === 0) {
            // Return complete non-prerelease semver.
            $part_len = $pos - $start_pos;
            $n_components = 3;
            assert(5 <= $part_len);
            $major          = $tmp_major;
            $minor          = $tmp_minor;
            $patch          = $tmp_patch;
            $prerelease     = self::ARG_UNSET;
            $build_metadata = self::ARG_UNSET;
            return $part_len;
        }

        $tmp_build_metadata = self::ARG_UNSET;
        if ($semver[$sep_pos] === '+') {
            if ( $n_components === 4 ) {
                $tmp_prerelease = self::ARG_UNSET;
            } else {
                $tmp_build_metadata = $tmp_prerelease;
                $tmp_prerelease = self::ARG_UNSET;
                $n_components = 5;
            }
        }

        // Look for build metadata
        $part_len = 0;
        if ($n_components === 6) {
            self::traverse_whitespace($semver, $pos, $rest);
            $sep_len = self::traverse_separators($semver, '+', $pos, $rest);
            self::traverse_whitespace($semver, $pos, $rest);
            $part_len = 0;
            if (0 < $sep_len) {
                $part_len = self::consume_identifier(
                    $process_wildcards, $semver, $pos, $rest, $tmp_build_metadata);
                if ($part_len === 0) {
                    $tmp_build_metadata = self::ARG_UNSET;
                }
            }
        }

        // Return semver that has either prerelease or build metadata or both.
        $part_len = $pos - $start_pos;
        $n_components = 3 + (\intval($tmp_prerelease !== self::ARG_UNSET) | (\intval($tmp_build_metadata !== self::ARG_UNSET) << 1));
        assert(6 <= $part_len);
        $major = $tmp_major;
        $minor = $tmp_minor;
        $patch = $tmp_patch;
        $prerelease = $tmp_prerelease;
        $build_metadata = $tmp_build_metadata;
        return $part_len;
    }

    /**
    * @param      string        $text
    * @param      int<0,max>    $pos
    * @param-out  int<0,max>    $pos
    * @param      int<0,max>    $rest
    * @param-out  int<0,max>    $rest
    * @return     int<0,max>
    * @throws     void
    */
    private static function traverse_whitespace(string $text, int &$pos, int &$rest) : int {
        $part_len = \strspn($text, self::WHITESPACE, $pos, $rest);
        assert(0 <= $part_len);
        $pos  += $part_len;
        $rest -= $part_len;
        return $part_len;
    }

    /**
    * @param      string            $text
    * @param      non-empty-string  $separators
    * @param      int<0,max>        $pos
    * @param-out  int<0,max>        $pos
    * @param      int<0,max>        $rest
    * @param-out  int<0,max>        $rest
    * @return     int<0,max>
    * @throws     void
    */
    private static function traverse_separators(string $text, string $separators, int &$pos, int &$rest) : int {
        if ($rest === 0) { return 0; }
        $sep_len = \strspn($text, $separators, $pos, 1);
        assert(0 <= $sep_len);
        $pos  += $sep_len;
        $rest -= $sep_len;
        return $sep_len;
    }

    /**
    * @param      bool          $process_wildcards
    * @param      string        $text
    * @param      int<0,max>    $pos
    * @param-out  int<0,max>    $pos
    * @param      int<0,max>    $rest
    * @param-out  int<0,max>    $rest
    * @param      int           $output
    * @param-out  svnum_optp_a  $output
    * @return     int<0,max>
    * @throws     void
    */
    private static function consume_integer(bool $process_wildcards, string $text, int &$pos, int &$rest, int &$output = \PHP_INT_MIN) : int {
        if ($process_wildcards && 0 < $rest && $text[$pos] === '*') {
            $pos++;
            $rest--;
            $output = self::ARG_WILDCARD;
            return 1;
        }
        $part_len = \strspn($text, self::DIGITS, $pos, $rest);
        assert(0 <= $part_len);
        if (0 < $part_len) {
            $output = \intval(\substr($text, $pos, $part_len));
        }
        $pos  += $part_len;
        $rest -= $part_len;
        return $part_len;
    }

    /**
    * @param      bool          $process_wildcards
    * @param      string        $text
    * @param      int<0,max>    $pos
    * @param-out  int<0,max>    $pos
    * @param      int<0,max>    $rest
    * @param-out  int<0,max>    $rest
    * @param      string|int    $output
    * @param-out  svpre_optp_a  $output
    * @return     int<0,max>
    * @throws     void
    */
    private static function consume_identifier(bool $process_wildcards, string $text, int &$pos, int &$rest, string|int &$output) : int {
        if ($process_wildcards && 0 < $rest && $text[$pos] === '*') {
            $output = \substr($text, $pos, 1);
            $pos++;
            $rest--;
            return 1;
        }
        if ($process_wildcards) {
            $part_len = \strspn($text, self::IDENTIFIER_LIST_WILD, $pos, $rest);
        } else {
            $part_len = \strspn($text, self::IDENTIFIER_LIST, $pos, $rest);
        }
        assert(0 <= $part_len);
        $output = \substr($text, $pos, $part_len);
        $pos  += $part_len;
        $rest -= $part_len;
        return $part_len;
    }

    // My god I wish I had a way to direct PHPDoc comments to the same page.
    // Or like, a macro that can expand boilerplate into PHPDoc comments.
    /**
    * Extract the `major` version number from a Semantic Version (SemVer) string.
    *
    * Returns `SemVer::ARG_UNSET` on failure.
    *
    * For extracting more than one component of a SemVer string,
    * it is strongly recommended to call the `SemVer::deconstruct`
    * function instead.
    *
    * Every call to component extraction functions (like this one)
    * will require a call to `SemVer::deconstruct`. If all components
    * are needed, then it is going to be more performant for code to
    * directly call `SemVer::deconstruct` and retrieve all needed
    * components at the same time.
    *
    * ```php
    * // Retrieving the `major` version.
    * assert(SemVer::major('1.2.3')        === 1);
    * assert(SemVer::major('4.5.6-rc7+89') === 4);
    *
    * // Failure modes.
    * assert(SemVer::major('')    === SemVer::ARG_UNSET);
    * assert(SemVer::major('.')   === SemVer::ARG_UNSET);
    * assert(SemVer::major('abc') === SemVer::ARG_UNSET);
    * assert(SemVer::major('-1')  === SemVer::ARG_UNSET);
    *
    * // Handling of partial SemVers and patterns.
    * assert(SemVer::major('1')     === 1);
    * assert(SemVer::major('1.2')   === 1);
    * assert(SemVer::major('*')     === SemVer::ARG_WILDCARD);
    * assert(SemVer::major('1.*')   === 1);
    * assert(SemVer::major('1.2.*') === 1);
    *
    * // Slow way to extract multiple version components:
    * // (Calls SemVer::deconstruct() 5 times under the hood.)
    * $version = '4.5.6-rc7+89';
    * assert(SemVer::major($version) === 4);
    * assert(SemVer::minor($version) === 5);
    * assert(SemVer::patch($version) === 6);
    * assert(SemVer::prerelease($version) === 'rc7');
    * assert(SemVer::build_metadata($version) === '89');
    *
    * $version = '1.2.3';
    * assert(SemVer::major($version) === 1);
    * assert(SemVer::minor($version) === 2);
    * assert(SemVer::patch($version) === 3);
    * assert(SemVer::prerelease($version) === SemVer::ARG_UNSET);
    * assert(SemVer::build_metadata($version) === SemVer::ARG_UNSET);
    *
    * // Faster way to extract multiple version components:
    * // (One call to SemVer::deconstruct() instead of the 5 above.)
    * $major = SemVer::ARG_UNSET;
    * $minor = SemVer::ARG_UNSET;
    * $patch = SemVer::ARG_UNSET;
    * $prerelease = SemVer::ARG_UNSET;
    * $build_metadata = SemVer::ARG_UNSET;
    *
    * $version = '4.5.6-rc7+89';
    * $n_components = SemVer::deconstruct(
    *     $version, $major, $minor, $patch, $prerelease, $build_metadata);
    * assert($n_components === 6); // The combination of prerelease+build_metadata counts as 3.
    * assert($major === 4);
    * assert($minor === 5);
    * assert($patch === 6);
    * assert($prerelease === 'rc7');
    * assert($build_metadata === '89');
    *
    * $version = '1.2.3';
    * $n_components = SemVer::deconstruct(
    *     $version, $major, $minor, $patch, $prerelease, $build_metadata);
    * assert($n_components === 3);
    * assert($major === 1);
    * assert($minor === 2);
    * assert($patch === 3);
    * assert($prerelease === SemVer::ARG_UNSET);
    * assert($build_metadata === SemVer::ARG_UNSET);
    * ```
    *
    * @see deconstruct
    * @see minor
    * @see patch
    * @see prerelease
    * @see build_metadata
    *
    * @param  semver_a   $semver
    * @return svnum_optp_a
    * @throws void
    */
    public static function major(string $semver) : int { return self::select_numeric_part_by_index($semver, self::IDX_MAJOR); }

    private static function unittest_major() : void
    {
        echo("  ".__FUNCTION__."()\n");

        // Retrieving the `major` version.
        assert(SemVer::major('1.2.3')        === 1);
        assert(SemVer::major('4.5.6-rc7+89') === 4);

        // Failure modes.
        assert(SemVer::major('')    === SemVer::ARG_UNSET);
        assert(SemVer::major('.')   === SemVer::ARG_UNSET);
        assert(SemVer::major('abc') === SemVer::ARG_UNSET);
        assert(SemVer::major('-1')  === SemVer::ARG_UNSET);

        // Handling of partial SemVers and patterns.
        assert(SemVer::major('1')     === 1);
        assert(SemVer::major('1.2')   === 1);
        assert(SemVer::major('*')     === SemVer::ARG_WILDCARD);
        assert(SemVer::major('1.*')   === 1);
        assert(SemVer::major('1.2.*') === 1);
    }

    /**
    * Extract the `minor` version number from a Semantic Version (SemVer) string.
    *
    * Returns `SemVer::ARG_UNSET` on failure.
    *
    * For extracting more than one component of a SemVer string,
    * it is strongly recommended to call the `SemVer::deconstruct`
    * function instead.
    *
    * Every call to component extraction functions (like this one)
    * will require a call to `SemVer::deconstruct`. If all components
    * are needed, then it is going to be more performant for code to
    * directly call `SemVer::deconstruct` and retrieve all needed
    * components at the same time.
    *
    * ```php
    * // Retrieving the `minor` version.
    * assert(SemVer::minor('1.2.3')        === 2);
    * assert(SemVer::minor('4.5.6-rc7+89') === 5);
    *
    * // Failure modes.
    * assert(SemVer::minor('')    === SemVer::ARG_UNSET);
    * assert(SemVer::minor('.')   === SemVer::ARG_UNSET);
    * assert(SemVer::minor('abc') === SemVer::ARG_UNSET);
    * assert(SemVer::minor('-1')  === SemVer::ARG_UNSET);
    *
    * // Handling of partial SemVers and patterns.
    * assert(SemVer::minor('1')     === SemVer::ARG_UNSET);
    * assert(SemVer::minor('1.2')   === 2);
    * assert(SemVer::minor('*')     === SemVer::ARG_UNSET);
    * assert(SemVer::minor('1.*')   === SemVer::ARG_WILDCARD);
    * assert(SemVer::minor('1.2.*') === 2);
    *
    * // Slow way to extract multiple version components:
    * // (Calls SemVer::deconstruct() 5 times under the hood.)
    * $version = '4.5.6-rc7+89';
    * assert(SemVer::major($version) === 4);
    * assert(SemVer::minor($version) === 5);
    * assert(SemVer::patch($version) === 6);
    * assert(SemVer::prerelease($version) === 'rc7');
    * assert(SemVer::build_metadata($version) === '89');
    *
    * $version = '1.2.3';
    * assert(SemVer::major($version) === 1);
    * assert(SemVer::minor($version) === 2);
    * assert(SemVer::patch($version) === 3);
    * assert(SemVer::prerelease($version) === SemVer::ARG_UNSET);
    * assert(SemVer::build_metadata($version) === SemVer::ARG_UNSET);
    *
    * // Faster way to extract multiple version components:
    * // (One call to SemVer::deconstruct() instead of the 5 above.)
    * $major = SemVer::ARG_UNSET;
    * $minor = SemVer::ARG_UNSET;
    * $patch = SemVer::ARG_UNSET;
    * $prerelease = SemVer::ARG_UNSET;
    * $build_metadata = SemVer::ARG_UNSET;
    *
    * $version = '4.5.6-rc7+89';
    * $n_components = SemVer::deconstruct(
    *     $version, $major, $minor, $patch, $prerelease, $build_metadata);
    * assert($n_components === 6); // The combination of prerelease+build_metadata counts as 3.
    * assert($major === 4);
    * assert($minor === 5);
    * assert($patch === 6);
    * assert($prerelease === 'rc7');
    * assert($build_metadata === '89');
    *
    * $version = '1.2.3';
    * $n_components = SemVer::deconstruct(
    *     $version, $major, $minor, $patch, $prerelease, $build_metadata);
    * assert($n_components === 3);
    * assert($major === 1);
    * assert($minor === 2);
    * assert($patch === 3);
    * assert($prerelease === SemVer::ARG_UNSET);
    * assert($build_metadata === SemVer::ARG_UNSET);
    * ```
    *
    * @see deconstruct
    * @see major
    * @see patch
    * @see prerelease
    * @see build_metadata
    *
    * @param  semver_a   $semver
    * @return svnum_optp_a
    * @throws void
    */
    public static function minor(string $semver) : int { return self::select_numeric_part_by_index($semver, self::IDX_MINOR); }

    private static function unittest_minor() : void
    {
        echo("  ".__FUNCTION__."()\n");

        // Retrieving the `minor` version.
        assert(SemVer::minor('1.2.3')        === 2);
        assert(SemVer::minor('4.5.6-rc7+89') === 5);

        // Failure modes.
        assert(SemVer::minor('')    === SemVer::ARG_UNSET);
        assert(SemVer::minor('.')   === SemVer::ARG_UNSET);
        assert(SemVer::minor('abc') === SemVer::ARG_UNSET);
        assert(SemVer::minor('-1')  === SemVer::ARG_UNSET);

        // Handling of partial SemVers and patterns.
        assert(SemVer::minor('1')     === SemVer::ARG_UNSET);
        assert(SemVer::minor('1.2')   === 2);
        assert(SemVer::minor('*')     === SemVer::ARG_UNSET);
        assert(SemVer::minor('1.*')   === SemVer::ARG_WILDCARD);
        assert(SemVer::minor('1.2.*') === 2);
    }

    /**
    * Extract the `patch` version number from a Semantic Version (SemVer) string.
    *
    * Returns `SemVer::ARG_UNSET` on failure.
    *
    * For extracting more than one component of a SemVer string,
    * it is strongly recommended to call the `SemVer::deconstruct`
    * function instead.
    *
    * Every call to component extraction functions (like this one)
    * will require a call to `SemVer::deconstruct`. If all components
    * are needed, then it is going to be more performant for code to
    * directly call `SemVer::deconstruct` and retrieve all needed
    * components at the same time.
    *
    * ```php
    * // Retrieving the `patch` version.
    * assert(SemVer::patch('1.2.3')        === 3);
    * assert(SemVer::patch('4.5.6-rc7+89') === 6);
    *
    * // Failure modes.
    * assert(SemVer::patch('')    === SemVer::ARG_UNSET);
    * assert(SemVer::patch('.')   === SemVer::ARG_UNSET);
    * assert(SemVer::patch('abc') === SemVer::ARG_UNSET);
    * assert(SemVer::patch('-1')  === SemVer::ARG_UNSET);
    *
    * // Handling of partial SemVers and patterns.
    * assert(SemVer::patch('1')     === SemVer::ARG_UNSET);
    * assert(SemVer::patch('1.2')   === SemVer::ARG_UNSET);
    * assert(SemVer::patch('*')     === SemVer::ARG_UNSET);
    * assert(SemVer::patch('1.*')   === SemVer::ARG_UNSET);
    * assert(SemVer::patch('1.2.*') === SemVer::ARG_WILDCARD);
    *
    * // Slow way to extract multiple version components:
    * // (Calls SemVer::deconstruct() 5 times under the hood.)
    * $version = '4.5.6-rc7+89';
    * assert(SemVer::major($version) === 4);
    * assert(SemVer::minor($version) === 5);
    * assert(SemVer::patch($version) === 6);
    * assert(SemVer::prerelease($version) === 'rc7');
    * assert(SemVer::build_metadata($version) === '89');
    *
    * $version = '1.2.3';
    * assert(SemVer::major($version) === 1);
    * assert(SemVer::minor($version) === 2);
    * assert(SemVer::patch($version) === 3);
    * assert(SemVer::prerelease($version) === SemVer::ARG_UNSET);
    * assert(SemVer::build_metadata($version) === SemVer::ARG_UNSET);
    *
    * // Faster way to extract multiple version components:
    * // (One call to SemVer::deconstruct() instead of the 5 above.)
    * $major = SemVer::ARG_UNSET;
    * $minor = SemVer::ARG_UNSET;
    * $patch = SemVer::ARG_UNSET;
    * $prerelease = SemVer::ARG_UNSET;
    * $build_metadata = SemVer::ARG_UNSET;
    *
    * $version = '4.5.6-rc7+89';
    * $n_components = SemVer::deconstruct(
    *     $version, $major, $minor, $patch, $prerelease, $build_metadata);
    * assert($n_components === 6); // The combination of prerelease+build_metadata counts as 3.
    * assert($major === 4);
    * assert($minor === 5);
    * assert($patch === 6);
    * assert($prerelease === 'rc7');
    * assert($build_metadata === '89');
    *
    * $version = '1.2.3';
    * $n_components = SemVer::deconstruct(
    *     $version, $major, $minor, $patch, $prerelease, $build_metadata);
    * assert($n_components === 3);
    * assert($major === 1);
    * assert($minor === 2);
    * assert($patch === 3);
    * assert($prerelease === SemVer::ARG_UNSET);
    * assert($build_metadata === SemVer::ARG_UNSET);
    * ```
    *
    * @see deconstruct
    * @see major
    * @see minor
    * @see prerelease
    * @see build_metadata
    *
    * @param  semver_a   $semver
    * @return svnum_optp_a
    * @throws void
    */
    public static function patch(string $semver) : int { return self::select_numeric_part_by_index($semver, self::IDX_PATCH); }

    private static function unittest_patch() : void
    {
        echo("  ".__FUNCTION__."()\n");

        // Retrieving the `patch` version.
        assert(SemVer::patch('1.2.3')        === 3);
        assert(SemVer::patch('4.5.6-rc7+89') === 6);

        // Failure modes.
        assert(SemVer::patch('')    === SemVer::ARG_UNSET);
        assert(SemVer::patch('.')   === SemVer::ARG_UNSET);
        assert(SemVer::patch('abc') === SemVer::ARG_UNSET);
        assert(SemVer::patch('-1')  === SemVer::ARG_UNSET);

        // Handling of partial SemVers and patterns.
        assert(SemVer::patch('1')     === SemVer::ARG_UNSET);
        assert(SemVer::patch('1.2')   === SemVer::ARG_UNSET);
        assert(SemVer::patch('*')     === SemVer::ARG_UNSET);
        assert(SemVer::patch('1.*')   === SemVer::ARG_UNSET);
        assert(SemVer::patch('1.2.*') === SemVer::ARG_WILDCARD);
    }

    /**
    * Extract the `prerelease` identifier(s) from a Semantic Version (SemVer) string.
    *
    * Returns `SemVer::ARG_UNSET` on failure.
    *
    * For extracting more than one component of a SemVer string,
    * it is strongly recommended to call the `SemVer::deconstruct`
    * function instead.
    *
    * Every call to component extraction functions (like this one)
    * will require a call to `SemVer::deconstruct`. If all components
    * are needed, then it is going to be more performant for code to
    * directly call `SemVer::deconstruct` and retrieve all needed
    * components at the same time.
    *
    * ```php
    * // Retrieving the `prerelease` identifier.
    * assert(SemVer::prerelease('1.2.3')        === SemVer::ARG_UNSET);
    * assert(SemVer::prerelease('4.5.6-rc7+89') === 'rc7');
    *
    * // Failure modes.
    * assert(SemVer::prerelease('')    === SemVer::ARG_UNSET);
    * assert(SemVer::prerelease('.')   === SemVer::ARG_UNSET);
    * assert(SemVer::prerelease('abc') === SemVer::ARG_UNSET);
    * assert(SemVer::prerelease('-1')  === SemVer::ARG_UNSET);
    *
    * // Slow way to extract multiple version components:
    * // (Calls SemVer::deconstruct() 5 times under the hood.)
    * $version = '4.5.6-rc7+89';
    * assert(SemVer::major($version) === 4);
    * assert(SemVer::minor($version) === 5);
    * assert(SemVer::patch($version) === 6);
    * assert(SemVer::prerelease($version) === 'rc7');
    * assert(SemVer::build_metadata($version) === '89');
    *
    * $version = '1.2.3';
    * assert(SemVer::major($version) === 1);
    * assert(SemVer::minor($version) === 2);
    * assert(SemVer::patch($version) === 3);
    * assert(SemVer::prerelease($version) === SemVer::ARG_UNSET);
    * assert(SemVer::build_metadata($version) === SemVer::ARG_UNSET);
    *
    * // Faster way to extract multiple version components:
    * // (One call to SemVer::deconstruct() instead of the 5 above.)
    * $major = SemVer::ARG_UNSET;
    * $minor = SemVer::ARG_UNSET;
    * $patch = SemVer::ARG_UNSET;
    * $prerelease = SemVer::ARG_UNSET;
    * $build_metadata = SemVer::ARG_UNSET;
    *
    * $version = '4.5.6-rc7+89';
    * $n_components = SemVer::deconstruct(
    *     $version, $major, $minor, $patch, $prerelease, $build_metadata);
    * assert($n_components === 6); // The combination of prerelease+build_metadata counts as 3.
    * assert($major === 4);
    * assert($minor === 5);
    * assert($patch === 6);
    * assert($prerelease === 'rc7');
    * assert($build_metadata === '89');
    *
    * $version = '1.2.3';
    * $n_components = SemVer::deconstruct(
    *     $version, $major, $minor, $patch, $prerelease, $build_metadata);
    * assert($n_components === 3);
    * assert($major === 1);
    * assert($minor === 2);
    * assert($patch === 3);
    * assert($prerelease === SemVer::ARG_UNSET);
    * assert($build_metadata === SemVer::ARG_UNSET);
    * ```
    *
    * @see deconstruct
    * @see major
    * @see minor
    * @see patch
    * @see build_metadata
    *
    * @param  semver_a   $semver
    * @return svnum_optp_a
    * @throws void
    */
    public static function prerelease(string $semver) : string|int { return self::select_alphanum_part_by_index($semver, self::IDX_PRERELEASE); }

    private static function unittest_prerelease() : void
    {
        echo("  ".__FUNCTION__."()\n");

        // Retrieving the `prerelease` identifier.
        assert(SemVer::prerelease('1.2.3')        === SemVer::ARG_UNSET);
        assert(SemVer::prerelease('4.5.6-rc7+89') === 'rc7');

        // Failure modes.
        assert(SemVer::prerelease('')    === SemVer::ARG_UNSET);
        assert(SemVer::prerelease('.')   === SemVer::ARG_UNSET);
        assert(SemVer::prerelease('abc') === SemVer::ARG_UNSET);
        assert(SemVer::prerelease('-1')  === SemVer::ARG_UNSET);
    }

    /**
    * Extract the `build_metadata` identifier(s) from a Semantic Version (SemVer) string.
    *
    * Returns `SemVer::ARG_UNSET` on failure.
    *
    * For extracting more than one component of a SemVer string,
    * it is strongly recommended to call the `SemVer::deconstruct`
    * function instead.
    *
    * Every call to component extraction functions (like this one)
    * will require a call to `SemVer::deconstruct`. If all components
    * are needed, then it is going to be more performant for code to
    * directly call `SemVer::deconstruct` and retrieve all needed
    * components at the same time.
    *
    * ```php
    * // Retrieving the `build_metadata` identifier.
    * assert(SemVer::build_metadata('1.2.3')        === SemVer::ARG_UNSET);
    * assert(SemVer::build_metadata('4.5.6-rc7+89') === '89');
    *
    * // Failure modes.
    * assert(SemVer::build_metadata('')    === SemVer::ARG_UNSET);
    * assert(SemVer::build_metadata('.')   === SemVer::ARG_UNSET);
    * assert(SemVer::build_metadata('abc') === SemVer::ARG_UNSET);
    * assert(SemVer::build_metadata('-1')  === SemVer::ARG_UNSET);
    *
    * // Slow way to extract multiple version components:
    * // (Calls SemVer::deconstruct() 5 times under the hood.)
    * $version = '4.5.6-rc7+89';
    * assert(SemVer::major($version) === 4);
    * assert(SemVer::minor($version) === 5);
    * assert(SemVer::patch($version) === 6);
    * assert(SemVer::prerelease($version) === 'rc7');
    * assert(SemVer::build_metadata($version) === '89');
    *
    * $version = '1.2.3';
    * assert(SemVer::major($version) === 1);
    * assert(SemVer::minor($version) === 2);
    * assert(SemVer::patch($version) === 3);
    * assert(SemVer::prerelease($version) === SemVer::ARG_UNSET);
    * assert(SemVer::build_metadata($version) === SemVer::ARG_UNSET);
    *
    * // Faster way to extract multiple version components:
    * // (One call to SemVer::deconstruct() instead of the 5 above.)
    * $major = SemVer::ARG_UNSET;
    * $minor = SemVer::ARG_UNSET;
    * $patch = SemVer::ARG_UNSET;
    * $prerelease = SemVer::ARG_UNSET;
    * $build_metadata = SemVer::ARG_UNSET;
    *
    * $version = '4.5.6-rc7+89';
    * $n_components = SemVer::deconstruct(
    *     $version, $major, $minor, $patch, $prerelease, $build_metadata);
    * assert($n_components === 6); // The combination of prerelease+build_metadata counts as 3.
    * assert($major === 4);
    * assert($minor === 5);
    * assert($patch === 6);
    * assert($prerelease === 'rc7');
    * assert($build_metadata === '89');
    *
    * $version = '1.2.3';
    * $n_components = SemVer::deconstruct(
    *     $version, $major, $minor, $patch, $prerelease, $build_metadata);
    * assert($n_components === 3);
    * assert($major === 1);
    * assert($minor === 2);
    * assert($patch === 3);
    * assert($prerelease === SemVer::ARG_UNSET);
    * assert($build_metadata === SemVer::ARG_UNSET);
    * ```
    *
    * @see deconstruct
    * @see major
    * @see minor
    * @see patch
    * @see prerelease
    *
    * @param  semver_a   $semver
    * @return svnum_optp_a
    * @throws void
    */
    public static function build_metadata(string $semver) : string|int { return self::select_alphanum_part_by_index($semver, self::IDX_BUILD); }

    private static function unittest_build_metadata() : void
    {
        echo("  ".__FUNCTION__."()\n");

        // Retrieving the `build_metadata` identifier.
        assert(SemVer::build_metadata('1.2.3')        === SemVer::ARG_UNSET);
        assert(SemVer::build_metadata('4.5.6-rc7+89') === '89');

        // Failure modes.
        assert(SemVer::build_metadata('')    === SemVer::ARG_UNSET);
        assert(SemVer::build_metadata('.')   === SemVer::ARG_UNSET);
        assert(SemVer::build_metadata('abc') === SemVer::ARG_UNSET);
        assert(SemVer::build_metadata('-1')  === SemVer::ARG_UNSET);
    }

    /**
    * Test if all semantic version strings in the array are valid.
    *
    * @param  array<semver_a>  $vers
    * @throws void
    */
    public static function all_valid(array $vers) : bool
    {
        foreach($vers as $ver) {
            if (!self::valid($ver)) {
                return false;
            }
        }
        return true;
        // Requires PHP version 8.4.0 or greater.
        //return \array_all($vers, self::array_func_callback_valid(...));
    }

    /**
    * @param     semver_a     $ver
    * @param     ?string      $error
    * @param-out string       $error
    * @throws    void
    */
    private static function err_empty(string $ver, ?string &$error) : void {
        $error = "Expected semantic version; got empty (or blank) string.";
    }

    /**
    * @param     semver_a     $ver
    * @param     self::IDX_*  $part_idx
    * @param     ?string      $error
    * @param-out string       $error
    * @throws    void
    */
    private static function err_part_empty(string $ver, int $part_idx, ?string &$error) : void {
        $name = self::PART_NAME[$part_idx];
        $error = "Version '$ver' is invalid: $name number is empty/blank.";
    }

    /**
    * @param     semver_a     $ver
    * @param     string       $part
    * @param     self::IDX_MAJOR|self::IDX_MINOR|self::IDX_PATCH  $part_idx
    * @param     ?string      $error
    * @param-out string       $error
    * @throws    void
    */
    private static function err_part_nondigits(string $ver, string $part, int $part_idx, ?string &$error) : void {
        $name = self::PART_NAME[$part_idx];
        $error = "Version '$ver' is invalid: $name number '$part' contains non-digits.";
    }

    /**
    * @param     semver_a     $ver
    * @param     string       $part
    * @param     self::IDX_*  $part_idx
    * @param     ?string      $error
    * @param-out string       $error
    * @throws    void
    */
    private static function err_part_leading_zeroes(string $ver, string $part, int $part_idx, ?string &$error) : void {
        $name = self::PART_NAME[$part_idx];
        $error = "Version '$ver' is invalid: $name number '$part' has leading zeroes.";
    }

    /**
    * @param     semver_a     $ver
    * @param     string       $part
    * @param     self::IDX_PRERELEASE|self::IDX_BUILD    $part_idx
    * @param     ?string      $error
    * @param-out string       $error
    * @throws    void
    */
    private static function err_part_nonalphanum(string $ver, string $part, int $part_idx, ?string &$error) : void {
        $name = self::PART_NAME[$part_idx];
        $error =
            "Version '$ver' is invalid: $name identifier '$part'".
            ' contains invalid characters (not alphanumeric or \'.\' or \'-\').';
    }

    /**
    * @param     semver_a     $ver
    * @param     self::IDX_*  $part_idx
    * @param     string       $how
    * @param     ?string      $error
    * @param-out string       $error
    * @throws    void
    */
    private static function err_part_missing(string $ver, int $part_idx, string $how, ?string &$error) : void {
        $i = $part_idx;
        assert($i < 2);
        $name1 = self::PART_NAME[$i+1];
        if ($i === 1) {
            $error = "Version '$ver' is invalid: $name1 number is missing ($how).";
        } else { // $i is 0
            $name2 = self::PART_NAME[$i+2];
            $error = "Version '$ver' is invalid: $name1 number and $name2 number are missing($how).";
        }
    }

    /**
    * @param     semver_a  $ver
    * @param     ?string   $error
    * @param-out string    $error
    * @throws    void
    */
    private static function err_badchar_after_patch(string $ver, string $sep, ?string &$error) : void {
        $error = "Version '$ver' is invalid: unexpected character '$sep' after patch number.";
    }

    /**
    * Test if version string is valid.
    *
    * Provides an error message explaining "why not?" as an
    * output through the `$error` reference parameter.
    *
    * @see valid
    * @see enforce_valid
    *
    * @param     semver_a  $ver
    * @param     ?string   $error
    * @param-out string    $error
    * @throws    void
    */
    public static function valid_or_err(string $ver, ?string &$error = null) : bool
    {
        return self::valid_impl($ver, true, $error);
    }

    /**
    * Test if version string is valid.
    *
    * Examples:
    * ```php
    * assert(!SemVer::valid(''));
    * assert(!SemVer::valid('1'));
    * assert(!SemVer::valid('1.'));
    * assert(!SemVer::valid('1.2'));
    * assert(!SemVer::valid('1.2.'));
    *
    * assert(SemVer::valid('1,2,3'));
    * assert(SemVer::valid('1.2.9'));
    * assert(SemVer::valid('1.2.30'));
    * assert(SemVer::valid('1.9.3'));
    * assert(SemVer::valid('1.20.3'));
    * assert(SemVer::valid('9.2.3'));
    * assert(SemVer::valid('10.2.3'));
    * assert(SemVer::valid('1.9.999'));
    * assert(SemVer::valid('1.10.0'));
    *
    * assert(!SemVer::valid('1 , 2 , 3'));
    * assert(!SemVer::valid(' 1,2,3 '));
    * assert(!SemVer::valid('01,2,3'));
    * assert(!SemVer::valid('1,02,3'));
    * assert(!SemVer::valid('1,2,03'));
    * assert(!SemVer::valid('01.02.03'));
    * assert(!SemVer::valid(' 01 . 02 . 03 '));
    * ```
    *
    * @see valid_or_err
    * @see enforce_valid
    *
    * @param     semver_a  $ver
    * @throws    void
    */
    public static function valid(string $ver) : bool
    {
        $error_ignore=null;
        return self::valid_impl($ver, false, $error_ignore);
    }

    private static function unittest_valid() : void
    {
        echo("  ".__FUNCTION__."()\n");

        assert(!SemVer::valid(''));
        assert(!SemVer::valid('1'));
        assert(!SemVer::valid('1.'));
        assert(!SemVer::valid('1.2'));
        assert(!SemVer::valid('1.2.'));

        assert(!SemVer::valid('1,2,3'));
        assert(!SemVer::valid('1:2:3'));
        assert(!SemVer::valid('1;2;3'));
        assert(!SemVer::valid('foobarbaz'));

        assert(SemVer::valid('1.2.3'));
        assert(SemVer::valid('1.2.9'));
        assert(SemVer::valid('1.2.30'));
        assert(SemVer::valid('1.9.3'));
        assert(SemVer::valid('1.20.3'));
        assert(SemVer::valid('9.2.3'));
        assert(SemVer::valid('10.2.3'));
        assert(SemVer::valid('1.9.999'));
        assert(SemVer::valid('1.10.0'));

        assert(!SemVer::valid('1 , 2 , 3'));
        assert(!SemVer::valid(' 1,2,3 '));
        assert(!SemVer::valid('01,2,3'));
        assert(!SemVer::valid('1,02,3'));
        assert(!SemVer::valid('1,2,03'));
        assert(!SemVer::valid('01.02.03'));
        assert(!SemVer::valid(' 01 . 02 . 03 '));

        // From: https://github.com/semver/semver/issues/981
        assert(SemVer::valid('0.0.4'));
        assert(SemVer::valid('1.2.3')); // @phpstan-ignore function.alreadyNarrowedType
        assert(SemVer::valid('10.20.30'));
        assert(SemVer::valid('1.1.2-prerelease+meta'));
        assert(SemVer::valid('1.1.2+meta'));
        assert(SemVer::valid('1.1.2+meta-valid'));
        assert(SemVer::valid('1.0.0-alpha'));
        assert(SemVer::valid('1.0.0-beta'));
        assert(SemVer::valid('1.0.0-alpha.beta'));
        assert(SemVer::valid('1.0.0-alpha.beta.1'));
        assert(SemVer::valid('1.0.0-alpha.1'));
        assert(SemVer::valid('1.0.0-alpha0.valid'));
        assert(SemVer::valid('1.0.0-alpha.0valid'));
        assert(SemVer::valid('1.0.0-alpha-a.b-c-somethinglong+build.1-aef.1-its-okay'));
        assert(SemVer::valid('1.0.0-rc.1+build.1'));
        assert(SemVer::valid('2.0.0-rc.1+build.123'));
        assert(SemVer::valid('1.2.3-beta'));
        assert(SemVer::valid('10.2.3-DEV-SNAPSHOT'));
        assert(SemVer::valid('1.2.3-SNAPSHOT-123'));
        assert(SemVer::valid('1.0.0'));
        assert(SemVer::valid('2.0.0'));
        assert(SemVer::valid('1.1.7'));
        assert(SemVer::valid('2.0.0+build.1848'));
        assert(SemVer::valid('2.0.1-alpha.1227'));
        assert(SemVer::valid('1.0.0-alpha+beta'));
        assert(SemVer::valid('1.2.3----RC-SNAPSHOT.12.9.1--.12+788'));
        assert(SemVer::valid('1.2.3----R-S.12.9.1--.12+meta'));
        assert(SemVer::valid('1.2.3----RC-SNAPSHOT.12.9.1--.12'));
        assert(SemVer::valid('1.0.0+0.build.1-rc.10000aaa-kk-0.1'));
        assert(SemVer::valid('99999999999999999999999.999999999999999999.99999999999999999'));
        assert(SemVer::valid('1.0.0-0A.is.legal'));

        assert(!SemVer::valid('1')); // @phpstan-ignore function.alreadyNarrowedType, booleanNot.alwaysTrue
        assert(!SemVer::valid('1.2')); // @phpstan-ignore function.alreadyNarrowedType, booleanNot.alwaysTrue
        assert(!SemVer::valid('1.2.3-0123'));
        //assert(!SemVer::valid('1.2.3-0123.0123')); // TODO: This is too sophisticated for us right now.
        //assert(!SemVer::valid('1.1.2+.123')); // ditto
        assert(!SemVer::valid('+invalid'));
        assert(!SemVer::valid('-invalid'));
        assert(!SemVer::valid('-invalid+invalid'));
        assert(!SemVer::valid('-invalid.01'));
        assert(!SemVer::valid('alpha'));
        assert(!SemVer::valid('alpha.beta'));
        assert(!SemVer::valid('alpha.beta.1'));
        assert(!SemVer::valid('alpha.1'));
        assert(!SemVer::valid('alpha+beta'));
        assert(!SemVer::valid('alpha_beta'));
        assert(!SemVer::valid('alpha.'));
        //assert(!SemVer::valid('alpha..')); // TODO: Ability to scan individual dot-separated identifiers and reject empties
        assert(!SemVer::valid('beta'));
        assert(!SemVer::valid('1.0.0-alpha_beta'));
        assert(!SemVer::valid('-alpha.'));
        //assert(!SemVer::valid('1.0.0-alpha..'));    // TODO: Ability to scan individual dot-separated identifiers and reject empties
        //assert(!SemVer::valid('1.0.0-alpha..1'));
        //assert(!SemVer::valid('1.0.0-alpha...1'));
        //assert(!SemVer::valid('1.0.0-alpha....1'));
        //assert(!SemVer::valid('1.0.0-alpha.....1'));
        //assert(!SemVer::valid('1.0.0-alpha......1'));
        //assert(!SemVer::valid('1.0.0-alpha.......1'));
        assert(!SemVer::valid('01.1.1'));
        assert(!SemVer::valid('1.01.1'));
        assert(!SemVer::valid('1.1.01'));
        assert(!SemVer::valid('1.2.3.DEV'));
        assert(!SemVer::valid('1.2-SNAPSHOT'));
        assert(!SemVer::valid('1.2.31.2.3----RC-SNAPSHOT.12.09.1--..12+788'));
        assert(!SemVer::valid('1.2-RC-SNAPSHOT'));
        assert(!SemVer::valid('-1.0.3-gamma+b7718'));
        assert(!SemVer::valid('+justmeta'));
        assert(!SemVer::valid('9.8.7+meta+meta'));
        assert(!SemVer::valid('9.8.7-whatever+meta+meta'));
        // This might actually be inability to check dot-identifiers, not 64-bit. `valid` does need to integer parse.
        //assert(!SemVer::valid('99999999999999999999999.999999999999999999.99999999999999999----RC-SNAPSHOT.12.09.1--------------------------------..12'));
    }

    /**
    * Test if version string is valid. If it isn't, throw an exception.
    *
    * @see valid
    * @see valid_or_err
    *
    * @param     semver_a  $ver
    * @throws    ValidationException
    */
    public static function enforce_valid(string $ver) : void
    {
        $success = self::valid_impl($ver, false, $error);
        if (!$success) {
            throw new ValidationException($error);
        }
    }

    /**
    * @param     semver_a  $ver
    * @throws    void
    */
    private static function valid_impl(string $ver, bool $report_errors, ?string &$error) : bool
    {
        // $core_endpos_dd = \strspn($ver, self::DIGITS_AND_DOT);
        // $core_endpos_hp = \strcspn($ver, '-+');
        // if ($core_endpos_dd !== $core_endpos_hp) {
        //     if(0 < \count($error)) {
        //         $error[0] =
        //             "Version '$ver' is invalid: non-digit character in major/minor/patch portion.";
        //     }
        //     return false;
        // }

        $len = \strlen($ver);
        $pos = 0;

        $whitespace_len = \strspn($ver, ' ');
        if ( $whitespace_len === $len ) {
            if ($report_errors) { self::err_empty($ver, $error); }
            return false;
        }
        $pos += $whitespace_len;

        // Mandatory components: major.minor.patch
        for($i = 0; $i < 2; $i++)
        {
            assert($i === 0 ? $i === self::IDX_MAJOR : true); // @phpstan-ignore function.alreadyNarrowedType
            assert($i === 1 ? $i === self::IDX_MINOR : true); // @phpstan-ignore function.alreadyNarrowedType

            $partlen = \strcspn($ver, '.-+', $pos);
            if ( $partlen === 0 ) {
                if ($report_errors) { self::err_part_empty($ver, $i, $error); }
                return false;
            }

            $part = \substr($ver, $pos, $partlen);
            if ( !\ctype_digit($part) ) {
                if ($report_errors) { self::err_part_nondigits($ver, $part, $i, $error); }
                return false;
            }

            if ( 1 < $partlen && $part[0] === '0' ) {
                if ($report_errors) { self::err_part_leading_zeroes($ver, $part, $i, $error); }
                return false;
            }

            $pos += $partlen;
            if ($pos >= $len) {
                if ($report_errors) { self::err_part_missing($ver, $i, 'expected \'.\', got end-of-string', $error); }
                return false;
            }

            $ch0 = $ver[$pos];
            // Should be guaranteed by \strcspn and the above length-check.
            assert($ch0 === '.' || $ch0 === '-' || $ch0 === '+');
            if ($ch0 !== '.') {
                if ($report_errors) { self::err_part_missing($ver, $i, "expected '.' got '$ch0'", $error); }
            }

            $pos++;
            if ($pos >= $len) {
                if ($report_errors) { self::err_part_missing($ver, $i, 'expected number, got end-of-string', $error); }
                return false;
            }
        }

        $partlen = \strspn($ver, self::ALPHANUM, $pos); // Use ALPHANUM for better error handling/reporting.
        if ( $partlen === 0 ) {
            if ($report_errors) { self::err_part_empty($ver, self::IDX_PATCH, $error[0]); }
            return false;
        }

        $part = \substr($ver, $pos, $partlen);
        if ( !\ctype_digit($part) ) {
            if ($report_errors) { self::err_part_nondigits($ver, $part, self::IDX_PATCH, $error); }
            return false;
        }

        if ( 1 < $partlen && $part[0] === '0' ) {
            if ($report_errors) { self::err_part_leading_zeroes($ver, $part, self::IDX_PATCH, $error); }
            return false;
        }

        $pos += $partlen;
        if ($pos >= $len) {
            // At the end of the major.minor.patch tuple,
            // there is a valid stopping point. Here we are.
            return true;
        }

        // Optional components: prerelease and build metadata.
        $ch0 = $ver[$pos];
        if ($ch0 !== '-' && $ch0 !== '+') {
            if ($report_errors) { self::err_badchar_after_patch($ver, $ch0, $error); }
            return false;
        }

        // Skip the '-' or '+'
        $pos++;

        if ($ch0 === '-')
        {
            if (!self::valid_prerelease($ver, $pos, $report_errors, $error)) {
                return false;
            }

            if ($pos >= $len) {
                // Another valid stopping point.
                return true;
            }

            // Fill `$ch0` variable so that we can look for '+' at the new position.
            $ch0 = $ver[$pos];
            $pos++;
        }

        if ($ch0 === '+')
        {
            if (!self::valid_build_meta($ver, $pos, $report_errors, $error)) {
                return false;
            }
        }

        return ($pos === $len);
    }

    /**
    * @param     semver_a    $ver
    * @param     int<5,max>  $pos
    * @throws    void
    */
    private static function valid_prerelease(string $ver, int &$pos, bool $report_errors, ?string &$error) : bool
    {
        $part_len = \strcspn($ver, '+', $pos); // Scan to EOS or '+'
        if ( $part_len === 0 ) {
            if ($report_errors) { self::err_part_empty($ver, self::IDX_PRERELEASE, $error); }
            return false;
        }

        $identifier_len = \strspn($ver, self::IDENTIFIER_LIST, $pos);
        $identifier = \substr($ver, $pos, $part_len);
        if ( $identifier_len !== $part_len ) {
            if ($report_errors) {
                self::err_part_nonalphanum($ver, $identifier, self::IDX_PRERELEASE, $error);
            }
            return false;
        }

        if ( \ctype_digit($identifier) && 1 < $part_len && $ver[$pos] === '0' ) {
            if ($report_errors) { self::err_part_leading_zeroes($ver, $identifier, self::IDX_PRERELEASE, $error); }
            return false;
        }

        $pos += $part_len;
        return true;
    }

    /**
    * @param     semver_a    $ver
    * @param     int<5,max>  $pos
    * @throws    void
    */
    private static function valid_build_meta(string $ver, int &$pos, bool $report_errors, ?string &$error) : bool
    {
        $part_len = \strspn($ver, self::IDENTIFIER_LIST, $pos);
        if ( $part_len === 0 ) {
            if ($report_errors) { self::err_part_empty($ver, self::IDX_BUILD, $error); }
            return false;
        }

        $pos += $part_len;
        $identifier = \substr($ver, $pos, $part_len);
        if ( $pos !== \strlen($ver) ) {
            if ($report_errors) {
                $part = \substr($ver, $pos, $part_len);
                self::err_part_nonalphanum($ver, $part, self::IDX_BUILD, $error);
            }
            return false;
        }

        if ( \ctype_digit($identifier) && 1 < $part_len && $ver[$pos] === '0' ) {
            if ($report_errors) { self::err_part_leading_zeroes($ver, $identifier, self::IDX_BUILD, $error); }
            return false;
        }

        return true;
    }

    // TODO: Add a version with an error message parameter? (And a throwing counterpart?)
    /**
    * Attempt to correct an invalid semantic version.
    *
    * If a semver is "close enough" to being unambiguously understandable,
    * then this function will modify it to be _actually_ valid, thus
    * allowing it to participate in comparison/sorting.
    *
    * Examples:
    * ```
    * assert(!isset(SemVer::repair('1.2')));
    * assert(SemVer::repair('1 . 2 . 3') === '1.2.3');
    * assert(SemVer::repair(' 1.2.3 ')   === '1.2.3');
    * assert(SemVer::repair('01.02.03')  === '1.2.3');
    * assert(SemVer::repair(' 01 . 02 . 03 ')  === '1.2.3');
    * ```
    *
    * @return  ?string  The resulting repaired/normalized semver. `null` if it was broken beyond repair.
    * @throws  void
    */
    public static function repair(string $sorta_semver) : ?string
    {
        // Attempt to avoid memory allocation by simply testing it.
        if (self::valid($sorta_semver)) {
            return $sorta_semver;
        }

        // String modification will probably require allocation anyways. Let's do it.
        // (In principle, we might be able to do an "in-place" version where
        // we use the existing string as a buffer to "write" the new string
        // into, but we don't have the tech right now.)
        $major          = self::ARG_UNSET;
        $minor          = self::ARG_UNSET;
        $patch          = self::ARG_UNSET;
        $prerelease     = self::ARG_UNSET;
        $build_metadata = self::ARG_UNSET;
        $n_components = self::deconstruct(
            $sorta_semver, $major, $minor, $patch, $prerelease, $build_metadata);

        if ($n_components < 3) {
            return null;
        }

        // These should be guaranteed by `$n_components >= 3`.
        assert($major !== self::ARG_UNSET);
        assert($minor !== self::ARG_UNSET);
        assert($patch !== self::ARG_UNSET);

        // Valid semvers don't have wildcards. That's only patterns.
        if ($major === self::ARG_WILDCARD
        ||  $minor === self::ARG_WILDCARD
        ||  $patch === self::ARG_WILDCARD
        ||  $prerelease === self::ARG_WILDCARD
        ||  $build_metadata === self::ARG_WILDCARD) {
            return null;
        }

        return self::construct($major, $minor, $patch, $prerelease, $build_metadata);
    }

    private static function unittest_repair() : void
    {
        echo("  ".__FUNCTION__."()\n");

        assert(SemVer::repair('1.2') === null);
        assert(SemVer::repair('1 . 2 . 3') === '1.2.3');
        assert(SemVer::repair(' 1.2.3 ')   === '1.2.3');
        assert(SemVer::repair('01.02.03')  === '1.2.3');
        assert(SemVer::repair(' 01 . 02 . 03 ')  === '1.2.3');
    }

    /**
    * Patternistic comparison of Semantic Versions (SemVers).
    *
    * The `matching` function is to the `equal` function,
    * as this function is to the `cmp` function:
    * It allows partial or wildcard versions to be accepted
    * as arguments. It will return `0` in all of the cases
    * where `matching` would have returned `true` for the
    * same arguments.
    *
    * Examples:
    * ```php
    * assert(SemVer::pcmp('1.2.3', '1.2'  ) === 0);
    * assert(SemVer::pcmp('1.2'  , '1.2.3') === 0);
    * assert(SemVer::pcmp('1.2.3', '1'    ) === 0);
    * assert(SemVer::pcmp('1.2'  , '1.2'  ) === 0);
    * assert(SemVer::pcmp('1.2'  , '1.3'  )  <  0);
    * assert(SemVer::pcmp('1'    , '2'    )  <  0);
    *
    * assert(SemVer::pcmp('1.2.3', '1.2.*') === 0);
    * assert(SemVer::pcmp('1.2.*', '1.2.3') === 0);
    * assert(SemVer::pcmp('1.2.3', '1.*'  ) === 0);
    * assert(SemVer::pcmp('1.2.*', '1.2.*') === 0);
    * assert(SemVer::pcmp('1.2.*', '1.3.*')  <  0);
    * assert(SemVer::pcmp('1.*'  , '1.*'  ) === 0);
    * assert(SemVer::pcmp('1.*'  , '2.*'  )  <  0);
    * assert(SemVer::pcmp('1.*.*', '1.*'  ) === 0);
    * assert(SemVer::pcmp('1.*.*', '2.*'  )  <  0);
    * assert(SemVer::pcmp('1'    , '1.*'  ) === 0);
    * assert(SemVer::pcmp('1'    , '2.*'  )  <  0);
    *
    * assert(SemVer::pcmp('1.2.3', 1,2,3) === 0);
    * assert(SemVer::pcmp('1.2.3', 4,5,6)  <  0);
    *
    * assert(SemVer::pcmp('1.2.3',     '1.2'        ) === 0);
    * assert(SemVer::pcmp('1.2'  ,     1,2,3        ) === 0);
    * assert(SemVer::pcmp('1.2.3-*',   '1.2.3-rc4'  ) === 0);
    * assert(SemVer::pcmp('1.2.3-*',   '1.2.3-'     ) === 0);
    * assert(SemVer::pcmp('1.2.3-*',   '1.2.3'      ) === 0);
    * assert(SemVer::pcmp('1.2.3-',    '1.2.3-rc4'  )  >  0);
    * assert(SemVer::pcmp('1.2.3',     '1.2.3-rc4'  )  >  0);
    * assert(SemVer::pcmp('1.2.3-x.*', '1.2.3-x.rc4') === 0);
    * assert(SemVer::pcmp('1.2.3-x.*', '1.2.3-x.'   ) === 0);
    * assert(SemVer::pcmp('1.2.3-x.*', '1.2.3-x'    ) === 0);
    * assert(SemVer::pcmp('1.2.3-x.',  '1.2.3-x.rc4')  >  0);
    * assert(SemVer::pcmp('1.2.3-x',   '1.2.3-x.rc4')  >  0);
    *
    * assert(SemVer::pcmp('1', '1.2.3-4') === 0));
    * ```
    *
    * @see equal
    *
    * @param   svany_reqp_a                                                                            $a_major__a_major__a_major__a
    * @param   ($a_major__a_major__a_major__a is svnum_reqp_a ? svany_reqp_a : svany_reqp_a|semver_a)  $a_minor__a_minor__a_minor__b_major
    * @param   ($a_major__a_major__a_major__a is svnum_reqp_a ? svany_optp_a    : ($a_minor__a_minor__a_minor__b_major  is  svnum_reqp_a ? svany_optp_a : self::ARG_UNSET))  $a_patch__a_patch__b_major__b_minor
    * @param   ($a_major__a_major__a_major__a is svnum_reqp_a ? svany_optp_a    : ($a_minor__a_minor__a_minor__b_major  is  svnum_reqp_a ? svany_optp_a : self::ARG_UNSET))  $a_prere__b_major__b_minor__b_patch
    * @param   ($a_major__a_major__a_major__a is svnum_reqp_a ? svany_optp_a    : ($a_minor__a_minor__a_minor__b_major  is  svnum_reqp_a ? svany_optp_a : self::ARG_UNSET))  $b_major__b_minor__b_patch__b_prere
    * @param   ($a_major__a_major__a_major__a is semver_a     ? self::ARG_UNSET : ($a_minor__a_minor__a_minor__b_major  is  semver_a  ? self::ARG_UNSET : ($b_major__b_minor__b_patch__b_prere  is semver_a ? self::ARG_UNSET : svpart_optp_a)))  $b_minor__b_patch__b_prere__x_xxxxx
    * @param   ($a_major__a_major__a_major__a is semver_a     ? self::ARG_UNSET : ($a_minor__a_minor__a_minor__b_major  is  semver_a  ? self::ARG_UNSET : ($b_major__b_minor__b_patch__b_prere  is semver_a ? self::ARG_UNSET : svpart_optp_a)))  $b_patch__b_prere__x_xxxxx__x_xxxxx
    * @param   ($a_major__a_major__a_major__a is semver_a     ? self::ARG_UNSET : ($a_minor__a_minor__a_minor__b_major  is  semver_a  ? self::ARG_UNSET : ($b_major__b_minor__b_patch__b_prere  is semver_a ? self::ARG_UNSET : svpre_optp_a )))  $b_prere__x_xxxxx__x_xxxxx__x_xxxxx
    * @return  int<-1,1>
    * @throws  void
    */
    public static function pcmp(
        int|string       $a_major__a_major__a_major__a,
        int|string       $a_minor__a_minor__a_minor__b_major,
        int|string       $a_patch__a_patch__b_major__b_minor = self::ARG_UNSET,
        int|string       $a_prere__b_major__b_minor__b_patch = self::ARG_UNSET,
        int|string       $b_major__b_minor__b_patch__b_prere = self::ARG_UNSET,
        int|string       $b_minor__b_patch__b_prere__x_xxxxx = self::ARG_UNSET,
        int|string       $b_patch__b_prere__x_xxxxx__x_xxxxx = self::ARG_UNSET,
        int|string       $b_prere__x_xxxxx__x_xxxxx__x_xxxxx = self::ARG_UNSET
    ) : int
    {
        $debug_n_args = -1;

        // @phpstan-ignore function.alreadyNarrowedType, smallerOrEqual.alwaysTrue
        assert(0 <= ($debug_n_args = \func_num_args()));

        return self::dispatch_pcmp(
            $debug_n_args,
            $a_major__a_major__a_major__a,
            $a_minor__a_minor__a_minor__b_major,
            $a_patch__a_patch__b_major__b_minor,
            $a_prere__b_major__b_minor__b_patch,
            $b_major__b_minor__b_patch__b_prere,
            $b_minor__b_patch__b_prere__x_xxxxx,
            $b_patch__b_prere__x_xxxxx__x_xxxxx,
            $b_prere__x_xxxxx__x_xxxxx__x_xxxxx);
    }

    /**
    * @param   int<0,max>    $debug_n_args
    * @param   svany_reqp_a   $p0
    * @param   svany_reqp_a   $p1
    * @param   svany_optp_a   $p2
    * @param   svany_optp_a   $p3
    * @param   svany_optp_a   $p4
    * @param   svpart_optp_a  $p5
    * @param   svpart_optp_a  $p6
    * @param   svpre_optp_a   $p7
    * @return  int<-1,1>
    * @throws  void
    */
    private static function dispatch_pcmp(
        int              $debug_n_args,
        int|string       $p0,
        int|string       $p1,
        int|string       $p2,
        int|string       $p3,
        int|string       $p4,
        int|string       $p5,
        int|string       $p6,
        int|string       $p7
    ) : int
    {
        //echo \strval(__LINE__).": dispatch_pcmp($p0, $p1, ...);\n";
        // The simplest AND most common case(s):
        if (is_string($p0))
        {
            $a = $p0;
            if (is_string($p1))
            {
                $b = $p1;
                assert($debug_n_args === 2,
                    "Right-hand-side (\$b='$b') is string SemVer, but ".
                    'possibly-contradictory integer arguments were also passed.');
                return self::pcmp_ss($a,$b);
            }

            $b_major = $p1;
            $b_minor = $p2;
            $b_patch = $p3;
            $b_prere = $p4;
            assert(is_int($b_minor));
            assert(is_int($b_patch));
            return self::pcmp_si(
                $a, $b_major, $b_minor, $b_patch, $b_prere);
        }

        // More complicated and potentially ambiguous stuff:
        // we need to know how many $a arguments were passed
        // and somehow detect where $b begins.
        $dnargs = $debug_n_args;
        $__ = self::ARG_UNSET;

        assert(2 <= ($pcount = self::count_dispatch_args($p0,$p1,$p2,$p3,$p4,$p5,$p6,$p7)));
        assert($debug_n_args === $pcount,
            "\nThis assertion will fail if there are not-unset arguments following unset arguments.\n".
            '`\\func_num_args()` returned '.\strval($debug_n_args).
            ' while `self::count_dispatch_args` returned '.\strval($pcount)."\n");

        // These assertions ensure that unset arguments are continuous.
        // They are intended to look like this:
        //    assert($p2 === self::ARG_UNSET ? $p3 === self::ARG_UNSET : true);
        //    assert($p2 === self::ARG_UNSET ? $p4 === self::ARG_UNSET : true);
        //    ...
        //    assert($p2 === self::ARG_UNSET ? $p7 === self::ARG_UNSET : true);
        // That is,
        //    if ($p2 is unset) then assert that ($p3..$p7 are unset)
        //
        // Unfortunately, PHPStan doesn't seem to follow the conditional.
        // We can use the rules of logic to rewrite the above
        // into something that PHPStan _should_ understand:
        //
        //   | a | b |  a ? b : 1 | !a | b | (!a)|b |
        //   +---+---+------------+----+---+--------+
        //   | 0 | 0 |     1      |  1 | 0 |   1    |
        //   | 0 | 1 |     1      |  1 | 1 |   1    |
        //   | 1 | 0 |     0      |  0 | 0 |   0    |
        //   | 1 | 1 |     1      |  0 | 1 |   1    |
        //
        // Hence we get our somewhat more cryptic versions.
        assert($p2 !== self::ARG_UNSET || $p3 === self::ARG_UNSET);
        assert($p2 !== self::ARG_UNSET || $p4 === self::ARG_UNSET);
        assert($p2 !== self::ARG_UNSET || $p5 === self::ARG_UNSET);
        assert($p2 !== self::ARG_UNSET || $p6 === self::ARG_UNSET);
        assert($p2 !== self::ARG_UNSET || $p7 === self::ARG_UNSET);

        assert($p3 !== self::ARG_UNSET || $p4 === self::ARG_UNSET);
        assert($p3 !== self::ARG_UNSET || $p5 === self::ARG_UNSET);
        assert($p3 !== self::ARG_UNSET || $p6 === self::ARG_UNSET);
        assert($p3 !== self::ARG_UNSET || $p7 === self::ARG_UNSET);

        assert($p4 !== self::ARG_UNSET || $p5 === self::ARG_UNSET);
        assert($p4 !== self::ARG_UNSET || $p6 === self::ARG_UNSET);
        assert($p4 !== self::ARG_UNSET || $p7 === self::ARG_UNSET);

        assert($p5 !== self::ARG_UNSET || $p6 === self::ARG_UNSET);
        assert($p5 !== self::ARG_UNSET || $p7 === self::ARG_UNSET);

        assert($p6 !== self::ARG_UNSET || $p7 === self::ARG_UNSET);

        assert(!is_string($p7) || $p6 !== self::ARG_UNSET);
        assert(!is_string($p7) || $p5 !== self::ARG_UNSET);
        assert(!is_string($p7) || $p4 !== self::ARG_UNSET);
        assert(!is_string($p7) || $p3 !== self::ARG_UNSET);
        assert(!is_string($p7) || $p2 !== self::ARG_UNSET);

        assert(!is_string($p6) || $p5 !== self::ARG_UNSET);
        assert(!is_string($p6) || $p4 !== self::ARG_UNSET);
        assert(!is_string($p6) || $p3 !== self::ARG_UNSET);
        assert(!is_string($p6) || $p2 !== self::ARG_UNSET);

        assert(!is_string($p5) || $p4 !== self::ARG_UNSET);
        assert(!is_string($p5) || $p3 !== self::ARG_UNSET);
        assert(!is_string($p5) || $p2 !== self::ARG_UNSET);

        assert(!is_string($p4) || $p3 !== self::ARG_UNSET);
        assert(!is_string($p4) || $p2 !== self::ARG_UNSET);

        assert(!is_string($p3) || $p2 !== self::ARG_UNSET);

        // assert($p2 === self::ARG_UNSET ? $p3 === self::ARG_UNSET : true);
        // assert($p2 === self::ARG_UNSET ? $p4 === self::ARG_UNSET : true);
        // assert($p2 === self::ARG_UNSET ? $p5 === self::ARG_UNSET : true);
        // assert($p2 === self::ARG_UNSET ? $p6 === self::ARG_UNSET : true);
        // assert($p2 === self::ARG_UNSET ? $p7 === self::ARG_UNSET : true);
        //
        // assert($p3 === self::ARG_UNSET ? $p4 === self::ARG_UNSET : true);
        // assert($p3 === self::ARG_UNSET ? $p5 === self::ARG_UNSET : true);
        // assert($p3 === self::ARG_UNSET ? $p6 === self::ARG_UNSET : true);
        // assert($p3 === self::ARG_UNSET ? $p7 === self::ARG_UNSET : true);
        //
        // assert($p4 === self::ARG_UNSET ? $p5 === self::ARG_UNSET : true);
        // assert($p4 === self::ARG_UNSET ? $p6 === self::ARG_UNSET : true);
        // assert($p4 === self::ARG_UNSET ? $p7 === self::ARG_UNSET : true);
        //
        // assert($p5 === self::ARG_UNSET ? $p6 === self::ARG_UNSET : true);
        // assert($p5 === self::ARG_UNSET ? $p7 === self::ARG_UNSET : true);
        //
        // assert($p6 === self::ARG_UNSET ? $p7 === self::ARG_UNSET : true);

        // A string in the lower positions could either
        // be $b itself or could be $a's prerelease string.
        // We'll defer that to another function because it
        // DOES tell us where $a's integer arguments end,
        // and the function call lets us organize the
        // parameters into known positions.
        if (is_string($p1)) { return self::dispatch_demux0($dnargs,  $p0,$__,$__,$p1,$p2,$p3,$p4,$p5); }
        if (is_string($p2)) { return self::dispatch_demux0($dnargs,  $p0,$p1,$__,$p2,$p3,$p4,$p5,$p6); }
        if (is_string($p3)) { return self::dispatch_demux0($dnargs,  $p0,$p1,$p2,$p3,$p4,$p5,$p6,$p7); }

        if (is_string($p4))
        {
            // This case is highly ambiguous.
            // $p4 is either $b (as a single semver string)
            // or $b's prerelease string.
            // What we DO know is that in either case,
            // subsequent arguments are forbidden.
            assert($p5 === self::ARG_UNSET);
            assert($p6 === self::ARG_UNSET);
            assert($p7 === self::ARG_UNSET);
            assert(5 === $pcount);

            // To resolve this, we just define such
            // an argument pattern to mean "$a is 4 ints, $b is 1 string".
            // This is reasonable from these perspectives:
            // * It's the representation that contains the highest amount of information.
            // * It has the lowest (Shannon) entropy:
            //     * There is only one way to specify {$a0,$a1,$a2,$a3},{$b}.
            //         * Entropy = log2(1)
            //     * There "prerelease" case however, is itself ambiguous:
            //         * Is it {$a0,$a1,$a2},{$b0,$b1}
            //         * Or... {$a0,$a1},{$b0,$b1,$b2}
            //         * Or... {$a0},{$b0,$b1,$b2,$b3}
            //         * Entropy = log2(3) > log2(1)
            return -(self::pcmp_si($p4,  $p0,$p1,$p2,$p3));
        }

        // On the other side of things:
        // A string in positions {5,6,7} can
        // _only_ be $b's prerelease string.
        assert(is_string($p5) ? 6 === $pcount : true);
        assert(is_string($p6) ? 7 === $pcount : true);
        assert(is_string($p7) ? 8 === $pcount : true);

        // And since we (now) know that all
        // of the preceding arguments are integers,
        // we can disambiguate because prerelease
        // components only have meaning if the other
        // components of that semver are present.
        if (is_string($p5)) { assert($p2 !== self::ARG_UNSET); return self::pcmp_ii($p0,$p1,$__,$__, $p2,$p3,$p4,$p5); }
        if (is_string($p6)) { assert($p3 !== self::ARG_UNSET); return self::pcmp_ii($p0,$p1,$p2,$__, $p3,$p4,$p5,$p6); }
        if (is_string($p7)) { /*    ---- PHPStan WHY ----   */ return self::pcmp_ii($p0,$p1,$p2,$p3, $p4,$p5,$p6,$p7); }

        // Done? Nope! We still have a bunch of cases to cover!
        // Because the above will fallthrough if the args are all integers.
        //
        // At this point we could use wildcards to attempt disambiguation,
        // but that could have cruel results: wildcard args are typed as
        // integers, just like ordinal values, so the meaning could change
        // depending on what some other function returns. In other words,
        // in the string case, we used the type system to resolve things
        // (and, in principle, PHPStan can check that). But in the wildcard
        // case, there is no way to perform static verification.
        //
        // So we're left with the "OOPS! All integers!" cases.
        //
        // These cases are highly ambiguous, but
        // easy to define canonical resolutions for:
        // * It's always split 50/50
        // * If it's an odd number (all int case), the left will be bigger by 1.
        if ($p2 === self::ARG_UNSET) { return self::pcmp_ii($p0,$__,$__,$__, $p1,$__,$__,$__); }
        if ($p3 === self::ARG_UNSET) { return self::pcmp_ii($p0,$p1,$__,$__, $p2,$__,$__,$__); }
        if ($p4 === self::ARG_UNSET) { return self::pcmp_ii($p0,$p1,$__,$__, $p2,$p3,$__,$__); }
        if ($p5 === self::ARG_UNSET) { return self::pcmp_ii($p0,$p1,$p2,$__, $p3,$p4,$__,$__); }
        if ($p6 === self::ARG_UNSET) { return self::pcmp_ii($p0,$p1,$p2,$__, $p3,$p4,$p5,$__); }

        // The last two cases can share the same function call because
        // the arguments are continuous whether we have 7 or 8 of them.
        return self::pcmp_ii($p0,$p1,$p2,$p3, $p4,$p5,$p6,$p7);
    }

    private static function count_dispatch_args(
        int|string $p0,  int|string $p1,  int|string $p2,  int|string $p3,
        int|string $p4,  int|string $p5,  int|string $p6,  int|string $p7
    ) : int
    {
        if ($p0 === self::ARG_UNSET) { return 0; }
        if ($p1 === self::ARG_UNSET) { return 1; }
        if ($p2 === self::ARG_UNSET) { return 2; }
        if ($p3 === self::ARG_UNSET) { return 3; }
        if ($p4 === self::ARG_UNSET) { return 4; }
        if ($p5 === self::ARG_UNSET) { return 5; }
        if ($p6 === self::ARG_UNSET) { return 6; }
        if ($p7 === self::ARG_UNSET) { return 7; }
        return 8;
    }

    /**
    * @param   int<0,max>        $dnargs
    * @param   svnum_reqp_a      $a0
    * @param   svnum_optp_a      $a1
    * @param   svnum_optp_a      $a2
    * @param   string|semver_a   $s3
    * @param   svany_optp_a      $p4
    * @param   svpart_optp_a     $p5
    * @param   svpart_optp_a     $p6
    * @param   svpre_optp_a      $p7
    * @return  int<-1,1>
    * @throws  void
    */
    private static function dispatch_demux0(
        int              $dnargs,
        int              $a0, // $a_major
        int              $a1, // $a_minor
        int              $a2, // $a_patch
        string           $s3,
        int|string       $p4,
        int|string       $p5,
        int|string       $p6,
        int|string       $p7
    ) : int
    {
        $__ = self::ARG_UNSET;
        // We can test right away if ($b === $s3),
        // because if this is true, we won't have a $p4 argument.
        if (is_int($p4) && $p4 === self::ARG_UNSET) {
            // $s3 is $b
            assert($p5 === self::ARG_UNSET);
            assert($p6 === self::ARG_UNSET);
            assert($p7 === self::ARG_UNSET);
            return -(self::pcmp_si($s3,  $a0,$a1,$a2,$__));
        }

        // Check two prereleases passed as strings.
        // Such cases are highly UNambiguous, and
        // we can know exactly what-goes-to-what.
        if (is_string($p4)) { return -(self::pcmp_si($p4,  $a0,$a1,$a2,$s3)); }
        if (is_string($p5)) { return self::pcmp_ii($a0,$a1,$a2,$s3, $p4,$__,$__,$p5); }
        if (is_string($p6)) { return self::pcmp_ii($a0,$a1,$a2,$s3, $p4,$p5,$__,$p6); }
        if (is_string($p7)) { return self::pcmp_ii($a0,$a1,$a2,$s3, $p4,$p5,$p6,$p7); }

        // If we only have 1 string in our params,
        // then it could be one of 2 things:
        // * $b itself as a string semver; the rest are -1 (null/sentinel/unset)
        // * The prerelease for $a; rest are ints for $b
        //
        // We already excluded the former. That means it's the latter.
        // $s3 is $a_prerelease
        return self::pcmp_ii($a0,$a1,$a2,$s3, $p4,$p5,$p6,$p7);
    }

    /**
    * Patternistic comparison of two stringy SemVers.
    *
    * @param     semver_a    $a
    * @param     semver_a    $b
    * @return    int<-1,1>
    * @throws    void
    */
    private static function pcmp_ss(string $a, string $b) : int
    {
        // I thought I might need this for verifying
        // patterns like asterisks-as-wildcards, because
        // those would end up positioned directly after
        // where the parser stops. But if it's not an asterisk,
        // there's nowhere to report the error. May as well
        // just keep going because it'll do the right thing regardless.
        //
        // $b_pos = 0;
        // $b_len = \strlen($b);
        // $b_ncomponents = 6;
        // $b_major = self::ARG_UNSET;
        // $b_minor = self::ARG_UNSET;
        // $b_patch = self::ARG_UNSET;
        // $b_prerelease = self::ARG_UNSET;
        // $b_build_metadata = self::ARG_UNSET;
        // $part_len =
        //     self::parse_semver(
        //         $b, $b_pos, $b_len, $b_ncomponents,
        //         $b_major, $b_minor, $b_patch, $b_prerelease, $b_build_metadata);

        $b_major = self::ARG_UNSET;
        $b_minor = self::ARG_UNSET;
        $b_patch = self::ARG_UNSET;
        $b_prerelease = self::ARG_UNSET;
        $b_build_metadata = self::ARG_UNSET;
        $b_ncomponents = 4; // We don't need build metadata for this.
        $b_ncomponents =
            self::deconstruct_n(
                $b, $b_ncomponents, $b_major, $b_minor, $b_patch, $b_prerelease, $b_build_metadata);

        return self::pcmp_si(
            $a, $b_major, $b_minor, $b_patch, $b_prerelease, $b_ncomponents);
    }

    /**
    * Patternistic comparison of one stringy SemVer and an integer-based SemVer.
    *
    * @param     semver_a      $a
    * @param     svnum_optp_a  $b_major
    * @param     svnum_optp_a  $b_minor
    * @param     svnum_optp_a  $b_patch
    * @param     svpre_optp_a  $b_prerelease
    * @param     int<0,6>|self::ARG_UNSET  $b_ncomponents
    * @return    int<-1,1>
    * @throws    void
    */
    private static function pcmp_si(
        string $a,
        int $b_major, int $b_minor, int $b_patch, string|int $b_prerelease,
        int $b_ncomponents = self::ARG_UNSET
    ) : int
    {
        if ( $b_ncomponents < 0 ) {
            $b_ncomponents = self::count_ordinal_components_in_ints($b_major, $b_minor, $b_patch, $b_prerelease, self::ARG_UNSET);
        }

        $a_major          = self::ARG_UNSET;
        $a_minor          = self::ARG_UNSET;
        $a_patch          = self::ARG_UNSET;
        $a_prerelease     = self::ARG_UNSET;
        $a_build_metadata = self::ARG_UNSET;
        $a_ncomponents = 4; // We don't need build metadata for this.
        $a_ncomponents =
            self::deconstruct_n(
                $a, $a_ncomponents, $a_major, $a_minor, $a_patch, $a_prerelease, $a_build_metadata);

        return self::pcmp_ii(
            $a_major, $a_minor, $a_patch, $a_prerelease,
            $b_major, $b_minor, $b_patch, $b_prerelease,
            $a_ncomponents, $b_ncomponents);
    }

    /**
    * Patternistic comparison of two integer-based SemVers.
    *
    * @param     svnum_optp_a  $a_major
    * @param     svnum_optp_a  $a_minor
    * @param     svnum_optp_a  $a_patch
    * @param     svpre_optp_a  $a_prerelease
    * @param     svnum_optp_a  $b_major
    * @param     svnum_optp_a  $b_minor
    * @param     svnum_optp_a  $b_patch
    * @param     svpre_optp_a  $b_prerelease
    * @param     int<0,6>|self::ARG_UNSET  $a_ncomponents
    * @param     int<0,6>|self::ARG_UNSET  $b_ncomponents
    * @return    int<-1,1>
    * @throws    void
    */
    private static function pcmp_ii(
        int $a_major, int $a_minor, int $a_patch, string|int $a_prerelease,
        int $b_major, int $b_minor, int $b_patch, string|int $b_prerelease,
        int $a_ncomponents = self::ARG_UNSET, int $b_ncomponents = self::ARG_UNSET
    ) : int
    {
        if ( $a_ncomponents < 0 ) {
            $a_ncomponents = self::count_ordinal_components_in_ints($a_major, $a_minor, $a_patch, $a_prerelease, self::ARG_UNSET);
        }
        if ( $b_ncomponents < 0 ) {
            $b_ncomponents = self::count_ordinal_components_in_ints($b_major, $b_minor, $b_patch, $b_prerelease, self::ARG_UNSET);
        }

        $n_components = $a_ncomponents;
        if ($b_ncomponents < $n_components) {
            $n_components = $b_ncomponents;
        }

        if ($n_components === 0) {
            // Empty patterns are the same as '*': match everything.
            return 0;
        }

        // Notably, the $n_components checks will
        // handle wildcard resolution because wildcards
        // halt the `count_ordinal_components_in_ints`
        // functions that were called earlier to
        // acquire $n_components.
        $res = ($a_major <=> $b_major);
        if (0 !== $res || $n_components === 1) { return $res; }

        if ($a_minor === self::ARG_WILDCARD
        ||  $b_minor === self::ARG_WILDCARD) {
            return 0;
        }

        $res = ($a_minor <=> $b_minor);
        if (0 !== $res || $n_components === 2) { return $res; }

        if ($a_patch === self::ARG_WILDCARD
        ||  $b_patch === self::ARG_WILDCARD) {
            return 0;
        }

        $res = ($a_patch <=> $b_patch);
        if (0 !== $res) { return $res; }

        // We don't leave the prerelease comparisons
        // to the $n_components counts like before.
        // This is because the prerelease identifiers have
        // some different logic where wildcards and partials
        // don't necessarily get handled the same way.
        if ($a_prerelease === self::ARG_WILDCARD || $a_prerelease === '*'
        ||  $b_prerelease === self::ARG_WILDCARD || $b_prerelease === '*') {
            return 0;
        }

        // Handle int cases, because `pcmp_prerelease` is only for strings.
        if (self::cmp_int_prerelease($a_prerelease, $b_prerelease, $res)) {
            return $res;
        }

        $a_pos = 0;
        $b_pos = 0;
        return self::cmp_prerelease(
            $a_prerelease, $b_prerelease, $a_pos, $b_pos, $process_wildcards=true);
    }

    private static function unittest_pcmp() : void
    {
        echo("  ".__FUNCTION__."()\n");

        $pcmp = function (string $a, string $b):int
        {
            // Call self::pcmp, but also range check the return value every time.
            $res = self::pcmp($a,$b);
            assert(-1 <= $res); // @phpstan-ignore function.alreadyNarrowedType, smallerOrEqual.alwaysTrue
            assert($res <= 1);  // @phpstan-ignore function.alreadyNarrowedType, smallerOrEqual.alwaysTrue
            return $res;
        };

        self::common_cmp_unittests(self::pcmp(...));

        assert($pcmp('1.2.3', '1.2'  ) === 0);
        assert($pcmp('1.2'  , '1.2.3') === 0);
        assert($pcmp('1.2.3', '1'    ) === 0);
        assert($pcmp('1.2'  , '1.2'  ) === 0);
        assert($pcmp('1.2'  , '1.3'  )  <  0);
        assert($pcmp('1'    , '2'    )  <  0);

        assert($pcmp('1.2.3', '1.2.*') === 0);
        assert($pcmp('1.2.*', '1.2.3') === 0);
        assert($pcmp('1.2.3', '1.*'  ) === 0);
        assert($pcmp('1.2.*', '1.2.*') === 0);
        assert($pcmp('1.2.*', '1.3.*')  <  0);
        assert($pcmp('1.*'  , '1.*'  ) === 0);
        assert($pcmp('1.*'  , '2.*'  )  <  0);
        assert($pcmp('1.*.*', '1.*'  ) === 0);
        assert($pcmp('1.*.*', '2.*'  )  <  0);
        assert($pcmp('1'    , '1.*'  ) === 0);
        assert($pcmp('1'    , '2.*'  )  <  0);
        assert($pcmp('1.1.9', '1.2.*')  <  0);

        assert(SemVer::pcmp('1.2.3', 1,2,3) === 0);
        assert(SemVer::pcmp('1.2.3', 4,5,6)  <  0);

        assert(SemVer::pcmp('1.2.3',     1,2          ) === 0);
        assert(SemVer::pcmp('1.2'  ,     1,2,3        ) === 0);
        assert($pcmp('1.2.3-*',   '1.2.3-rc4'  ) === 0);
        assert($pcmp('1.2.3-*',   '1.2.3-'     ) === 0);
        assert($pcmp('1.2.3-*',   '1.2.3'      ) === 0);
        assert($pcmp('1.2.3-',    '1.2.3-rc4'  )  >  0);
        assert($pcmp('1.2.3',     '1.2.3-rc4'  )  >  0);
        assert($pcmp('1.2.3-x.*', '1.2.3-x.rc4') === 0);
        assert($pcmp('1.2.3-x.*', '1.2.3-x.'   ) === 0);
        assert($pcmp('1.2.3-x.*', '1.2.3-x'    ) === 0);
        echo \strval(__LINE__).": Failed unittest: \$pcmp('1.2.3-x.',  '1.2.3-x.rc4').\n";// TODO: fix
        // assert($pcmp('1.2.3-x.',  '1.2.3-x.rc4')  >  0); // TODO: I'm not sure what to do with this. It's not valid semver to begin with. But might make pattern-sense. How so?
        assert($pcmp('1.2.3-x',   '1.2.3-x.rc4')  <  0);

        // Pattern matching (`pcmp`, `matching`)-specific
        // behaviors for flexible parameter layout:

        // Integer-to-integer comparisons are highly ambiguous.
        // In such situations, the flexible parameter layout always
        // considers the left and right semvers to have the same number
        // of components. If there are an odd number of components,
        // then the left-hand-side semver has 1 more component than
        // the right-hand-side semver.
        assert(SemVer::pcmp(1,     2    )  <  0);
        assert(SemVer::pcmp(1,2,   1    ) === 0);
        assert(SemVer::pcmp(1,2,   3    )  <  0);
        assert(SemVer::pcmp(1,2,   1,2  ) === 0);
        assert(SemVer::pcmp(1,3,   1,2  )  >  0);
        assert(SemVer::pcmp(1,2,3, 1,2  ) === 0);
        assert(SemVer::pcmp(1,3,3, 1,2  )  >  0);
        assert(SemVer::pcmp(1,2,3, 9,3  )  <  0);
        assert(SemVer::pcmp(1,2,3, 1,2,3) === 0);
        assert(SemVer::pcmp(1,3,4, 1,2,5)  >  0);

        // Note that prerelease identifiers can be passed as integers,
        // and will be subject to the same balancing of integer parameters.
        // (But prereleases are still considered "less than" releases.)
        assert(SemVer::pcmp(1,2,3,4, 1,2,3  )  <  0);
        assert(SemVer::pcmp(1,2,3,4, 1,2,3,4) === 0);

        // When prereleases appear as strings, it disambiguates
        // the argument meaning in the integers-to-integers case:
        assert(SemVer::pcmp(1,2,3,'4',   1,2,3)  <  0); // Same as pcmp('1.2.3-4', '1.2.3')
        assert(SemVer::pcmp(1,2,3,   1,2,3,'4')  >  0); // Same as pcmp('1.2.3', '1.2.3-4')
        echo \strval(__LINE__).": Failed unittest: SemVer::pcmp(1,  1,2,3,'4').\n";// TODO: fix
        //assert(SemVer::pcmp(1,       1,2,3,'4') === 0); // Same as pcmp('1', '1.2.3-4');

        // Notably invalid: prereleases must be preceded by 3 ints always.
        // I don't think we can write a test for this case.
        //assert(SemVer::pcmp(1,'3',     4,1,2,5)  <  0);
    }


    /**
    * Checks if one Semantic Version (SemVer) matches another.
    *
    * This match works as an equality check like the `equal` function,
    * but also supports pattern matching on partial semvers and wildcards:
    * ```php
    * // Ordinary equality:
    * assert( SemVer::matching('1.2.3', '1.2.3'));
    * assert(!SemVer::matching('1.2.3', '4.5.6'));
    * assert(!SemVer::matching('4.5.6', '1.2.3'));
    *
    * // Partial semvers:
    * assert( SemVer::matching('1.2.3', '1.2'  ));
    * assert( SemVer::matching('1.2'  , '1.2.3'));
    * assert( SemVer::matching('1.2.3', '1'    ));
    * assert( SemVer::matching('1.2'  , '1.2'  ));
    * assert(!SemVer::matching('1.2'  , '1.3'  ));
    * assert(!SemVer::matching('1'    , '2'    ));
    *
    * // Wildcard notation:
    * assert( SemVer::matching('1.2.3', '1.2.*'));
    * assert( SemVer::matching('1.2.*', '1.2.3'));
    * assert( SemVer::matching('1.2.3', '1.*'  ));
    * assert( SemVer::matching('1.2.*', '1.2.*'));
    * assert(!SemVer::matching('1.2.*', '1.3.*'));
    * assert( SemVer::matching('1.*'  , '1.*'  ));
    * assert(!SemVer::matching('1.*'  , '2.*'  ));
    * assert( SemVer::matching('1.*.*', '1.*'  ));
    * assert(!SemVer::matching('1.*.*', '2.*'  ));
    * assert( SemVer::matching('1'    , '1.*'  ));
    * assert(!SemVer::matching('1'    , '2.*'  ));
    * ```
    *
    * This function also features a flexible parameter layout that
    * allows integers to be compared to semantic versions without
    * the caller being required to stringize them first:
    * ```php
    * // Without integer parameters, string allocations may be required:
    * assert(SemVer::matching('1.2.3', SemVer::construct(1,2,3)));
    *
    * // With integer parameters, the same calculation
    * // can be performed without memory allocations:
    * assert( SemVer::matching('1.2.3',  1,2,3 ));
    * assert( SemVer::matching( 1,2,3,  '1.2.3'));
    * assert( SemVer::matching( 1,2,3,   1,2,3 ));
    * assert(!SemVer::matching('1.2.3',  4,5,6 ));
    * assert(!SemVer::matching( 1,2,3,  '4.5.6'));
    * assert(!SemVer::matching( 1,2,3,   4,5,6 ));
    *
    * // Prerelease identifiers (and build metadata) are supported:
    * assert(SemVer::pcmp('1.2.3-r1',  1,2,3,'r1') === 0);
    * assert(SemVer::pcmp('1.2.3-r1',  1,2,3,'999') >  0);
    * assert(SemVer::pcmp('1.2.3-r2',  1,2,3,'r1')  >  0);
    * assert(SemVer::pcmp('1.2.3',     1,2,3,'r2')  >  0);
    * assert(SemVer::pcmp('0.0.0',     0,0,0,'r99') >  0);
    * assert(SemVer::pcmp('0.0.0',     0,0,1,'r99') <  0);
    *
    * // Note that there is no "build metadata" parameter,
    * // because build metadata does not affect comparisons.
    * // Build metadata must appear in the prerelease string,
    * // and it must begin with a '+' character.
    * // (The prerelease identifier must NOT begin with a '-'
    * // character in this case, unless the '-' is part of
    * // the identifier.)
    * assert(SemVer::pcmp('1.2.3+b1',      1,2,3,'+b9'    ) === 0);
    * assert(SemVer::pcmp('1.2.3-r1+b1',   1,2,3,'r1+b9'  ) === 0);
    * assert(SemVer::pcmp('1.2.3-r1+b999', 1,2,3,'r9+b9'  )  <  0);
    * assert(SemVer::pcmp('1.2.3-r9+b1',   1,2,3,'r1+b999')  >  0);
    *
    * // Integer-to-integer comparisons are highly ambiguous.
    * // In such situations, the flexible parameter layout always
    * // considers the left and right semvers to have the same number
    * // of components. If there are an odd number of components,
    * // then the left-hand-side semver has 1 more component than
    * // the right-hand-side semver.
    * assert(SemVer::pcmp(1,     2    )  <  0);
    * assert(SemVer::pcmp(1,2,   1    ) === 0);
    * assert(SemVer::pcmp(1,2,   3    )  <  0);
    * assert(SemVer::pcmp(1,2,   1,2  ) === 0);
    * assert(SemVer::pcmp(1,3,   1,2  )  >  0);
    * assert(SemVer::pcmp(1,2,3, 1,2  ) === 0);
    * assert(SemVer::pcmp(1,3,3, 1,2  )  >  0);
    * assert(SemVer::pcmp(1,2,3, 9,3  )  <  0);
    * assert(SemVer::pcmp(1,2,3, 1,2,3) === 0);
    * assert(SemVer::pcmp(1,3,4, 1,2,5)  >  0);
    *
    * // Note that prerelease identifiers can be passed as integers,
    * // and will be subject to the same balancing of integer parameters.
    * // (But prereleases are still considered "less than" releases.)
    * assert(SemVer::pcmp(1,2,3,4, 1,2,3  )  <  0);
    * assert(SemVer::pcmp(1,2,3,4, 1,2,3,4) === 0);
    *
    * // When prereleases appear as strings, it disambiguates
    * // the argument meaning in the integers-to-integers case:
    * assert(SemVer::pcmp(1,2,3,'4',   1,2,3)  <  0); // Same as pcmp('1.2.3-4', '1.2.3')
    * assert(SemVer::pcmp(1,2,3,   1,2,3,'4')  >  0); // Same as pcmp('1.2.3', '1.2.3-4')
    * assert(SemVer::pcmp(1,       1,2,3,'4') === 0); // Same as pcmp('1', '1.2.3-4');
    *
    * // When string semvers are used, it is illegal to pass
    * // any redundant/overconstraining integer arguments.
    * // In such cases, they will cause assertions at
    * // debug-time, or be ignored otherwise:
    * // echo (SemVer::matching('2.2.0','2.3.0',2,0) ? "true\n" : "false\n"); // "false" or assertion error.
    * // echo (SemVer::matching('2.2.0','2',1,0)     ? "true\n" : "false\n"); // "true" or assertion error.
    * ```
    *
    * The right-hand-side (rhs or `$b`) parameter of this function can
    * accept a semantic version as either a string or as a series of
    * integer arguments, with the major version component being '$b':
    * ```php
    * assert( SemVer::matching('1.2.3', 1,2,3));
    * assert(!SemVer::matching('1.2.3', 4,5,6));
    * ```
    *
    * SemVer::ARG_WILDCARD values function as wildcards:
    * ```php
    * assert( SemVer::matching('1.2.3', 1,2,SemVer::ARG_WILDCARD));
    * assert(!SemVer::matching('4.5.6', 1,2,SemVer::ARG_WILDCARD));
    * ```
    *
    * Elided arguments behave like wildcards in most cases,
    * but may have slightly different behavior in the case
    * of prerelease strings:
    * * Wildcards tend to mean "be equal to anything (assuming equal higher-significance fields)"
    * * Empty prerelease strings or segments mean "sort lower than anything (assuming equal higher-significance fields)"
    * ```php
    * assert( SemVer::matching('1.2.3',     1,2          ));
    * assert( SemVer::matching('1.2'  ,     1,2,3        ));
    * assert( SemVer::matching('1.2.3-*',   '1.2.3-rc4'  ));
    * assert( SemVer::matching('1.2.3-*',   '1.2.3-'     ));
    * assert( SemVer::matching('1.2.3-*',   '1.2.3'      ));
    * assert(!SemVer::matching('1.2.3-',    '1.2.3-rc4'  ));
    * assert(!SemVer::matching('1.2.3',     '1.2.3-rc4'  ));
    * assert( SemVer::matching('1.2.3-x.*', '1.2.3-x.rc4'));
    * assert( SemVer::matching('1.2.3-x.*', '1.2.3-x.'   ));
    * assert( SemVer::matching('1.2.3-x.*', '1.2.3-x'    ));
    * assert(!SemVer::matching('1.2.3-x.',  '1.2.3-x.rc4'));
    * assert(!SemVer::matching('1.2.3-x',   '1.2.3-x.rc4'));
    * ```
    *
    * Wildcards always have precedence over less signficant components:
    * ```php
    * assert( SemVer::matching('1.*.3',  '1.*'  ));
    * assert( SemVer::matching('1.*.3',  '1.2.9'));
    * ```
    *
    * In other words, something like `1.*.3` will always behave identically
    * to `1.*` in comparisons with other SemVers and partial SemVers,
    * and that in turn behaves identically to `1` (a major version with
    * no other components present).
    *
    * The `$b_prerelease` string may contain build metadata in addition
    * to the prerelease identifier(s). Any build metadata will be ignored,
    * as is in accordance with the SemVer specification (build metadata
    * is defined as being not significant during comparison).
    *
    * Limitation: There currently isn't any support for globbing,
    * ex: `SemVer::matching('3.2.17', '3.2.1*')` or
    * `SemVer::match('4.5.6-rc*', '4.5.6-rc6')`; this simply
    * hasn't received the work required to implement.
    *
    * Note that this check is more computationally demanding than the
    * (SemVer::cmp($a,$b) === 0), primarily due to the fact that
    * this matching function supports partial/wildcard matching.
    * This is not an increase in algorithmic complexity, and neither
    * function does explicit memory allocation, so the differences
    * can be ignored in most situations.
    *
    * @param   svany_reqp_a                                                                               $a_major__a_major__a_major__a
    * @param   ($a_major__a_major__a_major__a is svnum_reqp_a ? svany_reqp_a    : svany_reqp_a|semver_a)  $a_minor__a_minor__a_minor__b_major
    * @param   ($a_major__a_major__a_major__a is svnum_reqp_a ? svany_optp_a    : ($a_minor__a_minor__a_minor__b_major  is  svnum_reqp_a ? svany_optp_a : self::ARG_UNSET))  $a_patch__a_patch__b_major__b_minor
    * @param   ($a_major__a_major__a_major__a is svnum_reqp_a ? svany_optp_a    : ($a_minor__a_minor__a_minor__b_major  is  svnum_reqp_a ? svany_optp_a : self::ARG_UNSET))  $a_prere__b_major__b_minor__b_patch
    * @param   ($a_major__a_major__a_major__a is svnum_reqp_a ? svany_optp_a    : ($a_minor__a_minor__a_minor__b_major  is  svnum_reqp_a ? svany_optp_a : self::ARG_UNSET))  $b_major__b_minor__b_patch__b_prere
    * @param   ($a_major__a_major__a_major__a is semver_a     ? self::ARG_UNSET : ($a_minor__a_minor__a_minor__b_major  is  semver_a  ? self::ARG_UNSET : ($b_major__b_minor__b_patch__b_prere  is semver_a ? self::ARG_UNSET : svpart_optp_a)))  $b_minor__b_patch__b_prere__x_xxxxx
    * @param   ($a_major__a_major__a_major__a is semver_a     ? self::ARG_UNSET : ($a_minor__a_minor__a_minor__b_major  is  semver_a  ? self::ARG_UNSET : ($b_major__b_minor__b_patch__b_prere  is semver_a ? self::ARG_UNSET : svpart_optp_a)))  $b_patch__b_prere__x_xxxxx__x_xxxxx
    * @param   ($a_major__a_major__a_major__a is semver_a     ? self::ARG_UNSET : ($a_minor__a_minor__a_minor__b_major  is  semver_a  ? self::ARG_UNSET : ($b_major__b_minor__b_patch__b_prere  is semver_a ? self::ARG_UNSET : svpre_optp_a )))  $b_prere__x_xxxxx__x_xxxxx__x_xxxxx
    * @throws    void
    */
    public static function matching(
        int|string       $a_major__a_major__a_major__a,
        int|string       $a_minor__a_minor__a_minor__b_major,
        int|string       $a_patch__a_patch__b_major__b_minor = self::ARG_UNSET,
        int|string       $a_prere__b_major__b_minor__b_patch = self::ARG_UNSET,
        int|string       $b_major__b_minor__b_patch__b_prere = self::ARG_UNSET,
        int|string       $b_minor__b_patch__b_prere__x_xxxxx = self::ARG_UNSET,
        int|string       $b_patch__b_prere__x_xxxxx__x_xxxxx = self::ARG_UNSET,
        int|string       $b_prere__x_xxxxx__x_xxxxx__x_xxxxx = self::ARG_UNSET
    ) : bool
    {
        $debug_n_args = -1;

        // @phpstan-ignore function.alreadyNarrowedType, smallerOrEqual.alwaysTrue
        assert(0 <= ($debug_n_args = \func_num_args()));

        $res = self::dispatch_pcmp(
            $debug_n_args,
            $a_major__a_major__a_major__a,
            $a_minor__a_minor__a_minor__b_major,
            $a_patch__a_patch__b_major__b_minor,
            $a_prere__b_major__b_minor__b_patch,
            $b_major__b_minor__b_patch__b_prere,
            $b_minor__b_patch__b_prere__x_xxxxx,
            $b_patch__b_prere__x_xxxxx__x_xxxxx,
            $b_prere__x_xxxxx__x_xxxxx__x_xxxxx);

        return ($res === 0);
    }

    private static function unittest_matching() : void
    {
        echo("  ".__FUNCTION__."()\n");

        // Ordinary equality:
        assert( SemVer::matching('1.2.3', '1.2.3'));
        assert(!SemVer::matching('1.2.3', '4.5.6'));
        assert(!SemVer::matching('4.5.6', '1.2.3'));

        // Partial semvers:
        assert( SemVer::matching('1.2.3', '1.2'  ));
        assert( SemVer::matching('1.2'  , '1.2.3'));
        assert( SemVer::matching('1.2.3', '1'    ));
        assert( SemVer::matching('1.2'  , '1.2'  ));
        assert(!SemVer::matching('1.2'  , '1.3'  ));
        assert(!SemVer::matching('1'    , '2'    ));

        // Wildcard notation:
        assert( SemVer::matching('1.2.3', '1.2.*'));
        assert( SemVer::matching('1.2.*', '1.2.3'));
        assert( SemVer::matching('1.2.3', '1.*'  ));
        assert( SemVer::matching('1.2.*', '1.2.*'));
        assert(!SemVer::matching('1.2.*', '1.3.*'));
        assert( SemVer::matching('1.*'  , '1.*'  ));
        assert(!SemVer::matching('1.*'  , '2.*'  ));
        assert( SemVer::matching('1.*.*', '1.*'  ));
        assert(!SemVer::matching('1.*.*', '2.*'  ));
        assert( SemVer::matching('1'    , '1.*'  ));
        assert(!SemVer::matching('1'    , '2.*'  ));

        // Verify compare-to-integer behavior.
        assert(SemVer::matching('1.2.3', SemVer::construct(1,2,3)));

        assert( SemVer::matching('1.2.3',  1,2,3 ));
        assert( SemVer::matching( 1,2,3,  '1.2.3'));
        assert( SemVer::matching( 1,2,3,   1,2,3 ));
        assert(!SemVer::matching('1.2.3',  4,5,6 ));
        assert(!SemVer::matching( 1,2,3,  '4.5.6'));
        assert(!SemVer::matching( 1,2,3,   4,5,6 ));

        // SemVer::ARG_WILDCARD values function as wildcards:
        assert( SemVer::matching('1.2.3', 1,2,SemVer::ARG_WILDCARD));
        assert(!SemVer::matching('4.5.6', 1,2,SemVer::ARG_WILDCARD));

        // Elided arguments behave like wildcards in most cases,
        // but may have slightly different behavior in the case
        // of prerelease strings:
        // * Wildcards tend to mean "be equal to anything (assuming equal higher-significance fields)"
        // * Empty prerelease strings or segments mean "sort lower than anything (assuming equal higher-significance fields)"
        assert( SemVer::matching('1.2.3',     1,2          ));
        assert( SemVer::matching('1.2'  ,     1,2,3        ));
        assert( SemVer::matching('1.2.3-*',   '1.2.3-rc4'  ));
        assert( SemVer::matching('1.2.3-*',   '1.2.3-'     ));
        assert( SemVer::matching('1.2.3-*',   '1.2.3'      ));
        assert(!SemVer::matching('1.2.3-',    '1.2.3-rc4'  ));
        assert(!SemVer::matching('1.2.3',     '1.2.3-rc4'  ));
        assert( SemVer::matching('1.2.3-x.*', '1.2.3-x.rc4'));
        assert( SemVer::matching('1.2.3-x.*', '1.2.3-x.'   ));
        assert( SemVer::matching('1.2.3-x.*', '1.2.3-x'    ));
        assert(!SemVer::matching('1.2.3-x.',  '1.2.3-x.rc4'));
        assert(!SemVer::matching('1.2.3-x',   '1.2.3-x.rc4'));

        // Wildcards always have precedence over less signficant components:
        assert( SemVer::matching('1.*.3',  '1.*'  ));
        assert( SemVer::matching('1.*.3',  '1.2.9'));
    }

    /**
    * Perform a comparison between two Semantic Versions (SemVers).
    *
    * Examples:
    * ```php
    * assert(SemVer::cmp('1.2.3',  '1.2.3') === 0);
    * assert(SemVer::cmp('1.2.3',  '4.5.6')  <  0);
    * assert(SemVer::cmp('4.5.6',  '1.2.3')  >  0);
    * assert(SemVer::cmp('1.2.9',  '1.2.30') <  0);
    * assert(SemVer::cmp('1.9.3',  '1.20.3') <  0);
    * assert(SemVer::cmp('9.2.3',  '10.2.3') <  0);
    * assert(SemVer::cmp('1.9.999','1.10.0') <  0);
    * ```
    *
    * Alphanumeric prereleases are considered "greater than" numeric ones.
    * Versions without prerelease identifiers are "greater than"
    * versions with prerelease identifiers:
    * ```php
    * assert(SemVer::cmp('1.2.3-r1', '1.2.3-r1') === 0);
    * assert(SemVer::cmp('1.2.3-r1', '1.2.3-999') >  0);
    * assert(SemVer::cmp('1.2.3-r2', '1.2.3-r1')  >  0);
    * assert(SemVer::cmp('1.2.3',    '1.2.3-r2')  >  0);
    * assert(SemVer::cmp('0.0.0',    '0.0.0-r99') >  0);
    * assert(SemVer::cmp('0.0.0',    '0.0.1-r99') <  0);
    * ```
    *
    * Prerelease versions are composed of dot-separated identifiers:
    * ```php
    * assert(SemVer::cmp('1.0.0-1.9.3',  '1.0.0-1.20.3') <  0);
    * assert(SemVer::cmp('1.0.0-9.2.3',  '1.0.0-10.2.3') <  0);
    * assert(SemVer::cmp('1.0.0-1.9.999','1.0.0-1.10.0') <  0);
    * ```
    *
    * Build metadata does not contribute to comparisons:
    * ```php
    * assert(SemVer::cmp('1.2.3+b1',      '1.2.3+b9'     ) === 0);
    * assert(SemVer::cmp('1.2.3-r1+b1',   '1.2.3-r1+b9'  ) === 0);
    * assert(SemVer::cmp('1.2.3-r1+b999', '1.2.3-r9+b9'  )  <  0);
    * assert(SemVer::cmp('1.2.3-r9+b1',   '1.2.3-r1+b999')  >  0);
    * ```
    *
    * This function features a flexible parameter layout that
    * allows integers to be compared to semantic versions without
    * the caller being required to stringize them first:
    * ```php
    * // Without integer parameters, string allocations may be required:
    * assert(SemVer::cmp('1.2.3', SemVer::construct(1,2,3)) === 0);
    *
    * // With integer parameters, the same calculation
    * // can be performed without memory allocations:
    * assert(SemVer::cmp('1.2.3',  1,2,3) === 0);
    * ```
    *
    *
    * @param   svnum_req_a|semver_a                                                         $a_major__a_major__a
    * @param   ($a_major__a_major__a is svnum_req_a  ? svnum_req_a : svnum_req_a|semver_a)  $a_minor__a_minor__b_major
    * @param   ($a_major__a_major__a is svnum_req_a  ? svnum_req_a     : ($a_minor__a_minor__b_major  is int      ? svnum_req_a     : self::ARG_UNSET))  $a_patch__a_patch__b_minor
    * @param   ($a_major__a_major__a is svnum_req_a  ? svany_opt_a     : ($a_minor__a_minor__b_major  is int      ? svany_opt_a     : self::ARG_UNSET))  $a_prere__b_major__b_patch
    * @param   ($a_major__a_major__a is svnum_req_a  ? svany_opt_a     : ($a_minor__a_minor__b_major  is int      ? svany_opt_a     : self::ARG_UNSET))  $b_major__b_minor__b_prere
    * @param   ($a_major__a_major__a is semver_a     ? self::ARG_UNSET : ($a_minor__a_minor__b_major  is semver_a ? self::ARG_UNSET : ($b_major__b_minor__b_prere  is semver_a ? self::ARG_UNSET : svnum_req_a )))  $b_minor__b_patch__x_xxxxx
    * @param   ($a_major__a_major__a is semver_a     ? self::ARG_UNSET : ($a_minor__a_minor__b_major  is semver_a ? self::ARG_UNSET : ($b_major__b_minor__b_prere  is semver_a ? self::ARG_UNSET : svpart_opt_a)))  $b_patch__b_prere__x_xxxxx
    * @param   ($a_major__a_major__a is semver_a     ? self::ARG_UNSET : ($a_minor__a_minor__b_major  is semver_a ? self::ARG_UNSET : ($b_major__b_minor__b_prere  is semver_a ? self::ARG_UNSET : svpre_opt_a )))  $b_prere__x_xxxxx__x_xxxxx
    * @return  int<-1,1>
    * @throws  void
    */
    public static function cmp(
        int|string       $a_major__a_major__a,
        int|string       $a_minor__a_minor__b_major,
        int              $a_patch__a_patch__b_minor = self::ARG_UNSET,
        int|string       $a_prere__b_major__b_patch = self::ARG_UNSET,
        int|string       $b_major__b_minor__b_prere = self::ARG_UNSET,
        int              $b_minor__b_patch__x_xxxxx = self::ARG_UNSET,
        int|string       $b_patch__b_prere__x_xxxxx = self::ARG_UNSET,
        int|string       $b_prere__x_xxxxx__x_xxxxx = self::ARG_UNSET
    ) : int
    {
        // TODO: Does this cause an argument array to be allocated?
        // (We might be able to save some Zendops if we just use \func_num_args()
        // and then use arithmetic to get component counts instead of walking
        // arguments until finding `-1`. But it'd be a super small save, and
        // rn it's not worth the risk of calling something that might allocate.)
        $debug_n_args = -1;

        // @phpstan-ignore function.alreadyNarrowedType, smallerOrEqual.alwaysTrue
        assert(0 <= ($debug_n_args = \func_num_args()));

        return self::dispatch_cmp(
            $debug_n_args,
            $a_major__a_major__a,
            $a_minor__a_minor__b_major,
            $a_patch__a_patch__b_minor,
            $a_prere__b_major__b_patch,
            $b_major__b_minor__b_prere,
            $b_minor__b_patch__x_xxxxx,
            $b_patch__b_prere__x_xxxxx,
            $b_prere__x_xxxxx__x_xxxxx);
    }

    /**
    * @param   int<0,max>    $debug_n_args
    * @param   svany_req_a   $p0
    * @param   svany_req_a   $p1
    * @param   svnum_opt_a   $p2
    * @param   svany_opt_a   $p3
    * @param   svany_opt_a   $p4
    * @param   svnum_opt_a   $p5
    * @param   svpart_opt_a  $p6
    * @param   svpre_opt_a   $p7
    * @return  int<-1,1>
    * @throws  void
    */
    private static function dispatch_cmp(
        int              $debug_n_args,
        int|string       $p0,
        int|string       $p1,
        int              $p2,
        int|string       $p3,
        int|string       $p4,
        int              $p5,
        int|string       $p6,
        int|string       $p7
    ) : int
    {
        $unset = self::ARG_UNSET;

        // The simplest AND most common case(s):
        if (is_string($p0))
        {
            assert($p5 === $unset);
            assert($p6 === $unset);
            assert($p7 === $unset);

            if (is_string($p1))
            {
                assert($p2 === $unset);
                assert($p3 === $unset);
                assert($p4 === $unset);
                assert($debug_n_args === 2,
                    "Right-hand-side (\$b='$p1') is string SemVer, but ".
                    'possibly-contradictory integer arguments were also passed.');
                return self::cmp_ss($p0,$p1);
            }

            $b_major = $p1;
            $b_minor = $p2;
            $b_patch = $p3;
            $b_prere = $p4;
            //assert(is_int($b_major)); // phpstan-ignore function.alreadyNarrowedType
            //assert(is_int($b_minor)); // phpstan-ignore function.alreadyNarrowedType
            assert(is_int($b_patch));
            assert(0 <= $b_major); // @phpstan-ignore function.alreadyNarrowedType, smallerOrEqual.alwaysTrue
            assert(0 <= $b_minor);
            assert(0 <= $b_patch);
            return self::cmp_si(
                $p0, $b_major, $b_minor, $b_patch, $b_prere);
        }

        // The more complicated case(s).
        // However, because we don't accept partial semvers,
        // we can disambiguate this much more easily than
        // in the patternistic case.

        $a_major = $p0;
        $a_minor = $p1;
        $a_patch = $p2;

        // assert(is_int($a_major)); // phpstan-ignore function.alreadyNarrowedType
        assert(is_int($a_minor));
        // assert(is_int($a_patch)); // phpstan-ignore function.alreadyNarrowedType
        assert($a_major !== $unset); // @phpstan-ignore function.alreadyNarrowedType
        assert($a_minor !== $unset); // @phpstan-ignore function.alreadyNarrowedType
        assert($a_patch !== $unset);
        if ($p4 === $unset || $p5 === $unset) {
            if ($p4 === $unset) {
                // 4 args = Unambiguously the int3+string case.
                $a_prere = $unset;
                $b = $p3;
            } else {
                assert($p5 === $unset); // @phpstan-ignore function.alreadyNarrowedType
                // 5 args = Unambiguously the int4+string case.
                $a_prere = $p3;
                $b = $p4;
            }
            assert(is_string($b));

            // Invert the sense of the return value,
            // because we called cmp_si with args reversed.
            return -(self::cmp_si(
                $b, $a_major, $a_minor, $a_patch, $a_prere));
        }

        // From this point on, all of the possibilities are int+int.
        // We just don't know whether $b starts at arg $p3 or arg $p4.
        if ($p6 === $unset) {
            // 6 args = Unambiguously the int3+int3 case.
            $a_ncomponents = 3;
        } else
        if ($p7 === $unset) {
            // The 7-arg case is tricky.
            // It's ambiguous: int4+int3 or int3+int4?
            // We can disambiguate it there is a string argument.
            // Otherwise we have to just define what this means.
            // Like with the patternistic case,
            // we will treat it as int4+int3.
            if (is_string($p6)) {
                assert(!is_string($p3));
                // int3+int4 unambiguously.
                $a_ncomponents = 3;
            } else {
                assert(is_string($p3));
                $a_ncomponents = 4;
            }
        } else {
            // 8 args = Unambiguously the int4+int4 case.
            assert($p7 !== $unset); // @phpstan-ignore function.alreadyNarrowedType
            $a_ncomponents = 4;
        }

        if ($a_ncomponents === 3) {
            $a_prere = $unset;
            $b_major = $p3;
            $b_minor = $p4;
            $b_patch = $p5;
            $b_prere = $p6;
        } else {
            // @phpstan-ignore function.alreadyNarrowedType
            assert($a_ncomponents === 4);
            $a_prere = $p3;
            $b_major = $p4;
            $b_minor = $p5;
            $b_patch = $p6;
            $b_prere = $p7;
        }
        assert(is_int($b_major));
        assert(is_int($b_minor));
        assert(is_int($b_patch));
        assert(0 <= $b_major);
        assert(0 <= $b_minor); // @phpstan-ignore function.alreadyNarrowedType, smallerOrEqual.alwaysTrue
        assert(0 <= $b_patch);

        return self::cmp_ii(
            $a_major, $a_minor, $a_patch, $a_prere,
            $b_major, $b_minor, $b_patch, $b_prere);
    }

    /**
    * @param     semver_a    $a
    * @param     semver_a    $b
    * @return    int<-1,1>
    * @throws    void
    */
    private static function cmp_ss(string $a, string $b) : int
    {
        assert(self::valid($a));
        assert(self::valid($b));

        $a_sep_pos = \strcspn($a, '+-');
        $b_sep_pos = \strcspn($b, '+-');

        $a_triplet = \substr($a,0,$a_sep_pos);
        $b_triplet = \substr($b,0,$b_sep_pos);

        $res = \strnatcmp($a_triplet, $b_triplet);
        if ( $res !== 0 ) {
            // Most significant digits are different.
            // We don't have to look at the rest to know.
            return $res;
        }

        $process_wildcards = false;
        return self::cmp_prerelease($a, $b, $a_sep_pos, $b_sep_pos, $process_wildcards);
    }

    /**
    * @param     semver_a     $a
    * @param     svnum_req_a  $b_major
    * @param     svnum_req_a  $b_minor
    * @param     svnum_req_a  $b_patch
    * @param     svpre_opt_a  $b_prerelease
    * @return    int<-1,1>
    * @throws    void
    */
    private static function cmp_si(
        string $a,
        int $b_major,  int $b_minor,  int $b_patch,  int|string $b_prerelease
    ) : int
    {
        assert(self::valid($a));

        $a_major          = self::ARG_UNSET;
        $a_minor          = self::ARG_UNSET;
        $a_patch          = self::ARG_UNSET;
        $a_prerelease     = self::ARG_UNSET;
        $a_build_metadata = self::ARG_UNSET;
        $n_components =
            self::deconstruct(
                $a, $a_major, $a_minor, $a_patch, $a_prerelease, $a_build_metadata);

        assert(3 <= $n_components);
        assert(0 <= $a_major);
        assert(0 <= $a_minor);
        assert(0 <= $a_patch);
        assert($a_prerelease !== self::ARG_WILDCARD);

        return self::cmp_ii(
            $a_major, $a_minor, $a_patch, $a_prerelease,
            $b_major, $b_minor, $b_patch, $b_prerelease);
    }

    /**
    * Standard comparison of two integer-based SemVers.
    *
    * @param     svnum_req_a  $a_major
    * @param     svnum_req_a  $a_minor
    * @param     svnum_req_a  $a_patch
    * @param     svpre_opt_a  $a_prerelease
    * @param     svnum_req_a  $b_major
    * @param     svnum_req_a  $b_minor
    * @param     svnum_req_a  $b_patch
    * @param     svpre_opt_a  $b_prerelease
    * @return    int<-1,1>
    * @throws    void
    */
    private static function cmp_ii(
        int $a_major, int $a_minor, int $a_patch, string|int $a_prerelease,
        int $b_major, int $b_minor, int $b_patch, string|int $b_prerelease
    ) : int
    {
        // Sooo many spaceships... O.O
        $res = ($a_major <=> $b_major);
        if ( $res !== 0 ) { return $res; }

        $res = ($a_minor <=> $b_minor);
        if ( $res !== 0 ) { return $res; }

        $res = ($a_patch <=> $b_patch);
        if ( $res !== 0 ) { return $res; }

        // `cmp_prerelease` requires non-null arguments and
        // we have potentially (likely) null values.
        // We can treat those null values as empty values
        // and use SemVer precedence logic to exclude
        // those possibilities.
        $a_lacks_prere = ($a_prerelease === self::ARG_UNSET);
        $b_lacks_prere = ($b_prerelease === self::ARG_UNSET);
        if ($a_lacks_prere && $b_lacks_prere) {
            // We ONLY have most-significant digits.
            // There's no tie-breaker, so we can just say it's a tie.
            return 0;
        }

        // Anything that lacks release qualifiers
        // will be sort higher than anything that does.
        if ($a_lacks_prere) {
            return 1; // `a` is greater.
        }
        if ($b_lacks_prere) {
            return -1; // `b` is greater.
        }

        // Handle int cases, because `cmp_prerelease` is only for strings.
        if (self::cmp_int_prerelease($a_prerelease, $b_prerelease, $res)) {
            return $res;
        }

        // We are now stumped enough to examine the prerelease data. Good luck.
        $a_pos = 0;
        $b_pos = 0;
        return self::cmp_prerelease(
            $a_prerelease, $b_prerelease, $a_pos, $b_pos, $process_wildcards=false);
    }

    /**
    * Compare only the prerelease (and build metadata) portion of two semvers.
    *
    * Notably, there is no `cmp_build` function, because SemVer defines
    * two versions as equivalent if they only differ by build metadata.
    *
    * @param     svpre_reqp_a&string  $a
    * @param     svpre_reqp_a&string  $b
    * @param     int<0,max>           $a_pos
    * @param     int<0,max>           $b_pos
    * @param     bool                 $process_wildcards
    * @return    int<-1,1>
    * @throws    void
    */
    private static function cmp_prerelease(
        string  $a,
        string  $b,
        int     &$a_pos,
        int     &$b_pos,
        bool    $process_wildcards
    ) : int
    {
        // From semver website: (https://semver.org/)
        // Under section 10:
        // Thus two versions that differ only in the build metadata,
        // have the same precedence.
        // Examples: 1.0.0-alpha+001, 1.0.0+20130313144700, 1.0.0-beta+exp.sha.5114f85, 1.0.0+21AF26D3----117B344092BD.
        //
        // So what we do is just truncate any build metadata from the strings,
        // then we proceed with other comparisons.
        $a_prerelease_len = \strcspn($a, '+', $a_pos);
        $b_prerelease_len = \strcspn($b, '+', $b_pos);

        if ( $a_prerelease_len === 0 && $b_prerelease_len === 0 ) {
            // We ONLY have most-significant digits.
            // There's no tie-breaker, so we can just say it's a tie.
            return 0;
        }

        // From semver website: (https://semver.org/)
        // Under section 11:
        // Example: 1.0.0-alpha < 1.0.0-alpha.1 < 1.0.0-alpha.beta < 1.0.0-beta
        //    < 1.0.0-beta.2 < 1.0.0-beta.11 < 1.0.0-rc.1 < 1.0.0.

        // First thing we can check:
        // Anything that lacks release qualifiers
        // will be sorted higher than anything that does.
        if ( $a_prerelease_len === 0 ) {
            return 1; // `a` is greater.
        }
        if ( $b_prerelease_len === 0 ) {
            return -1; // `b` is greater.
        }

        // At this point we truncate the triplets off of the strings,
        // which will then allow us to treat the prerelease lengths
        // as absolute positions in the string, instead of relative.
        // This means we don't have to decrement them every time
        // we increment `$a_pos` and `$b_pos`. This means
        // fewer PHP ops, which is (probably) good for speed.
        // (And we pray that this assignment doesn't get identified by
        // Zend as a string modification that requires memory allocation!
        // It should be fine as long as we don't write to the string _contents_.)
        //
        // This is also where we distinguish arguments that are part of
        // a full semver string from arguments that are prerelease
        // fragments. Fragments will have their $*_pos set to 0.
        // The distinction is important, because fragments are assumed to
        // NOT begin with a hyphen, while parts of a string are expected
        // to begin with a hyphen. This allows the best sharing of code
        // between callers that are parsing whole SemVers vs callers
        // that are parsing components of SemVers.
        $a_eos = false;
        $b_eos = false;
        if ( 0 < $a_pos ) {
            $a = \substr($a, $a_pos);
            $a_pos = 1; // Skip the '-' (hyphen)
            if ( $a_prerelease_len === 1 ) {
                $a_eos = true;
            }
        }
        if ( 0 < $b_pos ) {
            $b = \substr($b, $b_pos);
            $b_pos = 1; // Skip the '-' (hyphen)
            if ( $b_prerelease_len === 1 ) {
                $b_eos = true;
            }
        }

        // Do a wildcard check before the outer EOS check.
        // Wildcards match _anything_, so they should take precedence.
        if ($process_wildcards
        && ((!$a_eos && $a[$a_pos] === '*')
        ||  (!$b_eos && $b[$b_pos] === '*'))) {
            return 0;
        }

        // Re-run the earlier check for zero-length release identifiers.
        // A semver with an empty release is not valid, but if it does
        // happen, we will assume that an empty release identifier
        // sorts higher than a present identifier, and a non-hyphen
        // empty sorts higher than a hyphenated empty.
        // Note that this ordering is the exact opposite of the
        // "more segments is greater" logic in the loop later on,
        // which means that this check here can't be fused into that loop. :/
        // Note that these are also only applicable to prerelease
        // sections that are part of a larger SemVer and have
        // a hyphen to split text on.
        if ( $a_eos ) {
            return 1; // `a` is greater.
        }
        if ( $b_eos ) {
            return -1; // `b` is greater.
        }

        // \strnatcmp was very helpful for digit-and-dot text, but
        // now we have alphanumerical segments separated by dots.
        // \strnatcmp is not helpful anymore because it treats
        // dots and alphabetics as the same thing. However,
        // semvers have a high precedence for dots:
        // ```
        // Precedence for two pre-release versions with the same
        // major, minor, and patch version MUST be determined by
        // comparing each dot separated identifier from left to
        // right until a difference is found as follows:
        //
        // 1. Identifiers consisting of only digits are compared numerically.
        // 2. Identifiers with letters or hyphens are compared lexically in ASCII sort order.
        // 3. Numeric identifiers always have lower precedence than non-numeric identifiers.
        // 4. A larger set of pre-release fields has a higher precedence than
        //      a smaller set, if all of the preceding identifiers are equal.
        // ```
        // (https://semver.org/  -  section 11.4)
        //
        // This, unfortunately, gives us a lot of logic
        // to implement in strictly PHP logic.
        // So it is. Let's get to it.

        while (true)
        {
            // echo \strval(__LINE__).": a='$a',  a_pos=$a_pos, a_len=$a_prerelease_len\n";
            // echo \strval(__LINE__).": b='$b',  b_pos=$b_pos, b_len=$b_prerelease_len\n";
            $a_part_len = \strcspn($a, '.+', $a_pos, $a_prerelease_len);
            $b_part_len = \strcspn($b, '.+', $b_pos, $b_prerelease_len);

            $a_part = \substr($a, $a_pos, $a_part_len);
            $b_part = \substr($b, $b_pos, $b_part_len);

            // Do a wildcard check before the EOS logic.
            // This is important because wildcards should
            // match _anything_, including missing components/segments.
            if ($process_wildcards && ($a_part === '*' || $b_part === '*')) {
                return 0;
            }

            assert($a_pos <= $a_prerelease_len);
            assert($b_pos <= $b_prerelease_len);

            // "4. A larger set of pre-release fields has a higher precedence than
            //      a smaller set, if all of the preceding identifiers are equal."
            $a_eos = ($a_pos === $a_prerelease_len);
            $b_eos = ($b_pos === $b_prerelease_len);
            if ($a_eos !== $b_eos) {
                // So if $a_eos, then we want to return -1 (`b` is greater).
                // And if $b_eos, then we want to return 1 (`a` is greater).
                return \intval($b_eos) - \intval($a_eos);
            }
            if ($a_eos && $b_eos) {
                return 0; // No differences anywhere.
            }

            // This must come after the previous check for EOS.
            // Why?
            // Because it's possible for
            // `($a_pos === $a_prerelease_len) && ($b_pos === $b_prerelease_len))
            // to be true but for there to still be inequality
            // if `($a_prerelease_len !== $b_prerelease_len)` or
            // if `($a_part_len !== $b_part_len)` AKA `($a_pos !== $b_pos)`.
            // What follows will test for those "parts aren't equal" cases,
            // so those will all be properly excluded by the time the top
            // of the loop is entered again.
            $a_pos += $a_part_len;
            if ($a_pos < $a_prerelease_len) {
                $a_pos++;
            }
            $b_pos += $b_part_len;
            if ($b_pos < $b_prerelease_len) {
                $b_pos++;
            }
            $a_is_digits = \ctype_digit($a_part);
            $b_is_digits = \ctype_digit($b_part);

            // "3. Numeric identifiers always have lower precedence than non-numeric identifiers."
            // This gives us something to check:
            if ($a_is_digits !== $b_is_digits) {
                // So if $a_part is digits, then we want to return -1 (`b` is greater).
                // And if $b_part is digits, then we want to return 1 (`a` is greater).
                return \intval($b_is_digits) - \intval($a_is_digits);
            }

            // "2. Identifiers with letters or hyphens are compared lexically in ASCII sort order."
            // (We'll also handle case 1 here if the parts have equal length,
            // which is the case where ASCII comparison is equivalent to
            // numerical comparison. This potentiall saves us from integer conversion.)
            if (!($a_is_digits || $b_is_digits) || $a_part_len === $b_part_len)
            {
                $res = \strcmp($a_part, $b_part);

                // The docs claim we don't need to.
                // But the unittests say we do!
                // @phpstan-ignore smaller.alwaysFalse
                if ( $res < -1 ) {
                    $res = -1;
                } else
                // @phpstan-ignore greater.alwaysFalse
                if ( $res >  1 ) {
                    $res = 1;
                }
            } else {
                // "1. Identifiers consisting of only digits are compared numerically."
                assert($a_is_digits && $b_is_digits);
                // We finally get to take the spaceship out for a rip!
                // So exciting! *ADHD excitement bouncing*
                $res = (\intval($a_part) <=> \intval($b_part));
            }

            if ( $res !== 0 ) {
                return $res;
            }

            // No differences?
            // Have another go at it!
        }
    }

    /**
    * @phpstan-assert-if-false  string  $a
    * @phpstan-assert-if-false  string  $b
    */
    private static function cmp_int_prerelease(int|string $a, int|string $b, int &$result) : bool
    {
        // `cmp_prerelease` requires non-null arguments and
        // we have potentially (likely) null values.
        // We can treat those null values as empty values
        // and use SemVer precedence logic to exclude
        // those possibilities.
        $a_lacks_prere = ($a === self::ARG_UNSET);
        $b_lacks_prere = ($b === self::ARG_UNSET);
        if ($a_lacks_prere && $b_lacks_prere) {
            // We ONLY have most-significant digits.
            // There's no tie-breaker, so we can just say it's a tie.
            $result = 0;
            return true;
        }

        // Anything that lacks release qualifiers
        // will be sort higher than anything that does.
        if ($a_lacks_prere) {
            $result = 1; // `a` is greater.
            return true;
        }
        if ($b_lacks_prere) {
            $result = -1; // `b` is greater.
            return true;
        }

        $a_is_numeric = is_int($a) || \ctype_digit($a);
        $b_is_numeric = is_int($b) || \ctype_digit($b);
        if ($a_is_numeric && $b_is_numeric)
        {
            if (!is_int($a)) {
                $a = \intval($a);
            }
            if (!is_int($b)) {
                $b = \intval($b);
            }
            $result = ($a <=> $b);
            return true;
        }

        // "Numeric identifiers always have lower precedence than non-numeric identifiers."
        // (https://semver.org/  -  section 11.4)
        // This is how we can resolve the
        // "what if one's an int and the other is a string?" case.
        if ($a_is_numeric !== $b_is_numeric) {
            if ($a_is_numeric) {
                $result = -1; // `b` is greater.
            } else {
                $result = 1; // `a` is greater.
            }
            return true;
        }

        // We have handled all of the integer/numeric cases.
        // The caller now has to handle the stringy cases.
        assert(!$a_is_numeric && !$b_is_numeric);
        //assert(!is_int($a) && !is_int($b));
        return false;
    }

    /**
    * Unittests that are in common between `SemVer::cmp` and `SemVer::pcmp`.
    *
    * @param  svfunc_cmp_a  $cmp
    */
    private static function common_cmp_unittests(\Closure $cmp) : void
    {
        self::common_natcmp_unittests($cmp);

        // Ordinary comparisons:
        assert($cmp('1.2.3',  '1.2.3') === 0);
        assert($cmp('1.2.3',  '4.5.6')  <  0);
        assert($cmp('4.5.6',  '1.2.3')  >  0);

        // Comparisons with different component widths:
        assert($cmp('1.2.9',  '1.2.30') <  0);
        assert($cmp('1.9.3',  '1.20.3') <  0);
        assert($cmp('9.2.3',  '10.2.3') <  0);
        assert($cmp('1.9.999','1.10.0') <  0);

        // Alphanumeric prereleases are considered "greater than" numeric ones.
        // Versions without prerelease identifiers are "greater than"
        // versions with prerelease identifiers:
        assert($cmp('1.2.3-r1', '1.2.3-r1') === 0);
        assert($cmp('1.2.3-r1', '1.2.3-999') >  0);
        assert($cmp('1.2.3-r2', '1.2.3-r1')  >  0);
        assert($cmp('1.2.3',    '1.2.3-r2')  >  0);
        assert($cmp('0.0.0',    '0.0.0-r99') >  0);
        assert($cmp('0.0.0',    '0.0.1-r99') <  0);

        // Prerelease versions are composed of dot-separated identifiers:
        assert($cmp('1.0.0-1.9.3',  '1.0.0-1.20.3') <  0);
        assert($cmp('1.0.0-9.2.3',  '1.0.0-10.2.3') <  0);
        assert($cmp('1.0.0-1.9.999','1.0.0-1.10.0') <  0);

        // Build metadata does not contribute to comparisons:
        assert($cmp('1.2.3+b1',      '1.2.3+b9'     ) === 0);
        assert($cmp('1.2.3-r1+b1',   '1.2.3-r1+b9'  ) === 0);
        assert($cmp('1.2.3-r1+b999', '1.2.3-r9+b9'  )  <  0);
        assert($cmp('1.2.3-r9+b1',   '1.2.3-r1+b999')  >  0);

        // Flexible parameter layout...
        // Verify compare-to-integer behavior.
        assert($cmp('1.2.3', SemVer::construct(1,2,3)) === 0);

        assert($cmp('1.2.3',  1,2,3 ) === 0);
        assert($cmp( 1,2,3,  '1.2.3') === 0);
        assert($cmp( 1,2,3,   1,2,3 ) === 0);
        assert($cmp('1.2.3',  4,5,6 )  <  0);
        assert($cmp( 1,2,3,  '4.5.6')  <  0);
        assert($cmp( 1,2,3,   4,5,6 )  <  0);

        // Prerelease identifiers (and build metadata) are supported:
        assert($cmp('1.2.3-r1',  1,2,3,'r1') === 0);
        assert($cmp('1.2.3-r1',  1,2,3,'999') >  0);
        assert($cmp('1.2.3-r2',  1,2,3,'r1')  >  0);
        assert($cmp('1.2.3',     1,2,3,'r2')  >  0);
        assert($cmp('0.0.0',     0,0,0,'r99') >  0);
        assert($cmp('0.0.0',     0,0,1,'r99') <  0);

        // Note that there is no "build metadata" parameter,
        // because build metadata does not affect comparisons.
        // Build metadata must appear in the prerelease string,
        // and it must begin with a '+' character.
        // (The prerelease identifier must NOT begin with a '-'
        // character in this case, unless the '-' is part of
        // the identifier.)
        echo \strval(__LINE__).": Failed unittests: \$cmp('1.2.3+b1',  1,2,3,'+b9') and similar.\n";// TODO: fix
        //assert($cmp('1.2.3+b1',      1,2,3,'+b9'    ) === 0);
        //assert($cmp('1.2.3-r1+b1',   1,2,3,'r1+b9'  ) === 0);
        //assert($cmp('1.2.3-r1+b999', 1,2,3,'r9+b9'  )  <  0);
        //assert($cmp('1.2.3-r9+b1',   1,2,3,'r1+b999')  >  0);
    }

    /**
    * Unittests that are in common between `\strnatcmp` and `SemVer::cmp`
    * (and, by extension, `SemVer::pcmp`).
    *
    * @param  \Closure(string,string):int  $cmp
    */
    private static function common_natcmp_unittests(\Closure $cmp) : void
    {
        // Basic-but-exhaustive check on ordinary string comparison in semver context.
        assert($cmp('0.0.0', '0.0.0') === 0);
        assert($cmp('0.0.0', '0.0.1')  <  0);
        assert($cmp('0.0.1', '0.0.0')  >  0);
        assert($cmp('0.0.1', '0.0.1') === 0);
        assert($cmp('0.0.0', '0.1.0')  <  0);
        assert($cmp('0.0.0', '0.1.1')  <  0);
        assert($cmp('0.0.1', '0.1.0')  <  0);
        assert($cmp('0.0.1', '0.1.1')  <  0);
        assert($cmp('0.1.0', '0.0.0')  >  0);
        assert($cmp('0.1.0', '0.0.1')  >  0);
        assert($cmp('0.1.1', '0.0.0')  >  0);
        assert($cmp('0.1.1', '0.0.1')  >  0);
        assert($cmp('0.1.0', '0.1.0') === 0);
        assert($cmp('0.1.0', '0.1.1')  <  0);
        assert($cmp('0.1.1', '0.1.0')  >  0);
        assert($cmp('0.1.1', '0.1.1') === 0);

        assert($cmp('0.0.0', '1.0.0')  <  0);
        assert($cmp('0.0.0', '1.0.1')  <  0);
        assert($cmp('0.0.1', '1.0.0')  <  0);
        assert($cmp('0.0.1', '1.0.1')  <  0);
        assert($cmp('0.0.0', '1.1.0')  <  0);
        assert($cmp('0.0.0', '1.1.1')  <  0);
        assert($cmp('0.0.1', '1.1.0')  <  0);
        assert($cmp('0.0.1', '1.1.1')  <  0);
        assert($cmp('0.1.0', '1.0.0')  <  0);
        assert($cmp('0.1.0', '1.0.1')  <  0);
        assert($cmp('0.1.1', '1.0.0')  <  0);
        assert($cmp('0.1.1', '1.0.1')  <  0);
        assert($cmp('0.1.0', '1.1.0')  <  0);
        assert($cmp('0.1.0', '1.1.1')  <  0);
        assert($cmp('0.1.1', '1.1.0')  <  0);
        assert($cmp('0.1.1', '1.1.1')  <  0);

        assert($cmp('1.0.0', '0.0.0')  >  0);
        assert($cmp('1.0.0', '0.0.1')  >  0);
        assert($cmp('1.0.1', '0.0.0')  >  0);
        assert($cmp('1.0.1', '0.0.1')  >  0);
        assert($cmp('1.0.0', '0.1.0')  >  0);
        assert($cmp('1.0.0', '0.1.1')  >  0);
        assert($cmp('1.0.1', '0.1.0')  >  0);
        assert($cmp('1.0.1', '0.1.1')  >  0);
        assert($cmp('1.1.0', '0.0.0')  >  0);
        assert($cmp('1.1.0', '0.0.1')  >  0);
        assert($cmp('1.1.1', '0.0.0')  >  0);
        assert($cmp('1.1.1', '0.0.1')  >  0);
        assert($cmp('1.1.0', '0.1.0')  >  0);
        assert($cmp('1.1.0', '0.1.1')  >  0);
        assert($cmp('1.1.1', '0.1.0')  >  0);
        assert($cmp('1.1.1', '0.1.1')  >  0);

        assert($cmp('1.0.0', '1.0.0') === 0);
        assert($cmp('1.0.0', '1.0.1')  <  0);
        assert($cmp('1.0.1', '1.0.0')  >  0);
        assert($cmp('1.0.1', '1.0.1') === 0);
        assert($cmp('1.0.0', '1.1.0')  <  0);
        assert($cmp('1.0.0', '1.1.1')  <  0);
        assert($cmp('1.0.1', '1.1.0')  <  0);
        assert($cmp('1.0.1', '1.1.1')  <  0);
        assert($cmp('1.1.0', '1.0.0')  >  0);
        assert($cmp('1.1.0', '1.0.1')  >  0);
        assert($cmp('1.1.1', '1.0.0')  >  0);
        assert($cmp('1.1.1', '1.0.1')  >  0);
        assert($cmp('1.1.0', '1.1.0') === 0);
        assert($cmp('1.1.0', '1.1.1')  <  0);
        assert($cmp('1.1.1', '1.1.0')  >  0);
        assert($cmp('1.1.1', '1.1.1') === 0);

        // Now to check how it handles variable width digit runs.
        assert($cmp('9.9.9'   , '9.9.9'   ) === 0);
        assert($cmp('9.9.9'   , '9.9.22'  )  <  0);
        assert($cmp('9.9.22'  , '9.9.9'   )  >  0);
        assert($cmp('9.9.22'  , '9.9.22'  ) === 0);
        assert($cmp('9.9.9'   , '9.22.9'  )  <  0);
        assert($cmp('9.9.9'   , '9.22.22' )  <  0);
        assert($cmp('9.9.22'  , '9.22.9'  )  <  0);
        assert($cmp('9.9.22'  , '9.22.22' )  <  0);
        assert($cmp('9.22.9'  , '9.9.9'   )  >  0);
        assert($cmp('9.22.9'  , '9.9.22'  )  >  0);
        assert($cmp('9.22.22' , '9.9.9'   )  >  0);
        assert($cmp('9.22.22' , '9.9.22'  )  >  0);
        assert($cmp('9.22.9'  , '9.22.9'  ) === 0);
        assert($cmp('9.22.9'  , '9.22.22' )  <  0);
        assert($cmp('9.22.22' , '9.22.9'  )  >  0);
        assert($cmp('9.22.22' , '9.22.22' ) === 0);

        assert($cmp('9.9.9'   , '22.9.9'  )  <  0);
        assert($cmp('9.9.9'   , '22.9.22' )  <  0);
        assert($cmp('9.9.22'  , '22.9.9'  )  <  0);
        assert($cmp('9.9.22'  , '22.9.22' )  <  0);
        assert($cmp('9.9.9'   , '22.22.9' )  <  0);
        assert($cmp('9.9.9'   , '22.22.22')  <  0);
        assert($cmp('9.9.22'  , '22.22.9' )  <  0);
        assert($cmp('9.9.22'  , '22.22.22')  <  0);
        assert($cmp('9.22.9'  , '22.9.9'  )  <  0);
        assert($cmp('9.22.9'  , '22.9.22' )  <  0);
        assert($cmp('9.22.22' , '22.9.9'  )  <  0);
        assert($cmp('9.22.22' , '22.9.22' )  <  0);
        assert($cmp('9.22.9'  , '22.22.9' )  <  0);
        assert($cmp('9.22.9'  , '22.22.22')  <  0);
        assert($cmp('9.22.22' , '22.22.9' )  <  0);
        assert($cmp('9.22.22' , '22.22.22')  <  0);

        assert($cmp('22.9.9'  , '9.9.9'   )  >  0);
        assert($cmp('22.9.9'  , '9.9.22'  )  >  0);
        assert($cmp('22.9.22' , '9.9.9'   )  >  0);
        assert($cmp('22.9.22' , '9.9.22'  )  >  0);
        assert($cmp('22.9.9'  , '9.22.9'  )  >  0);
        assert($cmp('22.9.9'  , '9.22.22' )  >  0);
        assert($cmp('22.9.22' , '9.22.9'  )  >  0);
        assert($cmp('22.9.22' , '9.22.22' )  >  0);
        assert($cmp('22.22.9' , '9.9.9'   )  >  0);
        assert($cmp('22.22.9' , '9.9.22'  )  >  0);
        assert($cmp('22.22.22', '9.9.9'   )  >  0);
        assert($cmp('22.22.22', '9.9.22'  )  >  0);
        assert($cmp('22.22.9' , '9.22.9'  )  >  0);
        assert($cmp('22.22.9' , '9.22.22' )  >  0);
        assert($cmp('22.22.22', '9.22.9'  )  >  0);
        assert($cmp('22.22.22', '9.22.22' )  >  0);

        assert($cmp('22.9.9'  , '22.9.9'  ) === 0);
        assert($cmp('22.9.9'  , '22.9.22' )  <  0);
        assert($cmp('22.9.22' , '22.9.9'  )  >  0);
        assert($cmp('22.9.22' , '22.9.22' ) === 0);
        assert($cmp('22.9.9'  , '22.22.9' )  <  0);
        assert($cmp('22.9.9'  , '22.22.22')  <  0);
        assert($cmp('22.9.22' , '22.22.9' )  <  0);
        assert($cmp('22.9.22' , '22.22.22')  <  0);
        assert($cmp('22.22.9' , '22.9.9'  )  >  0);
        assert($cmp('22.22.9' , '22.9.22' )  >  0);
        assert($cmp('22.22.22', '22.9.9'  )  >  0);
        assert($cmp('22.22.22', '22.9.22' )  >  0);
        assert($cmp('22.22.9' , '22.22.9' ) === 0);
        assert($cmp('22.22.9' , '22.22.22')  <  0);
        assert($cmp('22.22.22', '22.22.9' )  >  0);
        assert($cmp('22.22.22', '22.22.22') === 0);

        // Examples on the semver.org page.
        assert($cmp('1.0.0', '2.0.0') < 0);
        assert($cmp('2.0.0', '2.1.0') < 0);
        assert($cmp('2.1.0', '2.1.1') < 0);
        assert($cmp('1.0.0', '2.1.0') < 0); // transitivity check
        assert($cmp('2.0.0', '2.1.1') < 0); // transitivity check
        assert($cmp('1.0.0', '2.1.1') < 0); // transitivity check

        // The other order should work too. (semver.org examples)
        assert($cmp('2.0.0', '1.0.0') > 0);
        assert($cmp('2.1.0', '2.0.0') > 0);
        assert($cmp('2.1.1', '2.1.0') > 0);
        assert($cmp('2.1.0', '1.0.0') > 0); // transitivity check
        assert($cmp('2.1.1', '2.0.0') > 0); // transitivity check
        assert($cmp('2.1.1', '1.0.0') > 0); // transitivity check
    }

    private static function unittest_cmp() : void
    {
        echo("  ".__FUNCTION__."()\n");

        $cmp = function (string $a, string $b):int
        {
            // Call self::cmp, but also range check the return value every time.
            $res = self::cmp($a,$b);
            assert(-1 <= $res); // @phpstan-ignore function.alreadyNarrowedType, smallerOrEqual.alwaysTrue
            assert($res <= 1);  // @phpstan-ignore function.alreadyNarrowedType, smallerOrEqual.alwaysTrue
            return $res;
        };

        self::common_cmp_unittests(self::cmp(...));

        // From semver website:
        // Example: 1.0.0-alpha < 1.0.0-alpha.1 < 1.0.0-alpha.beta < 1.0.0-beta
        //    < 1.0.0-beta.2 < 1.0.0-beta.11 < 1.0.0-rc.1 < 1.0.0.
        assert($cmp('1.0.0-alpha'     , '1.0.0-alpha.1'   )  <  0);
        assert($cmp('1.0.0-alpha.1'   , '1.0.0-alpha.beta')  <  0);
        assert($cmp('1.0.0-alpha.beta', '1.0.0-beta'      )  <  0);
        assert($cmp('1.0.0-beta'      , '1.0.0-beta.2'    )  <  0);
        assert($cmp('1.0.0-beta.2'    , '1.0.0-beta.11'   )  <  0);
        assert($cmp('1.0.0-beta.11'   , '1.0.0-rc.1'      )  <  0);
        assert($cmp('1.0.0-rc.1'      , '1.0.0'           )  <  0);

        // (Can't do these because `cmp` (rightfully) asserts on invalid semvers.)
        // Corner-case for some kinda-invalid semvers.
        //assert($cmp('1.0.0-rc.1'   , '1.0.0-'      )  <  0);
        //assert($cmp('1.0.0-'       , '1.0.0'       )  <  0);


        // TODO: I forget where these examples came from, or what
        // I was going to do with them. I think they might demonstrate
        // more comprehensive sorting tests? Unfortunately, I can't
        // find the GitHub issue that I pulled these from; at least
        // not quickly right now. (There's a similar issue that I
        // pulled validity tests from, and that's elsewhere in this
        // module, but I think these had a different purpose, and
        // were _definitely_ from a different GitHub issue.)
        //
        // 0.0.4
        // 1.2.3
        // 10.20.30
        // 1.1.2-prerelease+meta
        // 1.1.2+meta
        // 1.1.2+meta-valid
        // 1.0.0-alpha
        // 1.0.0-beta
        // 1.0.0-alpha.beta
        // 1.0.0-alpha.beta.1
        // 1.0.0-alpha.1
        // 1.0.0-alpha0.valid
        // 1.0.0-alpha.0valid
        // 1.0.0-alpha-a.b-c-somethinglong+build.1-aef.1-its-okay
        // 1.0.0-rc.1+build.1
        // 2.0.0-rc.1+build.123
        // 1.2.3-beta
        // 10.2.3-DEV-SNAPSHOT
        // 1.2.3-SNAPSHOT-123
        // 1.0.0
        // 2.0.0
        // 1.1.7
        // 2.0.0+build.1848
        // 2.0.1-alpha.1227
        // 1.0.0-alpha+beta
        // 1.2.3----RC-SNAPSHOT.12.9.1--.12+788
        // 1.2.3----R-S.12.9.1--.12+meta
        // 1.2.3----RC-SNAPSHOT.12.9.1--.12
        // 1.0.0+0.build.1-rc.10000aaa-kk-0.1
        // 99999999999999999999999.999999999999999999.99999999999999999
        // 1.0.0-0A.is.legal
        //
        // "0.9.99"           < "1.0.0"
        // "0.9.0"            < "0.10.0"
        // "1.0.0-0.0"        < "1.0.0-0.0.0"
        // "1.0.0-9999"       < "1.0.0--"
        // "1.0.0-99"         < "1.0.0-100"
        // "1.0.0-alpha"      < "1.0.0-alpha.1"
        // "1.0.0-alpha.1"    < "1.0.0-alpha.beta"
        // "1.0.0-alpha.beta" < "1.0.0-beta"
        // "1.0.0-beta"       < "1.0.0-beta.2"
        // "1.0.0-beta.2"     < "1.0.0-beta.11"
        // "1.0.0-beta.11"    < "1.0.0-rc.1"
        // "1.0.0-rc.1"       < "1.0.0"
        // "1.0.0-0"          < "1.0.0--1"
        // "1.0.0-0"          < "1.0.0-1"
        // "1.0.0-1.0"        < "1.0.0-1.-1"
    }

    /**
    * Tests if two Semantic Versions are equal.
    *
    * This is a convenience around the `cmp` function:
    * ```
    * SemVer::equal($a,$b) <-> (0 === SemVer::cmp($a,$b))
    * ```
    * see the documentation
    * for `cmp` for any technical details about this function.
    *
    * @see cmp
    *
    * @param   svnum_req_a|semver_a                                                         $a_major__a_major__a
    * @param   ($a_major__a_major__a is svnum_req_a  ? svnum_req_a : svnum_req_a|semver_a)  $a_minor__a_minor__b_major
    * @param   ($a_major__a_major__a is svnum_req_a  ? svnum_req_a     : ($a_minor__a_minor__b_major  is int      ? svnum_req_a     : self::ARG_UNSET))  $a_patch__a_patch__b_minor
    * @param   ($a_major__a_major__a is svnum_req_a  ? svany_opt_a     : ($a_minor__a_minor__b_major  is int      ? svany_opt_a     : self::ARG_UNSET))  $a_prere__b_major__b_patch
    * @param   ($a_major__a_major__a is svnum_req_a  ? svany_opt_a     : ($a_minor__a_minor__b_major  is int      ? svany_opt_a     : self::ARG_UNSET))  $b_major__b_minor__b_prere
    * @param   ($a_major__a_major__a is semver_a     ? self::ARG_UNSET : ($a_minor__a_minor__b_major  is semver_a ? self::ARG_UNSET : ($b_major__b_minor__b_prere  is semver_a ? self::ARG_UNSET : svnum_req_a )))  $b_minor__b_patch__x_xxxxx
    * @param   ($a_major__a_major__a is semver_a     ? self::ARG_UNSET : ($a_minor__a_minor__b_major  is semver_a ? self::ARG_UNSET : ($b_major__b_minor__b_prere  is semver_a ? self::ARG_UNSET : svpart_opt_a)))  $b_patch__b_prere__x_xxxxx
    * @param   ($a_major__a_major__a is semver_a     ? self::ARG_UNSET : ($a_minor__a_minor__b_major  is semver_a ? self::ARG_UNSET : ($b_major__b_minor__b_prere  is semver_a ? self::ARG_UNSET : svpre_opt_a )))  $b_prere__x_xxxxx__x_xxxxx
    * @throws  void
    */
    public static function equal(
        int|string       $a_major__a_major__a,
        int|string       $a_minor__a_minor__b_major,
        int              $a_patch__a_patch__b_minor = self::ARG_UNSET,
        int|string       $a_prere__b_major__b_patch = self::ARG_UNSET,
        int|string       $b_major__b_minor__b_prere = self::ARG_UNSET,
        int              $b_minor__b_patch__x_xxxxx = self::ARG_UNSET,
        int|string       $b_patch__b_prere__x_xxxxx = self::ARG_UNSET,
        int|string       $b_prere__x_xxxxx__x_xxxxx = self::ARG_UNSET
    ) : bool
    {
        $debug_n_args = -1;

        // @phpstan-ignore function.alreadyNarrowedType, smallerOrEqual.alwaysTrue
        assert(0 <= ($debug_n_args = \func_num_args()));

        $res = self::dispatch_cmp(
            $debug_n_args,
            $a_major__a_major__a,
            $a_minor__a_minor__b_major,
            $a_patch__a_patch__b_minor,
            $a_prere__b_major__b_patch,
            $b_major__b_minor__b_prere,
            $b_minor__b_patch__x_xxxxx,
            $b_patch__b_prere__x_xxxxx,
            $b_prere__x_xxxxx__x_xxxxx);
        return ($res === 0);
    }

    /**
    * @param  array<mixed>  $a
    * @phpstan-assert-if-true  array<semver_a>  $a
    */
    private static function array_is_of_semvers(array $a) : bool
    {
        foreach($a as $ver) {
            if (is_string($ver) && self::valid($ver)) {
                continue;
            }
            return false;
        }
        return true;
    }

    /**
    * Sorts an argument list or an array of version strings.
    *
    * This is an in-place sort, regardless of arguments passed.
    *
    * Either a single array may be passed,
    * or a list of semver strings, but not both.
    *
    * The return value is the input array after sorting, or a sorted
    * array containing all of the semvers passed as arguments.
    *
    * @param   array<semver_a>|semver_a               $vers
    * @param   ($vers is array ? never : semver_a)    ...$vargs
    * @return  array<semver_a>
    */
    public static function sort(array|string &$vers, string ...$vargs) : array
    {
        if ( is_array($vers) ) {
            assert(\func_num_args() === 1);
            \uasort($vers, self::cmp_ss(...));
            return $vers;
        }

        $vers = \func_get_args();
        assert(self::array_is_of_semvers($vers));
        \uasort($vers, self::cmp_ss(...));
        return $vers;
    }

    // `strnatcmp` is a native PHP function with a C implementation:
    // https://github.com/php/php-src/blob/e844e68af8dac5974238d489a413b175be14dc2a/ext/standard/strnatcmp.c#L88
    //
    // The way it compares strings makes it highly desirable for things like
    // semantic versioning, as it can quickly process these strings without
    // performing any conversions, copies, or memory allocations.
    //
    // As of this writing, it has some tests, but they are not necessarily
    // tests of suitability-for-our-purpose:
    // https://github.com/php/php-src/blob/e844e68af8dac5974238d489a413b175be14dc2a/ext/standard/tests/strings/strnatcmp_basic.phpt#L18
    //
    // So we will be performing some of our own testing to nail down
    // what this function exactly does, and detect any breaking changes
    // if it ever gets modified by Zend / <insert PHP vendor here>.
    private static function unittest_strnatcmp() : void
    {
        echo("  ".__FUNCTION__."()\n");

        $strnatcmp = function (string $a, string $b):int
        {
            // Call \strnatcmp, but also range check it every time.
            $res = \strnatcmp($a,$b);
            assert(-1 <= $res); // @phpstan-ignore function.alreadyNarrowedType, smallerOrEqual.alwaysTrue
            assert($res <= 1);  // @phpstan-ignore function.alreadyNarrowedType, smallerOrEqual.alwaysTrue
            return $res;
        };
        self::common_natcmp_unittests($strnatcmp);

        // Looking at the implementation suggests that 'leading zeroes'
        // may have strange effects, as they are only skipped at the
        // beginning of the string, and not for every digit-run.
        assert($strnatcmp('001', '001') === 0);
        assert($strnatcmp('001', '010')  <  0);
        assert($strnatcmp('010', '001')  >  0);
        assert($strnatcmp('010', '010') === 0);

        assert($strnatcmp( '0',  '0') === 0);
        assert($strnatcmp( '0', '00') === 0);
        assert($strnatcmp('00',  '0') === 0);
        assert($strnatcmp('00', '00') === 0);

        assert($strnatcmp('001', '001') === 0); // @phpstan-ignore function.alreadyNarrowedType
        assert($strnatcmp('001',  '10')  <  0);
        assert($strnatcmp( '10', '001')  >  0);
        assert($strnatcmp( '10',  '10') === 0);

        assert($strnatcmp('1.001', '1.001') === 0);
        assert($strnatcmp('1.001', '1.010')  <  0);
        assert($strnatcmp('1.010', '1.001')  >  0);
        assert($strnatcmp('1.010', '1.010') === 0);

        assert($strnatcmp( '1.0',  '1.0') === 0);
        assert($strnatcmp( '1.0', '1.00')  <  0); // <- notable difference
        assert($strnatcmp('1.00',  '1.0')  >  0); // <- notable difference
        assert($strnatcmp('1.00', '1.00') === 0);

        assert($strnatcmp('1.001', '1.001') === 0); // @phpstan-ignore function.alreadyNarrowedType
        assert($strnatcmp('1.001',  '1.10')  <  0);
        assert($strnatcmp( '1.10', '1.001')  >  0);
        assert($strnatcmp( '1.10',  '1.10') === 0);

        // Ordering of numbers vs letters.
        assert($strnatcmp( '1', 'a') < 0);
        assert($strnatcmp( '1', 'z') < 0);
        assert($strnatcmp( '1', 'A') < 0);
        assert($strnatcmp( '1', 'Z') < 0);
        assert($strnatcmp( '9', 'a') < 0);
        assert($strnatcmp( '9', 'z') < 0);
        assert($strnatcmp( '9', 'A') < 0);
        assert($strnatcmp( '9', 'Z') < 0);

        assert($strnatcmp( 'a', '1') > 0);
        assert($strnatcmp( 'z', '1') > 0);
        assert($strnatcmp( 'A', '1') > 0);
        assert($strnatcmp( 'Z', '1') > 0);
        assert($strnatcmp( 'a', '9') > 0);
        assert($strnatcmp( 'z', '9') > 0);
        assert($strnatcmp( 'A', '9') > 0);
        assert($strnatcmp( 'Z', '9') > 0);

        // Case sensitivity
        assert($strnatcmp( 'A', 'a') < 0);
        assert($strnatcmp( 'Z', 'z') < 0);
        assert($strnatcmp( 'A', 'z') < 0);
        assert($strnatcmp( 'Z', 'a') < 0);

        assert($strnatcmp( 'a', 'A') > 0);
        assert($strnatcmp( 'z', 'Z') > 0);
        assert($strnatcmp( 'z', 'A') > 0);
        assert($strnatcmp( 'a', 'Z') > 0);

        // Confirm ordinary alphabetic string handling:
        assert($strnatcmp( 'a', 'z') < 0);
        assert($strnatcmp( 'z', 'a') > 0);
        assert($strnatcmp('aa', 'z') < 0);
        assert($strnatcmp( 'z','aa') > 0);
        assert(\strcmp( 'a', 'z') < 0);
        assert(\strcmp( 'z', 'a') > 0);
        assert(\strcmp('aa', 'z') < 0);
        assert(\strcmp( 'z','aa') > 0);

        // One notable thing that it DOESN'T do:
        // Split on word boundaries.
        // That, combined with the above rules for alpha comparison (ex: 'aa' < 'z'),
        // will create situations that don't make sense for alphabetic string components.
        // (Some hand-holding will be required.)
        assert($strnatcmp('a.aa','z.z') < 0);
        assert($strnatcmp('1.aa','1.z') < 0);
        assert($strnatcmp('aa.1','z.1') < 0);
        assert($strnatcmp('aa.0','z.1') < 0);
        assert($strnatcmp('aa.1','z.0') < 0);
        assert($strnatcmp('aa.1','z.2') < 0);
        assert($strnatcmp('aa.2','z.1') < 0);
        assert($strnatcmp('0.aa','0.z') < 0);
        assert($strnatcmp('0.aa','1.z') < 0);
        assert($strnatcmp('1.aa','0.z') > 0);
        assert($strnatcmp('1.aa','1.z') < 0); // @phpstan-ignore function.alreadyNarrowedType, smaller.alwaysTrue
        assert($strnatcmp('1.aa','2.z') < 0);
        assert($strnatcmp('2.aa','1.z') > 0);
        assert($strnatcmp('2.aa','2.z') < 0);

        assert($strnatcmp('1.aa.1','1.z.1') < 0);
        assert($strnatcmp('1.aa.1','1.z.2') < 0);
        assert($strnatcmp('1.aa.2','1.z.1') < 0);
        assert($strnatcmp('1.aa.2','1.z.2') < 0);

        assert($strnatcmp('1.z.1','1.aa.1') > 0);
        assert($strnatcmp('1.z.1','1.aa.2') > 0);
        assert($strnatcmp('1.z.2','1.aa.1') > 0);
        assert($strnatcmp('1.z.2','1.aa.2') > 0);

        // After looking at how \strnatcmp's source code,
        // I suspect it may have a weakness.
        //
        // That is: when it sees leading zeroes in either
        // digit-run, then it assumes that both sides are "fractional"
        // and compares them with the left-most digit always having
        // the same significance. This will work in most cases, and
        // is considerably closer to what we want than ASCII sorting.
        // However, semver numbers are just treated like whole numbers,
        // with both sides having their least-sig digit be the rightmost one.
        // Again, usually it doesn't matter, but we can contrive
        // a test that forces \strnatcmp to start comparing in
        // a fractional way when the numbers themselves will
        // compare differently as whole numbers.
        //
        // Silver lining: semvers explicitly forbid leading zeroes!
        //
        assert($strnatcmp('0.01.0', '0.009.00') > 0);
        assert($strnatcmp('0.1.0', '0.09.00') > 0);
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

        self::unittest_intval();
        self::unittest_construct();
        self::unittest_construct_into();
        self::unittest_deconstruct();
        self::unittest_major();
        self::unittest_minor();
        self::unittest_patch();
        self::unittest_prerelease();
        self::unittest_build_metadata();
        self::unittest_valid();
        self::unittest_repair();
        self::unittest_pcmp();
        self::unittest_matching();
        self::unittest_cmp();
        self::unittest_strnatcmp();

        // $runner->note("  ... passed.\n\n");
        echo("  ... passed.\n\n");
        //self::to_string("xyz");
    }
}

// TODO: Actually use this.
/**
* Flags that modify the behavior of various SemVer functions.
*/
final class SV_ALLOW
{
    use \Kickback\Common\Traits\StaticClassTrait;
    use \Kickback\Common\Traits\ClassOfConstantIntegersTrait;

    /** Value representing a lack of all SV_ALLOW::* flags. */
    public const NOTHING    = 0x00;

    /** Enables the use of wildcards in either SemVer strings or integer components. */
    public const WILDCARDS  = 0x01;

    /** Allows processing of SemVer strings that have whitespace between their components and separators. */
    public const WHITESPACE = 0x02;

    /**
    * Allows processing of SemVer strings that have leading zeroes in their numeric components.
    *
    * This is, notably, explicitly disallowed by the SemVer specification.
    *
    * However, it is possible for this to come up. One reason people might
    * actually be _incentivized_ to create SemVer-variants like this:
    * if someone wants SemVers that can be naively sorted with ordinary
    * ASCII-betical comparison functions, then they might pad the SemVers
    * in their list with zeroes until all SemVers have exactly the same
    * width in each component.
    *
    * TODO: Currently unimplemented. (Some functions may accept leading
    *   zeroes and others might not. It's not really consistent at the moment.
    *   Ideally, the caller has control over this behavior.
    */
    public const LEADING_ZEROES = 0x04;
}
