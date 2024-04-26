<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");



if (isset($_GET['id']))
$profile = GetAccountById($_GET['id']);

if (isset($_GET['u']) && !isset($profile))
$profile = GetAccountByUsername($_GET['u']);

$profile = $profile->Data;

$thisProfile = $profile;

if (!isset($hasError))
    $hasError = false;
if (!isset($hasSuccess))
    $hasSuccess = false;
$isMyProfile = false;
if (IsLoggedIn())
{
    $isMyProfile = ($profile["Id"] == $_SESSION["account"]["Id"]);

    if ($isMyProfile)
    {
        
    }
    else
    {

        if (isset($_POST["review-submit"]))
        {
            
            $rating = $_POST["review-rating"];
            if ($rating == "-1")
                $commend = 0;
    
            if ($rating == "1")
                $commend = 1;
    
            $usePrestigeTokenResp = UsePrestigeToken($_SESSION['account']['Id'],$profile["Id"],$commend,$_POST["review-message"]);
            if ($usePrestigeTokenResp->Success == false)
            {
                $hasError = true;
                $errorMessage = $usePrestigeTokenResp->Message;
            }
            else
            {
                $hasSuccess = true;
                $successMessage= $usePrestigeTokenResp->Message;
            }
        }
    }
}


$badgesResp = GetBadgesByAccountId($profile['Id']);
$badges = $badgesResp->Data;

$prestigeResp = GetAccountPrestige($profile['Id']);

$prestigeReviews = $prestigeResp->Data;

$prestigeNet = GetAccountPrestigeValue($prestigeReviews);

/*$remainingPrestigeTokensResp = GetPrestigeTokensRemaining($profile['Id']);
$remainingPrestigeTokens = $remainingPrestigeTokensResp->Data["PrestigeTokens"];


$unusedTicketsResp = GetTotalUnusedRaffleTickets($profile['Id']);
$unusedTickets = $unusedTicketsResp->Data;
*/

$accountInventoryResp = GetAccountInventory($profile["Id"]);
$accountInventory = $accountInventoryResp->Data;

$accountActivityResp = GetAccountActivity($profile["Id"]);
$accountActivity = $accountActivityResp->Data;

$itemInfos = [];
foreach ($accountInventory as $accountInventoryItem) {
    array_push($itemInfos, ConvertIntoItemInformation($accountInventoryItem));
}
$itemInformationJSON = json_encode($itemInfos);

$activeTab = 'active';
$activeTabPage = 'active show';
?>

<!DOCTYPE html>
<html lang="en">


<?php require("php-components/base-page-head.php"); ?>

