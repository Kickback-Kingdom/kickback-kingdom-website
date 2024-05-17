<?php
declare(strict_types=1);

namespace Kickback\UnitTesting\PHPRegex;

/**
* # Note: PHPTokens can either be a single character (i.e.: ;, ., >, !, etc...),
* # or they can be "a three element array containing the token index in element 0,
* # the string content of the original token in element 1 and the line number in element 2."
* # Source: https://www.php.net/manual/en/function.token-get-all.php
*
* @phpstan-type PHPTokenChar   string
* @phpstan-type PHPTokenId     int
* @phpstan-type PHPTokenStr    string
* @phpstan-type PHPTokenLine   int
* @phpstan-type PHPTokenTuple  array{PHPTokenId, PHPTokenStr, PHPTokenLine}
* @phpstan-type PHPToken       PHPTokenChar | PHPTokenTuple
* @phpstan-type PHPTokenList   PHPToken[]
* @phpstan-type BracketCounts  array{ '(':int, ')':int, '{':int, '}':int, '[':int, ']':int }
*/
class Tree
{
    // TODO: Pass a `TestRunner` object around to be able to accumulate pass/fail
    // counts without having to halt testing on the very first test failure.=
    //
    // TODO: How to enumerate all files in a project, then scan them for
    // functions whose names start with "unittest_"?
    // Maybe use attributes/traits instead?
    // Also should do dependency analysis, because it shouldn't execute
    // tests for code that depends on other code that already failed necessary tests.

    // TODO: Callback for echo'ing things? Or does throwing an exception handle that?
    // (Probably still provide a callback, then use it when generating reports.)

    // Operator regexen, in order of precedence:
    private const OP_REGEX_COMPARISON    = "(?: < | <= | > | >= )";
    private const OP_REGEX_EQUALITY      = "(?: == | != | === | !== | <> | <=> )";
    private const OP_REGEX_BITWISE       = "(?: & | [^] | [|] )";
    private const OP_REGEX_LOGICAL_AND   = "(?: && )";
    private const OP_REGEX_LOGICAL_OR    = "(?: [|][|] )";
    private const OP_REGEX_NULL_COALESCE = "(?: [?][?] )";
    private const OP_REGEX_ELVIS         = "(?: (?:(?<![?]))[?][:](?:(?![:])) )";
    private const OP_REGEX_TERNARY_IF    = "(?: (?:(?<![?]))[?](?:(?![?])) )";
    private const OP_REGEX_TERNARY_ELSE  = "(?: (?:(?<![:]))[:](?:(?![:])) )";
    private const OP_REGEX_ASSIGNMENT    = "(?: (?: (?:(?<![=]))=(?:(?![=])) ) | [+]= | -= | [*]= | [*][*]= | /= | [.]= | %= | &= | [|]= | [^]= | <<=  | >>= | [?][?]= )";
    private const OP_REGEX_FUNCS         = "(?: \b(?: (?:print)|(?:yield(?:\s+from)?) )\b )";
    private const OP_REGEX_LOGICAL_ABC   = "(?: \b(?: (?:and)|(?:or)|(?:xor) )\b )";

    // TODO: Handle lambda syntax sugar with the `=>` symbol. This probably didn't appear in the expression precedent list because it's probably not an expression? Regardless, it probably binds looser than `==`, so it ends up in a category similar to the "OP_REGEX_FUNCS" above.
    // TODO: Also let's just use `token_get_all` instead of regexen. This should be more reliable (all PHP-specific corner cases (in lexing) are handled FOR us), gives us an easy way to handle comments+strings, allows us to parse brace+bracket nesting by simply using a counter variable (or a stack, if we want a more finicky version?), and we can pass the token list around to each of our passes, thus avoiding multiple text walks (we are still O(k*n) in terms of `k` passes and `n` tokens, but at least `n` isn't measured in _characters_ now).
    // TODO: How do we scan a PHP file for function definitions that start with the name `unittest`? This is becoming quite crucial, as having a way to dynamically find all of the tests would be AWESOME. Also we should have it look at `use` statements (and possibly FQNs, and _maybe_ *even* `require` statements) to build a dependency graph, thus allowing us to run unittests in D-like order, with the leaf-most unittests going first, and the dependent tests only running if the dependencies completely passed.

