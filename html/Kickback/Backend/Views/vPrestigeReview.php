<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vQuest;
use Kickback\Backend\Views\vDateTime;

class vPrestigeReview extends vRecordId
{
    public bool $commend;
    public string $message;
    public vAccount $fromAccount;
    public ?vQuest $fromQuest = null;
    public vDateTime $dateTime;
    
    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }
}



?>