<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

use Kickback\Backend\Views\vMedia;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Models\ForeignRecordId;

class vChallengeHistory extends vRecordId
{
    public ForeignRecordId $gameId;
    public ForeignRecordId $tournamentId;
    public int $playerCount;
    public ?array $teams = null;
    public vDateTime $dateTime;

    
    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }
}

?>