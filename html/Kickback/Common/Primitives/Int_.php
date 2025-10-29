<?php
declare(strict_types=1);

namespace Kickback\Common\Primitives;

/**
* Extended functionality for the `int` type.
*/
final class Int_
{
    use \Kickback\Common\Traits\StaticClassTrait;

    // TODO: These PHPDoc comments should probably be updated to be
    //   in sync with what's in `Mixed_`. I just don't have time for it right now.
    //   (And perhaps later versions of PHPStan could make that logic less nasty...)
    //   -- Chad Joan  2025-07-08
    /**
    * @param      array<array-key, ?int>  $arr
    * @param      ?int          $first_min_index   The lowest 0-based position in the array where the minimum appears.
    * @param-out  int           $first_min_index
    * @param      ?array-key    $first_min_key
    * @param-out  array-key     $first_min_key
    * @param      ?int          $last_min_index    The highest 0-based position in the array where the minimum appears.
    * @param-out  int           $last_min_index
    * @param      ?array-key    $last_min_key
    * @param-out  array-key     $last_min_key
    * @param      ?int          $min_value
    * @param-out  int           $min_value
    *
    * @return (
    *   $arr is non-empty-array<array-key, null> ? false :
    *   $arr is non-empty-array<array-key, ?int> ? true  : false)
    */
    public static function min_pair(
        array $arr,
        ?int &$first_min_index, int|string|null &$first_min_key,
        ?int &$last_min_index,  int|string|null &$last_min_key,
        ?int &$min_value) : bool
    {
        return Mixed_::min_pair($arr,
            $first_min_index, $first_min_key,
            $last_min_index,  $last_min_key,
            $min_value);
    }

    /**
    * @param      array<array-key, ?int>  $arr
    * @param      ?array-key              $min_key
    * @param-out  ?array-key              $min_key
    *
    * @return int  The 0-based position in the array where the minimum occurs.
    */
    public static function first_min_pair(array $arr, int|string|null &$min_key,  int &$min_value) : int
    {
        return Mixed_::first_min_pair($arr, $min_key, $min_value);
    }

    /**
    * @param      array<array-key, ?int>  $arr
    * @param      ?array-key              $min_key
    * @param-out  array-key               $min_key
    *
    * @return int  The 0-based position in the array where the minimum occurs.
    */
    public static function last_min_pair(array $arr, int|string|null &$min_key,  int &$min_value) : int
    {
        return Mixed_::last_min_pair($arr, $min_key, $min_value);
    }

    /**
    * @param      array<array-key, ?int>  $arr
    * @param      ?int          $first_max_index   The lowest 0-based position in the array where the maximum appears.
    * @param-out  int           $first_max_index
    * @param      ?array-key    $first_max_key
    * @param-out  array-key     $first_max_key
    * @param      ?int          $last_max_index    The highest 0-based position in the array where the maximum appears.
    * @param-out  int           $last_max_index
    * @param      ?array-key    $last_max_key
    * @param-out  array-key     $last_max_key
    * @param      ?int          $max_value
    * @param-out  int           $max_value
    *
    * @return (
    *   $arr is non-empty-array<array-key, null> ? false :
    *   $arr is non-empty-array<array-key, ?int> ? true  : false)
    */
    public static function max_pair(
        array $arr,
        ?int &$first_max_index, int|string|null &$first_max_key,
        ?int &$last_max_index,  int|string|null &$last_max_key,
        ?int &$max_value) : bool
    {
        return Mixed_::max_pair($arr,
            $first_max_index, $first_max_key,
            $last_max_index,  $last_max_key,
            $max_value);
    }

    /**
    * @param      array<array-key, ?int>  $arr
    * @param      ?array-key              $max_key
    * @param-out  array-key               $max_key
    *
    * @return int  The 0-based position in the array where the minimum occurs.
    */
    public static function first_max_pair(array $arr, int|string|null &$max_key,  int &$max_value) : int
    {
        return Mixed_::first_max_pair($arr, $max_key, $max_value);
    }

    /**
    * @param      array<array-key, ?int>  $arr
    * @param      ?array-key              $max_key
    * @param-out  array-key               $max_key
    *
    * @return int  The 0-based position in the array where the minimum occurs.
    */
    public static function last_max_pair(array $arr, int|string|null &$max_key,  int &$max_value) : int
    {
        return Mixed_::last_max_pair($arr, $max_key, $max_value);
    }


