<?php 
declare(strict_types=1);

namespace Kickback\Views;

use Kickback\Views\vRecordId;
use Kickback\Views\vMedia;
use Kickback\Views\vAccount;
use Kickback\Views\vQuest;
use Kickback\Views\vQuestLine;
use Kickback\Views\vBlogPost;

class vFeedRecord
{
    public string $type;
    public ?vQuest $quest = null;
    public ?vQuestLine $questLine = null;
    public ?vBlogPost $blogPost = null;
    public ?vBlog $blog = null;

}

?>