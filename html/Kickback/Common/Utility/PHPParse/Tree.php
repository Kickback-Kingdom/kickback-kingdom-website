<?php
declare(strict_types=1);

namespace Kickback\Common\Utility\PHPParse;

use Kickback\Common\Utility\PHPParse\Node;
use Kickback\Common\Utility\PHPParse\NodeType;

use Kickback\Common\Unittesting\TestRunnerCore;

/*
* @phpstan-type PHPTokenChar   string
* @phpstan-type PHPTokenId     int
* @phpstan-type PHPTokenStr    string
* @phpstan-type PHPTokenLine   int
* @phpstan-type PHPTokenTuple  array{PHPTokenId, PHPTokenStr, PHPTokenLine}
* @phpstan-type PHPToken       PHPTokenChar | PHPTokenTuple
* @phpstan-type PHPTokenList   PHPToken[]
*/
final class Tree
{
    public function __construct(
        public  Node  $root
    ) {}

    private const ROOT_TOKEN_ID           = T_BAD_CHARACTER;
    private const BRACKET_CURLY_TOKEN_ID  = T_BAD_CHARACTER;
    private const BRACKET_PAREN_TOKEN_ID  = T_BAD_CHARACTER;
    private const BRACKET_SQUARE_TOKEN_ID = T_BAD_CHARACTER;

    /**
    * Wrapper around `token_get_all`
    *
    * This operates just like `token_get_all` but does not require the expression
    * to begin with an HTML PHP opening tag.
    *
    * Internally, it does this:
    * 1. Add PHP tags to $php_expr as-needed.
    * 2. Calls `token_get_all` on modified expression.
    * 3. If PHP tags were added in step 1, they are removed from the token list.
    * 4. Returns the token list. (Profit!)
    *
    * @param  string       $php_expr
    * @return PHPTokenList
    */
    public static function tokenize_php(string $php_expr, TokenizeFlag $flags = TokenizeFlag::NONE) : array
    {
        // Add a PHP opening tag to the expression, as needed.
        // This is necessary because `token_get_all` will return
        // a single T_INLINE_HTML token if the expression doesn't begin
        // with a PHP tag.
        $had_opening_tag = false;
        $had_closing_tag = false;
        $php_expr = trim($php_expr);
        if (!\str_starts_with($php_expr, "<?php")
        &&  !\str_starts_with($php_expr, "<?")
        &&  !\str_starts_with($php_expr, "<%") ) {
            $had_opening_tag = false;
            $php_expr = "<?php" . $php_expr;
        }

        if (!\str_ends_with($php_expr, "?>")
        &&  !\str_ends_with($php_expr, "%>") ) {
            $had_closing_tag = false;
        }

        // Lex/Tokenize the PHP expression.
        $token_get_all_flags = 0;
        if ( 0 !== ($flags->value & TokenizeFlag::TOKEN_PARSE->value) ) {
            $token_get_all_flags = \TOKEN_PARSE;
        }
        $php_token_list = token_get_all($php_expr, $token_get_all_flags);
        assert(isset($php_token_list));
        assert(is_array($php_token_list));
        assert(1 < count($php_token_list));
        assert(3 === count($php_token_list[0]));
        $token_id = $php_token_list[0][0];
        assert((T_OPEN_TAG === $token_id) || (T_OPEN_TAG_WITH_ECHO === $token_id));

        // PHP tag remove/keep logic.
        $php_tag_mode = TokenizeFlag::from($flags->value & (TokenizeFlag::PHP_TAG_REMOVE->value | TokenizeFlag::PHP_TAG_KEEP->value));
        $php_tag_remove_opening = false;
        $php_tag_remove_closing = false;
        switch($php_tag_mode)
        {
            case TokenizeFlag::PHP_TAG_REMOVE:
                $php_tag_remove_opening = true;
                $php_tag_remove_closing = true;
                break;

            case TokenizeFlag::PHP_TAG_KEEP:
                $php_tag_remove_opening = false;
                $php_tag_remove_closing = false;
                break;

            // TokenizeFlag::PHP_TAG_PRESERVE and TokenizeFlag::NONE
            default:
                $php_tag_remove_opening = !$had_opening_tag;
                $php_tag_remove_closing = !$had_closing_tag;
                break;
        }

        // (Conditionally) Chop off opening PHP TAG, ex: `<?php`
        if ($php_tag_remove_opening) {
            $php_token_list = array_splice($php_token_list,0,1);
            if ( 0 !== count($php_token_list) ) {
                $token_id = $php_token_list[0][0];
                assert(T_OPEN_TAG !== $token_id);
                assert(T_OPEN_TAG_WITH_ECHO !== $token_id);
            }
        }

        // (Conditionally) Chop off closing PHP TAG.
        if (($php_tag_remove_closing)
        &&  (0 < count($php_token_list))
        &&  is_array(end($php_token_list))
        &&  (T_CLOSE_TAG === end($php_token_list)[0]) ) {
            $php_token_list = array_splice($php_token_list,-1);
        }

        return $php_token_list;
    }

    public static function from_expr(string $php_expr, TokenizeFlag $flags = TokenizeFlag::NONE) : Tree
    {
        return self::from_tokens(self::tokenize_php($php_expr));
    }

    /**
    * @param  PHPTokenList  $php_token_list
    * @return Tree
    */
    public static function from_tokens(array $php_token_list) : Tree
    {
        $root = new Node(self::ROOT_TOKEN_ID, NodeType::ROOT, $php_token_list);
        $parse_tree = new Tree($root);
        return $parse_tree;
    }

    private const BRACKET_TYPE_INVALID  = -1;
    private const BRACKET_TYPE_CURLY    = 0x00;
    private const BRACKET_TYPE_PAREN    = 0x01;
    private const BRACKET_TYPE_SQUARE   = 0x02;
    private const BRACKET_TYPE_TAG      = 0x03;
    private const BRACKET_TYPE_COUNT    = 4;

