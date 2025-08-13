<?php
declare(strict_types=1);

namespace Kickback\Common\Meta;
use Kickback\Common\Primitives\Meta;
use Kickback\Common\Traits\StaticClassTrait;
use Kickback\Common\Traits\ClassOfConstantIntegersTrait;

/**
* Enumeration of, and operations for, PHP's binary operators.
*
* This is, at the very least, very useful for unittesting framework
* implementation, as it allows assertion statements to be broken into
* components and then reassembled and executed. (At least, up to some point.)
*
* It was tempting to simply use the PHP tokenizer's token constants as
* the constants for this mapping:
* https://www.php.net/manual/en/tokens.php
*
* However, they aren't suitable for efficient (dense) array indexing:
* https://gist.github.com/hacfi/b634580de56772e9a7d9#file-token_constants-php
* https://gist.github.com/clytras/169931c574c5061bdc1f3458fbffbd53
*
* The PHP token list has some other problems:
* * Some tokens, like '!=' and '<>', both map to the \T_IS_NOT_EQUAL
*     constant, making it sketchy to forward them correctly.
* * Some tokens, like '<', '>', and '.' seem to be entirely unrepresented
*     (at least in the aforementioned documentation).
*
* So this class intends to do better than that, and establishes a set
* of constants that can be used for array lookups on PHP operators.
* And it is unambiguous and capable of round-tripping token/ids
* and their string representations.
*/
final class PHP_BinaryOps
{
    use StaticClassTrait;
    use ClassOfConstantIntegersTrait;

    public const INVALID                  = 0;

    // Equality and Inequality group
    public const STRICT_EQUALITY          = 1;
    public const STRICT_INEQUALITY        = 2;

    // Other comparison operators
    public const LESS_THAN                = 3;
    public const LESS_THAN_OR_EQUAL       = 4;
    public const GREATER_THAN             = 5;
    public const GREATER_THAN_OR_EQUAL    = 6;

    // Boolean (binary) logic operators
    public const BOOLEAN_AND_SYMBOL       = 7;
    public const BOOLEAN_OR_SYMBOL        = 8;

    // Non-boolean binary operators
    public const ADDITION                 = 9;
    public const SUBTRACTION              = 10;
    public const BITWISE_AND              = 11;
    public const BITWISE_OR               = 12;
    public const BITWISE_XOR              = 13;
    public const BITSHIFT_LEFT            = 14;
    public const BITSHIFT_RIGHT           = 15;
    public const MODULUS                  = 16;
    public const MULTIPLICATION           = 17;
    public const DIVISION                 = 18;
    public const CONCATENATION            = 19;
    public const COALESCE                 = 20;
    public const EXPONENTIATION           = 21;

    // Op-assignment operators
    public const ADD_ASSIGN               = 22;
    public const SUB_ASSIGN               = 23;
    public const AND_ASSIGN               = 24;
    public const OR_ASSIGN                = 25;
    public const XOR_ASSIGN               = 26;
    public const MOD_ASSIGN               = 27;
    public const MUL_ASSIGN               = 28;
    public const DIV_ASSIGN               = 29;
    public const CONCAT_ASSIGN            = 30;
    public const SHIFT_LEFT_ASSIGN        = 31;
    public const SHIFT_RIGHT_ASSIGN       = 32;
    public const COALESCE_ASSIGN          = 33;
    public const EXP_ASSIGN               = 34;

    // Scope resolution and dereferencing
    public const PAAMAYIM_NEKUDOTAYIM     = 35;
    public const MEMBER_ACCESS            = 36;
    public const NULLSAFE_MEMBER_ACCESS   = 37;

    // Loose comparisons
    public const LOOSE_EQUALITY           = 38;
    public const LOOSE_INEQUALITY_EPOINT  = 39;
    public const LOOSE_INEQUALITY_DIAMOND = 40;

    // Special comparison
    public const SPACESHIP                = 41;

    // Ternary-like and keyword forms
    public const ELVIS                    = 42;
    public const BOOLEAN_AND_KEYWORD      = 43;
    public const BOOLEAN_OR_KEYWORD       = 44;
    public const BOOLEAN_XOR_KEYWORD      = 45;

