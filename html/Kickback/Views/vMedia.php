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

    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);
    }

    public function getFullPath()
    {
        return "/assets/media/".$this->mediaPath;
    }

    public function setFullPath(string $fullPath)
    {
        $this->mediaPath = str_replace("/assets/media/", '', $fullPath);

    }
    
}

?>