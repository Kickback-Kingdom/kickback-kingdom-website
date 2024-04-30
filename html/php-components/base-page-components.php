<?php 

require("base-page-loading-overlay.php"); 


$mediaDirsResp = GetMediaDirectories();
$mediaDirs = $mediaDirsResp->Data;
?>

<!--CONFETTI-->
<div class="confetti-box">
    <div class="js-container-confetti" style="width:100vw; height:100vh;">

    </div>
</div>

<?php if(IsLoggedIn()) { ?>
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
                            <img id="imgItem" class="img-fluid" src="" style="-khtml-user-select: none;-o-user-select: none;-moz-user-select: none;-webkit-user-select: none;user-select: none;position: absolute;margin: auto;top: 0;bottom: 0;left: 0;right: 0;left: 0;z-index: 1;width: 250px;height: 250px;">
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
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Back</button>
        <button type="button" class="btn bg-ranked-1" onclick="">Select</button>
      </div>
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
        <?php if(IsArtist()) { ?><button type="button" class="btn btn-primary" onclick="OpenMediaUploadModal()">Upload Media</button><?php } ?>
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Back</button>
        <button type="button" class="btn bg-ranked-1" onclick="AcceptSelectedMedia()">Select</button>
      </div>
    </div>
  </div>
</div>

<?php if(IsArtist()) { ?>
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
                    <h1 class="display-6 mb-3">Step 3 - Edit Metadata</h1>
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
                                                echo "<option value='{$dir["Directory"]}'>{$dir["Directory"]}</option>";
                                            }
                                        ?>
                                    </select>
                                    <?php if (IsAdmin()) { ?>
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


<!--NOTIFICATIONS-->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasMenuRight" aria-labelledby="offcanvasMenuRightLabel">
    <div class="offcanvas-header bg-primary">
        <h5 class="offcanvas-title text-white" id="offcanvasMenuRightLabel">Notifications</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"
            aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <?php 
        
            if (IsLoggedIn() && !is_null($activeAccountInfo->notifications))
            {
                
                for ($i=0; $i < count($activeAccountInfo->notifications); $i++) { 
                    # code...
                    $not = $activeAccountInfo->notifications[$i];

                    ?>
                    <div class="toast show mb-1" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="bg-primary text-bg-primary toast-header">
                            <strong class="me-auto">
                            <?php

                            switch ($not["Type"]) {
                                case "Quest Review":
                                case "Thanks For Hosting":
                                    echo '<i class="fa-solid fa-gift"></i> Pending Rewards';
                                    break;
                                case "Prestige":
                                    echo "New Prestige";
                                    break;

                                case "Quest Reviewed":
                                    echo '<i class="fa-solid fa-star"></i> Quest Reviewed';
                                    break;

                                case "Quest In Progress":
                                    echo '<i class="fa-solid fa-spinner fa-spin"></i> Quest In Progress';
                                    break;
                                default:
                                    echo "{".$not["Type"]."}";
                                    break;
                            }

                            ?>    
                            </strong>
                            <small><?php echo timeElapsedString($not["date"]); ?></small>
                            <?php

                            switch ($not["Type"]) {
                                case "Quest Review":
                                case "Quest In Progress":
                                case "Thanks For Hosting":
                                case "Quest Reviewed":
                                    break;

                                default:
                                    echo '<button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>';
                                    break;
                            }

                            ?>
                        </div>
                        <div class="toast-body">
                            <?php
                                switch ($not["Type"]) {
                                    case "Quest Review":
                                        echo "<strong>Thanks for participating</strong> in <a href='".$urlPrefixBeta."/q/".$not["locator"]."'>".$not["name"]."</a>. Please review your experience so we can build better quests for you in the future. Thanks! ".'<i class="fa-regular fa-face-smile-beam"></i>';
                                        break;

                                    
                                    case "Thanks For Hosting":
                                        echo "<strong>Thanks for hosting</strong> <a href='".$urlPrefixBeta."/q/".$not["locator"]."'>".$not["name"]."</a>! Once a few of your participants send in their reviews you will recieve your host reward. In the meantime enjoy your quest rewards. Thanks! ".'<i class="fa-regular fa-face-smile-beam"></i>';
                                        break;

                                    case "Prestige":
                                        echo "<a class='username' href='".$urlPrefixBeta."/u/".$not["locator"]."'>".$not["name"]."</a> used a prestige token on you.";
                                        break;

                                    case "Quest In Progress":
                                        echo "You are participating in <a href='".$urlPrefixBeta."/q/".$not["locator"]."'>".$not["name"]."</a> which is currently in progress. Please check in often to make sure no one is waiting on you. Thanks! ".'<i class="fa-regular fa-face-smile-beam"></i>';
                                        break;

                                    
                                    case "Quest Reviewed":
                                        echo "<a class='username' href='".$urlPrefixBeta."/u/".$not["from_name"]."'>".$not["from_name"]."</a> just left a review for your quest - <a href='".$urlPrefixBeta."/q/".$not["locator"]."'>".$not["name"]."</a>";
                                        break;

                                    default:
                                        echo "Unknown Event Occurred";
                                        break;
                                }
                            ?>

                        </div>
                        <?php 
                            switch ($not["Type"]) {
                                case "Quest Review":
                                    ?>
                                        <div class="toast-body"><button class="bg-ranked-1 btn btn-sm" onclick="LoadQuestReviewModal(<?php echo $i ?>);"><i class="fa-solid fa-gift"></i> Collect Rewards</button></div>
                                    <?php
                                    break;
                                case "Thanks For Hosting":
                                    ?>
                                        <form method="POST">
                                            <input type="hidden" name="quest-notifications-thanks-for-hosting-quest-id" value="<?php echo $not["quest_id"]; ?>"/>
                                            <div class="toast-body">
                                                <button type="submit" name="submit-notifications-thanks-for-hosting" class="bg-ranked-1 btn btn-sm"><i class="fa-solid fa-gift"></i> Collect Rewards</button>
                                            </div>
                                        </form>
                                    <?php
                                    break;
                                
                                case "Quest Reviewed":
                                    ?> 
                                        <!--<div class="toast-body"><a class="bg-ranked-1 btn btn-sm" href="#">View</a></div>-->
                                    <?php
                                    break;
                                case "Prestige":
                                    ?> 
                                        <!--<div class="toast-body"><a class="bg-ranked-1 btn btn-sm" href="#">View</a></div>-->
                                    <?php
                                    break;
                                    
                                default:
                                    # code...
                                    break;
                            }
                        ?>
                    </div>

                    <?php
                } // for ($i=0; $i < count($activeAccountInfo->notifications); $i++)
            } // if (IsLoggedIn() && !is_null($activeAccountInfo->notifications))


        ?>
        <!--<div class="toast show mb-1" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <img src="..." class="rounded me-2" alt="...">
                <strong class="me-auto">Bootstrap</strong>
                <small>11 mins ago</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                Hello, world! This is a toast message.
            </div>
        </div>-->
        
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
            <img id="inventoryItemImage" src="" class="img-fluid" alt="Item Image">
            <p class="float-end" style="font-size: .8em;">Artwork by <a class="username" id="inventoryItemArtist" href="#">Artist: Artist Name</a></p>
          </div>
          <div class="col-12 col-md-6">
            
            <h6>Date Created</h6>
            <p id="inventoryItemDate">Release Date: Date</p>
                <h6>Description</h6>
              <p id="inventoryItemDescription">Item Description</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>


<!-- ERROR MODAL -->
<div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header text-bg-danger">
        <h1 class="modal-title fs-5" id="errorModalLabel">Modal title</h1>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p id="errorModalMessage"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn bg-ranked-1" data-bs-dismiss="modal">Okay</button>
      </div>
    </div>
  </div>
</div> 

<!-- SUCCESS MODAL -->
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="successModalLabel">Modal title</h1>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p id="successModalMessage"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn bg-ranked-1" data-bs-dismiss="modal">Okay</button>
      </div>
    </div>
  </div>
</div>

<!--LOADING MODAL-->
<div class="modal fade" id="loadingModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"  aria-labelledby="loadingModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        
      <div class="d-flex align-items-center flex-fill">
        <h5 class="modal-title" id="loadingModalLabel">Loading...</h5>        
        
        <i class="fa-solid fa-slash fa-spin ms-auto"></i>
        </div>
      </div>
      <div class="modal-body">
        <div class="progress" id="loadingModalProgress" role="progressbar" aria-label="Animated striped example" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100">
            <div  id="loadingModalProgressBar" class="progress-bar progress-bar-striped progress-bar-animated" style="width: 75%"></div>
        </div>
      </div> 
      <div class="modal-footer">
        Please Wait...
      </div>
    </div>
  </div>
</div>



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
                <a class="nav-link mobile-menu-item active" aria-current="page" href="<?php echo $urlPrefixBeta; ?>/"><i class="nav-icon fa-solid fa-house"></i> Home <i class="fa-solid fa-chevron-right mobile-menu-item-arrow"></i></a>
            </li>
            <li class="nav-item">
                <a class="nav-link mobile-menu-item" href="<?php echo $urlPrefixBeta; ?>/town-square.php"><i class="nav-icon fa-regular fa-address-card"></i> Town Square <i class="fa-solid fa-chevron-right mobile-menu-item-arrow"></i></a>
            </li>
            <li class="nav-item">
                <a class="nav-link mobile-menu-item" href="<?php echo $urlPrefixBeta; ?>/forums.php"><i class="nav-icon fa-solid fa-pen-to-square"></i> Forums <i class="fa-solid fa-chevron-right mobile-menu-item-arrow"></i></a>
            </li>
            <li class="nav-item">
                <a class="nav-link mobile-menu-item" href="<?php echo $urlPrefixBeta; ?>/challenges.php"><i class="nav-icon fa-solid fa-trophy"></i> Ranked Challenges <i class="fa-solid fa-chevron-right mobile-menu-item-arrow"></i></a>
            </li>
            <li class="nav-item">
                <a class="nav-link mobile-menu-item" href="<?php echo $urlPrefixBeta; ?>/blogs.php"><i class="nav-icon fa-solid fa-newspaper"></i> Blogs <i class="fa-solid fa-chevron-right mobile-menu-item-arrow"></i></a>
            </li>
            <li class="nav-item">
                <a class="nav-link mobile-menu-item" href="<?php echo $urlPrefixBeta; ?>/games.php"><i class="nav-icon fa-solid fa-gamepad"></i> Games & Activities<i class="fa-solid fa-chevron-right mobile-menu-item-arrow"></i></a>
            </li>
            <li class="nav-item">
                <a class="nav-link mobile-menu-item" href="<?php echo $urlPrefixBeta; ?>/business-plan.php"><i class="nav-icon fa-regular fa-file-lines"></i> Business Plan <i class="fa-solid fa-chevron-right mobile-menu-item-arrow"></i></a>
            </li>
            <li class="nav-item">
                <a class="nav-link mobile-menu-item" href="<?php echo $urlPrefixBeta; ?>/project-roadmaps.php"><i class="nav-icon fa-solid fa-road"></i> Project Roadmaps <i class="fa-solid fa-chevron-right mobile-menu-item-arrow"></i></a>
            </li>
            <li class="nav-item">
                <a class="nav-link mobile-menu-item" href="<?php echo $urlPrefixBeta; ?>/castles.php"><i class="nav-icon fa-brands fa-fort-awesome"></i> Castles <i class="fa-solid fa-chevron-right mobile-menu-item-arrow"></i></a>
            </li>
            <li class="nav-item">
                <a class="nav-link mobile-menu-item" href="<?php echo $urlPrefixBeta; ?>/guild-halls.php"><i class="nav-icon fa-solid fa-signs-post"></i> Guild Halls <i class="fa-solid fa-chevron-right mobile-menu-item-arrow"></i></a>
            </li>
            <li class="nav-item">
                <a class="nav-link mobile-menu-item" href="<?php echo $urlPrefixBeta; ?>/admin-dashboard.php"><i class="nav-icon fa-solid fa-shield-halved"></i> Admin Dashboard <i class="fa-solid fa-chevron-right mobile-menu-item-arrow"></i></a>
            </li>
            <?php

            if (IsLoggedIn())
            {
                ?>


<li class="nav-item">
                <a class="nav-link mobile-menu-item" href="<?php echo $urlPrefixBeta; ?>/login.php"><i class="nav-icon fa-solid fa-right-from-bracket"></i> Logout <i class="fa-solid fa-chevron-right mobile-menu-item-arrow"></i></a>
            </li>
<?php
            }

            ?>
        </ul>
    </div>
</div>

<!--MOBILE CENTER BUTTON-->
<a class="btn btn-secondary btn-lg rounded-top-pill mobile-bar-btn-center d-block d-md-none" type="button" href="<?php echo $urlPrefixBeta; ?>/challenges.php">
    <i class="fa-solid fa-trophy"></i>
</a>

<!--MOBILE TOP BAR-->
<nav class="d-md-none d-sm-block fixed-top navbar bg-primary" data-bs-theme="dark">
    <div class="container-fluid">
        <button class="btn btn-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasMenu"
            aria-controls="offcanvasMenu" aria-label="Toggle navigation">
            <i class="fa-solid fa-bars"></i>
        </button>
        <a class="me-0 me-lg-2 navbar-brand p-0 mobile-navbar-logo" href="<?php echo $urlPrefixBeta; ?>/" aria-label="Bootstrap">
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
        <a class="btn btn-lg btn-primary" type="button" href="<?php echo $urlPrefixBeta; ?>/schedule.php">
            <i class="fa-solid fa-calendar-days"></i>
        </a>

        <a class="btn btn-lg btn-primary" type="button" href="<?php echo $urlPrefixBeta; ?>/town-square.php">
            <i class="fa-solid fa-users"></i>
        </a>

        <button class="btn btn-primary btn-lg" type="button">
            <i class="fa-solid fa-trophy"></i>
        </button>

        <button class="btn btn-lg btn-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasMenuRightDiscord" aria-controls="offcanvasMenuRightDiscord" aria-label="Toggle navigation">
            <i class="fa-brands fa-discord"></i>
        </button>

        <?php 

            if (IsLoggedIn())
            {
                ?>


        <a class="btn btn-lg btn-primary" type="button" href="<?php echo $urlPrefixBeta; ?>/u/<?php echo $_SESSION["account"]["Username"]; ?>">
            <i class="fa-solid fa-user"></i>
        </a>
<?php
            }
            else
            {
?>


<a class="btn btn-lg btn-primary" type="button" href="<?php echo $urlPrefixBeta; ?>/login.php">
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
        <a class="navbar-brand kk-logo-desktop" href="<?php echo $urlPrefixBeta; ?>/">
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
                        <li><a class="dropdown-item" href="<?php echo $urlPrefixBeta; ?>/town-square.php"><i class="nav-icon fa-regular fa-address-card"></i> Town Square</a></li>
                        <li><a class="dropdown-item" href="<?php echo $urlPrefixBeta; ?>/forums.php"><i class="nav-icon fa-solid fa-pen-to-square"></i> Forums</a></li>
                        <li><a class="dropdown-item" href="<?php echo $urlPrefixBeta; ?>/challenges.php"><i class="nav-icon fa-solid fa-trophy"></i> Ranked Challenges</a></li>
                        <li><a class="dropdown-item" href="<?php echo $urlPrefixBeta; ?>/blogs.php"><i class="nav-icon fa-solid fa-newspaper"></i> Blogs</a></li>
                        <li><a class="dropdown-item" href="<?php echo $urlPrefixBeta; ?>/games.php"><i class="nav-icon fa-solid fa-gamepad"></i> Games & Activities</a></li>
                        <li><a class="dropdown-item" href="<?php echo $urlPrefixBeta; ?>/guild-halls.php"><i class="nav-icon fa-solid fa-landmark"></i> Guild Halls</a></li>
                        <!--<li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo $urlPrefixBeta; ?>/adventurers-guild.php"><i class="nav-icon fa-solid fa-person-hiking"></i> Adventurers Guild</a></li>
                        <li><a class="dropdown-item" href="<?php echo $urlPrefixBeta; ?>/merchants-guild.php"><i class="nav-icon fa-solid fa-sack-dollar"></i> Merchants Guild</a></li>
                        <li><a class="dropdown-item" href="<?php echo $urlPrefixBeta; ?>/craftsmen-guild.php"><i class="nav-icon fa-solid fa-hammer"></i> Craftsmen Guild</a></li>
                        <li><a class="dropdown-item" href="<?php echo $urlPrefixBeta; ?>/apprentices-guild.php"><i class="nav-icon fa-solid fa-user-graduate"></i> Apprentices Guild</a></li>
                        <li><a class="dropdown-item" href="<?php echo $urlPrefixBeta; ?>/stewards-guild.php"><i class="nav-icon fa-solid fa-person-digging"></i> Stewards Guild</a></li>-->
                    </ul>
                </li>
                <li class="nav-item dropdown" data-bs-theme="light">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <i class="nav-icon fa-solid fa-chess"></i> About Us
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="<?php echo $urlPrefixBeta; ?>/business-plan.php"><i class="nav-icon fa-regular fa-file-lines"></i> Business Plan</a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?php echo $urlPrefixBeta; ?>/project-roadmaps.php"><i class="nav-icon fa-solid fa-road"></i> Project Roadmaps</a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?php echo $urlPrefixBeta; ?>/castles.php"><i class="nav-icon fa-brands fa-fort-awesome"></i> Castles</a>
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
                <li class="nav-item">
                    <a class="btn btn-primary" type="button" href="<?php echo $urlPrefixBeta; ?>/schedule.php">
                        <i class="fa-solid fa-calendar-days"></i>
                    </a>
                </li>
                <?php 
                if (IsLoggedIn())
                {
                    ?>
                <li class="nav-item">
                    <button class="btn btn-primary" type="button" data-bs-toggle="offcanvas"
                        data-bs-target="#offcanvasMenuRight" aria-controls="offcanvasMenuRight"
                        aria-label="Toggle navigation">
                        <i class="fa-solid fa-bell"></i>
                        <span
                            class="bg-secondary border border-light p-1 position-absolute rounded-circle top-50 translate-middle">
                            <span class="visually-hidden">New alerts</span>
                        </span>
                    </button>
                </li>
                <?php 

                }

                ?>
                <li class="nav-item dropdown">
                    <a class="btn dropdown-toggle btn-primary" type="button" style="height: 38px;" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php

                            if (IsLoggedIn())
                            {
                        ?>
                        <img class="rounded-circle" style="height: 100%;width: auto;" src="/assets/media/<?php echo GetAccountProfilePicture($_SESSION["account"]); ?>"/>
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

                            if (IsLoggedIn())
                            {
                        ?>

                        <li>
                            <a class="dropdown-item" href="<?php echo $urlPrefixBeta; ?>/u/<?php echo $_SESSION["account"]["Username"]; ?>">
                                <i class="nav-icon fa-solid fa-user"></i> Profile
                            </a>
                        </li>
                        <?php if (IsAdmin()) { ?>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?php echo $urlPrefixBeta; ?>/admin-dashboard.php">
                                <i class="nav-icon fa-solid fa-shield-halved"></i> Admin Dashboard
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" onclick="OpenSelectAccountModal(null,'UseDelegateAccess')">
                                <i class="nav-icon fa-solid fa-eye"></i> Delegate Access
                            </a>
                        </li>
                        <?php } ?>
                        <?php if (IsDelegatingAccess()) { ?>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?php $urlPrefixBeta; ?>/?exitDelegate=1" onclick="">
                                <i class="nav-icon fa-solid fa-eye-low-vision"></i> Exit Delegate Access
                            </a>
                        </li>
                        
                        <?php } ?>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?php echo $urlPrefixBeta; ?>/login.php">
                                <i class="nav-icon fa-solid fa-right-from-bracket"></i> Logout
                            </a>
                        </li>

                        <?php
                            }
                            else
                            {
                        ?>

                        <li>
                            <a class="dropdown-item" href="<?php echo $urlPrefixBeta; ?>/login.php">
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
