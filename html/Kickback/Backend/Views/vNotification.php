<?php 
declare(strict_types=1);

namespace Kickback\Backend\Views;

use Kickback\Backend\Views\vQuest;
use Kickback\Backend\Models\NotificationType;

class vNotification
{

    public ?vQuest $quest;
    public NotificationType $type;
    public vDateTime $date;
    public ?vPrestigeReview $prestigeReview;
    public ?vQuestReview $questReview;

    public function getText() : string
    {
        if ( is_null($this->quest) ) {
            // If it's OK for us to not have Quest info, then this should be fine
            // still, because it just won't get rendered.
            $quest_hyperlink = '(Error: Quest not found)';
        } else {
            $quest_url   = $this->quest->url();
            $quest_title = $this->quest->title;
            $quest_hyperlink = "<a href='$quest_url'>$quest_title</a>";
        }

        switch ($this->type)
        {
            case NotificationType::QUEST_REVIEW:
                return '<strong>Thanks for participating</strong> in '.$quest_hyperlink.'. Please review your experience so we can build better quests for you in the future. Thanks! <i class="fa-regular fa-face-smile-beam"></i>';

            case NotificationType::THANKS_FOR_HOSTING:
                return '<strong>Thanks for hosting</strong> '.$quest_hyperlink.'! Once a few of your participants send in their reviews you will recieve your host reward. In the meantime enjoy your quest rewards. Thanks! <i class="fa-regular fa-face-smile-beam"></i>';

            case NotificationType::PRESTIGE:
                if ( !is_null($this->prestigeReview) ) {
                    $person_name = $this->prestigeReview->fromAccount->getAccountElement();
                } else {
                    $person_name = '(Error: couldn\'t retrieve account/name)';
                }
                return $person_name.' used a prestige token on you.';

            case NotificationType::QUEST_IN_PROGRESS:
                return 'You are participating in '.$quest_hyperlink.' which is currently in progress. Please check in often to make sure no one is waiting on you. Thanks! <i class="fa-regular fa-face-smile-beam"></i>';

            case NotificationType::QUEST_REVIEWED:
                if ( !is_null($this->questReview) ) {
                    $person_name = $this->questReview->fromAccount->getAccountElement();
                } else {
                    $person_name = '(Error: couldn\'t retrieve account/name)';
                }
                return $person_name.' just left a review for your quest - '.$quest_hyperlink;

            default:
                return "Unknown Event Occurred";
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

    // public function getCTA()
    // {
    //
    // }
}

?>
