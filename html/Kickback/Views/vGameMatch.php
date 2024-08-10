<?php
declare(strict_types=1);

namespace Kickback\Views;

use Kickback\Views\vRecordId;

class vGameMatch extends vRecordId
{
    public int $bracket;
    public int $round;
    public int $match;
    public int $set;
    public string $description;
    public string $characterHint;
    
    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }
}

?>