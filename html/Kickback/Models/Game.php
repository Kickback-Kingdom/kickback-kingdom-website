<?php
declare(strict_types=1);

namespace Kickback\Models;

class Game
{
    public int   $id;
    public string $name;
    public string $desc;
    public int $minRankedMatches;
    public string $shortName;
    public bool $canRank;
    public int $mediaIconId;
    public int $mediaBannerId;
    public int $mediaBannerMobileId;

    
}



?>