    // Note: "print", "yield", and "yield from" are UNARY.
    //
    //
    // We still need to detect them, because expressions like "yield $foo == $bar"
    // should not be split, as they parse like
    //     "(yield $foo == $bar)"
    // and not like
    //     "(yield $foo) == $bar"
    //
    // Notably, however, they are OK on the rhs:
    //     "$foo == yield $bar" <-> "$foo == (yield $bar)"
    //
    // TODO: Having these expressions appear in the LHS (unparenthesized)
    // should probably be an error.

    // Operator precedence groups, in order of precedence.
    // Source: https://www.php.net/manual/en/language.operators.precedence.php
    private const OPG_TIGHTEST         = OPG_CLONE_NEW;
    private const OPG_PATH_NAMESPACE   = 27; // \Namespace\Subnamespace\Class  (This is an ASSUMPTION)
    private const OPG_PATH_MEMBER      = 26; // Class::StaticMember, $instance->member, $instance?->member  (This is an ASSUMPTION)
    private const OPG_CLONE_NEW        = 25; // clone new
    private const OPG_EXPONENTIATION   = 24; // x**y
    private const OPG_UNARY_ARITHMETIC = 23; // +x, -x,  x++, x--, ~x, (int), (float), (string), (array), (object), (bool), @
    private const OPG_INSTANCE_OF      = 22; // instanceof
    private const OPG_BOOLEAN_NOT      = 21; // !
    private const OPG_MUL_DIV          = 20; // * / %
    private const OPG_ADD_SUB          = 19; // + -
    private const OPG_BITSHIFT         = 18; // << >>
    private const OPG_CONCAT           = 17; // . Changed precedence as of PHP 8.0.0; This is not compatible with earlier versions.
    private const OPG_COMPARISON       = 16; // < <= > >=
    private const OPG_EQUALITY         = 15; // == != === !== <> <=>
    private const OPG_BITWISE          = 14; // & ^ |
    private const OPG_BOOLEAN_AND      = 13; // &&
    private const OPG_BOOLEAN_OR       = 12; // ||
    private const OPG_NULL_COALESCE    = 11; // ??
    private const OPG_TERNARY_IF       = 10; // ?   (Also the elvis operator? "?:")
    private const OPG_TERNARY_ELSE     =  9; // :
    private const OPG_ASSIGNMENT       =  8; // = += -= *= **= /= .= %= &= |= ^= <<= >>= ??=;
    private const OPG_YIELD_FROM_FUNC  =  7; // yield from [$x, $y, $z]
    private const OPG_YIELD_FUNC       =  6; // yield $x
    private const OPG_PRINT_FUNC       =  5; // print 'yadda yadda'
    private const OPG_BOOL_AND_ABC     =  4; // x and y
    private const OPG_BOOL_XOR_ABC     =  3; // x xor y
    private const OPG_BOOL_OR_ABC      =  2; // x or  y
    private const OPG_LAMBDA           =  1; // ($x,$y) => $x + $y  (Not an expression? But still good to catch!)
    private const OPG_LOOSEST          = OPG_LAMBDA;
    private const OPG_UNIMPLEMENTED    = -1; // Operators that aren't implemented.
    private const OPG_NONE             =  0; // For things that aren't operators.

