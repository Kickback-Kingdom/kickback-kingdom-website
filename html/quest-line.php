<?php 

$session = require($_SERVER['DOCUMENT_ROOT']."/api/v1/engine/session/verifySession.php");


require("php-components/base-page-pull-active-account-info.php");

use Kickback\Backend\Controllers\QuestLineController;
use Kickback\Backend\Controllers\QuestController;
use Kickback\Backend\Controllers\FeedCardController;
use Kickback\Services\Session;

if (isset($_GET['id']))
{

    $id = $_GET['id'];
    $questLineResp = QuestLineController::requestQuestLineResponseById($id);

    /*$showPopUpSuccess = true;
    $PopUpTitle = "Loaded quest line by id";
    $PopUpMessage= json_encode($questLineResp);*/
}

if (isset($_GET['locator'])){
        
    $name = $_GET['locator'];
    $questLineResp = QuestLineController::requestQuestLineResponseByLocator($name);

    /*$showPopUpSuccess = true;
    $PopUpTitle = "Loaded quest line by locator";
    $PopUpMessage= json_encode($questLineResp);*/
}

if (isset($_GET['new']))
{
    $name = "New Quest Line";
    $newPost = true;
    $questLineResp = QuestLineController::insertNewQuestLine();

    
    /*$showPopUpSuccess = true;
    $PopUpTitle = "New Quest Line Debug";
    $PopUpMessage= json_encode($questLineResp);*/
}

if (!$questLineResp->success)
{
    unset($questLineResp);
}
if (!isset($questLineResp))
{
    Session::redirect("adventurers-guild.php");
}