    private static function bracket_type_to_node_type(int $bracket_type) : NodeType
    {
        switch($bracket_type) {
            case self::BRACKET_TYPE_INVALID: return NodeType::INVALID;
            case self::BRACKET_TYPE_CURLY:   return NodeType::BRACKET_CURLY;
            case self::BRACKET_TYPE_PAREN:   return NodeType::BRACKET_PAREN;
            case self::BRACKET_TYPE_SQUARE:  return NodeType::BRACKET_SQUARE;
            case self::BRACKET_TYPE_TAG:     return NodeType::PHP_TAG;
        }
        return NodeType::INVALID;
    }

    private static function bracket_type_to_token_id(int $bracket_type) : int
    {
        switch($bracket_type) {
            case self::BRACKET_TYPE_INVALID: return NodeType::INVALID;
            case self::BRACKET_TYPE_CURLY:   return self::BRACKET_CURLY_TOKEN_ID;
            case self::BRACKET_TYPE_PAREN:   return self::BRACKET_PAREN_TOKEN_ID;
            case self::BRACKET_TYPE_SQUARE:  return self::BRACKET_SQUARE_TOKEN_ID;
            case self::BRACKET_TYPE_TAG:     return T_OPEN_TAG;
        }
        return NodeType::INVALID;
    }

    /** @var ?\SplFixedArray<int> */
    private ?\SplFixedArray $_prealloc_bracket_counts;
    private function &prealloc_bracket_counts() : \SplFixedArray
    {
        if ( !isset($this->_prealloc_bracket_counts) ) {
            $this->_prealloc_bracket_counts = new \SplFixedArray(self::BRACKET_TYPE_COUNT);
        }
        return $this->_prealloc_bracket_counts;
    }

    /**
    * @param     PHPTokenList   $token_list
    * @param     int            $index
    * @param-out int            $bracket_type
    * @param-out bool           $is_closing
    */
    private static function get_bracket_info_from_token_at(
        array $token_list, int $index, int &$token_id, int &$bracket_type, bool &$is_closing) : bool
    {
        $token = $token_list[$index];

        // Reshape $token==={(T_OPEN_TAG|T_CLOSE_TAG),$str,$line} into $token===(T_OPEN_TAG|T_CLOSE_TAG)
        // We want to treat PHP tags as bracketing elements, like parens, curly-braces, and square-braces.
        if (is_array($token)) {
            if ($token[0] === T_OPEN_TAG) {
                $token = T_OPEN_TAG;
            } else
            if ($token[1] === T_CLOSE_TAG) {
                $token = T_CLOSE_TAG;
            }
        }

        // Not a bracket token of any type.
        if (is_array($token)) {
            return false;
        }

        // Detect which bracket type it is.
        switch($token)
        {
            case '{':                   $is_closing = false; $bracket_type = self::BRACKET_TYPE_CURLY;  return true;
            case '(':                   $is_closing = false; $bracket_type = self::BRACKET_TYPE_PAREN;  return true;
            case '[':                   $is_closing = false; $bracket_type = self::BRACKET_TYPE_SQUARE; return true;
            case T_OPEN_TAG:            $is_closing = false; $bracket_type = self::BRACKET_TYPE_TAG;    return true;
            case T_OPEN_TAG_WITH_ECHO:  $is_closing = false; $bracket_type = self::BRACKET_TYPE_TAG;    return true;
            case '}';                   $is_closing = true;  $bracket_type = self::BRACKET_TYPE_CURLY;  return true;
            case ')';                   $is_closing = true;  $bracket_type = self::BRACKET_TYPE_PAREN;  return true;
            case ']';                   $is_closing = true;  $bracket_type = self::BRACKET_TYPE_SQUARE; return true;
            case T_CLOSE_TAG:           $is_closing = true;  $bracket_type = self::BRACKET_TYPE_TAG;    return true;
        }

        // Anything else is Not A Bracket.
        return false;
    }

    /**
    * Counts all brackets (e.g. {}, (), [], <?PHP ?>) from `$start_at` to either end of `$token_list`.
    *
    * Time-complexity is O(n) where `n` is the number of tokens.
    *
    * Thread-safe only if the given `\SplFixedArray` object is not shared
    * between threads during calls to this function.
    *
    * @param     PHPTokenList        $token_list
    * @param     int                 $start_at
    * @param     int                 $incr
    * @param-out \SplFixedArray<int> $bracket_counts
    */
    private static function count_brackets(array $token_list, int $start_at, int $incr, \SplFixedArray &$bracket_counts) : void
    {
        assert(self::BRACKET_TYPE_COUNT <= $bracket_counts->count());
        for($i = 0; $i < self::BRACKET_TYPE_COUNT; $i++) {
            $bracket_counts[$i] = 0;
        }

        $len = count($token_list);
        $cursor = $start_at;
        for(; (0 <= $cursor) && ($cursor < $len); $cursor += $incr)
        {
            $is_closing = false;
            $bracket_type = self::BRACKET_TYPE_INVALID;
            $token_id = T_BAD_CHARACTER;
            if (!self::get_bracket_info_from_token_at(
                $token_list, $cursor, $token_id, $bracket_type, $is_closing))
            {
                continue;
            }

            if ( $is_closing ) {
                $bracket_counts[$bracket_type]--;
            } else {
                $bracket_counts[$bracket_type]++;
            }
        }
    }

    /**
    * Shorthand for `self::count_brackets($token_list, $start_at, -1, $bracket_counts)`
    *
    * @see self::count_brackets
    *
    * @param     PHPTokenList        $token_list
    * @param     int                 $start_at
    * @param-out \SplFixedArray<int> $bracket_counts
    */
    private static function count_brackets_left(array $token_list, int $start_at, \SplFixedArray &$bracket_counts) : void {
        self::count_brackets($token_list, $start_at, -1, $bracket_counts);
    }

