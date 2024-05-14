<?php
declare(strict_types=1);

namespace Kickback\Models;

class Game extends RecordId
{
    public string $name;
    public string $desc;
    public int $minRankedMatches;
    public string $shortName;
    public bool $canRank;
    public RecordId $mediaIconId;
    public RecordId $mediaBannerId;
    public RecordId $mediaBannerMobileId;
}



?>