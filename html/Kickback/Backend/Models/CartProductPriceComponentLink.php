<?php

declare(strict_types = 1);

namespace Kickback\Backend\Models;

use Kickback\Backend\Views\vRecordId;

class CartProductPriceComponentLink extends RecordId
{
    public bool $removed;
    public bool $checkedOut;

    public vRecordId $cartProductLinkId;
    public vRecordId $priceComponentId;

    public function __construct()
    {
        parent::__construct();

        $this->removed = false;
        $this->checkedOut = false;
    }
}

?>