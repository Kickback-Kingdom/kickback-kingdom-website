<?php

declare(strict_types = 1);

namespace Kickback\Backend\Views;

class vProduct extends vRecordId
{
    public vAccount $owner;
    public vStore $store;
    public vItem $item;

    public string $locator;

    public function __construct(
        string $ctime = '',
        int $crand = 0,
        ?string $locator = null,
        ?vItem $item = null,
        ?vAccount $owner = null,
        ?vStore $store = null
    )
    {
        parent::__construct($ctime, $crand);

        $this->locator = $locator;

        $this->item = $item ?? new vItem();

        $this->store = $store ?? new vStore();

        $this->store->owner = $owner ??  new vAccount();

    }
}

?>