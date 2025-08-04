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
        $this->name         = '';
        $this->desc         = '';
        $this->extension    = '';
        $this->directory    = '';
        $this->mediaPath    = '';
        $this->url          = '';
        parent::__construct($ctime, $crand);

        if ($crand == 221)
        {
            $this->_valid = false;
        }
    }

    public function getFullPath() : string
    {
        return "/assets/media/".$this->mediaPath;
    }

    public function setFullPath(string $fullPath) : void
    {
        $this->url = $fullPath;
        $this->mediaPath = str_replace("/assets/media/", '', $fullPath);
    }

    public function setMediaPath(string $path) : void
    {
        $this->mediaPath = $path;
        $this->url = $this->getFullPath();
    }
    
    public static function fromUrl(string $url): vMedia {
        $media = new vMedia();
        $media->setFullPath($url);
        return $media;
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

    public static function isValidRecordId(vRecordId $media) : bool {
        if ($media->crand == 221)
            return false;
        if ($media->crand < 0)
            return false;
        
        return true;
    }
}

?>
