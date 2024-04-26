<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

$foundBlogPost = false;


if (isset($_GET['blogLocator'])) {
    
    $blogLocator = $_GET['blogLocator'];
    $blogResp = GetBlogByLocator($blogLocator);

    if ($blogResp->Success)
    {
        $blog = $blogResp->Data;

        if (isset($_GET['postLocator']))
        {
            $postLocator = $_GET['postLocator'];
        
            $blogPostResp = GetBlogPostByLocators($blogLocator,$postLocator);
        
            
        }
        elseif (isset($_GET["new"]))
        {
            $blogPostResp = InsertNewBlogPost($blog["Id"], $blogLocator);
        }
        
        if ($blogPostResp->Success)
        {
            $foundBlogPost = true;
            $blogPost = $blogPostResp->Data;
            $postLocator = $blogPost["Postlocator"];

            $contentResp = GetContentDataById($blogPost["Content_id"],"BLOG-POST", $blogLocator."/".$postLocator);

            $pageContent = $contentResp->Data;
        }
    }
    
}

$isWriterForBlogPost = IsWriterForBlogPost($blogPost);

$thisPostDate = date_create($blogPost["PostDate"]);           
$thisPostDateBasic = date_format($thisPostDate,"M j, Y");
$thisPostDateDetailed = date_format($thisPostDate,"M j, Y H:i:s");

