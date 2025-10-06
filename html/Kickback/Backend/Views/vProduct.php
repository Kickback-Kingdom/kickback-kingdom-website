<?php

declare(strict_types = 1);

namespace Kickback\Backend\Views;

class vProduct extends vRecordId
{
    public string $name;
    public string $description;

    public vAccount $owner;
    public vStore $store;

    public ?vMedia $mediaSmall;
    public ?vMedia $mediaLarge;
    public ?vMedia $mediaBack;

    public array $prices;

    public int $stock;
    public int $amountAvailable; //stock - amount_reserved

    public ?string $locator;

    public bool $removed;

    public function __construct(
        string $ctime = '',
        int $crand = 0,
        string $name = '',
        string $description = '',
        ?string $locator = null,
        int $stock = -1,
        int $amountAvailable = -1,
        $removed = false,
        ?vAccount $owner = null,
        ?vStore $store = null,
        ?vMedia $mediaSmall = null,
        ?vMedia $mediaLarge = null,
        ?vMedia $mediaBack = null,    
    )
    {
        parent::__construct($ctime, $crand);

        $this->name = $name;
        $this->description = $description;

        $this->locator = $locator;
        $this->stock = $stock;
        $this->amountAvailable = $amountAvailable;
        $this->removed = $removed;

        $this->store = $store ?? new vStore();

        $this->store->owner = $owner ??  new vAccount();

        $this->mediaSmall = $mediaSmall;
        $this->mediaLarge = $mediaLarge;
        $this->mediaBack = $mediaBack;

    }
}

?>