    private const LOG2_b = [0x2, 0xC, 0xF0, 0xFF00, 0xFFFF0000, 0xFFFFFFFF00000000];
    private const LOG2_S = [1, 2, 4, 8, 16, 32];

    /**
    * Find the _integer_ base-2 logarithm of a value.
    *
    * This version assumes that $v is _unsigned_.
    *
    * PHP provides the \log function which performs a _floating point_
    * logarithm of arbitrary base. This is great if we're doing
    * floating point math, and _not so great_ if we are doing
    * integer math (ex: we want to know how many bits/bytes are required
    * to represent a given integer value).
    *
    * This function fills that role for integers.
    */
    public static function ulog2_floor(int $v) : int
    {
        // This implementation was taken from the Bit Twiddling Hacks page:
        // https://graphics.stanford.edu/~seander/bithacks.html#IntegerLog

        $r = 0; // result of log2(v) will go here
        for ($i = 5; $i >= 0; $i--)
        {
            if (0 !== ($v & self::LOG2_b[$i]))
            {
                $v >>= self::LOG2_S[$i];
                $r |= self::LOG2_S[$i];
            }
        }
        return $r;
    }

    private static function unittest_ulog2_floor() : void
    {
        echo("  ".__FUNCTION__."()\n");

        assert(self::ulog2_floor(0)  === 0);
        assert(self::ulog2_floor(1)  === 0);
        assert(self::ulog2_floor(2)  === 1);
        assert(self::ulog2_floor(3)  === 1);
        assert(self::ulog2_floor(4)  === 2);
        assert(self::ulog2_floor(5)  === 2);
        assert(self::ulog2_floor(7)  === 2);
        assert(self::ulog2_floor(8)  === 3);
        assert(self::ulog2_floor(9)  === 3);
        assert(self::ulog2_floor(15) === 3);
        assert(self::ulog2_floor(16) === 4);
        assert(self::ulog2_floor(17) === 4);
        assert(self::ulog2_floor(31) === 4);
        assert(self::ulog2_floor(32) === 5);
        assert(self::ulog2_floor(33) === 5);
        assert(self::ulog2_floor(64) === 6);
        assert(self::ulog2_floor(128) === 7);
        assert(self::ulog2_floor(255) === 7);
        assert(self::ulog2_floor(256) === 8);
        assert(self::ulog2_floor(257) === 8);
        assert(self::ulog2_floor(\PHP_INT_MAX)   === 62); // 0x7FFF...FFFF
        assert(self::ulog2_floor(\PHP_INT_MIN)   === 63); // 0x8000...0000
        assert(self::ulog2_floor(\PHP_INT_MIN+1) === 63); // 0x8000...0001
        assert(self::ulog2_floor(-1) === 63);             // 0xFFFF...FFFF (just shy of 2^64)
    }

    public static function ulog2_ceiling(int $v) : int
    {
        // 0 and 1 behave differently; we spacial-case them.
        // (Be careful of negative numbers: `$v < 2` would include
        // them into the special case, and we don't want that.)
        if (($v & 1) === $v) { return $v; }

        // The ceiling is pretty much the floor function
        // but with 1 added conditionally.
        $r = self::ulog2_floor($v);

        // And here is the condition for NOT adding 1:
        if ($v === (1 << $r)) {
            // Exact powers-of-2.
            return $r;
        }

        // Everything else needs 1 added to it for things to work out.
        return $r+1;
    }

