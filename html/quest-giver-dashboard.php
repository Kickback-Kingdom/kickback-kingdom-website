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
    <section class="mt-4">
        <h3>Upcoming Quests</h3>
        <?php if (count($futureQuests) === 0) { ?>
            <p>No upcoming quests.</p>
        <?php } else { foreach ($futureQuests as $quest) {
            $_vFeedCard = FeedCardController::vQuest_to_vFeedCard($quest);
            require("php-components/vFeedCardRenderer.php");
        }} ?>
    </section>
    <section class="mt-5">
        <h3>Past Quests</h3>
        <?php if (count($pastQuests) === 0) { ?>
            <p>No past quests.</p>
        <?php } else { foreach ($pastQuests as $quest) {
            $_vFeedCard = FeedCardController::vQuest_to_vFeedCard($quest);
            require("php-components/vFeedCardRenderer.php");
        }} ?>
    </section>
    <section class="mt-5 mb-5">
        <h3>Quest Reviews</h3>
        <?php if (count($questReviews) === 0) { ?>
            <p>No quest reviews yet.</p>
        <?php } else { foreach ($questReviews as $qr) { ?>
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title"><a href="/q/<?= $qr['quest']->locator; ?>"><?= htmlspecialchars($qr['quest']->title); ?></a></h5>
                    <p class="card-text">From <a href="/u/<?= $qr['review']->fromAccount->username; ?>"><?= htmlspecialchars($qr['review']->fromAccount->username); ?></a> on <?= $qr['review']->dateTime->formattedBasic; ?></p>
                    <p class="card-text"><?= htmlspecialchars($qr['review']->message); ?></p>
                </div>
            </div>
        <?php }} ?>
    </section>
</main>
<?php require("php-components/base-page-javascript.php"); ?>
</body>
</html>
