<?php
declare(strict_types=1);

namespace Kickback\Views;

use Kickback\Views\vMedia;

class vGame
{
    public int   $id;
    public string $name;
    public string $description;
    public int $minRankedMatches;
    public string $shortName;
    public bool $canRank;
    
    public vMedia $icon;
    public vMedia $banner;
    public vMedia $bannerMobile;

    public function GetURL()
    {
        return "/g/".$this->name;
    }
}



?>