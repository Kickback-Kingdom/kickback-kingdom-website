<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

use \Kickback\Common\Version;

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

use Kickback\Backend\Controllers\ContentController;
use Kickback\Backend\Controllers\BlogController;
use Kickback\Backend\Controllers\BlogPostController;
use Kickback\Backend\Controllers\FeedCardController;
use Kickback\Services\Session;

if (isset($_GET['blogLocator'])) {
    

        if (isset($_GET['postLocator']))
        {
        
            $blogPostResp = BlogPostController::getBlogPostByLocators($_GET['blogLocator'],$_GET['postLocator']);
        
            
        }
        elseif (isset($_GET["new"]))
        {
            $blog = BlogController::getBlogByLocator($_GET['blogLocator'])->data;
            $blogPostResp = BlogPostController::insertNewBlogPost($blog, $_GET['blogLocator']);
        }
        
        if ($blogPostResp->success)
        {
            $thisBlogPost = $blogPostResp->data;
            $thisBlogPost->populateContent();
            
            $blogResp = BlogController::getBlogByLocator($_GET['blogLocator']);
            $thisBlogPost->blog = $blogResp->data;
        }
    
}

if (!isset($thisBlogPost) || (isset($thisBlogPost) && $thisBlogPost == null))
{
    Session::Redirect("/blog/".$_GET['blogLocator']);
}

$isWriterForBlogPost = $thisBlogPost->isWriter();



if ($thisBlogPost->blogLocator == "Kickback-Kingdom")
{

    if ( array_key_exists($thisBlogPost->postLocator, Version::history_by_blogpost_locator()) ) {
        Version::$client_is_viewing_blogpost_for_current_version_update = true;
    }
}

?>

<!DOCTYPE html>
<html lang="en">


<?php require("php-components/base-page-head.php"); ?>

