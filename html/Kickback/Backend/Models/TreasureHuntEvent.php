<?php
declare(strict_types=1);

namespace Kickback\Backend\Models;

class TreasureHuntEvent extends RecordId
{
    public string $name;
    public string $desc;
    public string $startDate;
    public string $endDate;

    function __construct(string $name, string $desc, string $startDate, string $endDate)
    {
        parent::__construct();
        $this->name = $name;
        $this->desc = $desc;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }
}
?>
