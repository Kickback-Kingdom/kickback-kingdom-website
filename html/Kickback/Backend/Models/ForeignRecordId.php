<?php 
declare(strict_types=1);

namespace Kickback\Backend\Models;
use Kickback\Backend\Views\vRecordId;

class ForeignRecordId extends vRecordId
{
    
    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }
}

?>
