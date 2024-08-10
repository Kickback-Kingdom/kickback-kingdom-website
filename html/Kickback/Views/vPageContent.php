<?php
declare(strict_types=1);

namespace Kickback\Views;

use Kickback\Views\vRecordId;

class vPageContent extends vRecordId 
{
    public array $data;
    public string $containerType;
    public string $containerId;
    
    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }
}

?>