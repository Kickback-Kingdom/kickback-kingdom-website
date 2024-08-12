<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

use Kickback\Backend\Views\vMedia;

class vGame extends vRecordId
{
    public string $name;
    public string $description;
    public int $minRankedMatches;
    public string $shortName;
    public bool $canRank;
    
    public vMedia $icon;
    public vMedia $banner;
    public vMedia $bannerMobile;

    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }

    public function GetURL()
    {
        return "/g/".$this->name;
    }
}



?>