    /**
    * Convert a string PHP binary operator into an operator ID.
    *
    * When `$op_text` isn't a known operator, this function will
    * throw an \UnexpectedValueException.
    *
    * @return  self::*
    */
    public static function from_string(string $op_text) : int
    {
        $res = self::from_string_nt($op_text);
        if ( $res !== self::INVALID ) {
            return $res;
        }

        $class_shortname = Meta::shortname(self::class);
        throw new \UnexpectedValueException("There is currently no ID listed for the operator rendered as '$op_text' in the $class_shortname list.");
    }

    /**
    * Non-throwing version of PHP_BinaryOps::from_string
    *
    * When `$op_text` isn't a known operator, this function will return
    * PHP_BinaryOps::INVALID (instead of throwing).
    *
    * @return  self::*
    */
    public static function from_string_nt(string $op_text) : int
    {
        if ( \array_key_exists($op_text, PHP_BinaryOps__StringToID::TABLE) ) {
            return PHP_BinaryOps__StringToID::TABLE[$op_text];
        }

        // Some things require string processing to _normalize_ the $op_text.
        // (Ex: 'and', 'AnD', 'aND', etc -> 'AND'.)
        // These are done after the more opportunistic check, because the
        // string manipulation  makes these cases the most expensive to
        // check for. (It likely involves CoW string allocations.)
        $op_text = self::normalize($op_text);

        if ( \array_key_exists($op_text, PHP_BinaryOps__StringToID::TABLE) ) {
            return PHP_BinaryOps__StringToID::TABLE[$op_text];
        }

        return self::INVALID;
    }

    private static function unittest_from_string() : void
    {
        assert(self::from_string('===') === self::STRICT_EQUALITY);
        assert(self::from_string('!==') === self::STRICT_INEQUALITY);
        assert(self::from_string('<')   === self::LESS_THAN);
        assert(self::from_string('<=')  === self::LESS_THAN_OR_EQUAL);
        assert(self::from_string('>')   === self::GREATER_THAN);
        assert(self::from_string('>=')  === self::GREATER_THAN_OR_EQUAL);
        assert(self::from_string('<=>') === self::SPACESHIP);
        assert(self::from_string('/')   === self::DIVISION);
        assert(self::from_string('/=')  === self::DIV_ASSIGN);
        assert(self::from_string('&')   === self::BITWISE_AND);
        assert(self::from_string('&&')  === self::BOOLEAN_AND_SYMBOL);
        assert(self::from_string('AND') === self::BOOLEAN_AND_KEYWORD);
        assert(self::from_string('and') === self::BOOLEAN_AND_KEYWORD);
        assert(self::from_string('aNd') === self::BOOLEAN_AND_KEYWORD);
        assert(self::from_string('?:')  === self::ELVIS);
        assert(self::from_string('? :') === self::ELVIS);

        // Spot-check that `from_string` and `from_string_nt`
        // do the same thing:
        assert(self::from_string_nt('===') === self::STRICT_EQUALITY);
        assert(self::from_string_nt('!==') === self::STRICT_INEQUALITY);
        assert(self::from_string_nt('<')   === self::LESS_THAN);
        assert(self::from_string_nt('<=')  === self::LESS_THAN_OR_EQUAL);
        assert(self::from_string_nt('>')   === self::GREATER_THAN);
        assert(self::from_string_nt('>=')  === self::GREATER_THAN_OR_EQUAL);

        // Throw on empty strings.
        $threw = false;
        try {
            assert(self::from_string('') === self::INVALID);
        } catch ( \UnexpectedValueException $e ) {
            $threw = true;
        }
        assert($threw);

        // Throw when it's not a token at all.
        $threw = false;
        try {
            assert(self::from_string('abc') === self::INVALID);
        } catch ( \UnexpectedValueException $e ) {
            $threw = true;
        }
        assert($threw);

        // Throw when it's just PART of a token.
        $threw = false;
        try {
            assert(self::from_string('AN') === self::INVALID);
        } catch ( \UnexpectedValueException $e ) {
            $threw = true;
        }
        assert($threw);

        // Test how the non-throwing version handles failures:
        assert(self::from_string_nt('')    === self::INVALID);
        assert(self::from_string_nt(' ')   === self::INVALID);
        assert(self::from_string_nt('  ')  === self::INVALID);
        assert(self::from_string_nt('   ') === self::INVALID);
        assert(self::from_string_nt('abc') === self::INVALID);
        assert(self::from_string_nt('AN')  === self::INVALID);

        echo("  ".__FUNCTION__."()\n");
    }

