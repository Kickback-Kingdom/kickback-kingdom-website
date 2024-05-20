<?php 
declare(strict_types=1);

namespace Kickback\Views;

class vRecordId
{
    public string $ctime;
    public int $crand;
    
    function __construct(string $ctime = '', int $crand = -1)
    {
        $this->ctime = $ctime;
        $this->crand = $crand;
    }
}

?>