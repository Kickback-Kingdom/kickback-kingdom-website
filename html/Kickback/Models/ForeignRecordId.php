<?php 
declare(strict_types=1);

namespace Kickback\Models;
use Kickback\Views;

class ForeignRecordId extends vRecordId
{
    
    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }
}

?>