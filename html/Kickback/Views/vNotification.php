<?php 
declare(strict_types=1);

namespace Kickback\Views;

use Kickback\Views\vQuest;
use Kickback\Models\NotificationType;

class vNotification
{

    public ?vQuest $quest;
    public NotificationType $type;
    public vDateTime $date;
    public ?vPrestigeReview $prestigeReview;
    public ?vQuestReview $questReview;

    public function getText()
    {
        switch ($this->type) {
            case NotificationType::QUEST_REVIEW:
                return "<strong>Thanks for participating</strong> in <a href='".$this->quest->getURL()."'>".$this->quest->title."</a>. Please review your experience so we can build better quests for you in the future. Thanks! ".'<i class="fa-regular fa-face-smile-beam"></i>';
                break;

            
            case NotificationType::THANKS_FOR_HOSTING:
                return "<strong>Thanks for hosting</strong> <a href='".$this->quest->getURL()."'>".$this->quest->title."</a>! Once a few of your participants send in their reviews you will recieve your host reward. In the meantime enjoy your quest rewards. Thanks! ".'<i class="fa-regular fa-face-smile-beam"></i>';
                break;

            case NotificationType::PRESTIGE:
                return $this->prestigeReview->fromAccount->getAccountElement()." used a prestige token on you.";
                break;

            case NotificationType::QUEST_IN_PROGRESS:
                return "You are participating in <a href='".$this->quest->getURL()."'>".$this->quest->title."</a> which is currently in progress. Please check in often to make sure no one is waiting on you. Thanks! ".'<i class="fa-regular fa-face-smile-beam"></i>';
                break;

            
            case NotificationType::QUEST_REVIEWED:
                return $this->questReview->fromAccount->getAccountElement()." just left a review for your quest - <a href='".$this->quest->getURL()."'>".$this->quest->title."</a>";
                break;

            default:
                return "Unknown Event Occurred";
                break;
        }
    }

    public function getTitle() : string
    {
        // Access the string value of the enum
        $type = $this->type->value;

        switch ($type) {
            case NotificationType::QUEST_REVIEW->value:
            case NotificationType::THANKS_FOR_HOSTING->value:
                return '<i class="fa-solid fa-gift"></i> Pending Rewards';

            case NotificationType::PRESTIGE->value:
                return "New Prestige";

            case NotificationType::QUEST_REVIEWED->value:
                return '<i class="fa-solid fa-star"></i> Quest Reviewed';

            case NotificationType::QUEST_IN_PROGRESS->value:
                return '<i class="fa-solid fa-spinner fa-spin"></i> Quest In Progress';

            default:
                return "{" . $type . "}";
        }
    }




    public function getCTA()
    {

    }
}

?>