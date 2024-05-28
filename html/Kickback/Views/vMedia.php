<?php 
declare(strict_types=1);

namespace Kickback\Views;
use Kickback\Views\vDateTime;

class vMedia extends vRecordId
{
    public string $name;
    public string $desc;
    public vRecordId $authorId;
    public vDateTime $dateCreated;
    public string $extension;
    public string $directory;
    private string $mediaPath;
    public string $url;

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
        $this->url = $fullPath;
        $this->mediaPath = str_replace("/assets/media/", '', $fullPath);

    }

    public function setMediaPath(string $path)
    {
        $this->mediaPath = $path;
        $this->url = $this->getFullPath();
    }
    
}

?>