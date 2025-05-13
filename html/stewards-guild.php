<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

use Kickback\Common\Version;
use Kickback\Services\Session;
use Kickback\Backend\Controllers\AnalyticController;

$thisMonthsAnalytics = AnalyticController::getThisMonthsGrowthStats()->data;
$thisMonthsGrowthPercentage = 0;
$thisMonthsTotalAccounts = 0;
$thisMonthsRetentionRate = 0;

if (isset($thisMonthsAnalytics["growth_percentage"]))
{
    $thisMonthsGrowthPercentage = (float)$thisMonthsAnalytics["growth_percentage"];
}
if (isset($thisMonthsAnalytics["retention_rate"])) {
    $thisMonthsRetentionRate = (float)$thisMonthsAnalytics["retention_rate"];
}
if (isset($thisMonthsAnalytics["total_accounts"])) {
    $thisMonthsTotalAccounts = (int)$thisMonthsAnalytics["total_accounts"];
}

$growthProgress = min($thisMonthsGrowthPercentage / 5 * 100, 100);
$retentionProgress = min($thisMonthsRetentionRate / 20 * 100, 100);

$divisionData = [
    [
        'name' => 'Horsemen',
        'icon' => 'fa-chess-knight',
        'desc' => 'Leadership and strategic oversight.',
        'goal' => 'Release L.I.C.H., Atlas Odyssey and Craftmens Guild',
        'progress' => 25
    ],
    [
        'name' => 'Technology Division',
        'icon' => 'fa-microchip',
        'desc' => 'Development and infrastructure.',
        'goal' => 'Launch store and optimize load times',
        'progress' => 50
    ],
    [
        'name' => 'Expansion Division',
        'icon' => 'fa-seedling',
        'desc' => 'Growth and partnerships.',
        'goal' => 'Grow to 200 Guildsmen',
        'progress' => $thisMonthsTotalAccounts / 200 * 100
    ]
];

$newsItems = [];

$item1 = new stdClass();
$item1->title = "Kickback is legal in Florida";
$item1->date = "April 16, 2025";
$item1->summary = "Our USA company is now filed and registered in the state of Florida.";
$newsItems[] = $item1;

$item1 = new stdClass();
$item1->title = "BRA company is legal";
$item1->date = "April 7, 2025";
$item1->summary = "Our Brazilian company has been processed and we have recieved our CNPJ.";
$newsItems[] = $item1;

$item1 = new stdClass();
$item1->title = "USA and BRA companies have been filed";
$item1->date = "April 1, 2025";
$item1->summary = "Legal companies in both Brazil and the United States have been filed and are being processed. It should be ready to conduct business in ~1 month.";
$newsItems[] = $item1;

$item1 = new stdClass();
$item1->title = "New Deal!";
$item1->date = "March 26, 2025";
$item1->summary = "We have secured a deal with Rudirock Inc to offer Craftsmen Guild services with a 1k USD a month service charge per guildmember used.";
$newsItems[] = $item1;

$item2 = new stdClass();
$item2->title = "L.I.C.H. Deck Editor!";
$item2->date = "March 30, 2025";
$item2->summary = "The deck editor has been finished and is ready to use to build the precon decks for the starter packs";
$newsItems[] = $item2;



