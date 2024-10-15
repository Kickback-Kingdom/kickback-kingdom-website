<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

use Kickback\Backend\Controllers\BlogController;
use Kickback\Backend\Controllers\FeedController;
use Kickback\Common\Version;

if (isset($_GET['locator'])){
        
    $locator = $_GET['locator'];
    $blogResp = BlogController::getBlogByLocator($locator);
    $thisBlog = $blogResp->data;

    
    $blogPostsResp = FeedController::getBlogFeed($locator);

    $blogPosts = $blogPostsResp->data;
}
else{
    echo "no locator!";
}

$isBlogManager = $thisBlog->isManager();
$isBlogWriter = $thisBlog->isWriter();

?>

<!DOCTYPE html>
<html lang="en">


<?php require("php-components/base-page-head.php"); ?>

<body class="bg-body-secondary container p-0">
    
    <?php 
    
    require("php-components/base-page-components.php"); 
    
    require("php-components/ad-carousel.php"); 
    
    ?>

    

    <!--MAIN CONTENT-->
    <main class="container pt-3 bg-body" style="margin-bottom: 56px;">
        <div class="row">
            <div class="col-12 col-xl-9">
                
                
                <?php 
                
                
                $activePageName = $thisBlog->title;
                require("php-components/base-page-breadcrumbs.php"); 
if ($isBlogWriter) {                
                ?>
                <div class="row">
                    <div class="col-12">
                        <div class="card mb-3">
    
                            <div class="card-header bg-ranked-1">
                                <h5 class="mb-0">Welcome back, Scribe <?php echo Kickback\Services\Session::getCurrentAccount()->username; ?>. What would you like to do?</h5>
                            </div>
                            <div class="card-body">
                                
                                <a href="<?php echo Version::urlBetaPrefix(); ?>/blogpost.php?blogLocator=<?php echo $_GET['locator']; ?>&new" class="btn btn-primary">Write a New Post</a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php

}

                for ($i=0; $i < count($blogPosts); $i++) 
                { 
                    $showBlogPost = true;
                    $blogPost = $blogPosts[$i];
                    if ($blogPost["published"] == FALSE)
                    {
                        $showBlogPost = false;
                        if ($isBlogManager)
                        {
                            $showBlogPost = true;
                        }
                        else
                        {
                            if ($isBlogWriter)
                            {
                                if ($blogPost["account_1_id"] == Kickback\Services\Session::getCurrentAccount()->crand)
                                {
                                    $showBlogPost = true;
                                }
                            }
                        }
                    }

                    if ($showBlogPost)
                    {

                        $feedCard = $blogPost;
                        require ("php-components/feed-card.php");
                    }
                ?>
                
                <?php
                }
                ?>

            </div>
            
            <?php require("php-components/base-page-discord.php"); ?>
        </div>
        <?php require("php-components/base-page-footer.php"); ?>
    </main>

    
    <?php require("php-components/base-page-javascript.php"); ?>

</body>

</html>
