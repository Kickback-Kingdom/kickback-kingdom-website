<?php
declare(strict_types=1);

namespace Kickback\Utility\PHPParse;

/**
* @see \Kickback\Utility\PHPParse\Tree
* @see \Kickback\Utility\PHPParse\Node
*/
enum NodeType : int
{
	case INVALID         = 0;
    case ROOT            = 1;
    case PHP_TAG         = 2;
    case BRACKET_CURLY   = 3;
    case BRACKET_PAREN   = 4;
    case BRACKET_SQUARE  = 5;
    case NUM_NODE_TYPES  = 6;
}
?>
