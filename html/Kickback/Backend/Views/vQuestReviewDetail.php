<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

class vQuestReviewDetail
{
    public int $accountId;
    public string $username;
    public ?string $avatar;
    public ?int $hostRating;
    public ?int $questRating;
    public ?string $message;
}

?>
