<?php 

require("base-page-loading-overlay.php"); 

use Kickback\Backend\Controllers\MediaController;
use Kickback\Backend\Controllers\TreasureHuntController;
use Kickback\Backend\Models\NotificationType;
use Kickback\Common\Version;
use Kickback\Backend\Controllers\TaskController;
use Kickback\Services\Session;

$mediaDirs = MediaController::queryMediaDirectories();

$recurringTasks = [];
$achievements = [];
$unclaimedRecurringCount = 0;
$unclaimedAchievementsCount = 0;

if (Session::isLoggedIn()) {
    $account = Session::getCurrentAccount();

    TaskController::ensureRecurringTasks($account);
    // Fetch recurring tasks (daily, weekly, monthly)
    $recurringResponse = TaskController::getAccountTasks($account);
    if ($recurringResponse->success) {
        $recurringTasks = $recurringResponse->data;

        foreach ($recurringTasks as $task) {
            if ($task->isCompleted && !$task->isClaimed) {
                $unclaimedRecurringCount++;
            }
        }
    }

    // Fetch all achievements (assigned + unassigned)
    $achievementResponse = TaskController::getAchievementTasks($account);
    if ($achievementResponse->success) {
        $achievements = $achievementResponse->data;

        foreach ($achievements as $task) {
            if ($task->isCompleted && !$task->isClaimed) {
                $unclaimedAchievementsCount++;
            }
        }
    }
}


$totalUnclaimedTasks = $unclaimedRecurringCount + $unclaimedAchievementsCount;



?>

<!--CONFETTI-->
<div class="confetti-box">
    <div class="js-container-confetti" style="width:100vw; height:100vh;">

    </div>
</div>





