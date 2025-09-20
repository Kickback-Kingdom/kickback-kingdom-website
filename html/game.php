<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

use Kickback\Backend\Controllers\GameController;
use Kickback\Backend\Controllers\AccountController;
use Kickback\Backend\Controllers\ChallengeHistoryController;
use Kickback\Backend\Controllers\FeedController;
use Kickback\Backend\Controllers\FeedCardController;
use Kickback\Common\Version;

$gameLocator =  urldecode($_GET['locator']);
$gameResp = GameController::getGameByLocator($gameLocator);

if ($gameResp->success) {
    $thisGame = $gameResp->data;
} else {
    $thisGame = null;
}


$accountsResp = $thisGame ? AccountController::getAccountsByGoldCard($thisGame) : null;

if ($accountsResp && $accountsResp->success) {
    $goldCardHolders = $accountsResp->data;
} else {
    $goldCardHolders = [];
}

$accountRankingsResp = $thisGame ? AccountController::getAccountsByGame($thisGame) : null;

if ($accountRankingsResp && $accountRankingsResp->success) {
    $accountRankings = $accountRankingsResp->data;
} else {
    $accountRankings = [];
}

$currentWinStreakResp = GameController::getCurrentWinStreak($thisGame);
$currentWinStreak = $currentWinStreakResp->data;


$AllTimeWinStreakResp = GameController::getAllTimeWinStreak($thisGame);
$AllTimeWinStreak = $AllTimeWinStreakResp->data;

$randomCharacterWinResp = GameController::getBestRandomPlayer($thisGame);
$randomCharacterWin = $randomCharacterWinResp->data;

$rankedMatches = [];


