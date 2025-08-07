<?php

declare(strict_types = 1);

namespace Kickback\Backend\Views;

class vCartProductLink extends vRecordId
{
    public bool $removed;
    public bool $checkedOut;

    public vProduct $product;
    public vCart $cart;

}

?>

