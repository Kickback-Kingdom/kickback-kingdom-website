<?php

declare(strict_types = 1);

namespace Kickback\Backend\Views;

class vStore extends vRecordId
{
    public ?string $name;
    public ?string $locator;
    public ?string $description;
    public ?string $ownerUsername;
    public ?vAccount $owner;

    public function __construct(string $ctime = '', int $crand = 0)
    {
        parent::__construct($ctime, $crand);
    }
}

?>