$gameQuestsResp = FeedController::getQuestsByGameId($thisGame, 1, 100);
$gameQuests = $gameQuestsResp->data->items;

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
                // Show game name in breadcrumbs if game is available
                if ($thisGame !== null) {
                    $activePageName = $thisGame->name;
                    require("php-components/base-page-breadcrumbs.php");
                }

                // Show "Champion(s) of the kingdom" text if there are gold card holders
                if (count($goldCardHolders) > 0) {
                    $championText = (count($goldCardHolders) === 1) ? "Champion of the kingdom" : "Champions of the kingdom";
                    echo "<h2 class='mt-4 mb-3 text-center'>$championText</h2>";
                }
                ?>
                
                <div class="d-flex flex-wrap justify-content-evenly align-items-center mt-3">
                    <?php
                    // Render player cards for each gold card holder
                    foreach ($goldCardHolders as $goldCardHolder) :
                        $_vPlayerCardAccount = $goldCardHolder;
                        require("php-components/vPlayerCardRenderer.php"); 
                    endforeach;
                    ?>
                </div>
                <div class="row mt-4 g-3 mb-4">
                    <!-- Current Highest Win Streak -->
                    <div class="col-md-4">
                        <div class="card border-primary shadow-sm h-100">
                            <div class="card-header text-center bg-primary text-white">
                                <i class="fa-solid fa-fire me-2"></i> Current Highest Win Streak
                            </div>
                            <div class="card-body d-flex flex-column justify-content-center">
                                <?php if (!empty($currentWinStreak)): ?>
                                    <?php foreach ($currentWinStreak as $streak): ?>
                                        <div class="d-flex align-items-center mb-3 p-2 bg-light rounded">
                                            <img src="<?= $streak['account']->profilePictureURL() ?>"
                                                alt="<?= htmlspecialchars($streak['account']->username) ?>" 
                                                class="rounded-circle shadow"
                                                style="height: 50px; width: 50px; object-fit: cover; border: 2px solid #007bff;">
                                            <div class="ms-3 text-start w-100">
                                                <h5 class="mb-1 fw-bold text-primary"><?= $streak['account']->getAccountElement() ?></h5>
                                                <p class="mb-0 text-muted small">
                                                    🔥 On Fire <span class="fw-bold text-primary"><?= $streak['current_streak'] ?> Wins</span>
                                                </p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted mb-0 text-center">No active win streaks.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- All-Time Highest Win Streak -->
                    <div class="col-md-4">
                        <div class="card border-success shadow-sm h-100">
                            <div class="card-header text-center bg-success text-white">
                                <i class="fa-solid fa-crown me-2"></i> All-Time Highest Win Streak
                            </div>
                            <div class="card-body d-flex flex-column justify-content-center">
                                <?php if (!empty($AllTimeWinStreak)): ?>
                                    <?php foreach ($AllTimeWinStreak as $streak): ?>
                                        <div class="d-flex align-items-center mb-3 p-2 bg-light rounded">
                                            <img src="<?= $streak['account']->profilePictureURL() ?>"
                                                alt="<?= htmlspecialchars($streak['account']->username) ?>" 
                                                class="rounded-circle shadow"
                                                style="height: 50px; width: 50px; object-fit: cover; border: 2px solid #28a745;">
                                            <div class="ms-3 text-start w-100">
                                                <h5 class="mb-1 fw-bold text-success"><?= $streak['account']->getAccountElement() ?></h5>
                                                <p class="mb-0 text-muted small">
                                                    👑 Legend <span class="fw-bold text-success"><?= $streak['max_streak'] ?> Wins</span>
                                                </p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted mb-0 text-center">No all-time win streaks.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Most Wins With Random -->
                    <div class="col-md-4">
                        <div class="card border-info shadow-sm h-100">
                            <div class="card-header text-center bg-info text-white">
                                <i class="fa-solid fa-dice me-2"></i> Most Wins With Random
                            </div>
                            <div class="card-body d-flex flex-column justify-content-center">
                                <?php if (!empty($randomCharacterWin)): ?>
                                    <?php foreach ($randomCharacterWin as $streak): ?>
                                        <div class="d-flex align-items-center mb-3 p-2 bg-light rounded">
                                            <img src="<?= $streak['account']->profilePictureURL() ?>"
                                                alt="<?= htmlspecialchars($streak['account']->username) ?>" 
                                                class="rounded-circle shadow"
                                                style="height: 50px; width: 50px; object-fit: cover; border: 2px solid #17a2b8;">
                                            <div class="ms-3 text-start w-100">
                                                <h5 class="mb-1 fw-bold text-info"><?= $streak['account']->getAccountElement() ?></h5>
                                                <p class="mb-0 text-muted small">
                                                    🎲 Unpredictable <span class="fw-bold text-info"><?= $streak['total_random_wins'] ?> Set Wins</span>
                                                </p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted mb-0 text-center">No wins with random picks.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>







                
                <div class="row">
                    <div class="col-12">
                        <nav>
                            <div class="nav nav-tabs" id="nav-tab" role="tablist">
                                <button class="nav-link active" id="nav-rankings-tab" data-bs-toggle="tab" data-bs-target="#nav-rankings" type="button" role="tab" aria-controls="nav-rankings" aria-selected="true"><i class="fa-solid fa-ranking-star"></i></button>
                                <button class="nav-link" id="nav-history-tab" data-bs-toggle="tab" data-bs-target="#nav-history" type="button" role="tab" aria-controls="nav-history" aria-selected="false"><i class="fa-solid fa-clock-rotate-left"></i></button>
                                <button class="nav-link" id="nav-quests-tab" data-bs-toggle="tab" data-bs-target="#nav-quests" type="button" role="tab" aria-controls="nav-quests" aria-selected="false"><i class="fa-regular fa-compass"></i></button>
                            </div>
                        </nav>
                        <div class="tab-content" id="nav-tabContent">
                            <div class="tab-pane fade show active" id="nav-rankings" role="tabpanel" aria-labelledby="nav-rankings-tab" tabindex="0">
                                <div class="display-6 tab-pane-title mt-4">Rankings</div>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table id="datatable-ranks" class="dataTable no-footer nowrap table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th scope="col">Rank</th>
                                                        <th scope="col">Guildsmen</th>
                                                        <th scope="col">ELO</th>
                                                        <th scope="col">Wins</th>
                                                        <th scope="col">Loses</th>
                                                        <th scope="col">Matches</th>
                                                        <th scope="col">W/L Ratio</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    
                                                    <?php 
                                                    foreach ($accountRankings as $account) : 
                                                        $gameStats = $account->game_stats[$thisGame->crand];
                                                        ?>
                                                            <tr >
                                                            <td><?= $gameStats->getRankElement();?></td>
                                                            <td><?= $account->getAccountElement();?></td>
                                                            <td><?= $gameStats->elo;?></td>
                                                            <td><?= $gameStats->total_wins;?></td>
                                                            <td><?= $gameStats->total_losses;?></td>
                                                            <td><?= $gameStats->ranked_matches;?></td>
                                                            <td><?= number_format($gameStats->win_rate * 100, 2); ?>%</td>
                                                            </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="nav-history" role="tabpanel" aria-labelledby="nav-history-tab" tabindex="0">
                                <div class="table-responsive">
                                    <table id="datatable-history" class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Match</th>
                                                <th>Date</th>
                                                <th>Players</th>
                                                <th>Details</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($rankedMatches as $match): ?>
                                                <tr data-match-id="<?= htmlspecialchars($match->crand) ?>">
                                                <td><?= htmlspecialchars($match->crand) ?></td>
                                                <td><?= $match->dateTime->getDateTimeElement(); ?></td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <?php foreach ($match->teams as $teamName => $teamPlayers): ?>
                                                                <?php foreach ($teamPlayers as $index => $player): ?>
                                                                    <span tabindex="0" 
                                                                        style="margin-right: <?= ($index === count($teamPlayers) - 1) ? 15 : 2 ?>px;" 
                                                                        data-bs-toggle="popover" 
                                                                        data-bs-custom-class="custom-popover" 
                                                                        data-bs-trigger="focus" 
                                                                        data-bs-placement="top" 
                                                                        data-bs-title="<?= htmlspecialchars($player->username) ?>" 
                                                                        data-bs-content="<?= htmlspecialchars($teamName) ?>">
                                                                        <img src="<?= htmlspecialchars($player->profilePictureURL()) ?>"
                                                                            class="loot-badge" 
                                                                            alt="<?= htmlspecialchars($player->username) ?>">
                                                                    </span>
                                                                <?php endforeach; ?>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-primary btn-sm toggle-details" 
                                                                data-match-id="<?= htmlspecialchars($match->crand) ?>">
                                                            View Details
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>



                                    </table>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="nav-quests" role="tabpanel" aria-labelledby="nav-quests-tab" tabindex="0">
                            <?php 

                            for ($i=0; $i < count($gameQuests); $i++) 
                            { 
                                $_vFeedCard = FeedCardController::vFeedRecord_to_vFeedCard($gameQuests[$i]);
                                require("php-components/vFeedCardRenderer.php");
                            }
                            ?>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
            
            <?php require("php-components/base-page-discord.php"); ?>
        </div>
        <?php require("php-components/base-page-footer.php"); ?>
    </main>

    
    <?php 
        require("php-components/base-page-javascript.php"); 
        require("php-components/base-page-javascript-match-history.php"); 
    ?>

    <script>
        var rankedMatches = <?= json_encode($rankedMatches); ?>        


        $(document).ready( function () {
            $('#datatable-ranks').DataTable({
                "order": [[2, 'desc']],
                "pageLength": 100,
                //"responsive": true,
                //"scrollX": true,
            });

            var table = $('#datatable-history').DataTable({
                processing: true,       // Show loading indicator
                serverSide: true,       // Enable server-side processing
        autoWidth: false, // Disable auto width
        responsive: true, // Ensure responsiveness
        searching: false, // Disable the search bar
                ajax: {
                    url: '<?= Version::formatUrl("/api/v1/match/history.php?json"); ?>', // Your API endpoint
                    type: 'POST',       // HTTP method
                    data: function (d) {
                        // Add custom parameters if needed
                        d.gameId = <?= $thisGame->crand; ?>;
                        d.sessionToken = "<?= $_SESSION["sessionToken"] ?? ""; ?>";

                        // Calculate page and pageSize
                        d.page = Math.floor(d.start / d.length) + 1; // Convert start index to page number
                        d.itemsPerPage = d.length; // Number of rows per page
                    },
                    dataSrc: function (json) {
                        // Map API response to DataTables format
                        if (json.success) {
                            json.recordsTotal = json.data.totalItems; // Total items available
            json.recordsFiltered = json.data.totalItems; // Total items after filtering (same in this case)

                            var matches = json.data.items.map(item => ({
                                crand: item.crand,
                                dateTime: item.dateTime,
                                players: item.teams,
                                teams: item.teams,
                                details: `<button class="btn btn-primary btn-sm toggle-details" data-match-id="${item.crand}">View Details</button>`
                            }));
                            return matches;
                        } else {
                            return [];
                        }
                    }
                },
                columns: [
                            { 
                                data: 'crand', 
                                orderable: false,
                                searchable: false,
                                render: function (data) {
                                    return `<td>${data}</td>`;
                                }
                            },
                            { 
                                data: 'dateTime', 
                                orderable: false,
                                searchable: false,
                                render: function (data) {
                                    return `<td><span class="date" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="${data.formattedDetailed} UTC" data-datetime-utc="${data.valueString}" data-db-value="${data.dbValue}">${data.formattedBasic}</span></td>`;
                                }
                            },
                            { 
                                data: 'players',
                                orderable: false,
                                searchable: false,
                                render: function (data) {
                                    let playersHTML = '<div class="d-flex align-items-center">';
                                    for (const [teamName, players] of Object.entries(data)) {
                                        players.forEach((player, index) => {
                                            playersHTML += `
                                                <span tabindex="0" 
                                                    style="margin-right: ${index === players.length - 1 ? 15 : 2}px;" 
                                                    data-bs-toggle="popover" 
                                                    data-bs-custom-class="custom-popover" 
                                                    data-bs-trigger="focus" 
                                                    data-bs-placement="top" 
                                                    data-bs-title="${player.username}" 
                                                    data-bs-content="${teamName}">
                                                    <img src="${player.avatar.url}" 
                                                        class="loot-badge" 
                                                        alt="${player.username}">
                                                </span>
                                            `;
                                        });
                                    }
                                    playersHTML += '</div>';
                                    return playersHTML;
                                }
                            },
                            { 
                                data: 'details', 
                                orderable: false,
                                searchable: false,
                                render: function (data) {
                                    return `<td>${data}</td>`;
                                }
                            }
                        ],
                        drawCallback: function () {
            // Initialize tooltips and popovers after each table redraw
            $('[data-bs-toggle="tooltip"]').tooltip();
            $('[data-bs-toggle="popover"]').popover();
        },
        pageLength: 10
                
            });

            $('#datatable-history tbody').on('click', '.toggle-details', function () {
                var tr = $(this).closest('tr');
                var row = table.row(tr);

                if (row.child.isShown()) {
                    // Close the row details
                    row.child.hide();
                    tr.removeClass('shown');
                } else {
                    // Get the data for the row
                    var rowData = row.data();

                    // Construct details HTML from the row data
                    var detailsHtml = '<table class="table"><thead>' +
                        '<tr><th>Player</th><th>Team</th><th>Result</th><th>Character</th><th>Random</th><th>ELO Change</th></tr>' +
                        '</thead><tbody>';

                    for (const [teamName, players] of Object.entries(rowData.teams)) {
                        players.forEach(player => {
                            const accountId = player.accountId ?? player.account_id ?? player.crand ?? '';
                            const accountIdAttr = String(accountId ?? '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                            const usernameAttr = (player.username ?? '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                            const eloChange = player.match_stats[rowData.crand].eloChange;
                            const result = eloChange > 0 ? 'Win' : 'Loss';
                            const rowClass = eloChange > 0 ? 'table-success' : 'table-danger';

                            detailsHtml += `<tr class="${rowClass}">` +
                                `<td>
                                    <div class="d-flex align-items-center">
                                        <img src="${player.avatar.url}" alt="${player.username}" class="me-2" style="width: 40px; height: 40px;">
                                        <a href="/u/${player.username}" class="username" data-account-id="${accountIdAttr}" data-username="${usernameAttr}">${player.username}</a>
                                    </div>
                                </td>` +
                                `<td>${teamName}</td>` +
                                `<td>${result}</td>` +
                                `<td>${player.match_stats[rowData.crand].character}</td>` +
                                `<td>${player.match_stats[rowData.crand].randomCharacter ? 'Yes' : 'No'}</td>` +
                                `<td>${eloChange}</td>` +
                                '</tr>';
                        });
                    }

                    detailsHtml += '</tbody></table>';

                    // Show the details
                    row.child(detailsHtml).show();
                    tr.addClass('shown');
                }
            });

        });

    </script>
</body>

</html>
