<?php 
declare(strict_types=1);

namespace Kickback\Backend\Views;

use Kickback\Backend\Models\ForeignRecordId;

class vRecordId
{
    public string $ctime;
    public int $crand;
    
    function __construct(string $ctime = '', int $crand = -1)
    {
        $this->ctime = $ctime;
        $this->crand = $crand;
    }

    function getForeignRecordId() : ForeignRecordId {
        return new ForeignRecordId($this->ctime, $this->crand);
    }
}

?>