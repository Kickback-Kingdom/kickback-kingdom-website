<?php
$pageTitle = "Quest Giver Dashboard";
$pageImage = "https://kickback-kingdom.com/assets/media/context/loading.gif";
$pageDesc = "Manage your quests and reviews";

require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

use Kickback\Services\Session;
use Kickback\Backend\Controllers\QuestController;
use Kickback\Backend\Controllers\FeedCardController;
use Kickback\Backend\Views\vDateTime;
use Kickback\Common\Version;

if (!Session::isQuestGiver()) {
    Session::redirect("index.php");
}

$account = Session::getCurrentAccount();

$futureResp = QuestController::queryHostedFutureQuests($account);
$futureQuests = $futureResp->success ? $futureResp->data : [];

$pastResp = QuestController::queryHostedPastQuests($account);
$pastQuests = $pastResp->success ? $pastResp->data : [];

$reviewsResp = QuestController::queryQuestReviewsByHostAsResponse($account);
$questReviewAverages = $reviewsResp->success ? $reviewsResp->data : [];
usort($questReviewAverages, fn($a, $b) => strtotime($a->questEndDate) <=> strtotime($b->questEndDate));

$totalHostedQuests = count($futureQuests) + count($pastQuests);
$questTitles = array_map(fn($qr) => $qr->questTitle, $questReviewAverages);
$avgHostRatings = array_map(fn($qr) => (float)$qr->avgHostRating, $questReviewAverages);
$avgQuestRatings = array_map(fn($qr) => (float)$qr->avgQuestRating, $questReviewAverages);

$participantCounts = [];
$participantQuestTitles = [];
$uniqueParticipants = [];
$participantTotals = [];
$participantDetails = [];
$perQuestParticipantCounts = [];
$allQuests = array_merge($futureQuests, $pastQuests);
usort($allQuests, fn($a, $b) => strcmp(
    $a->hasEndDate() ? $a->endDate()->formattedYmd : '',
    $b->hasEndDate() ? $b->endDate()->formattedYmd : ''
));
foreach ($allQuests as $quest) {
    $participantsResp = QuestController::queryQuestApplicantsAsResponse($quest);
    $participants = $participantsResp->success ? $participantsResp->data : [];
    $count = count($participants);
    $participantCounts[] = $count;
    $participantQuestTitles[] = $quest->title;
    $perQuestParticipantCounts[$quest->title] = $count;
    foreach ($participants as $participant) {
        $crand = $participant->account->crand;
        $uniqueParticipants[$crand] = true;
        $participantTotals[$crand] = ($participantTotals[$crand] ?? 0) + 1;
        if (!isset($participantDetails[$crand])) {
            $participantDetails[$crand] = [
                'username' => $participant->account->username,
                'avatar' => $participant->account->avatar->getFullPath(),
                'url' => $participant->account->url(),
            ];
        }
    }
}
$totalUniqueParticipants = count($uniqueParticipants);

arsort($participantTotals);
$topParticipants = [];
foreach (array_slice($participantTotals, 0, 10, true) as $crand => $count) {
    $info = $participantDetails[$crand];
    $topParticipants[] = [
        'username' => $info['username'],
        'avatar' => $info['avatar'],
        'url' => $info['url'],
        'count' => $count,
    ];
}

$questRatingsMap = [];
foreach ($questReviewAverages as $qr) {
    $questRatingsMap[$qr->questTitle] = (float)$qr->avgQuestRating;
}

$bestQuestCandidates = [];
foreach ($pastQuests as $quest) {
    $title = $quest->title;
    $participants = $perQuestParticipantCounts[$title] ?? 0;
    $avgRating = $questRatingsMap[$title] ?? 0;
    $bestQuestCandidates[] = [
        'title' => $title,
        'locator' => $quest->locator,
        'icon' => $quest->icon ? $quest->icon->getFullPath() : '',
        'participants' => $participants,
        'avgRating' => $avgRating,
        'score' => $participants * $avgRating,
    ];
}
usort($bestQuestCandidates, fn($a, $b) => $b['score'] <=> $a['score']);
$topBestQuests = array_slice($bestQuestCandidates, 0, 5);

