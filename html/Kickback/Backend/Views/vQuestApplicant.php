<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vRecordId;

class vQuestApplicant extends vRecordId
{
    public vAccount $account;
    public int $seed;
    public int $rank;
    public string $displayName;

    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }
}



?>