$thisQuestLine = $questLineResp->data;
$thisQuestLine->populateEverything();
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

        <img src="<?= $thisQuestLine->banner->getFullPath(); ?>" class="" />

    </div>
    <div class="d-block d-md-none w-100 ratio" style="margin-top: 56px; --bs-aspect-ratio: 46.3%;">

        <img src="<?= $thisQuestLine->bannerMobile->getFullPath(); ?>" />

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
                
                
                $activePageName = $thisQuestLine->title;
                require("php-components/base-page-breadcrumbs.php"); 
                
                ?>

                <div class="row">
                    <div class="col-12">
                        
                        
                        <h5 class="quest-hosted-by">Created by 
                            <?= $thisQuestLine->createdBy->getAccountElement(); ?>
                            on <span  id="quest_time" class="date" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="<?= $thisQuestLine->dateCreated->formattedDetailed ?> UTC"><?=$thisQuestLine->dateCreated->formattedDetailed ?> UTC</span>
                        </h5>
                        
                
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        
                        <?php if (!$thisQuestLine->reviewStatus->published) { ?>
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
                        <?php if ($thisQuestLine->reviewStatus->beingReviewed) { ?>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="card mb-3">
                                        <div class="bg-ranked-1 card-body">
                                            <div class="d-flex align-items-center">
                                                <h3 role="status">This Quest Line is under review...</h3>
                                                <div class="spinner-border ms-auto" aria-hidden="true"></div>
                                            </div>
                                        </div>
                                        <?php if (Kickback\Services\Session::isMagisterOfTheAdventurersGuild()) { ?>
                                        <div class="card-footer">
                                            <button type="button" class="btn btn-success float-end mx-1" onclick="OpenModalApprove()">Approve Quest Line</button>
                                            <button type="button" class="btn btn-danger float-end" onclick="OpenModalReject()">Reject Quest Line</button>
                                        </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                        <?php if ($thisQuestLine->canEdit()) { ?>
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="card mb-3">
            
                                    <div class="card-header bg-ranked-1">
                                        <h5 class="mb-0">Welcome back, Quest Giver <?php echo Kickback\Services\Session::getCurrentAccount()->username; ?>. What would you like to do?</h5>
                                    </div>
                                    <div class="card-body">
                                        <button type="button" class="btn btn-primary" onclick="OpenModalEditQuestImages()">Edit Banner & Icon</button>
                                        <button type="button" class="btn btn-primary" onclick="OpenModalEditQuestOptions()">Quest Line Details</button>
                                        <?php if (!$thisQuestLine->reviewStatus->beingReviewed && !$thisQuestLine->reviewStatus->published) { ?><button type="button" class="btn btn-success float-end" onclick="OpenModalPublishQuest()">Publish Quest Line</button><?php } ?>
                                    </div>
                                    
                                    <?php if ($thisQuestLine->reviewStatus->published || $thisQuestLine->reviewStatus->beingReviewed) { ?>
                                        <div class="card-footer">
                                            <h5>Editing this quest line will unpublish it and remove it from the review queue.</h5>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>

                        <form method="POST">
                            <input type="hidden" name="form_token" value="<?php echo $_SESSION['form_token']; ?>">
                            <input type="hidden" value="<?php echo $thisQuestLine->crand; ?>" name="edit-quest-line-id" />
                            <input type="hidden" value="<?php echo $thisQuestLine->banner->crand; ?>" name="edit-quest-line-images-desktop-banner-id" id="edit-quest-line-images-desktop-banner-id"/>
                            <input type="hidden" value="<?php echo $thisQuestLine->bannerMobile->crand; ?>" name="edit-quest-line-images-mobile-banner-id" id="edit-quest-line-images-mobile-banner-id" />
                            <input type="hidden" value="<?php echo $thisQuestLine->icon->crand; ?>" name="edit-quest-line-images-icon-id" id="edit-quest-line-images-icon-id"/>
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

                                                <img src="<?= $thisQuestLine->banner->getFullPath(); ?>" class="" id="edit-quest-line-images-desktop-banner-img"/>

                                            </div>
                                            <!--MOBILE TOP BANNER-->
                                            <h3 class="display-6">Mobile Banner<button type="button" class="btn btn-primary float-end" onclick="OpenSelectMediaModal('modalEditQuestImages','edit-quest-line-images-mobile-banner-img','edit-quest-line-images-mobile-banner-id')">Select Media</button></h3>
                                            <div class="w-100 ratio" style="--bs-aspect-ratio: 46.3%;">

                                                <img src="<?= $thisQuestLine->bannerMobile->getFullPath(); ?>"  id="edit-quest-line-images-mobile-banner-img"/>

                                            </div>
                                            <!--Quest Icon-->
                                            <h3 class="display-6">Icon<button type="button" class="btn btn-primary float-end" onclick="OpenSelectMediaModal('modalEditQuestImages','edit-quest-line-images-icon-img','edit-quest-line-images-icon-id')">Select Media</button></h3>
                                            <div class="col-md-6" >

                                                <img class="img-thumbnail" src="<?= $thisQuestLine->icon->getFullPath(); ?>"  id="edit-quest-line-images-icon-img"/>

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
                            <input type="hidden" value="<?= $thisQuestLine->crand; ?>" name="edit-quest-line-id" />
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
                                                        <input type="text" id="edit-quest-line-options-title" name="edit-quest-line-options-title" class="form-control" value="<?= $thisQuestLine->title; ?>">
                                                    </div>
                                                </div>
                                                
                                            </div>
                                            <div class="row mb-3">
                                                <label for="edit-quest-options-locator" class="form-label">URL:</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">https://kickback-kingdom.com/quest-line/</span>
                                                    <input type="text" id="edit-quest-line-options-locator" name="edit-quest-line-options-locator" class="form-control" value="<?= $thisQuestLine->locator; ?>">
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-12">
                                                    <div class="form-group">
                                                        <label for="edit-quest-options-summary" class="form-label">Summary:</label>
                                                        <textarea class="form-control" id="edit-quest-line-options-summary" name="edit-quest-line-options-summary" rows="5" maxlength="512" oninput="updateCharCountQuestLineDesc()"><?= $thisQuestLine->summary; ?></textarea>
                                                    </div>
                                                    <div class="float-end form-text text-success" id="charCount-questLine">400 characters remaining</div>
                                                    <script>
                                                    function updateCharCountQuestLineDesc() {
                                                        var messageInput = document.getElementById('edit-quest-line-options-summary');
                                                        var charCountElement = document.getElementById('charCount-questLine');
                                                        var charLength = messageInput.value.length;
                                                        var minRequiredChars = 200;
                                                        var maxAllowedChars = 512;

                                                        if(charLength < minRequiredChars) {
                                                            charCountElement.textContent = 'You need at least ' + minRequiredChars + ' characters. ' + (minRequiredChars - charLength) + ' more to go.';
                                                            charCountElement.classList.add('text-danger');
                                                            charCountElement.classList.remove('text-success');
                                                        } else if(charLength > maxAllowedChars) {
                                                            charCountElement.textContent = 'Character limit exceeded by ' + (charLength - maxAllowedChars) + '.';
                                                            charCountElement.classList.add('text-danger');
                                                            charCountElement.classList.remove('text-success');
                                                        } else {
                                                            charCountElement.textContent = (maxAllowedChars - charLength) + ' characters remaining.';
                                                            charCountElement.classList.add('text-success');
                                                            charCountElement.classList.remove('text-danger');
                                                        }
                                                    }
                                                    updateCharCountQuestLineDesc();
                                                    </script>
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
                            <input type="hidden" name="form_token" value="<?= $_SESSION['form_token']; ?>">
                            <input type="hidden" name="quest-line-id" value="<?= $thisQuestLine->crand; ?>" />
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
                                                        $_vFeedCard = FeedCardController::vQuestLine_to_vFeedCard($thisQuestLine);
                                                        require("php-components/vFeedCardRenderer.php");
                                                    ?>
                                                </div>
                                                <div class="col-lg-12 col-xl-3">
                                                    <?php if($thisQuestLine->nameIsValid()) { ?>
                                                        <p class="text-success"><i class="fa-solid fa-square-check"></i> Valid Title</p>
                                                    <?php } else { ?>
                                                        <p class="text-danger"><i class="fa-solid fa-square-xmark"></i> Title is too short or invalid</p>
                                                    <?php } ?>

                                                    <?php if($thisQuestLine->summaryIsValid()) { ?>
                                                        <p class="text-success"><i class="fa-solid fa-square-check"></i> Valid Summary</p>
                                                    <?php } else { ?>
                                                        <p class="text-danger"><i class="fa-solid fa-square-xmark"></i> Summary is too short</p>
                                                    <?php } ?>

                                                    <?php if($thisQuestLine->pageContentIsValid()) { ?>
                                                        <p class="text-success"><i class="fa-solid fa-square-check"></i> Valid Content</p>
                                                    <?php } else { ?>
                                                        <p class="text-danger"><i class="fa-solid fa-square-xmark"></i> Content is too short</p>
                                                    <?php } ?>

                                                    <?php if($thisQuestLine->locatorIsValid()) { ?>
                                                        <p class="text-success"><i class="fa-solid fa-square-check"></i> Valid URL Locator</p>
                                                    <?php } else { ?>
                                                        <p class="text-danger"><i class="fa-solid fa-square-xmark"></i> Please use a valid url locator</p>
                                                    <?php } ?>

                                                    <?php if($thisQuestLine->imagesAreValid()) { ?>
                                                        <p class="text-success"><i class="fa-solid fa-square-check"></i> Valid Images</p>
                                                    <?php } else { ?>
                                                        <p class="text-danger"><i class="fa-solid fa-square-xmark"></i> Please select a valid icon and banners</p>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                                            <input type="submit" name="submit-quest-line-publish" class="btn bg-ranked-1" onclick="" <?php if(!$thisQuestLine->isValidForPublish()) { ?>disabled<?php } ?> value="Submit Quest Line For Review" />
                                            
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                        
                        <?php if (Kickback\Services\Session::isMagisterOfTheAdventurersGuild()) { ?>
                        <form method="POST">
                            <input type="hidden" name="form_token" value="<?= $_SESSION['form_token']; ?>">
                            <input type="hidden" name="quest-line-id" value="<?= $thisQuestLine->crand; ?>" />
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
                                                        $_vFeedCard = FeedCardController::vQuestLine_to_vFeedCard($thisQuestLine);
                                                        require("php-components/vFeedCardRenderer.php");
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
                            <input type="hidden" name="form_token" value="<?= $_SESSION['form_token']; ?>">
                            <input type="hidden" name="quest-line-id" value="<?= $thisQuestLine->crand; ?>" />
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
                                                        $_vFeedCard = FeedCardController::vQuestLine_to_vFeedCard($thisQuestLine);
                                                        require("php-components/vFeedCardRenderer.php");
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

                            <?php if (Kickback\Services\Session::isMagisterOfTheAdventurersGuild()) { ?>
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
                                
                                if ($thisQuestLine->hasPageContent())
                                {
                                    $_vCanEditContent = $thisQuestLine->canEdit();
                                    $_vContentViewerEditorTitle = "Quest Line Information Manager";
                                    $_vPageContent = $thisQuestLine->pageContent();
                                    require("php-components/content-viewer.php");
                                }
                                
                                ?>
                            </div>
                            <div class="tab-pane fade" id="nav-quests" role="tabpanel" aria-labelledby="nav-quests-tab" tabindex="0">
                                <div class="display-6 tab-pane-title">Quests</div>
                                <?php 
                                    for ($i=0; $i < count($thisQuestLine->quests); $i++) 
                                    { 
                                        
                                        $_vFeedCard = FeedCardController::vQuest_to_vFeedCard($thisQuestLine->quests[$i]);
                                        require("php-components/vFeedCardRenderer.php");
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
