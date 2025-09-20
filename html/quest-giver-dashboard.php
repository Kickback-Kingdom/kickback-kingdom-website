<?php
$pageTitle = "Quest Giver Dashboard";
$pageImage = "https://kickback-kingdom.com/assets/media/context/loading.gif";
$pageDesc = "Manage your quests and reviews";

require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

use Kickback\Services\Session;
use Kickback\Backend\Controllers\FeedCardController;
use Kickback\Backend\Services\QuestDashboardService;
use Kickback\Backend\Views\vDateTime;
use Kickback\Common\Version;

if (!Session::isQuestGiver()) {
    Session::redirect("index.php");
}

$account = Session::getCurrentAccount();

$dashboardService = new QuestDashboardService();
$dashboardData = $dashboardService->buildDashboard($account);

$overview = $dashboardData['overview'];
$reviewsData = $dashboardData['reviews'];
$suggestions = $dashboardData['suggestions'];
$questLinesData = $dashboardData['questLines'];
$topData = $dashboardData['top'];
$rawData = $dashboardData['raw'];

$futureQuests = $rawData['futureQuests'];
$pastQuests = $rawData['pastQuests'];
$questReviewAverages = $reviewsData['summaries'];

$questTitles = $reviewsData['chart']['questTitles'];
$avgHostRatings = $reviewsData['chart']['avgHostRatings'];
$avgQuestRatings = $reviewsData['chart']['avgQuestRatings'];
$participantQuestTitles = $reviewsData['chart']['participantQuestTitles'];
$participantCounts = $reviewsData['chart']['participantCounts'];
$ratingDates = $reviewsData['chart']['ratingDates'];
$avgRatingsOverTime = $reviewsData['chart']['avgRatingsOverTime'];

$totalHostedQuests = $overview['totals']['hosted'];
$totalUniqueParticipants = $overview['participants']['unique'];
$avgHostRatingRecent = $overview['ratings']['recentHost'];
$avgQuestRatingRecent = $overview['ratings']['recentQuest'];

$recommendedQuest = $suggestions['recommendedQuest'];
$dormantQuest = $suggestions['dormantQuest'];
$fanFavoriteQuest = $suggestions['fanFavoriteQuest'];
$hiddenGemQuest = $suggestions['hiddenGemQuest'];
$underperformingQuest = $suggestions['underperformingQuest'];
$coHostCandidates = $suggestions['coHostCandidates'];

$questLineStatusCounts = $questLinesData['statusCounts'];
$questLineStatsList = $questLinesData['lines'];
$questLinesError = $questLinesData['error'];

$topParticipants = $topData['participants'];
$topCoHosts = $topData['coHosts'];
$topBestQuests = $topData['quests'];

