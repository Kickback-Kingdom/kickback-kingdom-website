<?php
declare(strict_types=1);

namespace Kickback\Controllers;

use Kickback\Views\vItem;
use Kickback\Views\vMedia;
use Kickback\Views\vRecordId;
use Kickback\Models\Response;
use Kickback\Services\Database;

class ItemController
{

    private static function row_to_vItem($row) : vItem
    {
        $badge = new vItem();

        return $badge;
    }

}
?>
