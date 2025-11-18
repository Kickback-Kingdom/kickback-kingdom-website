<?php

declare(strict_types = 1);

namespace Kickback\Backend\Views;

class vTransaction extends vRecordId
{
    public ?string $description;
    public bool $complete;
    public bool $void;
    public array $price;

    public function __construct(
        string $ctime = '', 
        int $crand = -1, 
        ?string $description = null, 
        bool $complete = false, 
        bool $void = false, 
        array $price = [])
    {
        parent::__construct($ctime, $crand);

        $this->description = $description;
        $this->complete = $complete;
        $this->void = $void;
        $this->price = $price;
    }

    private function priceJsonToObjectArray(string $priceJson)
    {
        $array = json_decode($priceJson);

        $price = new vPriceComponent();
    }
}

?>