<?php
declare(strict_types=1);

namespace Kickback\Views;

use Kickback\Controllers\ContentController;
use Kickback\Views\vRecordId;
use Kickback\Views\vPageContent;

class vContent extends vRecordId
{
    public string $htmlContent;
    public ?vPageContent $pageContent = null;

    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }

    public function isValid() : bool {
        return ($this->pageContent != null && count($this->pageContent->data) > 0);
    }

    public function hasPageContent() : bool {
        return $this->crand > -1;
    }

    public function populateContent(string $container_type,string $container_id) : void {
        $contentResp = ContentController::getContentDataById($this,$container_type,$container_id);
        if ($contentResp->success)
        {
            $this->pageContent = $contentResp->data;
        }
        else{
            throw new \Exception($contentResp->message);
        }
    }

}



?>