    private static function unittest_ulog2_ceiling() : void
    {
        echo("  ".__FUNCTION__."()\n");

        assert(self::ulog2_ceiling(0)  === 0);
        assert(self::ulog2_ceiling(1)  === 1);
        assert(self::ulog2_ceiling(2)  === 1);
        assert(self::ulog2_ceiling(3)  === 2);
        assert(self::ulog2_ceiling(4)  === 2);
        assert(self::ulog2_ceiling(5)  === 3);
        assert(self::ulog2_ceiling(7)  === 3);
        assert(self::ulog2_ceiling(8)  === 3);
        assert(self::ulog2_ceiling(9)  === 4);
        assert(self::ulog2_ceiling(15) === 4);
        assert(self::ulog2_ceiling(16) === 4);
        assert(self::ulog2_ceiling(17) === 5);
        assert(self::ulog2_ceiling(31) === 5);
        assert(self::ulog2_ceiling(32) === 5);
        assert(self::ulog2_ceiling(33) === 6);
        assert(self::ulog2_ceiling(64) === 6);
        assert(self::ulog2_ceiling(128) === 7);
        assert(self::ulog2_ceiling(255) === 8);
        assert(self::ulog2_ceiling(256) === 8);
        assert(self::ulog2_ceiling(257) === 9);
        assert(self::ulog2_ceiling(\PHP_INT_MAX)   === 63); // 0x7FFF...FFFF
        assert(self::ulog2_ceiling(\PHP_INT_MIN)   === 63); // 0x8000...0000
        assert(self::ulog2_ceiling(\PHP_INT_MIN+1) === 64); // 0x8000...0001
        assert(self::ulog2_ceiling(-1) === 64);             // 0xFFFF...FFFF
    }


    /**
    * @param  int<0,5>  $pow2n_bits_per_seq
    * @return int<0,64>
    */
    public static function number_of_pow2n_bit_sequences_needed_to_represent(int $v, int $pow2n_bits_per_seq) : int
    {
        // This answers the question "how many bits are needed to represent $v?"
        $nbits = self::ulog2_floor($v) + 1;
        // (Caveat at ($v===0) :
        // It's technically 0 bits, but this will return 1.
        // That's OK, because we need to print '0'
        // when the number is 0, so it works out for us!)

        // But how many seqs/nybbles/bytes is that?
        $num_seqs = $nbits >> $pow2n_bits_per_seq;

        // That will underestimate a lot.
        // Consider the case of nybbles.
        // The number of bits (nbits) in a sequence is (2^$pow2n_bits_per_seq).
        // 1 nybble requires 4 bits, and 2^2 === 4, so our $pow2n_bits_per_seq === 2.
        // Ex: (3 >> 2) === 0, but we need 1 nybble to represent 3 bits.
        // We can fix this by adding 1.
        // However...
        // Note that exact powers-of-2 will already report correctly:
        // Ex: (4 >> 2) === 1, and it does take 1 nybble to represent 4 bits.
        // So adding 1 to these would make us over-report.
        // We'll only add 1 if it's not an exact power of 2.
        $check_nbits = $num_seqs << $pow2n_bits_per_seq;
        if ( $check_nbits !== $nbits ) {
            $num_seqs++;
        }

        // Now it should be reported correctly.
        assert($num_seqs > 0);
        assert($num_seqs <= 64);
        return $num_seqs;
    }

    // TODO: unittest number_of_nbit_sequences_needed_to_represent
    /**
    * @param  int<0,63>  $nbits_per_seq
    */
    public static function number_of_nbit_sequences_needed_to_represent(int $v, int $nbits_per_seq) : int
    {
        // This is just `number_of_pow2n_bit_sequences_needed_to_represent`
        // but using integer division instead of bitshifts.
        $nbits = self::ulog2_floor($v) + 1;
        $num_seqs = \intdiv($nbits, $nbits_per_seq);
        $check_nbits = $num_seqs * $nbits_per_seq;
        if ( $check_nbits !== $nbits ) {
            $num_seqs++;
        }
        return $num_seqs;
    }

    /**
    * @param   int        $v
    * @return  int<0,16>
    */
    public static function number_of_nybbles_needed_to_represent(int $v) : int {
        $result = self::number_of_pow2n_bit_sequences_needed_to_represent($v, 2);
        assert($result <= 16);
        return $result;
    }

    /**
    * @param   int        $v
    * @return  int<0,8>
    */
    public static function number_of_bytes_needed_to_represent(int $v) : int {
        $result = self::number_of_pow2n_bit_sequences_needed_to_represent($v, 3);
        assert($result <= 8);
        return $result;
    }

