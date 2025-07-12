<?php
declare(strict_types=1);

namespace Kickback\Backend\Models;

enum TaskType: string
{
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
    case ACHIEVEMENT = 'achievement';

    public static function fromString(string $type): self
    {
        return self::from($type);
    }
}


?>