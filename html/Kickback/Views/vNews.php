<?php 
declare(strict_types=1);

namespace Kickback\Views;

use Kickback\Views\vRecordId;
use Kickback\Views\vMedia;
use Kickback\Views\vAccount;
use Kickback\Views\vQuest;
use Kickback\Views\vBlogPost;
use DateTime;

class vNews
{
    public string $type;
    public ?vQuest $quest = null;
    public ?vBlogPost $blogPost = null;
}

?>