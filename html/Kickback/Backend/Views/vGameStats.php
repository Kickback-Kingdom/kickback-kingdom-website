<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

use Kickback\Backend\Models\ForeignRecordId;

class vGameStats
{
    // Declare the fields
    public ForeignRecordId $gameId;
    public ?float $elo;              // Elo rating
    public bool $is_ranked;          // Whether the player is ranked
    public int $ranked_matches;      // Number of ranked matches
    public int $total_wins;          // Total wins
    public int $total_losses;        // Total losses
    public ?float $win_rate;         // Win rate percentage
    public ?int $rank;               // Player's rank
    

    // Constructor to initialize the game stats object
    function __construct(string $ctime = '', int $crand = -1)
    {
        $this->gameId = new ForeignRecordId($ctime, $crand);
        
        // Initialize other fields with default values
        $this->elo = null;
        $this->is_ranked = false;
        $this->ranked_matches = 0;
        $this->total_wins = 0;
        $this->total_losses = 0;
        $this->win_rate = null;
        $this->rank = null;
    }

    public function getRankElement() : string {
        if ($this->is_ranked)
        {
            return "#".$this->rank;
        }
        else{
            return "unranked";
        }
    }
}
