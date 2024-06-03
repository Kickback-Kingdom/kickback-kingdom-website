<?php
declare(strict_types=1);

namespace Kickback\Views;

use Kickback\Views\vAccount;
use Kickback\Views\vDateTime;
use Kickback\Views\vReviewStatus;
use Kickback\Views\vRecordId;
use Kickback\Controllers\QuestController;

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
        return '/quest-line/'.$this->locator;
    }
    
    public function populateQuests() : void {
        
        $resp = QuestController::getQuestsByQuestLineId($this);
        if ($resp->success)
            $this->quests = $resp->data;
        else
            throw new \Exception($resp->message);
    }
}



?>