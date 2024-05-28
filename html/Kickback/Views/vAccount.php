<?php
declare(strict_types=1);

namespace Kickback\Views;

use Kickback\Views\vMedia;

class vAccount extends vRecordId
{
    public string $username;
    public string $firstName;
    public string $lastName;
    public bool $isBanned;
    public string $email;
    public int $exp;
    public int $level;
    public int $expNeeded;
    public int $expStarted;
    public int $prestige;
    public int $badges;
    public int $expCurrent;
    public int $expGoal;

    public string $title;

    public bool $isAdmin;
    public bool $isMerchant;
    public bool $isAdventurer;
    public bool $isSteward;
    public bool $isCraftsmen;
    public bool $isMasterOrApprentice;
    public bool $isArtist;
    public bool $isQuestGiver;

    public bool $isGoldCardHolder;

    public ?vMedia $avatar = null;
    public ?vMedia $playerCardBorder = null;
    public ?vMedia $banner = null;
    public ?vMedia $background = null;
    public ?vMedia $charm = null;
    public ?vMedia $companion = null;
    
    public array $badge_display = [];
    public array $game_ranks = [];
    
    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);

        $this->setDefaultProfilePicture();
    }

    public function getURL() : string
    {
        return "/u/".$this->username;
    }

    public function getProfilePictureURL() : string
    {
        return $this->avatar->getFullPath();
    }

    private function setDefaultProfilePicture()
    {
        $avatarMedia = new vMedia();
        $avatarMedia->setMediaPath(self::getDefaultProfilePicture($this->crand));
        $this->avatar = $avatarMedia;
    }


    private static function getAccountDefaultProfilePicture(vRecordId $recordId) : string 
    {
        return self::getDefaultProfilePicture($recordId->crand);
    }

    private static function getDefaultProfilePicture(int $i) : string
    {

        $total = 34;
        $hash = md5((string)$i);
        $hash_number = hexdec(substr($hash, 0, 8));
        $random_number = $hash_number % $total + 1;
        $image_id = $random_number;
        return "profiles/young-".$image_id.".jpg";
    }

    public function getAccountElement() : string {
        return '<a href="'.$this->getURL().'" class="username">'.$this->username.'</a>';
    }
}



?>