$thisBlogPost = $blogPost;
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
    <main class="container pt-3 bg-body" style="margin-bottom: 56px;">
        <div class="row">
            <div class="col-12 col-xl-9">
                
                
                <div class="card mb-3">
                    <div class="card-header bg-primary d-flex flex-wrap justify-content-between align-items-center">
                        <a class="btn bg-ranked-1" href="<?php echo $urlPrefixBeta."/blog/".$blogPost["Bloglocator"]; ?>"><i class="fa-solid fa-arrow-left"></i><i class="fa-solid fa-blog"></i></a>
                        <h5 class="text-center text-white"><?php echo $blog["name"]; ?></h5>
                        <a class="btn bg-ranked-1 float-end" href="#page_bottom"><i class="fa-solid fa-arrow-down"></i></a>
                    </div>
                    <div class="card-body">
                        <div class="row">
                
        
                            <div class="col-12  pb-3"><h1 class="text-center"><?php echo $blogPost["Title"];?></h1>
                                <p class="card-text text-center">
                                    <small class="text-body-secondary">Written by 
                                        <a href="<?php echo $urlPrefixBeta; ?>/u/<?php echo $blogPost["Author_Username"]; ?>" class="username"><?php echo $blogPost["Author_Username"]; ?></a> on 
                                        <span class="date" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="<?php echo $thisPostDateDetailed; ?>"><?php echo $thisPostDateBasic; ?></span>
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
                                <h5 class="mb-0">Welcome back, Scribe <?php echo $_SESSION["account"]["Username"]; ?>. What would you like to do?</h5>
                            </div>
                            <div class="card-body">
                                <button type="button" class="btn btn-primary" onclick="OpenModalEditBlogPostOptions()">Edit Blog Post Details</button>
                                <button type="button" class="btn btn-primary" onclick="OpenModalPublishBlogPost()">Publish Blog Post</button>
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
                                        $feedCard = $blogPost;
                                        $feedCard["type"] = "BLOG-POST";
                                        $feedCard["title"] = $blogPost["Title"];
                                        $feedCard["image"] = $blogPost["Image_Path"];
                                        $feedCard["published"] = true;
                                        $feedCard["account_1_username"] = $blogPost["Author_Username"];
                                        $feedCard["text"] = $blogPost["Desc"];
                                        require("php-components/feed-card.php");
                                    ?>
                                </div>
                                <div class="col-lg-12 col-xl-3">
                                    <?php if(BlogPostTitleIsValid($blogPost["Title"])) { ?>
                                        <p class="text-success"><i class="fa-solid fa-square-check"></i> Valid Blog Post Title</p>
                                    <?php } else { ?>
                                        <p class="text-danger"><i class="fa-solid fa-square-xmark"></i> Title is too short or invalid</p>
                                    <?php } ?>

                                    <?php if(BlogPostSummaryIsValid($blogPost["Desc"])) { ?>
                                        <p class="text-success"><i class="fa-solid fa-square-check"></i> Valid Blog Post Summary</p>
                                    <?php } else { ?>
                                        <p class="text-danger"><i class="fa-solid fa-square-xmark"></i> Summary is too short</p>
                                    <?php } ?>

                                    <?php if(BlogPostPageContentIsValid($pageContent["data"])) { ?>
                                        <p class="text-success"><i class="fa-solid fa-square-check"></i> Valid Blog Post content</p>
                                    <?php } else { ?>
                                        <p class="text-danger"><i class="fa-solid fa-square-xmark"></i> Content is too short</p>
                                    <?php } ?>

                                    <?php if(BlogPostLocatorIsValid($blogPost["Postlocator"])) { ?>
                                        <p class="text-success"><i class="fa-solid fa-square-check"></i> Valid URL Locator</p>
                                    <?php } else { ?>
                                        <p class="text-danger"><i class="fa-solid fa-square-xmark"></i> Please use a valid url locator</p>
                                    <?php } ?>

                                    <?php if(BlogPostIconIsValid($blogPost["Image_Id"])) { ?>
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
                                <input type="hidden" name="blog-post-id" value="<?php echo $blogPost["Id"]; ?>" />
                                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                                <input type="submit" name="submit-blog-post-publish" class="btn bg-ranked-1" onclick="" <?php if(!BlogPostIsValidForPublish($blogPost,$pageContent)) { ?>disabled<?php } ?> value="Publish Blog Post" />
                            </form>
                        </div>
                        </div>
                    </div>
                </div>

                <form method="POST">
                    <input type="hidden" value="<?php echo $blogPost["Id"]; ?>" name="blogPostId" />
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
                                            <input type="text" class="form-control" id="blogPostOptionsTitle" name="blogPostOptionsTitle" value="<?php echo $blogPost["Title"]; ?>">
                                        </div>
                                    </div>
                                    
                                </div>
                                <div class="row mb-3">
                                    <label for="blogPostOptionsLocator" class="form-label">URL</label>
                                    <div class="input-group">
                                        <span class="input-group-text">/blog/<?php echo $blogLocator; ?>/</span>
                                        <input type="text" class="form-control" id="blogPostOptionsLocator" name="blogPostOptionsLocator" value="<?php echo $postLocator; ?>">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label for="blogPostOptionsDesc" class="form-label">Summary:</label>
                                            <textarea class="form-control" rows="5" id="blogPostOptionsDesc" name="blogPostOptionsDesc"><?php echo $blogPost["Desc"]; ?></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-12">
                                        
                                        <h3 class="display-6">Post Icon<button type="button" class="btn btn-primary float-end" onclick="OpenSelectMediaModal('modalEditBlogPostOptions','blogPostOptionsIcon','blogPostOptionsIconFormInput');">Select Media</button></h3>
                                        <div class="col-md-6" >

                                            <input type="hidden" value="<?php echo $blogPost["Image_Id"];?>" id="blogPostOptionsIconFormInput" name="blogPostOptionsIcon" />
                                            <img class="img-thumbnail" src="/assets/media/<?php echo $blogPost["Image_Path"]; ?>" id="blogPostOptionsIcon" />

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
                                <button class="nav-link" id="nav-comments-tab" data-bs-toggle="tab" data-bs-target="#nav-comments" type="button" role="tab" aria-controls="nav-comments" aria-selected="true"><i class="fa-regular fa-comments"></i></button>
                                
                            </div>
                        </nav>
                        <div class="tab-content" id="nav-tabContent">
                            <div class="tab-pane fade active show" id="nav-content" role="tabpanel" aria-labelledby="nav-content-tab" tabindex="0">
                                
                                <?php 

                                    $canEditContent = $isWriterForBlogPost;
                                    $contentViewerEditorTitle = "Blog Post Content Manager";
                                    require("php-components/content-viewer.php"); 

                                ?>
                            </div>
                            <div class="tab-pane fade" id="nav-comments" role="tabpanel" aria-labelledby="nav-comments-tab" tabindex="0">
                                
                                <?php require("php-components/coming-soon.php"); ?>
                            </div>
                            
                        </div>
                    </div>
                </div>
                
<div class="card mb-3 mt-3">
    <div class="card-header bg-primary d-flex flex-wrap justify-content-between align-items-center">
        <a class="btn bg-ranked-1" href="#"><i class="fa-solid fa-arrow-left"></i><i class="fa-solid fa-blog"></i></a>
        <h5 class="text-center text-white">Blog Navigation</h5>
        <a class="btn bg-ranked-1 float-end" href="#page_top"><i class="fa-solid fa-arrow-up"></i></a>
    </div>
            <?php if ($blogPost["Next_Locator"] != null || $blogPost["Prev_Locator"] != null) { ?>
    <div class="card-body">
        <div class="row">
                    
            <div class="col-6">
                
                <?php if ($blogPost["Prev_Locator"] != null) { 
$prevPostDate = date_create($blogPost["Prev_PostDate"]);           
$prevPostDateBasic = date_format($prevPostDate,"M j, Y");
$prevPostDateDetailed = date_format($prevPostDate,"M j, Y H:i:s");
?>
                <div class="card" >
                    <img src="/assets/media/<?php echo $blogPost["Prev_Image_Path"];?>" class="card-img-top" >
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $blogPost["Prev_Title"];?></h5>
                        <small class="text-body-secondary">Written by 
                            <a href="<?php echo $urlPrefixBeta; ?>/u/<?php echo $blogPost["Prev_Author"]; ?>" class="username"><?php echo $blogPost["Prev_Author"]; ?></a> on 
                            <span class="date" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="<?php echo $prevPostDateDetailed; ?> UTC"><?php echo $prevPostDateBasic; ?></span>
                        </small>
                    </div>
                    <div class="card-footer">
                        <a href="<?php echo $blogPost["Prev_Locator"]; ?>" class="btn btn-primary"><i class="fa-solid fa-angles-left"></i> Previous Post</a>
                    </div>
                </div>
                <?php } ?>
            </div>
            <div class="col-6">
                
                <?php if ($blogPost["Next_Locator"] != null) { 
                    
$nextPostDate = date_create($blogPost["Next_PostDate"]);           
$nextPostDateBasic = date_format($nextPostDate,"M j, Y");
$nextPostDateDetailed = date_format($nextPostDate,"M j, Y H:i:s");

                    ?>
                <div class="card float-end" >
                    
                    <img src="/assets/media/<?php echo $blogPost["Next_Image_Path"];?>" class="card-img-top">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $blogPost["Next_Title"];?></h5>
                        <small class="text-body-secondary">Written by 
                            <a href="<?php echo $urlPrefixBeta; ?>/u/<?php echo $blogPost["Next_Author"]; ?>" class="username"><?php echo $blogPost["Next_Author"]; ?></a> on 
                            <span class="date" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="<?php echo $nextPostDateDetailed; ?> UTC"><?php echo $nextPostDateBasic; ?></span>
                        </small>
                    </div>
                    <div class="card-footer">
                        <a href="<?php echo $blogPost["Next_Locator"]; ?>" class="btn btn-primary float-end">Next Post <i class="fa-solid fa-angles-right"></i></a>
                    </div>
                </div>
                <?php } ?>
            </div>
            
            
        </div>
    </div>
            <?php } ?>
</div>
            </div>
            
            <?php require("php-components/base-page-discord.php"); ?>
        </div>
        <div id="page_bottom">
        </div>
    </main>

    
    <?php require("php-components/base-page-javascript.php"); ?>
    <?php require("php-components/content-viewer-javascript.php"); ?>

</body>

</html>
