<?php
declare(strict_types=1);

namespace Kickback\Views;

use Kickback\Views\vMedia;
use Kickback\Views\vItem;

class vBlog extends vRecordId
{
    public string $title;
    public string $locator;
    public string $description;
    public ?vItem $managerItem = null;
    public ?vItem $writerItem = null;
    public ?vAccount $lastWriter = null;
    public ?vDateTime $lastPostDate = null;
    
    public vMedia $icon;

    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }

    public function getURL() : string {
        return '/blog/'.$this->locator;
    }
}



?>