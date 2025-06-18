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
use Kickback\Common\Str;

class vQuestLine extends vRecordId
{
    public string $title;
    public string $locator;
    public string $summary;
    public vDateTime $dateCreated;
    public vAccount $createdBy;
    public vReviewStatus $reviewStatus;
    public vContent $content;

    /**
    * @var ?array<vQuest> $quests
    */
    public ?array $quests = null;

    public ?vMedia $icon;
    public ?vMedia $banner;
    public ?vMedia $bannerMobile;

    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }

    public function url() : string {
        return Version::formatUrl('/quest-line/'.$this->locator);
    }
    
    public function populateQuests() : void {
        $this->quests = QuestController::queryQuestsByQuestLineId($this);
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
    
    public function nameIsValid() : bool
    {
        $valid = Str::is_longer_than($this->title, 10);
        if ($valid) 
        {
            if (strtolower($this->title) == "new quest")
                $valid = false;
        }

        return $valid;
    }

    public function summaryIsValid() : bool {
        $valid = Str::is_longer_than($this->summary, 200);

        return $valid;
    }
    
    public function hasPageContent() : bool {
        return $this->content->hasPageContent();
    }

    public function pageContentIsValid() : bool {
        return ($this->hasPageContent() && ($this->content->isValid()));
    }

    public function pageContent() : vPageContent {
        return $this->content->pageContent();
    }

    public function locatorIsValid() : bool {
        $valid = Str::is_longer_than($this->locator, 5);
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

    private static function imageIsValid(?vMedia $media) : bool {
        return isset($media);
        // && !is_null($media); <- redundant with `isset`; it makes PHPStan complain because then `is_null($media)` is ALWAYS false.
    }

    public function isValidForPublish() : bool {
        return $this->nameIsValid() && $this->summaryIsValid() && $this->locatorIsValid() && $this->pageContentIsValid() && $this->imagesAreValid();
    }

    public function canEdit() : bool
    {
        return $this->isCreator() || Session::isMagisterOfTheAdventurersGuild();
    }

    public function isCreator() : bool
    {
        if (Session::isLoggedIn())
        {
            return !is_null(Session::getCurrentAccount()) && (Session::getCurrentAccount()->crand == $this->createdBy->crand );
        }
        else{
            return false;
        }
        
    }
}



?>
