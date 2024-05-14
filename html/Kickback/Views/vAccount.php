<?php
declare(strict_types=1);

namespace Kickback\Views;

use Kickback\Views\vMedia;

class vAccount extends vRecordId
{
    
    public vMedia $avatar;
    public vMedia $playerCardBorder;
    public vMedia $banner;
    public vMedia $background;
    public vMedia $charm;
    public vMedia $companion;
    
    
    function __construct(int $crand, string $ctime = '')
    {
        parent::__construct($crand, $ctime);
    }

    public function GetURL()
    {
        return "/u/".$this->name;
    }
}



?>