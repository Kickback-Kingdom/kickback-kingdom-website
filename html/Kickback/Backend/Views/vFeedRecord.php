<?php 
declare(strict_types=1);

namespace Kickback\Backend\Views;

use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vMedia;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vQuest;
use Kickback\Backend\Views\vQuestLine;
use Kickback\Backend\Views\vBlogPost;

class vFeedRecord
{
    public string $type;
    public ?vQuest $quest = null;
    public ?vQuestLine $questLine = null;
    public ?vBlogPost $blogPost = null;
    public ?vBlog $blog = null;

}

?>