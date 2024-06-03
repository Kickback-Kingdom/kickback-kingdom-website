<?php
declare(strict_types=1);

namespace Kickback\Views;


class vContent extends vRecordId
{
    public string $htmlContent;
    public ?array $pageContent = null;

    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }

    public function isValid() : bool {
        return ($this->pageContent != null && count($this->pageContent["data"]) > 0);
    }

}



?>