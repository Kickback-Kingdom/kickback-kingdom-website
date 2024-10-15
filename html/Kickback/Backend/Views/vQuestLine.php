<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vDateTime;
use Kickback\Backend\Views\vReviewStatus;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Controllers\QuestController;
use Kickback\Services\Session;
use Kickback\Common\Version;

class vQuestLine extends vRecordId
{
    public string $title;
    public string $locator;
    public string $summary;
    public vDateTime $dateCreated;
    public vAccount $createdBy;
    public vReviewStatus $reviewStatus;
    public vContent $content;
    
    public ?array $quests = null;

    public ?vMedia $icon;
    public ?vMedia $banner;
    public ?vMedia $bannerMobile;

    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }

    public function getURL() : string {
        return Version::formatUrl('/quest-line/'.$this->locator);
    }
    
    public function populateQuests() : void {
        
        $resp = QuestController::getQuestsByQuestLineId($this);
        if ($resp->success)
            $this->quests = $resp->data;
        else
            throw new \Exception($resp->message);
    }


    public function populateContent() : void {
        if ($this->hasPageContent())
        {
            $this->content->populateContent("QUEST-LINE", $this->locator);
        }
    }

    public function populateEverything() : void {
        $this->populateQuests();
        $this->populateContent();
    }
    
    public function nameIsValid() {
        $valid = StringIsValid($this->title, 10);
        if ($valid) 
        {
            if (strtolower($this->title) == "new quest")
                $valid = false;
        }

        return $valid;
    }

    public function summaryIsValid() : bool {
        $valid = StringIsValid($this->summary, 200);

        return $valid;
    }
    
    public function hasPageContent() : bool {
        return $this->content->hasPageContent();
    }

    public function pageContentIsValid() : bool {
        return ($this->hasPageContent() && ($this->content->isValid()));
    }

    public function getPageContent() : vPageContent {
        return $this->content->pageContent;
    }

    public function locatorIsValid() : bool {
        $valid = StringIsValid($this->locator, 5);
        if ($valid) 
        {
            if (strpos(strtolower($this->locator), 'new-quest-') === 0) {
                $valid = false;
            }
        }

        return $valid;
    }

    public function imagesAreValid() : bool {
        return self::imageIsValid($this->icon) && self::imageIsValid($this->banner) && self::imageIsValid($this->bannerMobile);
    }

    private static function imageIsValid($media) : bool {
        return isset($media) && !is_null($media);
    }

    public function isValidForPublish() : bool {
        return $this->nameIsValid() && $this->summaryIsValid() && $this->locatorIsValid() && $this->pageContentIsValid() && $this->imagesAreValid();
    }

    public function canEdit()
    {
        return $this->isCreator() || Session::isAdmin();
    }

    public function isCreator()
    {
        if (Session::isLoggedIn())
        {
            return (Session::getCurrentAccount()->crand == $this->createdBy->crand );
        }
        else{
            return false;
        }
        
    }
}



?>