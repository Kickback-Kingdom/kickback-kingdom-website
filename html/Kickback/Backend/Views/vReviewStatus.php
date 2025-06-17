<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

class vReviewStatus
{
    public bool $published;
    public bool $beingReviewed;
    public bool $closed;


    function __construct(bool $published = false, bool $beingReviewed = false, bool $closed = false)
    {
        $this->published = $published;
        $this->beingReviewed = $beingReviewed;
        $this->closed = $closed;
    }

    public function isDraft(): bool
    {
        return !$this->published && !$this->beingReviewed;
    }

    public function isPublished(): bool
    {
        return $this->published;
    }

    public function isBeingReviewed(): bool
    {
        return $this->beingReviewed;
    }

    public function isPublishedOrInReview(): bool
    {
        return $this->published || $this->beingReviewed;
    }

    public function isClosed(): bool {
        return $this->closed;
    }
}



?>