<body class="bg-body-secondary container p-0">
    
    <?php 
    
    require("php-components/base-page-components.php"); 
    
    
    ?>

    <div>
        <!--TOP BANNER-->
        <div class="d-none d-md-block w-100 ratio" style="--bs-aspect-ratio: 26%; margin-top: 56px">
            <img src="/assets/images/kk-1.jpg" class="" />
            <img class="img-fluid img-thumbnail" src="/assets/media/<?php echo GetAccountProfilePicture($profile); ?>" style="width: auto;height: 90%;top: 5%;left: 5%;">
        </div>
        <div class="d-block d-md-none w-100 ratio" style="margin-top: 56px; --bs-aspect-ratio: 46.3%;">
            <img src="/assets/images/kk-2.jpg" />
            <img class="img-fluid img-thumbnail" src="/assets/media/<?php echo GetAccountProfilePicture($profile); ?>" style="width: auto;height: 90%;top: 5%;left: 5%;">
        </div>
    </div>

    <!--REVIEW MODAL-->
    <form method="POST">
        <div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" style="display: none;" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="reviewModalLabel">Write about:</h5>
                        <button class="btn-close btn-close-white" type="button" data-bs-dismiss="modal" aria-label="Close" data-bs-original-title="" title=""></button>
                    </div>
                    <div class="modal-body">  
                        <div class="mb-3">
                            <label class="col-form-label" for="textareaReviewModal">Message:</label>
                            <textarea class="form-control" id="textareaReviewModal" name="review-message" required="" maxlength="500" style="height: 103px;"></textarea>
                            <input id="review-rating" type="hidden" name="review-rating" required=""/>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <input id="plusButton" class="badge" onclick="rate(1)" type="button" value="+1" required style="display:block; background-color: rgb(143, 151, 178);"/>
                        <input id="minusButton" class="badge" onclick="rate(2)" type="button" value="-1" required style="display:block; background-color: rgb(143, 151, 178);"/>
                        <button class="btn btn-primary" type="button" data-bs-dismiss="modal" data-bs-original-title="" title="">Close</button>
                        <input class="btn bg-ranked-1" name="review-submit" onclick="validateSelection(event)" type="submit" value="Submit"/>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!--EQUIPMENT MODAL-->
    <form method="POST">
            
        <input id="equipment-account-id" type="hidden" name="equipment-account-id" required="" value="<?php echo $profile["Id"]; ?>"/>
        <input id="equipment-avatar" type="hidden" name="equipment-avatar" required=""/>
        <input id="equipment-pc-card" type="hidden" name="equipment-pc-card" required=""/>
        <input id="equipment-banner" type="hidden" name="equipment-banner" required=""/>
        <input id="equipment-background" type="hidden" name="equipment-background" required=""/>
        <input id="equipment-charm" type="hidden" name="equipment-charm" required=""/>
        <input id="equipment-pet" type="hidden" name="equipment-pet" required=""/>

        <div class="modal fade" id="equipmentModal" tabindex="-1" aria-labelledby="equipmentModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="equipmentModalLabel">Equipment</h1>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12 col-lg-6">
                                <div class="card border-dark mb-3">
                                    
                                    <div class="row g-0">
                                        <div class="col-4">
                                            <button type="button"  class="btn btn-dark p-0" data-bs-target="#selectAvatarModal" data-bs-toggle="modal"><img id="equipment-preview-avatar" src="/assets/media/menu/empty_slot.jpg" class="img-fluid"></button>
                                        </div>
                                        <div class="col-8">
                                            
                                            <div class="card-header">Avatar</div>
                                            <div class="card-body pt-1">
                                                <p class="card-text">This is how others will see you.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-lg-6">
                                <div class="card border-dark mb-3">
                                    
                                    <div class="row g-0">
                                        <div class="col-4">
                                            <button type="button" data-bs-target="#selectPCBorderModal" data-bs-toggle="modal" class="btn btn-dark p-0"><img src="/assets/media/menu/empty_slot.jpg" class="img-fluid"></button>
                                        </div>
                                        <div class="col-8">
                                            
                                            <div class="card-header">Player Card Border</div>
                                            <div class="card-body pt-1">
                                                <p class="card-text">This is a border that will be on top of your player card.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                            </div>
                            <div class="col-12 col-lg-6">
                                <div class="card border-dark mb-3">
                                    
                                    <div class="row g-0">
                                        <div class="col-4">
                                            <button type="button" data-bs-target="#selectBannerModal" data-bs-toggle="modal" class="btn btn-dark p-0"><img src="/assets/media/menu/empty_slot.jpg" class="img-fluid"></button>
                                        </div>
                                        <div class="col-8">
                                            
                                            <div class="card-header">Banner</div>
                                            <div class="card-body pt-1">
                                                <p class="card-text">This is what shows at the top of your profile page.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            
                            </div>
                            <div class="col-12 col-lg-6">
                                <div class="card border-dark mb-3">
                                    
                                    <div class="row g-0">
                                        <div class="col-4">
                                            <button type="button" data-bs-target="#selectBackgroundModal" data-bs-toggle="modal" class="btn btn-dark p-0"><img src="/assets/media/menu/empty_slot.jpg" class="img-fluid"></button>
                                        </div>
                                        <div class="col-8">
                                            
                                            <div class="card-header">Background</div>
                                            <div class="card-body pt-1">
                                                <p class="card-text">This is the background of your profile page.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-lg-6">
                                <div class="card border-dark mb-3">
                                    
                                    <div class="row g-0">
                                        <div class="col-4">
                                            <button type="button" data-bs-target="#selectCharmModal" data-bs-toggle="modal" class="btn btn-dark p-0"><img src="/assets/media/menu/empty_slot.jpg" class="img-fluid"></button>
                                        </div>
                                        <div class="col-8">
                                            
                                            <div class="card-header">Charm</div>
                                            <div class="card-body pt-1">
                                                <p class="card-text">Can effect your experiences in the kingdom</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-lg-6">
                                <div class="card border-dark mb-3">
                                    
                                    <div class="row g-0">
                                        <div class="col-4">
                                            <button type="button" data-bs-target="#selectPetModal" data-bs-toggle="modal" class="btn btn-dark p-0"><img src="/assets/media/menu/empty_slot.jpg" class="img-fluid"></button>
                                        </div>
                                        <div class="col-8">
                                            
                                            <div class="card-header">Pet</div>
                                            <div class="card-body pt-1">
                                                <p class="card-text">Can effect your experiences in the kingdom</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                        
                        <?php 

                        if ($isMyProfile)
                        {

                        ?>
                        <input type="submit" name="submit-equipment" class="btn bg-ranked-1" value="Save changes" />
                        <?php 
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </form>
    

    <!--SELECT AVATAR MODAL-->
    <div class="modal fade" id="selectAvatarModal" tabindex="-1" aria-labelledby="selectAvatarModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="selectAvatarModalLabel">Select an Avatar</h1>
                <button type="button" class="btn-close btn-close-white"  data-bs-target="#equipmentModal" data-bs-toggle="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <!-- side-bar colleps block stat-->
                        <div class="inventory-grid-sm">
                            <?php
                            
                            // Show category title

                            foreach ($accountInventory as $accountInventoryItem) {
                                if ($accountInventoryItem["equipable"] && $accountInventoryItem["equipment_slot"] == "AVATAR")
                                {
                                ?>
                            <div class="inventory-item" onclick="SelectInventoryItemEquipment(<?php echo htmlspecialchars($accountInventoryItem['item_id']); ?>);"  data-bs-toggle="tooltip" data-bs-dismiss="modal" data-bs-placement="bottom" data-bs-title="<?php echo htmlspecialchars($accountInventoryItem["name"])?>">
                                <img src="/assets/media/<?php echo htmlspecialchars($accountInventoryItem["large_image"]); ?>" alt="Item Name 1">
                                <div class="item-count">x<?php echo htmlspecialchars($accountInventoryItem["amount"]); ?></div>
                            </div>
                            
                            <?php
                                }
                            }

                            ?>
                        </div>
                    </div>
                </div> 
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-target="#equipmentModal" data-bs-toggle="modal">Back</button>
            </div>
            </div>
        </div>
    </div>

    
    <!--SELECT PET MODAL-->
    <div class="modal fade" id="selectPetModal" tabindex="-1" aria-labelledby="selectPetModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="selectPetModalLabel">Select a Pet</h1>
                <button type="button" class="btn-close btn-close-white"  data-bs-target="#equipmentModal" data-bs-toggle="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <!-- side-bar colleps block stat-->
                        <div class="inventory-grid-sm">
                            
                        <?php
                            
                            // Show category title

                            foreach ($accountInventory as $accountInventoryItem) {
                                if ($accountInventoryItem["equipable"] && $accountInventoryItem["equipment_slot"] == "PET")
                                {
                                ?>
                            <div class="inventory-item" onclick="SelectInventoryItemEquipment(<?php echo htmlspecialchars($accountInventoryItem['item_id']); ?>);"  data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="<?php echo htmlspecialchars($accountInventoryItem["name"])?>">
                                <img src="/assets/media/<?php echo htmlspecialchars($accountInventoryItem["large_image"]); ?>" alt="Item Name 1">
                                <div class="item-count">x<?php echo htmlspecialchars($accountInventoryItem["amount"]); ?></div>
                            </div>
                            
                            <?php
                                }
                            }

                            ?>
                        </div>
                    </div>
                </div> 
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-target="#equipmentModal" data-bs-toggle="modal">Back</button>
            </div>
            </div>
        </div>
    </div>

    
    <!--SELECT CHARM MODAL-->
    <div class="modal fade" id="selectCharmModal" tabindex="-1" aria-labelledby="selectCharmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="selectCharmModalLabel">Select a Charm</h1>
                <button type="button" class="btn-close btn-close-white"  data-bs-target="#equipmentModal" data-bs-toggle="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <!-- side-bar colleps block stat-->
                        <div class="inventory-grid-sm">
                            
                        <?php
                            
                            // Show category title

                            foreach ($accountInventory as $accountInventoryItem) {
                                if ($accountInventoryItem["equipable"] && $accountInventoryItem["equipment_slot"] == "CHARM")
                                {
                                ?>
                            <div class="inventory-item" onclick="SelectInventoryItemEquipment(<?php echo htmlspecialchars($accountInventoryItem['item_id']); ?>);"  data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="<?php echo htmlspecialchars($accountInventoryItem["name"])?>">
                                <img src="/assets/media/<?php echo htmlspecialchars($accountInventoryItem["large_image"]); ?>" alt="Item Name 1">
                                <div class="item-count">x<?php echo htmlspecialchars($accountInventoryItem["amount"]); ?></div>
                            </div>
                            
                            <?php
                                }
                            }

                            ?>
                        </div>
                    </div>
                </div> 
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-target="#equipmentModal" data-bs-toggle="modal">Back</button>
            </div>
            </div>
        </div>
    </div>

    
    <!--SELECT PC BORDER MODAL-->
    <div class="modal fade" id="selectPCBorderModal" tabindex="-1" aria-labelledby="selectPCBorderModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="selectPCBorderModalLabel">Select a Player Card Border</h1>
                <button type="button" class="btn-close btn-close-white"  data-bs-target="#equipmentModal" data-bs-toggle="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <!-- side-bar colleps block stat-->
                        <div class="inventory-grid-sm">
                            
                        <?php
                            
                            // Show category title

                            foreach ($accountInventory as $accountInventoryItem) {
                                if ($accountInventoryItem["equipable"] && $accountInventoryItem["equipment_slot"] == "PC_BORDER")
                                {
                                ?>
                            <div class="inventory-item" onclick="SelectInventoryItemEquipment(<?php echo htmlspecialchars($accountInventoryItem['item_id']); ?>);"  data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="<?php echo htmlspecialchars($accountInventoryItem["name"])?>">
                                <img src="/assets/media/<?php echo htmlspecialchars($accountInventoryItem["large_image"]); ?>" alt="Item Name 1">
                                <div class="item-count">x<?php echo htmlspecialchars($accountInventoryItem["amount"]); ?></div>
                            </div>
                            
                            <?php
                                }
                            }

                            ?>
                        </div>
                    </div>
                </div> 
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-target="#equipmentModal" data-bs-toggle="modal">Back</button>
            </div>
            </div>
        </div>
    </div>

    
    <!--SELECT BANNER MODAL-->
    <div class="modal fade" id="selectBannerModal" tabindex="-1" aria-labelledby="selectBannerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="selectBannerModalLabel">Select a Banner</h1>
                <button type="button" class="btn-close btn-close-white"  data-bs-target="#equipmentModal" data-bs-toggle="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <!-- side-bar colleps block stat-->
                        <div class="inventory-grid-sm">
                            
                        <?php
                            
                            // Show category title

                            foreach ($accountInventory as $accountInventoryItem) {
                                if ($accountInventoryItem["equipable"] && $accountInventoryItem["equipment_slot"] == "BANNER")
                                {
                                ?>
                            <div class="inventory-item" onclick="SelectInventoryItemEquipment(<?php echo htmlspecialchars($accountInventoryItem['item_id']); ?>);"  data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="<?php echo htmlspecialchars($accountInventoryItem["name"])?>">
                                <img src="/assets/media/<?php echo htmlspecialchars($accountInventoryItem["large_image"]); ?>" alt="Item Name 1">
                                <div class="item-count">x<?php echo htmlspecialchars($accountInventoryItem["amount"]); ?></div>
                            </div>
                            
                            <?php
                                }
                            }

                            ?>
                        </div>
                    </div>
                </div> 
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-target="#equipmentModal" data-bs-toggle="modal">Back</button>
            </div>
            </div>
        </div>
    </div>

    
    <!--SELECT BACKGROUND MODAL-->
    <div class="modal fade" id="selectBackgroundModal" tabindex="-1" aria-labelledby="selectBackgroundModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="selectBackgroundModalLabel">Select a Background</h1>
                <button type="button" class="btn-close btn-close-white"  data-bs-target="#equipmentModal" data-bs-toggle="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <!-- side-bar colleps block stat-->
                        <div class="inventory-grid-sm">
                            
                        <?php
                            
                            // Show category title

                            foreach ($accountInventory as $accountInventoryItem) {
                                if ($accountInventoryItem["equipable"] && $accountInventoryItem["equipment_slot"] == "BACKGROUND")
                                {
                                ?>
                            <div class="inventory-item" onclick="SelectInventoryItemEquipment(<?php echo htmlspecialchars($accountInventoryItem['item_id']); ?>);"  data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="<?php echo htmlspecialchars($accountInventoryItem["name"])?>">
                                <img src="/assets/media/<?php echo htmlspecialchars($accountInventoryItem["large_image"]); ?>" alt="Item Name 1">
                                <div class="item-count">x<?php echo htmlspecialchars($accountInventoryItem["amount"]); ?></div>
                            </div>
                            
                            <?php
                                }
                            }

                            ?>
                        </div>
                    </div>
                </div> 
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-target="#equipmentModal" data-bs-toggle="modal">Back</button>
            </div>
            </div>
        </div>
    </div>

    <!--TRADE MODAL-->
    <div class="modal fade" id="tradeModal" tabindex="-1" aria-labelledby="tradeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="tradeModalLabel">Offer a Trade</h1>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ...
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Save changes</button>
            </div>
            </div>
        </div>
    </div>
    
    <!--NOMINATE MODAL-->
    <div class="modal fade" id="nominateModal" tabindex="-1" aria-labelledby="nominateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="nominateModalLabel">Nominate <?php echo $profile["Username"]; ?></h1>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ...
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Save changes</button>
            </div>
            </div>
        </div>
    </div>

    
    <!--CRAFT MODAL-->
    <div class="modal fade" id="craftModal" tabindex="-1" aria-labelledby="craftModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="craftModalLabel">Craft</h1>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ...
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Save changes</button>
            </div>
            </div>
        </div>
    </div>

    <!--MAIN CONTENT-->
    <main class="container pt-3 bg-body" style="margin-bottom: 56px;">
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
        <div class="row">
            <div class="col-12 col-xl-9">
                
                
                <?php 
                
                
                $activePageName = $profile["Username"];
                require("php-components/base-page-breadcrumbs.php"); 
                
                ?>
                <div class="row">
                    <div class="col-12">
                        <nav>
                            <div class="nav nav-tabs" id="nav-tab" role="tablist">
                            <button class="nav-link active" id="nav-reviews-tab" data-bs-toggle="tab" data-bs-target="#nav-reviews" type="button" role="tab" aria-controls="nav-reviews" aria-selected="true"><i class="fa-solid fa-crown"></i></button>
                            <button class="nav-link" id="nav-badges-tab" data-bs-toggle="tab" data-bs-target="#nav-badges" type="button" role="tab" aria-controls="nav-badges" aria-selected="true"><i class="fa-solid fa-medal"></i></button>
                            <button class="nav-link" id="nav-inventory-tab" data-bs-toggle="tab" data-bs-target="#nav-inventory" type="button" role="tab" aria-controls="nav-inventory" aria-selected="true"><i class="fa-solid fa-suitcase"></i></button>
                            <button class="nav-link" id="nav-activity-tab" data-bs-toggle="tab" data-bs-target="#nav-activity" type="button" role="tab" aria-controls="nav-activity" aria-selected="true"><i class="fa-solid fa-bolt"></i></button>
                            <button class="nav-link" id="nav-feed-tab" data-bs-toggle="tab" data-bs-target="#nav-feed" type="button" role="tab" aria-controls="nav-feed" aria-selected="true"><i class="fa-solid fa-newspaper"></i></button>
                            </div>
                        </nav>
                        <div class="tab-content" id="nav-tabContent">
                            <div class="tab-pane fade active show" id="nav-reviews" role="tabpanel" aria-labelledby="nav-reviews-tab" tabindex="0">
                                <div class="display-6 tab-pane-title">Prestige
                        <?php if (!$isMyProfile) { ?><span class="float-end" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Commend or Denounce"><button class="btn bg-ranked-1" type="button" data-bs-toggle="modal" data-bs-target="#reviewModal"><i class="fa-solid fa-crown"></i>/<i class="fa-solid fa-biohazard"></i></button></span><?php } ?></div>    
                                <div class="row">
                                <?php
                                    for ($i=0; $i < count($prestigeReviews); $i++) { 
                                        ?>
                                    <div class="col-12 col-lg-6">
                                        <div class="card mb-2 <?php echo ($prestigeReviews[$i]["commend"]?"border-success":"border-danger"); ?>">
                                            <div class="card-header <?php echo ($prestigeReviews[$i]["commend"]?"text-bg-success":"text-bg-danger"); ?>">
                                                <?php echo ($prestigeReviews[$i]["commend"]==1?"<i class='fa-solid fa-crown'></i> Commend":"<i class='fa-solid fa-biohazard'></i> Denounce"); ?>
                                            </div>
                                            
                                            <div class="row g-0">
                                                <div class="col-md-12">
                                                    <div class="card-body">
                                                        <div class="row">
                                                            <div class="col-4">
                                                            
                                                                <img src="/assets/media/<?php echo GetPrestigeProfilePicture($prestigeReviews[$i]); ?>" class="img-fluid img-thumbnail">
                                                            </div>
                                                            <div class="col-8">
                                                                <blockquote class="blockquote mb-0">
                                                                <p><?php echo $prestigeReviews[$i]["desc"]; ?></p>
                                                                <footer class="blockquote-footer"><a class="username" href="<?php echo $urlPrefixBeta; ?>/u/<?php echo $prestigeReviews[$i]["Username"]; ?>"><?php echo $prestigeReviews[$i]["Username"]; ?></a></footer>
                                                                </blockquote>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="card-footer">
                                            <?php
                                            if ($prestigeReviews[$i]["quest_id"]!=null)
                                            {
                                                ?>
                                                <a class="btn btn-primary btn-sm" href="<?php echo $urlPrefixBeta; ?>/q/<?php echo $prestigeReviews[$i]["locator"]; ?>"><?php echo $prestigeReviews[$i]["name"]; ?></a>
                                                <?php 

                                            }

                                            ?>
                                                <small class="text-body-secondary float-end"><?php echo date_format(date_create($prestigeReviews[$i]["date"]),"M j, Y"); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                        <?php
                                    }
                                ?>
                                </div>
                            </div>
                            
                            <div class="tab-pane fade" id="nav-badges" role="tabpanel" aria-labelledby="nav-badges-tab" tabindex="0">
                                <div class="display-6 tab-pane-title">Badges<?php if (!$isMyProfile) { ?><button class="btn float-end bg-ranked-1" type="button" data-bs-toggle="modal" data-bs-target="#nominateModal"><i class="fa-solid fa-bullhorn"></i></button><?php } ?></div> 
                                <div class="row">
                                    <div class="col-12">
                                        <?php for ($i=0; $i < count($badges) ; $i++) { 
                                            $badge = $badges[$i];
                                            $earnedInQuest = ($badge["quest_id"]!=null);
                                            $nominatedByUser = ($badge["nominated_by_id"]!=null);
                                            $awardedByHorsemen = (!$earnedInQuest && !$nominatedByUser);
                                            $title = "";
                                            if ($earnedInQuest)
                                            {
                                                $title = "<i class='fa-solid fa-medal'></i> Earned by Quest";
                                            }
                                            if ($nominatedByUser)
                                            {
                                                $title = "<i class='fa-solid fa-bullhorn'></i> Earned by Nomination";
                                            }
                                            if ($awardedByHorsemen)
                                            {
                                                $title = "<i class='fa-solid fa-gift'></i> Gifted by Horsemen";
                                            }

                                        ?>
                                        <div class="card mb-3">
                                            
                                            <div class="card-header <?php echo ($awardedByHorsemen?"bg-ranked-1":""); ?>">
                                            <?php echo $title; ?>
                                            </div>
                                            <div class="row g-0">
                                                <div class="col-12 col-sm-3 col-md-2">
                                                    <img src="/assets/media/<?php echo $badge['BigImgPath']; ?>" class="img-fluid p-3">
                                                </div>
                                                <div class="col-12 col-sm-9 col-md-10">
                                                    <div class="card-body">
                                                        <h5 class="card-title"><?php echo $badge['name']; ?></h5>
                                                        <blockquote class="blockquote mb-0">
                                                                <p><?php echo $badge['desc']; ?></p>
                                                                <?php if ($nominatedByUser)
                                                                {
                                                                    ?>
                                                                
                                                                <footer class="blockquote-footer"><a class="username" href="<?php echo $urlPrefixBeta; ?>/u/<?php echo $badge["nominated_by"]; ?>"><?php echo $badge["nominated_by"]; ?></a></footer>
                                                            <?php 
                                                            
                                                                }

                                                                ?>
                                                            </blockquote>
                                                    </div>
                                                    
                                                </div>
                                            </div>
                                            
                                            <div class="card-footer <?php echo ($awardedByHorsemen?"bg-ranked-1":""); ?>">
                                                        <?php
                                                        if ($badge["quest_id"]!=null)
                                                        {
                                                            ?>
                                                            <a class="btn btn-primary btn-sm" href="<?php echo $urlPrefixBeta; ?>/q/<?php echo $badge["quest_locator"]; ?>"><?php echo $badge["quest_name"]; ?></a>
                                                            <?php 

                                                        }
                                                        ?>
                                                        <p class="card-text float-end"><?php echo date_format(date_create($badge["dateObtained"]),"M j, Y"); ?></p>
                                                    </div>
                                        </div>
                                        <?php 
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="tab-pane fade" id="nav-inventory" role="tabpanel" aria-labelledby="nav-inventory-tab" tabindex="0">
                                <div class="display-6 tab-pane-title">Inventory<?php if (!$isMyProfile) { ?><button class="btn float-end bg-ranked-1" type="button" data-bs-toggle="modal" data-bs-target="#tradeModal"><i class="fa-solid fa-arrow-right-arrow-left"></i></button><?php } ?><button class="btn float-end bg-ranked-1 me-2" type="button" data-bs-toggle="modal" data-bs-target="#equipmentModal"><i class="fa-solid fa-shirt"></i></button><?php if ($isMyProfile) { ?><button class="btn float-end bg-ranked-1 me-2" type="button" data-bs-toggle="modal" data-bs-target="#craftModal"><i class="fa-solid fa-flask-vial"></i></button><?php } ?></div>
                                <div class="row">
                                        <div class="col-12">
                                            <!-- side-bar colleps block stat-->
                                            <div class="inventory-grid">
                                                <?php
                                                
                                                // Show category title

                                                foreach ($accountInventory as $accountInventoryItem) {
                                                    ?>
                                                <div class="inventory-item" onclick="ShowInventoryItemModal(<?php echo htmlspecialchars($accountInventoryItem['item_id']); ?>);"  data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="<?php echo htmlspecialchars($accountInventoryItem["name"])?>">
                                                    <img src="/assets/media/<?php echo htmlspecialchars($accountInventoryItem["large_image"]); ?>" alt="Item Name 1">
                                                    <div class="item-count">x<?php echo htmlspecialchars($accountInventoryItem["amount"]); ?></div>
                                                </div>
                                                
                                                <?php
                                                }

                                                ?>
                                            </div>
                                        </div>
                                </div> 
                            </div>
                            
                            <div class="tab-pane fade" id="nav-activity" role="tabpanel" aria-labelledby="nav-activity-tab" tabindex="0">
                                <div class="display-6 tab-pane-title">Activity</div> 
                               
                                    <?php 
                                    
                                    foreach ($accountActivity as $activity) {
                                    

                                        $feedCard = $activity;
                                        $feedCard["text"] = json_encode($activity);
                                        require("php-components/feed-card.php");
                                    ?>

                                    <?php
                                    }
                                    
                                    ?>

                            </div>
                            
                            <div class="tab-pane fade" id="nav-feed" role="tabpanel" aria-labelledby="nav-feed-tab" tabindex="0">
                                <div class="display-6 tab-pane-title">Feed</div>
                                <?php 
    
    require("php-components/coming-soon.php"); 
    
    
    ?>

                            </div>
                        </div>
                    </div>
                </div>

            </div>
            
            <?php require("php-components/base-page-discord.php"); ?>
        </div>
    </main>

    
    <?php require("php-components/base-page-javascript.php"); ?>
    <script>
        
        function openReviewModal() {
            document.getElementById("reviewModal").style.display = "block";
        }

        function closeReviewModal() {
            document.getElementById("reviewModal").style.display = "none";
        } 

        var selectedButtonReviewModal = null;
        function rate(buttonNumber) {
            var plus = document.getElementById("plus");
            var minus = document.getElementById("minus");
            var reviewRating = document.getElementById("review-rating");

            if (selectedButtonReviewModal !== buttonNumber) {
                selectedButtonReviewModal = buttonNumber;

                // Update button styles
                if (buttonNumber === 1) {
                    plusButton.style.backgroundColor = "#7dc006";
                    minusButton.style.backgroundColor = "#8f97b2";
                    reviewRating.value = "1";

                } else if (buttonNumber === 2) {
                    plusButton.style.backgroundColor = "#8f97b2";
                    minusButton.style.backgroundColor = "#e52727";
                    reviewRating.value = "-1";
                }

                // Perform button-specific actions
                if (buttonNumber === 1) {
                    // +1
                    console.log("Button 1 clicked");
                } else if (buttonNumber === 2) {
                    // -1
                    console.log("Button 2 clicked");
                }
            }
        }

        
        function validateSelection(event) {
            if (selectedButtonReviewModal === null) {
              alert("Please give a rating");
              event.preventDefault(); // Prevent the default form submission behavior
              return;
            }
        }

        var textareaReviewModal = document.getElementById("textareaReviewModal");

        textareaReviewModal.addEventListener("input", function() {   
            var maxLength = parseInt(textareaReviewModal.getAttribute("maxlength"));
            var currentLength = textareaReviewModal.value.length;

            if (currentLength > maxLength) {
                textareaReviewModal.value = textareaReviewModal.value.slice(0, maxLength);
            }
        });

        function SelectInventoryItemEquipment(item_id)
        {
            var item = GetItemInformationById(item_id);

            $("#equipmentModal").modal("show");

            <?php 

            if ($isMyProfile)
            {

            ?>
            
            if (item.equipment_slot == "AVATAR")
            {
                $("#equipment-avatar").val(item.next_loot_id);
                $("#equipment-preview-avatar").attr("src","/assets/media/"+item.image);
            }
            if (item.equipment_slot == "PC_CARD")
            {
                $("#equipment-pc-card").val(item.next_loot_id);
                $("#equipment-preview-pc-card").attr("src","/assets/media/"+item.image);
            }
            if (item.equipment_slot == "BANNER")
            {
                $("#equipment-banner").val(item.next_loot_id);
                $("#equipment-preview-banner").attr("src","/assets/media/"+item.image);
            }
            if (item.equipment_slot == "BACKGROUND")
            {
                $("#equipment-background").val(item.next_loot_id);
                $("#equipment-preview-background").attr("src","/assets/media/"+item.image);
            }
            if (item.equipment_slot == "CHARM")
            {
                $("#equipment-charm").val(item.next_loot_id);
                $("#equipment-preview-charm").attr("src","/assets/media/"+item.image);
            }
            if (item.equipment_slot == "PET")
            {
                $("#equipment-pet").val(item.next_loot_id);
                $("#equipment-preview-pet").attr("src","/assets/media/"+item.image);
            }
            
            <?php 
            }

            ?>
            console.log(item);
        }

    </script>
</body>

</html>
