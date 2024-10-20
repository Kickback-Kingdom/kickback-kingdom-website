<?php
declare(strict_types=1);

namespace Kickback\Backend\Models;

class Lobby extends RecordId
{
    public ForeignRecordId $hostId;
    public string $name;
    public ForeignRecordId $gameId;

    function __construct(ForeignRecordId $game, ForeignRecordId $host, string $name)
    {
        parent::__construct();
        $this->hostId = $host;
        $this->name = $name;
        $this->gameId = $game;
    }
}



?>