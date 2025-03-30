<?php

declare(strict_types=1);

namespace Kickback\Backend\Models;


enum ItemType: int {
    case Badge = 0; //cannot be traded and they go to your badge inventory instead of item inventory. these must be earned!
    case PrestigeToken = 1; //can be used to denounce or commend someone
    case RaffleTicket = 2; //can be used for raffles
    case Standard = 3; //basic item
    case Unique = 4; //only one allowed in existance
    case NonTradable = 5; //cannot be traded
    case WritOfPassage = 6; //Writ of Passage
}

?>