<?php
declare(strict_types=1);

namespace Kickback\Views;

use Kickback\Views\vAccount;
use Kickback\Views\vDateTime;

class vQuest extends vRecordId
{
    public string $title;
    public string $locator;
    public string $summary;
    public vDateTime $endDate;
    public vAccount $host1;
    public ?vAccount $host2 = null;
    
    public ?vMedia $icon;

    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }

    

}



?>