$stewardsByDivision = [
    'Horsemen' => [
        ['name' => 'Alexander', 'role' => 'Horseman', 'image' => '/assets/media/logo.png', 'goals' => [
            ['name' => 'Self Score Reporting', 'progress' => 80],
            ['name' => 'Emberwood Deliveries for Store Page', 'progress' => 40],
            ['name' => 'Twilight Racer Full Game Loop', 'progress' => 30],
            ['name' => 'Treasure Hunter Full Game Loop', 'progress' => 80],
            ['name' => 'Legalize Kickback in USA and BRL', 'progress' => 95],
            ['name' => 'Setup Kickback Steam Account', 'progress' => 30],
            ['name' => 'Build Game Server Page', 'progress' => 0],
            ['name' => 'Build Invasion Events', 'progress' => 0],
            ['name' => 'Fix Merchants\' Guild page', 'progress' => 90],
            ['name' => 'Fix Admin page', 'progress' => 0],
            ['name' => 'Fix Apprentices\' Guild page', 'progress' => 0],
            ['name' => 'Build Craftsmens\' Guild page', 'progress' => 0],
            ['name' => 'Build Craftsmens\' Guild Website', 'progress' => 0],
            ['name' => 'Refactor ELO Logic', 'progress' => 0],
            ['name' => 'Refactor Merchants Logic', 'progress' => 95]
        ]],
        ['name' => 'Eric', 'role' => 'Horseman', 'image' => '/assets/media/logo.png', 'goals' => [
        ]],
    ],
    'Technology Division' => [
        ['name' => 'Lily', 'role' => 'Chancellor', 'image' => '/assets/media/logo.png', 'goals' => [
            ['name' => 'Website Load Time Optimizations', 'progress' => 15],
            ['name' => 'Database optimizations', 'progress' => 0]
        ]],
        ['name' => 'Hans', 'role' => 'Steward', 'image' => '/assets/media/logo.png', 'goals' => [
            ['name' => 'Kickback Store Page', 'progress' => 97],
            ['name' => 'Write out 30 canon Heroes for L.I.C.H.', 'progress' => 0]
        ]],
    ],
    'Expansion Division' => [
        ['name' => 'Tylor', 'role' => 'Chancellor', 'image' => '/assets/media/logo.png', 'goals' => [
            ['name' => '5% Growth', 'progress' => $growthProgress],
            ['name' => '20% Retention', 'progress' => $retentionProgress],
            ['name' => 'Maintain Social Media', 'progress' => 100]
        ]],
        ['name' => 'Andy', 'role' => 'Magister of the Adventurers\' Guild', 'image' => '/assets/media/logo.png', 'goals' => [
            ['name' => '5% Growth', 'progress' => $growthProgress],
            ['name' => '20% Retention', 'progress' => $retentionProgress],
            ['name' => 'Maintain Game Servers', 'progress' => 100]
        ]],
    ]
];

