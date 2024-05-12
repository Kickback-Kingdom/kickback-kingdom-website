<?php 
declare(strict_types=1);

namespace Kickback\Views;

class vMedia
{
    public int   $id;
    public string $name;
    public string $desc;
    public int $author_id;
    public string $dateCreated;
    public string $extension;
    public string $directory;
    public string $mediaPath;


    public function GetFullPath()
    {
        return "/assets/media/".$this->mediaPath;
    }
    
}

?>