<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

class vQuestReviewSummary
{
    public int $questId;
    public string $questTitle;
    public string $questEndDate;
    public string $questIcon;
    public string $questBanner;
    public float $avgHostRating;
    public float $avgQuestRating;
    public bool $hasComments;
}

?>
