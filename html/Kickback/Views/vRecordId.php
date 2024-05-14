<?php 
declare(strict_types=1);

namespace Kickback\Views;

class vRecordId
{
    public string $ctime;
    public int $crand;
    public bool $usesLegacyId = false;
    
    function __construct(int $crand, string $ctime = '')
    {
        $this->ctime = $ctime;
        $this->crand = $crand;
        $this->usesLegacyId = empty($ctime);
    }
}

?>