function renderStarRating(float $rating): string
{
    $rounded = round($rating * 2) / 2;
    $stars = '<span class="star-rating" style="pointer-events: none; display: inline-block;">';
    for ($i = 1; $i <= 5; $i++) {
        if ($rounded >= $i) {
            $class = 'fa-solid fa-star selected';
        } elseif ($rounded >= $i - 0.5) {
            $class = 'fa-solid fa-star-half-stroke selected';
        } else {
            $class = 'fa-regular fa-star';
        }
        $stars .= "<i class=\"{$class}\"></i>";
    }
    return $stars . '</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<?php require("php-components/base-page-head.php"); ?>
<body class="bg-body-secondary container p-0">
<?php
    require("php-components/base-page-components.php");
    require("php-components/ad-carousel.php");
?>
<main class="container pt-3 bg-body" style="margin-bottom: 56px;">
<?php
    $activePageName = "Quest Giver Dashboard";
    require("php-components/base-page-breadcrumbs.php");
?>

    <div id="questCloneAlert" class="alert alert-dismissible fade d-none" role="alert">
        <span class="message"></span>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <div class="modal fade" id="questClonedModal" tabindex="-1" aria-labelledby="questClonedModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="questClonedModalLabel">Quest Cloned</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="modal-message mb-0"></p>
                </div>
                <div class="modal-footer">
                    <a class="btn btn-outline-secondary view-quest-link" role="button" href="#" target="_blank" rel="noopener">View quest</a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <div class="row text-center g-3 mt-3 mb-3">
        <div class="col-12 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <small>Total Quests Hosted</small>
                    <h3 class="mb-0"><?= $totalHostedQuests; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <small>Total Unique Participants</small>
                    <h3 class="mb-0"><?= $totalUniqueParticipants; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <small>Average Host Rating (Last 10)</small>
                    <div><?= renderStarRating($avgHostRatingRecent); ?><span class="ms-1"><?= number_format($avgHostRatingRecent, 2); ?>/5</span></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <small>Average Quest Rating (Last 10)</small>
                    <div><?= renderStarRating($avgQuestRatingRecent); ?><span class="ms-1"><?= number_format($avgQuestRatingRecent, 2); ?>/5</span></div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-12">
            <nav>
                <div class="nav nav-tabs" id="nav-tab" role="tablist">
                    <button class="nav-link active" id="nav-upcoming-tab" data-bs-toggle="tab" data-bs-target="#nav-upcoming" type="button" role="tab" aria-controls="nav-upcoming" aria-selected="true"><i class="fa-regular fa-calendar"></i></button>
                    <button class="nav-link" id="nav-review-inbox-tab" data-bs-toggle="tab" data-bs-target="#nav-review-inbox" type="button" role="tab" aria-controls="nav-review-inbox" aria-selected="false"><i class="fa-solid fa-inbox"></i></button>
                    <button class="nav-link" id="nav-reviews-tab" data-bs-toggle="tab" data-bs-target="#nav-reviews" type="button" role="tab" aria-controls="nav-reviews" aria-selected="false"><i class="fa-solid fa-star"></i></button>
                    <button class="nav-link" id="nav-graphs-tab" data-bs-toggle="tab" data-bs-target="#nav-graphs" type="button" role="tab" aria-controls="nav-graphs" aria-selected="false"><i class="fa-solid fa-chart-line"></i></button>
                    <button class="nav-link" id="nav-suggestions-tab" data-bs-toggle="tab" data-bs-target="#nav-suggestions" type="button" role="tab" aria-controls="nav-suggestions" aria-selected="false"><i class="fa-solid fa-lightbulb"></i></button>
                    <button class="nav-link" id="nav-quest-lines-tab" data-bs-toggle="tab" data-bs-target="#nav-quest-lines" type="button" role="tab" aria-controls="nav-quest-lines" aria-selected="false"><i class="fa-solid fa-route"></i></button>
                    <button class="nav-link" id="nav-schedule-tab" data-bs-toggle="tab" data-bs-target="#nav-schedule" type="button" role="tab" aria-controls="nav-schedule" aria-selected="false"><i class="fa-solid fa-calendar-days"></i></button>
                    <button class="nav-link" id="nav-top-tab" data-bs-toggle="tab" data-bs-target="#nav-top" type="button" role="tab" aria-controls="nav-top" aria-selected="false"><i class="fa-solid fa-trophy"></i></button>
                </div>
            </nav>
            <div class="tab-content" id="nav-tabContent">
                <div class="tab-pane fade show active" id="nav-upcoming" role="tabpanel" aria-labelledby="nav-upcoming-tab" tabindex="0">
                    <div class="display-6 tab-pane-title">Upcoming Quests</div>
                    <?php if (count($futureQuests) === 0) { ?>
                        <p>No upcoming quests.</p>
                    <?php } else { foreach ($futureQuests as $quest) { ?>
                        <div class="mb-4">
                            <?php
                                $_vFeedCard = FeedCardController::vQuest_to_vFeedCard($quest);
                                require("php-components/vFeedCardRenderer.php");
                            ?>
                            <div class="text-end">
                                <button class="btn btn-sm btn-outline-secondary clone-quest-btn" data-quest-id="<?= $quest->crand; ?>" data-quest-title="<?= htmlspecialchars($quest->title); ?>">
                                    <i class="fa-regular fa-clone me-1"></i>Clone Quest
                                </button>
                            </div>
                        </div>
                    <?php }} ?>
                </div>
                <div class="tab-pane fade" id="nav-review-inbox" role="tabpanel" aria-labelledby="nav-review-inbox-tab" tabindex="0">
                    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2 mb-3">
                        <div class="display-6 tab-pane-title mb-0">Review Inbox</div>
                        <button id="claim-all-reviews" class="btn btn-primary btn-lg">Claim All</button>
                    </div>
                    <div class="table-responsive">
                        <table id="datatable-review-inbox" class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Quest</th>
                                    <th>Player</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                <div class="tab-pane fade" id="nav-reviews" role="tabpanel" aria-labelledby="nav-reviews-tab" tabindex="0">
                    <div class="display-6 tab-pane-title">Quest Reviews</div>
                    <?php if (count($questReviewAverages) === 0) { ?>
                        <p>No quest reviews yet.</p>
                    <?php } else { ?>
                        <div class="table-responsive">
                            <table id="datatable-reviews" class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Quest</th>
                                        <th>Date</th>
                                        <th>Average Host Rating</th>
                                        <th>Average Quest Rating</th>
                                        <th>Reviews</th>
                                        <th>Clone</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($questReviewAverages as $qr) { ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?= htmlspecialchars($qr->questIcon); ?>" class="rounded me-2" style="width:40px;height:40px;" alt="">
                                                    <a href="<?= htmlspecialchars(Version::formatUrl('/q/' . $qr->questLocator)); ?>" target="_blank"><?= htmlspecialchars($qr->questTitle); ?></a>
                                                </div>
                                            </td>
                                            <?php $qd = new vDateTime($qr->questEndDate); ?>
                                            <td data-order="<?= htmlspecialchars($qd->dbValue); ?>">
                                                <span class="date" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="<?= htmlspecialchars($qd->formattedDetailed); ?> UTC" data-datetime-utc="<?= htmlspecialchars($qd->valueString); ?>" data-db-value="<?= htmlspecialchars($qd->dbValue); ?>"><?= htmlspecialchars($qd->formattedBasic); ?></span>
                                            </td>
                                            <td data-order="<?= $qr->avgHostRating; ?>" class="align-middle">
                                                <?= renderStarRating($qr->avgHostRating); ?><span class="ms-1"><?= number_format($qr->avgHostRating, 2); ?></span>
                                            </td>
                                            <td data-order="<?= $qr->avgQuestRating; ?>" class="align-middle">
                                                <?= renderStarRating($qr->avgQuestRating); ?><span class="ms-1"><?= number_format($qr->avgQuestRating, 2); ?></span>
                                            </td>
                                            <td class="align-middle">
                                                <?php $btnClass = !empty($qr->hasComments) ? 'btn-primary' : 'btn-outline-secondary'; ?>
                                                <?php $iconClass = !empty($qr->hasComments) ? 'fa-solid' : 'fa-regular'; ?>
                                                <button class="btn btn-sm <?= $btnClass ?> view-reviews-btn" data-quest-id="<?= $qr->questId; ?>" data-quest-title="<?= htmlspecialchars($qr->questTitle); ?>" data-quest-banner="<?= htmlspecialchars($qr->questBanner); ?>"><i class="<?= $iconClass ?> fa-comments me-1"></i>View</button>
                                            </td>
                                            <td class="align-middle">
                                                <button class="btn btn-sm btn-outline-secondary clone-quest-btn" data-quest-id="<?= $qr->questId; ?>" data-quest-title="<?= htmlspecialchars($qr->questTitle); ?>"><i class="fa-regular fa-clone me-1"></i>Clone</button>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    <?php } ?>
                </div>
                <div class="tab-pane fade" id="nav-graphs" role="tabpanel" aria-labelledby="nav-graphs-tab" tabindex="0">
                    <div class="display-6 tab-pane-title">Quest Analytics</div>
                    <div class="accordion" id="graphAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingRatings">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRatings" aria-expanded="true" aria-controls="collapseRatings">
                                    Average Ratings
                                </button>
                            </h2>
                            <div id="collapseRatings" class="accordion-collapse collapse show" aria-labelledby="headingRatings">
                                <div class="accordion-body">
                                    <canvas id="reviewChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingRatingOverTime">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRatingOverTime" aria-expanded="false" aria-controls="collapseRatingOverTime">
                                    Average Quest Rating Over Time
                                </button>
                            </h2>
                            <div id="collapseRatingOverTime" class="accordion-collapse collapse" aria-labelledby="headingRatingOverTime">
                                <div class="accordion-body">
                                    <canvas id="ratingOverTimeChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingPerQuest">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePerQuest" aria-expanded="false" aria-controls="collapsePerQuest">
                                    Participants per Quest
                                </button>
                            </h2>
                            <div id="collapsePerQuest" class="accordion-collapse collapse" aria-labelledby="headingPerQuest">
                                <div class="accordion-body">
                                    <canvas id="participantPerQuestChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="nav-suggestions" role="tabpanel" aria-labelledby="nav-suggestions-tab" tabindex="0">
                    <div class="display-6 tab-pane-title">Suggestions</div>
                    <?php if ($recommendedQuest || $underperformingQuest || $dormantQuest || $fanFavoriteQuest || $hiddenGemQuest || !empty($coHostCandidates)) { ?>
                        <?php if ($dormantQuest) { ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-2">
                                        <?php if (!empty($dormantQuest['icon'])) { ?>
                                            <img src="<?= htmlspecialchars($dormantQuest['icon']); ?>" class="rounded me-3" style="width:60px;height:60px;" alt="">
                                        <?php } ?>
                                        <div>
                                            <h5 class="card-title mb-1">Revive this beloved quest with a fresh twist</h5>
                                            <?php $dormantLastRan = $dormantQuest['endDateFormatted'] ?? null; ?>
                                            <p class="card-text mb-1"><a href="<?= htmlspecialchars(Version::formatUrl('/q/' . $dormantQuest['locator'])); ?>" target="_blank"><?= htmlspecialchars($dormantQuest['title']); ?></a> last ran <?= $dormantLastRan !== null ? htmlspecialchars($dormantLastRan) : 'date TBD'; ?></p>
                        <p class="card-text mb-0">
                                                Quest Rating: <?= renderStarRating($dormantQuest['avgQuestRating']); ?><span class="ms-1"><?= number_format($dormantQuest['avgQuestRating'], 1); ?></span>
                                                &middot; Host Rating: <?= renderStarRating($dormantQuest['avgHostRating']); ?><span class="ms-1"><?= number_format($dormantQuest['avgHostRating'], 1); ?></span>
                                            </p>
                                        </div>
                                    </div>
                                    <p class="card-text mb-2"><?= QuestDashboardService::generateBringBackSuggestion($dormantQuest); ?></p>
                                    <div class="mt-2 d-flex flex-wrap gap-2">
                                        <button class="btn btn-sm btn-outline-primary view-reviews-btn" data-quest-id="<?= $dormantQuest['id']; ?>" data-quest-title="<?= htmlspecialchars($dormantQuest['title']); ?>" data-quest-banner="<?= htmlspecialchars($dormantQuest['banner']); ?>"><i class="fa-regular fa-comments me-1"></i>Reviews</button>
                                        <button class="btn btn-sm btn-outline-secondary clone-quest-btn" data-quest-id="<?= $dormantQuest['id']; ?>" data-quest-title="<?= htmlspecialchars($dormantQuest['title']); ?>"><i class="fa-regular fa-clone me-1"></i>Clone Quest</button>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                        <?php if ($fanFavoriteQuest) { ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-2">
                                        <?php if (!empty($fanFavoriteQuest['icon'])) { ?>
                                            <img src="<?= htmlspecialchars($fanFavoriteQuest['icon']); ?>" class="rounded me-3" style="width:60px;height:60px;" alt="">
                                        <?php } ?>
                                        <div>
                                            <h5 class="card-title mb-1">Reward loyal players with a long-awaited sequel</h5>
                                            <?php $fanFavoriteLastRan = $fanFavoriteQuest['endDateFormatted'] ?? null; ?>
                                            <p class="card-text mb-1"><a href="<?= htmlspecialchars(Version::formatUrl('/q/' . $fanFavoriteQuest['locator'])); ?>" target="_blank"><?= htmlspecialchars($fanFavoriteQuest['title']); ?></a> last ran <?= $fanFavoriteLastRan !== null ? htmlspecialchars($fanFavoriteLastRan) : 'date TBD'; ?></p>
                                            <p class="card-text mb-0">
                                                Quest Rating: <?= renderStarRating($fanFavoriteQuest['avgQuestRating']); ?><span class="ms-1"><?= number_format($fanFavoriteQuest['avgQuestRating'], 1); ?></span>
                                                &middot; Host Rating: <?= renderStarRating($fanFavoriteQuest['avgHostRating']); ?><span class="ms-1"><?= number_format($fanFavoriteQuest['avgHostRating'], 1); ?></span>
                                            </p>
                                        </div>
                                    </div>
                                    <p class="card-text mb-2"><?= QuestDashboardService::generateSequelSuggestion($fanFavoriteQuest); ?></p>
                                    <div class="mt-2 d-flex flex-wrap gap-2">
                                        <button class="btn btn-sm btn-outline-primary view-reviews-btn" data-quest-id="<?= $fanFavoriteQuest['id']; ?>" data-quest-title="<?= htmlspecialchars($fanFavoriteQuest['title']); ?>" data-quest-banner="<?= htmlspecialchars($fanFavoriteQuest['banner']); ?>"><i class="fa-regular fa-comments me-1"></i>Reviews</button>
                                        <button class="btn btn-sm btn-outline-secondary clone-quest-btn" data-quest-id="<?= $fanFavoriteQuest['id']; ?>" data-quest-title="<?= htmlspecialchars($fanFavoriteQuest['title']); ?>"><i class="fa-regular fa-clone me-1"></i>Clone Quest</button>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                        <?php if ($recommendedQuest) { ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-2">
                                        <?php if (!empty($recommendedQuest['icon'])) { ?>
                                            <img src="<?= htmlspecialchars($recommendedQuest['icon']); ?>" class="rounded me-3" style="width:60px;height:60px;" alt="">
                                        <?php } ?>
                                        <div>
                                            <h5 class="card-title mb-1">Launch a new quest inspired by your top performer</h5>
                                            <?php $recommendedLastRan = $recommendedQuest['endDateFormatted'] ?? null; ?>
                                            <p class="card-text mb-1"><a href="<?= htmlspecialchars(Version::formatUrl('/q/' . $recommendedQuest['locator'])); ?>" target="_blank"><?= htmlspecialchars($recommendedQuest['title']); ?></a> last ran <?= $recommendedLastRan !== null ? htmlspecialchars($recommendedLastRan) : 'date TBD'; ?></p>
                                            <p class="card-text mb-0">
                                                Quest Rating: <?= renderStarRating($recommendedQuest['avgQuestRating']); ?><span class="ms-1"><?= number_format($recommendedQuest['avgQuestRating'], 1); ?></span>
                                                &middot; Host Rating: <?= renderStarRating($recommendedQuest['avgHostRating']); ?><span class="ms-1"><?= number_format($recommendedQuest['avgHostRating'], 1); ?></span>
                                            </p>
                                        </div>
                                    </div>
                                    <p class="card-text mb-2"><?= QuestDashboardService::generateSimilarQuestSuggestion($recommendedQuest); ?></p>
                                    <div class="mt-2 d-flex flex-wrap gap-2">
                                        <button class="btn btn-sm btn-outline-primary view-reviews-btn" data-quest-id="<?= $recommendedQuest['id']; ?>" data-quest-title="<?= htmlspecialchars($recommendedQuest['title']); ?>" data-quest-banner="<?= htmlspecialchars($recommendedQuest['banner']); ?>"><i class="fa-regular fa-comments me-1"></i>Reviews</button>
                                        <button class="btn btn-sm btn-outline-secondary clone-quest-btn" data-quest-id="<?= $recommendedQuest['id']; ?>" data-quest-title="<?= htmlspecialchars($recommendedQuest['title']); ?>"><i class="fa-regular fa-clone me-1"></i>Clone Quest</button>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                        <?php if ($hiddenGemQuest) { ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-2">
                                        <?php if (!empty($hiddenGemQuest['icon'])) { ?>
                                            <img src="<?= htmlspecialchars($hiddenGemQuest['icon']); ?>" class="rounded me-3" style="width:60px;height:60px;" alt="">
                                        <?php } ?>
                                        <div>
                                            <h5 class="card-title mb-1">Relaunch this highly rated quest with stronger promotion</h5>
                                            <?php $hiddenGemLastRan = $hiddenGemQuest['endDateFormatted'] ?? null; ?>
                                            <p class="card-text mb-1"><a href="<?= htmlspecialchars(Version::formatUrl('/q/' . $hiddenGemQuest['locator'])); ?>" target="_blank"><?= htmlspecialchars($hiddenGemQuest['title']); ?></a> last ran <?= $hiddenGemLastRan !== null ? htmlspecialchars($hiddenGemLastRan) : 'date TBD'; ?></p>
                                            <p class="card-text mb-0">
                                                Quest Rating: <?= renderStarRating($hiddenGemQuest['avgQuestRating']); ?><span class="ms-1"><?= number_format($hiddenGemQuest['avgQuestRating'], 1); ?></span>
                                                &middot; Host Rating: <?= renderStarRating($hiddenGemQuest['avgHostRating']); ?><span class="ms-1"><?= number_format($hiddenGemQuest['avgHostRating'], 1); ?></span>
                                            </p>
                                        </div>
                                    </div>
                                    <p class="card-text mb-2"><?= QuestDashboardService::generatePromoteQuestSuggestion($hiddenGemQuest); ?></p>
                                    <div class="mt-2 d-flex flex-wrap gap-2">
                                        <button class="btn btn-sm btn-outline-primary view-reviews-btn" data-quest-id="<?= $hiddenGemQuest['id']; ?>" data-quest-title="<?= htmlspecialchars($hiddenGemQuest['title']); ?>" data-quest-banner="<?= htmlspecialchars($hiddenGemQuest['banner']); ?>"><i class="fa-regular fa-comments me-1"></i>Reviews</button>
                                        <button class="btn btn-sm btn-outline-secondary clone-quest-btn" data-quest-id="<?= $hiddenGemQuest['id']; ?>" data-quest-title="<?= htmlspecialchars($hiddenGemQuest['title']); ?>"><i class="fa-regular fa-clone me-1"></i>Clone Quest</button>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                        <?php if (!empty($coHostCandidates)) { ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title mb-3">Partner with a co-host to expand your reach</h5>
                                    <p class="card-text mb-3">Partner with reliable players to manage larger quests and reach new audiences.</p>
                                    <div class="table-responsive">
                                        <table class="table table-striped mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Player</th>
                                                    <th>Joined</th>
                                                    <th>Reliability</th>
                                                    <th>Hosted</th>
                                                    <th>Network</th>
                                                    <th>Last Quest</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($coHostCandidates as $coHostCandidate) { ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <?php if (!empty($coHostCandidate['avatar'])) { ?>
                                                                    <img src="<?= htmlspecialchars($coHostCandidate['avatar']); ?>" class="rounded me-2" style="width:40px;height:40px;" alt="">
                                                                <?php } ?>
                                                                <a href="<?= htmlspecialchars($coHostCandidate['url']); ?>" target="_blank" class="username"><?= htmlspecialchars($coHostCandidate['username']); ?></a>
                                                            </div>
                                                        </td>
                                                        <td class="align-middle"><?= $coHostCandidate['loyalty']; ?></td>
                                                        <td class="align-middle"><?= number_format($coHostCandidate['reliability'] * 100, 0); ?>%</td>
                                                        <td class="align-middle"><?= $coHostCandidate['questsHosted']; ?></td>
                                                        <td class="align-middle"><?= $coHostCandidate['network']; ?></td>
                                                        <td class="align-middle">
                                                            <?php if (isset($coHostCandidate['daysSinceLastQuest'])) { ?>
                                                                <?= $coHostCandidate['daysSinceLastQuest']; ?> day<?= $coHostCandidate['daysSinceLastQuest'] === 1 ? '' : 's'; ?> ago
                                                            <?php } else { ?>
                                                                &ndash;
                                                            <?php } ?>
                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                        <?php if ($underperformingQuest) { ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-2">
                                        <?php if (!empty($underperformingQuest['icon'])) { ?>
                                            <img src="<?= htmlspecialchars($underperformingQuest['icon']); ?>" class="rounded me-3" style="width:60px;height:60px;" alt="">
                                        <?php } ?>
                                        <div>
                                            <h5 class="card-title mb-1">Refine this quest to improve its performance</h5>
                                            <?php $underperformingLastRan = $underperformingQuest['endDateFormatted'] ?? null; ?>
                                            <p class="card-text mb-1"><a href="<?= htmlspecialchars(Version::formatUrl('/q/' . $underperformingQuest['locator'])); ?>" target="_blank"><?= htmlspecialchars($underperformingQuest['title']); ?></a> last ran <?= $underperformingLastRan !== null ? htmlspecialchars($underperformingLastRan) : 'date TBD'; ?></p>
                                            <p class="card-text mb-0">
                                                Quest Rating: <?= renderStarRating($underperformingQuest['avgQuestRating']); ?><span class="ms-1"><?= number_format($underperformingQuest['avgQuestRating'], 1); ?></span>
                                                &middot; Host Rating: <?= renderStarRating($underperformingQuest['avgHostRating']); ?><span class="ms-1"><?= number_format($underperformingQuest['avgHostRating'], 1); ?></span>
                                            </p>
                                        </div>
                                    </div>
                                    <p class="card-text mb-2"><?= QuestDashboardService::generateImproveQuestSuggestion($underperformingQuest); ?></p>
                                    <div class="mt-2 d-flex flex-wrap gap-2">
                                        <button class="btn btn-sm btn-outline-primary view-reviews-btn" data-quest-id="<?= $underperformingQuest['id']; ?>" data-quest-title="<?= htmlspecialchars($underperformingQuest['title']); ?>" data-quest-banner="<?= htmlspecialchars($underperformingQuest['banner']); ?>"><i class="fa-regular fa-comments me-1"></i>Reviews</button>
                                        <button class="btn btn-sm btn-outline-secondary clone-quest-btn" data-quest-id="<?= $underperformingQuest['id']; ?>" data-quest-title="<?= htmlspecialchars($underperformingQuest['title']); ?>"><i class="fa-regular fa-clone me-1"></i>Clone Quest</button>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    <?php } else { ?>
                        <p>No suggestions found. Keep hosting adventures!</p>
                    <?php } ?>
                </div>
                <div class="tab-pane fade" id="nav-quest-lines" role="tabpanel" aria-labelledby="nav-quest-lines-tab" tabindex="0">
                    <div class="display-6 tab-pane-title">Quest Lines</div>
                    <p class="text-muted">Monitor your quest lines and spot where to plan the next adventure.</p>
                    <?php if ($questLinesError) { ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($questLinesError); ?>
                        </div>
                    <?php } elseif (empty($questLines)) { ?>
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <p class="mb-2">You haven't created any quest lines yet.</p>
                                <a class="btn btn-primary" href="<?= Version::urlBetaPrefix(); ?>/quest-line.php?new"><i class="fa-solid fa-plus me-1"></i>Create your first quest line</a>
                            </div>
                        </div>
                    <?php } else { ?>
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <small>Total Quest Lines</small>
                                        <h3 class="mb-0"><?= $questLineStatusCounts['total']; ?></h3>
                                        <div class="text-muted small">With upcoming quests: <?= $questLineStatusCounts['withUpcoming']; ?></div>
                                        <div class="text-muted small">No quests yet: <?= $questLineStatusCounts['withoutQuests']; ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <small>Published</small>
                                        <h3 class="mb-0"><?= $questLineStatusCounts['published']; ?></h3>
                                        <div class="text-muted small">Need scheduling: <?= $questLineStatusCounts['needingScheduling']; ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <small>In Review</small>
                                        <h3 class="mb-0"><?= $questLineStatusCounts['inReview']; ?></h3>
                                        <div class="text-muted small">Pending approval</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <small>Drafts</small>
                                        <h3 class="mb-0"><?= $questLineStatusCounts['draft']; ?></h3>
                                        <div class="text-muted small">Finish setup to publish</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card mb-3">
                            <div class="card-header d-flex align-items-center">
                                <span class="fw-semibold">Quest Line Overview</span>
                                <a class="btn btn-sm btn-outline-primary ms-auto" href="<?= Version::urlBetaPrefix(); ?>/quest-line.php?new"><i class="fa-solid fa-plus me-1"></i>Create Quest Line</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover mb-0 align-middle">
                                        <thead>
                                            <tr>
                                                <th>Quest Line</th>
                                                <th>Quests</th>
                                                <th>Schedule</th>
                                                <th>Ratings</th>
                                                <th>Engagement</th>
                                                <th class="text-end">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($questLineStatsList as $lineStats) {
                                                $questLine = $lineStats['questLine'];
                                                $publicUrl = $questLine->url();
                                                $statusLabel = $questLine->reviewStatus->published ? 'Published' : ($questLine->reviewStatus->beingReviewed ? 'In Review' : 'Draft');
                                                $statusBadgeClass = $questLine->reviewStatus->published ? 'bg-success' : ($questLine->reviewStatus->beingReviewed ? 'bg-warning text-dark' : 'bg-secondary');
                                                $hasUpcoming = $lineStats['futureCount'] > 0;
                                                $needsScheduling = $questLine->reviewStatus->published && $lineStats['futureCount'] === 0 && $lineStats['questCount'] > 0;
                                                $noQuestsYet = $lineStats['questCount'] === 0;
                                            ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <?php if ($questLine->icon) { ?>
                                                                <img src="<?= htmlspecialchars($questLine->icon->getFullPath()); ?>" class="rounded me-2" style="width:48px;height:48px;object-fit:cover;" alt="">
                                                            <?php } ?>
                                                            <div>
                                                                <div class="fw-semibold"><?= htmlspecialchars($questLine->title); ?></div>
                                                                <div class="small">
                                                                    <span class="badge <?= $statusBadgeClass; ?> me-1"><?= $statusLabel; ?></span>
                                                                    <?php if ($hasUpcoming) { ?><span class="badge bg-info text-dark me-1">Upcoming quests</span><?php } ?>
                                                                    <?php if ($needsScheduling) { ?><span class="badge bg-warning text-dark me-1">Needs scheduling</span><?php } ?>
                                                                    <?php if ($noQuestsYet) { ?><span class="badge bg-secondary me-1">No quests yet</span><?php } ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="fw-semibold"><?= $lineStats['questCount']; ?></div>
                                                        <div class="small text-muted"><?= $lineStats['futureCount']; ?> upcoming &middot; <?= $lineStats['pastCount']; ?> past</div>
                                                        <div class="small text-muted">Published: <?= $lineStats['publishedQuests']; ?> &middot; Review: <?= $lineStats['inReviewQuests']; ?> &middot; Draft: <?= $lineStats['draftQuests']; ?></div>
                                                    </td>
                                                    <td>
                                                        <div class="small">
                                                            <div><strong>Next:</strong>
                                                                <?php if ($lineStats['futureCount'] > 0) { ?>
                                                                    <?php if ($lineStats['nextRun'] instanceof vDateTime) { ?>
                                                                        <?= $lineStats['nextRun']->getDateTimeElement(); ?>
                                                                    <?php } else { ?>
                                                                        <span class="text-muted">Date TBD</span>
                                                                    <?php } ?>
                                                                <?php } else { ?>
                                                                    <span class="text-muted">No quest scheduled</span>
                                                                <?php } ?>
                                                            </div>
                                                            <div><strong>Last:</strong>
                                                                <?php if ($lineStats['lastRun'] instanceof vDateTime) { ?>
                                                                    <?= $lineStats['lastRun']->getDateTimeElement(); ?>
                                                                <?php } elseif ($lineStats['pastCount'] > 0) { ?>
                                                                    <span class="text-muted">Date TBD</span>
                                                                <?php } else { ?>
                                                                    <span class="text-muted">Never</span>
                                                                <?php } ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php if ($lineStats['avgQuestRating'] !== null || $lineStats['avgHostRating'] !== null) { ?>
                                                            <div class="d-flex align-items-center">
                                                                <strong>Quest:</strong>
                                                                <?php if ($lineStats['avgQuestRating'] !== null) { ?>
                                                                    <span class="ms-2"><?= renderStarRating($lineStats['avgQuestRating']); ?></span>
                                                                <?php } else { ?>
                                                                    <span class="ms-2 text-muted">&ndash;</span>
                                                                <?php } ?>
                                                            </div>
                                                            <div class="d-flex align-items-center">
                                                                <strong>Host:</strong>
                                                                <?php if ($lineStats['avgHostRating'] !== null) { ?>
                                                                    <span class="ms-2"><?= renderStarRating($lineStats['avgHostRating']); ?></span>
                                                                <?php } else { ?>
                                                                    <span class="ms-2 text-muted">&ndash;</span>
                                                                <?php } ?>
                                                            </div>
                                                        <?php } else { ?>
                                                            <span class="text-muted">No reviews yet</span>
                                                        <?php } ?>
                                                    </td>
                                                    <td>
                                                        <div class="fw-semibold"><?= $lineStats['participantsTotal']; ?></div>
                                                        <div class="small text-muted">Registrations: <?= $lineStats['registeredTotal']; ?></div>
                                                        <div class="small text-muted">Attendance:
                                                            <?php if ($lineStats['attendanceRate'] !== null) { ?>
                                                                <?= number_format($lineStats['attendanceRate'] * 100, 0); ?>%
                                                            <?php } else { ?>
                                                                &ndash;
                                                            <?php } ?>
                                                        </div>
                                                    </td>
                                                    <td class="text-end">
                                                        <?php if ($questLine->reviewStatus->published) { ?>
                                                            <a href="<?= htmlspecialchars($publicUrl); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">View</a>
                                                        <?php } else { ?>
                                                            <button type="button" class="btn btn-sm btn-outline-secondary" disabled>View</button>
                                                        <?php } ?>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <?php if ($questLineStatusCounts['needingScheduling'] > 0) {
                            $count = $questLineStatusCounts['needingScheduling'];
                            $needsVerb = $count === 1 ? 'needs' : 'need';
                            $lineLabel = $count === 1 ? 'quest line' : 'quest lines';
                        ?>
                            <div class="alert alert-warning" role="alert">
                                <i class="fa-solid fa-bell me-2"></i><?= $count; ?> published <?= $lineLabel; ?> <?= $needsVerb; ?> a scheduled follow-up. Plan the next quest to keep players engaged.
                            </div>
                        <?php } ?>
                    <?php } ?>
                </div>
                <div class="tab-pane fade" id="nav-schedule" role="tabpanel" aria-labelledby="nav-schedule-tab" tabindex="0">
                    <div class="display-6 tab-pane-title">Schedule Planning</div>
                    <p>Review past quest performance to discover the best days and times for hosting. Plan future quests when participation has historically been highest.</p>
                    <div class="card card-body">
                        <h5>
                            <span id="scheduleCalendarMonth"></span>
                            <button class="btn btn-sm btn-outline-secondary float-end ms-2" id="scheduleNext">Next</button>
                            <button class="btn btn-sm btn-outline-secondary float-end" id="schedulePrev">Previous</button>
                        </h5>
                        <table id="scheduleCalendar" class="table table-sm table-bordered mb-0"></table>
                    </div>
                    <div class="d-flex align-items-center mt-2">
                        <div class="small text-muted flex-grow-1" id="calendarLegend">
                            <span class="badge bg-primary me-1">&nbsp;</span>Your quests
                            <span class="badge bg-secondary ms-2 me-1">&nbsp;</span>Other hosts
                            <span class="badge bg-success ms-2 me-1">&nbsp;</span>High participation
                            <span class="badge bg-warning ms-2 me-1">&nbsp;</span>Moderate
                            <span class="badge bg-danger ms-2 me-1">&nbsp;</span>Low
                            <span class="badge bg-light border border-danger ms-2 me-1">&nbsp;</span>Conflicts
                            <span class="ms-2">Numbers show participants per day. Hover for details and conflicts.</span>
                        </div>
                        <div class="form-check form-switch ms-3">
                            <input class="form-check-input" type="checkbox" id="showOtherHosts" checked>
                            <label class="form-check-label" for="showOtherHosts">Show other hosts</label>
                        </div>
                    </div>
                    <div class="card card-body mt-3">
                        <h5 class="mb-3">Suggested Next Quest Dates</h5>
                        <ul id="suggestedDatesList" class="list-group mb-0"></ul>
                    </div>
                    <div class="card card-body mt-3">
                        <h5 class="mb-3">Average Participation by Weekday</h5>
                        <canvas id="weekdayAveragesChart"></canvas>
                        <small class="text-muted">Average participants for quests run on each weekday.</small>
                    </div>
                    <div class="card card-body mt-3">
                        <h5 class="mb-3">Average Participation by Hour</h5>
                        <canvas id="hourlyAveragesChart"></canvas>
                        <small class="text-muted" id="hourlyPeakInfo"></small>
                    </div>
                </div>
                <div class="tab-pane fade" id="nav-top" role="tabpanel" aria-labelledby="nav-top-tab" tabindex="0">
                    <div class="display-6 tab-pane-title">Top Quests & Participants</div>
                    <div class="accordion" id="topAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingTopQuests">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTopQuests" aria-expanded="true" aria-controls="collapseTopQuests">
                                    Top 10 Quests
                                </button>
                            </h2>
                            <div id="collapseTopQuests" class="accordion-collapse collapse show" aria-labelledby="headingTopQuests">
                                <div class="accordion-body">
                                    <?php if (count($topBestQuests) === 0) { ?>
                                        <p>No completed quests.</p>
                                    <?php } else { ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Quest</th>
                                                        <th>Participants</th>
                                                        <th>Avg Quest Rating</th>
                                                        <th>Avg Host Rating</th>
                                                        <th>Reviews</th>
                                                        <th>Clone</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($topBestQuests as $q) { ?>
                                                        <tr>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <?php if (!empty($q['icon'])) { ?>
                                                                        <img src="<?= htmlspecialchars($q['icon']); ?>" class="rounded me-2" style="width:40px;height:40px;" alt="">
                                                                    <?php } ?>
                                                                    <a href="<?= htmlspecialchars(Version::formatUrl('/q/' . $q['locator'])); ?>" target="_blank"><?= htmlspecialchars($q['title']); ?></a>
                                                                </div>
                                                            </td>
                                                            <td class="align-middle"><?= $q['participants']; ?></td>
                                                            <td class="align-middle">
                                                                <?= renderStarRating($q['avgQuestRating']); ?><span class="ms-1"><?= number_format($q['avgQuestRating'], 2); ?></span>
                                                            </td>
                                                            <td class="align-middle">
                                                                <?= renderStarRating($q['avgHostRating']); ?><span class="ms-1"><?= number_format($q['avgHostRating'], 2); ?></span>
                                                            </td>
                                                            <td class="align-middle">
                                                                <button class="btn btn-sm btn-outline-primary view-reviews-btn" data-quest-id="<?= $q['id']; ?>" data-quest-title="<?= htmlspecialchars($q['title']); ?>" data-quest-banner="<?= htmlspecialchars($q['banner']); ?>"><i class="fa-regular fa-comments me-1"></i>Reviews</button>
                                                            </td>
                                                            <td class="align-middle">
                                                                <button class="btn btn-sm btn-outline-secondary clone-quest-btn" data-quest-id="<?= $q['id']; ?>" data-quest-title="<?= htmlspecialchars($q['title']); ?>"><i class="fa-regular fa-clone me-1"></i>Clone</button>
                                                            </td>
                                                        </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingTopParticipants">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTopParticipants" aria-expanded="false" aria-controls="collapseTopParticipants">
                                    Top 10 Loyal Participants
                                </button>
                            </h2>
                            <div id="collapseTopParticipants" class="accordion-collapse collapse" aria-labelledby="headingTopParticipants">
                                <div class="accordion-body">
                                    <?php if (count($topParticipants) === 0) { ?>
                                        <p>No participants yet.</p>
                                    <?php } else { ?>
                                        <div class="mb-3">
                                            <label for="participantSort" class="form-label">Sort by:</label>
                                            <select id="participantSort" class="form-select form-select-sm" style="max-width:200px;">
                                                <option value="loyalty">Quests Joined</option>
                                                <option value="reliability">Reliability</option>
                                                <option value="questshosted">Hosted Quests</option>
                                                <option value="network">Network Reach</option>
                                            </select>
                                        </div>
                                        <div class="row mb-3 g-2">
                                            <div class="col">
                                                <input type="number" id="reliabilityFilter" class="form-control form-control-sm" placeholder="Min reliability %">
                                            </div>
                                            <div class="col">
                                                <input type="number" id="hostedFilter" class="form-control form-control-sm" placeholder="Min hosted quests">
                                            </div>
                                            <div class="col">
                                                <input type="number" id="networkFilter" class="form-control form-control-sm" placeholder="Min network reach">
                                            </div>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-striped" id="topParticipantsTable">
                                                <thead>
                                                    <tr>
                                                        <th>Participant</th>
                                                        <th>Quests Joined</th>
                                                        <th>Reliability</th>
                                                        <th>Hosted Quests</th>
                                                        <th>Network</th>
                                                        <th>Avg Quest Rating</th>
                                                        <th>Avg Host Rating</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($topParticipants as $p) { ?>
                                                        <tr data-loyalty="<?= $p['loyalty']; ?>" data-reliability="<?= $p['reliability']; ?>" data-questshosted="<?= $p['questsHosted']; ?>" data-network="<?= $p['network']; ?>">
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <img src="<?= htmlspecialchars($p['avatar']); ?>" class="rounded me-2" style="width:40px;height:40px;" alt="">
                                                                    <div><a href="<?= htmlspecialchars($p['url']); ?>" target="_blank" class="username"><?= htmlspecialchars($p['username']); ?></a></div>
                                                                </div>
                                                            </td>
                                                            <td class="align-middle"><?= $p['loyalty']; ?></td>
                                                            <td class="align-middle"><?= number_format($p['reliability'] * 100, 0); ?>%</td>
                                                            <td class="align-middle"><?= $p['questsHosted']; ?></td>
                                                            <td class="align-middle"><?= $p['network']; ?></td>
                                                            <td class="align-middle">
                                                                <?= renderStarRating($p['avgQuestRating']); ?>
                                                                <span class="ms-1"><?= number_format($p['avgQuestRating'], 2); ?></span>
                                                            </td>
                                                            <td class="align-middle">
                                                                <?= renderStarRating($p['avgHostRating']); ?>
                                                                <span class="ms-1"><?= number_format($p['avgHostRating'], 2); ?></span>
                                                            </td>
                                                        </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingTopCoHosts">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTopCoHosts" aria-expanded="false" aria-controls="collapseTopCoHosts">
                                    Top 10 Co-Hosts
                                </button>
                            </h2>
                            <div id="collapseTopCoHosts" class="accordion-collapse collapse" aria-labelledby="headingTopCoHosts">
                                <div class="accordion-body">
                                    <?php if (count($topCoHosts) === 0) { ?>
                                        <p>No co-hosts yet.</p>
                                    <?php } else { ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Co-Host</th>
                                                        <th>Quests Co-Hosted</th>
                                                        <th>Avg Participants</th>
                                                        <th>Unique Participants</th>
                                                        <th>Avg Host Rating</th>
                                                        <th>Avg Quest Rating</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($topCoHosts as $h) { ?>
                                                        <tr>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <?php if (!empty($h['avatar'])) { ?>
                                                                        <img src="<?= htmlspecialchars($h['avatar']); ?>" class="rounded me-2" style="width:40px;height:40px;" alt="">
                                                                    <?php } ?>
                                                                    <div><a href="<?= htmlspecialchars($h['url']); ?>" target="_blank" class="username"><?= htmlspecialchars($h['username']); ?></a></div>
                                                                </div>
                                                            </td>
                                                            <td class="align-middle"><?= $h['questCount']; ?></td>
                                                            <td class="align-middle"><?= number_format($h['avgParticipants'], 1); ?></td>
                                                            <td class="align-middle"><?= $h['uniqueParticipants']; ?></td>
                                                            <td class="align-middle">
                                                                <?= renderStarRating($h['avgHostRating']); ?>
                                                                <span class="ms-1"><?= number_format($h['avgHostRating'], 2); ?></span>
                                                            </td>
                                                            <td class="align-middle">
                                                                <?= renderStarRating($h['avgQuestRating']); ?>
                                                                <span class="ms-1"><?= number_format($h['avgQuestRating'], 2); ?></span>
                                                            </td>
                                                        </tr>
                                                   <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reviewModalLabel">Quest Reviews</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <img id="reviewModalBanner" class="img-fluid mb-3" src="" alt="">
                    <div id="reviewModalBody"></div>
                </div>
            </div>
        </div>
    </div>

    <?php require("php-components/base-page-footer.php"); ?>
</main>
<?php require("php-components/base-page-javascript.php"); ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.js"></script>
<script>
const questTitles = <?= json_encode($questTitles); ?>;
const avgHostRatings = <?= json_encode($avgHostRatings); ?>;
const avgQuestRatings = <?= json_encode($avgQuestRatings); ?>;
const participantCounts = <?= json_encode($participantCounts); ?>;
const participantQuestTitles = <?= json_encode($participantQuestTitles); ?>;
const ratingDates = <?= json_encode($ratingDates); ?>;
const avgRatingsOverTime = <?= json_encode($avgRatingsOverTime); ?>;

function renderStarRatingJs(rating) {
    const rounded = Math.round(rating * 2) / 2;
    let stars = '<span class="star-rating" style="pointer-events: none; display: inline-block;">';
    for (let i = 1; i <= 5; i++) {
        let cls;
        if (rounded >= i) {
            cls = 'fa-solid fa-star selected';
        } else if (rounded >= i - 0.5) {
            cls = 'fa-solid fa-star-half-stroke selected';
        } else {
            cls = 'fa-regular fa-star';
        }
        stars += `<i class="${cls}"></i>`;
    }
    return stars + '</span>';
}

$(document).ready(function () {
    const sessionToken = "<?= $_SESSION['sessionToken']; ?>";
    const currentHostId = <?= $account->crand; ?>;

    let globalParticipationCounts = {};
    let personalParticipationCounts = {};
    let calendarEvents = {};
    let weekdayChart;
    let hourlyChart;
    let cloneAlertTimeout = null;
    let calMonth = (new Date()).getMonth();
    let calYear = (new Date()).getFullYear();
    const tzAbbr = new Date().toLocaleTimeString('en-us', { timeZoneName: 'short' }).split(' ').pop();
    document.getElementById('hourlyPeakInfo').textContent = 'Times shown in ' + tzAbbr + '. Loading peak hour(s)...';

    function renderScheduleCalendar() {
        const first = new Date(calYear, calMonth, 1);
        const today = new Date();
        const days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
        let header = '<thead><tr>' + days.map(d => `<th class="text-center">${d}</th>`).join('') + '</tr></thead>';
        const showOthers = $('#showOtherHosts').is(':checked');
        const countsMap = showOthers ? globalParticipationCounts : personalParticipationCounts;
        const daysInfo = [];
        let date = new Date(first);
        while (date.getMonth() === calMonth) {
            const cellDate = new Date(date);
            const dStr = `${date.getFullYear()}-${String(date.getMonth()+1).padStart(2,'0')}-${String(date.getDate()).padStart(2,'0')}`;
            const events = (calendarEvents[dStr] || []).filter(e => showOthers || !(e.host_id && e.host_id !== currentHostId));
            const totalParticipants = events.reduce((sum, e) => {
                const participants = parseInt(e.participants, 10);
                return sum + (Number.isNaN(participants) ? 0 : participants);
            }, 0);
            let mapCount;
            if (countsMap && Object.prototype.hasOwnProperty.call(countsMap, dStr)) {
                const parsed = parseInt(countsMap[dStr], 10);
                mapCount = Number.isNaN(parsed) ? 0 : parsed;
            }
            let count = 0;
            if (showOthers) {
                count = mapCount !== undefined ? mapCount : totalParticipants;
            } else if (events.length) {
                count = totalParticipants;
            } else if (mapCount !== undefined) {
                count = mapCount;
            }
            daysInfo.push({ date: cellDate, events: events, count: count });
            date.setDate(date.getDate() + 1);
        }
        const max = daysInfo.reduce((m, info) => Math.max(m, info.count || 0), 0);
        let body = '<tbody><tr>';
        for (let i = 0; i < first.getDay(); i++) { body += '<td></td>'; }
        daysInfo.forEach(function(info, idx) {
            let cls = 'align-top';
            if (info.date.toDateString() === today.toDateString()) { cls += ' calendar-today'; }
            if (info.count > 0 && max > 0) {
                const ratio = info.count / max;
                if (ratio > 0.66) { cls += ' bg-success text-white'; }
                else if (ratio > 0.33) { cls += ' bg-warning'; }
                else { cls += ' bg-danger text-white'; }
            }
            let tooltipLines = [`Participants: ${info.count}`];
            if (info.events.length > 1) { tooltipLines.push('Conflicting events'); }
            let tooltip = `data-bs-toggle=\"tooltip\" data-bs-html=\"true\" data-bs-placement=\"bottom\" title=\"${tooltipLines.join('<br>').replace(/\"/g, '&quot;')}\"`;
            if (info.events.length > 1) { cls += ' border border-danger border-2'; }
            else if (info.events.length === 1) { cls += ' border border-success border-2'; }
            let pills = '';
            if (info.events.length > 1) {
                pills += '<div class="badge bg-danger rounded-pill text-truncate mb-1">Conflict</div>';
            }
            info.events.forEach(function(e) {
                const start = new Date(e.start_date);
                const time = start.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
                const part = e.participants !== null ? ` - ${e.participants} participants` : '';
                const otherHost = e.host_id && e.host_id !== currentHostId;
                const hostInfo = otherHost && e.host_name ? ` - Host: ${e.host_name}` : '';
                const pillTip = `${e.title}${part}${hostInfo} @ ${time}`;
                const pillClass = otherHost ? 'bg-secondary' : 'bg-primary';
                pills += `<div class=\"badge ${pillClass} rounded-pill text-truncate mb-1 calendar-event-pill\" data-bs-toggle=\"tooltip\" title=\"${pillTip.replace(/\"/g,'&quot;')}\">${time}</div>`;
            });
            const countInfo = (!info.events.length && info.count) ? `<small>${info.count} participants</small>` : '';
            body += `<td class="${cls}" ${tooltip}><div class="schedule-calendar-cell"><div class="fw-bold">${info.date.getDate()}</div>${pills}${countInfo}</div></td>`;
            if (info.date.getDay() === 6 && idx !== daysInfo.length - 1) { body += '</tr><tr>'; }
        });
        const lastDay = new Date(calYear, calMonth + 1, 0);
        for (let i = lastDay.getDay(); i < 6; i++) { body += '<td></td>'; }
        body += '</tr></tbody>';
        $('#scheduleCalendarMonth').text(first.toLocaleString('default', { month: 'long', year: 'numeric' }));
        $('#scheduleCalendar').html(header + body);
        document.querySelectorAll('#scheduleCalendar [data-bs-toggle="tooltip"]').forEach(function (tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    function loadParticipationByDate() {
        const month = calMonth + 1;
        const year = calYear;

        function requestParticipationCounts(payload) {
            return new Promise(function(resolve, reject) {
                $.ajax({
                    url: '/api/v1/quest/participationByDate.php',
                    method: 'POST',
                    data: payload,
                    dataType: 'json',
                    success: resolve,
                    error: function(_, textStatus, errorThrown) {
                        reject(errorThrown || textStatus || 'Request failed');
                    }
                });
            });
        }

        const globalPromise = requestParticipationCounts({ includeAll: 1, month: month, year: year }).catch(function() { return null; });
        let personalPromise;
        if (sessionToken) {
            personalPromise = requestParticipationCounts({ sessionToken: sessionToken, month: month, year: year }).catch(function() { return null; });
        } else {
            personalPromise = Promise.resolve(null);
        }

        Promise.all([globalPromise, personalPromise]).then(function(responses) {
            if (month !== calMonth + 1 || year !== calYear) {
                return;
            }
            const [globalResp, personalResp] = responses;
            globalParticipationCounts = {};
            personalParticipationCounts = {};
            if (globalResp && globalResp.success && Array.isArray(globalResp.data)) {
                globalResp.data.forEach(function(r) {
                    if (r && r.date) {
                        const value = parseInt(r.participants, 10);
                        globalParticipationCounts[r.date] = Number.isNaN(value) ? 0 : value;
                    }
                });
            }
            if (personalResp && personalResp.success && Array.isArray(personalResp.data)) {
                personalResp.data.forEach(function(r) {
                    if (r && r.date) {
                        const value = parseInt(r.participants, 10);
                        personalParticipationCounts[r.date] = Number.isNaN(value) ? 0 : value;
                    }
                });
            }
            renderScheduleCalendar();
        });
    }

    function loadWeekdayAverages() {
        $.post('/api/v1/quest/participationAveragesByWeekday.php', { sessionToken: sessionToken }, function(resp) {
            if (resp && resp.success) {
                const personal = resp.data.personal;
                const global = resp.data.global;
                const labels = global.map(function(r) { return r.weekday; });
                const personalMap = {};
                personal.forEach(function(r) { personalMap[r.weekday] = r.avgParticipants; });
                const personalData = labels.map(function(l) { return personalMap[l] || 0; });
                const globalData = global.map(function(r) { return r.avgParticipants; });
                const ctx = document.getElementById('weekdayAveragesChart').getContext('2d');
                if (weekdayChart) { weekdayChart.destroy(); }
                weekdayChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'My Avg',
                                data: personalData,
                                backgroundColor: 'rgba(54, 162, 235, 0.6)'
                            },
                            {
                                label: 'Global Avg',
                                data: globalData,
                                backgroundColor: 'rgba(255, 159, 64, 0.6)'
                            }
                        ]
                    },
                    options: {
                        scales: {
                            yAxes: [{
                                ticks: { beginAtZero: true, precision: 0 }
                            }]
                        }
                    }
                });
            }
        }, 'json');
    }

    function loadHourlyAverages() {
        function formatHour(h) {
            const hour12 = h % 12 === 0 ? 12 : h % 12;
            const ampm = h < 12 ? 'AM' : 'PM';
            return hour12 + ' ' + ampm + ' ' + tzAbbr;
        }

        $.post('/api/v1/quest/participationAveragesByHour.php', { sessionToken: sessionToken }, function(resp) {
            if (resp && resp.success) {
                const personal = resp.data.personal;
                const global = resp.data.global;
                const personalMap = {};
                personal.forEach(function(r) { personalMap[r.hour] = r.avgParticipants; });
                const labels = global.map(function(r) { return formatHour(r.hour); });
                const personalData = global.map(function(r) { return personalMap[r.hour] || 0; });
                const globalData = global.map(function(r) { return r.avgParticipants; });
                const ctx = document.getElementById('hourlyAveragesChart').getContext('2d');
                if (hourlyChart) { hourlyChart.destroy(); }
                hourlyChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'My Avg',
                                data: personalData,
                                borderColor: 'rgba(54, 162, 235, 0.6)',
                                fill: false
                            },
                            {
                                label: 'Global Avg',
                                data: globalData,
                                borderColor: 'rgba(255, 159, 64, 0.6)',
                                fill: false
                            }
                        ]
                    },
                    options: {
                        scales: {
                            yAxes: [{
                                ticks: { beginAtZero: true, precision: 0 }
                            }]
                        }
                    }
                });

                const max = Math.max.apply(null, globalData);
                const peak = global.filter(function(r) { return r.avgParticipants === max; })
                    .map(function(r) { return formatHour(r.hour); });
                document.getElementById('hourlyPeakInfo').textContent = 'Times shown in ' + tzAbbr + '. Peak hour(s): ' + peak.join(', ');
            }
        }, 'json');
    }

    function loadSuggestedDates() {
        $.post('/api/v1/schedule/suggestedDates.php', { sessionToken: sessionToken, month: calMonth + 1, year: calYear }, function(resp) {
            const list = $('#suggestedDatesList');
            list.empty();
            if (resp && resp.success && resp.data.length) {
                resp.data.forEach(function(item, idx) {
                    const [y, m, d] = item.date.split('-').map(Number);
                    const dateObj = new Date(y, m - 1, d);
                    const formatted = dateObj.toLocaleDateString(undefined, {
                        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
                    });

                    const li = $('<li class="list-group-item"></li>');
                    const header = $('<div class="d-flex justify-content-between align-items-start"></div>');
                    const left = $('<div></div>');
                    if (idx === 0) {
                        left.append('<span class="badge bg-success me-2">Next quest</span>');
                    }
                    left.append($('<strong></strong>').text(formatted));
                    header.append(left);
                    if (typeof item.score !== 'undefined') {
                        header.append($('<span class="badge bg-primary"></span>').text(item.score));
                    }
                    li.append(header);
                    if (Array.isArray(item.reasons) && item.reasons.length) {
                        const ul = $('<ul class="mb-0 mt-2"></ul>');
                        item.reasons.forEach(function(r) {
                            ul.append($('<li></li>').text(r));
                        });
                        li.append(ul);
                    }
                    list.append(li);
                });
            } else {
                list.append('<li class="list-group-item">No suggestions available</li>');
            }
        }, 'json');
    }

    function loadCalendarEvents() {
        const showOthers = $('#showOtherHosts').is(':checked');
        $.post('/api/v1/schedule/events.php', { sessionToken: sessionToken, month: calMonth + 1, year: calYear, includeAll: 1 }, function(resp) {
            if (resp && resp.success) {
                calendarEvents = {};
                resp.data.forEach(function(e) {
                    const otherHost = e.host_id && e.host_id !== currentHostId;
                    if (!showOthers && otherHost) { return; }
                    const d = e.start_date.substring(0,10);
                    if (!calendarEvents[d]) { calendarEvents[d] = []; }
                    calendarEvents[d].push(e);
                });
                // sort events by start time for each day
                Object.keys(calendarEvents).forEach(function(k) {
                    calendarEvents[k].sort(function(a,b){ return new Date(a.start_date) - new Date(b.start_date); });
                });
                renderScheduleCalendar();
            }
        }, 'json');
    }

    $('#scheduleNext').on('click', function() {
        if (calMonth === 11) { calMonth = 0; calYear++; } else { calMonth++; }
        renderScheduleCalendar();
        loadCalendarEvents();
        loadParticipationByDate();
        loadSuggestedDates();
    });
    $('#schedulePrev').on('click', function() {
        if (calMonth === 0) { calMonth = 11; calYear--; } else { calMonth--; }
        renderScheduleCalendar();
        loadCalendarEvents();
        loadParticipationByDate();
        loadSuggestedDates();
    });

    loadParticipationByDate();
    loadCalendarEvents();
    loadWeekdayAverages();
    loadHourlyAverages();
    loadSuggestedDates();

    $('#showOtherHosts').on('change', function() {
        renderScheduleCalendar();
        loadCalendarEvents();
    });

    loadReviewInbox();

    $('#claim-all-reviews').on('click', function () {
        $.post('/api/v1/quest/claimAllReviews.php', { sessionToken: sessionToken }, function(resp) {
            if (resp.success) {
                loadReviewInbox();
            } else {
                alert(resp.message);
            }
        }, 'json');
    });

    function loadReviewInbox() {
        if ($.fn.DataTable.isDataTable('#datatable-review-inbox')) {
            $('#datatable-review-inbox').DataTable().destroy();
        }
        $.post('/api/v1/quest/reviewInbox.php', { sessionToken: sessionToken }, function(resp) {
            if (resp.success) {
                const tbody = $('#datatable-review-inbox tbody');
                tbody.empty();
                resp.data.forEach(function(r) {
                    const row = $('<tr></tr>');

                    const questCell = $('<td></td>');
                    const questWrapper = $('<div class="d-flex align-items-center"></div>');
                    questWrapper.append(
                        $('<img>').attr('src', r.questIcon).addClass('rounded me-2').css({ width: '40px', height: '40px' })
                    );
                    const questLink = $('<a></a>')
                        .attr('href', '/q/' + r.questLocator)
                        .attr('target', '_blank')
                        .text(r.questTitle);
                    questWrapper.append(questLink);
                    questCell.append(questWrapper);
                    row.append(questCell);

                    const playerLink = $('<a></a>').attr('href', '/u/' + r.username).attr('target', '_blank').addClass('username').text(r.username);
                    row.append($('<td></td>').append(playerLink));

                    const date = r.questEndDate;
                    const dateCell = $('<td></td>').attr('data-order', date.dbValue);
                    dateCell.append(`<span class="date" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="${date.formattedDetailed} UTC" data-datetime-utc="${date.valueString}" data-db-value="${date.dbValue}">${date.formattedBasic}</span>`);
                    row.append(dateCell);

                    const statusCell = $('<td class="status"></td>');
                    const statusContent = $('<div class="d-flex flex-column flex-sm-row align-items-sm-center gap-2"></div>');

                    if (r.hasReview) {
                        const statusText = $('<span class="status-text"></span>').text(r.viewed ? 'Viewed' : 'Pending');
                        statusContent.append(statusText);
                    } else {
                        statusContent.append($('<span class="status-text"></span>').text('Pending Review'));
                    }

                    statusCell.append(statusContent);
                    row.append(statusCell);

                    tbody.append(row);
                });

                tbody.find('.date').each(function () {
                    const utcDateTime = $(this).attr('data-datetime-utc');
                    if (utcDateTime) {
                        const localDate = new Date(utcDateTime);
                        const formattedDate = localDate.toLocaleDateString(undefined, {
                            weekday: 'short',
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric'
                        }) + ' ' + localDate.toLocaleTimeString();
                        $(this).text(formattedDate);
                    }
                });

                $('#datatable-review-inbox').DataTable({
                    pageLength: 10,
                    lengthChange: true,
                    order: [[2, 'desc']],
                    columnDefs: [{ targets: [3], orderable: false }]
                });
            } else {
                $('#datatable-review-inbox tbody').html('<tr><td colspan="4" class="text-danger">' + resp.message + '</td></tr>');
            }
        }, 'json');
    }

    $('#datatable-reviews').DataTable({
        pageLength: 10,
        lengthChange: true,
        columnDefs: [{ targets: [4, 5], orderable: false }],
        order: [[1, 'desc']]
    });

    function escapeHtml(str) {
        return $('<div>').text(str ?? '').html();
    }

    function resetCloneAlert() {
        const alertBox = $('#questCloneAlert');
        if (cloneAlertTimeout) {
            clearTimeout(cloneAlertTimeout);
            cloneAlertTimeout = null;
        }
        alertBox.removeClass('show alert-success alert-danger').addClass('d-none');
        alertBox.find('.message').empty();
    }

    function showCloneSuccessModal(title, locator) {
        resetCloneAlert();
        const modal = $('#questClonedModal');
        const messageEl = modal.find('.modal-message');
        const viewBtn = modal.find('.view-quest-link');
        const safeTitle = title ? escapeHtml(title) : 'Quest';
        messageEl.html('Quest <strong>' + safeTitle + '</strong> cloned successfully.');

        if (locator) {
            const viewUrl = '/q/' + encodeURIComponent(locator);
            viewBtn.attr('href', viewUrl)
                .removeClass('d-none disabled')
                .removeAttr('aria-disabled')
                .removeAttr('tabindex');
        } else {
            viewBtn.attr('href', '#')
                .addClass('d-none')
                .attr('aria-disabled', 'true')
                .attr('tabindex', '-1');
        }

        modal.modal('show');
    }

    function showCloneAlert(message, isError = false) {
        const alertBox = $('#questCloneAlert');
        if (cloneAlertTimeout) {
            clearTimeout(cloneAlertTimeout);
            cloneAlertTimeout = null;
        }
        alertBox.removeClass('d-none alert-success alert-danger show');
        alertBox.addClass(isError ? 'alert-danger' : 'alert-success');
        alertBox.find('.message').html(message);
        alertBox.removeClass('d-none');
        alertBox.addClass('show');
        if (!isError) {
            cloneAlertTimeout = setTimeout(() => {
                alertBox.removeClass('show');
                setTimeout(() => {
                    alertBox.addClass('d-none').removeClass('alert-success alert-danger');
                    alertBox.find('.message').empty();
                }, 200);
            }, 8000);
        }
    }

    $('#questCloneAlert').on('close.bs.alert', function (event) {
        event.preventDefault();
        if (cloneAlertTimeout) {
            clearTimeout(cloneAlertTimeout);
            cloneAlertTimeout = null;
        }
        const alertBox = $(this);
        alertBox.removeClass('show');
        setTimeout(() => {
            alertBox.addClass('d-none').removeClass('alert-success alert-danger');
            alertBox.find('.message').empty();
        }, 200);
    });

    $(document).on('click', '.clone-quest-btn', function () {
        const btn = $(this);
        const questId = parseInt(btn.data('quest-id'), 10);
        const questTitle = btn.data('quest-title') || '';

        if (!questId) {
            showCloneAlert('Unable to determine which quest to clone.', true);
            return;
        }

        btn.prop('disabled', true);
        $.post('/api/v1/quest/cloneQuest.php', { sessionToken: sessionToken, questId: questId }, function (resp) {
            if (resp && resp.success && resp.data) {
                const locator = resp.data.locator || '';
                const title = resp.data.title || questTitle;
                showCloneSuccessModal(title, locator);
            } else if (resp && !resp.success) {
                showCloneAlert(escapeHtml(resp.message || 'Failed to clone quest.'), true);
            } else {
                showCloneAlert('Failed to clone quest.', true);
            }
        }, 'json')
        .fail(function () {
            showCloneAlert('Failed to clone quest.', true);
        })
        .always(function () {
            btn.prop('disabled', false);
        });
    });

    function updateParticipantTable() {
        const sortKey = $('#participantSort').val();
        const relMin = parseFloat($('#reliabilityFilter').val()) || 0;
        const hostedMin = parseFloat($('#hostedFilter').val()) || 0;
        const networkMin = parseFloat($('#networkFilter').val()) || 0;
        const rows = $('#topParticipantsTable tbody tr').get();

        rows.forEach(row => {
            const rel = parseFloat($(row).data('reliability')) * 100;
            const hosted = parseFloat($(row).data('questshosted'));
            const network = parseFloat($(row).data('network'));
            if (rel >= relMin && hosted >= hostedMin && network >= networkMin) {
                $(row).show();
            } else {
                $(row).hide();
            }
        });

        rows.sort((a, b) => {
            const aVal = parseFloat($(a).data(sortKey));
            const bVal = parseFloat($(b).data(sortKey));
            return bVal - aVal;
        });
        $.each(rows, (idx, row) => $('#topParticipantsTable tbody').append(row));
    }

    $('#participantSort, #reliabilityFilter, #hostedFilter, #networkFilter').on('input change', updateParticipantTable);
    updateParticipantTable();

    $(document).on('click', '.view-reviews-btn', function () {
        const questId = $(this).data('quest-id');
        const title = $(this).data('quest-title');
        const banner = $(this).data('quest-banner');
        $('#reviewModalLabel').text(title + ' Reviews');
        $('#reviewModalBanner').attr('src', banner);
        $('#reviewModalBody').html('<div class="text-center p-3"><i class="fa fa-spinner fa-spin"></i></div>');
        $('#reviewModal').modal('show');

        $.post('/api/v1/quest/reviews.php', { questId: questId, sessionToken: sessionToken }, function (resp) {
            if (resp.success) {
                const list = $('<div class="list-group"></div>');
                resp.data.forEach(function (r) {
                    const item = $('<div class="list-group-item d-flex align-items-start"></div>');
                    const img = $('<img class="rounded me-3" style="width:40px;height:40px;">').attr('src', r.avatar);
                    const body = $('<div class="flex-grow-1"></div>');
                    body.append(`<div><a href="/u/${r.username}" class="username" target="_blank">${r.username}</a></div>`);
                    if (r.hostRating !== null) {
                        body.append('Host Rating: ' + renderStarRatingJs(r.hostRating) + '<br>');
                        body.append('Quest Rating: ' + renderStarRatingJs(r.questRating) + '<br>');
                        body.append(`<small>${r.message}</small>`);
                    } else {
                        body.append('<em>Review pending</em>');
                    }
                    item.append(img).append(body);
                    list.append(item);
                });
                $('#reviewModalBody').html(list);
            } else {
                $('#reviewModalBody').html('<div class="text-danger">' + resp.message + '</div>');
            }
        }, 'json');
    });
    var reviewCtx = document.getElementById('reviewChart').getContext('2d');
    new Chart(reviewCtx, {
        type: 'bar',
        data: {
            labels: questTitles,
            datasets: [
                {
                    label: 'Avg Host Rating',
                    data: avgHostRatings,
                    backgroundColor: 'rgba(75, 192, 192, 0.6)'
                },
                {
                    label: 'Avg Quest Rating',
                    data: avgQuestRatings,
                    backgroundColor: 'rgba(153, 102, 255, 0.6)'
                }
            ]
        },
        options: {
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        max: 5
                    }
                }]
            }
        }
    });

    var ratingOverTimeCtx = document.getElementById('ratingOverTimeChart').getContext('2d');
    new Chart(ratingOverTimeCtx, {
        type: 'line',
        data: {
            labels: ratingDates,
            datasets: [{
                label: 'Avg Quest Rating',
                data: avgRatingsOverTime,
                fill: false,
                borderColor: 'rgba(255, 206, 86, 1)',
                tension: 0.1
            }]
        },
        options: {
            scales: {
                xAxes: [{
                    type: 'time',
                    time: {
                        parser: 'YYYY-MM-DD',
                        unit: 'day',
                        displayFormats: {
                            day: 'MMM D'
                        }
                    }
                }],
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        max: 5
                    }
                }]
            }
        }
    });

    var perQuestCtx = document.getElementById('participantPerQuestChart').getContext('2d');
    new Chart(perQuestCtx, {
        type: 'bar',
        data: {
            labels: participantQuestTitles,
            datasets: [{
                label: 'Participants',
                data: participantCounts,
                backgroundColor: 'rgba(54, 162, 235, 0.6)'
            }]
        },
        options: {
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        precision: 0
                    }
                }]
            }
        }
    });
});
</script>
</body>
</html>
