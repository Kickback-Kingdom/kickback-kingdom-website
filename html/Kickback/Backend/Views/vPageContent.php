<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

use Kickback\Backend\Views\vRecordId;

/**
* @phpstan-type vPageContent_data_a array<
*     array{
*         content_id:        int,
*         content_detail_id: int,
*         content_type_name: string,
*         element_order:     int,
*         content_type:      int,
*         data_items: array<array{
*             content_detail_data_id: int,
*             data:                   ?string,
*             data_order:             int,
*             image_path:             ?string,
*             media_id:               ?int
*         }>
*     }>
*/
class vPageContent extends vRecordId 
{
    /**
    * @var vPageContent_data_a
    */
    public array $data;

    public string $containerType;
    public string $containerId;
    
    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }
}

?>
