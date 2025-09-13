<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

class vQuestReviewSummary
{
    public int $questId;
    public string $questTitle;
    public string $questDate;
    public string $questIcon;
    public float $avgHostRating;
    public float $avgQuestRating;
    public bool $hasComments;
}

?>
