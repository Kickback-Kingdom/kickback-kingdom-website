<?php
declare(strict_types=1);

namespace Kickback\Backend\Models;

class Game extends RecordId
{
    public string $name;
    public string $desc;
    public int $minRankedMatches;
    public string $shortName;
    public bool $canRank;
    public ForeignRecordId $mediaIconId;
    public ForeignRecordId $mediaBannerId;
    public ForeignRecordId $mediaBannerMobileId;
}

?>