    /**
    * @return  array<self::*, string>
    */
    public static function op_ids_to_code() : array
    {
        static $values_to_names = null;
        if (isset($values_to_names)) {
            return $values_to_names;
        }
        return self::ClassOfConstantIntegersTrait__generate_name_lookup(
            PHP_BinaryOps__StringToID::TABLE, $values_to_names);
    }

    /**
    * Create a string representing the PHP-code form of a binary operation.
    *
    * @param  self::*  $op_id
    */
    public static function to_code(int $op_id) : string
    {
        return self::op_ids_to_code()[$op_id];
    }

    /**
    * Normalizes some operators, ex: 'and', 'AnD', 'aND', etc -> 'AND'.
    *
    * This is the normalization logic used in PHP_BinaryOps::from_string if
    * it doesn't get an immediate hit with its table lookup.
    *
    * Don't call this before calling PHP_BinaryOps::from_string; it would just make
    * the code slower by effectively forcing the more expensive check to
    * be performed before the least expensive check.
    */
    public static function normalize(string $op_text) : string
    {
        // Replace "?   :" with "?:" (Elvis operator)
        $first_ch = \substr($op_text,0,1);
        if ( $first_ch === '?' && \ltrim(\substr($op_text, 1)) === ':' ) {
             // Optimization: It's probably better to return a constant than do a string concat.
            return '?:';
        }

        // Optimization: exit early if possible.
        // "strspn — Finds the length of the initial segment of a string
        // consisting entirely of characters contained within a given mask."
        // If $op_text contains any characters besides those found in
        // 'AND'|'and'|'OR'|'or'|'XOR'|'xor', then it isn't an operator
        // that needs normalization, and we can return right away
        // (and avoid more complicated calculations).
        if ( \strlen($op_text) !== \strspn($op_text,'ANDORXandorx') ) {
            return $op_text;
        }

        // Optimizations:
        // * Don't assume that PCRE extension is available on the host.
        // * Returning constants instead of using \strtoupper
        //      -> Don't modify the input string (even if it isn't normal).
        //         (So, this function might do no memory allocations.)
        //
        if ( \strcasecmp($op_text,'AND') === 0 ) { return 'AND'; }
        if ( \strcasecmp($op_text,'OR')  === 0 ) { return 'OR';  }
        if ( \strcasecmp($op_text,'XOR') === 0 ) { return 'XOR'; }

        return $op_text;

        // // Also, if it's normalized already, don't modify the string.
        // // (E.g. don't call `\strtoupper`)
        // switch($op_text) {
        //     case 'AND':
        //     case 'OR':
        //     case 'XOR':
        //         return $op_text;
        // }
        //
        // // Supposedly, according to ChatGPT, this is cached, so
        // // "Repeated calls within one script execution are fast."
        // $have_pcre = \extension_loaded('pcre');
        //
        // // Replace things like "and", "AnD", "aND", etc all with "AND".
        // // We do case-insensitive comparison first before attempting
        // // to do the lookup, because that avoids modifying the string
        // // (avoids expensive memory allocations) whenever the string
        // // doesn't need to be normalized.
        // if ($have_pcre && \preg_match('/^(AND|OR|XOR)$/i', $input)) {
        //     // Maybe-faster version if we have PCRE.
        //     // Note that PCRE supposedly has a cache of 4096
        //     // pre-compiled regular expressions, so we don't have to
        //     // (and possibly _can't_) cache our regex compilation.
        //     // Source: https://stackoverflow.com/questions/209906/compile-regex-in-php
        //     return \strtoupper($op_text);
        // } else // Potentially slower multiple-function-call check.
        // if (\strcasecmp($op_text,'AND') === 0
        // ||  \strcasecmp($op_text,'OR')  === 0
        // ||  \strcasecmp($op_text,'XOR') === 0) {
        //     return \strtoupper($op_text);
        // }
        //
        // // Above code replaces this:
        // //$op_text = preg_replace('/\s+/', '', $op_text);
        // // (Because it's nice if we don't need to assume that
        // // the PCRE extension is available on the host's PHP.)
        //
        // return $op_text;
    }