<?php if(Session::isLoggedIn()) { ?>



    <?php require(\Kickback\SCRIPT_ROOT . "/php-components/league-viewer.php"); ?>

<!--CHESTS-->
<div class="modal fade modal-chest " id="modalChest" tabindex="-1" aria-labelledby="modalChestLabel" aria-hidden="true" onclick="ToggleChest();">
    <div class="modal-dialog  modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body">
                <div class="card">
                    <div class="animate-widget">
                        <div>
                            <img id="imgShineBackground" class="img-fluid fa-spin" src="" style="    -khtml-user-select: none;    -o-user-select: none;    -moz-user-select: none;    -webkit-user-select: none;    user-select: none;    position: absolute;    left: 0;    right: 0;    top: 0;    bottom: 0;    z-index: -1;    " />
                            <img id="imgChest" class="img-fluid" src="" style="-khtml-user-select: none;    -o-user-select: none;    -moz-user-select: none;    -webkit-user-select: none;    user-select: none;" />
                            
                            <div id="imgItemWrapper" class="chest-item chest-item-flip-container">
                                <img id="imgItemFront" class="img-fluid chest-item-face front" src="" />
                                <img id="imgItemBack" class="img-fluid chest-item-face back" src="/assets/media/cards/card-back.png" />
                            </div>

                            <img id="imgShineForeground" class="img-fluid fa-spin" src="" style="    -khtml-user-select: none;    -o-user-select: none;    -moz-user-select: none;    -webkit-user-select: none;    user-select: none;    position: absolute;    left: 0;    right: 0;    top: 0;    bottom: 0;    width: 400px;    height: 400px;    margin: auto;" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!--SELECT ACCOUNT-->
<div class="modal fade" id="selectAccountModal" tabindex="-1" aria-labelledby="selectAccountModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" >Select Account</h5>        
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>

      </div>
      <div class="modal-body">
        <?php 
        
        $selectUserFormId = "modal-";
        require(\Kickback\SCRIPT_ROOT . "/php-components/select-user.php"); 
        
        ?>
        
      </div> 
      <!--<div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Back</button>
        <button type="button" class="btn bg-ranked-1" onclick="">Select</button>
      </div>-->
    </div>
  </div>
</div>

<!--SELECT MEDIA-->
<div class="modal fade" id="selectMediaModal" tabindex="-1" aria-labelledby="selectMediaModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" >Select Media</h5>        
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>

      </div>
      <div class="modal-body">
        <?php require(\Kickback\SCRIPT_ROOT . "/php-components/select-media.php"); ?>
      </div> 
      <div class="modal-footer">
        <?php if(Kickback\Services\Session::getCurrentAccount()->canUploadImages()) { ?><button type="button" class="btn btn-primary" onclick="OpenMediaUploadModal()">Upload Media</button><?php } ?>
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Back</button>
        <button type="button" class="btn bg-ranked-1" onclick="AcceptSelectedMedia()">Select</button>
      </div>
    </div>
  </div>
</div>

<?php if(Kickback\Services\Session::getCurrentAccount()->canUploadImages()) { ?>
<!--UPLOAD MEDIA-->
<div class="modal fade" id="uploadMediaModal" tabindex="-1" aria-labelledby="uploadMediaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" >Upload Image</h5>        
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-fill">
                    <li class="nav-item">
                        <a class="nav-link active" id="mediaUploadStep-1-link" aria-current="page" href="#">
                            <span class="badge bg-ranked-1 rounded-pill" id="mediaUploadStep-1-pill" style="font-size: 20px;">1</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="mediaUploadStep-2-link" href="#">
                            <span class="badge bg-primary rounded-pill" id="mediaUploadStep-2-pill" style="font-size: 20px;">2</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="mediaUploadStep-3-link" href="#">
                            <span class="badge bg-primary rounded-pill" id="mediaUploadStep-3-pill" style="font-size: 20px;">3</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="mediaUploadStep-4-link" href="#">
                            <span class="badge bg-primary rounded-pill" id="mediaUploadStep-4-pill" style="font-size: 20px;">4</span>
                        </a>
                    </li>
                </ul>
                <hr/>
                <div class="wizard-step wizard-step-active" id="mediaUploadStep-1">
                    <h1 class="display-6 mb-3">Step 1 - Upload Image</h1>
                    <div class="row">
                        <div class="col-12">
                            <div class="input-group mb-3">
                                <label class="input-group-text" for="inputMediaUploadPhoto"><i class="fa-solid fa-cloud-arrow-up"></i></label>
                                <input type="file" class="form-control" id="inputMediaUploadPhoto" onchange="OnUploadFileChanged(this)">
                            </div>
                        </div>
                    </div>  
                    <div class="row">
                        <div class="col-12">
                            
                            <div style="width: 100%;">
                                <img id="imageUploadPreview" src="" class="img-fluid img-thumbnail">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="wizard-step" id="mediaUploadStep-2">
                    <h1 class="display-6 mb-3">Step 2 - Edit Image</h1>
                    <div class="row">
                        <div class="col-12">
                            <div class="input-group mb-3">
                                <label class="input-group-text" for="mediaUploadUsageSelect"><i class="fa-solid fa-crop-simple"></i></label>
                                <select class="form-select" id="mediaUploadUsageSelect" onchange="OnPhotoUsageChanged(this)">
                                    <option value="-1" selected>Choose an image usage size...</option>
                                    <option value=".26">Desktop Banner</option>
                                    <option value=".463">Mobile Banner</option>
                                    <option value="1">Icon (Square)</option>
                                    <option value="0">Custom</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            
                            <div style="width: 100%;">
                                <img id="imagePreview" src="" class="img-fluid">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="wizard-step" id="mediaUploadStep-3">
                    <h1 class="display-6 mb-3">Step 3 - Pixelize Image</h1>
                    <div id="pixelEditor" class="row mb-3">
                        <div class="col-md-4" style="height:400px;">
                            <div class="d-flex h-100">
                                <div class="nav flex-column nav-pills me-2" id="pixelEditorTabs" role="tablist" aria-orientation="vertical">
                                    <button class="nav-link active" id="pixelTabPixelation" data-bs-toggle="pill" data-bs-target="#pixelPanePixelation" type="button" role="tab" aria-controls="pixelPanePixelation" aria-selected="true" title="Pixelation"><i class="fa-solid fa-border-all"></i></button>
                                    <button class="nav-link" id="pixelTabAdjust" data-bs-toggle="pill" data-bs-target="#pixelPaneAdjust" type="button" role="tab" aria-controls="pixelPaneAdjust" aria-selected="false" title="Adjustments"><i class="fa-solid fa-sliders"></i></button>
                                    <button class="nav-link" id="pixelTabTune" data-bs-toggle="pill" data-bs-target="#pixelPaneTune" type="button" role="tab" aria-controls="pixelPaneTune" aria-selected="false" title="Color Tuning"><i class="fa-solid fa-palette"></i></button>
                                    <button class="nav-link" id="pixelTabHue" data-bs-toggle="pill" data-bs-target="#pixelPaneHue" type="button" role="tab" aria-controls="pixelPaneHue" aria-selected="false" title="Hue Remap"><i class="fa-solid fa-shuffle"></i></button>
                                </div>
                                <div class="tab-content flex-grow-1 overflow-auto" id="pixelEditorTabContent">
                                    <div class="tab-pane fade show active" id="pixelPanePixelation" role="tabpanel" aria-labelledby="pixelTabPixelation">
                                        <div class="mb-2">
                                            <label class="form-label">Pixel width</label>
                                            <input type="number" class="form-control form-control-sm" data-pixel-width value="64" min="8" max="1024">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Method</label>
                                            <select class="form-select form-select-sm" data-method>
                                                <option value="neighbor">Nearest Neighbor</option>
                                                <option value="average">Block Average</option>
                                                <option value="palette">Palette (k-means)</option>
                                            </select>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Palette size (for k-means)</label>
                                            <input type="number" class="form-control form-control-sm" data-palette-size value="16" min="2" max="64">
                                        </div>
                                        <div class="d-flex flex-wrap gap-3 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" data-dither id="pixelDither">
                                                <label class="form-check-label" for="pixelDither">Dither (FS)</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" data-auto-render id="pixelAutoRender" checked>
                                                <label class="form-check-label" for="pixelAutoRender">Auto Render</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" data-auto-fit id="pixelAutoFit" checked>
                                                <label class="form-check-label" for="pixelAutoFit">Auto Fit</label>
                                            </div>
                                        </div>
                                        <div class="mb-2 d-flex gap-2">
                                            <button type="button" class="btn btn-primary btn-sm" data-render>Render</button>
                                            <button type="button" class="btn btn-secondary btn-sm" data-reset>Reset</button>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="pixelPaneAdjust" role="tabpanel" aria-labelledby="pixelTabAdjust">
                                        <div class="mb-2">
                                            <label class="form-label">Brightness</label>
        <input type="range" class="form-range" data-brightness min="-100" max="100" value="0">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Contrast</label>
                                            <input type="range" class="form-range" data-contrast min="-100" max="100" value="0">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Saturation</label>
                                            <input type="range" class="form-range" data-saturation min="0" max="200" value="100">
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="pixelPaneTune" role="tabpanel" aria-labelledby="pixelTabTune">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" data-enable-tune id="pixelEnableTune">
                                            <label class="form-check-label" for="pixelEnableTune">Enable tuning</label>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Reds</label>
                                            <input type="range" class="form-range" data-tune-red min="-100" max="100" value="0">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Yellows</label>
                                            <input type="range" class="form-range" data-tune-yellow min="-100" max="100" value="0">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Greens</label>
                                            <input type="range" class="form-range" data-tune-green min="-100" max="100" value="0">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Cyans</label>
                                            <input type="range" class="form-range" data-tune-cyan min="-100" max="100" value="0">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Blues</label>
                                            <input type="range" class="form-range" data-tune-blue min="-100" max="100" value="0">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Magentas</label>
                                            <input type="range" class="form-range" data-tune-magenta min="-100" max="100" value="0">
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="pixelPaneHue" role="tabpanel" aria-labelledby="pixelTabHue">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" data-enable-remap id="pixelEnableRemap">
                                            <label class="form-check-label" for="pixelEnableRemap">Enable hue remap</label>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Global remap strength</label>
                                            <input type="range" class="form-range" data-remap-strength min="0" max="100" value="100">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Reds →</label>
                                            <select class="form-select form-select-sm mb-1" data-map-r></select>
                                            <input type="range" class="form-range" data-map-r-str min="0" max="100" value="100">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Yellows →</label>
                                            <select class="form-select form-select-sm mb-1" data-map-y></select>
                                            <input type="range" class="form-range" data-map-y-str min="0" max="100" value="100">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Greens →</label>
                                            <select class="form-select form-select-sm mb-1" data-map-g></select>
                                            <input type="range" class="form-range" data-map-g-str min="0" max="100" value="100">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Cyans →</label>
                                            <select class="form-select form-select-sm mb-1" data-map-c></select>
                                            <input type="range" class="form-range" data-map-c-str min="0" max="100" value="100">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Blues →</label>
                                            <select class="form-select form-select-sm mb-1" data-map-b></select>
                                            <input type="range" class="form-range" data-map-b-str min="0" max="100" value="100">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Magentas →</label>
                                            <select class="form-select form-select-sm mb-1" data-map-m></select>
                                            <input type="range" class="form-range" data-map-m-str min="0" max="100" value="100">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="mb-2 d-flex justify-content-between">
                                <span class="text-muted">Pixelated size: <span data-pix-meta>—</span></span>
                                <span class="text-muted" data-status></span>
                            </div>
                            <div data-viewport style="position:relative; overflow:auto; width:100%; height:400px; border:1px solid #dee2e6; border-radius:0.25rem;">
                                <div data-wrap style="position:relative; width:max-content; height:max-content; transform-origin:top left;">
                                    <canvas id="pixelCanvas" style="image-rendering:pixelated;"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" onclick="SkipPixelation()">Skip</button>
                            <button type="button" class="btn btn-primary" onclick="ApplyPixelation()">Apply Pixelation</button>
                        </div>
                    </div>
                </div>

                <div class="wizard-step" id="mediaUploadStep-4">
                    <h1 class="display-6 mb-3">Step 4 - Edit Metadata</h1>
                    <div class="row mb-3">
                        <div class="col-12">

                            <div style="width: 100%;max-height: 200px;" class="d-flex flex-wrap justify-content-evenly align-items-center">
                                <img id="imagePreviewEdited" src="" style="max-height: inherit;" class="img-fluid img-thumbnail">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 col-lg-6">
                            <div class="mb-3">
                                <label for="mediaUploadImageNameTextbox" class="form-label">Name</label>
                                <input type="text" class="form-control" id="mediaUploadImageNameTextbox" required>
                            </div>
                            <div class="mb-3">
                                <label for="mediaUploadImageFolderSelect" class="form-label">Image Folder</label>
                                <div class="input-group">
                                    <label class="input-group-text" for="mediaUploadImageFolderSelect"><i class="fa-solid fa-folder-tree"></i></label>

                                    <select class="form-select" id="mediaUploadImageFolderSelect">
                                        <option value="" selected>Choose a folder...</option>
                                        <?php

                                            foreach($mediaDirs as $dir) {
                                                echo "<option value='{$dir}'>{$dir}</option>";
                                            }
                                        ?>
                                    </select>
                                    <?php if (Kickback\Services\Session::isAdmin()) { ?>
                                    <button class="btn btn-primary" type="button" onclick="CreateNewFolderForUpload()">Create New Folder</button>
                                    <script>
                                        function CreateNewFolderForUpload() {
                                            // Ask the user for a folder name
                                            const folderName = prompt("Enter the new folder name:");

                                            // Check if user pressed Cancel or entered an empty folder name
                                            if (!folderName) return;

                                            // Create a new option element
                                            const newOption = document.createElement('option');
                                            newOption.value = folderName;
                                            newOption.textContent = folderName;

                                            // Append the new option to the dropdown
                                            const dropdown = document.getElementById('mediaUploadImageFolderSelect');
                                            dropdown.appendChild(newOption);

                                            // Set the newly added option as the selected option
                                            dropdown.value = folderName;
                                        }
                                    </script>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 col-lg-6">

                        <div class="mb-3">
                                <label for="mediaUploadImageDescTextbox" class="form-label">Description</label>
                                <textarea class="form-control" id="mediaUploadImageDescTextbox" rows="5"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div> 
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="MediaUploadPrevStep()" id="mediaUploadButtonPrev">Back</button>
                <button type="button" class="btn bg-ranked-1" onclick="MediaUploadNextStep()" id="mediaUploadButtonNext">Next</button>
            </div>
        </div>
    </div>
</div>
<?php } ?>
<!-- PRESTIGE VIEW MODAL -->
<form method="POST">
    <input type="hidden" name="notification-view-prestige-id" id="notification-view-prestige-id" class="rating-value" required>
    <div class="modal fade" id="notificationViewPrestigeModal" data-bs-keyboard="false" data-bs-backdrop="static" tabindex="-1" aria-labelledby="notificationViewPrestigeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" style="border-color: transparent;background: transparent;">
                <div class="modal-header" style="background: transparent; border-bottom: none; text-align: center;">
                    <h2 id="animated-prestige-title" class="animate__animated" style="color: gold; font-size: 28px; font-weight: bold; text-shadow: 0px 0px 15px gold;">
                        Your Name Echoes in the Halls…
                    </h2>
                </div>
                <div class="modal-body animate__animated" id="animated-prestige-body" style="background: #1e1e2d; color: white; border-radius: 8px; opacity: 0;">
                    <div class="card p-3 shadow-sm" style="background: #252537; border: 1px solid gold; border-radius: 12px;">
                        <div class="d-flex align-items-center mb-3">
                            <img id="notification-view-prestige-avatar" src="" class="rounded-circle me-3" width="60" height="60" style="border: 2px solid gold;">
                            <div>
                                <h6 id="notification-view-prestige-username" class="mb-0 fw-bold" style="color: gold;"></h6>
                                <small id="notification-view-prestige-date" style="color: gold;"></small>
                            </div>
                        </div>
                        <p id="notification-view-prestige-message" class="fst-italic border-start ps-3" style="color: white;"></p>
                        <div id="notification-view-prestige-commend" class="mt-2"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
<!--QUEST REVIEW MODAL-->
<form method="POST">
    
    <input type="hidden" name="quest-review-quest-id" id="quest-review-quest-id" class="rating-value" required="">
    <div class="modal fade" id="questReviewModal" tabindex="-1" aria-labelledby="questReviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header ">
                    <h5 class="modal-title">Review Quest</h5>        
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>

                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <div class="card mb-3 feed-card">
                                <div class="row g-0">
                                    <div class="col col-auto col-md" style="margin:auto;position: relative;">
                                        <span class="feed-stamp feed-stamp-quest bg-secondary bg-ranked-1">QUEST</span>
                                        <img id="quest-review-quest-image" src="/assets/media/quests/41.png" class="img-fluid img-thumbnail">
                                    </div>
                                    <div class="col col-12 col-md-8 col-lg-9">
                                        <div class="card-body">
                                            <a class="feed-title" id="quest-review-quest-title-link" href="/beta/q/Barotrauma-Barothon-4">
                                                <h5 class="card-title" id="quest-review-quest-title">Cpt. Longs' Barothon (Continued)</h5>
                                            </a>
                                            <p class="card-text">
                                                <small class="text-body-secondary">Hosted by <a id="quest-review-quest-host-1" href="/beta/u/hansibaba" class="username">hansibaba</a>
                                                <span id="quest-review-quest-host-2-span">and <a id="quest-review-quest-host-2" href="/beta/u/hansibaba" class="username">hansibaba</a></span>
                                                                                    on <span id="quest-review-quest-date" class="date">Jul 21, 2023</span>
                                                </small>
                                            </p>
                                            <p id="quest-review-quest-summary"></p>
                                            
                                                                            
                                            <p class="feed-tags">
                                                <span id="quest-review-play-style" class="quest-tag quest-tag-roleplay" tabindex="0" >Roleplay</span>
                                                
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6 col-lg-6">
                            <label class="form-label">Rate the host(s)</label>
                            <div class="star-rating">
                                <i class="fa-regular fa-star" data-rating="1"></i>
                                <i class="fa-regular fa-star" data-rating="2"></i>
                                <i class="fa-regular fa-star" data-rating="3"></i>
                                <i class="fa-regular fa-star" data-rating="4"></i>
                                <i class="fa-regular fa-star" data-rating="5"></i>
                                <input type="hidden" name="quest-review-host" class="rating-value" required="">
                            </div>
                        </div>
                        <div class="col-6 col-lg-6">
                            <label class="form-label">Rate the quest</label>
                            <div class="star-rating">
                                <i class="fa-regular fa-star" data-rating="1"></i>
                                <i class="fa-regular fa-star" data-rating="2"></i>
                                <i class="fa-regular fa-star" data-rating="3"></i>
                                <i class="fa-regular fa-star" data-rating="4"></i>
                                <i class="fa-regular fa-star" data-rating="5"></i>
                                <input type="hidden" name="quest-review-quest" class="rating-value" required="">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <div class="my-3">
                                <label for="quest-review-comment" class="form-label">Comments for the host(s)</label>
                                <textarea class="form-control" id="quest-review-comment" rows="3" name="quest-review-comment" placeholder="Only the hosts will be able to see this" maxlength="1024"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                    <input type="submit" name="submit-quest-review" class="btn bg-ranked-1" value="Submit & Collect" />
                </div>
            </div>
        </div>
    </div>
</form>

<!--SHOPPING CART-->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasMenuRightShoppingCart" aria-labelledby="offcanvasMenuRightShoppingCartLabel">
    <div class="offcanvas-header bg-primary">
        <h5 class="offcanvas-title text-white" id="offcanvasMenuRightShoppingCartLabel">Shopping Cart</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"
            aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <!-- Shopping Cart Items -->
        <div class="shopping-cart-items">

            <?php
            // Placeholder for your cart items array
            if (Kickback\Services\Session::isAdmin())
            {
                $cart_items = [
                    ['id' => 1, 'name' => 'Epic Sword', 'quantity' => 1, 'price' => '120 Coins', 'icon_url' => '/assets/media/items/21.png'],
                    ['id' => 2, 'name' => 'Mystic Potion', 'quantity' => 2, 'price' => '60 Coins', 'icon_url' => '/assets/media/items/21.png']
                    // ... more items ...
                ];
                $cart_items = [];
            }
            else 
            {
                $cart_items = [];
            }
            if(empty($cart_items)) {
                echo "<p class='text-muted'>Your cart is empty.</p>";
            } else {
                foreach($cart_items as $item) {
                    echo "
            <div class='cart-item d-flex justify-content-between align-items-center mb-3'>
                <div class='item-icon me-2'>
                    <img class='img img-thumbnail' src='{$item['icon_url']}' alt='{$item['name']}'>
                </div>
        
                <div class='item-details d-flex align-items-center flex-grow-1'>
                    <div class='me-3'>
                        <div class='item-title fw-bold fs-5'>{$item['name']}</div>
                        <div class='item-quantity d-flex align-items-center'>
                            <button class='quantity-decrease btn btn-outline-secondary btn-sm'>-</button>
                            <input type='number' value='{$item['quantity']}' class='form-control quantity-input mx-2 form-control-sm' min='1'>
                            <button class='quantity-increase btn btn-outline-secondary btn-sm'>+</button>
                        </div>
                    </div>
                    <div class='item-price ms-auto me-3'>
                        <span>{$item['price']}</span>
                    </div>
                </div>
                <div class='item-actions'>
                    <button class='btn btn-danger btn-sm'><i class='fa-regular fa-trash-can'></i></button>
                </div>
            </div>";
                }
            }
        ?>
        </div>

        <!-- Shopping Cart Total -->
    </div>
    <div class="offcanvas-footer p-3 border-top">
        <!-- Shopping Cart Totals Summary -->
        <div class="shopping-cart-summary mb-3 p-2 border-bottom">
            <p class="summary-line">Subtotal: <span class="subtotal-amount">0 </span></p>
            <p class="summary-line">Discount Applied: <span class="discount-amount">0 </span></p>
            <p class="summary-line fw-bold total-line">Total: <span class="final-total-amount">0 </span></p>
        </div>
        <!-- Proceed to Checkout Button -->
        <a class="btn btn-primary w-100 disabled" href="<?php echo Version::urlBetaPrefix(); ?>/checkout.php" disabled>Proceed to Checkout</a>
    </div>

</div>

<!--NOTIFICATIONS-->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasMenuRight" aria-labelledby="offcanvasMenuRightLabel">
    <div class="offcanvas-header bg-primary">
        <h5 class="offcanvas-title text-white" id="offcanvasMenuRightLabel">Notifications</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"
            aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <?php require(\Kickback\SCRIPT_ROOT . "/php-components/base-page-components-notifications.php"); ?>
        
    </div>
</div>


<!--Tasks and Achievements-->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasMenuRightTasks" aria-labelledby="offcanvasMenuRightTasksLabel">
    <div class="offcanvas-header bg-primary">
        <h5 class="offcanvas-title text-white" id="offcanvasMenuRightTasksLabel">Tasks & Achievements</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"
            aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">

    
    <?php require(\Kickback\SCRIPT_ROOT . "/php-components/base-page-components-tasks.php"); ?>
    
    </div>
</div>

<?php } ?>

<!--ITEM MODAL-->
<div class="modal fade" id="inventoryItemModal" tabindex="-1" aria-labelledby="inventoryItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="inventoryItemTitle">Item Title</h5>        
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12 col-md-6">
                        <div style="position: relative;" id="inventoryItemImageContainer">
                            <img id="inventoryItemImage" src="" class="img-fluid animate__animated" alt="Item Image" style="width: 100%;">
                            <img id="inventoryItemImageSecondary" src="" class="img-fluid animate__animated" alt="Item Image" style="width: 100%; display: none;">
                        </div>
                        <p class="float-end" style="font-size: .8em;">Artwork by <a class="username" id="inventoryItemArtist" href="#">Artist: Artist Name</a></p>
                    </div>
                    <div class="col-12 col-md-6">
                        
                        <h6>Date Created</h6>
                        <p id="inventoryItemDate">Release Date: Date</p>
                            <h6>Description</h6>
                        <p id="inventoryItemDescription">Item Description</p>
                    </div>
                </div>
                <style>
                    .container-panel {
    background-color: #f8f9fa;
    border: 1px solid #ddd;
    border-radius: 12px;
    box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);
}

