<?php
declare(strict_types=1);

use Kickback\AtlasOdyssey\Emberwood\EmberwoodTradingCargoship;
use Kickback\Backend\Controllers\AccountController;
use Kickback\Backend\Controllers\ShipmentController;
use Kickback\Backend\Views\vRecordId;
use Kickback\AtlasOdyssey\AtlasDateTime;
// Create an instance of the EmberwoodTradingCargoship
$emberwoodShip = new EmberwoodTradingCargoship(2);

// Journey progress and ATC date
$progressPercentage = $emberwoodShip->getJourneyPercentage();
$timeUntilNextDelivery = $emberwoodShip->getTimeUntilNextDeliveryInATC();
$currentATCDate = $emberwoodShip->getCurrentATCDateTime();

// Get status details with color and icon
$shipLocation = $emberwoodShip->getLocation();
$shipStatus = $emberwoodShip->getShipStatusWithDetails();
$trackingNumber = $emberwoodShip->getTrackingNumber();


$imgStarMap = "https://png.pngtree.com/background/20230612/original/pngtree-solar-system-with-many-planets-picture-image_3362535.jpg";
$imgShip = "https://i0.wp.com/thelegocarblog.com/wp-content/uploads/2024/09/Screenshot-2024-09-19-at-13.52.59.png";

// Calculate dynamic left positioning with boundaries for 0% and 100% to keep icon within bounds
$shipWidth = 50; // Ship image width in pixels
$adjustedProgress = max(0, min($progressPercentage, 100));
$leftPosition = $adjustedProgress === 0 ? '0%' : ($adjustedProgress === 100 ? "calc(100% - {$shipWidth}px)" : "{$adjustedProgress}%");

$shipmentManifest = [];
$itemInfos = [];


$profile = AccountController::getAccountByUsername("Alibaba");
$profile = $profile->data;

$shipmentManifestResp = ShipmentController::getShipmentManifest($trackingNumber);

if ($shipmentManifestResp->success == false)
{
    print_r($shipmentManifestResp);
}
else{

    $shipmentManifest = $shipmentManifestResp->data;
    foreach ($shipmentManifest as $accountInventoryItemStack) {

        array_push($itemInfos, $accountInventoryItemStack->item);
    }
    
}



$itemInformationJSON = json_encode($itemInfos);
$itemStackInformationJSON = json_encode($shipmentManifest);

?>


<div class="card mt-4 shadow-lg rounded emberwood-tracker-card">
    <div class="card-body text-center py-4">

        <!-- Information Display with Ship Location, Shipment Number, and ETA -->
        <div class="emberwood-tracker-info-container mb-4">
            <div class="emberwood-tracker-info-box">
                <h5 class="text-muted"><i class="fas fa-map-marker-alt"></i> Ship Location</h5>
                <p class="text-primary emberwood-tracker-info-text"><?= $shipLocation; ?></p>
            </div>
            <div class="emberwood-tracker-info-box">
                <h5 class="text-muted"><i class="fas fa-box"></i> Tracking #</h5>
                <p class="emberwood-tracker-info-text"><?= $trackingNumber; ?></p>
            </div>
            <div class="emberwood-tracker-info-box">
                <h5 class="text-muted"><i class="far fa-clock"></i> Estimated Arrival</h5>
                <p class="text-danger emberwood-tracker-info-text"><?= $timeUntilNextDelivery; ?></p>
            </div>
        </div>

        <!-- Star Map and Ship Progress Display -->
        <div class="emberwood-tracker-progress-route mb-4">
            <div class="emberwood-tracker-ship-icon" style="left: <?= $leftPosition; ?>;">
                <img src="<?= $imgShip; ?>" alt="Emberwood Ship" class="emberwood-tracker-ship-image">
            </div>
        </div>

        <!-- Enhanced Ship Status with Bootstrap Color and Icon on the Left -->
        <div class="d-flex align-items-center justify-content-center p-3 rounded bg-<?= $shipStatus->bootstrapColorClass; ?> text-bg-<?= $shipStatus->bootstrapColorClass; ?>">
            <i class="<?= $shipStatus->icon; ?> me-3" style="font-size: 2rem;"></i>
            <span class="ship-status-text"><?= $shipStatus->text; ?></span>
        </div>
    </div>