    // Could have a test to ensure that it doesn't try to strtoupper
    // things that aren't tokens.
    private static function unittest_normalize() : void
    {
        // 0-cases
        assert(self::normalize('')    === '');
        assert(self::normalize(' ')   === ' ');

        // Passthrough cases should pass through.
        assert(self::normalize('?:')  === '?:');
        assert(self::normalize('AND') === 'AND');
        assert(self::normalize('OR')  === 'OR');
        assert(self::normalize('XOR') === 'XOR');

        // Elvis condensation
        assert(self::normalize('? :')  === '?:');
        assert(self::normalize('?  :') === '?:');

        // All lowercase
        assert(self::normalize('and') === 'AND');
        assert(self::normalize('or')  === 'OR');
        assert(self::normalize('xor') === 'XOR');

        // Mixed case
        assert(self::normalize('aND') === 'AND');
        assert(self::normalize('aNd') === 'AND');
        assert(self::normalize('AnD') === 'AND');
        assert(self::normalize('oR')  === 'OR');
        assert(self::normalize('Or')  === 'OR');
        assert(self::normalize('xOR') === 'XOR');
        assert(self::normalize('xOr') === 'XOR');
        assert(self::normalize('XoR') === 'XOR');

        // We don't handle trimming.
        assert(self::normalize(' ?:  ') === ' ?:  ');
        assert(self::normalize(' and ') === ' and ');
        assert(self::normalize(' or  ') === ' or  ');
        assert(self::normalize(' xor ') === ' xor ');

        // More "this shouldn't be modified, it's not a token" cases
        assert(self::normalize('?  ?') === '?  ?');
        assert(self::normalize(':  :') === ':  :');
        assert(self::normalize('??::') === '??::');
        assert(self::normalize('? ?:') === '? ?:');
        assert(self::normalize('?: :') === '?: :');
        assert(self::normalize('? ::') === '? ::');
        assert(self::normalize('xyz')  === 'xyz');
        assert(self::normalize('a')    === 'a');
        assert(self::normalize('o')    === 'o');
        assert(self::normalize('x')    === 'x');

        echo("  ".__FUNCTION__."()\n");
    }

