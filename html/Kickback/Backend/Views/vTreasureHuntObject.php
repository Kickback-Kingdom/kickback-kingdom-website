<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

class vTreasureHuntObject extends vRecordId
{
    public string $pageUrl;
    public float $xPercentage;
    public float $yPercentage;
    public ?vMedia $media = null;
    public bool $found = false;
    public bool $foundByMe = false;
    public bool $oneTimeFind;
    public vItem $item;
    public string $locator;

    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }

    public function getURL() : string {
        return "/treasure-hunt/".$this->locator;
    }
}
?>
