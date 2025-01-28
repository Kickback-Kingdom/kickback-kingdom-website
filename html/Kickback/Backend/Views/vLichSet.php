<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

use Kickback\Backend\Views\vMedia;
use Kickback\Backend\Models\ForeignRecordId;
use Kickback\Backend\Models\LichCard;
use Kickback\Backend\Views\vReviewStatus;
use Kickback\Services\Session;

class vLichSet extends vRecordId
{
    public string $name;
    public string $locator;
    public vContent $content;

    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }

    
    public function hasPageContent() : bool {
        return $this->content->hasPageContent();
    }

    public function getPageContent() : vPageContent {
        return $this->content->pageContent;
    }

    public function canEdit() : bool {
        return Session::isServantOfTheLich();
    }

    public function populateContent() : void {
        if ($this->hasPageContent())
        {
            $this->content->populateContent("LICH-SET",$this->locator);
        }
    }

    
    public function populateEverything() : void {
        $this->populateContent();
    }
}