.container-icon {
    color: #6f42c1;
}

.open-container-button {
    transition: all 0.2s ease-in-out;
    font-weight: 500;
}

.open-container-button:hover {
    background-color: #6f42c1;
    color: white;
    border-color: #6f42c1;
}



                </style>
<!-- Container Interaction Section (Hidden by Default) -->
<div class="row justify-content-center" id="inventoryItemContainerSection" style="display: none;">
    <div class="col-12 col-md-10">
        <div class="container-panel text-center p-4 mt-3">
            <div class="container-icon mb-2">
                <i class="fa-solid fa-box-archive fa-lg" id="containerIcon"></i>
            </div>
            <div class="container-label mb-3" id="containerExplanation">
                This is a <strong>container item</strong>. Click below to view its contents.
            </div>
            <button class="btn btn-outline-secondary btn-sm open-container-button me-2" id="inventoryItemOpenContainerButton">
                <i class="fa-duotone fa-regular fa-box-open me-1"></i> View Contents
            </button>
            <a href="#" class="btn btn-outline-primary btn-sm open-container-button d-none" id="inventoryItemEditDeckButton">
                <i class="fa-regular fa-cards-blank me-1"></i> Edit Deck
            </a>

        </div>
    </div>
</div>




            </div>
            <div class="modal-footer" id="inventoryItemFooter">
                <div class="row g-3 flex-fill">
                    <!-- Input group column -->
                    <div class="col-sm-8" id="inventoryItemCopyContainer">
                        <div class="input-group mb-3">
                            <input type="text" id="inventoryItemCopyInput" class="form-control" aria-describedby="inventoryItemCopyButton">
                            <button class="btn btn-primary" type="button" id="inventoryItemCopyButton" onclick="CopyContainerToClipboard()">Copy</button>
                        </div>
                    </div>
                    <!-- Use button column -->
                    <div class="col-sm-4">
                        <button type="button" id="inventoryItemUseButton" class="btn bg-ranked-1 float-end" onclick="UseInventoryItem()">Use</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!--ITEM Container MODAL-->
