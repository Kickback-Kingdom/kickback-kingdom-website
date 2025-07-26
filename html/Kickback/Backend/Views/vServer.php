<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

use Kickback\Backend\Views\vMedia;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vGame;
use Kickback\Backend\Views\vDateTime;
use Kickback\Backend\Views\vContent;
use Kickback\Common\Version;

class vServer extends vRecordId
{
    public string $name;
    public string $description;
    public ?string $serverVersion = null;

    public vGame $game;
    public ?vAccount $owner = null;

    public string $ip;
    public int $port;
    public ?string $password = null;

    public bool $isOfficial = false;
    public bool $isPublic = true;
    public bool $requiresWhitelist = false;
    public bool $isOnline = true;

    public int $currentPlayers = 0;
    public int $maxPlayers = 64;

    public ?string $region = null;
    public string $joinMethod = 'steam'; // steam, manual, kickback

    /** @var array<string> */
    public array $tags = [];

    public ?vMedia $icon = null;
    public ?vMedia $banner = null;
    public ?vMedia $bannerMobile = null;

    public ?vContent $content = null;
    public ?string $locator = null;

    public ?vDateTime $lastSeenOnline = null;
    public ?vDateTime $lastSeenOffline = null;

    public vDateTime $dateCreated;
    public vDateTime $lastUpdated;

    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }

    public function url(): string
    {
        return Version::formatUrl("/servers/" . $this->crand);
    }

    public function hasPassword(): bool
    {
        return !empty($this->password);
    }

    public function isJoinable(): bool
    {
        return $this->isOnline && $this->currentPlayers < $this->maxPlayers;
    }

    public function connectUrl(): ?string
    {
        switch ($this->joinMethod) {
            case 'steam':
                return "steam://connect/{$this->ip}:{$this->port}";
            case 'kickback':
                return "kickback://join/{$this->ip}:{$this->port}";
            case 'manual':
            default:
                return null;
        }
    }

    public function requiresManualJoin(): bool
    {
        return $this->joinMethod === 'manual' || is_null($this->connectUrl());
    }

    public function playerSummary(): string
    {
        return "{$this->currentPlayers}/{$this->maxPlayers} players";
    }

    public function displayTags(): string
    {
        return implode(', ', $this->tags);
    }

    public function hasMedia(): bool
    {
        return vMedia::isValidRecordId($this->icon)
            || vMedia::isValidRecordId($this->banner)
            || vMedia::isValidRecordId($this->bannerMobile);
    }

    public function hasOwner(): bool
    {
        return !is_null($this->owner);
    }

    public function gameUrl(): string
    {
        return $this->game->url();
    }

    public function hasTags(): bool
    {
        return count($this->tags) > 0;
    }
}
?>
