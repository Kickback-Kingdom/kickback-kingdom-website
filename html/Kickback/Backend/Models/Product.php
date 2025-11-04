<?php

declare(strict_types = 1);

namespace Kickback\Backend\Models;

use Exception;
use InvalidArgumentException;
use Kickback\Backend\Views\vMedia;
use Kickback\Backend\Views\vPrice;
use Kickback\Backend\Views\vStore;

class Product extends RecordId
{
    public ?string $name;
    public ?string $description;
    public ?string $locator;
    public ?vStore $store;

    public ?vMedia $largeMedia;
    public ?vMedia $smallMedia;
    public ?vMedia $backMedia;

    public bool $removed;

    public array $prices;

    public string $tag;
    public array $categories;

    public function __construct(
        string $name = '',
        string $description = '',
        bool $removed = false,
        string $locator = '',
        string $tag = '',
        array $categories = [],
        ?vStore $store = null,
        array $prices = [],
        ?vMedia $largeMedia = null,
        ?vMedia $smallMedia = null,
        ?vMedia $backMedia = null,
    )
    {
        parent::__construct();

        $this->name = $name;
        $this->description = $description;
        $this->locator = $locator;
        $this->store = $store;

        $this->tags = $this->validateStringArray("tag", $tags);
        $this->categories = $this->validateStringArray("categories", $categories);

        $this->removed = $removed;

        $this->largeMedia = $largeMedia;
        $this->smallMedia = $smallMedia;
        $this->backMedia = $backMedia;

        $this->prices = static::validatePrices($prices) ? $prices : throw new InvalidArgumentException("Prices Array must contain only prices");
    }

    private static function validateStringArray(string $fieldName, array $stringArray) : array
    {
        foreach($stringArray as $object)
        {
            if(!is_string($object)) throw new InvalidArgumentException("$fieldName must only contain strings");
        }

        return $stringArray;
    }

    private static function validatePrices(array $prices) : bool
    {
        foreach($prices as $price)
        {
            if(!$price instanceof vPrice)
            {
                return false;
            }
        }

        return true;
    }
}


?>