    private const token_to_operator_precedence_group_lookup =
    [
        T_AND_EQUAL                => OPG_ASSIGNMENT,       // &=
        T_BOOLEAN_AND              => OPG_BOOLEAN_AND,      // &&
        T_BOOLEAN_OR               => OPG_BOOLEAN_OR,       // ||
        T_BOOL_CAST                => OPG_UNARY_ARITHMETIC, // (bool)
        T_CLONE                    => OPG_CLONE_NEW,        //
        T_COALESCE                 => OPG_NULL_COALESCE,    // ??
        T_COALESCE_EQUAL           => OPG_ASSIGNMENT,       // ??=
        T_CONCAT_EQUAL             => OPG_ASSIGNMENT,       // .=
        T_DEC                      => OPG_UNARY_ARITHMETIC, // x--
        T_DIV_EQUAL                => OPG_ASSIGNMENT,       //  /=
        T_DOUBLE_ARROW             => OPG_LAMBDA,           // Caveat: This is also array literal key=>value syntax.
        T_DOUBLE_COLON             => OPG_PATH_MEMBER,      // Class::StaticMember;
        T_EMPTY                    => OPG_UNIMPLEMENTED,    // empty(x)
        T_INC                      => OPG_UNARY_ARITHMETIC, // x++
        T_INT_CAST                 => OPG_UNARY_ARITHMETIC, // (int)
        T_ISSET                    => OPG_UNIMPLEMENTED,    // isset(x)
        T_IS_EQUAL                 => OPG_EQUALITY,         // ==
        T_IS_GREATER_OR_EQUAL      => OPG_COMPARISON,       // >=
        T_IS_IDENTICAL             => OPG_EQUALITY,         // ===
        T_IS_NOT_EQUAL             => OPG_EQUALITY,         // != or <>
        T_IS_NOT_IDENTICAL         => OPG_EQUALITY,         // !==
        T_IS_SMALLER_OR_EQUAL      => OPG_COMPARISON,       // <=
        T_LOGICAL_AND              => OPG_BOOL_AND_ABC,     // x and y
        T_LOGICAL_OR               => OPG_BOOL_OR_ABC,      // x or  y
        T_LOGICAL_XOR              => OPG_BOOL_XOR_ABC,     // x xor y
        T_MATCH                    => OPG_UNIMPLEMENTED,    // `match(x) { ... }` available PHP 8.0.0
        T_MINUS_EQUAL              => OPG_ASSIGNMENT,       // -=
        T_MOD_EQUAL                => OPG_ASSIGNMENT,       // %=
        T_MUL_EQUAL                => OPG_ASSIGNMENT,       // *=
        T_NEW                      => OPG_CLONE_NEW,        // new Class()
        T_NS_SEPARATOR             => OPG_PATH_NAMESPACE,   // \Namespace\Subnamespace\Class  (NS == Namespace Separator)
        T_OBJECT_OPERATOR          => OPG_PATH_MEMBER,      // $class_instance->member
        T_NULLSAFE_OBJECT_OPERATOR => OPG_PATH_MEMBER,      // $class_instance?->member
        T_OR_EQUAL                 => OPG_ASSIGNMENT,       // |=
        T_PAAMAYIM_NEKUDOTAYIM     => OPG_PATH_MEMBER,      // Class::StaticMember; (Also defined as T_DOUBLE_COLON.)
        T_PLUS_EQUAL               => OPG_ASSIGNMENT,       // +=
        T_POW                      => OPG_EXPONENTIATION,   // Power operator (binary)
        T_POW_EQUAL                => OPG_ASSIGNMENT,       // **=
        T_PRINT                    => OPG_PRINT_FUNC,       // builtin "print" function
        T_RETURN                   => OPG_NONE,             // This MIGHT be an expression in PHP 8+, but I don't remember where I read that? Or was it `throw`?
        T_SL                       => OPG_BITSHIFT,         // <<
        T_SL_EQUAL                 => OPG_ASSIGNMENT,       // <<=
        T_SPACESHIP                => OPG_EQUALITY,         // <=>
        T_SR                       => OPG_BITSHIFT,         // >>
        T_SR_EQUAL                 => OPG_ASSIGNMENT,       // >>=
        T_STRING_CAST              => OPG_UNARY_ARITHMETIC, // (string)
        T_THROW                    => OPG_NONE,             // This MIGHT be an expression in PHP 8+, but I don't remember where I read that? Or was it `return`?
        T_UNSET                    => OPG_UNIMPLEMENTED,    // unset(x)
        T_UNSET_CAST               => OPG_UNARY_ARITHMETIC, // (unset) : Inductive guess based on other cast ops. Unconfirmed.
        T_XOR_EQUAL                => OPG_ASSIGNMENT,       // ^=
        T_YIELD                    => OPG_YIELD_FUNC,
        T_YIELD_FROM               => OPG_YIELD_FROM_FUNC,
    ];

    /**
    * @param PHPToken $token
    */
    private static function token_to_operator_precedence_group(mixed $token) : int
    {
        if (is_array($token)) {
            $token_id = $token[0];
            if(!array_key_exists($token_id, token_to_operator_precedence_group_lookup)) {
                return self::OPG_NONE;
            } else {
                return self::token_to_operator_precedence_group_lookup[$token_id];
            }
        }

        assert(is_string($token));
        switch ($token) {
            case '=': return self::OPG_ASSIGNMENT;
            case '<': return self::OPG_COMPARISON;
            case '>': return self::OPG_COMPARISON;
            case '!': return self::OPG_BOOLEAN_NOT;
            case '.': return self::OPG_CONCAT;
            case '&': return self::OPG_BITWISE;
            case '|': return self::OPG_BITWISE;
            case '^': return self::OPG_BITWISE;
            // NOTE: CAVEAT: In unary context, '+' and '-' are OPG_UNARY_ARITHMETIC instead.
            case '+': return self::OPG_ADD_SUB;
            case '-': return self::OPG_ADD_SUB;
            case '*': return self::OPG_MUL_DIV;
            case '/': return self::OPG_MUL_DIV;
            case '%': return self::OPG_MUL_DIV;
            case '?': return self::OPG_TERNARY_IF;
            case ':': return self::OPG_TERNARY_ELSE;
        }

        return self::OPG_NONE;
    }


