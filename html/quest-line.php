<?php 

$session = require($_SERVER['DOCUMENT_ROOT']."/api/v1/engine/session/verifySession.php");


require("php-components/base-page-pull-active-account-info.php");


if (isset($_GET['id']))
{

    $id = $_GET['id'];
    $questLineResp = GetQuestLineById($id);
}

if (isset($_GET['locator'])){
        
    $name = $_GET['locator'];
    $questLineResp = GetQuestLineByLocator($name);
}

if (isset($_GET['new']))
{
    $name = "New Quest Line";
    $newPost = true;
    $questLineResp = InsertNewQuestLine();
}

if (!$questLineResp->Success)
{
    unset($questLineResp);
}
if (!isset($questLineResp))
{
    Redirect("adventurers-guild.php");
}



$thisQuestLine = $questLineResp->Data;


$hasSuccess = true;
$successMessage = json_encode($thisQuestLine);

$pageContent = null;
if ($thisQuestLine["content_id"] != null)
{
    $contentResp = GetContentDataById($thisQuestLine["content_id"],"QUEST-LINE",$thisQuestLine["locator"]);
    $pageContent = $contentResp->Data;
}


$thisQuestLinesQuestsResp = GetQuestsByQuestLineId($thisQuestLine["Id"]);
$thisQuestLinesQuests =  $thisQuestLinesQuestsResp->Data;
?>

<!DOCTYPE html>
<html lang="en">


<?php require("php-components/base-page-head.php"); ?>

