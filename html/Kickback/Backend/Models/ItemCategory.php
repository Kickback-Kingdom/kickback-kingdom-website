<?php
declare(strict_types=1);

namespace Kickback\Backend\Models;

enum ItemCategory: int {
    case LichCard = 1;
    case DeckBox = 2;
    case CardBinder = 3;
}

?>