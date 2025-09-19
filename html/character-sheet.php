<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require("php-components/base-page-pull-active-account-info.php");

use Kickback\Services\Session;
use Kickback\Backend\Controllers\AccountController;
use Kickback\Backend\Controllers\ActivityController;
use Kickback\Backend\Controllers\LootController;
use Kickback\Backend\Controllers\GameController;
use Kickback\Backend\Views\vRecordId;

$character = null;
$badges = [];
$inventoryStacks = [];
$activities = [];
$gamesById = [];
$totalWins = 0;
$totalLosses = 0;
$totalRankedMatches = 0;
$activityBreakdown = [];
$recentMatchHighlights = [];

if (Session::isLoggedIn()) {
    $activeAccount = Session::getCurrentAccount();

    if ($activeAccount instanceof vRecordId) {
        $accountResp = AccountController::getAccountById($activeAccount);
        if ($accountResp->success && $accountResp->data !== null) {
            $character = $accountResp->data;
        } else {
            $character = $activeAccount;
        }

        $badgesResp = LootController::getBadgesByAccount($activeAccount);
        if ($badgesResp->success && is_array($badgesResp->data)) {
            $badges = $badgesResp->data;
        }

        $inventoryResp = AccountController::getAccountInventory($activeAccount);
        if ($inventoryResp->success && is_array($inventoryResp->data)) {
            $inventoryStacks = $inventoryResp->data;
        }

        if ($character !== null) {
            $activityResp = ActivityController::getActivityByAccount($character);
            if ($activityResp->success && is_array($activityResp->data)) {
                $activities = $activityResp->data;
            }
        }

        $gamesResp = GameController::getGames();
        if ($gamesResp->success && is_array($gamesResp->data)) {
            foreach ($gamesResp->data as $game) {
                $gamesById[$game->crand] = $game;
            }
        }

        if ($character !== null && is_array($character->game_stats ?? null)) {
            foreach ($character->game_stats as $gameStat) {
                $totalWins += (int) $gameStat->total_wins;
                $totalLosses += (int) $gameStat->total_losses;
                $totalRankedMatches += (int) $gameStat->ranked_matches;
            }
        }

        if ($character !== null && is_array($character->match_stats ?? null)) {
            $recentMatchHighlights = array_slice(array_values($character->match_stats), 0, 5);
        }

        foreach ($activities as $activity) {
            $type = $activity->type ?? 'other';
            $activityBreakdown[$type] = ($activityBreakdown[$type] ?? 0) + 1;
        }
    }
}

$winRate = ($totalWins + $totalLosses) > 0 ? ($totalWins / ($totalWins + $totalLosses)) * 100 : 0;
$expProgress = 0;
$prestige = $character?->prestige ?? 0;
$badgeCount = is_array($badges) ? count($badges) : 0;
$inventoryItemCount = 0;
$inventoryUniqueCount = count($inventoryStacks);

foreach ($inventoryStacks as $stack) {
    $inventoryItemCount += (int) $stack->amount;
}

$bannerMedia = $character?->banner;
$playerCardBorder = $character?->playerCardBorder;
$accountTitle = $character?->getAccountTitle() ?? 'Adventurer';
$displayUsername = $character?->username ?? 'Adventurer';
$displayLevel = (int) ($character?->level ?? 0);
$lifetimeExp = (int) ($character?->exp ?? 0);
$nextLevelExp = max(0, (int) (($character?->expGoal ?? 0) - ($character?->expCurrent ?? 0)));

if ($character !== null) {
    $expRange = max(1, ($character->expGoal ?? 0) - ($character->expStarted ?? 0));
    $currentProgress = ($character->expCurrent ?? 0) - ($character->expStarted ?? 0);
    $expProgress = max(0, min(100, ($currentProgress / $expRange) * 100));
}

usort($inventoryStacks, function ($a, $b) {
    return ($b->amount <=> $a->amount);
});
$inventoryPreview = array_slice($inventoryStacks, 0, 6);

arsort($activityBreakdown);
$topActivities = array_slice($activityBreakdown, 0, 4, true);

$activePageName = "Character Sheet";
?>

<!DOCTYPE html>
<html lang="en">

<?php require("php-components/base-page-head.php"); ?>

