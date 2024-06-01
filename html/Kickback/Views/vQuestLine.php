<?php
declare(strict_types=1);

namespace Kickback\Views;

use Kickback\Views\vAccount;
use Kickback\Views\vDateTime;

class vQuestLine extends vRecordId
{
    public string $title;
    public string $locator;
    public string $summary;
    public vDateTime $dateCreated;
    public vAccount $host1;
    public bool $published;
    
    public ?vMedia $icon;
    public ?vMedia $banner;
    public ?vMedia $bannerMobile;

    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }
    
}



?>