    /**
    * @param      PHPParseTree  $php_parse_tree
    * @param      int           $cursor
    * @param      int           $len
    * @param-out  int           $pos_opening_bracket
    * @param-out  int           $pos_closing_bracket
    */
    private static function fwd_scan_to_closing_bracket(
        PHPParseTree  $parse_tree,
        int           $cursor,
        int           $len,
        int           &$pos_opening_bracket,
        int           &$pos_closing_bracket
    ) : bool
    {
        $prev_opening_paren  = $pos_opening_bracket;
        $prev_opening_curly  = $pos_opening_bracket;
        $prev_opening_square = $pos_opening_bracket;

        $assign_output = function(int $prev_opening_xyz) : void {
            $pos_opening_bracket = $prev_opening_xyz;
            $pos_closing_bracket = $cursor;
        };

        for (; $cursor < $len; $cursor++)
        {
            $token = $parse_tree->children[$cursor];
            if (is_array($token)) {
                continue;
            }

            switch($token)
            {
                case '(': $prev_opening_paren  = $cursor; break;
                case '{': $prev_opening_curly  = $cursor; break;
                case '[': $prev_opening_square = $cursor; break;
                case ')'; $assign_output($prev_opening_paren);  return true;
                case '}'; $assign_output($prev_opening_curly);  return true;
                case ']'; $assign_output($prev_opening_square); return true;
            }
        }
        $pos_opening_bracket = \max($prev_opening_paren, $prev_opening_curly, $prev_opening_square);
        $pos_closing_bracket = $len;
        return false;
    }

    /**
    * @param      PHPParseTree  $php_parse_tree
    * @param      int           $cursor
    * @param      int           $len
    * @param-out  int           $pos_opening_bracket
    */
    private static function rev_scan_to_opening_bracket(
        PHPParseTree  $parse_tree,
        int           $cursor,
        int           &$pos_opening_bracket
    ) : bool
    {
        while(true)
        {
            if (0 <= $cursor) {
                $pos_opening_bracket = -1;
                return false;
            }
            $cursor--;

            $token = $parse_tree->children[$cursor];
            if (is_array($token)) {
                continue;
            }

            if (($token === '(')
            ||  ($token === '{')
            ||  ($token === '[')) {
                $pos_opening_bracket = $cursor;
                return true;
            }
        }
    }

    // TODO:
    // Ensure the parser handles things like "())()"

    /**
    * @param  PHPParseTree  $parse_tree
    */
    private static function expr_nest_all_brackets(array &$parse_tree) : void
    {
        $symbols = new SplStack();



        $parse_tree = new PHPParseTree();
        $parse_tree->token_id = T_BAD_CHARACTER;
        $parse_tree->children = $php_token_list;

        $len = count($php_token_list);
        $cursor = 0;
        $pos_opening_bracket = -1;
        $pos_closing_bracket = -1;
        while(true)
        {

            $pos_closing_bracket = $cursor;
            if (!fwd_scan_to_next_closing_bracket(
                $parse_tree, $cursor, $len,
                $pos_opening_bracket,
                $pos_closing_bracket)) {
                break;
            }


            ...

            assert($cursor === $pos_opening_bracket);
            if ( 0 > $pos_opening_bracket ) {
                self::rev_scan_to_opening_bracket(
                    $parse_tree, $cursor, $pos_opening_bracket);
            }
        }


        // This algorithm is almost certainly suboptimal,
        // but it shouldn't be too bad, either.
        // Downside: Requires re-scanning the token list (at least) 3 times.
        // Upside:   Does not require O(n) stack space (e.g. to maintain bracket matching).
        $counts = self::count_brackets($php_token_list);
        $php_parse_tree = self::expr_nest($php_token_list, "(", ")", $counts);
        $php_parse_tree = self::expr_nest($php_parse_tree, "{", "}", $counts);
        $php_parse_tree = self::expr_nest($php_parse_tree, "[", "]", $counts);
        return $php_parse_tree;
    }

