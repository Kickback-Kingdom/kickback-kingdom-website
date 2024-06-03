<?php

declare(strict_types=1);

namespace Kickback\Models;


enum ItemType: int {
    case Badge = 0;
    case PrestigeToken = 1;
    case RaffleTicket = 2;
    case Standard = 3;
    case Unique = 4;
    case NonTradable = 5;
}

?>