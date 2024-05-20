<?php
declare(strict_types=1);

namespace Kickback\Models;
use Kickback\Views;

class RecordId extends vRecordId
{
    //public string    $ctime;
    //public int  $crand;
    
    public function __construct() {
        $this->ctime = $this->GetCTime();
        $this->crand = $this->GenerateCRand();
    }
    
    // Get the system salt by combining local IP and process ID
    protected function GetSystemSalt(): int {
        $localIP = gethostbyname(gethostname());
        $processId = getmypid();
        return crc32($localIP . $processId);
    }

    // Get high-resolution time in nanoseconds
    protected function GetHighResTime(): int {
        return hrtime(true);
    }

    // Generate a random integer based on a seed
    protected function GetSeededRandomInt(int $seed): int {
        mt_srand($seed);
        return mt_rand();
    }

    // Combine system salt and high-resolution time to generate a unique crand
    public function GenerateCRand(): int {
        $salt = $this->GetSystemSalt();
        $time = $this->GetHighResTime();
        $seed = $time + $salt;
        return $this->GetSeededRandomInt($seed);
    }

    // Get current time with microseconds
    public function GetCTime(): string {
        return date('Y-m-d H:i:s.u');
    }
}

?>