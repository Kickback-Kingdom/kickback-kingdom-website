<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vQuest;
use Kickback\Backend\Views\vDateTime;

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