</div>

<div class="card mt-4 shadow-lg rounded emberwood-tracker-card">
    <div class="card-body text-center py-4">
        <div class="display-6 tab-pane-title">Shipment Cargo</div>
        <div class="row">
            <div class="col-12">
                <!-- side-bar colleps block stat-->
                <div class="inventory-grid">
                    <?php
                    
                    // Show category title

                    foreach ($shipmentManifest as $shipmentCargoItemStack) {
                        ?>
                        <div class="inventory-item" onclick="ShowInventoryItemModal(<?= $shipmentCargoItemStack->item->crand; ?>);"  data-bs-toggle="tooltip" data-bs-dismiss="modal" data-bs-placement="bottom" data-bs-title="<?= htmlspecialchars($shipmentCargoItemStack->item->name)?>">
                            <img src="<?= $shipmentCargoItemStack->item->iconSmall->getFullPath(); ?>" alt="Item <?= $shipmentCargoItemStack->item->name; ?>">
                            <div class="item-count">x<?= $shipmentCargoItemStack->amount; ?></div>
                        </div>
                    
                    <?php
                    }

                    ?>
                </div>
            </div>
        </div> 
    </div>
</div>

<style>
/* Info Box Container */
.emberwood-tracker-info-container {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    border-bottom: 1px solid #ddd;
    padding-bottom: 1rem;
    margin-bottom: 1rem;
}

/* Info Box Styling */
.emberwood-tracker-info-box {
    flex: 1 1 30%;
    padding: 0.5rem;
    text-align: center;
    background-color: #ffffff;
    border-radius: 8px;
    margin: 0.5rem;
    box-shadow: 0px 2px 6px rgba(0, 0, 0, 0.05);
    transition: transform 0.2s ease-in-out;
}

.emberwood-tracker-info-box:hover {
    transform: translateY(-4px);
    box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.1);
}

.emberwood-tracker-info-text {
    font-size: 1.35rem;
    font-weight: bold;
}

/* Ensure responsiveness: Stack boxes on small screens like phones */
@media (max-width: 768px) {
    .emberwood-tracker-info-container {
        display: block;
        text-align: center;
    }
    .emberwood-tracker-info-box {
        flex: 1 1 100%;
        margin: 0.75rem 0;
    }
}

/* Star Map and Ship Progress */
.emberwood-tracker-progress-route {
    position: relative;
    height: 300px;
    border: 2px solid #ddd;
    border-radius: 10px;
    margin-bottom: 1rem;
    overflow: hidden;
    background: url("<?= $imgStarMap; ?>") no-repeat center center;
    background-size: cover;
}

/* Ship Icon */
.emberwood-tracker-ship-icon {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    transition: left 1s ease-in-out;
}

.emberwood-tracker-ship-image {
    width: 50px;
    border-radius: 50%;
    box-shadow: 0px 0px 15px rgba(255, 200, 50, 0.8);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.emberwood-tracker-ship-image:hover {
    transform: scale(1.2) rotate(10deg);
    box-shadow: 0px 0px 20px rgba(255, 150, 0, 1);
    cursor: pointer;
}

/* Enhanced Ship Status Styling */
.enhanced-status {
    font-size: 1.35rem;
    text-align: center;
    font-weight: bold;
    border-radius: 12px;
    padding: 20px;
    margin-top: 1.5rem;
    box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.1);
    transition: background-color 0.5s ease, transform 0.2s ease;
}

.enhanced-status:hover {
    transform: scale(1.02);
}

.status-icon-container {
    margin-bottom: 0.5rem;
}

.ship-status-text {
    font-size: 1.4rem;
    line-height: 1.5;
    font-family: 'Poppins', sans-serif;
}

@media (max-width: 768px) {
    .enhanced-status {
        padding: 15px;
        font-size: 1.25rem;
    }
    .status-icon-container {
        font-size: 2rem;
    }
}
</style>
