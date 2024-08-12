<?php
declare(strict_types=1);

namespace Kickback\Backend\Models;
use Kickback\Backend\Views\vRecordId;

class RecordId extends vRecordId
{
    public function __construct() {
        $this->ctime = self::getCTime();
        $this->crand = self::generateCRand();
    }
    
    public static function getSystemSalt(): int {
        $localIP = gethostbyname(gethostname());
        $processId = getmypid();
        return crc32($localIP . $processId);
    }

    public static function getHighResTime(): int {
        return hrtime(true);
    }

    public static function getSeededRandomInt(int $seed): int {
        mt_srand($seed);
        return mt_rand();
    }

    public static function generateCRand(): int {
        $salt = self::getSystemSalt();
        $time = self::getHighResTime();
        $seed = $time + $salt;
        return self::getSeededRandomInt($seed);
    }

    public static function getCTime(): string {
        return date('Y-m-d H:i:s.u');
    }
}

?>