<?php
declare(strict_types=1);

namespace Kickback\Utility\PHPParse;

use \Kickback\Utility\PHPParse\NodeType;

/** @see \Kickback\Utility\PHPParse\Tree */
final class Node
{
    public function __construct(
        public int      $token_id, // Or T_BAD_CHARACTER if N/A.
        public NodeType $node_type,

        /** @var (PHPToken|PHPParseTree)[] */
        public array    $children,
    ) {}
}
?>