$ratingData = [];
foreach ($pastQuests as $quest) {
    $title = $quest->title;
    if (isset($questRatingsMap[$title]) && $quest->hasEndDate()) {
        $ratingData[] = [
            'date' => $quest->endDate()->formattedYmd,
            'rating' => $questRatingsMap[$title],
        ];
    }
}
usort($ratingData, fn($a, $b) => strcmp($a['date'], $b['date']));
$ratingDates = array_column($ratingData, 'date');
$avgRatingsOverTime = array_column($ratingData, 'rating');

$recentReviews = $questReviewAverages;
usort($recentReviews, fn($a, $b) => strtotime($b->questEndDate) <=> strtotime($a->questEndDate));
$recentReviews = array_slice($recentReviews, 0, 10);
$recentCount = count($recentReviews);
$avgHostRatingRecent = $recentCount > 0 ? array_sum(array_map(fn($qr) => (float)$qr->avgHostRating, $recentReviews)) / $recentCount : 0;
$avgQuestRatingRecent = $recentCount > 0 ? array_sum(array_map(fn($qr) => (float)$qr->avgQuestRating, $recentReviews)) / $recentCount : 0;

function renderStarRating(int $rating): string
{
    $stars = '<span class="star-rating" style="pointer-events: none; display: inline-block;">';
    for ($i = 1; $i <= 5; $i++) {
        $class = $i <= $rating ? 'fa-solid fa-star selected' : 'fa-regular fa-star';
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
    <h2>Quest Giver Dashboard</h2>
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
                    <div><?= renderStarRating((int)round($avgHostRatingRecent)); ?><span class="ms-1"><?= number_format($avgHostRatingRecent, 2); ?>/5</span></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <small>Average Quest Rating (Last 10)</small>
                    <div><?= renderStarRating((int)round($avgQuestRatingRecent)); ?><span class="ms-1"><?= number_format($avgQuestRatingRecent, 2); ?>/5</span></div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-12">
            <nav>
                <div class="nav nav-tabs" id="nav-tab" role="tablist">
                    <button class="nav-link active" id="nav-upcoming-tab" data-bs-toggle="tab" data-bs-target="#nav-upcoming" type="button" role="tab" aria-controls="nav-upcoming" aria-selected="true"><i class="fa-regular fa-calendar"></i></button>
                    <button class="nav-link" id="nav-past-tab" data-bs-toggle="tab" data-bs-target="#nav-past" type="button" role="tab" aria-controls="nav-past" aria-selected="false"><i class="fa-solid fa-clock-rotate-left"></i></button>
                    <button class="nav-link" id="nav-reviews-tab" data-bs-toggle="tab" data-bs-target="#nav-reviews" type="button" role="tab" aria-controls="nav-reviews" aria-selected="false"><i class="fa-solid fa-star"></i></button>
                    <button class="nav-link" id="nav-graphs-tab" data-bs-toggle="tab" data-bs-target="#nav-graphs" type="button" role="tab" aria-controls="nav-graphs" aria-selected="false"><i class="fa-solid fa-chart-line"></i></button>
                    <button class="nav-link" id="nav-top-tab" data-bs-toggle="tab" data-bs-target="#nav-top" type="button" role="tab" aria-controls="nav-top" aria-selected="false"><i class="fa-solid fa-trophy"></i></button>
                </div>
            </nav>
            <div class="tab-content" id="nav-tabContent">
                <div class="tab-pane fade show active" id="nav-upcoming" role="tabpanel" aria-labelledby="nav-upcoming-tab" tabindex="0">
                    <div class="display-6 tab-pane-title">Upcoming Quests</div>
                    <?php if (count($futureQuests) === 0) { ?>
                        <p>No upcoming quests.</p>
                    <?php } else { foreach ($futureQuests as $quest) {
                        $_vFeedCard = FeedCardController::vQuest_to_vFeedCard($quest);
                        require("php-components/vFeedCardRenderer.php");
                    }} ?>
                </div>
                <div class="tab-pane fade" id="nav-past" role="tabpanel" aria-labelledby="nav-past-tab" tabindex="0">
                    <div class="display-6 tab-pane-title">Past Quests</div>
                    <?php if (count($pastQuests) === 0) { ?>
                        <p>No past quests.</p>
                    <?php } else { foreach ($pastQuests as $quest) {
                        $_vFeedCard = FeedCardController::vQuest_to_vFeedCard($quest);
                        require("php-components/vFeedCardRenderer.php");
                    }} ?>
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
                                                <?= renderStarRating((int)round($qr->avgHostRating)); ?><span class="ms-1"><?= number_format($qr->avgHostRating, 2); ?></span>
                                            </td>
                                            <td data-order="<?= $qr->avgQuestRating; ?>" class="align-middle">
                                                <?= renderStarRating((int)round($qr->avgQuestRating)); ?><span class="ms-1"><?= number_format($qr->avgQuestRating, 2); ?></span>
                                            </td>
                                            <td class="align-middle">
                                                <?php $btnClass = !empty($qr->hasComments) ? 'btn-primary' : 'btn-outline-secondary'; ?>
                                                <?php $iconClass = !empty($qr->hasComments) ? 'fa-solid' : 'fa-regular'; ?>
                                                <button class="btn btn-sm <?= $btnClass ?> view-reviews-btn" data-quest-id="<?= $qr->questId; ?>" data-quest-title="<?= htmlspecialchars($qr->questTitle); ?>" data-quest-banner="<?= htmlspecialchars($qr->questBanner); ?>"><i class="<?= $iconClass ?> fa-comments me-1"></i>View</button>
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
                <div class="tab-pane fade" id="nav-top" role="tabpanel" aria-labelledby="nav-top-tab" tabindex="0">
                    <div class="display-6 tab-pane-title">Top Quests & Participants</div>
                    <div class="row">
                        <div class="col-12 col-lg-6 mb-3 mb-lg-0">
                            <h4>Top 5 Quests</h4>
                            <?php if (count($topBestQuests) === 0) { ?>
                                <p>No completed quests.</p>
                            <?php } else { ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Quest</th>
                                                <th>Participants</th>
                                                <th>Avg Rating</th>
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
                                                        <?= renderStarRating((int)round($q['avgRating'])); ?><span class="ms-1"><?= number_format($q['avgRating'], 2); ?></span>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php } ?>
                        </div>
                        <div class="col-12 col-lg-6">
                            <h4>Top 10 Loyal Participants</h4>
                            <?php if (count($topParticipants) === 0) { ?>
                                <p>No participants yet.</p>
                            <?php } else { ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Participant</th>
                                                <th>Quests Joined</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($topParticipants as $p) { ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <img src="<?= htmlspecialchars($p['avatar']); ?>" class="rounded me-2" style="width:40px;height:40px;" alt="">
                                                            <a href="<?= htmlspecialchars($p['url']); ?>" target="_blank"><?= htmlspecialchars($p['username']); ?></a>
                                                        </div>
                                                    </td>
                                                    <td class="align-middle"><?= $p['count']; ?></td>
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
    let stars = '<span class="star-rating" style="pointer-events: none; display: inline-block;">';
    for (let i = 1; i <= 5; i++) {
        const cls = i <= rating ? 'fa-solid fa-star selected' : 'fa-regular fa-star';
        stars += `<i class="${cls}"></i>`;
    }
    return stars + '</span>';
}

$(document).ready(function () {
    const sessionToken = "<?= $_SESSION['sessionToken']; ?>";

    $('#datatable-reviews').DataTable({
        pageLength: 10,
        lengthChange: true,
        columnDefs: [{ targets: [4], orderable: false }],
        order: [[1, 'desc']]
    });

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
                    body.append(`<strong>${r.username}</strong><br>`);
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
