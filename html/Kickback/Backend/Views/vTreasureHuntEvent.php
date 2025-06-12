<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;
use Kickback\Services\Session;

class vTreasureHuntEvent extends vRecordId
{
    public string $name;
    public string $desc;
    public vDateTime $startDate;
    public vDateTime $endDate;
    public string $locator;

    public vContent $content;

    public ?vMedia $icon;
    public ?vMedia $banner;
    public ?vMedia $bannerMobile;

    public ?vMedia $bannerDate;
    public ?vMedia $bannerProgress;

    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }

    public function hasPageContent() : bool {
        return $this->content->hasPageContent();
    }

    public function pageContent() : vPageContent {
        return $this->content->pageContent();
    }

    public function canEdit() : bool {
        return Session::isServantOfTheLich();
    }

    public function populateContent() : void {
        if ($this->hasPageContent())
        {
            $this->content->populateContent("TREASURE-HUNT",$this->locator);
        }
    }

    
    public function populateEverything() : void {
        $this->populateContent();
    }

    public function getURL() : string {
        return "/treasure-hunt/".$this->locator;
    }
}
?>
