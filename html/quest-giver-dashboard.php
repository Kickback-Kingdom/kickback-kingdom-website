<?php
$pageTitle = "Quest Giver Dashboard";
$pageImage = "https://kickback-kingdom.com/assets/media/context/loading.gif";
$pageDesc = "Manage your quests and reviews";

require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

use Kickback\Services\Session;
use Kickback\Common\Version;

if (!Session::isQuestGiver()) {
    Session::redirect("index.php");
}

$account = Session::getCurrentAccount();

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
                <div class="card-body summary-card" data-summary-key="hosted">
                    <small>Total Quests Hosted</small>
                    <div class="summary-spinner text-center py-2">
                        <div class="spinner-border spinner-border-sm text-secondary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <div class="summary-error alert alert-danger d-none mt-2" role="alert"></div>
                    <h3 class="summary-value mb-0 d-none"></h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card h-100">
                <div class="card-body summary-card" data-summary-key="uniqueParticipants">
                    <small>Total Unique Participants</small>
                    <div class="summary-spinner text-center py-2">
                        <div class="spinner-border spinner-border-sm text-secondary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <div class="summary-error alert alert-danger d-none mt-2" role="alert"></div>
                    <h3 class="summary-value mb-0 d-none"></h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card h-100">
                <div class="card-body summary-card" data-summary-key="recentHost">
                    <small>Average Host Rating (Last 10)</small>
                    <div class="summary-spinner text-center py-2">
                        <div class="spinner-border spinner-border-sm text-secondary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <div class="summary-error alert alert-danger d-none mt-2" role="alert"></div>
                    <div class="summary-rating d-none"></div>
                    <div class="summary-value text-muted d-none"></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card h-100">
                <div class="card-body summary-card" data-summary-key="recentQuest">
                    <small>Average Quest Rating (Last 10)</small>
                    <div class="summary-spinner text-center py-2">
                        <div class="spinner-border spinner-border-sm text-secondary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <div class="summary-error alert alert-danger d-none mt-2" role="alert"></div>
                    <div class="summary-rating d-none"></div>
                    <div class="summary-value text-muted d-none"></div>
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
                    <div id="upcomingSection">
                        <div id="upcomingSpinner" class="section-spinner text-center py-3">
                            <div class="spinner-border text-secondary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <div id="upcomingError" class="alert alert-danger d-none" role="alert"></div>
                        <p id="upcomingEmpty" class="text-muted d-none">No upcoming quests.</p>
                        <div id="upcomingList" class="d-none"></div>
                    </div>
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
                    <div id="questReviewsSection">
                        <div id="questReviewsSpinner" class="section-spinner text-center py-3">
                            <div class="spinner-border text-secondary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <div id="questReviewsError" class="alert alert-danger d-none" role="alert"></div>
                        <p id="questReviewsEmpty" class="text-muted d-none">No quest reviews yet.</p>
                        <div id="questReviewsTableWrapper" class="table-responsive d-none">
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
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
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
                                    <div id="reviewChartSpinner" class="section-spinner text-center py-3">
                                        <div class="spinner-border text-secondary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                    <div id="reviewChartError" class="alert alert-danger d-none" role="alert"></div>
                                    <p id="reviewChartEmpty" class="text-muted d-none mb-0">Not enough data to render this chart.</p>
                                    <canvas id="reviewChart" class="d-none"></canvas>
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
                                    <div id="ratingOverTimeChartSpinner" class="section-spinner text-center py-3">
                                        <div class="spinner-border text-secondary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                    <div id="ratingOverTimeChartError" class="alert alert-danger d-none" role="alert"></div>
                                    <p id="ratingOverTimeChartEmpty" class="text-muted d-none mb-0">Not enough data to render this chart.</p>
                                    <canvas id="ratingOverTimeChart" class="d-none"></canvas>
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
                                    <div id="participantPerQuestChartSpinner" class="section-spinner text-center py-3">
                                        <div class="spinner-border text-secondary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                    <div id="participantPerQuestChartError" class="alert alert-danger d-none" role="alert"></div>
                                    <p id="participantPerQuestChartEmpty" class="text-muted d-none mb-0">Not enough data to render this chart.</p>
                                    <canvas id="participantPerQuestChart" class="d-none"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="nav-suggestions" role="tabpanel" aria-labelledby="nav-suggestions-tab" tabindex="0">
                    <div class="display-6 tab-pane-title">Suggestions</div>
                    <div id="suggestionsSection">
                        <div id="suggestionsSpinner" class="section-spinner text-center py-3">
                            <div class="spinner-border text-secondary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <div id="suggestionsError" class="alert alert-danger d-none" role="alert"></div>
                        <p id="suggestionsEmpty" class="text-muted d-none">No suggestions found. Keep hosting adventures!</p>
                        <div id="suggestionsContent" class="suggestions-list d-none">
                            <div id="suggestionCards" class="mb-3"></div>
                            <div id="coHostSuggestionCard" class="card d-none">
                                <div class="card-body">
                                    <h5 class="card-title mb-3">Partner with a co-host to expand your reach</h5>
                                    <p class="card-text mb-3">Partner with reliable players to manage larger quests and reach new audiences.</p>
                                    <div class="table-responsive">
                                        <table class="table table-striped mb-0" id="coHostSuggestionTable">
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
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="nav-quest-lines" role="tabpanel" aria-labelledby="nav-quest-lines-tab" tabindex="0">
                    <div class="display-6 tab-pane-title">Quest Lines</div>
                    <p class="text-muted">Monitor your quest lines and spot where to plan the next adventure.</p>
                    <div id="questLinesSection">
                        <div id="questLinesSpinner" class="section-spinner text-center py-3">
                            <div class="spinner-border text-secondary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <div id="questLinesError" class="alert alert-danger d-none" role="alert"></div>
                        <div id="questLinesEmpty" class="card border-0 shadow-sm d-none">
                            <div class="card-body text-center">
                                <p class="mb-2">You haven't created any quest lines yet.</p>
                                <a class="btn btn-primary" href="<?= Version::urlBetaPrefix(); ?>/quest-line.php?new"><i class="fa-solid fa-plus me-1"></i>Create your first quest line</a>
                            </div>
                        </div>
                        <div id="questLinesContent" class="d-none">
                            <div class="row g-3 mb-3">
                                <div class="col-12 col-md-3">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <small>Total Quest Lines</small>
                                            <h3 class="mb-0" data-quest-line-count="total" data-status-key="total">—</h3>
                                            <div class="text-muted small">With upcoming quests: <span data-quest-line-count="withUpcoming" data-status-key="withUpcoming">—</span></div>
                                            <div class="text-muted small">No quests yet: <span data-quest-line-count="withoutQuests" data-status-key="withoutQuests">—</span></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-3">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <small>Published</small>
                                            <h3 class="mb-0" data-quest-line-count="published" data-status-key="published">—</h3>
                                            <div class="text-muted small">Need scheduling: <span data-quest-line-count="needingScheduling" data-status-key="needingScheduling">—</span></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-3">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <small>In Review</small>
                                            <h3 class="mb-0" data-quest-line-count="inReview" data-status-key="inReview">—</h3>
                                            <div class="text-muted small">Pending approval</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-3">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <small>Drafts</small>
                                            <h3 class="mb-0" data-quest-line-count="draft" data-status-key="draft">—</h3>
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
                                        <table class="table table-striped table-hover mb-0 align-middle" id="questLinesTable">
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
                                            <tbody id="questLinesTableBody"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div id="questLinesSchedulingAlert" class="alert alert-warning d-none" role="alert"></div>
                        </div>
                    </div>
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
                    <div id="topSection">
                        <div id="topSpinner" class="section-spinner text-center py-3">
                            <div class="spinner-border text-secondary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <div id="topError" class="alert alert-danger d-none" role="alert"></div>
                        <div id="topContent" class="d-none">
                            <div class="accordion" id="topAccordion">
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingTopQuests">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTopQuests" aria-expanded="true" aria-controls="collapseTopQuests">
                                            Top 10 Quests
                                        </button>
                                    </h2>
                                    <div id="collapseTopQuests" class="accordion-collapse collapse show" aria-labelledby="headingTopQuests">
                                        <div class="accordion-body">
                                            <div id="topQuestsSection" class="top-subsection">
                                                <div class="section-spinner text-center py-3 top-quests-spinner d-none">
                                                    <div class="spinner-border text-secondary" role="status">
                                                        <span class="visually-hidden">Loading...</span>
                                                    </div>
                                                </div>
                                                <div id="topQuestsError" class="alert alert-danger d-none" role="alert"></div>
                                                <p id="topQuestsEmpty" class="text-muted d-none mb-0">No completed quests.</p>
                                                <div class="table-responsive d-none" id="topQuestsTableWrapper">
                                                    <table class="table table-striped" id="topQuestsTable">
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
                                                        <tbody id="topQuestsBody"></tbody>
                                                    </table>
                                                </div>
                                            </div>
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
                                            <div id="topParticipantsSection" class="top-subsection">
                                                <div class="section-spinner text-center py-3 top-participants-spinner d-none">
                                                    <div class="spinner-border text-secondary" role="status">
                                                        <span class="visually-hidden">Loading...</span>
                                                    </div>
                                                </div>
                                                <div id="topParticipantsError" class="alert alert-danger d-none" role="alert"></div>
                                                <p id="topParticipantsEmpty" class="text-muted d-none mb-0">No participants yet.</p>
                                                <div id="topParticipantsControls" class="d-none">
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
                                                </div>
                                                <div class="table-responsive d-none" id="topParticipantsTableWrapper">
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
                                                        <tbody id="topParticipantsBody"></tbody>
                                                    </table>
                                                </div>
                                            </div>
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
                                            <div id="topCoHostsSection" class="top-subsection">
                                                <div class="section-spinner text-center py-3 top-cohosts-spinner d-none">
                                                    <div class="spinner-border text-secondary" role="status">
                                                        <span class="visually-hidden">Loading...</span>
                                                    </div>
                                                </div>
                                                <div id="topCoHostsError" class="alert alert-danger d-none" role="alert"></div>
                                                <p id="topCoHostsEmpty" class="text-muted d-none mb-0">No co-hosts yet.</p>
                                                <div class="table-responsive d-none" id="topCoHostsTableWrapper">
                                                    <table class="table table-striped" id="topCoHostsTable">
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
                                                        <tbody id="topCoHostsBody"></tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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
window.questDashboardConfig = Object.assign({}, window.questDashboardConfig || {}, {
    sessionToken: "<?= $_SESSION['sessionToken']; ?>",
    currentHostId: <?= $account->crand; ?>
});
</script>
<script src="<?= Version::urlBetaPrefix(); ?>/assets/js/questGiverDashboard.js"></script>
<script>
$(document).ready(function () {
    const config = window.questDashboardConfig || {};
    const sessionToken = config.sessionToken || '';
    const currentHostId = config.currentHostId || 0;

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
    document.addEventListener('questDashboard:participantsRendered', updateParticipantTable);
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
});
</script>
</body>
</html>
