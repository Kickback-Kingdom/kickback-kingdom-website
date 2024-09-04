<?php 
declare(strict_types=1);

namespace Kickback\Backend\Views;

use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vMedia;
use Kickback\Backend\Views\vQuest;
use Kickback\Backend\Views\vQuestLine;
use Kickback\Backend\Views\vQuote;
use Kickback\Backend\Views\vBlogPost;
use Kickback\Backend\Views\vActivity;
use Kickback\Backend\Views\vDateTime;
use Kickback\Backend\Views\vReviewStatus;
use Kickback\Backend\Models\PlayStyle;
use Kickback\Common\Version;

class vFeedCard
{
    public string $type;
    public string $typeText;
    public vMedia $icon;
    public ?vQuest $quest = null;
    public ?vQuote $quote = null;
    public ?vQuestLine $questLine = null;
    public ?vBlog $blog = null;
    public ?vBlogPost $blogPost = null;
    public ?vActivity $activity = null;
    public ?string $url = null;
    public string $title;
    public ?vDateTime $dateTime = null;
    public ?vReviewStatus $reviewStatus = null;
    public string $description;

    //RENDERING TEXT
    public string $createdByPrefix = "Hosted";
    public string $cta = "Learn More";

    //RENDERING OPTIONS
    public bool $hideCTA = false;
    public bool $hideType = false;
    public bool $quoteStyleText = false;
    public bool $createdByShowOnlyDate = false;
    public bool $hasCreatedBy = true;
    public bool $hasTags = false;
    public bool $hasRewards = false;

    //HTML CSS
    public string $cssClassCard = "";
    public string $cssClassImageColSize = "col col-auto col-md";
    public string $cssClassTextColSize = "col col-12 col-md-8 col-lg-9";
    public string $cssClassRight = "";

    public function getURL()
    {
        return Version::formatUrl($this->url);
    }

    public function getAccountLinks() : string {
        $html = '';
    
        $accounts = $this->getAccounts();
        $totalAccounts = count($accounts);
    
        foreach ($accounts as $index => $account) {
            $html .= $account->getAccountElement();
            if ($index < $totalAccounts - 1) {
                $html .= ' and ';
            }
        }
    
        return $html;
    }
    
    public function isDraft() : bool {
        if ($this->reviewStatus == null)
            return false;

        return $this->reviewStatus->isDraft();
    }

    public function getTitle() : string {
        if ($this->isDraft())
        {
            return "[DRAFT] ".$this->title." [DRAFT]";
        }
        else{
            return $this->title;
        }
    }

    public function getAccounts() : array {
        $accounts = [];
        if ($this->quest != null)
        {
            $accounts[] = $this->quest->host1;
            if ($this->quest->host2 != null)
            {
                $accounts[] = $this->quest->host2;
            }
        }
        if ($this->quote != null)
        {
            $account = new vAccount();
            $account->username = $this->quote->author;
            $accounts[] = $account;
        }
        if ($this->blogPost != null)
        {
            $accounts[] = $this->blogPost->author;
        }

        if ($this->activity != null)
        {
            $accounts[] = $this->activity->account;
        }

        if ($this->blog != null && $this->blog->lastWriter != null)
        {
            $accounts[] = $this->blog->lastWriter;
        }

        return $accounts;
    }
    public function getAccountCount() : int {
        return count(getAccounts());
    }
    
    public function hasDateTime() : bool {
        return ($this->dateTime != null);
    }

    public function useGoldTrim() : bool {
        return !(($this->hasDateTime() && $this->dateTime->isExpired()) || !$this->hasDateTime());
    }
}

?>