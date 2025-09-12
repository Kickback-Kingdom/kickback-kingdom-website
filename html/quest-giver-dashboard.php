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
                    <?php } else { foreach ($questReviews as $qr) { ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="card-title"><a href="/q/<?= $qr['quest']->locator; ?>"><?= htmlspecialchars($qr['quest']->title); ?></a></h5>
                                <p class="card-text">From <a href="/u/<?= $qr['review']->fromAccount->username; ?>"><?= htmlspecialchars($qr['review']->fromAccount->username); ?></a> on <?= $qr['review']->dateTime->formattedBasic; ?></p>
                                <div class="row mb-2">
                                    <div class="col-6">
                                        <small>Host Rating</small>
                                        <?= renderStarRating($qr['review']->hostRating); ?>
                                    </div>
                                    <div class="col-6">
                                        <small>Quest Rating</small>
                                        <?= renderStarRating($qr['review']->questRating); ?>
                                    </div>
                                </div>
                                <p class="card-text"><?= htmlspecialchars($qr['review']->message); ?></p>
                            </div>
                        </div>
                    <?php }} ?>
                </div>
            </div>
        </div>
    </div>
</main>
<?php require("php-components/base-page-javascript.php"); ?>
</body>
</html>
