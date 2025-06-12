<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vDateTime;
use Kickback\Backend\Views\vTournament;
use Kickback\Backend\Views\vContent;
use Kickback\Services\Session;
use Kickback\Backend\Views\vReviewStatus;
use Kickback\Backend\Views\vQuestLine;
use Kickback\Backend\Models\PlayStyle;
use Kickback\Backend\Controllers\QuestLineController;
use Kickback\Backend\Controllers\QuestController;
use Kickback\Backend\Models\Response;
use Kickback\Common\Exceptions\UnexpectedNullException;
use Kickback\Common\Version;
use Kickback\Common\Str;

class vQuest extends vRecordId
{
    public string $title;
    public string $locator;
    public string $summary;
    private ?vDateTime $endDate_ = null;
    public vAccount $host1;
    public ?vAccount $host2 = null;
    public vReviewStatus $reviewStatus;
    public PlayStyle $playStyle; 
    public bool $requiresApplication;

    public ?vTournament $tournament = null;
    public vContent $content;
    public ?vRaffle $raffle = null;
    public ?vQuestLine $questLine = null;

    public ?vMedia $icon;
    public ?vMedia $banner;
    public ?vMedia $bannerMobile;

    /** @var ?array<string,array<vQuestReward>> */
    public ?array $rewards = null;

    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }

    public function getURL() : string {
        return Version::formatUrl('/q/'.$this->locator);
    }

    public function endDate(vDateTime ...$newValue) : vDateTime
    {
        if ( count($newValue) === 1 ) {
            $this->endDate_ = $newValue[0];
            return $this->endDate_;
        }

        if ( is_null($this->endDate_) ) {
            throw new UnexpectedNullException();
        } else {
            return $this->endDate_;
        }
    }

    public function nullableEndDate(?vDateTime ...$newValue) : ?vDateTime
    {
        if ( count($newValue) === 1 ) {
            $this->endDate_ = $newValue[0];
        }
        return $this->endDate_;
    }

    public function hasEndDate() : bool {
        return (!is_null($this->endDate_));
    }

    public function expired() : bool {
        return ($this->hasEndDate() && $this->endDate()->expired());
    }

    public function isTournament() : bool {
        return ($this->tournament != null);
    }

    public function isRaffle() : bool {
        return ($this->raffle != null);
    }

    public function isBracketTournament() : bool {
        return (!is_null($this->tournament) && $this->tournament->hasBracket());
    }

    public function hasQuestLine() : bool {
        return ($this->questLine != null);
    }

    public function canEdit() : bool {
        return $this->isHost() || Session::isMagisterOfTheAdventurersGuild();
    }

    public function isHost() : bool {
        if (Session::isLoggedIn() && !is_null(Session::getCurrentAccount()))
        {
            $account = Session::getCurrentAccount();
            return ($account->crand == $this->host1->crand || ($this->host2 != null && $account->crand == $this->host2->crand));
        }
        else{
            return false;
        }
    }

    public function hasPageContent() : bool {
        return $this->content->hasPageContent();
    }

    public function getHost2Id() : int {
        if ($this->host2 == null)
            return -1;
        return $this->host2->crand;
    }

    public function getHost2Username() : string {
        if ($this->host2 == null)
            return "";
        return $this->host2->username;
    }

    public function nameIsValid() : bool {
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
    
    public function pageContentIsValid() : bool {
        return ($this->hasPageContent() && ($this->content->isValid()));
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

    public function rewardsAreValid() : bool {
        return $this->rewards != null && $this->hasRewards();
    }

    public function hasRewards() : bool {
        return (!is_null($this->rewards) && count($this->rewards) > 0);
    }

    public function isValidForPublish() : bool {
        return $this->nameIsValid() && $this->summaryIsValid() && $this->locatorIsValid() && $this->pageContentIsValid() && $this->imagesAreValid() && $this->rewardsAreValid();
    }

    public function populateQuestLine() : void
    {
        if (!is_null($this->questLine))
        {
            $this->questLine = QuestLineController::requestQuestLineById($this->questLine);
            if ($this->questLine->reviewStatus->published) {
                $this->questLine->populateQuests();
            }
        }
    }

    public function populateContent() : void {
        if ($this->hasPageContent())
        {
            $this->content->populateContent("QUEST", $this->locator);
        }
    }

    public function populateRewards() : void {
        $questRewards = QuestController::requestQuestRewardsByQuestId($this);
        $this->rewards = [];
        foreach ($questRewards as $questReward) {
            $this->rewards[$questReward->category][] = $questReward;
        }
    }

    public function populateTournament() : void {
        if (!is_null($this->tournament)) {
            $this->tournament->populate();
        }
    }

    public function checkSpecificParticipationRewardsExistById() : bool
    {
        // Define the specific reward Ids to check for
        $specificRewardIds = [3, 4, 15];
        
        // Check if 'Participation' category exists
        if (!isset($this->rewards['Participation'])) {
            return false;
        }
    
        // Extract Ids of the rewards in the Participation category
        $participationRewardIds = array_map(function($reward) {
            return $reward->item->crand;
        }, $this->rewards['Participation']);
    
        // Check for the existence of each specific reward Id
        foreach ($specificRewardIds as $specificRewardId)
        {
            if (!in_array($specificRewardId, $participationRewardIds, true)) {
                return false;
            }
        }
    
        // If all specific reward Ids are found, return true
        return true;
    }

    public function pageContent() : vPageContent {
        return $this->content->pageContent();
    }

    /**
    * @return array<vQuestApplicant>
    */
    public function requestQuestApplicants() : array {
        return QuestController::requestQuestApplicants($this);
    }

    public function populateEverything() : void {
        
        $this->populateContent();
        $this->populateRewards();
        $this->populateTournament();
        $this->populateQuestLine();
    }
}



?>