    /**
    * Shorthand for `self::count_brackets($token_list, $start_at, 1, $bracket_counts)`
    *
    * @see self::count_brackets
    *
    * @param     PHPTokenList        $token_list
    * @param     int                 $start_at
    * @param-out \SplFixedArray<int> $bracket_counts
    */
    private static function count_brackets_right(array $token_list, int $start_at, \SplFixedArray &$bracket_counts) : void {
        self::count_brackets($token_list, $start_at, 1, $bracket_counts);
    }

    /** @var ?\SplStack<int> */
    private ?\SplStack $_prealloc_bracket_stack;
    private function &prealloc_bracket_stack() : \SplStack
    {
        if ( !isset($this->_prealloc_bracket_stack) ) {
            $this->_prealloc_bracket_stack = new \SplStack();
        }
        return $this->_prealloc_bracket_stack;
    }

    private static function clear_stack(\SplStack &$stack) : void
    {
        $len = $stack->count();
        for($i = 0; $i < $len; $i++) {
            $stack->pop();
        }
    }

    /**
    * In the given `$target_node`, replace runs of bracketed tokens with single `PHPParse\Node` objects.
    *
    * `$target_node` is expected to be within the current `PHPParse\Tree` (`$this`) object.
    *
    * The current implementation has _at least_ O(n^2) time-complexity, but
    * this is only on very unlikely pathological inputs (ex: string full of
    * unmatched brackets.). This should otherwise be O(n) for sane inputs.
    * So this caveat shouldn't matter in most cases, but may matter in some
    * security contexts where it could be possible for an attacker to DoS
    * a system by feeding it inputs that are intentionally made to be pathological.
    *
    * This implementation is thread-safe only if the enclosing `PHPParse\Tree`
    * object is not shared between threads, or if access to the object is
    * synchronized so that no other thread is calling its methods or writing
    * to it during this method call. (As an optimization, this method
    * uses pre-allocated memory resources at the class-object level.)
    */
    public final function nest_all_brackets(?Node $target_node = null) : void
    {
        if ( is_null($target_node) ) {
            $target_node = $this->root;
        }
        assert(!is_null($target_node));
        $this->nest_all_brackets_impl($target_node, $this->prealloc_bracket_stack(), $this->prealloc_bracket_counts());
    }

    /**
    * @see self::nest_all_brackets
    *
    * @param     Node                $target_node
    * @param-out \SplStack<int>      $markers         The stack to use for matching up brackets. (Intended to be preallocated.)
    * @param-out \SplFixedArray<int> $bracket_counts  The array to use for counting brackets. (Intended to be preallocated.)
    */
    private function nest_all_brackets_impl(Node $target_node, \SplStack &$markers, \SplFixedArray &$bracket_counts) : void
    {
        self::clear_stack($markers);
        $token_list = &$target_node->children;
            var_dump($token_list);
        $len = count($token_list);
        echo "len == $len\n";
        $cursor = 0;
        for(; $cursor <= $len; $cursor++)
        {
            $eos = !($cursor < $len); // End of string.
            $is_closing   = false;
            $bracket_type = self::BRACKET_TYPE_INVALID;
            $token_id     = T_BAD_CHARACTER;
            if ($eos) {
                $is_closing   = true;
            }
            else
            if (!self::get_bracket_info_from_token_at(
                $token_list, $cursor, $token_id, $bracket_type, $is_closing))
            {
                continue;
            }

            if (!$eos) { // Invalid $bracket_type is OK if it's the EOS.
                assert($bracket_type !== self::BRACKET_TYPE_INVALID);
            }

            var_dump($markers);
            if (!$is_closing) {
                $opening_pos  = $cursor;
                $opening_type = $bracket_type;
                $markers->push($opening_pos);
                $markers->push($opening_type);
                continue;
            }

            var_dump($markers);
            var_dump($token_list);
            $closing_pos  = $cursor;
            $closing_type = $bracket_type;
            echo "closing_pos == $closing_pos\n";
            echo "opening_pos == $opening_pos\n";

            if ( $markers->isEmpty() ) {
                if ( $eos ) {
                    // All brackets have been matched, or string had no brackets. (success)
                    break;
                } else {
                    // Case where one or more closing brackets don't have matching opening brackets.
                    $opening_pos  = 0;
                    $opening_type = $closing_type;
                }
            } else {
                // There are two brackets at the same level of nesting.
                // (...but we don't know if they are the same type, yet.)
                $opening_type = $markers->pop();
                $opening_pos  = $markers->pop();
            }

            var_dump($markers);
            echo "closing_pos == $closing_pos\n";
            echo "opening_pos == $opening_pos\n";
            // Check for mismatched brackets.
            if ( $eos && ($opening_type !== $closing_type) ) {
                // Case where one or more opening brackets don't have matching closing brackets.
                assert($opening_type !== self::BRACKET_TYPE_INVALID);
                assert($closing_type === self::BRACKET_TYPE_INVALID);
                $closing_pos  = $len-1;
                $closing_type = $opening_type;
            echo "eos: closing_pos == $closing_pos\n";
            echo "eos: opening_pos == $opening_pos\n";
            }
            else
            if ( !$eos && ($opening_type !== $closing_type) ) {
                // Case where two brackets at same level of nesting are different brackets.
                //
                // Slow-but-simple-yet-almost-comprehensive handling of this case:
                // This probably makes the algorithm O(n^2) because these two
                // scanning steps contribute an O(n) at potentially every bracket,
                // so a string full of unmatched brackets could create a lot of
                // token list scans. This is extremely unlikely to happen, though.
                // We just don't want to use this code in any security-sensitive
                // context where an attacker could DOS a system by feeding it
                // pathological inputs. (The current use-case is unittesting,
                // which does not suffer from such concerns!)
                self::count_brackets_left($token_list, $opening_pos, $bracket_counts);
                $otype_lcount = $bracket_counts[$opening_type];
                $ctype_lcount = $bracket_counts[$closing_type];

                self::count_brackets_right($token_list, $closing_pos, $bracket_counts);
                $otype_rcount = -$bracket_counts[$opening_type];
                $ctype_rcount = -$bracket_counts[$closing_type];

                $otype_balance = $otype_lcount - $otype_rcount;
                $ctype_balance = $ctype_lcount - $ctype_rcount;

                $otype_deviation = abs($otype_balance);
                $ctype_deviation = abs($ctype_balance);
                assert(is_int($otype_deviation));
                assert(is_int($ctype_deviation));

                // We want to stub out the bracket with the LEAST balanced
                // (highest deviation) set of brackets. Because that is the
                // bracket that is most likely to be missing its complement
                // anywhere else in the string.
                //
                // The more balanced one will get handled in a later iteration.
                if ($otype_deviation < $ctype_deviation) {
                    // Pretend that the closing bracket has a complete pair.
                    $opening_pos++;
                    $opening_type = $closing_type;
                } else {
                    // Pretend that the opening bracket has a complete pair.
                    $closing_pos--;
                    $closing_type = $opening_type;
                }
            echo "!eos: closing_pos == $closing_pos\n";
            echo "!eos: opening_pos == $opening_pos\n";
            }

            // Reset cursor to opening_pos
            $cursor = $opening_pos;

            // Acquire metadata for the parse node we're about to construct
            $node_type = self::bracket_type_to_node_type($opening_type);
            if ( $node_type !== NodeType::PHP_TAG ) {
                // Use the `bracket_type_to_token_id` function because
                // bracket mismatches might mean the original `$token_id`
                // isn't actually a bracket. (But only for non-tag things,
                // because `bracket_type_to_token_id` can't recreate the
                // the difference between T_OPEN_TAG and T_OPEN_TAG_WITH_ECHO.)
                $token_id = self::bracket_type_to_token_id($opening_type);
            }

            // +1 to make it inclusive, because the positions should represent
            // the tokens that are the bracket tokens.
            $n_tokens = ($closing_pos - $opening_pos) + 1;

            echo "closing_pos == $closing_pos\n";
            echo "opening_pos == $opening_pos\n";

            // Construct new node and replace the bracket-run with it.
            $child_tree = new Node($token_id, $node_type, []);
            $child_list = \array_splice($token_list, $cursor, $n_tokens, []);
            $token_list[$cursor] = $child_tree;
            $token_list[$cursor]->children = $child_list;

            // Ensure that the for-loop has the correct `$len` so it doesn't
            // read beyond the end of the (modified) `$token_list`.
            // `$cursor` itself will be incremented by the for-statement.
            $len = count($token_list);
        } // for(; $cursor < $len; $cursor++)
    } // function nest_all_brackets_impl(...)

