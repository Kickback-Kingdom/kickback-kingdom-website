<?php
declare(strict_types=1);

namespace Kickback\Backend\Models;

class TreasureHuntObject extends RecordId
{
    public ForeignRecordId $eventId;
    public int $itemId;
    public string $pageUrl;
    public float $xPercentage;
    public float $yPercentage;


    function __construct(TreasureHuntEvent $event, int $itemId, string $pageUrl, float $xPercentage, float $yPercentage)
    {
        parent::__construct();
        $this->eventId = $event->getForeignRecordId();
        $this->itemId = $itemId;
        $this->pageUrl = $pageUrl;
        $this->xPercentage = $xPercentage;
        $this->yPercentage = $yPercentage;
    }
}
?>
