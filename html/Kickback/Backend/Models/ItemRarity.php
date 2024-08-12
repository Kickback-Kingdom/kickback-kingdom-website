<?php

declare(strict_types=1);

namespace Kickback\Backend\Models;


enum ItemRarity: int {
    case Common = 0;
    case Uncommon = 1;
    case Rare = 2;
    case Epic = 3;
    case Legendary = 4;
    case Unique = 5;
}

?>