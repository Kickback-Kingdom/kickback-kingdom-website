<?php 
declare(strict_types=1);

namespace Kickback\Backend\Views;
use Kickback\Backend\Views\vDateTime;

class vMedia extends vRecordId
{
    public string $name;
    public string $desc;
    public vAccount $author;
    public vDateTime $dateCreated;
    public string $extension;
    public string $directory;
    private string $mediaPath;
    public string $url;
    private bool $_valid = true;

    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);

        if ($crand == 221)
        {
            $this->_valid = false;
        }
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
    
    public static function defaultIcon() : vMedia {
        $media = new vMedia('',221);
        $media->setMediaPath('items/221.png');
        $media->_valid = false;
        return $media;
    }
    public static function defaultBanner() : vMedia {
        $media = new vMedia('',221);
        $media->setMediaPath('items/221.png');
        $media->_valid = false;
        return $media;
    }
    public static function defaultBannerMobile() : vMedia {
        $media = new vMedia('',221);
        $media->setMediaPath('items/221.png');
        $media->_valid = false;
        return $media;
    }

    public function isValid() : bool {
        return $this->_valid;
    }
}

?>