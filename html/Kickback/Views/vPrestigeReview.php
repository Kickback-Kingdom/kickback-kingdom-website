<?php
declare(strict_types=1);

namespace Kickback\Views;

use Kickback\Views\vAccount;

class vPrestigeReview extends vRecordId
{
    public vAccount $fromAccount;
    public ?vQuest $fromQuest;
    
    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }
}



?>