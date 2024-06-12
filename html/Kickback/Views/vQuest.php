<?php
declare(strict_types=1);

namespace Kickback\Views;

use Kickback\Views\vAccount;
use Kickback\Views\vDateTime;
use Kickback\Views\vTournament;
use Kickback\Views\vContent;
use Kickback\Services\Session;
use Kickback\Views\vReviewStatus;
use Kickback\Views\vQuestLine;
use Kickback\Models\PlayStyle;
use Kickback\Controllers\QuestLineController;
use Kickback\Controllers\QuestController;
use Kickback\Models\Response;

class vQuest extends vRecordId
{
    public string $title;
    public string $locator;
    public string $summary;
    public ?vDateTime $endDate = null;
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

    public ?array $rewards = null;

    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }

    public function getURL() : string {
        return '/q/'.$this->locator;
    }

    public function hasEndDate() : bool {
        return ($this->endDate != null);
    }

    public function hasExpired() : bool {
        return ($this->hasEndDate() && $this->endDate->isExpired());
    }

    public function isTournament() : bool {
        return ($this->tournament != null);
    }

    public function isRaffle() : bool {
        return ($this->raffle != null);
    }

    public function isBracketTournament() : bool {
        return ($this->isTournament() && $this->tournament->hasBracket);
    }

    public function hasQuestLine() : bool {
        return ($this->questLine != null);
    }

    public function canEdit() : bool {
        return $this->isHost() || Session::isAdmin();
    }

    public function isHost() : bool {
        if (Session::isLoggedIn())
        {
            return (Session::getCurrentAccount()->crand == $this->host1->crand || ($this->host2 != null && Session::getCurrentAccount()->crand == $this->host2->crand));
        }
        else{
            return false;
        }
    }

    public function hasPageContent() : bool {
        return $this->content->hasPageContent();
    }

    public function getHost2Id() : string {
        if ($this->host2 == null)
            return "";
        return $this->host2->crand;
    }

    public function getHost2Username() : string {
        if ($this->host2 == null)
            return "";
        return $this->host2->username;
    }

    public function nameIsValid() : bool {
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
    
    public function pageContentIsValid() : bool {
        return ($this->hasPageContent() && ($this->content->isValid()));
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

    public function rewardsAreValid() : bool {
        return $this->rewards != null && $this->hasRewards();
    }

    public function hasRewards() : bool {
        return (count($this->rewards) > 0);
    }

    public function isValidForPublish() : bool {
        return $this->nameIsValid() && $this->summaryIsValid() && $this->locatorIsValid() && $this->pageContentIsValid() && $this->imagesAreValid() && $this->rewardsAreValid();
    }

    public function populateQuestLine() : void {
        if ($this->hasQuestLine())
        {
            $resp = QuestLineController::getQuestLineById($this->questLine);
            if ($resp->success)
            {
                $this->questLine = $resp->data;
                if ($this->questLine->reviewStatus->published)
                {
                    $this->questLine->populateQuests();
                }
            }
            else
                throw new \Exception($resp->message);
                
        }
    }

    public function populateContent() : void {
        if ($this->hasPageContent())
        {
            $this->content->populateContent("QUEST", $this->locator);
        }
    }

    public function populateRewards() : void {
        $questRewardsResp = QuestController::getQuestRewardsByQuestId($this);
        if ($questRewardsResp->success)
        {
            $questRewards = $questRewardsResp->data;
            $this->rewards = [];
            foreach ($questRewards as $questReward) {
                $this->rewards[$questReward->category][] = $questReward;
            }
        }
        else {
            throw new \Exception($questRewardsResp->message);
        }
    }

    public function populateTournament() : void {
        if ($this->isTournament())
        {
            $this->tournament->populate();
        }
    }

    public function checkSpecificParticipationRewardsExistById() {
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
        foreach ($specificRewardIds as $specificRewardId) {
            if (!in_array($specificRewardId, $participationRewardIds)) {
                return false;
            }
        }
    
        // If all specific reward Ids are found, return true
        return true;
    }

    public function getPageContent() : array {
        return $this->content->pageContent;
    }

    public function getQuestApplicants() : Response {
        return QuestController::getQuestApplicants($this);
    }

    public function populateEverything() : void {
        
        $this->populateContent();
        $this->populateRewards();
        $this->populateTournament();
        $this->populateQuestLine();
    }

}



?>