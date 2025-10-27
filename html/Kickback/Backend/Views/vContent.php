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
        $this->htmlContent = '';
        parent::__construct($ctime, $crand);
    }

    /**
    * @phpstan-assert-if-true  vPageContent=  $pageContent_
    */
    public function isValid() : bool {
        return ($this->pageContent_ != null && count($this->pageContent_->data) > 0);
    }

    /**
    * @phpstan-assert-if-true  vPageContent=  $pageContent_
    */
    public function hasPageContent() : bool {
        return $this->crand > -1;
    }

    #[KickbackGetter]
    #[KickbackSetter]
    public function pageContent(vPageContent ...$newValue) : ?vPageContent
    {
        if ( 1 !== \count($newValue) ) {
            return $this->pageContent_;
        }
        $this->pageContent_ = $newValue[0];
        return $this->pageContent_;
    }

    public function populateContent(string $container_type,string $container_id) : void
    {
        $contentResp = ContentController::queryContentDataByIdAsResponse($this, $container_type, $container_id);
        if ($contentResp->success)
        {
            $convSuccess = ContentController::convertContentDataResponseInto($contentResp, $this->pageContent_);
            assert($convSuccess);
        }
        else{
            throw new \Exception($contentResp->message);
        }
    }

}



?>
