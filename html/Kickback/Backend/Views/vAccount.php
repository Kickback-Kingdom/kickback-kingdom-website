<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

use Kickback\Backend\Views\vMedia;
use Kickback\Backend\Views\vRecordId;
use Kickback\Common\Version;
use Kickback\Backend\Controllers\AccountController;

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
    public bool $isMagisterOfAdventurers;
    public bool $isChancellorOfExpansion;
    public bool $isChancellorOfTechnology;
    public bool $isStewardOfExpansion;
    public bool $isStewardOfTechnology;
    public bool $isServantOfTheLich;
    public bool $isGoldCardHolder;

    public ?vMedia $avatar = null;
    public ?vMedia $playerCardBorder = null;
    public ?vMedia $banner = null;
    public ?vMedia $background = null;
    public ?vMedia $charm = null;
    public ?vMedia $companion = null;

    /** @var array<vLoot> */
    public ?array $badge_display = null;

    /**
    * @var array<array{
    *     rank:     int,
    *     locator:  string,
    *     name:     string,
    *     ranked_matches:  int,
    *     minimum_ranked_matches_required:  int
    * }>
    */
    public ?array $game_ranks = null;

    /** @var array<vGameStats> */
    public ?array $game_stats = null;

    /** @var array<vMatchStats> */
    public ?array $match_stats = null;
    
    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);

        $this->setDefaultProfilePicture();
    }

    public function canUploadImages() : bool {
        return $this->isArtist || $this->isQuestGiver;
    }

    public function url() : string
    {
        return Version::formatUrl("/u/".$this->username);
    }

    public function profilePictureURL() : ?string
    {
        return !is_null($this->avatar) ? $this->avatar->getFullPath() : null;
    }

    private function setDefaultProfilePicture() : void
    {
        $avatarMedia = new vMedia();
        $avatarMedia->setMediaPath(self::getDefaultProfilePicture($this->crand));
        $this->avatar = $avatarMedia;
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
        $username = htmlspecialchars($this->username, ENT_QUOTES, 'UTF-8');
        $accountId = htmlspecialchars((string)$this->crand, ENT_QUOTES, 'UTF-8');

        return '<a href="'.$this->url().'" class="username" data-account-id="'.$accountId.'" data-username="'.$username.'">'.$username.'</a>';
    }

    public function getAccountTitle() : string {
        return AccountController::getAccountTitle($this);
    }
}



?>