<body class="bg-body-secondary container p-0">

    <?php require("php-components/base-page-components.php"); ?>

    <main class="container pt-3 bg-body" style="margin-bottom: 56px;">
        <?php require("php-components/base-page-breadcrumbs.php"); ?>

        <?php if (!Session::isLoggedIn()) : ?>
            <section class="alert alert-warning mt-4" role="alert">
                <h2 class="h4">Log in to view your adventurer dossier</h2>
                <p class="mb-0">The Kickback Kingdom scribe can only summon your character sheet once you are signed in.</p>
            </section>
        <?php else : ?>
            <section class="card border-0 shadow-sm overflow-hidden mb-4">
                <div class="position-relative" style="min-height: 220px;">
                    <?php if ($bannerMedia?->isValid()) : ?>
                        <img src="<?= htmlspecialchars($bannerMedia->url); ?>" class="w-100 h-100 object-fit-cover" alt="Character banner">
                    <?php else : ?>
                        <div class="w-100 h-100 bg-dark" style="opacity: 0.65;"></div>
                    <?php endif; ?>
                    <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(135deg, rgba(14, 11, 24, 0.85) 0%, rgba(16, 24, 39, 0.6) 60%, rgba(13, 148, 136, 0.45) 100%);"></div>
                    <div class="position-absolute bottom-0 start-0 p-4 p-lg-5 text-white">
                        <div class="d-flex align-items-center gap-3">
                            <div class="position-relative">
                                <?php
