<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

use Kickback\Backend\Controllers\TreasureHuntController;

$thisLichSet = null;
// Check if the locator is set in the GET request
if (isset($_GET["locator"])) {
    $locator = $_GET["locator"];
    
    // Fetch the Lich Set by its locator
    $response = TreasureHuntController::getEventByLocator($locator);
    

    // Lich Set details
    $thisTreasureHuntEvent = $response->data; // This is a vLichSet object
    $thisTreasureHuntEvent->populateEverything();
} 

if ($thisTreasureHuntEvent == null) {
    // Redirect to homepage if no locator is provided
    header('Location: /index.php');
    exit();
}

$allTreasuresHidden = TreasureHuntController::getAllHiddenObjectsForEvent($thisTreasureHuntEvent)->data;
$uniqueLootItems = [];
$seenItemIds = [];

foreach ($allTreasuresHidden as $obj) {
    $itemId = $obj->item->crand;
    if (!in_array($itemId, $seenItemIds)) {
        $seenItemIds[] = $itemId;
        $uniqueLootItems[] = $obj->item;
    }
}

shuffle($uniqueLootItems); 


$now = new DateTime();
$start = new DateTime($thisTreasureHuntEvent->startDate->dbValue);
$end = new DateTime($thisTreasureHuntEvent->endDate->dbValue);

$status = 'Upcoming';
$statusClass = 'primary';
$icon = 'fa-hourglass-start';

if ($now >= $start && $now <= $end) {
    $status = 'Ongoing';
    $statusClass = 'success';
    $icon = 'fa-fire';
} elseif ($now > $end) {
    $status = 'Ended';
    $statusClass = 'secondary';
    $icon = 'fa-flag-checkered';
}

$totalDuration = $start->diff($end)->days;
$elapsed = $start->diff(min($now, $end))->days;
$percent = min(100, max(0, round(($elapsed / max(1, $totalDuration)) * 100)));

$bannerUrl = $thisTreasureHuntEvent->banner->getFullPath();
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
                
                
                $activePageName = $thisTreasureHuntEvent->name;
                require("php-components/base-page-breadcrumbs.php"); 
                
                ?>
<div class="mb-5" style="background-color: #f8f4e3; border: 2px solid #e0d8b0; border-radius: 1rem; padding: 2rem; box-shadow: 0 6px 16px rgba(0,0,0,0.1); position: relative;">
    
    <!-- Scroll Icon Header -->
    <div style="text-align: center; margin-bottom: 1.5rem;">
        <h4 style="font-weight: bold; color: #8a6d3b; margin: 0;">
            <i class="fa-solid fa-scroll me-2" style="color: #d4a017;"></i>The Hunt Begins…
        </h4>
        <p style="color: #6c5c3f; font-size: 1.1rem; margin-top: 0.5rem;">
            Only those who pay close attention will uncover the Kingdom’s secrets.
        </p>
    </div>

    <!-- Lore Quote -->
    <blockquote style="font-style: italic; color: #4a3c28; font-size: 1.25rem; line-height: 1.8; border-left: 5px solid #d4a017; padding-left: 1rem; margin: 0;">
        <?= $thisTreasureHuntEvent->desc; ?>
    </blockquote>

    <!-- Decorative Bottom -->
    <div style="position: absolute; bottom: -20px; left: 50%; transform: translateX(-50%);">
        <i class="fa-solid fa-sparkles" style="color: #d4a017; font-size: 1.5rem;"></i>
    </div>
</div>



<div class="card border-0 overflow-hidden shadow-lg mb-4 position-relative" style="height: 200px;">
    <div class="position-absolute top-0 start-0 w-100 h-100 bg-dark bg-opacity-50" style="background-image: url('<?= $thisTreasureHuntEvent->bannerDate->getFullPath(); ?>'); background-size: cover; background-position: center;"></div>

    <div class="position-relative h-100 d-flex flex-column justify-content-center align-items-center text-white text-shadow px-3">
        <h4 class="fw-bold mb-2">
            <i class="fa-solid <?= $icon ?> me-2 text-<?= $statusClass ?>"></i>
            <?= $status ?> Treasure Hunt
        </h4>

        <div class="mb-2 small">
            <?= $start->format('F j, Y') ?> &mdash; <?= $end->format('F j, Y') ?>
        </div>

        <div class="progress w-100" style="height: 6px; max-width: 300px;">
            <div class="progress-bar progress-bar-animated progress-bar-striped bg-<?= $statusClass ?>" role="progressbar" style="width: <?= $percent ?>%"></div>
        </div>

        <span class="badge bg-<?= $statusClass ?> mt-3 px-3 py-2 rounded-pill">
            <i class="fa-regular fa-clock me-1"></i> <?= $status ?>
        </span>
    </div>
</div>