    private static function unittest_number_of_bytes_needed_to_represent() : void
    {
        echo("  ".__FUNCTION__."()\n");

        assert(self::number_of_bytes_needed_to_represent(0)  === 1);
        assert(self::number_of_bytes_needed_to_represent(1)  === 1);
        assert(self::number_of_bytes_needed_to_represent(2)  === 1);
        assert(self::number_of_bytes_needed_to_represent(3)  === 1);
        assert(self::number_of_bytes_needed_to_represent(4)  === 1);
        assert(self::number_of_bytes_needed_to_represent(5)  === 1);
        assert(self::number_of_bytes_needed_to_represent(7)  === 1);
        assert(self::number_of_bytes_needed_to_represent(8)  === 1);
        assert(self::number_of_bytes_needed_to_represent(9)  === 1);
        assert(self::number_of_bytes_needed_to_represent(15) === 1);
        assert(self::number_of_bytes_needed_to_represent(16) === 1);
        assert(self::number_of_bytes_needed_to_represent(17) === 1);
        assert(self::number_of_bytes_needed_to_represent(31) === 1);
        assert(self::number_of_bytes_needed_to_represent(32) === 1);
        assert(self::number_of_bytes_needed_to_represent(33) === 1);
        assert(self::number_of_bytes_needed_to_represent(64) === 1);
        assert(self::number_of_bytes_needed_to_represent(128) === 1);
        assert(self::number_of_bytes_needed_to_represent(255) === 1);
        assert(self::number_of_bytes_needed_to_represent(256) === 2); // 0x800
        assert(self::number_of_bytes_needed_to_represent(257) === 2); // 0x801
        assert(self::number_of_bytes_needed_to_represent(0x800) === 2);
        assert(self::number_of_bytes_needed_to_represent(0x8FF) === 2);
        assert(self::number_of_bytes_needed_to_represent(0xFFF) === 2);
        assert(self::number_of_bytes_needed_to_represent(0x8000) === 2);
        assert(self::number_of_bytes_needed_to_represent(0xFFFF) === 2);
        assert(self::number_of_bytes_needed_to_represent(0x80000) === 3);
        assert(self::number_of_bytes_needed_to_represent(0xFFFFF) === 3);
        assert(self::number_of_bytes_needed_to_represent(0x800000) === 3);
        assert(self::number_of_bytes_needed_to_represent(0xFFFFFF) === 3);
        assert(self::number_of_bytes_needed_to_represent(0x8000000) === 4);
        assert(self::number_of_bytes_needed_to_represent(0xFFFFFFF) === 4);
        assert(self::number_of_bytes_needed_to_represent(0x80000000) === 4);
        assert(self::number_of_bytes_needed_to_represent(0xFFFFFFFF) === 4);
        assert(self::number_of_bytes_needed_to_represent(0x800000000) === 5);
        assert(self::number_of_bytes_needed_to_represent(0xFFFFFFFFF) === 5);
        assert(self::number_of_bytes_needed_to_represent(0x80000000000000) === 7); // 0x0080...0000
        assert(self::number_of_bytes_needed_to_represent(0xFFFFFFFFFFFFFF) === 7); // 0x00FF...FFFF
        assert(self::number_of_bytes_needed_to_represent(0x800000000000000) === 8); // 0x0800...0000
        assert(self::number_of_bytes_needed_to_represent(0xFFFFFFFFFFFFFFF) === 8); // 0x0FFF...FFFF
        assert(self::number_of_bytes_needed_to_represent(\PHP_INT_MAX)   === 8); // 0x7FFF...FFFF
        assert(self::number_of_bytes_needed_to_represent(\PHP_INT_MIN)   === 8); // 0x8000...0000
        assert(self::number_of_bytes_needed_to_represent(\PHP_INT_MIN+1) === 8); // 0x8000...0001
        assert(self::number_of_bytes_needed_to_represent(-1) === 8);             // 0xFFFF...FFFF
    }

