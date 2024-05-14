<?php 
declare(strict_types=1);

namespace Kickback\Models;
use Kickback\Views;

class ForeignRecordId extends vRecordId
{
    
    function __construct(int $crand, string $ctime = '')
    {
        parent::__construct($crand, $ctime);
    }
}

?>