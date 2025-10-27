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
use Kickback\Common\Primitives\Str;

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

    public function url() : string {
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

    /**
    * @phpstan-assert-if-true !null $this->endDate_
    */
    public function hasEndDate() : bool {
        return (!is_null($this->endDate_));
    }

    public function expired() : bool {
        return ($this->hasEndDate() && $this->endDate_->expired());
    }

    /**
    * @phpstan-assert-if-true !null $this->tournament
    */
    public function isTournament() : bool {
        return ($this->tournament != null);
    }

    /**
    * @phpstan-assert-if-true !null $this->raffle
    */
    public function isRaffle() : bool {
        return ($this->raffle != null);
    }

    public function isBracketTournament() : bool {
        return ($this->isTournament() && $this->tournament->hasBracket());
    }

    /**
    * @phpstan-assert-if-true !null $this->questLine
    */
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

    /**
    * @phpstan-assert-if-true !null $this->rewards
    */
    public function rewardsAreValid() : bool {
        return $this->rewards != null && $this->hasRewards();
    }

    /**
    * @phpstan-assert-if-true !null $this->rewards
    */
    public function hasRewards() : bool {
        return (!is_null($this->rewards) && count($this->rewards) > 0);
    }

    public function isValidForPublish() : bool {
        return $this->nameIsValid() && $this->summaryIsValid() && $this->locatorIsValid() && $this->pageContentIsValid() && $this->imagesAreValid() && $this->rewardsAreValid();
    }

    public function populateQuestLine() : void
    {
        if ($this->hasQuestLine())
        {
            $this->questLine = QuestLineController::queryQuestLineById($this->questLine);
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
        $questRewards = QuestController::queryQuestRewardsByQuestId($this);
        $this->rewards = [];
        foreach ($questRewards as $questReward) {
            $this->rewards[$questReward->category][] = $questReward;
        }
    }

    public function populateTournament() : void {
        if ($this->isTournament()) {
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
    public function queryQuestApplicants() : array {
        return QuestController::queryQuestApplicants($this);
    }

    public function populateEverything() : void {
        
        $this->populateContent();
        $this->populateRewards();
        $this->populateTournament();
        $this->populateQuestLine();
    }
}



?>
