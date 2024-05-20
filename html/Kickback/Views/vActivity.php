<?php 
declare(strict_types=1);

namespace Kickback\Views;

use Kickback\Views\vRecordId;
use Kickback\Views\vMedia;
use Kickback\Views\vAccount;
use DateTime;

class vActivity
{
    public string $type;
    public vAccount $account;
    public string $verb;
    public int $nameId;
    public string $name;
    public ?string $team;
    public ?string $character;
    public ?bool $charaterWasRandom;
    public DateTime $dateTime;
    public string $dateTimeString;
    public ?vMedia $icon = null;
    public ?string $url = null;

    public function SetDateTime(string $dateTimeString)
    {
        $this->dateTimeString = $dateTimeString;
        $this->dateTime = date_create($dateTimeString);
    }

    public function getMedia() : vMedia
    {
        if ($this->icon == null)
        {
            $media = new vMedia();
            $media->setFullPath(vAccount::getDefaultProfilePicture($this->nameId));
            return $media;
        }

        return $this->icon;
    }
}

?>