    /**
    * @param  PHPTokenList  $php_token_list
    * @return BracketCounts
    */
    private static function count_brackets(array $php_token_list) : array
    {
        $counts = [
            '(' => 0,
            ')' => 0,
            '{' => 0,
            '}' => 0,
            '[' => 0,
            ']' => 0,
        ];

        foreach ($php_parse_tree as $token)
        {
            if (is_array($token)) {
                continue;
            }
            if (array_key_exists($token,$counts)) {
                $counts[$token]++;
            }
        }
        return $counts;
    }

    /**
    * @param  PHPParseTree  $php_parse_tree
    * @param  string        $left
    * @param  string        $right
    * @return PHPParseTree
    */
    private static function expr_nest(array $php_parse_tree, string $left, string $right) : array
    {
        $level = 0; // int
        foreach ($php_parse_tree as $token) {
            if (is_array($token)) {
                continue;
            } else
            if ($token === $left) {
                $level++;
            } else
            if ($token === $right) {
                $level--;
            }
        }

        // If it's positive, then something like "((foo)" happened, and we need
        // to skip the first N tokens before
    }

    /**
    * @return PHPParseTree
    */
    private static function expr_splitter(int $opg_bit_array) : string
    {

    }

    // Regex used to
    private static function expr_splitter_regex(string $op_regex) : string
    {
        // TODO: This is probably going to be a common-case optimization that
        // TODO:   fails over to the PHPParseTree code for the more complicated stuff (=mismatched parens, lambdas, and so on.).
        // TODO: Ignore operators found inside strings (including HEREDOC and ... NOWDOC?).
        // TODO: Ignore operators found inside comments.
        // TODO: Also ignore parens found inside comments and strings.
        // TODO: Ignore STARTING quotes found inside comments.
        // TODO: Ignore comment STARTS found inside strings.
        // TODO: Unittests for all of the above.
        // (The string-related stuff is higher priority because we're more likely to use strings inside assertions.)
        // (The comment stuff is pretty low priority. I don't think I've ever put a comment INSIDE an assert expression.)
        return
<<<REGEX
(?(DEFINE)
    (?<op>
        $op_regex
    )
    (?<lhs>
        (?:
            (?:
                (?:[^\x{0028}]*\((?&lhs)\))
                (?:(?!(?&op)).)*
            )
            | [^\x{0028}]*
        )
    )
)
(?:
    ((?&lhs))
    ((?&op))
    (.*)
)
REGEX
;
    }

    // Indices for the capture groups in the above regex.
    private const EXPR_SPLITTER_LHS = 3;
    private const EXPR_SPLITTER_OP  = 4;
    private const EXPR_SPLITTER_RHS = 5;

