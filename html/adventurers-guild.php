<?php


$pageTitle = "Adventurers' Guild";
$pageImage = "https://kickback-kingdom.com/assets/media/context/loading.gif";
$pageDesc = "Top secret super cool hangout spot";


require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

use Kickback\Backend\Controllers\FeedCardController;
use Kickback\Backend\Controllers\FeedController;
use Kickback\Common\Version;

$availableQuestsResp = FeedController::getAvailableQuestsFeed(1, 100);
$availableQuests = $availableQuestsResp->data;

$archivedQuestsResp = FeedController::getArchivedQuestsFeed(1, 100);
$archivedQuests = $archivedQuestsResp->data;

$questLinesResp = FeedController::getAvailableQuestLinesFeed(1, 100);
$questLines = $questLinesResp->data;

$tbaQuestsResp = FeedController::getTBAQuestsFeed(1, 100);
$tbaQuests = $tbaQuestsResp->data;
$showTBAQuests = count($tbaQuests)>0;
$tabActive = "active";
$tabPageActive = "active show";
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
                
                
                $activePageName = "Adventurers' Guild";
                require("php-components/base-page-breadcrumbs.php"); 
                
                ?>
                <?php if (Kickback\Services\Session::isQuestGiver()) { ?>
                <div class="row">
                    <div class="col-12">
                        <div class="card mb-3">
    
                            <div class="card-header bg-ranked-1">
                                <h5 class="mb-0">Welcome back, Quest Giver <?php echo Kickback\Services\Session::getCurrentAccount()->username; ?>. What would you like to do?</h5>
                            </div>
                            <div class="card-body">
                                
                                <a href="<?= Version::urlBetaPrefix(); ?>/quest.php?new" class="btn btn-primary">Post a New Quest</a>
                                <a href="<?= Version::urlBetaPrefix(); ?>/quest-line.php?new" class="btn btn-primary">Create New Quest Line</a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php } ?>
                <div class="row">
                    <div class="col-12">
                        
                        <nav>
                            <div class="nav nav-tabs" id="nav-tab" role="tablist">
                                <?php if ($showTBAQuests) { ?><button class="nav-link <?php echo $tabActive; $tabActive=""; ?>" id="nav-tba-quests-tab" data-bs-toggle="tab" data-bs-target="#nav-tba-quests" type="button" role="tab" aria-controls="nav-tba-quests" aria-selected="true"><i class="fa-solid fa-pen-fancy"></i></button><?php } ?>
                                <button class="nav-link <?php echo $tabActive; $tabActive=""; ?>" id="nav-active-quests-tab" data-bs-toggle="tab" data-bs-target="#nav-active-quests" type="button" role="tab" aria-controls="nav-active-quests" aria-selected="true"><i class="fa-regular fa-compass"></i></button>
                                <button class="nav-link" id="nav-quest-lines-tab" data-bs-toggle="tab" data-bs-target="#nav-quest-lines" type="button" role="tab" aria-controls="nav-quest-lines" aria-selected="true"><i class="fa-solid fa-route"></i></button>
                                <button class="nav-link" id="nav-hosts-tab" data-bs-toggle="tab" data-bs-target="#nav-hosts" type="button" role="tab" aria-controls="nav-hosts" aria-selected="true"><i class="fa-solid fa-landmark-dome"></i></button>
                                <button class="nav-link" id="nav-archives-tab" data-bs-toggle="tab" data-bs-target="#nav-archives" type="button" role="tab" aria-controls="nav-archives" aria-selected="true"><i class="fa-solid fa-box-archive"></i></button>
                                <button class="nav-link" id="nav-reflections-tab" data-bs-toggle="tab" data-bs-target="#nav-reflections" type="button" role="tab" aria-controls="nav-reflections" aria-selected="true"><i class="fa-solid fa-star"></i></button>
                            </div>
                        </nav>
                        <div class="tab-content" id="nav-tabContent">
                            <?php if ($showTBAQuests) { ?>
                            <div class="tab-pane fade <?php echo $tabPageActive; $tabPageActive=""; ?>" id="nav-tba-quests" role="tabpanel" aria-labelledby="nav-tba-quests-tab" tabindex="0">
                                <div class="display-6 tab-pane-title">Unpublished Quests</div>
                                <?php 

                                    for ($i=0; $i < count($tbaQuests); $i++) 
                                    { 
                                        $_vFeedCard = FeedCardController::vFeedRecord_to_vFeedCard($tbaQuests[$i]);
                                        require("php-components/vFeedCardRenderer.php");
                                    }
                                ?>
                            </div>
                            <?php } ?>
                            <div class="tab-pane fade <?php echo $tabPageActive; $tabPageActive=""; ?>" id="nav-active-quests" role="tabpanel" aria-labelledby="nav-active-quests-tab" tabindex="0">
                                <div class="display-6 tab-pane-title">Available Quests</div>
                                <?php 

                                    for ($i=0; $i < count($availableQuests); $i++) 
                                    { 
                                        $_vFeedCard = FeedCardController::vFeedRecord_to_vFeedCard($availableQuests[$i]);
                                        require("php-components/vFeedCardRenderer.php");
                                    }
                                ?>
                            </div>
                            <div class="tab-pane fade" id="nav-quest-lines" role="tabpanel" aria-labelledby="nav-quest-lines-tab" tabindex="0">
                                <div class="display-6 tab-pane-title">Quests Lines</div>
                                <?php 

                                    for ($i=0; $i < count($questLines); $i++) 
                                    { 
                                        $_vFeedCard = FeedCardController::vFeedRecord_to_vFeedCard($questLines[$i]);
                                        require("php-components/vFeedCardRenderer.php");
                                    }
                                ?>
                            </div>
                            <div class="tab-pane fade" id="nav-reflections" role="tabpanel" aria-labelledby="nav-reflections-tab" tabindex="0">
                                <div class="display-6 tab-pane-title">Archive of Reflections</div>
                            </div>
                            <div class="tab-pane fade" id="nav-hosts" role="tabpanel" aria-labelledby="nav-hosts-tab" tabindex="0">
                                <div class="display-6 tab-pane-title">Quest Giver's Chamber</div>
                                
                                <div class="d-flex flex-wrap justify-content-evenly align-items-center">
                                <?php 

                                    $selectUserFormId = "quest-givers";
                                    $selectUsersFormPageSize = 21;
                                    $selectUsersFilter = json_encode(["IsQuestGiver" => 1]);
                                    require("php-components/select-user.php");

                                ?>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="nav-archives" role="tabpanel" aria-labelledby="nav-archives-tab" tabindex="0">
                                <div class="display-6 tab-pane-title">Quest Archives</div>
                                <?php 

                                    for ($i=0; $i < count($archivedQuests); $i++) 
                                    { 
                                        $_vFeedCard = FeedCardController::vFeedRecord_to_vFeedCard($archivedQuests[$i]);
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

    
    <?php require("php-components/base-page-javascript.php"); ?>
        <script>
                                        
            SearchForAccount('<?php echo $selectUserFormId; ?>', 1, null, {"IsQuestGiver": 1});
        </script>
    </body>

</html>
