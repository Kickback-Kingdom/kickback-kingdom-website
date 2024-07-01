<?php
declare(strict_types=1);

namespace Kickback\Models;

enum ItemEquipmentSlot: string {
    case AVATAR = 'AVATAR';
    case PC_BORDER = 'PC_BORDER';
    case BANNER = 'BANNER';
    case BACKGROUND = 'BACKGROUND';
    case CHARM = 'CHARM';
    case PET = 'PET';
    public static function fromString(string $slotString): self {
        return self::from($slotString);
    }
}


?>