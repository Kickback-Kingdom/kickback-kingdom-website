<?php
$pageTitle = "Quest Giver Dashboard";
$pageImage = "https://kickback-kingdom.com/assets/media/context/loading.gif";
$pageDesc = "Manage your quests and reviews";

require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

use Kickback\Services\Session;
use Kickback\Backend\Controllers\QuestController;
use Kickback\Backend\Controllers\NotificationController;
use Kickback\Backend\Controllers\FeedCardController;

if (!Session::isQuestGiver()) {
    Session::redirect("index.php");
}

$account = Session::getCurrentAccount();

$futureResp = QuestController::queryHostedFutureQuests($account);
$futureQuests = $futureResp->success ? $futureResp->data : [];

$pastResp = QuestController::queryHostedPastQuests($account);
$pastQuests = $pastResp->success ? $pastResp->data : [];

$reviewsResp = NotificationController::queryQuestReviewsByHostAsResponse($account);
$questReviewAverages = $reviewsResp->success ? $reviewsResp->data : [];

$totalHostedQuests = count($futureQuests) + count($pastQuests);
$questTitles = array_column($questReviewAverages, 'questTitle');
$avgHostRatings = array_map('floatval', array_column($questReviewAverages, 'avgHostRating'));
$avgQuestRatings = array_map('floatval', array_column($questReviewAverages, 'avgQuestRating'));

$participantCounts = [];
$participantQuestTitles = [];
foreach (array_merge($futureQuests, $pastQuests) as $quest) {
    $participantsResp = QuestController::queryQuestApplicantsAsResponse($quest);
    $participants = $participantsResp->success ? $participantsResp->data : [];
    $participantCounts[] = count($participants);
    $participantQuestTitles[] = $quest->title;
}

$questRatingsMap = [];
foreach ($questReviewAverages as $qr) {
    $questRatingsMap[$qr['questTitle']] = (float)$qr['avgQuestRating'];
}

$ratingDates = [];
$avgRatingsOverTime = [];
foreach ($pastQuests as $quest) {
    $title = $quest->title;
    if (isset($questRatingsMap[$title]) && $quest->hasEndDate()) {
        $ratingDates[] = $quest->endDate()->formattedYmd;
        $avgRatingsOverTime[] = $questRatingsMap[$title];
    }
}

$reviewCount = count($questReviewAverages);
$avgHostRating = $reviewCount > 0 ? array_sum($avgHostRatings) / $reviewCount : 0;
$avgQuestRating = $reviewCount > 0 ? array_sum($avgQuestRatings) / $reviewCount : 0;

function renderStarRating(int $rating): string
{
    $stars = '<div class="star-rating" style="pointer-events: none;">';
    for ($i = 1; $i <= 5; $i++) {
        $class = $i <= $rating ? 'fa-solid fa-star selected' : 'fa-regular fa-star';
        $stars .= "<i class=\"{$class}\"></i>";
    }
    return $stars . '</div>';
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
        <div class="col-12 col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <small>Total Quests Hosted</small>
                    <h3 class="mb-0"><?= $totalHostedQuests; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <small>Average Host Rating</small>
                    <?= renderStarRating((int)round($avgHostRating)); ?>
                    <div><?= number_format($avgHostRating, 2); ?>/5</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <small>Average Quest Rating</small>
                    <?= renderStarRating((int)round($avgQuestRating)); ?>
                    <div><?= number_format($avgQuestRating, 2); ?>/5</div>
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
                                            <td><?= htmlspecialchars($qr['questTitle']); ?></td>
                                            <td><?= htmlspecialchars($qr['questDate']); ?></td>
                                            <td data-order="<?= $qr['avgHostRating']; ?>">
                                                <?= renderStarRating((int)round($qr['avgHostRating'])); ?>
                                                <?= number_format($qr['avgHostRating'], 2); ?>
                                            </td>
                                            <td data-order="<?= $qr['avgQuestRating']; ?>">
                                                <?= renderStarRating((int)round($qr['avgQuestRating'])); ?>
                                                <?= number_format($qr['avgQuestRating'], 2); ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($qr['hasComments'])) { ?>
                                                    <i class="fa-solid fa-comment text-info me-1" title="Has comments"></i>
                                                <?php } ?>
                                                <button class="btn btn-sm btn-primary view-reviews-btn" data-quest-id="<?= $qr['questId']; ?>" data-quest-title="<?= htmlspecialchars($qr['questTitle']); ?>">View</button>
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
                <div class="modal-body" id="reviewModalBody"></div>
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
    let stars = '<div class="star-rating" style="pointer-events: none;">';
    for (let i = 1; i <= 5; i++) {
        const cls = i <= rating ? 'fa-solid fa-star selected' : 'fa-regular fa-star';
        stars += `<i class="${cls}"></i>`;
    }
    return stars + '</div>';
}

$(document).ready(function () {
    const sessionToken = "<?= $_SESSION['sessionToken']; ?>";

    $('#datatable-reviews').DataTable({
        pageLength: 5,
        lengthChange: true,
        columnDefs: [{ targets: [4], orderable: false }]
    });

    $(document).on('click', '.view-reviews-btn', function () {
        const questId = $(this).data('quest-id');
        const title = $(this).data('quest-title');
        $('#reviewModalLabel').text(title + ' Reviews');
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