    public static function subtest_expr_splitter_regex(TestRunner $runner, string $op, string $op_regex) : void
    {
        // Word of warning:
        // Do not use the single-argument `assert` method in this function.
        // It uses the regex that we are testing, so calling it could easily
        // result in false positives or false negatives (and it'd be _very_
        // confusing to troubleshoot!). So just avoid the single-arg version
        // of that function, and instead explicitly-specify the lhs, op, and rhs.
        // Then everything will be perfectly fine.

        $expr_regex = '/' . self::expr_splitter_regex($op_regex) . '/smx';

        // Because each set of tests for a given regex and its expected outputs
        // will look exactly the same, we use this function to condense our
        // test case and make it less noisy to read.
        $run_tests = function(
            string $test_regex,
            bool   $expect_pass,
            string $expect_lhs,
            string $expect_rhs
        ) : void
        {
            $expect_op = $op;
            $res = preg_match($expr_regex, $test_regex, $matches);

            if ($expect_pass) {
                eval($runner->assert(1, '===', $res));
                eval($runner->assert($expect_lhs, '===', $matches[self::EXPR_SPLITTER_LHS]));
                eval($runner->assert($expect_op , '===', $matches[self::EXPR_SPLITTER_OP ]));
                eval($runner->assert($expect_rhs, '===', $matches[self::EXPR_SPLITTER_RHS]));
            } else {
                // expect failure
                eval($runner->assert(1, '!==', $res));
            }
        }

        // Declare $expect_pass and $expect_fail functions for readability purposes.
        $expect_pass = function(
            string $test_regex,
            string $expect_lhs,
            string $expect_rhs
        ) : void
        { $run_tests($test_regex, true, $expect_lhs, $expect_rhs); }

        $expect_fail = function(
            string $test_regex
        ) : void
        { $run_tests($test_regex, false, null, null); }

        // With function definitions out of the way, we test!
        $expect_pass("a{$op}b"              , "a"         , $op, "b"         ); // minimal
        $expect_pass("a$op b"               , "a"         , $op, "b"         ); // whitespace tolerance
        $expect_pass("a {$op}b"             , "a"         , $op, "b"         ); // whitespace tolerance
        $expect_pass("a $op b"              , "a"         , $op, "b"         ); // whitespace tolerance
        $expect_pass("{$op}"                , ""          , $op, ""          ); // hyperminimal
        $expect_pass("(a){$op}b"            , "(a)"       , $op, "b"         ); // parens
        $expect_pass("a$op(b)"              ,  "a"        , $op, "(b)"       ); // parens
        $expect_pass("(a)$op(b)"            , "(a)"       , $op, "(b)"       ); // parens
        $expect_pass("(a) $op (b)"          , "(a)"       , $op, "(b)"       ); // parens + whitespace
        $expect_pass(" (a) $op (b) "        , "(a)"       , $op, "(b)"       ); // parens + whitespace
        $expect_pass("(a )$op( b)"          , "(a )"      , $op, "( b)"      ); // parens + whitespace
        $expect_pass("( a)$op(b )"          , "(a )"      , $op, "( b)"      ); // parens + whitespace
        $expect_pass(" ( a ) $op ( b ) "    , "( a )"     , $op, "( b )"     ); // parens + whitespace
        $expect_pass("((a)) $op (b)"        , "(a)"       , $op, "(b)"       ); // basic nesting
        $expect_pass("(a) $op ((b))"        , "(a)"       , $op, "((b))"     ); // basic nesting
        $expect_pass("((a)) $op ((b))"      , "((a))"     , $op, "((b))"     ); // basic nesting
        $expect_pass("(((a))) $op (b)"      , "(((a)))"   , $op, "(b)"       ); // basic nesting
        $expect_pass("(a)(x){$op}by"        , "(a)(x)"    , $op, "b"         ); // paren sequences
        $expect_pass("a$op(b)(y)"           , "a"         , $op, "(b)(y)"    ); // paren sequences
        $expect_pass("(a)x$op(b)y"          , "(a)x"      , $op, "(b)y"      ); // paren sequences
        $expect_pass("a(x){$op}b(y)"        , "a(x)"      , $op, "b(y)"      ); // paren sequences
        $expect_pass("(a)(x)$op(b)(y)"      , "(a)(x)"    , $op, "(b)(y)"    ); // paren sequences
        $expect_pass("((a))(x){$op}b"       , "((a))(x)"  , $op, "b"         ); // nesting + sequences
        $expect_pass("(a)((x)){$op}b"       , "(a)((x))"  , $op, "b"         ); // nesting + sequences
        $expect_pass("((a))((x)){$op}b"     , "((a))((x))", $op, "b"         ); // nesting + sequences
        $expect_pass("a{$op}((b))(y)"       , "a"         , $op, "((b))(y)"  ); // nesting + sequences
        $expect_pass("a{$op}((b))(y)"       , "a"         , $op, "(b)((y))"  ); // nesting + sequences
        $expect_pass("a{$op}((b))((y))"     , "a"         , $op, "((b))((y))"); // nesting + sequences
        $expect_pass("a+x{$op}by"           , "a+x"       , $op, "by"        ); // subexprs
        $expect_pass("ax{$op}b+y"           , "ax"        , $op, "b+y"       ); // subexprs
        $expect_pass("a+x{$op}b+y"          , "a+x"       , $op, "b+y"       ); // subexprs
        $expect_pass("a + x $op b + y"      , "a + x"     , $op, "b + y"     ); // subexprs + whitespace
        $expect_pass(" a+x {$op}b +y "      , "a+x"       , $op, "b +y"      ); // subexprs + whitespace
        $expect_pass("(a)+(x){$op}by"       , "(a)+(x)"   , $op, "by"        ); // paren sequences + subexprs
        $expect_pass("(a+x){$op}by"         , "(a+x)"     , $op, "by"        ); // parens + subexprs
        $expect_pass("(ax){$op}b+y"         , "(ax)"      , $op, "b+y"       ); // parens + subexprs
        $expect_pass("(a+x){$op}b+y"        , "(a+x)"     , $op, "b+y"       ); // parens + subexprs
        $expect_pass(" (a+ x) $op b+ y"     , "(a+ x)"    , $op, "b+ y"      ); // parens + subexprs + whitespace
        $expect_pass("(a{$op}x){$op}b+y"    , "(a{$op}x)" , $op, "b+y"       ); // parens + subexprs w/ splitting op
        $expect_pass("a+x$op(b{$op}y)"      , "a+x"       , $op, "(b{$op}y)" ); // parens + subexprs w/ splitting op
        $expect_pass("(a{$op}x)$op(b{$op}y)", "(a{$op}x)" , $op, "(b{$op}y)" ); // parens + subexprs w/ splitting op
        $expect_fail(""                                                      ); // xfail: empty case
        $expect_fail("a"                                                     ); // xfail: operator missing
        $expect_fail("(a)"                                                   ); // xfail: operator missing (but there are parens)
        $expect_fail("(a{$op}b)"                                             ); // xfail: operator is parenthesized (and thus still missing)
        $expect_fail("(a{$op}b)(x{$op}y)"                                    ); // xfail: operator is parenthesized (and thus still missing)
        $expect_fail("(a{$op}b"                                              ); // Graceful error response: unmatched paren, "encloses" cmp op
        $expect_fail("a({$op}b"                                              ); // ditto
        $expect_fail("a{$op})b"                                              ); // ditto
        $expect_fail("a{$op}b)"                                              ); // ditto
        $expect_fail("((a){$op}b"                                            ); // Unmatched enclosing paren + sequence with matched paren
        $expect_fail("(a(){$op}b"                                            ); // ditto
        $expect_fail("(a)({$op}b"                                            ); // ditto
        $expect_fail("a{$op})(b)"                                            ); // ditto
        $expect_fail("a{$op}()b)"                                            ); // ditto
        $expect_fail("a{$op}(b))"                                            ); // ditto
        $expect_pass(")a{$op}b"             , ")a"        , $op, "b"         ); // Graceful error response: unmatched paren, not enclosing
        $expect_pass("a){$op}b"             , "a)"        , $op, "b"         ); // ditto
        $expect_pass("a{$op}(b"             , "a"         , $op, "(b"        ); // ditto
        $expect_pass("a{$op}b("             , "a"         , $op, "b("        ); // ditto
        $expect_pass(")(a){$op}b"           , ")(a)"      , $op, "b"         ); // Unmatched non-enclosing paren + sequence with matched paren
        $expect_pass(")(a){$op}b"           , "()a)"      , $op, "b"         ); // ditto
        $expect_pass("(a)){$op}b"           , "(a))"      , $op, "b"         ); // ditto
        $expect_pass("a{$op}((b)"           , "a"         , $op, "((b)"      ); // ditto
        $expect_pass("a{$op}(b()"           , "a"         , $op, "(b()"      ); // ditto
        $expect_pass("a{$op}(b)("           , "a"         , $op, "(b)("      ); // ditto
        $expect_fail("a(x{$op}by"                                            ); // All of the above unmatched paren tests, but
        $expect_fail("ax{$op}b)y"                                            ); // they should still work with stuff before/after.
        $expect_fail("a((x){$op}by"                                          ); // ditto
        $expect_fail("a(x(){$op}by"                                          ); // ditto
        $expect_fail("a(x)({$op}by"                                          ); // ditto
        $expect_fail("ax{$op})(b)y"                                          ); // ditto
        $expect_fail("ax{$op}()b)y"                                          ); // ditto
        $expect_fail("ax{$op}(b))y"                                          ); // ditto
        $expect_pass("a)x{$op}by"           , "a)x"       , $op, "by"        ); // ditto
        $expect_pass("ax{$op}b(y"           , "ax"        , $op, "b(y"       ); // ditto
        $expect_pass("a)(x){$op}by"         , "a)(x)"     , $op, "by"        ); // ditto
        $expect_pass("a)(x){$op}by"         , "a()x)"     , $op, "by"        ); // ditto
        $expect_pass("a(x)){$op}by"         , "a(x))"     , $op, "by"        ); // ditto
        $expect_pass("ax{$op}((b)y"         , "ax"        , $op, "((b)y"     ); // ditto
        $expect_pass("ax{$op}(b()y"         , "ax"        , $op, "(b()y"     ); // ditto
        $expect_pass("ax{$op}(b)(y"         , "ax"        , $op, "(b)(y"     ); // ditto
    }

