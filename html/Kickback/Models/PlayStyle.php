<?php

declare(strict_types=1);

namespace Kickback\Models;


enum PlayStyle: int {
    case Casual = 0;
    case Ranked = 1;
    case Hardcore = 2;
    case Roleplay = 3;
}

?>