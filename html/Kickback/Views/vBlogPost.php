<?php
declare(strict_types=1);

namespace Kickback\Views;

use Kickback\Views\vAccount;
use Kickback\Views\vMedia;
use Kickback\Views\vRecordId;
use Kickback\Views\vDateTime;

class vBlogPost extends vRecordId
{
    public string $title;
    public string $locator;
    public string $summary;
    public vDateTime $publishedDateTime;
    public vAccount $author;
    
    public ?vMedia $icon = null;

    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }
}



?>