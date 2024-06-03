<?php 
declare(strict_types=1);

namespace Kickback\Views;

use Kickback\Views\vRecordId;
use Kickback\Views\vMedia;
use Kickback\Views\vQuest;
use Kickback\Views\vQuestLine;
use Kickback\Views\vQuote;
use Kickback\Views\vBlogPost;
use Kickback\Views\vActivity;
use Kickback\Views\vDateTime;
use Kickback\Views\vReviewStatus;
use Kickback\Models\PlayStyle;

class vFeedCard
{
    public string $type;
    public string $typeText;
    public vMedia $icon;
    public ?vQuest $quest = null;
    public ?vQuote $quote = null;
    public ?vQuestLine $questLine = null;
    public ?vBlogPost $blogPost = null;
    public ?vActivity $activity = null;
    public ?string $url = null;
    public string $title;
    public vDateTime $dateTime;
    public vREviewStatus $reviewStatus;
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
    public bool $hideDateTime = false;

    //HTML CSS
    public string $cssClassCard = "";
    public string $cssClassImageColSize = "col col-auto col-md";
    public string $cssClassTextColSize = "col col-12 col-md-8 col-lg-9";
    public string $cssClassRight = "";

    public function getURL()
    {
        return $this->url;
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

        return $accounts;
    }
    public function getAccountCount()
    {
        return count(getAccounts());
    }
}

?>