<?php
declare(strict_types=1);

namespace Kickback\Views;


class vContent extends vRecordId
{

    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }

}



?>