<?php
                                $avatarUrl = $character?->avatar?->url ?? ($character?->profilePictureURL() ?? '');
                                ?>
                                <img src="<?= htmlspecialchars($avatarUrl); ?>" class="rounded-circle border border-3 border-success-subtle" style="width: 96px; height: 96px; object-fit: cover;" alt="Character avatar">
                                <?php if ($playerCardBorder?->isValid()) : ?>
                                    <img src="<?= htmlspecialchars($playerCardBorder->url); ?>" class="position-absolute top-50 start-50 translate-middle" style="width: 120px; height: 120px; object-fit: contain;" alt="Player card border">
                                <?php endif; ?>
                            </div>
                            <div>
                                <p class="text-uppercase small mb-1 letter-spacing">Level <?= $displayLevel; ?> <?= htmlspecialchars($accountTitle); ?></p>
                                <h1 class="display-5 fw-bold mb-2"><?= htmlspecialchars($displayUsername); ?></h1>
                                <div class="d-flex flex-wrap gap-3">
                                    <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle px-3 py-2">Prestige <?= (int) $prestige; ?></span>
                                    <span class="badge bg-primary-subtle text-primary-emphasis border border-primary-subtle px-3 py-2">Badges <?= (int) $badgeCount; ?></span>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-2">Quests <?= (int) ($activityBreakdown['quest'] ?? 0); ?></span>
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-3 py-2">Ranked Matches <?= (int) $totalRankedMatches; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body bg-dark text-white">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <h2 class="h5 text-uppercase text-secondary">Experience</h2>
                            <p class="mb-1"><?= number_format($lifetimeExp); ?> lifetime EXP</p>
                            <div class="progress" style="height: 0.75rem;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?= number_format($expProgress, 2); ?>%;" aria-valuenow="<?= number_format($expProgress, 2); ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small class="text-secondary d-block mt-2">Next level in <?= $nextLevelExp; ?> EXP</small>
                        </div>
                        <div class="col-lg-6">
                            <h2 class="h5 text-uppercase text-secondary">Battle Record</h2>
                            <div class="d-flex flex-wrap gap-4">
                                <div>
                                    <p class="h3 fw-bold mb-0 text-success"><?= (int) $totalWins; ?></p>
                                    <span class="text-secondary">Wins</span>
                                </div>
                                <div>
                                    <p class="h3 fw-bold mb-0 text-danger"><?= (int) $totalLosses; ?></p>
                                    <span class="text-secondary">Losses</span>
                                </div>
                                <div>
                                    <p class="h3 fw-bold mb-0 text-info"><?= number_format($winRate, 1); ?>%</p>
                                    <span class="text-secondary">Win rate</span>
                                </div>
                            </div>
                            <p class="mt-3 mb-0">Your legend grows across the arenas of Kickback Kingdom. Harness these numbers to script your next Final Fantasy-style showdown.</p>
                        </div>
                    </div>
                </div>
            </section>

            <div class="row g-4 mb-4">
                <div class="col-xl-7">
                    <section class="card shadow-sm h-100">
                        <div class="card-header bg-transparent border-0">
                            <h2 class="h4 mb-0">Battle-ready skill grid</h2>
                            <p class="text-secondary mb-0">An overview of how your adventures, tournaments, and quests have shaped your combat identity.</p>
                        </div>
                        <div class="card-body">
                            <?php if (empty($topActivities)) : ?>
                                <p class="text-secondary mb-0">Complete quests, compete in tournaments, and explore the realm to unlock detailed analytics here.</p>
                            <?php else : ?>
                                <div class="row g-3">
                                    <?php foreach ($topActivities as $type => $count) : ?>
                                        <div class="col-sm-6">
                                            <div class="border rounded-3 p-3 h-100">
                                                <p class="text-uppercase text-secondary small mb-1"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $type))); ?></p>
                                                <p class="h3 fw-bold mb-0"><?= (int) $count; ?></p>
                                                <p class="mb-0 text-secondary">Moments logged in your chronicle.</p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>
                <div class="col-xl-5">
                    <section class="card shadow-sm h-100">
                        <div class="card-header bg-transparent border-0">
                            <h2 class="h4 mb-0">Lore alignment</h2>
                            <p class="text-secondary mb-0">Where your energy flows across Kickback Kingdom.</p>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item bg-transparent px-0 d-flex justify-content-between">
                                    <span class="text-secondary">Adventures completed</span>
                                    <span class="fw-semibold"><?= (int) ($activityBreakdown['adventure'] ?? 0); ?></span>
                                </li>
                                <li class="list-group-item bg-transparent px-0 d-flex justify-content-between">
                                    <span class="text-secondary">Quests fulfilled</span>
                                    <span class="fw-semibold text-success"><?= (int) ($activityBreakdown['quest'] ?? 0); ?></span>
                                </li>
                                <li class="list-group-item bg-transparent px-0 d-flex justify-content-between">
                                    <span class="text-secondary">Tournaments fought</span>
                                    <span class="fw-semibold text-warning"><?= (int) ($activityBreakdown['tournament'] ?? 0); ?></span>
                                </li>
                                <li class="list-group-item bg-transparent px-0 d-flex justify-content-between">
                                    <span class="text-secondary">Guild contracts</span>
                                    <span class="fw-semibold text-info"><?= (int) ($activityBreakdown['guild'] ?? 0); ?></span>
                                </li>
                                <li class="list-group-item bg-transparent px-0 d-flex justify-content-between">
                                    <span class="text-secondary">Market deals</span>
                                    <span class="fw-semibold"><?= (int) ($activityBreakdown['market'] ?? 0); ?></span>
                                </li>
                            </ul>
                        </div>
                    </section>
                </div>
            </div>

            <section class="card shadow-sm mb-4">
                <div class="card-header bg-transparent border-0 d-flex flex-wrap justify-content-between align-items-end gap-2">
                    <div>
                        <h2 class="h4 mb-0">Game-by-game tactics</h2>
                        <p class="text-secondary mb-0">Compare how your account performs across each battlefield.</p>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-dark table-striped table-hover mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th scope="col">Game</th>
                                    <th scope="col">Rank</th>
                                    <th scope="col">Elo</th>
                                    <th scope="col">Ranked Matches</th>
                                    <th scope="col">Wins</th>
                                    <th scope="col">Losses</th>
                                    <th scope="col">Win rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($character !== null && !empty($character->game_stats)) : ?>
                                    <?php foreach ($character->game_stats as $gameStat) :
                                        $gameName = $gamesById[$gameStat->gameId->crand]->name ?? ('Game #' . $gameStat->gameId->crand);
                                        $winRatePerGame = ($gameStat->total_wins + $gameStat->total_losses) > 0
                                            ? ($gameStat->total_wins / ($gameStat->total_wins + $gameStat->total_losses)) * 100
                                            : 0;
                                    ?>
                                        <tr>
                                            <td><span class="fw-semibold"><?= htmlspecialchars($gameName); ?></span></td>
                                            <td><?= htmlspecialchars($gameStat->getRankElement()); ?></td>
                                            <td><?= $gameStat->elo !== null ? number_format($gameStat->elo, 0) : '—'; ?></td>
                                            <td><?= (int) $gameStat->ranked_matches; ?></td>
                                            <td class="text-success"><?= (int) $gameStat->total_wins; ?></td>
                                            <td class="text-danger"><?= (int) $gameStat->total_losses; ?></td>
                                            <td><?= number_format($winRatePerGame, 1); ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-secondary py-4">No ranked records yet. Queue into a Kickback arena to begin charting your battle rhythm.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <div class="row g-4 mb-4">
                <div class="col-xl-6">
                    <section class="card shadow-sm h-100">
                        <div class="card-header bg-transparent border-0">
                            <h2 class="h4 mb-0">Recent ranked highlights</h2>
                            <p class="text-secondary mb-0">Snapshots of your latest Final Fantasy-inspired clashes.</p>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recentMatchHighlights)) : ?>
                                <p class="text-secondary mb-0">Complete ranked matches to populate your highlight reel.</p>
                            <?php else : ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($recentMatchHighlights as $match) : ?>
                                        <div class="list-group-item bg-transparent px-0">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <p class="mb-1 fw-semibold"><?= htmlspecialchars($match->teamName ?? 'Squad'); ?></p>
                                                    <p class="mb-0 text-secondary"><?= htmlspecialchars($match->character ?? 'Hero'); ?><?= $match->randomCharacter ? ' <span class="badge bg-warning-subtle text-warning-emphasis ms-2">Random pick</span>' : ''; ?></p>
                                                </div>
                                                <div class="text-end">
                                                    <span class="badge <?= ($match->eloChange ?? 0) >= 0 ? 'bg-success-subtle text-success-emphasis' : 'bg-danger-subtle text-danger-emphasis'; ?>">
                                                        <?= ($match->eloChange ?? 0) >= 0 ? '+' : ''; ?><?= (int) ($match->eloChange ?? 0); ?> Elo
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>
                <div class="col-xl-6">
                    <section class="card shadow-sm h-100">
                        <div class="card-header bg-transparent border-0">
                            <h2 class="h4 mb-0">Recent adventures</h2>
                            <p class="text-secondary mb-0">The latest entries in your Kickback chronicle.</p>
                        </div>
                        <div class="card-body">
                            <?php if (empty($activities)) : ?>
                                <p class="text-secondary mb-0">Set your account on an expedition—explore Ostrinus Cave, raid the Obitus Space Station, or duel in the town square.</p>
                            <?php else : ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach (array_slice($activities, 0, 6) as $activity) : ?>
                                        <div class="list-group-item bg-transparent px-0">
                                            <div class="d-flex align-items-center gap-3">
                                                <?php if ($activity->icon !== null) : ?>
                                                    <img src="<?= htmlspecialchars($activity->icon->url); ?>" alt="Activity icon" class="rounded" style="width: 48px; height: 48px; object-fit: cover;">
                                                <?php endif; ?>
                                                <div>
                                                    <p class="mb-1 fw-semibold"><?= htmlspecialchars($activity->verb . ' ' . $activity->name); ?></p>
                                                    <p class="mb-0 text-secondary small text-uppercase"><?= htmlspecialchars($activity->type); ?> • <?= htmlspecialchars($activity->dateTime?->timeElapsedString() ?? 'just now'); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>
            </div>

            <section class="card shadow-sm mb-4">
                <div class="card-header bg-transparent border-0 d-flex flex-wrap justify-content-between align-items-end gap-2">
                    <div>
                        <h2 class="h4 mb-0">Badge constellation</h2>
                        <p class="text-secondary mb-0">Trophies, merits, and curios collected throughout your travels.</p>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($badges)) : ?>
                        <p class="text-secondary mb-0">Earn accolades from quests, tournaments, and seasonal events to see them gathered here.</p>
                    <?php else : ?>
                        <div class="row g-3">
                            <?php foreach ($badges as $badge) : ?>
                                <div class="col-6 col-sm-4 col-lg-3 col-xl-2">
                                    <div class="text-center border rounded-3 p-3 h-100">
                                        <img src="<?= htmlspecialchars($badge->item->iconSmall->url ?? $badge->item->iconSmall->getFullPath()); ?>" alt="<?= htmlspecialchars($badge->item->name); ?>" class="img-fluid mb-2" style="max-height: 72px; object-fit: contain;">
                                        <p class="fw-semibold small mb-1"><?= htmlspecialchars($badge->item->name); ?></p>
                                        <p class="text-secondary small mb-0"><?= htmlspecialchars($badge->item->description); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <div class="row g-4 mb-4">
                <div class="col-xl-6">
                    <section class="card shadow-sm h-100">
                        <div class="card-header bg-transparent border-0">
                            <h2 class="h4 mb-0">Equipped relics</h2>
                            <p class="text-secondary mb-0">Your current loadout when marching into Kickback Kingdom skirmishes.</p>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <?php
                                $equipmentSlots = [
                                    'Avatar' => $character?->avatar,
                                    'Player Card Border' => $character?->playerCardBorder,
                                    'Banner' => $character?->banner,
                                    'Background' => $character?->background,
                                    'Charm' => $character?->charm,
                                    'Companion' => $character?->companion,
                                ];
                                ?>
                                <?php foreach ($equipmentSlots as $slotName => $media) : ?>
                                    <div class="col-6">
                                        <div class="border rounded-3 p-3 h-100 text-center">
                                            <p class="text-uppercase text-secondary small mb-2"><?= htmlspecialchars($slotName); ?></p>
                                            <?php if ($media !== null && $media->isValid()) : ?>
                                                <img src="<?= htmlspecialchars($media->url); ?>" alt="<?= htmlspecialchars($slotName); ?>" class="img-fluid rounded" style="max-height: 120px; object-fit: contain;">
                                            <?php else : ?>
                                                <div class="d-flex flex-column justify-content-center align-items-center text-secondary" style="height: 120px;">
                                                    <span class="fw-semibold">Empty slot</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </section>
                </div>
                <div class="col-xl-6">
                    <section class="card shadow-sm h-100">
                        <div class="card-header bg-transparent border-0">
                            <h2 class="h4 mb-0">Inventory snapshot</h2>
                            <p class="text-secondary mb-0">A quick glance at your most abundant resources.</p>
                        </div>
                        <div class="card-body">
                            <p class="text-secondary"><?= (int) $inventoryUniqueCount; ?> unique items • <?= (int) $inventoryItemCount; ?> total artifacts</p>
                            <?php if (empty($inventoryPreview)) : ?>
                                <p class="text-secondary mb-0">Complete expeditions to fill your pack with treasure.</p>
                            <?php else : ?>
                                <div class="row g-3">
                                    <?php foreach ($inventoryPreview as $stack) : ?>
                                        <div class="col-6">
                                            <div class="border rounded-3 p-3 h-100">
                                                <div class="d-flex align-items-center gap-3">
                                                    <img src="<?= htmlspecialchars($stack->item->iconSmall->url ?? $stack->item->iconSmall->getFullPath()); ?>" alt="<?= htmlspecialchars($stack->GetName()); ?>" class="rounded" style="width: 48px; height: 48px; object-fit: contain;">
                                                    <div>
                                                        <p class="fw-semibold mb-1"><?= htmlspecialchars($stack->GetName()); ?></p>
                                                        <p class="text-secondary mb-0">x<?= (int) $stack->amount; ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>
            </div>

            <section class="card shadow-sm mb-5">
                <div class="card-header bg-transparent border-0">
                    <h2 class="h4 mb-0">Strategic next steps</h2>
                    <p class="text-secondary mb-0">Suggestions to grow your idle adventure legacy.</p>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100">
                                <h3 class="h5">Explore Ostrinus Cave</h3>
                                <p class="text-secondary mb-0">Send your account on an automated delve to harvest relics that improve magic defense and resilience.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100">
                                <h3 class="h5">Raid Obitus Station</h3>
                                <p class="text-secondary mb-0">Queue a cooperative raid to pursue legendary tech augments and uncover cross-game stat boosts.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100">
                                <h3 class="h5">Champion a tournament</h3>
                                <p class="text-secondary mb-0">Join the next Kickback bracket to earn exclusive badges, prestige, and Final Fantasy-style duel logs.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <?php require("php-components/base-page-footer.php"); ?>
    </main>

    <?php require("php-components/base-page-javascript.php"); ?>
</body>

</html>
