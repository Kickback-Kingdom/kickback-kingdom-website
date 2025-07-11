<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

class vPageResult
{
    public int $totalItems;

    /** @var array<vRecordId> */
    public array $items;

    public int $totalPages;
    public int $currentPage;

    /**
    * @param array<vRecordId> $items
    */
    public function __construct(int $totalItems, array $items, int $itemsPerPage, int $currentPage)
    {
        $this->totalItems = $totalItems;
        $this->items = $items;
        $this->currentPage = $currentPage;
        $this->totalPages = (int)ceil($totalItems / $itemsPerPage);
    }
}

?>
