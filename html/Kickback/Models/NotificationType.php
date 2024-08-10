<?php
declare(strict_types=1);

namespace Kickback\Models;

enum NotificationType: string {
    case QUEST_REVIEW = 'Quest Review';
    case THANKS_FOR_HOSTING = 'Thanks For Hosting';
    case PRESTIGE = 'Prestige';
    case QUEST_IN_PROGRESS = 'Quest In Progress';
    case QUEST_REVIEWED = 'Quest Reviewed';
    case QUEST_FINALIZE = 'Finalize Quest';
}
?>
