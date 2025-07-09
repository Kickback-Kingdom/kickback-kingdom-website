<?php
declare(strict_types=1);

namespace Kickback\Backend\Models;

use Kickback\Backend\Models\Lobby;
use Kickback\Backend\Models\RecordId;
use Kickback\Backend\Models\ForeignRecordId;
use Kickback\Backend\Models\PlayStyle;

class LobbyChallenge extends RecordId
{
    public ForeignRecordId $lobbyId;
    public PlayStyle $style;
    public string $rules;

    function __construct(Lobby $lobby, PlayStyle $style = PlayStyle::Casual, string $rules = '')
    {
        parent::__construct();
        $this->lobbyId = $lobby->getForeignRecordId();
        $this->style = $style;
        $this->rules = $rules;
    }
}



?>