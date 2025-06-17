<?php
declare(strict_types=1);

namespace Kickback\Backend\Models;

class Quest extends RecordId
{
    public string $title;
    public string $locator;
    public vRecordId $host;
}



?>