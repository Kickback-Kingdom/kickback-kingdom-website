<?php
declare(strict_types=1);

namespace Kickback\Views;

use Kickback\Views\vAccount;
use Kickback\Views\vQuest;
use Kickback\Views\vDateTime;

class vQuestReview extends vRecordId
{
    public int $questRating;
    public int $hostRating;
    public string $message;
    public vAccount $fromAccount;
    public vDateTime $dateTime;
    
    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }
}



?>