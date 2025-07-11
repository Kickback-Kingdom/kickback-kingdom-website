<?php

declare(strict_types = 1);

namespace Kickback\Backend\Views;

class vTransaction extends vRecordId
{
    public ?string $description;
    public bool $complete;
    public bool $void;
    public array $prices;

    public function __construct(string $ctime = '', int $crand ='', ?string $description = null, bool $complete = 0, bool $void = 0, string $pricesJson = [])
    {
        parent::__construct($ctime, $crand);

        $this->description = $description;
        $this->complete = $complete;
        $this->void = $void;
        $this->prices = json_decode($pricesJson);
    }

    private function pricesJsonToObjectArray(string $pricesJson)
    {
        $array = json_decode($pricesJson);

        $prices = new vPrice();
    }
}

?>