<?php

declare(strict_types =1);

namespace Kickback\Backend\Models;

use Kickback\Backend\Views\vRecordId;

class Store extends recordId
{
    public string $name;
    public string $locator;
    public string $description;
    public vRecordId $accountId;

    function __construct(
        string $name,
        string $locator,
        string $description,
        vRecordId $accountId
    )
    {
        parent::__construct();

        $this->name = $name;
        $this->locator = $locator;
        $this->description = $description;
        $this->accountId = $accountId;
    }
}

?>