<?php

declare(strict_types = 1);

namespace Kickback\Backend\Views;

class vCartProductPriceLink extends vRecordId
{
    public bool $removed;
    public bool $checkedOut;

    public vRecordId $cartProductLinkId;
    public vRecordId $price;

    public function __construct($ctime = '', $crand = -1)
    {
        parent::__construct($ctime, $crand);

        $this->removed = false;
        $this->checkedOut = false;
    }
}

?>