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
    public string $blogLocator;
    public string $postLocator;
    public string $summary;
    public vDateTime $publishedDateTime;
    public vAccount $author;
    public bool $published;
    
    public ?vMedia $icon = null;

    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }

    public function setLocator(string $locator) {
        $parts = explode('/', $locator);
        if (count($parts) >= 2) {
            $this->blogLocator = $parts[0];
            $this->postLocator = $parts[1];
        } else {
            throw new \Exception("Invalid locator format. Expected format: 'blogLocator/postLocator'");
        }
    }

    public function getURL() : string {
        return '/blog/'.$this->blogLocator.'/'.$this->postLocator;
    }
}



?>