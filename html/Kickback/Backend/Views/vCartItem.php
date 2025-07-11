<?php

declare(strict_types = 1);

namespace Kickback\Backend\Views;

class vCartItem extends vRecordId
{
    public bool $removed;
    public bool $checkedOut;
    public vProduct $product;
    public vPrice $price;
}

?>