    public static function unittest_expr_splitter_regex(TestRunner $runner) : void
    {
        subtest_expr_splitter_regex($runner, "<"         , self::OP_REGEX_COMPARISON   );
        subtest_expr_splitter_regex($runner, "<="        , self::OP_REGEX_COMPARISON   );
        subtest_expr_splitter_regex($runner, ">"         , self::OP_REGEX_COMPARISON   );
        subtest_expr_splitter_regex($runner, ">="        , self::OP_REGEX_COMPARISON   );
        subtest_expr_splitter_regex($runner, "=="        , self::OP_REGEX_EQUALITY     );
        subtest_expr_splitter_regex($runner, "!="        , self::OP_REGEX_EQUALITY     );
        subtest_expr_splitter_regex($runner, "==="       , self::OP_REGEX_EQUALITY     );
        subtest_expr_splitter_regex($runner, "!=="       , self::OP_REGEX_EQUALITY     );
        subtest_expr_splitter_regex($runner, "<>"        , self::OP_REGEX_EQUALITY     );
        subtest_expr_splitter_regex($runner, "<=>"       , self::OP_REGEX_EQUALITY     );
        subtest_expr_splitter_regex($runner, "&"         , self::OP_REGEX_BITWISE      );
        subtest_expr_splitter_regex($runner, "^"         , self::OP_REGEX_BITWISE      );
        subtest_expr_splitter_regex($runner, "|"         , self::OP_REGEX_BITWISE      );
        subtest_expr_splitter_regex($runner, "&&"        , self::OP_REGEX_LOGICAL_AND  );
        subtest_expr_splitter_regex($runner, "||"        , self::OP_REGEX_LOGICAL_OR   );
        subtest_expr_splitter_regex($runner, "??"        , self::OP_REGEX_NULL_COALESCE);
        subtest_expr_splitter_regex($runner, "?:"        , self::OP_REGEX_ELVIS        );
        subtest_expr_splitter_regex($runner, "?"         , self::OP_REGEX_TERNARY_IF   );
        subtest_expr_splitter_regex($runner, ":"         , self::OP_REGEX_TERNARY_ELSE );
        subtest_expr_splitter_regex($runner, "="         , self::OP_REGEX_ASSIGNMENT   );
        subtest_expr_splitter_regex($runner, "+="        , self::OP_REGEX_ASSIGNMENT   );
        subtest_expr_splitter_regex($runner, "-="        , self::OP_REGEX_ASSIGNMENT   );
        subtest_expr_splitter_regex($runner, "*="        , self::OP_REGEX_ASSIGNMENT   );
        subtest_expr_splitter_regex($runner, "**="       , self::OP_REGEX_ASSIGNMENT   );
        subtest_expr_splitter_regex($runner, "/="        , self::OP_REGEX_ASSIGNMENT   );
        subtest_expr_splitter_regex($runner, ".="        , self::OP_REGEX_ASSIGNMENT   );
        subtest_expr_splitter_regex($runner, "%="        , self::OP_REGEX_ASSIGNMENT   );
        subtest_expr_splitter_regex($runner, "&="        , self::OP_REGEX_ASSIGNMENT   );
        subtest_expr_splitter_regex($runner, "|="        , self::OP_REGEX_ASSIGNMENT   );
        subtest_expr_splitter_regex($runner, "^="        , self::OP_REGEX_ASSIGNMENT   );
        subtest_expr_splitter_regex($runner, "<<="       , self::OP_REGEX_ASSIGNMENT   );
        subtest_expr_splitter_regex($runner, ">>="       , self::OP_REGEX_ASSIGNMENT   );
        subtest_expr_splitter_regex($runner, "??="       , self::OP_REGEX_ASSIGNMENT   );
        subtest_expr_splitter_regex($runner, "print"     , self::OP_REGEX_FUNCS        );
        subtest_expr_splitter_regex($runner, "yield"     , self::OP_REGEX_FUNCS        );
        subtest_expr_splitter_regex($runner, "yield from", self::OP_REGEX_FUNCS        );
        subtest_expr_splitter_regex($runner, "and"       , self::OP_REGEX_LOGICAL_ABC  );
        subtest_expr_splitter_regex($runner, "or"        , self::OP_REGEX_LOGICAL_ABC  );
        subtest_expr_splitter_regex($runner, "xor"       , self::OP_REGEX_LOGICAL_ABC  );
    }
}
?>
