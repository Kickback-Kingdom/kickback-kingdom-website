<?php
declare(strict_types=1);

namespace Kickback\Backend\Models;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views;
use \Datetime;

class Store extends RecordId
{
    public string $name;
    public string $locator;
    public string $description;
    public ForeignRecordId $ownerId;

    function __construct(string $name, string $description, string $locator, string $ref_account_ctime, int $ref_account_crand)
    {
        parent::__construct();
        $this->ownerId = new ForeignRecordId($ref_account_ctime, $ref_account_crand);
        $this->name = $name;
        $this->locator = $locator;
        $this->description = $description;
    }

}

?>