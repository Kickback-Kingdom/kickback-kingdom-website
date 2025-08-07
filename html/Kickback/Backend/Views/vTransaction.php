<?php

declare(strict_types = 1);

namespace Kickback\Backend\Views;

class vTransaction extends vRecordId
{
    public ?string $description;
    public bool $complete;
    public bool $void;
    public array $prices;

    public function __construct(
        string $ctime = '', 
        int $crand = -1, 
        ?string $description = null, 
        bool $complete = false, 
        bool $void = false, 
        array $prices = [])
    {
        parent::__construct($ctime, $crand);

        $this->description = $description;
        $this->complete = $complete;
        $this->void = $void;
        $this->prices = $prices;
    }

    private function pricesJsonToObjectArray(string $pricesJson)
    {
        $array = json_decode($pricesJson);

        $prices = new vPrice();
    }
}

?>