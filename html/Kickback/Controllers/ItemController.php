<?php
declare(strict_types=1);

namespace Kickback\Controllers;

use Kickback\Views\vItem;
use Kickback\Views\vMedia;
use Kickback\Views\vRecordId;
use Kickback\Models\Response;
use Kickback\Services\Database;
use Kickback\Views\vAccount;

class ItemController
{

    public static function row_to_vItem($row) : vItem
    {
        $item = new vItem();
        $item->name = $row["name"];
        $item->description = $row["desc"];

        if ($row["nominated_by_id"] != null)
        {
            $nominatedBy = new vAccount('', $row["nominated_by_id"]);
            $nominatedBy->username = $row["nominated_by"];
            $item->nominatedBy = $nominatedBy;
        }

        if ($row["BigImgPath"] != null)
        {
            $bigImg = new vMedia();
            $bigImg->setMediaPath($row["BigImgPath"]);
            $item->iconBig = $bigImg;
        }
        if ($row["SmallImgPath"] != null)
        {
            
            $smallImg = new vMedia();
            $smallImg->setMediaPath($row["SmallImgPath"]);
            $item->iconSmall = $smallImg;
        }


        return $item;
    }

}
?>