    /**
    * @param  array<self::*,callable(mixed,mixed):mixed>  $bin_expr_dispatch_table
    */
    private static function populate_binary_expression_evaluation_array(array &$bin_expr_dispatch_table) : void
    {
        $op = &$bin_expr_dispatch_table;

        // Equality and Inequality group
        $op[self::STRICT_EQUALITY          ] = fn($lhs, $rhs) => ($lhs === $rhs);
        $op[self::STRICT_INEQUALITY        ] = fn($lhs, $rhs) => ($lhs !== $rhs);

        // Other comparison operators
        $op[self::LESS_THAN                ] = fn($lhs, $rhs) => ($lhs <   $rhs);
        $op[self::LESS_THAN_OR_EQUAL       ] = fn($lhs, $rhs) => ($lhs <=  $rhs);
        $op[self::GREATER_THAN             ] = fn($lhs, $rhs) => ($lhs >   $rhs);
        $op[self::GREATER_THAN_OR_EQUAL    ] = fn($lhs, $rhs) => ($lhs >=  $rhs);

        // Boolean (binary) logic operators
        $op[self::BOOLEAN_AND_SYMBOL       ] = fn($lhs, $rhs) => ((bool)$lhs && (bool)$rhs);
        $op[self::BOOLEAN_OR_SYMBOL        ] = fn($lhs, $rhs) => ((bool)$lhs || (bool)$rhs);

        // Non-boolean binary operators
        $op[self::ADDITION                 ] = fn($lhs, $rhs) => ($lhs +  $rhs);
        $op[self::SUBTRACTION              ] = fn($lhs, $rhs) => ($lhs -  $rhs);
        $op[self::BITWISE_AND              ] = fn($lhs, $rhs) => ($lhs &  $rhs);
        $op[self::BITWISE_OR               ] = fn($lhs, $rhs) => ($lhs |  $rhs);
        $op[self::BITWISE_XOR              ] = fn($lhs, $rhs) => ($lhs ^  $rhs);
        $op[self::BITSHIFT_LEFT            ] = fn($lhs, $rhs) => ($lhs << $rhs);
        $op[self::BITSHIFT_RIGHT           ] = fn($lhs, $rhs) => ($lhs >> $rhs);
        $op[self::MODULUS                  ] = fn($lhs, $rhs) => ($lhs %  $rhs);
        $op[self::MULTIPLICATION           ] = fn($lhs, $rhs) => ($lhs *  $rhs);
        $op[self::DIVISION                 ] = fn($lhs, $rhs) => ($lhs /  $rhs);
        $op[self::CONCATENATION            ] = fn($lhs, $rhs) => ($lhs .  $rhs);
        $op[self::COALESCE                 ] = fn($lhs, $rhs) => ($lhs ?? $rhs);
        $op[self::EXPONENTIATION           ] = fn($lhs, $rhs) => ($lhs ** $rhs);

        // Op-assignment operators (these modify lhs, so dispatch functions may need special treatment)
        $op[self::ADD_ASSIGN               ] = fn($lhs, $rhs) => ($lhs +=  $rhs);
        $op[self::SUB_ASSIGN               ] = fn($lhs, $rhs) => ($lhs -=  $rhs);
        $op[self::AND_ASSIGN               ] = fn($lhs, $rhs) => ($lhs &=  $rhs);
        $op[self::OR_ASSIGN                ] = fn($lhs, $rhs) => ($lhs |=  $rhs);
        $op[self::XOR_ASSIGN               ] = fn($lhs, $rhs) => ($lhs ^=  $rhs);
        $op[self::MOD_ASSIGN               ] = fn($lhs, $rhs) => ($lhs %=  $rhs);
        $op[self::MUL_ASSIGN               ] = fn($lhs, $rhs) => ($lhs *=  $rhs);
        $op[self::DIV_ASSIGN               ] = fn($lhs, $rhs) => ($lhs /=  $rhs);
        $op[self::CONCAT_ASSIGN            ] = fn($lhs, $rhs) => ($lhs .=  $rhs);
        $op[self::SHIFT_LEFT_ASSIGN        ] = fn($lhs, $rhs) => ($lhs <<= $rhs);
        $op[self::SHIFT_RIGHT_ASSIGN       ] = fn($lhs, $rhs) => ($lhs >>= $rhs);
        $op[self::COALESCE_ASSIGN          ] = fn($lhs, $rhs) => ($lhs ??= $rhs);
        $op[self::EXP_ASSIGN               ] = fn($lhs, $rhs) => ($lhs **= $rhs);

        // Scope resolution and dereferencing
        $op[self::PAAMAYIM_NEKUDOTAYIM     ] = fn($lhs, $rhs) => ($lhs::$rhs);  // @phpstan-ignore staticProperty.nonObject
        $op[self::MEMBER_ACCESS            ] = fn($lhs, $rhs) => ($lhs->$rhs);  // @phpstan-ignore property.dynamicName
        $op[self::NULLSAFE_MEMBER_ACCESS   ] = fn($lhs, $rhs) => ($lhs?->$rhs); // @phpstan-ignore property.dynamicName

        // Loose equality operators
        $op[self::LOOSE_EQUALITY           ] = fn($lhs, $rhs) => ($lhs ==  $rhs);
        $op[self::LOOSE_INEQUALITY_EPOINT  ] = fn($lhs, $rhs) => ($lhs !=  $rhs);
        $op[self::LOOSE_INEQUALITY_DIAMOND ] = fn($lhs, $rhs) => ($lhs <>  $rhs);

        // Rare spaceship operator
        $op[self::SPACESHIP                ] = fn($lhs, $rhs) => ($lhs <=> $rhs);

        // Elvis and keyword logical operators
        $op[self::ELVIS                    ] = fn($lhs, $rhs) => ($lhs ?: $rhs);
        $op[self::BOOLEAN_AND_KEYWORD      ] = fn($lhs, $rhs) => ((bool)$lhs AND (bool)$rhs);
        $op[self::BOOLEAN_OR_KEYWORD       ] = fn($lhs, $rhs) => ((bool)$lhs OR  (bool)$rhs);
        $op[self::BOOLEAN_XOR_KEYWORD      ] = fn($lhs, $rhs) => ((bool)$lhs XOR (bool)$rhs);
    }

