<?php
declare(strict_types=1);

namespace Kickback\Common\Database;

use Kickback\Common\Database\RowFromArray;
use Kickback\Common\Database\RowInterface;

final class Row
{
    /**
    * @param array<string,?mixed> $row
    */
    public static function from_array(array $row) : RowInterface
    {
        return new RowFromArray($row);
    }

    // Prevent instantiation/construction of the (static/constant) class.
    /** @return never */
    private function __construct() {
        throw new \Exception("Instantiation of static class " . get_class($this) . "is not allowed.");
    }
}

?>
