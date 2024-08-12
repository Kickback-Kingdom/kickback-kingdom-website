<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vMedia;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vDateTime;
use Kickback\Backend\Views\vReviewStatus;
use Kickback\Services\Session;


class vBlogPost extends vRecordId
{
    public string $title;
    public string $blogLocator;
    public string $postLocator;
    public string $summary;
    public vDateTime $publishedDateTime;
    public vAccount $author;
    public vReviewStatus $reviewStatus;
    
    public vContent $content;
    public vMedia $icon;


    public vBlog $blog;

    public ?vBlogPost $prevBlogPost = null;
    public ?vBlogPost $nextBlogPost = null;

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

    public function isWriter() : bool {
        if (Session::isLoggedIn())
        {
            return ($this->blog->isManager() || Session::getCurrentAccount()->crand == $this->author->crand) && !isset($_GET['borderless']);
        }
        else
        {
            return false;
        }
    }
        
    public function titleIsValid()
    {
        $valid = StringIsValid($this->title, 10);
        if ($valid) 
        {
            if (strtolower($this->title) == "new blog post")
                $valid = false;
        }

        return $valid;
    }

    public function summaryIsValid() {
        $valid = StringIsValid($this->summary, 200);

        return $valid;
    }

    public function locatorIsValid()
    {
        $valid = StringIsValid($this->postLocator, 5);
        if ($valid) 
        {
            if (strpos(strtolower($this->postLocator), 'new-post-') === 0) {
                $valid = false;
            }
        }

        return $valid;
    }

    public function iconIsValid()
    {
        return $this->icon->isValid();
    }

    public function isValidForPublish()
    {
        return $this->titleIsValid() && $this->summaryIsValid() && $this->locatorIsValid() && $this->pageContentIsValid() && $this->iconIsValid();
    }

    
    public function pageContentIsValid() : bool {
        return ($this->hasPageContent() && ($this->content->isValid()));
    }

    public function populateContent() : void {
        if ($this->hasPageContent())
        {
            $this->content->populateContent("BLOG-POST", $this->blogLocator."/".$this->postLocator);
        }
    }
    
    public function hasPageContent() : bool {
        return $this->content->hasPageContent();
    }
}



?>