<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

use Kickback\Common\Exceptions\UnexpectedNullException;

use Kickback\Backend\Controllers\ContentController;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vPageContent;

class vContent extends vRecordId
{
    public  string $htmlContent;
    private ?vPageContent $pageContent_ = null;

    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }

    public function isValid() : bool {
        return ($this->pageContent_ != null && count($this->pageContent_->data) > 0);
    }

    public function hasPageContent() : bool {
        return $this->crand > -1;
    }

    public function pageContent(?vPageContent $newValue = null) : vPageContent
    {
        if ( !is_null($newValue) ) {
            $this->pageContent_ = $newValue;
        }

        if ( is_null($this->pageContent_) ) {
            throw new UnexpectedNullException(
                'Attempt to access page content when there is none.');
        } else {
            return $this->pageContent_;
        }
    }

    public function populateContent(string $container_type,string $container_id) : void {
        $contentResp = ContentController::getContentDataById($this,$container_type,$container_id);
        if ($contentResp->success)
        {
            $this->pageContent_ = $contentResp->data;
        }
        else{
            throw new \Exception($contentResp->message);
        }
    }

}



?>