    private static function unittest_number_of_nybbles_needed_to_represent() : void
    {
        echo("  ".__FUNCTION__."()\n");

        assert(self::number_of_nybbles_needed_to_represent(0)  === 1);
        assert(self::number_of_nybbles_needed_to_represent(1)  === 1);
        assert(self::number_of_nybbles_needed_to_represent(2)  === 1);
        assert(self::number_of_nybbles_needed_to_represent(3)  === 1);
        assert(self::number_of_nybbles_needed_to_represent(4)  === 1);
        assert(self::number_of_nybbles_needed_to_represent(5)  === 1);
        assert(self::number_of_nybbles_needed_to_represent(7)  === 1);
        assert(self::number_of_nybbles_needed_to_represent(8)  === 1);
        assert(self::number_of_nybbles_needed_to_represent(9)  === 1);
        assert(self::number_of_nybbles_needed_to_represent(15) === 1);
        assert(self::number_of_nybbles_needed_to_represent(16) === 2);
        assert(self::number_of_nybbles_needed_to_represent(17) === 2);
        assert(self::number_of_nybbles_needed_to_represent(31) === 2);
        assert(self::number_of_nybbles_needed_to_represent(32) === 2);
        assert(self::number_of_nybbles_needed_to_represent(33) === 2);
        assert(self::number_of_nybbles_needed_to_represent(64) === 2);
        assert(self::number_of_nybbles_needed_to_represent(128) === 2);
        assert(self::number_of_nybbles_needed_to_represent(255) === 2);
        assert(self::number_of_nybbles_needed_to_represent(256) === 3); // 0x800
        assert(self::number_of_nybbles_needed_to_represent(257) === 3); // 0x801
        assert(self::number_of_nybbles_needed_to_represent(0x800) === 3);
        assert(self::number_of_nybbles_needed_to_represent(0x8FF) === 3);
        assert(self::number_of_nybbles_needed_to_represent(0xFFF) === 3);
        assert(self::number_of_nybbles_needed_to_represent(0x8000) === 4);
        assert(self::number_of_nybbles_needed_to_represent(0xFFFF) === 4);
        assert(self::number_of_nybbles_needed_to_represent(0x80000) === 5);
        assert(self::number_of_nybbles_needed_to_represent(0xFFFFF) === 5);
        assert(self::number_of_nybbles_needed_to_represent(0x800000) === 6);
        assert(self::number_of_nybbles_needed_to_represent(0xFFFFFF) === 6);
        assert(self::number_of_nybbles_needed_to_represent(0x8000000) === 7);
        assert(self::number_of_nybbles_needed_to_represent(0xFFFFFFF) === 7);
        assert(self::number_of_nybbles_needed_to_represent(0x80000000) === 8);
        assert(self::number_of_nybbles_needed_to_represent(0xFFFFFFFF) === 8);
        assert(self::number_of_nybbles_needed_to_represent(0x800000000) === 9);
        assert(self::number_of_nybbles_needed_to_represent(0xFFFFFFFFF) === 9);
        assert(self::number_of_nybbles_needed_to_represent(0x80000000000000) === 14); // 0x0080...0000
        assert(self::number_of_nybbles_needed_to_represent(0xFFFFFFFFFFFFFF) === 14); // 0x00FF...FFFF
        assert(self::number_of_nybbles_needed_to_represent(0x800000000000000) === 15); // 0x0800...0000
        assert(self::number_of_nybbles_needed_to_represent(0xFFFFFFFFFFFFFFF) === 15); // 0x0FFF...FFFF
        assert(self::number_of_nybbles_needed_to_represent(\PHP_INT_MAX)   === 16); // 0x7FFF...FFFF
        assert(self::number_of_nybbles_needed_to_represent(\PHP_INT_MIN)   === 16); // 0x8000...0000
        assert(self::number_of_nybbles_needed_to_represent(\PHP_INT_MIN+1) === 16); // 0x8000...0001
        assert(self::number_of_nybbles_needed_to_represent(-1) === 16);             // 0xFFFF...FFFF
    }


    // Simple alternative to hoping PHP's `log` function will return
    // something correct after floating point mysteries are reified.
    // (It'd PROBABLY be fine, but I'd prefer "fine 100% of the time.")
    /**
    * @param   int<0,max>  $v
    * @return  int<0,20>
    */
    public static function number_of_digits_needed_to_represent(int $v) : int
    {
        // TODO: optimize this?
        assert(0 <= $v); // @phpstan-ignore function.alreadyNarrowedType, smallerOrEqual.alwaysTrue
        $n_digits = 1;
        $pow10 = 10;
        while ( $pow10 <= $v && $n_digits < 20 ) {
            $n_digits++;
            $pow10 *= 10;
        }
        return $n_digits;
    }

