<?php 
declare(strict_types=1);

namespace Kickback\Backend\Views;

use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vMedia;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vDateTime;

class vActivity
{
    public string $type;
    public vAccount $account;
    public string $verb;
    public int $nameId;
    public string $name;
    public ?string $team;
    public ?string $character;
    public ?bool $characterWasRandom;
    public vDateTime $dateTime;
    public vMedia $icon;
    public ?string $url = null;

    public function getMedia() : vMedia
    {
        return $this->icon;
    }
}

?>