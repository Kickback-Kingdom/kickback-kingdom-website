<?php
declare(strict_types=1);

namespace Kickback\Common\Utility\PHPParse;

use Kickback\Common\Utility\PHPParse\NodeType;

/** @see \Kickback\Common\Utility\PHPParse\Tree */
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