    private static function unittest_number_of_digits_needed_to_represent() : void
    {
        echo("  ".__FUNCTION__."()\n");

        assert(self::number_of_digits_needed_to_represent(0)  === 1);
        assert(self::number_of_digits_needed_to_represent(1)  === 1);
        assert(self::number_of_digits_needed_to_represent(2)  === 1);
        assert(self::number_of_digits_needed_to_represent(3)  === 1);
        assert(self::number_of_digits_needed_to_represent(4)  === 1);
        assert(self::number_of_digits_needed_to_represent(5)  === 1);
        assert(self::number_of_digits_needed_to_represent(7)  === 1);
        assert(self::number_of_digits_needed_to_represent(8)  === 1);
        assert(self::number_of_digits_needed_to_represent(9)  === 1);
        assert(self::number_of_digits_needed_to_represent(10) === 2);
        assert(self::number_of_digits_needed_to_represent(15) === 2);
        assert(self::number_of_digits_needed_to_represent(16) === 2);
        assert(self::number_of_digits_needed_to_represent(17) === 2);
        assert(self::number_of_digits_needed_to_represent(31) === 2);
        assert(self::number_of_digits_needed_to_represent(32) === 2);
        assert(self::number_of_digits_needed_to_represent(33) === 2);
        assert(self::number_of_digits_needed_to_represent(64) === 2);
        assert(self::number_of_digits_needed_to_represent(99) === 2);
        assert(self::number_of_digits_needed_to_represent(100) === 3);
        assert(self::number_of_digits_needed_to_represent(128) === 3);
        assert(self::number_of_digits_needed_to_represent(255) === 3);
        assert(self::number_of_digits_needed_to_represent(256) === 3); // 0x800
        assert(self::number_of_digits_needed_to_represent(257) === 3); // 0x801
        assert(self::number_of_digits_needed_to_represent(999) === 3);
        assert(self::number_of_digits_needed_to_represent(1000) === 4);
        assert(self::number_of_digits_needed_to_represent(9999) === 4);
        assert(self::number_of_digits_needed_to_represent(10000) === 5);
        assert(self::number_of_digits_needed_to_represent(99999) === 5);
        assert(self::number_of_digits_needed_to_represent(100000) === 6);
        assert(self::number_of_digits_needed_to_represent(999999) === 6);
        assert(self::number_of_digits_needed_to_represent(1000000) === 7);
        assert(self::number_of_digits_needed_to_represent(9999999) === 7);
        assert(self::number_of_digits_needed_to_represent(10000000) === 8);
        assert(self::number_of_digits_needed_to_represent(99999999) === 8);
        assert(self::number_of_digits_needed_to_represent(100000000) === 9);
        assert(self::number_of_digits_needed_to_represent(999999999) === 9);
        assert(self::number_of_digits_needed_to_represent(1000000000) === 10);
        assert(self::number_of_digits_needed_to_represent(0x7FFFFFFF) === 10); // '2147483647'
        assert(self::number_of_digits_needed_to_represent(0x80000000) === 10); // '2147483648'
        assert(self::number_of_digits_needed_to_represent(0xFFFFFFFF) === 10); // '4294967297'
        assert(self::number_of_digits_needed_to_represent(9999999999) === 10);
        assert(self::number_of_digits_needed_to_represent(10000000000) === 11);
        assert(self::number_of_digits_needed_to_represent(99999999999) === 11);
        assert(self::number_of_digits_needed_to_represent(1000000000000000000) === 19);  //                  '1000000000000000000'
        assert(self::number_of_digits_needed_to_represent(0x7FFFFFFFFFFFFFFF) === 19);   // 0x7FFF...FFFF -> '9223372036854775807'
        //assert(self::number_of_digits_needed_to_represent(0x8000000000000000) === 20); // 0x8000...0000 -> '-9223372036854775808'
        //assert(self::number_of_digits_needed_to_represent(0xFFFFFFFFFFFFFFFF) === 2);  // 0xFFFF...FFFF -> '-1'
    }

    public static function unittests() : void
    {
        $class_fqn = self::class;
        echo("Running `$class_fqn::unittests()`\n");

        self::unittest_ulog2_floor();
        self::unittest_ulog2_ceiling();
        self::unittest_number_of_bytes_needed_to_represent();
        self::unittest_number_of_nybbles_needed_to_represent();
        self::unittest_number_of_digits_needed_to_represent();

        echo("  ... passed.\n\n");
    }
}
?>
