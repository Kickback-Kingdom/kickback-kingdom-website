<?php
declare(strict_types=1);

namespace Kickback\Common\Unittesting;

use Kickback\Common\Unittesting\AssertException;
use Kickback\Common\Unittesting\AssertFailureException;
use Kickback\Common\Unittesting\AssertParseException;

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
class TestRunner extends CoreTestRunner
{


    // TODO: Less sucky name?
    public function assert(string $expr_lhs, ?string $op = null, ?string $expr_rhs = null) : string
    {
        if ( !isset($op) && !isset($expr_rhs) )
        {
            // Attempt to detect $op and $expr_rhs if they aren't passed.
            // (That is; use a regex to find out if it's _actually_
            // a binary comparison, using some simple rules.)
            // TODO: use \Kickback\Utility\PHPRegex\Tree.php
            $matches = {};
            $ret = preg_match("/".
                "(?(DEFINE) ())"
                "(.*?\((?1)*?\))". //
                "/smx", $expr_lhs, $matches);
            assert($ret !== false);
            if ($ret === 1)
            {
                // $matches[0] -> match containing entire expression
                // $matches[1] -> match containing left-hand-side (lhs)
                // $matches[2] -> match containing operator (ex: ===, ==, !==, !=, <=, >=, <, >)
                // $matches[3] -> match containing right-hand-side (rhs)
                assert(4 === count($matches));
                for ($i = 0; $i < 4; $i++)
                {
                    assert(isset($matches[$i]));
                    assert(0 < strlen($matches[$i]));
                }
                unset($i);

                $expr_lhs = $matches[1];
                $op       = $matches[2];
                $expr_rhs = $matches[3];
            }
            // Else $op and $expr_rhs remain unset, which falls into the
            //    `$this->assert_nullary($expr_lhs)` case, below.
        }

        if ( isset($op) && isset($expr_rhs) ) {
            return $this->assert_binary($expr_lhs, $op, $expr_rhs);
        } else {
            return $this->assert_nullary($expr_lhs);
        }
    }

    // TODO: Implement this
    public function expect_exception(string $expr) : string
    {

    }
}
?>