<body class="bg-body-secondary container p-0" id="page_top">
    
    <?php 
    
    require("php-components/base-page-components.php"); 
    
    require("php-components/ad-carousel.php"); 
    
    ?>

    

    <!--MAIN CONTENT-->
    <main class="container pt-3 bg-body">
        <div class="row">
            <div class="col-12 col-xl-9">
                
                
                <div class="card mb-3">
                    <?php if (!isset($_GET['borderless'])) { ?>
                    <div class="card-header bg-primary d-flex flex-wrap justify-content-between align-items-center">
                        <a class="btn bg-ranked-1" href="<?= $thisBlogPost->blog->getURL(); ?>"><i class="fa-solid fa-arrow-left"></i><i class="fa-solid fa-blog"></i></a>
                        <h5 class="text-center text-white"><?= $thisBlogPost->blog->title; ?></h5>
                        <a class="btn bg-ranked-1 float-end" href="#page_bottom"><i class="fa-solid fa-arrow-down"></i></a>
                    </div>
                    <?php } ?>
                    <div class="card-body">
                        <div class="row">
                
        
                            <div class="col-12  pb-3"><h1 class="text-center"><?= $thisBlogPost->blog->title;?></h1>
                                <p class="card-text text-center">
                                    <small class="text-body-secondary">Written by 
                                        <?= $thisBlogPost->author->getAccountElement();?> on 
                                        <?= $thisBlogPost->publishedDateTime->getDateTimeElement(); ?> and viewed <?= $thisPageVisits; ?> times
                                        
                                    </small>
                                </p>
                        
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($isWriterForBlogPost) { ?>
                <div class="row">
                    <div class="col-12">
                        <div class="card mb-3">
    
                            <div class="card-header bg-ranked-1">
                                <h5 class="mb-0">Welcome back, Scribe <?php echo Kickback\Services\Session::getCurrentAccount()->username; ?>. What would you like to do?</h5>
                            </div>
                            <div class="card-body">
                                <button type="button" class="btn btn-primary" onclick="OpenModalEditBlogPostOptions()">Edit Blog Post Details</button>
                                <?php if ($thisBlogPost->reviewStatus->isDraft()) { ?><button type="button" class="btn btn-primary" onclick="OpenModalPublishBlogPost()">Publish Blog Post</button><?php } ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal modal-xl fade" id="modalPublishBlogPost" tabindex="-1" aria-labelledby="modalPublishBlogPostLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                        <div class="modal-header">
                            <h1 class="modal-title fs-5">Publish Blog Post</h1>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row mb-3">
                                <div class="col-12">
                                    <p>Please make sure you save all of your changes before publishing your blog post! Below are a list of items that need to be met before publishing a blog post on Kickback Kingdom: </p>
                                </div>
                                
                            </div>
                            <div class="row mb-3">
                                <div class="col-lg-12 col-xl-9">
                                    <?php 
                                        $_vFeedCard = FeedCardController::vBlogPost_to_vFeedCard($thisBlogPost);
                                        require("php-components/vFeedCardRenderer.php");
                                    ?>
                                </div>
                                <div class="col-lg-12 col-xl-3">
                                    <?php if ($thisBlogPost->titleIsValid()) { ?>
                                        <p class="text-success"><i class="fa-solid fa-square-check"></i> Valid Blog Post Title</p>
                                    <?php } else { ?>
                                        <p class="text-danger"><i class="fa-solid fa-square-xmark"></i> Title is too short or invalid</p>
                                    <?php } ?>

                                    <?php if ($thisBlogPost->summaryIsValid()) { ?>
                                        <p class="text-success"><i class="fa-solid fa-square-check"></i> Valid Blog Post Summary</p>
                                    <?php } else { ?>
                                        <p class="text-danger"><i class="fa-solid fa-square-xmark"></i> Summary is too short</p>
                                    <?php } ?>

                                    <?php if ($thisBlogPost->pageContentIsValid()) { ?>
                                        <p class="text-success"><i class="fa-solid fa-square-check"></i> Valid Blog Post content</p>
                                    <?php } else { ?>
                                        <p class="text-danger"><i class="fa-solid fa-square-xmark"></i> Content is too short</p>
                                    <?php } ?>

                                    <?php if($thisBlogPost->locatorIsValid()) { ?>
                                        <p class="text-success"><i class="fa-solid fa-square-check"></i> Valid URL Locator</p>
                                    <?php } else { ?>
                                        <p class="text-danger"><i class="fa-solid fa-square-xmark"></i> Please use a valid url locator</p>
                                    <?php } ?>

                                    <?php if($thisBlogPost->iconIsValid()) { ?>
                                        <p class="text-success"><i class="fa-solid fa-square-check"></i> Valid Blog Post Icon</p>
                                    <?php } else { ?>
                                        <p class="text-danger"><i class="fa-solid fa-square-xmark"></i> Please select a Blog Post Icon</p>
                                    <?php } ?>
                                </div>
                            </div>
                            <div class="row mb-3">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <form method="POST">
                                <input type="hidden" name="form_token" value="<?php echo $_SESSION['form_token']; ?>">
                                <input type="hidden" name="blog-post-id" value="<?= $thisBlogPost->crand; ?>" />
                                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                                <input type="submit" name="submit-blog-post-publish" class="btn bg-ranked-1" onclick="" <?php if(!$thisBlogPost->isValidForPublish()) { ?>disabled<?php } ?> value="Publish Blog Post" />
                            </form>
                        </div>
                        </div>
                    </div>
                </div>

                <form method="POST">
                    <input type="hidden" name="form_token" value="<?php echo $_SESSION['form_token']; ?>">
                    <input type="hidden" value="<?=  $thisBlogPost->crand; ?>" name="blogPostId" />
                    <div class="modal modal-lg fade" id="modalEditBlogPostOptions" tabindex="-1" aria-labelledby="modalEditBlogPostOptionsLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h1 class="modal-title fs-5">Edit Blog Post Options</h1>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row mb-3">
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="blogPostOptionsTitle" class="form-label">Title:</label>
                                                <input type="text" class="form-control" id="blogPostOptionsTitle" name="blogPostOptionsTitle" value="<?= $thisBlogPost->title; ?>">
                                            </div>
                                        </div>
                                        
                                    </div>
                                    <div class="row mb-3">
                                        <label for="blogPostOptionsLocator" class="form-label">URL</label>
                                        <div class="input-group">
                                            <span class="input-group-text">/blog/<?= $thisBlogPost->blogLocator; ?>/</span>
                                            <input type="text" class="form-control" id="blogPostOptionsLocator" name="blogPostOptionsLocator" value="<?= $thisBlogPost->postLocator; ?>">
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="blogPostOptionsDesc" class="form-label">Summary:</label>
                                                <textarea class="form-control" rows="5" id="blogPostOptionsDesc" name="blogPostOptionsDesc"><?= $thisBlogPost->summary; ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-12">
                                            
                                            <h3 class="display-6">Post Icon<button type="button" class="btn btn-primary float-end" onclick="OpenSelectMediaModal('modalEditBlogPostOptions','blogPostOptionsIcon','blogPostOptionsIconFormInput');">Select Media</button></h3>
                                            <div class="col-md-6" >

                                                <input type="hidden" value="<?= $thisBlogPost->icon->crand;?>" id="blogPostOptionsIconFormInput" name="blogPostOptionsIcon" />
                                                <img class="img-thumbnail" src="<?= $thisBlogPost->icon->getFullPath(); ?>" id="blogPostOptionsIcon" />

                                            </div>
                                        </div>      
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                                    <input type="submit" class="btn bg-ranked-1" onclick="" value="Apply changes" name="submitBlogOptions">
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <script>
                    
                    function OpenModalEditBlogPostOptions()
                    {
                        $("#modalEditBlogPostOptions").modal("show");
                    }
                    function OpenModalPublishBlogPost()
                    {
                        $("#modalPublishBlogPost").modal("show");
                    }
                </script>

                <?php } ?>
                <div class="row">
                    <div class="col-12">
                        <nav>
                            <div class="nav nav-tabs" id="nav-tab" role="tablist">
                                <button class="nav-link active" id="nav-content-tab" data-bs-toggle="tab" data-bs-target="#nav-content" type="button" role="tab" aria-controls="nav-content" aria-selected="true"><i class="fa-solid fa-newspaper"></i></button>
                                <!--<button class="nav-link" id="nav-comments-tab" data-bs-toggle="tab" data-bs-target="#nav-comments" type="button" role="tab" aria-controls="nav-comments" aria-selected="true"><i class="fa-regular fa-comments"></i></button>-->
                                
                            </div>
                        </nav>
                        <div class="tab-content" id="nav-tabContent">
                            <div class="tab-pane fade active show" id="nav-content" role="tabpanel" aria-labelledby="nav-content-tab" tabindex="0">
                                
                                <?php 

                                    $_vCanEditContent = $isWriterForBlogPost;
                                    $_vContentViewerEditorTitle = "Blog Post Content Manager";
                                    $_vPageContent = $thisBlogPost->pageContent();
                                    require("php-components/content-viewer.php"); 
                                    /*$_vCanEditContent = $thisQuest->canEdit();
                                    $_vContentViewerEditorTitle = "Quest Information Manager";
                                    $_vPageContent = $thisQuest->pageContent();
                                    require("php-components/content-viewer.php");*/

                                ?>
                            </div>
                            <div class="tab-pane fade" id="nav-comments" role="tabpanel" aria-labelledby="nav-comments-tab" tabindex="0">
                                
                                <?php require("php-components/coming-soon.php"); ?>
                            </div>
                            
                        </div>
                    </div>
                </div>
                
                <?php if (!isset($_GET['borderless'])) { ?>
                <div class="card mb-3 mt-3">
                    <div class="card-header bg-primary d-flex flex-wrap justify-content-between align-items-center">
                        <a class="btn bg-ranked-1" href="#"><i class="fa-solid fa-arrow-left"></i><i class="fa-solid fa-blog"></i></a>
                        <h5 class="text-center text-white">Blog Navigation</h5>
                        <a class="btn bg-ranked-1 float-end" href="#page_top"><i class="fa-solid fa-arrow-up"></i></a>
                    </div>
                    <?php if (($thisBlogPost->nextBlogPost != null) || ($thisBlogPost->prevBlogPost != null)) { ?>
                    <div class="card-body">
                        <div class="row">
                                    
                            <div class="col-6">
                                
                                <?php if ($thisBlogPost->prevBlogPost != null) { ?>
                                <div class="card" >
                                    <img src="<?= $thisBlogPost->prevBlogPost->icon->getFullPath();?>" class="card-img-top" >
                                    <div class="card-body">
                                        <h5 class="card-title"><?= $thisBlogPost->prevBlogPost->title;?></h5>
                                        <small class="text-body-secondary">Written by 
                                            <?= $thisBlogPost->prevBlogPost->author->getAccountElement(); ?> on 
                                            <span class="date" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="<?= $thisBlogPost->prevBlogPost->publishedDateTime->formattedDetailed; ?> UTC"><?=  $thisBlogPost->prevBlogPost->publishedDateTime->formattedBasic; ?></span>
                                        </small>
                                    </div>
                                    <div class="card-footer">
                                        <a href="<?= $thisBlogPost->prevBlogPost->postLocator; ?>" class="btn btn-primary"><i class="fa-solid fa-angles-left"></i> Previous Post</a>
                                    </div>
                                </div>
                                <?php } ?>
                            </div>
                            <div class="col-6">
                                
                                <?php if ($thisBlogPost->nextBlogPost != null) { ?>
                                <div class="card float-end" >
                                    
                                    <img src="<?= $thisBlogPost->nextBlogPost->icon->getFullPath();?>" class="card-img-top">
                                    <div class="card-body">
                                        <h5 class="card-title"><?= $thisBlogPost->nextBlogPost->title;?></h5>
                                        <small class="text-body-secondary">Written by 
                                        <?= $thisBlogPost->nextBlogPost->author->getAccountElement(); ?> on 
                                            <span class="date" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="<?= $thisBlogPost->nextBlogPost->publishedDateTime->formattedDetailed; ?> UTC"><?= $thisBlogPost->nextBlogPost->publishedDateTime->formattedBasic; ?></span>
                                        </small>
                                    </div>
                                    <div class="card-footer">
                                        <a href="<?= $thisBlogPost->nextBlogPost->postLocator; ?>" class="btn btn-primary float-end">Next Post <i class="fa-solid fa-angles-right"></i></a>
                                    </div>
                                </div>
                                <?php } ?>
                            </div>
                            
                            
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <?php } ?>
            </div>
            
            <?php require("php-components/base-page-discord.php"); ?>
        </div>
        <div id="page_bottom">
        </div>
        <?php require("php-components/base-page-footer.php"); ?>
    </main>

    
    <?php require("php-components/base-page-javascript.php"); ?>
    <?php require("php-components/content-viewer-javascript.php"); ?>

</body>

</html>
