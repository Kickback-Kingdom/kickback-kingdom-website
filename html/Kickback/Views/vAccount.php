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

    public bool $isAdmin;
    public bool $isMerchant;
    public bool $isAdventurer;
    public bool $isSteward;
    public bool $isCraftsmen;
    public bool $isMasterOrApprentice;
    public bool $isArtist;
    public bool $isQuestGiver;

    public ?vMedia $avatar = null;
    public ?vMedia $playerCardBorder = null;
    public ?vMedia $banner = null;
    public ?vMedia $background = null;
    public ?vMedia $charm = null;
    public ?vMedia $companion = null;
    
    
    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }

    public function getURL() : string
    {
        return "/u/".$this->username;
    }

    public function getProfilePictureURL() : string
    {

        if ($this->avatar != null)
        {
            return $this->avatar->getFullPath();
        }
        else
        {
            return self::getAccountDefaultProfilePicture($this);
        }
    }


    private static function getAccountDefaultProfilePicture(vRecordId $recordId) : string 
    {
        return self::getDefaultProfilePicture($recordId->crand);
    }

    public static function getDefaultProfilePicture(int $i) : string
    {

        $total = 34;
        $hash = md5((string)$i);
        $hash_number = hexdec(substr($hash, 0, 8));
        $random_number = $hash_number % $total + 1;
        $image_id = $random_number;
        return "/assets/media/profiles/young-".$image_id.".jpg";
    }

    public function getAccountElement() : string {
        return '<a href="'.$this->getURL().'" class="username">'.$this->username.'</a>';
    }
}



?>