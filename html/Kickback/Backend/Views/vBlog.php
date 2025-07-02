<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

use Kickback\Backend\Views\vMedia;
use Kickback\Backend\Views\vItem;
use Kickback\Services\Session;
use Kickback\Backend\Controllers\BlogController;
use Kickback\Common\Version;

class vBlog extends vRecordId
{
    public string $title;
    public string $locator;
    public string $description;
    public ?vItem $managerItem = null;
    public ?vItem $writerItem = null;
    public ?vAccount $lastWriter = null;
    public ?vDateTime $lastPostDate = null;
    
    public vMedia $icon;

    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }

    public function url() : string {
        return Version::formatUrl('/blog/'.$this->locator);
    }

    public function isManager() : bool {
        if (Session::isLoggedIn() && !is_null(Session::getCurrentAccount()))
        {
            return BlogController::accountIsManager(Session::getCurrentAccount(), $this);
        }
        else
        {
            return false;
        }
    }

    public function isWriter() : bool {
        if (Session::isLoggedIn() && !is_null(Session::getCurrentAccount()))
        {
            return BlogController::accountIsWriter(Session::getCurrentAccount(), $this);
        }
        else
        {
            return false;
        }
    }
}



?>
