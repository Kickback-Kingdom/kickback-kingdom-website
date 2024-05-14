<?php
declare(strict_types=1);

namespace Kickback\Views;

use Kickback\Views\vMedia;

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

    function __construct(int $crand, string $ctime = '')
    {
        parent::__construct($crand, $ctime);
    }

    public function GetURL()
    {
        return "/g/".$this->name;
    }
}



?>