    public static function unittest_nest_all_brackets(TestRunnerCore $runner) : void
    {
        $runner->note("Running `\Kickback\UnitTesting\TestRunnerCore::unittest_nest_all_brackets(...)`\n");
        /*
        eval($runner->tests(
        <<<PHP
        $tree = self::from_expr("");
        $child_list_before = $tree->root->children;
        $tree->nest_all_brackets();
        $child_list_after = $tree->root->children;
        %assert([] === $child_list_after);
        %assert($child_list_before === $child_list_after);
        PHP
        ));
        */

        $tree = self::from_expr("");
        $child_list_before = $tree->root->children;
        $tree->nest_all_brackets();
        $child_list_after = $tree->root->children;
        eval($runner->assert_eq('[]', '$child_list_after'));
        eval($runner->assert_eq('$child_list_before', '$child_list_after'));

        $tree = self::from_expr("return");
        $child_list_before = $tree->root->children;
        $tree->nest_all_brackets();
        $child_list_after = $tree->root->children;
        eval($runner->assert_eq('1', 'count($child_list_after)'));
        eval($runner->assert_true('is_array($child_list_after[0])'));
        eval($runner->assert_eq('3', 'count($child_list_after[0])'));
        eval($runner->assert_eq('T_RETURN', '$child_list_after[0][0]'));
        eval($runner->assert_eq('$child_list_before', '$child_list_after'));

        $tree = self::from_expr("{ }");
        $child_list_before = $tree->root->children;
        $tree->nest_all_brackets();
        $child_list_after = $tree->root->children;

        eval($runner->assert_eq('2', 'count($child_list_before)'));
        eval($runner->assert_true('!is_array($child_list_before[0])'));
        eval($runner->assert_true('!is_array($child_list_before[1])'));
        eval($runner->assert_eq("'{'", '$child_list_before[0]'));
        eval($runner->assert_eq("'}'", '$child_list_before[1]'));

        eval($runner->assert_eq('1', 'count($child_list_after)'));
        $node = $child_list_after[0];
        eval($runner->assert_true('$node instanceof Node'));
        eval($runner->assert_eq('$node->token_id', 'T_BAD_CHARACTER'));
        eval($runner->assert_eq('$node->node_type', 'NodeType::BRACKET_CURLY'));
        eval($runner->assert_eq('$node->children', '[]'));


        $tree = self::from_expr("{ return }");
        $child_list_before = $tree->root->children;
        $tree->nest_all_brackets();
        $child_list_after = $tree->root->children;

        eval($runner->assert_eq('3', 'count($child_list_before)'));
        eval($runner->assert_true('!is_array($child_list_before[0])'));
        eval($runner->assert_true(' is_array($child_list_before[1])'));
        eval($runner->assert_true('!is_array($child_list_before[2])'));
        eval($runner->assert_eq("'{'", '$child_list_before[0]'));
        eval($runner->assert_eq('  3', 'count($child_list_before[1])'));
        eval($runner->assert_eq('T_RETURN', '$child_list_before[1][0]'));
        eval($runner->assert_eq("'}'", '$child_list_before[2]'));

        eval($runner->assert_eq('1', 'count($child_list_after)'));
        $node = $child_list_after[0];
        eval($runner->assert_true('$node instanceof Node'));
        eval($runner->assert_eq('$node->token_id', 'T_BAD_CHARACTER'));
        eval($runner->assert_eq('$node->node_type', 'NodeType::BRACKET_CURLY'));
        eval($runner->assert_eq('3', 'count($node->children)'));
        eval($runner->assert_eq("'{'", '$node->children[0]'));
        eval($runner->assert_true('is_array($node->children[1])'));
        eval($runner->assert_eq('3', 'count($node->children[1])'));
        eval($runner->assert_eq('T_RETURN', '$node->children[1][0]'));
        eval($runner->assert_eq("'}'", '$node->children[2]'));


        $tree = self::from_expr("()()");
        $child_list_before = $tree->root->children;
        $tree->nest_all_brackets();
        $child_list_after = $tree->root->children;

        eval($runner->assert_eq('4', 'count($child_list_before)'));
        eval($runner->assert_true('!is_array($child_list_before[0])'));
        eval($runner->assert_true('!is_array($child_list_before[1])'));
        eval($runner->assert_true('!is_array($child_list_before[2])'));
        eval($runner->assert_true('!is_array($child_list_before[3])'));
        eval($runner->assert_eq("'('", '$child_list_before[0]'));
        eval($runner->assert_eq("')'", '$child_list_before[1]'));
        eval($runner->assert_eq("'('", '$child_list_before[2]'));
        eval($runner->assert_eq("')'", '$child_list_before[3]'));

        eval($runner->assert_eq('2', 'count($child_list_after)'));
        $node = $child_list_after[0];
        eval($runner->assert_true('$node instanceof Node'));
        eval($runner->assert_eq('$node->token_id', 'T_BAD_CHARACTER'));
        eval($runner->assert_eq('$node->node_type', 'NodeType::BRACKET_PAREN'));
        eval($runner->assert_eq('$node->children', '[]'));
        $node = $child_list_after[1];
        eval($runner->assert_true('$node instanceof Node'));
        eval($runner->assert_eq('$node->token_id', 'T_BAD_CHARACTER'));
        eval($runner->assert_eq('$node->node_type', 'NodeType::BRACKET_PAREN'));
        eval($runner->assert_eq('$node->children', '[]'));


        $tree = self::from_expr("(){}");
        $child_list_before = $tree->root->children;
        $tree->nest_all_brackets();
        $child_list_after = $tree->root->children;

        eval($runner->assert_eq('4', 'count($child_list_before)'));
        eval($runner->assert_true('!is_array($child_list_before[0])'));
        eval($runner->assert_true('!is_array($child_list_before[1])'));
        eval($runner->assert_true('!is_array($child_list_before[2])'));
        eval($runner->assert_true('!is_array($child_list_before[3])'));
        eval($runner->assert_eq("'('", '$child_list_before[0]'));
        eval($runner->assert_eq("')'", '$child_list_before[1]'));
        eval($runner->assert_eq("'{'", '$child_list_before[2]'));
        eval($runner->assert_eq("'}'", '$child_list_before[3]'));

        eval($runner->assert_eq('2', 'count($child_list_after)'));
        $node = $child_list_after[0];
        eval($runner->assert_true('$node instanceof Node'));
        eval($runner->assert_eq('$node->node_type', 'NodeType::BRACKET_PAREN'));
        eval($runner->assert_eq('$node->children', '[]'));
        $node = $child_list_after[1];
        eval($runner->assert_true('$node instanceof Node'));
        eval($runner->assert_eq('$node->node_type', 'NodeType::BRACKET_CURLY'));
        eval($runner->assert_eq('$node->children', '[]'));


        $tree = self::from_expr("(())");
        $child_list_before = $tree->root->children;
        $tree->nest_all_brackets();
        $child_list_after = $tree->root->children;

        eval($runner->assert_eq('4', 'count($child_list_before)'));
        eval($runner->assert_true('!is_array($child_list_before[0])'));
        eval($runner->assert_true('!is_array($child_list_before[1])'));
        eval($runner->assert_true('!is_array($child_list_before[2])'));
        eval($runner->assert_true('!is_array($child_list_before[3])'));
        eval($runner->assert_eq("'('", '$child_list_before[0]'));
        eval($runner->assert_eq("'('", '$child_list_before[1]'));
        eval($runner->assert_eq("')'", '$child_list_before[2]'));
        eval($runner->assert_eq("')'", '$child_list_before[3]'));

        eval($runner->assert_eq('1', 'count($child_list_after)'));
        $node = $child_list_after[0];
        eval($runner->assert_true('$node instanceof Node'));
        eval($runner->assert_eq('$node->node_type', 'NodeType::BRACKET_PAREN'));
        eval($runner->assert_eq('3', 'count($node->children)'));
        eval($runner->assert_true('!is_array($node->children[0])'));
        eval($runner->assert_true('!is_array($node->children[2])'));
        eval($runner->assert_eq("'('", '$node->children[0]'));
        eval($runner->assert_eq("')'", '$node->children[2]'));
        $node = $node->children[1];
        eval($runner->assert_true('$node instanceof Node'));
        eval($runner->assert_eq('$node->node_type', 'NodeType::BRACKET_PAREN'));
        eval($runner->assert_eq('2', 'count($node->children)'));
        eval($runner->assert_true('!is_array($node->children[0])'));
        eval($runner->assert_true('!is_array($node->children[1])'));
        eval($runner->assert_eq("'('", '$node->children[0]'));
        eval($runner->assert_eq("')'", '$node->children[1]'));


        $tree = self::from_expr("({})");
        $child_list_before = $tree->root->children;
        $tree->nest_all_brackets();
        $child_list_after = $tree->root->children;

        eval($runner->assert_eq('4', 'count($child_list_before)'));
        eval($runner->assert_true('!is_array($child_list_before[0])'));
        eval($runner->assert_true('!is_array($child_list_before[1])'));
        eval($runner->assert_true('!is_array($child_list_before[2])'));
        eval($runner->assert_true('!is_array($child_list_before[3])'));
        eval($runner->assert_eq("'('", '$child_list_before[0]'));
        eval($runner->assert_eq("'{'", '$child_list_before[1]'));
        eval($runner->assert_eq("'}'", '$child_list_before[2]'));
        eval($runner->assert_eq("')'", '$child_list_before[3]'));

        eval($runner->assert_eq('1', 'count($child_list_after)'));
        $node = $child_list_after[0];
        eval($runner->assert_true('$node instanceof Node'));
        eval($runner->assert_eq('$node->node_type', 'NodeType::BRACKET_PAREN'));
        eval($runner->assert_eq('3', 'count($node->children)'));
        eval($runner->assert_true('!is_array($node->children[0])'));
        eval($runner->assert_true('!is_array($node->children[2])'));
        eval($runner->assert_eq("'('", '$node->children[0]'));
        eval($runner->assert_eq("')'", '$node->children[2]'));
        $node = $node->children[1];
        eval($runner->assert_true('$node instanceof Node'));
        eval($runner->assert_eq('$node->node_type', 'NodeType::BRACKET_CURLY'));
        eval($runner->assert_eq('2', 'count($node->children)'));
        eval($runner->assert_true('!is_array($node->children[0])'));
        eval($runner->assert_true('!is_array($node->children[1])'));
        eval($runner->assert_eq("'{'", '$node->children[0]'));
        eval($runner->assert_eq("'}'", '$node->children[1]'));


        $tree = self::from_expr(")()");
        $child_list_before = $tree->root->children;
        $tree->nest_all_brackets();
        $child_list_after = $tree->root->children;

        eval($runner->assert_eq('3', 'count($child_list_before)'));
        eval($runner->assert_true('!is_array($child_list_before[0])'));
        eval($runner->assert_true('!is_array($child_list_before[1])'));
        eval($runner->assert_true('!is_array($child_list_before[2])'));
        eval($runner->assert_eq("')'", '$child_list_before[0]'));
        eval($runner->assert_eq("'('", '$child_list_before[1]'));
        eval($runner->assert_eq("')'", '$child_list_before[2]'));

        eval($runner->assert_eq('2', 'count($child_list_after)'));
        eval($runner->assert_true('!is_array($child_list_after[0])'));
        eval($runner->assert_true('!is_array($child_list_after[1])'));
        $node = $child_list_after[0];
        eval($runner->assert_true('$node instanceof Node'));
        eval($runner->assert_eq('$node->token_id', 'T_BAD_CHARACTER'));
        eval($runner->assert_eq('$node->node_type', 'NodeType::BRACKET_PAREN'));
        eval($runner->assert_eq('1', 'count($node->children)'));
        eval($runner->assert_true('!is_array($node->children[0])'));
        eval($runner->assert_eq("')'", '$node->children[0]'));
        $node = $child_list_after[1];
        eval($runner->assert_true('$node instanceof Node'));
        eval($runner->assert_eq('$node->token_id', 'T_BAD_CHARACTER'));
        eval($runner->assert_eq('$node->node_type', 'NodeType::BRACKET_PAREN'));
        eval($runner->assert_eq('2', 'count($node->children)'));
        eval($runner->assert_true('!is_array($node->children[0])'));
        eval($runner->assert_true('!is_array($node->children[1])'));
        eval($runner->assert_eq("'('", '$node->children[0]'));
        eval($runner->assert_eq("')'", '$node->children[1]'));


        $tree = self::from_expr("()(");
        $child_list_before = $tree->root->children;
        $tree->nest_all_brackets();
        $child_list_after = $tree->root->children;

        eval($runner->assert_eq('3', 'count($child_list_before)'));
        eval($runner->assert_true('!is_array($child_list_before[0])'));
        eval($runner->assert_true('!is_array($child_list_before[1])'));
        eval($runner->assert_true('!is_array($child_list_before[2])'));
        eval($runner->assert_eq("'('", '$child_list_before[0]'));
        eval($runner->assert_eq("')'", '$child_list_before[1]'));
        eval($runner->assert_eq("'('", '$child_list_before[2]'));

        eval($runner->assert_eq('2', 'count($child_list_after)'));
        eval($runner->assert_true('!is_array($child_list_after[0])'));
        eval($runner->assert_true('!is_array($child_list_after[1])'));
        $node = $child_list_after[0];
        eval($runner->assert_true('$node instanceof Node'));
        eval($runner->assert_eq('$node->token_id', 'T_BAD_CHARACTER'));
        eval($runner->assert_eq('$node->node_type', 'NodeType::BRACKET_PAREN'));
        eval($runner->assert_eq('2', 'count($node->children)'));
        eval($runner->assert_true('!is_array($node->children[0])'));
        eval($runner->assert_true('!is_array($node->children[1])'));
        eval($runner->assert_eq("'('", '$node->children[0]'));
        eval($runner->assert_eq("')'", '$node->children[1]'));
        $node = $child_list_after[1];
        eval($runner->assert_true('$node instanceof Node'));
        eval($runner->assert_eq('$node->token_id', 'T_BAD_CHARACTER'));
        eval($runner->assert_eq('$node->node_type', 'NodeType::BRACKET_PAREN'));
        eval($runner->assert_eq('1', 'count($node->children)'));
        eval($runner->assert_true('!is_array($node->children[0])'));
        eval($runner->assert_eq("'('", '$node->children[0]'));


        $tree = self::from_expr("())");
        $child_list_before = $tree->root->children;
        $tree->nest_all_brackets();
        $child_list_after = $tree->root->children;

        eval($runner->assert_eq('3', 'count($child_list_before)'));
        eval($runner->assert_true('!is_array($child_list_before[0])'));
        eval($runner->assert_true('!is_array($child_list_before[1])'));
        eval($runner->assert_true('!is_array($child_list_before[2])'));
        eval($runner->assert_eq("'('", '$child_list_before[0]'));
        eval($runner->assert_eq("')'", '$child_list_before[1]'));
        eval($runner->assert_eq("')'", '$child_list_before[2]'));

        eval($runner->assert_eq('1', 'count($child_list_after)'));
        $node = $child_list_after[0];
        eval($runner->assert_true('$node instanceof Node'));
        eval($runner->assert_eq('$node->node_type', 'NodeType::BRACKET_PAREN'));
        eval($runner->assert_eq('2', 'count($node->children)'));
        eval($runner->assert_true('!is_array($node->children[1])'));
        eval($runner->assert_eq("')'", '$node->children[1]'));
        $node = $node->children[1];
        eval($runner->assert_true('$node instanceof Node'));
        eval($runner->assert_eq('$node->node_type', 'NodeType::BRACKET_PAREN'));
        eval($runner->assert_eq('2', 'count($node->children)'));
        eval($runner->assert_true('!is_array($node->children[0])'));
        eval($runner->assert_true('!is_array($node->children[1])'));
        eval($runner->assert_eq("'('", '$node->children[0]'));
        eval($runner->assert_eq("')'", '$node->children[1]'));


        $tree = self::from_expr("(()");
        $child_list_before = $tree->root->children;
        $tree->nest_all_brackets();
        $child_list_after = $tree->root->children;

        eval($runner->assert_eq('3', 'count($child_list_before)'));
        eval($runner->assert_true('!is_array($child_list_before[0])'));
        eval($runner->assert_true('!is_array($child_list_before[1])'));
        eval($runner->assert_true('!is_array($child_list_before[2])'));
        eval($runner->assert_eq("'('", '$child_list_before[0]'));
        eval($runner->assert_eq("'('", '$child_list_before[1]'));
        eval($runner->assert_eq("')'", '$child_list_before[2]'));

        eval($runner->assert_eq('1', 'count($child_list_after)'));
        $node = $child_list_after[0];
        eval($runner->assert_true('$node instanceof Node'));
        eval($runner->assert_eq('$node->node_type', 'NodeType::BRACKET_PAREN'));
        eval($runner->assert_eq('2', 'count($node->children)'));
        eval($runner->assert_true('!is_array($node->children[0])'));
        eval($runner->assert_eq("'('", '$node->children[0]'));
        $node = $node->children[1];
        eval($runner->assert_true('$node instanceof Node'));
        eval($runner->assert_eq('$node->node_type', 'NodeType::BRACKET_PAREN'));
        eval($runner->assert_eq('2', 'count($node->children)'));
        eval($runner->assert_true('!is_array($node->children[0])'));
        eval($runner->assert_true('!is_array($node->children[1])'));
        eval($runner->assert_eq("'('", '$node->children[0]'));
        eval($runner->assert_eq("')'", '$node->children[1]'));


        $tree = self::from_expr("(})");
        $child_list_before = $tree->root->children;
        $tree->nest_all_brackets();
        $child_list_after = $tree->root->children;

        eval($runner->assert_eq('3', 'count($child_list_before)'));
        eval($runner->assert_true('!is_array($child_list_before[0])'));
        eval($runner->assert_true('!is_array($child_list_before[1])'));
        eval($runner->assert_true('!is_array($child_list_before[2])'));
        eval($runner->assert_eq("'('", '$child_list_before[0]'));
        eval($runner->assert_eq("'}'", '$child_list_before[1]'));
        eval($runner->assert_eq("')'", '$child_list_before[2]'));

        eval($runner->assert_eq('1', 'count($child_list_after)'));
        $node = $child_list_after[0];
        eval($runner->assert_true('$node instanceof Node'));
        eval($runner->assert_eq('$node->node_type', 'NodeType::BRACKET_PAREN'));
        eval($runner->assert_eq('3', 'count($node->children)'));
        eval($runner->assert_true('!is_array($node->children[0])'));
        eval($runner->assert_true('!is_array($node->children[2])'));
        eval($runner->assert_eq("'('", '$node->children[0]'));
        eval($runner->assert_eq("')'", '$node->children[2]'));
        $node = $node->children[1];
        eval($runner->assert_true('$node instanceof Node'));
        eval($runner->assert_eq('$node->node_type', 'NodeType::BRACKET_CURLY'));
        eval($runner->assert_eq('1', 'count($node->children)'));
        eval($runner->assert_true('!is_array($node->children[0])'));
        eval($runner->assert_eq("'}'", '$node->children[0]'));


        $tree = self::from_expr("({)");
        $child_list_before = $tree->root->children;
        $tree->nest_all_brackets();
        $child_list_after = $tree->root->children;

        eval($runner->assert_eq('3', 'count($child_list_before)'));
        eval($runner->assert_true('!is_array($child_list_before[0])'));
        eval($runner->assert_true('!is_array($child_list_before[1])'));
        eval($runner->assert_true('!is_array($child_list_before[2])'));
        eval($runner->assert_eq("'('", '$child_list_before[0]'));
        eval($runner->assert_eq("'{'", '$child_list_before[1]'));
        eval($runner->assert_eq("')'", '$child_list_before[2]'));

        eval($runner->assert_eq('1', 'count($child_list_after)'));
        $node = $child_list_after[0];
        eval($runner->assert_true('$node instanceof Node'));
        eval($runner->assert_eq('$node->node_type', 'NodeType::BRACKET_PAREN'));
        eval($runner->assert_eq('3', 'count($node->children)'));
        eval($runner->assert_true('!is_array($node->children[0])'));
        eval($runner->assert_true('!is_array($node->children[2])'));
        eval($runner->assert_eq("'('", '$node->children[0]'));
        eval($runner->assert_eq("')'", '$node->children[2]'));
        $node = $node->children[1];
        eval($runner->assert_true('$node instanceof Node'));
        eval($runner->assert_eq('$node->node_type', 'NodeType::BRACKET_CURLY'));
        eval($runner->assert_eq('1', 'count($node->children)'));
        eval($runner->assert_true('!is_array($node->children[0])'));
        eval($runner->assert_eq("'{'", '$node->children[0]'));

        $tree = self::from_expr("())()");
        $child_list_before = $tree->root->children;
        $tree->nest_all_brackets();
        $child_list_after = $tree->root->children;

        eval($runner->assert_eq('5', 'count($child_list_before)'));
        eval($runner->assert_true('!is_array($child_list_before[0])'));
        eval($runner->assert_true('!is_array($child_list_before[1])'));
        eval($runner->assert_true('!is_array($child_list_before[2])'));
        eval($runner->assert_true('!is_array($child_list_before[3])'));
        eval($runner->assert_true('!is_array($child_list_before[4])'));
        eval($runner->assert_eq("'('", '$child_list_before[0]'));
        eval($runner->assert_eq("')'", '$child_list_before[1]'));
        eval($runner->assert_eq("')'", '$child_list_before[2]'));
        eval($runner->assert_eq("'('", '$child_list_before[3]'));
        eval($runner->assert_eq("')'", '$child_list_before[4]'));

        eval($runner->assert_eq('2', 'count($child_list_after)'));
        eval($runner->assert_true('!is_array($child_list_after[0])'));
        eval($runner->assert_true('!is_array($child_list_after[1])'));
        $node_left = $child_list_after[0];
        $node_right = $child_list_after[1];
        eval($runner->assert_true('$node_left  instanceof Node'));
        eval($runner->assert_eq('$node_left->token_id',  'T_BAD_CHARACTER'));
        eval($runner->assert_eq('$node_left->node_type', 'NodeType::BRACKET_PAREN'));
        eval($runner->assert_eq('2', 'count($node_left->children)'));
        eval($runner->assert_true('!is_array($node_left->children[0])'));
        eval($runner->assert_true('!is_array($node_left->children[1])'));
        eval($runner->assert_eq("')'", '$node_left->children[1]'));
        $node_left = $node_left[0];
        eval($runner->assert_true('$node_left  instanceof Node'));
        eval($runner->assert_eq('$node_left->token_id',  'T_BAD_CHARACTER'));
        eval($runner->assert_eq('$node_left->node_type', 'NodeType::BRACKET_PAREN'));
        eval($runner->assert_eq('2', 'count($node_left->children)'));
        eval($runner->assert_true('!is_array($node_left->children[0])'));
        eval($runner->assert_true('!is_array($node_left->children[1])'));
        eval($runner->assert_eq("'('", '$node_left->children[0]'));
        eval($runner->assert_eq("')'", '$node_left->children[1]'));
        eval($runner->assert_true('$node_right instanceof Node'));
        eval($runner->assert_eq('$node_right->token_id', 'T_BAD_CHARACTER'));
        eval($runner->assert_eq('$node_right->node_type', 'NodeType::BRACKET_PAREN'));
        eval($runner->assert_eq('2', 'count($node_right->children)'));
        eval($runner->assert_true('!is_array($node_right->children[0])'));
        eval($runner->assert_true('!is_array($node_right->children[1])'));
        eval($runner->assert_eq("'('", '$node_right->children[0]'));
        eval($runner->assert_eq("')'", '$node_right->children[1]'));


        $tree = self::from_expr("()(()");
        $child_list_before = $tree->root->children;
        $tree->nest_all_brackets();
        $child_list_after = $tree->root->children;

        eval($runner->assert_eq('5', 'count($child_list_before)'));
        eval($runner->assert_true('!is_array($child_list_before[0])'));
        eval($runner->assert_true('!is_array($child_list_before[1])'));
        eval($runner->assert_true('!is_array($child_list_before[2])'));
        eval($runner->assert_true('!is_array($child_list_before[3])'));
        eval($runner->assert_true('!is_array($child_list_before[4])'));
        eval($runner->assert_eq("'('", '$child_list_before[0]'));
        eval($runner->assert_eq("')'", '$child_list_before[1]'));
        eval($runner->assert_eq("'('", '$child_list_before[2]'));
        eval($runner->assert_eq("'('", '$child_list_before[3]'));
        eval($runner->assert_eq("')'", '$child_list_before[4]'));

        eval($runner->assert_eq('2', 'count($child_list_after)'));
        eval($runner->assert_true('!is_array($child_list_after[0])'));
        eval($runner->assert_true('!is_array($child_list_after[1])'));
        $node_left = $child_list_after[0];
        eval($runner->assert_true('$node_left  instanceof Node'));
        eval($runner->assert_eq('$node_left->token_id',  'T_BAD_CHARACTER'));
        eval($runner->assert_eq('$node_left->node_type', 'NodeType::BRACKET_PAREN'));
        eval($runner->assert_eq('2', 'count($node_left->children)'));
        eval($runner->assert_true('!is_array($node_left->children[0])'));
        eval($runner->assert_true('!is_array($node_left->children[1])'));
        eval($runner->assert_eq("'('", '$node_left->children[0]'));
        eval($runner->assert_eq("')'", '$node_left->children[1]'));
        $node_right = $child_list_after[1];
        eval($runner->assert_true('$node_right  instanceof Node'));
        eval($runner->assert_eq('$node_right->token_id',  'T_BAD_CHARACTER'));
        eval($runner->assert_eq('$node_right->node_type', 'NodeType::BRACKET_PAREN'));
        eval($runner->assert_eq('2', 'count($node_right->children)'));
        eval($runner->assert_true('!is_array($node_right->children[0])'));
        eval($runner->assert_true('!is_array($node_right->children[1])'));
        eval($runner->assert_eq("'('", '$node_right->children[0]'));
        $node_right = $node_right->children[1];
        eval($runner->assert_true('$node_right instanceof Node'));
        eval($runner->assert_eq('$node_right->token_id', 'T_BAD_CHARACTER'));
        eval($runner->assert_eq('$node_right->node_type', 'NodeType::BRACKET_PAREN'));
        eval($runner->assert_eq('2', 'count($node_right->children)'));
        eval($runner->assert_true('!is_array($node_right->children[0])'));
        eval($runner->assert_true('!is_array($node_right->children[1])'));
        eval($runner->assert_eq("'('", '$node_right->children[0]'));
        eval($runner->assert_eq("')'", '$node_right->children[1]'));

        $runner->note("  ... passed.\n\n");
    }
}
?>
