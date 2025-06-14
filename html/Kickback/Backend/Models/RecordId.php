<?php
declare(strict_types=1);

namespace Kickback\Backend\Models;

use Kickback\Backend\Views\vRecordId;
use Kickback\Common\Exceptions\UnexpectedNullException;

class RecordId extends vRecordId
{
    public function __construct() {
        $ctime = self::getCTime();
        $crand = self::generateCRand();
        parent::__construct($ctime, $crand);
    }
    
    public static function getSystemSalt(): int {
        $hostname = gethostname();
        if ($hostname === false) {
            throw new UnexpectedNullException(
                'gethostname() failed; the local hostname is required for constructing new records');
        }
        $localIP = gethostbyname($hostname);
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
