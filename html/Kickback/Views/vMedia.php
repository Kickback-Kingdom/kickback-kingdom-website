<?php 
declare(strict_types=1);

namespace Kickback\Views;

class vMedia extends vRecordId
{
    public string $name;
    public string $desc;
    public vRecordId $authorId;
    public string $dateCreated;
    public string $extension;
    public string $directory;
    public string $mediaPath;

    function __construct(int $crand, string $ctime = '')
    {
        parent::__construct($crand, $ctime);
    }

    public function GetFullPath()
    {
        return "/assets/media/".$this->mediaPath;
    }
    
}

?>