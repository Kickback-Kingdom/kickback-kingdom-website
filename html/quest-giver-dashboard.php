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
$questReviews = $reviewsResp->success ? $reviewsResp->data : [];

$totalHostedQuests = count($futureQuests) + count($pastQuests);
$reviewCount = count($questReviews);
$hostRatingSum = 0;
$questRatingSum = 0;
foreach ($questReviews as $qr) {
    $hostRatingSum += $qr['review']->hostRating;
    $questRatingSum += $qr['review']->questRating;
}
$avgHostRating = $reviewCount > 0 ? $hostRatingSum / $reviewCount : 0;
$avgQuestRating = $reviewCount > 0 ? $questRatingSum / $reviewCount : 0;

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
                    <?php if (count($questReviews) === 0) { ?>
                        <p>No quest reviews yet.</p>
                    <?php } else { ?>
                        <div class="table-responsive">
                            <table id="datatable-reviews" class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Quest</th>
                                        <th>From</th>
                                        <th>Date</th>
                                        <th>Host Rating</th>
                                        <th>Quest Rating</th>
                                        <th>Comment</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($questReviews as $qr) {
                                        $comment = trim($qr['review']->message);
                                    ?>
                                        <tr>
                                            <td><a href="/q/<?= $qr['quest']->locator; ?>"><?= htmlspecialchars($qr['quest']->title); ?></a></td>
                                            <td><a href="/u/<?= $qr['review']->fromAccount->username; ?>"><?= htmlspecialchars($qr['review']->fromAccount->username); ?></a></td>
                                            <td><span class="date"><?= $qr['review']->dateTime->formattedBasic; ?></span></td>
                                            <td><?= renderStarRating($qr['review']->hostRating); ?></td>
                                            <td><?= renderStarRating($qr['review']->questRating); ?></td>
                                            <td>
                                                <?php if ($comment !== '') { ?>
                                                    <button class="btn btn-primary btn-sm toggle-comment" data-comment="<?= htmlspecialchars($comment, ENT_QUOTES); ?>">View Comment</button>
                                                <?php } else { ?>
                                                    <span class="text-muted">No Comment</span>
                                                <?php } ?>
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
    <?php require("php-components/base-page-footer.php"); ?>
</main>
<?php require("php-components/base-page-javascript.php"); ?>
<script>
$(document).ready(function () {
    var reviewTable = $('#datatable-reviews').DataTable({
        pageLength: 5,
        lengthChange: false
    });

    $('#datatable-reviews tbody').on('click', '.toggle-comment', function () {
        var tr = $(this).closest('tr');
        var row = reviewTable.row(tr);
        if (row.child.isShown()) {
            row.child.hide();
            tr.removeClass('shown');
        } else {
            var comment = $(this).attr('data-comment');
            row.child('<div class="p-3">'+comment+'</div>').show();
            tr.addClass('shown');
        }
    });
});
</script>
</body>
</html>