<?php if (!empty($allTreasuresHidden)): ?>
    <section class="my-5">
        <h3 class="fw-bold mb-4 text-center text-ranked-1">
            <i class="fa-solid fa-coins me-2 text-warning"></i> Hidden Treasures
        </h3>

        <div class="row g-4 justify-content-center">
            <?php foreach ($allTreasuresHidden as $obj): ?>
                <?php
                    $isLegendary = $obj->oneTimeFind;
                    $specialClass = $isLegendary ? 'border-gold shadow-lg' : 'shadow';
                    $badgeText = $isLegendary ? 'Legendary Find' : 'Find me!';
                    $badgeClass = $isLegendary ? 'bg-danger text-light' : 'bg-warning text-dark';
                ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="position-relative treasure-hunt-card <?= $specialClass ?> rounded-4 text-center bg-white p-3 h-100">

                        <?php if ($obj->found): ?>
                            <div class="ribbon bg-success text-white fw-bold">Found!</div>
                        <?php endif; ?>

                        <div class="position-relative z-1">
                            <div class="shine-spin position-absolute top-50 start-50 translate-middle opacity-25 z-0">
                                <img src="/assets/media/chests/0_o_s.png" style="width: 100px;">
                            </div>
                            <img src="<?= $obj->media->url ?>"
                                alt="Hidden Treasure"
                                title="Hidden Treasure"
                                class="animate-treasure"
                                style="width: 72px; height: 72px; object-fit: contain;">
                        </div>

                        <h6 class="fw-semibold mt-3 mb-1">Hidden Treasure</h6>

                        <?php if (!$obj->found): ?>
                            <span class="badge <?= $badgeClass ?> mt-1"><?= $badgeText ?></span>
                        <?php else: ?>
                            <?php if ($isLegendary) { ?>
                                <span class="badge <?= $badgeClass ?> mt-1"><?= $badgeText ?></span>
                            <?php } ?>
                            <small class="text-muted d-block">You found this treasure</small>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

        </div>
    </section>

    <?php
    $total = count($allTreasuresHidden);
    $found = count(array_filter($allTreasuresHidden, fn($o) => $o->foundByMe));
    ?>
    
    <section class="my-5">
    <div class="card bg-dark text-white shadow-lg border-0 position-relative overflow-hidden">
    <div class="position-absolute top-0 start-0 w-100 h-100 bg-dark bg-opacity-50" style="background-image: url('<?= $thisTreasureHuntEvent->bannerProgress->getFullPath(); ?>'); background-size: cover; background-position: center;"></div>

        <div class="card-body text-center position-relative z-1 py-5">
            <h4 class="fw-bold mb-3">
                <i class="fa-solid fa-compass me-2 text-warning"></i> Your Treasure Progress
            </h4>

            <div class="fs-1 fw-bold text-warning">
                <?= $found ?> <span class="text-white fs-4">of</span> <?= $total ?>
            </div>
            <p class="text-light mb-4 small">You’ve uncovered <?= round(($found / max(1, $total)) * 100) ?>% of the treasures so far!</p>

            <div class="progress mx-auto" style="height: 8px; max-width: 300px;">
                <div class="progress-bar bg-warning" role="progressbar" style="width: <?= round(($found / max(1, $total)) * 100) ?>%"></div>
            </div>
        </div>
    </div>
</section>


    <section class="my-5">
        <h3 class="fw-bold mb-4 text-center text-ranked-1">
            <i class="fa-solid fa-gift me-2 text-purple"></i> Rewards You Can Find
        </h3>
        <div class="row g-3 justify-content-center">
            <?php foreach ($uniqueLootItems  as $item): ?>
                <div class="col-6 col-md-3 col-lg-2">
                    <div class="text-center p-2 bg-white shadow rounded-3">
                        <img src="<?= $item->iconSmall->url ?>" alt="<?= htmlspecialchars($item->name) ?>" class="img-fluid" style="height: 64px;">
                        <div class="mt-2 fw-semibold small"><?= htmlspecialchars($item->name) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <style>
        .treasure-hunt-card {
            position: relative;
            background: white;
            border-radius: 1rem;
            padding: 1rem;
            text-align: center;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .treasure-hunt-card:hover {
            transform: scale(1.04);
            box-shadow: 0 0 16px rgba(255, 215, 0, 0.45);
        }

        .animate-treasure {
            animation: pulse-rotate 4s ease-in-out infinite;
        }

        @keyframes pulse-rotate {
            0%, 100% { transform: rotate(-10deg) scale(1); opacity: 1; }
            50%      { transform: rotate(10deg) scale(1.08); opacity: 0.9; }
        }

        .shine-spin img {
            animation: spin 8s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to   { transform: rotate(360deg); }
        }

        .ribbon {
            position: absolute;
            right: -30px;
            width: 120px;
            text-align: center;
            background: #198754;
            color: white;
            padding: 4px 0;
            font-size: 0.7rem;
            font-weight: bold;
            transform: rotate(45deg);
            box-shadow: 0 1px 5px rgba(0,0,0,0.2);
            z-index: 10;
            pointer-events: none;
        }

    </style>
<?php else: ?>
    <div class="alert alert-info text-center my-5">
        <i class="fa-solid fa-magnifying-glass me-2"></i>
        No hidden treasures right now. Check back soon!
    </div>
<?php endif; ?>

                
                
                    <!-- Content Section -->
                    <div class="mt-4">
                        <?php 
                        if ($thisTreasureHuntEvent->hasPageContent()) {
                            $_vCanEditContent = $thisTreasureHuntEvent->canEdit();
                            $_vContentViewerEditorTitle = "Treasure Hunt Information Manager";
                            $_vPageContent = $thisTreasureHuntEvent->getPageContent();
                            require("php-components/content-viewer.php");
                        }
                        ?>
                    </div>

            </div>
            
            <?php require("php-components/base-page-discord.php"); ?>
        </div>
        <?php require("php-components/base-page-footer.php"); ?>
    </main>

    
    <?php require("php-components/base-page-javascript.php"); ?>
    <?php 
    if ($thisTreasureHuntEvent->hasPageContent())
    {
        $_vPageContent = $thisTreasureHuntEvent->getPageContent();
        require("php-components/content-viewer-javascript.php"); 
    }
    ?>
</body>

</html>
