<?php
declare(strict_types=1);

namespace Kickback\Common\Database;

use Kickback\Common\Database\RowFromArray;
use Kickback\Common\Database\RowInterface;

final class Row
{
    use \Kickback\Common\Traits\StaticClassTrait;

    /**
    * @param array<string,?mixed> $row
    */
    public static function from_array(array $row) : RowInterface
    {
        return new RowFromArray($row);
    }
}

?>