$eventData = [
    [
        'title' => 'Spring Treasure Hunt',
        'type' => 'Treasure Hunt',
        'icon' => 'fa-map',
        'color' => 'success',
        'date' => 'April 15, 2025',
        'desc' => 'A new kingdom-wide treasure hunt begins. Hidden clues, shiny loot.',
    ],
    [
        'title' => 'Chancellor Strategy Briefing',
        'type' => 'Meeting',
        'icon' => 'fa-chess-board',
        'color' => 'primary',
        'date' => 'April 7, 2025',
        'desc' => 'Quarterly sync between Horsemen and Chancellors.',
    ],
    [
        'title' => 'Expansion Partner Roundtable',
        'type' => 'Partnership Event',
        'icon' => 'fa-handshake',
        'color' => 'warning',
        'date' => 'April 12, 2025',
        'desc' => 'Discuss collaboration with potential allies.',
    ]
];
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
                
                
                $activePageName = "Steward's Guild";
                require("php-components/base-page-breadcrumbs.php"); 
                
                ?>

                <?php if (Session::isSteward()) { ?>
                    <div class="row mb-4">
<?php
foreach ($divisionData as $division) {
    $divisionComplete = $division['progress'] >= 100;
    $cardClass = $divisionComplete ? 'bg-ranked-1' : '';

    echo "
    <div class='col-md-4'>
        <div class='card shadow-sm mb-4 h-100 {$cardClass}'>
            <div class='card-body d-flex flex-column align-items-center text-center'>

                <i class='fa-solid {$division['icon']} fa-2x text-primary mb-2'></i>
                <h5 class='mb-1'>{$division['name']}</h5>
                <p class='text-muted small'>{$division['desc']}</p>

                <div class='bg-light rounded p-2 w-100 my-3 text-start small'>
                    <div class='fw-semibold text-dark mb-1'>
                        <i class='fa-solid fa-flag-checkered me-2 text-warning'></i>Division Goal
                    </div>
                    <div class='text-muted'>{$division['goal']}</div>
                </div>

                <div class='w-100'>
                    <div class='d-flex justify-content-between small'>
                        <span class='text-muted'><i class='fa-solid fa-chart-line me-1'></i>Progress</span>
                        <span class='text-muted'>{$division['progress']}%</span>
                    </div>
                    <div class='progress' style='height: 6px;'>
                        <div class='progress-bar bg-success' role='progressbar' style='width: {$division['progress']}%'></div>
                    </div>
                </div>

            </div>
        </div>
    </div>";
}
?>
</div>


<?php if (!empty($newsItems)): ?>
    <div class="card shadow-sm mb-5">
        <div class="card-body">
            <h4 class="mb-4"><i class="fa-solid fa-bullhorn me-2 text-info"></i>Latest Guild News</h4>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Title</th>
                            <th>Summary</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($newsItems as $news): ?>
                            <tr>
                                <td class="text-muted small"><?= $news->date ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($news->title) ?></td>
                                <td><?= htmlspecialchars($news->summary) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>


<div class="card shadow-sm mb-5">
    <div class="card-body">
        <h4 class="mb-4"><i class="fa-solid fa-users-gear me-2 text-primary"></i>Steward Goals by Division</h4>

        <?php

        foreach ($stewardsByDivision as $divisionName => $members) {
            echo "<h5 class='mt-4 text-uppercase text-muted'><i class='fa-solid fa-angle-down me-2'></i>{$divisionName}</h5>";
            echo "<div class='row g-3'>";
            foreach ($members as $member) {
                // Check if all goals are 100%
                $allGoalsComplete = true;
                foreach ($member['goals'] as $goal) {
                    if ($goal['progress'] < 100) {
                        $allGoalsComplete = false;
                        break;
                    }
                }
            
                // Conditionally add the class
                $cardClass = $allGoalsComplete ? 'bg-ranked-1' : '';
            
                echo "
                <div class='col-md-6'>
                    <div class='card border-0 shadow-sm h-100 {$cardClass}'>
                        <div class='card-body'>
                            <h6 class='mb-3 d-flex align-items-center'>
                                <img src='{$member['image']}' class='rounded-circle me-2' style='width: 28px; height: 28px; object-fit: cover; border: 1px solid #ccc;'>
                                <div>
                                    <strong>{$member['name']}</strong>
                                    <span class='badge bg-light text-dark border ms-2'>{$member['role']}</span>
                                </div>
                            </h6>";
            
                foreach ($member['goals'] as $goal) {
                    $progress = $goal['progress'];
                    echo "
                    <div class='mb-3'>
                        <div class='d-flex justify-content-between small'>
                            <span><i class='fa-solid fa-bullseye me-2 text-success'></i>{$goal['name']}</span>
                            <span class='text-muted'>{$progress}%</span>
                        </div>
                        <div class='progress' style='height: 6px;'>
                            <div class='progress-bar bg-success' role='progressbar' style='width: {$progress}%'></div>
                        </div>
                    </div>";
                }
            
                echo "      </div>
                    </div>
                </div>";
            }
            
            echo "</div>";
        }
        ?>
    </div>
</div>


<div class="card shadow-sm mb-5">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">
                <i class="fa-solid fa-map me-2 text-success"></i>Active Treasure Hunts
            </h4>
            <a href="/events/create.php?type=treasure-hunt" class="btn btn-sm btn-success disabled">
                <i class="fa-solid fa-plus me-1"></i> New Treasure Hunt
            </a>
        </div>

        <?php if (!empty($currentTreasureHunts)): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Event</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Description</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($currentTreasureHunts as $i => $event): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td>
                                    <?php if ($event->icon): ?>
                                        <img src="<?= $event->icon->url ?>" alt="icon" width="24" height="24" class="rounded me-2" style="object-fit: cover;">
                                    <?php else: ?>
                                        <i class="fa-solid fa-map text-muted me-2"></i>
                                    <?php endif; ?>
                                    <?= htmlspecialchars($event->name) ?>
                                </td>
                                <td><?= $event->startDate->formattedBasic ?></td>
                                <td><?= $event->endDate->formattedBasic ?></td>
                                <td class="text-muted small"><?= htmlspecialchars($event->desc) ?></td>
                                <td class="text-end">
                                    <a href="<?= $event->getURL() ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> View
                                    </a>
                                    <?php if ($event->canEdit()): ?>
                                        <a href="/events/edit.php?locator=<?= urlencode($event->locator) ?>" class="btn btn-sm btn-outline-secondary ms-2 disabled">
                                            <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-muted fst-italic">No active treasure hunts right now.</div>
        <?php endif; ?>
    </div>
</div>




                <?php } else { ?>
                    <div class="card mt-4 shadow-sm rounded">
                        <div class="card-body text-center py-4">
                            <!-- Centered and bigger Image of Merchant Guild Share -->
                            <div class="mb-4">
                                <img src="/assets/media/logo.png" alt="Merchant Guild Share" style="width: 150px; height: 150px;">
                            </div>

                            <!-- Access Restricted Title with the Warning Icon -->
                            <h5><i class="fa-solid fa-exclamation-triangle fa-lg me-2 text-muted"></i> Access Restricted</h5>
                        
                            <p class="mb-4">You must be logged in to gain access to the Stewards' Guild.</p>

                            <!-- Login Button -->
                            <a href="<?= Version::urlBetaPrefix(); ?>/login.php?redirect=stewards-guild.php" class="btn btn-primary">Log In</a>

                        </div>
                    </div>
                <?php } ?>
            </div>
            
            <?php require("php-components/base-page-discord.php"); ?>
        </div>
        <?php require("php-components/base-page-footer.php"); ?>
    </main>

    
    <?php require("php-components/base-page-javascript.php"); ?>

</body>

</html>
