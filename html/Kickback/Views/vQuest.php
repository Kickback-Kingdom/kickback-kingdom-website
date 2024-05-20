<?php
declare(strict_types=1);

namespace Kickback\Views;

use Kickback\Views\vAccount;

class vQuest extends vRecordId
{
    public string $title;
    public string $locator;
    public string $summary;

    public vAccount $host1;
    public ?vAccount $host2 = null;
    
    public ?vMedia $icon;

    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }
}



?>