<div class="modal fade" id="inventoryItemContainerModal" tabindex="-1" aria-labelledby="inventoryItemContainerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content shadow rounded-4">
            <div class="modal-header bg-dark text-white rounded-top-4">
                <h5 class="modal-title" id="inventoryItemContainerTitle">Container Contents</h5>        
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light text-center" style="max-height: 70vh; overflow-y: auto;">
                <div id="inventoryItemContainerLoading" class="my-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-3">Opening container...</p>
                </div>
                <div id="inventoryItemContainerContents" class="inventory-grid d-none">
                    <!-- Dynamically inserted container items -->
                </div>
            </div>
        </div>
    </div>
</div>


<?php if (Kickback\Services\Session::isEventOrganizer()) { 
    
    $currentAndUpComingTreasureHunts = TreasureHuntController::queryCurrentEventsAndUpcoming();
    ?>
<!--Treasure hunt hide object modal-->
<div class="modal fade" id="treasureHuntHideObjectModal" tabindex="-1" aria-labelledby="treasureHuntHideObjectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow rounded-4">
            <div class="modal-header bg-dark text-white rounded-top-4">
                <h5 class="modal-title">Hide and Manage Treasure</h5>        
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body bg-light">
                    <div class="mb-3 text-start">
                        <label for="huntSelect" class="form-label fw-semibold">Select Treasure Hunt</label>
                        <select class="form-select" id="huntSelect" name="treasure_hunt_locator" required>
                            <?php foreach ($currentAndUpComingTreasureHunts as $hunt): ?>
                                <option value="<?= $hunt->locator ?>">
                                    <?= htmlspecialchars($hunt->name) ?> (<?= $hunt->startDate->formattedBasic ?> – <?= $hunt->endDate->formattedBasic ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-check form-switch text-start mb-3">
                        <input class="form-check-input" type="checkbox" id="oneTimeFind" name="one_time_find">
                        <label class="form-check-label" for="oneTimeFind">This is a one-time find only</label>
                    </div>
                    <div class="mb-3 text-start">
                        <label for="objectContents" class="form-label fw-semibold">What's Inside?</label>
                        <select class="form-select" id="objectContents" name="object_crand" required>
                            <option value="" disabled selected>Select an item</option>
                            <?php foreach ($treasureHuntPossibleItems as $item): ?>
                                <option value="<?= $item->crand ?>">
                                    <?= htmlspecialchars($item->name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Object Image -->
                    <div class="mb-4 text-start">
                        <label class="form-label fw-semibold d-block">
                            Object Image
                            <button type="button" class="btn btn-sm btn-primary float-end"
                                onclick="OpenSelectMediaModal('treasureHuntHideObjectModal', 'treasure-object-image-preview', 'treasure-object-image-id')">
                                <i class="fa-solid fa-image me-1"></i> Select Media
                            </button>
                        </label>

                        <!-- Hidden input to store selected media CRAND -->
                        <input type="hidden" name="object_image_id" id="treasure-object-image-id" value="">

                        <!-- Preview box -->
                        <div class="text-center border rounded p-2 bg-white">
                            <img src="/assets/media/items/placeholder.png"
                                id="treasure-object-image-preview"
                                class="img-thumbnail"
                                style="max-height: 150px; object-fit: contain;">
                        </div>

                        <small class="form-text text-muted mt-1">
                            Select an image from the media library for the object you're hiding.
                        </small>
                    </div>

                    
                    <!-- Divider -->
                    <hr class="my-4">

                    <!-- Existing Hidden Objects List -->
                    <h6 class="fw-bold text-start mb-3"><i class="fa-solid fa-eye-slash me-2 text-secondary"></i>Hidden Objects</h6>

                    <?php if (!empty($currentHiddenObjects)): ?>
                        <div class="table-responsive small">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Item</th>
                                        <th>Name</th>
                                        <th>Found</th>
                                        <th class="text-end">Delete</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($currentHiddenObjects as $i => $obj): ?>
                                        <tr>
                                            <td><?= $i + 1 ?></td>
                                            <td>
                                                <img src="<?= $obj->media->url ?>" alt="Object" width="24" height="24" class="rounded me-2" style="object-fit: cover;">
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($obj->item->name) ?>
                                            </td>
                                            <td>
                                                <?php if ($obj->found): ?>
                                                    <span class="badge bg-success">✔</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">✘</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="submitTreasureHuntDeleteObject('<?= $obj->ctime; ?>', <?= $obj->crand; ?>)">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-muted small text-center">No hidden objects on this page yet.</div>
                    <?php endif; ?>

                </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn bg-ranked-1" onclick="submitTreasureHuntHideObject();" >Hide Object</button>
            </div>
        </div>
    </div>
</div>
<script>
    function submitTreasureHuntDeleteObject(ctime, crand) {
        

        TreasureHuntDeleteObject(ctime, crand, function(success, message) {
            console.log(success);
            console.log(message);

            if (success) {
                
                window.location.href = window.location.pathname;
            }
        });
    }
    function submitTreasureHuntHideObject() {
        const huntLocator = document.getElementById("huntSelect").value;
        const itemId = document.getElementById("objectContents").value;
        const mediaId = document.getElementById("treasure-object-image-id").value;
        const oneTimeOnly = document.getElementById("oneTimeFind").checked;
        const pageUrl = "<?= $pageVisitId; ?>";
        
        const xPercent = (Math.random() * (80 - 20) + 20).toFixed(2);
        const yPercent = (Math.random() * (80 - 20) + 20).toFixed(2);


        TreasureHuntHideObject(huntLocator, itemId, mediaId, oneTimeOnly, pageUrl, xPercent, yPercent, function(success, message) {
            console.log(success);
            console.log(message);

            if (success) {
                
                window.location.href = window.location.pathname;
            }
        });
    }

    
</script>
<?php } ?>



<!--DISCORD-->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasMenuRightDiscord" aria-labelledby="offcanvasMenuRightDiscordLabel">
    <div class="offcanvas-header bg-primary">
        <h5 class="offcanvas-title text-white" id="offcanvasMenuRightDiscordLabel">Kickback Kingdom - Discord</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"
            aria-label="Close"></button>
    </div>
    <div class="offcanvas-body" style="padding: 0px;overflow-y: hidden;">
        <iframe src="https://discord.com/widget?id=671894564411539548&amp;theme=dark" width="100%"
                allowtransparency="true" frameborder="0"
                sandbox="allow-popups allow-popups-to-escape-sandbox allow-same-origin allow-scripts"
                height="100%"></iframe>
    </div>
</div>

<?php if (!isset($_GET['borderless'])) { ?>

<!--MOBILE NAVIGATION-->
<div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasMenu" aria-labelledby="offcanvasMenuLabel">
    <div class="offcanvas-header bg-primary">
        <h5 class="offcanvas-title text-white" id="offcanvasMenuLabel">Navigation</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"
            aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
        <ul class="navbar-nav justify-content-end flex-grow-1">
            <li class="nav-item">
                <a class="nav-link mobile-menu-item active" aria-current="page" href="<?php echo Version::urlBetaPrefix(); ?>/"><i class="nav-icon fa-solid fa-house"></i> Home <i class="fa-solid fa-chevron-right mobile-menu-item-arrow"></i></a>
            </li>
            <li class="nav-item">
                <a class="nav-link mobile-menu-item" href="<?php echo Version::urlBetaPrefix(); ?>/town-square.php"><i class="nav-icon fa-regular fa-address-card"></i> Town Square <i class="fa-solid fa-chevron-right mobile-menu-item-arrow"></i></a>
            </li>
            <!--
            <li class="nav-item">
                <a class="nav-link mobile-menu-item" href="<?php echo Version::urlBetaPrefix(); ?>/challenges.php"><i class="nav-icon fa-solid fa-trophy"></i> Ranked Challenges <i class="fa-solid fa-chevron-right mobile-menu-item-arrow"></i></a>
            </li>
            -->
            <li class="nav-item">
                <a class="nav-link mobile-menu-item" href="<?php echo Version::urlBetaPrefix(); ?>/blogs.php"><i class="nav-icon fa-solid fa-newspaper"></i> Blogs <i class="fa-solid fa-chevron-right mobile-menu-item-arrow"></i></a>
            </li>
            <li class="nav-item">
                <a class="nav-link mobile-menu-item" href="<?php echo Version::urlBetaPrefix(); ?>/games.php"><i class="nav-icon fa-solid fa-gamepad"></i> Games & Activities<i class="fa-solid fa-chevron-right mobile-menu-item-arrow"></i></a>
            </li>
            <li class="nav-item">
                <a class="nav-link mobile-menu-item" href="<?php echo Version::urlBetaPrefix(); ?>/servers.php"><i class="nav-icon fa-regular fa-server"></i> Community Servers <i class="fa-solid fa-chevron-right mobile-menu-item-arrow"></i></a>
            </li>
            <li class="nav-item">
                <a class="nav-link mobile-menu-item" href="<?php echo Version::urlBetaPrefix(); ?>/business-plan.php"><i class="nav-icon fa-regular fa-file-lines"></i> Business Plan <i class="fa-solid fa-chevron-right mobile-menu-item-arrow"></i></a>
            </li>
            <li class="nav-item">
                <a class="nav-link mobile-menu-item" href="<?php echo Version::urlBetaPrefix(); ?>/analytics.php"><i class="nav-icon fa-solid fa-chart-line"></i> Analytics <i class="fa-solid fa-chevron-right mobile-menu-item-arrow"></i></a>
            </li>
            <li class="nav-item">
                <a class="nav-link mobile-menu-item" href="<?php echo Version::urlBetaPrefix(); ?>/guild-halls.php"><i class="nav-icon fa-solid fa-signs-post"></i> Guild Halls <i class="fa-solid fa-chevron-right mobile-menu-item-arrow"></i></a>
            </li>
            <?php

            if (Session::isAdmin())
            {
                ?>
            <li class="nav-item">
                <a class="nav-link mobile-menu-item" href="<?php echo Version::urlBetaPrefix(); ?>/admin-dashboard.php"><i class="nav-icon fa-solid fa-shield-halved"></i> Admin Dashboard <i class="fa-solid fa-chevron-right mobile-menu-item-arrow"></i></a>
            </li>
            <?php } ?>
            <?php

            if (Session::isLoggedIn())
            {
                ?>


<li class="nav-item">
                <a class="nav-link mobile-menu-item" href="<?php echo Version::urlBetaPrefix(); ?>/login.php"><i class="nav-icon fa-solid fa-right-from-bracket"></i> Logout <i class="fa-solid fa-chevron-right mobile-menu-item-arrow"></i></a>
            </li>
<?php
            }

            ?>
        </ul>
    </div>
</div>

<!--MOBILE CENTER BUTTON-->
<a class="btn btn-secondary btn-lg rounded-top-pill mobile-bar-btn-center d-block d-md-none" type="button" href="<?php echo Version::urlBetaPrefix(); ?>/challenges.php">
    <i class="fa-solid fa-trophy"></i>
</a>

<!--MOBILE TOP BAR-->
<nav class="d-md-none d-sm-block fixed-top navbar bg-primary" data-bs-theme="dark">
    <div class="container-fluid">
        <button class="btn btn-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasMenu"
            aria-controls="offcanvasMenu" aria-label="Toggle navigation">
            <i class="fa-solid fa-bars"></i>
        </button>
        <a class="me-0 me-lg-2 navbar-brand p-0 mobile-navbar-logo" href="<?php echo Version::urlBetaPrefix(); ?>/" aria-label="Bootstrap">
            <img class="kk-logo" src="/assets/images/logo-kk.png" />
        </a>

        <button class="btn btn-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasMenuRight"
            aria-controls="offcanvasMenuRight" aria-label="Toggle navigation">
            <i class="fa-solid fa-bell"></i>
            <span
                class="bg-secondary border border-light p-1 position-absolute rounded-circle top-50 translate-middle">
                <span class="visually-hidden">New alerts</span>
            </span>
        </button>
    </div>
</nav>

<!--MOBILE BOTTOM BAR-->
<nav class="d-md-none d-sm-block fixed-bottom navbar bg-primary py-0" data-bs-theme="dark">
    <div class="container-fluid">
        <a class="btn btn-lg btn-primary" type="button" href="<?php echo Version::urlBetaPrefix(); ?>/">
            <i class="fa-solid fa-home"></i>
        </a>

        <a class="btn btn-lg btn-primary" type="button" href="<?php echo Version::urlBetaPrefix(); ?>/town-square.php">
            <i class="fa-solid fa-users"></i>
        </a>

        <button class="btn btn-primary btn-lg" type="button">
            <i class="fa-solid fa-trophy"></i>
        </button>

        <button class="btn btn-lg btn-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasMenuRightDiscord" aria-controls="offcanvasMenuRightDiscord" aria-label="Toggle navigation">
            <i class="fa-brands fa-discord"></i>
        </button>

        <?php 

            if (Session::isLoggedIn())
            {
                ?>


        <a class="btn btn-lg btn-primary" type="button" href="<?php echo Version::urlBetaPrefix(); ?>/u/<?php echo Kickback\Services\Session::getCurrentAccount()->username; ?>">
            <i class="fa-solid fa-user"></i>
        </a>
<?php
            }
            else
            {
?>


<a class="btn btn-lg btn-primary" type="button" href="<?php echo Version::urlBetaPrefix(); ?>/login.php">
            <i class="fa-solid fa-user"></i>
        </a>
<?php
            }
        ?>


    </div>
</nav>

<!--DESKTOP NAVBAR-->
<nav class="container d-md-block d-sm-none d-none fixed-top navbar navbar-expand bg-primary" aria-label="Second navbar example" data-bs-theme="dark">
    <div class="container">
        <a class="navbar-brand kk-logo-desktop" href="<?php echo Version::urlBetaPrefix(); ?>/">
            <img class="kk-logo" src="https://kickback-kingdom.com/assets/images/logo-kk.png" /></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarsExample02"
            aria-controls="navbarsExample02" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarsExample02" >
            <ul class="navbar-nav me-auto">
                <li class="nav-item dropdown" data-bs-theme="light">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <i class="fa-solid fa-users"></i> Community
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo Version::urlBetaPrefix(); ?>/town-square.php"><i class="nav-icon fa-regular fa-address-card"></i> Town Square</a></li>
                        <!--<li><a class="dropdown-item" href="<?php echo Version::urlBetaPrefix(); ?>/challenges.php"><i class="nav-icon fa-solid fa-trophy"></i> Ranked Challenges</a></li>-->
                        <li><a class="dropdown-item" href="<?php echo Version::urlBetaPrefix(); ?>/blogs.php"><i class="nav-icon fa-solid fa-newspaper"></i> Blogs</a></li>
                        <li><a class="dropdown-item" href="<?php echo Version::urlBetaPrefix(); ?>/games.php"><i class="nav-icon fa-solid fa-gamepad"></i> Games & Activities</a></li>
                        <li><a class="dropdown-item" href="<?php echo Version::urlBetaPrefix(); ?>/servers.php"><i class="nav-icon fa-solid fa-server"></i> Community Servers</a></li>

                        <li><a class="dropdown-item" href="<?php echo Version::urlBetaPrefix(); ?>/guild-halls.php"><i class="nav-icon fa-solid fa-landmark"></i> Guild Halls</a></li>
                        <!--<li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo Version::urlBetaPrefix(); ?>/adventurers-guild.php"><i class="nav-icon fa-solid fa-person-hiking"></i> Adventurers Guild</a></li>
                        <li><a class="dropdown-item" href="<?php echo Version::urlBetaPrefix(); ?>/merchants-guild.php"><i class="nav-icon fa-solid fa-sack-dollar"></i> Merchants Guild</a></li>
                        <li><a class="dropdown-item" href="<?php echo Version::urlBetaPrefix(); ?>/craftsmen-guild.php"><i class="nav-icon fa-solid fa-hammer"></i> Craftsmen Guild</a></li>
                        <li><a class="dropdown-item" href="<?php echo Version::urlBetaPrefix(); ?>/apprentices-guild.php"><i class="nav-icon fa-solid fa-user-graduate"></i> Apprentices Guild</a></li>
                        <li><a class="dropdown-item" href="<?php echo Version::urlBetaPrefix(); ?>/stewards-guild.php"><i class="nav-icon fa-solid fa-person-digging"></i> Stewards Guild</a></li>-->
                    </ul>
                </li>
                <li class="nav-item dropdown" data-bs-theme="light">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <i class="nav-icon fa-solid fa-chess"></i> About Us
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="<?php echo Version::urlBetaPrefix(); ?>/business-plan.php"><i class="nav-icon fa-regular fa-file-lines"></i> Business Plan</a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?php echo Version::urlBetaPrefix(); ?>/analytics.php"><i class="nav-icon fa-solid fa-chart-line"></i> Analytics</a>
                        </li>
                    </ul>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item d-none d-xl-block">
                    <a class="btn btn-primary" type="button" href="https://discord.gg/NhTZwaWfqu" target="_blank">
                        <i class="fa-brands fa-discord"></i>
                    </a>
                </li>
                <li class="nav-item d-block d-xl-none">
                    <a class="btn btn-primary" type="button"  data-bs-toggle="offcanvas" data-bs-target="#offcanvasMenuRightDiscord" aria-controls="offcanvasMenuRightDiscord" aria-label="Toggle navigation">
                        <i class="fa-brands fa-discord"></i>
                    </a>
                </li>

                <?php if (Session::isLoggedIn()) { ?>
                    
                    <li class="nav-item">
                        <button class="btn btn-primary position-relative" type="button" data-bs-toggle="offcanvas"
                            data-bs-target="#offcanvasMenuRightTasks" aria-controls="offcanvasMenuRightTasks"
                            aria-label="Toggle navigation" style="background-color: transparent !important; border-color: transparent;">
                            <i class="fa-solid fa-scroll"></i>
                            <?php if ($totalUnclaimedTasks > 0) { ?>
                            <span class="badge bg-secondary position-absolute top-0 start-100 translate-middle rounded-pill"
                                style="z-index: 2;">
                                <?= $totalUnclaimedTasks; ?>
                                <span class="visually-hidden">unclaimed rewards</span>
                            </span>
                            <?php } ?>

                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="btn btn-primary position-relative" type="button" data-bs-toggle="offcanvas"
                            data-bs-target="#offcanvasMenuRightShoppingCart" aria-controls="offcanvasMenuRightShoppingCart"
                            aria-label="Toggle navigation">
                            <i class="fa-solid fa-cart-shopping"></i>
                            <?php if (Kickback\Services\Session::isAdmin()) { ?>
                            <span class="badge bg-danger position-absolute top-0 start-100 translate-middle rounded-pill">
                                99+
                                <span class="visually-hidden">unread messages</span>
                            </span>
                            <?php } ?>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="btn btn-primary position-relative" type="button" data-bs-toggle="offcanvas"
                            data-bs-target="#offcanvasMenuRight" aria-controls="offcanvasMenuRight"
                            aria-label="Toggle navigation" style="background-color: transparent !important; border-color: transparent;">
                        <i class="fa-solid fa-bell"></i>
                            <span class="badge bg-secondary position-absolute top-0 start-100 translate-middle rounded-pill">
                                <?php echo count($activeAccountInfo->notifications); ?>
                                <span class="visually-hidden">unread messages</span>
                            </span>
                        </button>
                    </li>

                <?php } ?>
                <li class="nav-item dropdown">
                    <a class="btn dropdown-toggle btn-primary" type="button" style="height: 38px;background-color: transparent !important;border-color: transparent;" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" >
                        <?php

                            if (Session::isLoggedIn())
                            {
                        ?>
                        <img class="rounded-circle" style="height: 100%;width: auto;" src="<?= Kickback\Services\Session::getCurrentAccount()->profilePictureURL(); ?>"/>
                        <?php
                            }
                            else
                            {
                        ?>
                            <img class="rounded-circle" style="height: 100%;width: auto;" src=""/>
                        <?php
                            }
                        ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" data-bs-theme="light">
                        <?php

                            if (Session::isLoggedIn())
                            {
                        ?>

                        <li>
                            <a class="dropdown-item" href="<?php echo Version::urlBetaPrefix(); ?>/u/<?php echo Kickback\Services\Session::getCurrentAccount()->username; ?>">
                                <i class="nav-icon fa-solid fa-user"></i> Profile
                            </a>
                        </li>
                        <?php if (Kickback\Services\Session::isAdmin()) { ?>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?php echo Version::urlBetaPrefix(); ?>/admin-dashboard.php">
                                <i class="nav-icon fa-solid fa-shield-halved"></i> Admin Dashboard
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" onclick="OpenSelectAccountModal(null,'UseDelegateAccess')">
                                <i class="nav-icon fa-solid fa-eye"></i> Delegate Access
                            </a>
                        </li>
                        <?php } ?>
                        <?php if (Kickback\Services\Session::isSteward()) { ?>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#treasureHuntHideObjectModal">
                                <i class="nav-icon fa-solid fa-treasure-chest"></i> Hide Treasure
                            </a>

                        </li>
                        <?php } ?>
                        <?php if (Kickback\Services\Session::isDelegatingAccess()) { ?>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?php Version::urlBetaPrefix(); ?>/?exitDelegate=1" onclick="">
                                <i class="nav-icon fa-solid fa-eye-low-vision"></i> Exit Delegate Access
                            </a>
                        </li>
                        
                        <?php } ?>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?php echo Version::urlBetaPrefix(); ?>/login.php">
                                <i class="nav-icon fa-solid fa-right-from-bracket"></i> Logout
                            </a>
                        </li>

                        <?php
                            }
                            else
                            {
                        ?>

                        <li>
                            <a class="dropdown-item" href="<?php echo Version::urlBetaPrefix(); ?>/login.php">
                                <i class="nav-icon fa-solid fa-right-from-bracket"></i> Login
                            </a>
                        </li>
                        <?php
                            }
                        ?>
                        
                        <li id="btnEnableBeta">
                            <a class="dropdown-item" href="#" onclick="enableBeta()">
                                <i class="nav-icon fa-solid fa-toggle-off"></i> Enable Beta
                            </a>
                        </li>
                        <li id="btnDisableBeta">
                            <a class="dropdown-item" href="#" onclick="disableBeta()">
                            <i class="nav-icon fa-solid fa-toggle-on"></i> Disable Beta
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>

        </div>
    </div>
</nav>
<?php } ?>
