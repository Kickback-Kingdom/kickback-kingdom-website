<?php
declare(strict_types=1);

namespace Kickback\Backend\Models;

use Kickback\Backend\Models\ForeignRecordId;
use Kickback\Backend\Views\vReviewStatus;

class LichCard extends RecordId
{
    public string $name;
    public int $type;
    public int $rarity;
    public string $description;
    public float $nameFontSize;
    public float $typeFontSize;
    public float $descriptionFontSize;
    public int $health;
    public int $intelligence;
    public int $defense;
    public int $arcanic;
    public int $abyssal;
    public int $thermic;
    public int $verdant;
    public int $luminate;
    /** @var array<array{crand: int,  ctime: string,  name: string}> */
    public array $subTypes; 
    public string $locator;
    
    public vReviewStatus $reviewStatus;
    public ForeignRecordId $set;
    public ForeignRecordId $art;
    public ForeignRecordId $cardImage;
    public ForeignRecordId $item;

}
