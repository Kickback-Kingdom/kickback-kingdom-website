<?php
declare(strict_types=1);

namespace Kickback\Models;

class LobbyMatch extends RecordId
{
    public ForeignRecordId $lobbyId;
    public int $style;
    public string $rules;

    function __construct(Lobby $lobby, int $style = 0, string $rules = '')
    {
        parent::__construct();
        $this->lobbyId = $lobby;
        $this->style = $style;
        $this->rules = $rules;
    }
}



?>