    /**
    * Evaluate "$lhs ${PHP_BinaryOps::to_code()} $rhs"
    *
    * @param  self::*  $op
    */
    public static function evaluate_binary_expr(int $op, mixed $lhs, mixed $rhs) : mixed
    {
        static $bin_expr_dispatch_table = null;
        if ( isset($bin_expr_dispatch_table) ) {
            return $bin_expr_dispatch_table[$op]($lhs, $rhs);
        } else {
            self::populate_binary_expression_evaluation_array($bin_expr_dispatch_table);
            return $bin_expr_dispatch_table[$op]($lhs, $rhs);
        }
    }

    // It used to be done this way (below).
    // However, this is potentially a very slow way to do it.
    // That's because PHP (as of 8.2 at least) does not optimize
    // switch-case statements into jumptables, even when the
    // cases are nice, well-behaved, consecutive integers.
    // Instead, the output is similar to consecutive
    // if-statements, albeit possibly condensed a bit
    // by the if-not-then-jump-else-do logic being replaced
    // with if-then-jump logic. (2 opcodes instead of 3, per case)
    //
    // But, the best way to do it is to write our own jumptable
    // using an array of closures, as implemented above.
    //
    // /**
    // * @param  self::*  $op
    // */
    // public static function evaluate_result_of_binary_expr(int $op, mixed $lhs, mixed $rhs) : mixed
    // {
    //     switch ($op) {
    //
    //         // Equality and Inequality group
    //         case self::STRICT_EQUALITY:         return ($lhs === $rhs);
    //         case self::STRICT_INEQUALITY:       return ($lhs !== $rhs);
    //
    //         // Other comparison operators
    //         case self::LESS_THAN:               return ($lhs <   $rhs);
    //         case self::LESS_THAN_OR_EQUAL:      return ($lhs <=  $rhs);
    //         case self::GREATER_THAN:            return ($lhs >   $rhs);
    //         case self::GREATER_THAN_OR_EQUAL:   return ($lhs >=  $rhs);
    //
    //         // Boolean (binary) logic operators
    //         case self::BOOLEAN_AND_SYMBOL:      return ((bool)$lhs && (bool)$rhs);
    //         case self::BOOLEAN_OR_SYMBOL:       return ((bool)$lhs || (bool)$rhs);
    //
    //         // Non-boolean binary operators
    //         case self::ADDITION:                return ($lhs +  $rhs);
    //         case self::SUBTRACTION:             return ($lhs -  $rhs);
    //         case self::BITWISE_AND:             return ($lhs &  $rhs);
    //         case self::BITWISE_OR:              return ($lhs |  $rhs);
    //         case self::BITWISE_XOR:             return ($lhs ^  $rhs);
    //         case self::BITSHIFT_LEFT:           return ($lhs << $rhs);
    //         case self::BITSHIFT_RIGHT:          return ($lhs >> $rhs);
    //         case self::MODULUS:                 return ($lhs %  $rhs);
    //         case self::MULTIPLICATION:          return ($lhs *  $rhs);
    //         case self::DIVISION:                return ($lhs /  $rhs);
    //         case self::CONCATENATION:           return ($lhs .  $rhs);
    //         case self::COALESCE:                return ($lhs ?? $rhs);
    //         case self::EXPONENTIATION:          return ($lhs ** $rhs);
    //
    //         // Op-assignment operators
    //         case self::ADD_ASSIGN:              return ($lhs +=  $rhs);
    //         case self::SUB_ASSIGN:              return ($lhs -=  $rhs);
    //         case self::AND_ASSIGN:              return ($lhs &=  $rhs);
    //         case self::OR_ASSIGN:               return ($lhs |=  $rhs);
    //         case self::XOR_ASSIGN:              return ($lhs ^=  $rhs);
    //         case self::MOD_ASSIGN:              return ($lhs %=  $rhs);
    //         case self::MUL_ASSIGN:              return ($lhs *=  $rhs);
    //         case self::DIV_ASSIGN:              return ($lhs /=  $rhs);
    //         case self::CONCAT_ASSIGN:           return ($lhs .=  $rhs);
    //         case self::SHIFT_LEFT_ASSIGN:       return ($lhs <<= $rhs);
    //         case self::SHIFT_RIGHT_ASSIGN:      return ($lhs >>= $rhs);
    //         case self::COALESCE_ASSIGN:         return ($lhs ??= $rhs);
    //         case self::EXP_ASSIGN:              return ($lhs **= $rhs);
    //
    //         // Scope resolution and dereferencing
    //         case self::PAAMAYIM_NEKUDOTAYIM:    return ($lhs::$rhs);  // @phpstan-ignore staticProperty.nonObject
    //         case self::MEMBER_ACCESS:           return ($lhs->$rhs);  // @phpstan-ignore property.dynamicName
    //         case self::NULLSAFE_MEMBER_ACCESS:  return ($lhs?->$rhs); // @phpstan-ignore property.dynamicName
    //
    //         // (Hopefully) less common equality operators
    //         case self::LOOSE_EQUALITY:          return ($lhs ==  $rhs);
    //         case self::LOOSE_INEQUALITY_EPOINT: return ($lhs !=  $rhs);
    //         case self::LOOSE_INEQUALITY_DIAMOND:return ($lhs <>  $rhs);
    //
    //         // The rare spaceship operator
    //         case self::SPACESHIP:               return ($lhs <=> $rhs);
    //
    //         // Elvis and logical keyword operators
    //         case self::ELVIS:                   return ($lhs ?: $rhs);
    //         case self::BOOLEAN_AND_KEYWORD:     return ((bool)$lhs AND (bool)$rhs);
    //         case self::BOOLEAN_OR_KEYWORD:      return ((bool)$lhs OR  (bool)$rhs);
    //         case self::BOOLEAN_XOR_KEYWORD:     return ((bool)$lhs XOR (bool)$rhs);
    //     }
    //
    //     // @phpstan-ignore function.impossibleType
    //     assert(false); // invalid/unhandled $op
    //     return null;
    // }

