<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

class vPageResult
{
    public int $totalItems;
    public array $items;
    public int $totalPages;
    public int $currentPage;

    public function __construct(int $totalItems, array $items, int $itemsPerPage, int $currentPage)
    {
        $this->totalItems = $totalItems;
        $this->items = $items;
        $this->currentPage = $currentPage;
        $this->totalPages = (int)ceil($totalItems / $itemsPerPage);
    }
}

?>