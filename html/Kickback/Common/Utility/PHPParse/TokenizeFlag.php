<?php
declare(strict_types=1);

namespace Kickback\Common\Utility\PHPParse;

/**
* @see \Kickback\Common\Utility\PHPParse\Tree::tokenize_php
*/
enum TokenizeFlag : int
{
	case NONE            = 0x00;

    /**
    * Corresponds to, but is NOT (numerically) equal to, the `TOKEN_PARSE` flag for `token_get_all`.
    *
    * Further clarification: Do not pass this to the `token_get_all` function.
    * This flag exists to tell `\Kickback\Utility\PHPParse\Tree::tokenize_php`
    * that it should call `token_get_all` with the proper `TOKEN_PARSE` flag.
    *
    * So, pass this to the `\Kickback\Utility\PHPParse\Tree::tokenize_php` function
    * if you want that function to pass `TOKEN_PARSE` to `token_get_all`.
    */
	case TOKEN_PARSE     = 0x01;

    /*
    * Tells `\Kickback\Utility\PHPParse\Tree::tokenize_php` to ALWAYS remove
    * the opening and closing PHP tags from the resulting token list.
    *
    * This should be considered as exclusive to the PHP_TAG_KEEP flag,
    * but should they be used together, note that both of these expressions
    * will result in the default behavior (PHP_TAG_PRESERVE) of removing
    * the tag(s) conditionally based on whether the string originally possessed them:
    * * (TokenizeFlag::PHP_TAG_REMOVE | TokenizeFlag::PHP_TAG_KEEP)
    * * (TokenizeFlag::PHP_TAG_REMOVE & TokenizeFlag::PHP_TAG_KEEP)
    */
	case PHP_TAG_REMOVE = 0x02;

    /*
    * Tells `\Kickback\Utility\PHPParse\Tree::tokenize_php` to ALWAYS remove
    * the opening and closing PHP tags from the resulting token list.
    *
    * This should be considered as exclusive to the PHP_TAG_REMOVE flag,
    * but should they be used together, note that both of these expressions
    * will result in the default behavior (PHP_TAG_PRESERVE) of removing
    * the tag(s) conditionally based on whether the string originally possessed them:
    * * (TokenizeFlag::PHP_TAG_REMOVE | TokenizeFlag::PHP_TAG_KEEP)
    * * (TokenizeFlag::PHP_TAG_REMOVE & TokenizeFlag::PHP_TAG_KEEP)
    */
	case PHP_TAG_KEEP = 0x04;

    /*
    * The default PHP tag removal/adding behavior of `\Kickback\Utility\PHPParse\Tree::tokenize_php`.
    *
    * This will ensure that the resulting token list has PHP tags on its
    * beginning or end if-and-only-if the input expression had PHP tags at
    * its beginning or end.
    */
	case PHP_TAG_PRESERVE = 0x02 | 0x04;
}
?>