    public static function unittests() : void
    {
        $class_fqn = self::class;
        echo("Running `$class_fqn::unittests()`\n");

        self::unittest_from_string();
        self::unittest_normalize();

        echo("  ... passed.\n\n");
    }
}


final class PHP_BinaryOps__StringToID
{
    use StaticClassTrait;

    /**
    * Table whose keys are operators-as-strings, and values are indexing integers.
    */
    public const TABLE = [

        // Equality and Inequality group
        '===' => PHP_BinaryOps::STRICT_EQUALITY,       // Token: \T_IS_IDENTICAL,
        '!==' => PHP_BinaryOps::STRICT_INEQUALITY,     // Token: \T_IS_NOT_IDENTICAL,

        // Other comparison operators
        '<'   => PHP_BinaryOps::LESS_THAN,             // Token: Not represented
        '<='  => PHP_BinaryOps::LESS_THAN_OR_EQUAL,    // Token: \T_IS_SMALLER_OR_EQUAL
        '>'   => PHP_BinaryOps::GREATER_THAN,          // Token: Not represented
        '>='  => PHP_BinaryOps::GREATER_THAN_OR_EQUAL, // Token: \T_IS_GREATER_OR_EQUAL
        // There are more near the bottom.
        // We want those to be higher-indexed because they
        // will not appear as often as the above ones.
        // (Or, at least, we _hope_ so!  At least
        // for the "loose" (in)equality operators.)

        // Boolean (binary) logic operators
        '&&'  => PHP_BinaryOps::BOOLEAN_AND_SYMBOL,    // Token: \T_BOOLEAN_AND
        '||'  => PHP_BinaryOps::BOOLEAN_OR_SYMBOL,     // Token: \T_BOOLEAN_OR

        // Non-boolean binary operators
        '+'   => PHP_BinaryOps::ADDITION,              // Token: Not represented
        '-'   => PHP_BinaryOps::SUBTRACTION,           // Token: Not represented
        '&'   => PHP_BinaryOps::BITWISE_AND,           // Token: Not represented (represented as a reference op tho ¯\_(ツ)_/¯
        '|'   => PHP_BinaryOps::BITWISE_OR,            // Token: Not represented
        '^'   => PHP_BinaryOps::BITWISE_XOR,           // Token: Not represented
        '<<'  => PHP_BinaryOps::BITSHIFT_LEFT,         // Token: \T_SL
        '>>'  => PHP_BinaryOps::BITSHIFT_RIGHT,        // Token: \T_SR
        '%'   => PHP_BinaryOps::MODULUS,               // Token: Not represented
        '*'   => PHP_BinaryOps::MULTIPLICATION,        // Token: Not represented
        '/'   => PHP_BinaryOps::DIVISION,              // Token: Not represented
        '.'   => PHP_BinaryOps::CONCATENATION,         // Token: Not represented
        '??'  => PHP_BinaryOps::COALESCE,              // Token: \T_COALESCE
        '**'  => PHP_BinaryOps::EXPONENTIATION,        // Token: \T_POW

        // Op-assignment operators
        '+='  => PHP_BinaryOps::ADD_ASSIGN,            // Token: \T_PLUS_EQUAL
        '-='  => PHP_BinaryOps::SUB_ASSIGN,            // Token: \T_MINUS_EQUAL
        '&='  => PHP_BinaryOps::AND_ASSIGN,            // Token: \T_AND_EQUAL
        '|='  => PHP_BinaryOps::OR_ASSIGN,             // Token: \T_OR_EQUAL
        '^='  => PHP_BinaryOps::XOR_ASSIGN,            // Token: \T_XOR_EQUAL,
        '%='  => PHP_BinaryOps::MOD_ASSIGN,            // Token: \T_MOD_EQUAL
        '*='  => PHP_BinaryOps::MUL_ASSIGN,            // Token: \T_MUL_EQUAL
        '/='  => PHP_BinaryOps::DIV_ASSIGN,            // Token: \T_DIV_EQUAL
        '.='  => PHP_BinaryOps::CONCAT_ASSIGN,         // Token: \T_CONCAT_EQUAL
        '<<=' => PHP_BinaryOps::SHIFT_LEFT_ASSIGN,     // Token: \T_SL_EQUAL
        '>>=' => PHP_BinaryOps::SHIFT_RIGHT_ASSIGN,    // Token: \T_SR_EQUAL
        '??=' => PHP_BinaryOps::COALESCE_ASSIGN,       // Token: \T_COALESCE_EQUAL
        '**=' => PHP_BinaryOps::EXP_ASSIGN,            // Token: \T_POW_EQUAL

        // Scope resolution and dereferencing
        '::'  => PHP_BinaryOps::PAAMAYIM_NEKUDOTAYIM,  // Token: \T_PAAMAYIM_NEKUDOTAYIM (means "double colon" in Hebrew; scope resolution)
        '->'  => PHP_BinaryOps::MEMBER_ACCESS,         // Token: \T_OBJECT_OPERATOR
        '?->' => PHP_BinaryOps::NULLSAFE_MEMBER_ACCESS,// Token: \T_NULLSAFE_OBJECT_OPERATOR

        // (Hopefully) less common equality operators
        '=='  => PHP_BinaryOps::LOOSE_EQUALITY,           // Token: \T_IS_EQUAL
        '!='  => PHP_BinaryOps::LOOSE_INEQUALITY_EPOINT,  // Token: \T_IS_NOT_EQUAL
        '<>'  => PHP_BinaryOps::LOOSE_INEQUALITY_DIAMOND, // Token: \T_IS_NOT_EQUAL

        // The rare spaceship operator
        '<=>' => PHP_BinaryOps::SPACESHIP,             // Token: \T_SPACESHIP

        // Operators that would potentially
        // require normalization before matching
        // or looking up correctly in this array.
        // (While these probably don't have any
        // official normalized forms, we are
        // defining the below to be "normal".)
        '?:'  => PHP_BinaryOps::ELVIS,                 // Token: Not represented
        'AND' => PHP_BinaryOps::BOOLEAN_AND_KEYWORD,   // Token: \T_LOGICAL_AND
        'OR'  => PHP_BinaryOps::BOOLEAN_OR_KEYWORD,    // Token: \T_LOGICAL_OR
        'XOR' => PHP_BinaryOps::BOOLEAN_XOR_KEYWORD    // Token: \T_LOGICAL_XOR

        // Is the 'as' operator binary? I might say it's really not
        // an operator, so much as just part of the `foreach` statement.
        // It doesn't seem to be used anywhere else.
        // Ditto for '=>': It's either part of an arrow function,
        // or it's used in array syntax.
    ];

}
?>
