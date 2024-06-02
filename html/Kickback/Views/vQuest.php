<?php
declare(strict_types=1);

namespace Kickback\Views;

use Kickback\Views\vAccount;
use Kickback\Views\vDateTime;
use Kickback\Views\vTournament;
use Kickback\Views\vContent;
use Kickback\Services\Session;

class vQuest extends vRecordId
{
    public string $title;
    public string $locator;
    public string $summary;
    public ?vDateTime $endDate;
    public vAccount $host1;
    public ?vAccount $host2 = null;
    public bool $published;

    public bool $requiresApplication;

    public ?vTournament $tournament = null;
    public ?vContent $content = null;
    public ?vRaffle $raffle = null;
    public ?vQuestLine $questLine = null;

    public ?vMedia $icon;
    public ?vMedia $banner;
    public ?vMedia $bannerMobile;

    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }

    public function getURL() : string {
        return '/q/'.$this->locator;
    }

    public function hasEndDate() : bool {
        return ($this->endDate != null);
    }

    public function hasExpired() : bool {
        return ($this->hasEndDate() && $this->endDate->isExpired());
    }

    public function isTournament() : bool {
        return ($this->tournament != null);
    }

    public function isRaffle() : bool {
        return ($this->raffle != null);
    }

    public function isBracketTournament() : bool {
        return ($this->isTournament() && $this->tournament->hasBracket);
    }

    public function hasQuestLine() : bool {
        return ($this->questLine != null);
    }

    public function canEditQuest() : bool {
        return $this->isQuestHost() || Session::isAdmin();
    }

    public function isQuestHost() : bool {
        if (Session::isLoggedIn())
        {
            return (Session::getCurrentAccount()->crand == $this->host1->crand || ($this->host2 != null && Session::getCurrentAccount()->crand == $this->host2->crand));
        }
        else{
            return false;
        }
    }

    public function hasContent() : bool {
        return ($this->content != null);
    }
}



?>