<body class="bg-body-secondary container p-0">
    <?php 
    
    require("php-components/base-page-components.php"); 
    
    
    ?>

    <!--TOP BANNER-->
    <div class="d-none d-md-block w-100 ratio" style="--bs-aspect-ratio: 26%; margin-top: 56px">

        <img src="/assets/media/<?php echo $thisQuestLine["imagePath"]; ?>" class="" />

    </div>
    <div class="d-block d-md-none w-100 ratio" style="margin-top: 56px; --bs-aspect-ratio: 46.3%;">

        <img src="/assets/media/<?php echo $thisQuestLine["imagePath_mobile"]; ?>" />

    </div>

    <!--MAIN CONTENT-->
    <main class="container pt-3 bg-body" style="margin-bottom: 56px;">
    
        <?php if ($hasError || $hasSuccess) {?>
        <div class="row">
            <div class="col-12">
                <?php if ($hasError) {?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Oh snap!</strong> <?php echo $errorMessage; ?>
                </div>
                <?php } ?>
                <?php if ($hasSuccess) {?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>Congrats!</strong> <?php echo $successMessage; ?>
                </div>
                <?php } ?>
            </div>
        </div>
        <?php } ?>
        <div class="row">
            <div class="col-12 col-xl-9">
                
                
                <?php 
                
                
                $activePageName = $thisQuestLine["name"];
                require("php-components/base-page-breadcrumbs.php"); 
                
                ?>

                <div class="row">
                    <div class="col-12">
                        
                        
                        <h5 class="quest-hosted-by">Created by 
                            <a class="username" href="<?php echo $urlPrefixBeta; ?>/u/<?php echo $thisQuestLine['created_by_username'];?>"><?php echo $thisQuestLine['created_by_username'];?></a>
                            on <span  id="quest_time" class="date" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="<?php echo date_format(date_create($thisQuestLine["date_created"]),"M j, Y H:i:s"); ?> UTC"><?php echo date_format(date_create($thisQuestLine["date_created"]),"M j, Y H:i:s"); ?> UTC</span>
                        </h5>
                        
                
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        
                        <?php if (!$thisQuestLine["published"]) { ?>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="card mb-3">
                                        <div class="bg-danger card-body text-bg-danger">
                                            <div class="d-flex align-items-center">
                                                <h3 role="status">This quest line has not been published and is in draft mode.</h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                        <?php if ($thisQuestLine["being_reviewed"]) { ?>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="card mb-3">
                                        <div class="bg-ranked-1 card-body">
                                            <div class="d-flex align-items-center">
                                                <h3 role="status">This Quest Line is under review...</h3>
                                                <div class="spinner-border ms-auto" aria-hidden="true"></div>
                                            </div>
                                        </div>
                                        <?php if (IsAdmin()) { ?>
                                        <div class="card-footer">
                                            <button type="button" class="btn btn-success float-end mx-1" onclick="OpenModalApprove()">Approve Quest Line</button>
                                            <button type="button" class="btn btn-danger float-end" onclick="OpenModalReject()">Reject Quest Line</button>
                                        </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                        <?php if (CanEditQuestLine($thisQuestLine)) { ?>
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="card mb-3">
            
                                    <div class="card-header bg-ranked-1">
                                        <h5 class="mb-0">Welcome back, Quest Giver <?php echo $_SESSION["account"]["Username"]; ?>. What would you like to do?</h5>
                                    </div>
                                    <div class="card-body">
                                        <button type="button" class="btn btn-primary" onclick="OpenModalEditQuestImages()">Edit Banner & Icon</button>
                                        <button type="button" class="btn btn-primary" onclick="OpenModalEditQuestOptions()">Quest Line Details</button>
                                        <?php if (!$thisQuestLine["being_reviewed"] && !$thisQuestLine["published"]) { ?><button type="button" class="btn btn-success float-end" onclick="OpenModalPublishQuest()">Publish Quest Line</button><?php } ?>
                                    </div>
                                    
                                    <?php if ($thisQuestLine["published"] || $thisQuestLine["being_reviewed"]) { ?>
                                        <div class="card-footer">
                                            <h5>Editing this quest line will unpublish it and remove it from the review queue.</h5>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>

                        <form method="POST">
                            <input type="hidden" name="form_token" value="<?php echo $_SESSION['form_token']; ?>">
                            <input type="hidden" value="<?php echo $thisQuestLine["Id"]; ?>" name="edit-quest-line-id" />
                            <input type="hidden" value="<?php echo $thisQuestLine["image_id"]; ?>" name="edit-quest-line-images-desktop-banner-id" id="edit-quest-line-images-desktop-banner-id"/>
                            <input type="hidden" value="<?php echo $thisQuestLine["image_id_mobile"]; ?>" name="edit-quest-line-images-mobile-banner-id" id="edit-quest-line-images-mobile-banner-id" />
                            <input type="hidden" value="<?php echo $thisQuestLine["image_id_icon"]; ?>" name="edit-quest-line-images-icon-id" id="edit-quest-line-images-icon-id"/>
                            <div class="modal modal-lg fade" id="modalEditQuestImages" tabindex="-1" aria-labelledby="modalEditQuestImagesLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h1 class="modal-title fs-5">Edit Quest Line Images</h1>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <!--DESKTOP TOP BANNER-->
                                            <h3 class="display-6">Desktop Banner<button type="button" class="btn btn-primary float-end" onclick="OpenSelectMediaModal('modalEditQuestImages','edit-quest-line-images-desktop-banner-img','edit-quest-line-images-desktop-banner-id')">Select Media</button></h3>
                                            <div class="w-100 ratio" style="--bs-aspect-ratio: 26%;">

                                                <img src="/assets/media/<?php echo $thisQuestLine["imagePath"]; ?>" class="" id="edit-quest-line-images-desktop-banner-img"/>

                                            </div>
                                            <!--MOBILE TOP BANNER-->
                                            <h3 class="display-6">Mobile Banner<button type="button" class="btn btn-primary float-end" onclick="OpenSelectMediaModal('modalEditQuestImages','edit-quest-line-images-mobile-banner-img','edit-quest-line-images-mobile-banner-id')">Select Media</button></h3>
                                            <div class="w-100 ratio" style="--bs-aspect-ratio: 46.3%;">

                                                <img src="/assets/media/<?php echo $thisQuestLine["imagePath_mobile"]; ?>"  id="edit-quest-line-images-mobile-banner-img"/>

                                            </div>
                                            <!--Quest Icon-->
                                            <h3 class="display-6">Icon<button type="button" class="btn btn-primary float-end" onclick="OpenSelectMediaModal('modalEditQuestImages','edit-quest-line-images-icon-img','edit-quest-line-images-icon-id')">Select Media</button></h3>
                                            <div class="col-md-6" >

                                                <img class="img-thumbnail" src="/assets/media/<?php echo $thisQuestLine["imagePath_icon"]; ?>"  id="edit-quest-line-images-icon-img"/>

                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                                            <input type="submit" name="edit-quest-line-images-submit" class="btn bg-ranked-1" value="Apply Changes" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <form method="POST">
                            <input type="hidden" value="<?php echo $thisQuestLine["Id"]; ?>" name="edit-quest-line-id" />
                            <input type="hidden" name="form_token" value="<?php echo $_SESSION['form_token']; ?>">
                            <div class="modal modal-lg fade" id="modalEditQuestOptions" tabindex="-1" aria-labelledby="modalEditQuestOptionsLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h1 class="modal-title fs-5">Edit Quest Line Details</h1>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row mb-3">
                                                <div class="col-12">
                                                    <div class="form-group">
                                                        <label for="edit-quest-options-title" class="form-label">Title:</label>
                                                        <input type="text" id="edit-quest-line-options-title" name="edit-quest-line-options-title" class="form-control" value="<?php echo $thisQuestLine["name"]; ?>">
                                                    </div>
                                                </div>
                                                
                                            </div>
                                            <div class="row mb-3">
                                                <label for="edit-quest-options-locator" class="form-label">URL:</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">https://kickback-kingdom.com/quest-line/</span>
                                                    <input type="text" id="edit-quest-line-options-locator" name="edit-quest-line-options-locator" class="form-control" value="<?php echo $thisQuestLine["locator"]; ?>">
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-12">
                                                    <div class="form-group">
                                                        <label for="edit-quest-options-summary" class="form-label">Summary:</label>
                                                        <textarea class="form-control" id="edit-quest-line-options-summary" name="edit-quest-line-options-summary" rows="5"><?php echo $thisQuestLine["desc"]; ?></textarea>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                                            <input type="submit" class="btn bg-ranked-1" name="edit-quest-line-options-submit" value="Apply changes" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <form method="POST">
                            <input type="hidden" name="form_token" value="<?php echo $_SESSION['form_token']; ?>">
                            <input type="hidden" name="quest-line-id" value="<?php echo $thisQuestLine["Id"]; ?>" />
                            <div class="modal modal-xl fade" id="modalQuestPublish" tabindex="-1" aria-labelledby="modalQuestPublishLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h1 class="modal-title fs-5">Publish Quest Line</h1>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            
                                            <div class="row mb-3">
                                                <div class="col-12">
                                                    <p>Please make sure you save all of your changes before publishing your quest line! Below are a list of items that need to be met before publishing a quest line on Kickback Kingdom: </p>
                                                </div>
                                                
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-lg-12 col-xl-9">
                                                    <?php 
                                                        $feedCard = $thisQuestLine;
                                                        $feedCard["type"] = "QUEST-LINE";
                                                        $feedCard["title"] = $thisQuestLine["name"];
                                                        $feedCard["image"] = $thisQuestLine["imagePath_icon"];
                                                        $feedCard["published"] = true;
                                                        $feedCard["account_1_username"] = $thisQuestLine["created_by_username"];
                                                        $feedCard["text"] =  $thisQuestLine["desc"];
                                                        $feedCard["date"] = $thisQuestLine["date_created"];
                                                        require("php-components/feed-card.php");
                                                    ?>
                                                </div>
                                                <div class="col-lg-12 col-xl-3">
                                                    <?php if(QuestLineNameIsValid($feedCard["title"])) { ?>
                                                        <p class="text-success"><i class="fa-solid fa-square-check"></i> Valid Title</p>
                                                    <?php } else { ?>
                                                        <p class="text-danger"><i class="fa-solid fa-square-xmark"></i> Title is too short or invalid</p>
                                                    <?php } ?>

                                                    <?php if(QuestLineSummaryIsValid($feedCard["text"])) { ?>
                                                        <p class="text-success"><i class="fa-solid fa-square-check"></i> Valid Summary</p>
                                                    <?php } else { ?>
                                                        <p class="text-danger"><i class="fa-solid fa-square-xmark"></i> Summary is too short</p>
                                                    <?php } ?>

                                                    <?php if((is_null($thisQuestLine["content_id"])) || QuestLinePageContentIsValid($pageContent["data"])) { ?>
                                                        <p class="text-success"><i class="fa-solid fa-square-check"></i> Valid Content</p>
                                                    <?php } else { ?>
                                                        <p class="text-danger"><i class="fa-solid fa-square-xmark"></i> Content is too short</p>
                                                    <?php } ?>

                                                    <?php if(QuestLineLocatorIsValid($thisQuestLine["locator"])) { ?>
                                                        <p class="text-success"><i class="fa-solid fa-square-check"></i> Valid URL Locator</p>
                                                    <?php } else { ?>
                                                        <p class="text-danger"><i class="fa-solid fa-square-xmark"></i> Please use a valid url locator</p>
                                                    <?php } ?>

                                                    <?php if(QuestLineImagesAreValid($thisQuestLine)) { ?>
                                                        <p class="text-success"><i class="fa-solid fa-square-check"></i> Valid Images</p>
                                                    <?php } else { ?>
                                                        <p class="text-danger"><i class="fa-solid fa-square-xmark"></i> Please select a valid icon and banners</p>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                                            <input type="submit" name="submit-quest-line-publish" class="btn bg-ranked-1" onclick="" <?php if(!QuestLineIsValidForPublish($thisQuestLine,$pageContent)) { ?>disabled<?php } ?> value="Submit Quest Line For Review" />
                                            
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                        
                        <?php if (IsAdmin()) { ?>
                        <form method="POST">
                            <input type="hidden" name="form_token" value="<?php echo $_SESSION['form_token']; ?>">
                            <input type="hidden" name="quest-line-id" value="<?php echo $thisQuestLine["Id"]; ?>" />
                            <div class="modal modal-xl fade" id="modalQuestApprove" tabindex="-1" aria-labelledby="modalQuestApproveLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header bg-success">
                                            <h1 class="modal-title fs-5">Approve Quest Line</h1>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row mb-3">
                                                <div class="col-12">
                                                    <?php 
                                                        $feedCard = $thisQuestLine;
                                                        $feedCard["type"] = "QUEST-LINE";
                                                        $feedCard["title"] = $thisQuestLine["name"];
                                                        $feedCard["image"] = $thisQuestLine["imagePath_icon"];
                                                        $feedCard["published"] = true;
                                                        $feedCard["account_1_username"] = $thisQuestLine["created_by_username"];
                                                        $feedCard["text"] =  $thisQuestLine["desc"];
                                                        $feedCard["date"] = $thisQuestLine["date_created"];
                                                        require("php-components/feed-card.php");
                                                    ?>
                                                </div>
                                                
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                                            <input type="submit" name="quest-line-approve-submit" class="btn btn-success" value="Approve Quest Line" />
                                            
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                        
                        <form method="POST">
                            <input type="hidden" name="form_token" value="<?php echo $_SESSION['form_token']; ?>">
                            <input type="hidden" name="quest-line-id" value="<?php echo $thisQuestLine["Id"]; ?>" />
                            <div class="modal modal-xl fade" id="modalQuestReject" tabindex="-1" aria-labelledby="modalQuestRejectLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header bg-danger">
                                            <h1 class="modal-title fs-5">Reject Quest Line</h1>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row mb-3">
                                                <div class="col-12">
                                                    <?php 
                                                        $feedCard = $thisQuestLine;
                                                        $feedCard["type"] = "QUEST-LINE";
                                                        $feedCard["title"] = $thisQuestLine["name"];
                                                        $feedCard["image"] = $thisQuestLine["imagePath_icon"];
                                                        $feedCard["published"] = true;
                                                        $feedCard["account_1_username"] = $thisQuestLine["created_by_username"];
                                                        $feedCard["text"] =  $thisQuestLine["desc"];
                                                        $feedCard["date"] = $thisQuestLine["date_created"];
                                                        require("php-components/feed-card.php");
                                                    ?>
                                                </div>
                                                
                                            </div>
                                            <!--<div class="row">
                                                <div class="col">
                                                    <div class="form-floating">
                                                        <textarea class="form-control" placeholder="Leave a reason for rejection" id="floatingTextarea2" style="height: 100px" required></textarea>
                                                        <label for="floatingTextarea2">Reason for rejection</label>
                                                    </div>
                                                </div>                
                                            </div>-->
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                                            <input type="submit" name="quest-line-reject-submit" class="btn btn-danger" value="Reject Quest Line" />
                                            
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <?php } ?>
                        <script>
                            
                            function OpenModalEditQuestOptions()
                            {
                                $("#modalEditQuestOptions").modal("show");
                            }

                            function OpenModalEditQuestImages()
                            {
                                $("#modalEditQuestImages").modal("show");
                            }

                            function OpenModalPublishQuest()
                            {
                                $("#modalQuestPublish").modal("show");
                            }

                            <?php if (IsAdmin()) { ?>
                            function OpenModalApprove()
                            {
                                $("#modalQuestApprove").modal("show");
                            }

                            function OpenModalReject()
                            {
                                $("#modalQuestReject").modal("show");
                                
                            }
                            <?php } ?>

                        </script>

                        <?php } ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <nav>
                            <div class="nav nav-tabs" id="nav-tab" role="tablist">
                            <button class="nav-link active" id="nav-info-tab" data-bs-toggle="tab" data-bs-target="#nav-info" type="button" role="tab" aria-controls="nav-info" aria-selected="true"><i class="fa-solid fa-newspaper"></i></button>
                            <button class="nav-link" id="nav-quests-tab" data-bs-toggle="tab" data-bs-target="#nav-quests" type="button" role="tab" aria-controls="nav-quests" aria-selected="true"><i class="fa-solid fa-route"></i></button>
                            </div>
                        </nav>
                        <div class="tab-content" id="nav-tabContent">
                            <div class="tab-pane fade active show" id="nav-info" role="tabpanel" aria-labelledby="nav-info-tab" tabindex="0">
                                <div class="display-6 tab-pane-title">Quest Line Information</div>    
                                <?php 
                                
                                if ($thisQuestLine["content_id"] != null)
                                {
                                    $canEditContent = CanEditQuestLine($thisQuestLine);
                                    $contentViewerEditorTitle = "Quest Line Information Manager";
                                    require("php-components/content-viewer.php");
                                }
                                
                                ?>
                            </div>
                            <div class="tab-pane fade" id="nav-quests" role="tabpanel" aria-labelledby="nav-quests-tab" tabindex="0">
                                <div class="display-6 tab-pane-title">Quests</div>
                                <?php 
                                    for ($i=0; $i < count($thisQuestLinesQuests); $i++) 
                                    { 
                                        $feedCard = $thisQuestLinesQuests[$i];
                                        
                                        require ("php-components/feed-card.php");
                                    }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php require("php-components/base-page-discord.php"); ?>
        </div>
        <?php require("php-components/base-page-footer.php"); ?>
    </main>

    <!--Test-->
    <?php require("php-components/base-page-javascript.php"); ?>
    <?php require("php-components/content-viewer-javascript.php"); ?>
</body>

</html>
