<?php

declare(strict_types = 1);

namespace Kickback\Backend\Models;

use Exception;
use InvalidArgumentException;

class Product extends RecordId
{
    public string $name;
    public string $description;
    public string $locator;
    public string $ref_store_ctime;
    public int $ref_store_crand;
    public string $ref_item_ctime;
    public int $ref_item_crand;

    public array $prices;

    public function __construct(
        string $name,
        string $description,
        string $locator,
        string $ref_store_ctime,
        int $ref_store_crand,
        string $ref_item_ctime,
        int $ref_item_crand,
        array $prices
    )
    {
        parent::__construct();

        $this->name = $name;
        $this->description = $description;
        $this->locator = $locator;
        $this->ref_store_ctime = $ref_store_ctime;
        $this->ref_store_crand = $ref_store_crand;
        $this->ref_item_ctime = $ref_item_ctime;
        $this->ref_item_crand = $ref_item_crand;

        $this->prices = static::validatePrices($prices) ? $prices : throw new InvalidArgumentException("Prices Array must contain only prices");
    }

    private static function validatePrices(array $prices) : bool
    {
        foreach($prices as $price)
        {
            if($price->get_class() != Price::class)
            {
                return false;
            }
        }

        return true;
    }
}


?>