<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

use Kickback\Common\Version;
use Kickback\Services\Session;
use Kickback\Backend\Controllers\ServerController;

$response = ServerController::getAllServers();
$servers = $response->success ? $response->data : [];

function getRegionFlag($region) {
    return match($region) {
        'US East', 'US West' => '🇺🇸',
        'Brazil' => '🇧🇷',
        'Europe', 'EU Central', 'EU West' => '🇪🇺',
        'Asia' => '🌏',
        'Australia' => '🇦🇺',
        default => '🏳️'
    };
}

function getRegionFlagCode($region) {
    return match(strtolower($region)) {
        'us east', 'us west', 'usa', 'united states' => 'us',
        'brazil' => 'br',
        'canada' => 'ca',
        'europe', 'eu central', 'eu west' => 'eu',
        'germany' => 'de',
        'france' => 'fr',
        'united kingdom', 'uk', 'england' => 'gb',
        'japan' => 'jp',
        'china' => 'cn',
        'australia' => 'au',
        'russia' => 'ru',
        'south korea' => 'kr',
        default => 'un' // use a placeholder/fallback
    };
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
<style>
/* General Card Styling */
.server-card {
    position: relative;
    display: flex;
    flex: 1;
    flex-direction: column;
    color: white;
    border-radius: 1rem;
    overflow: hidden;
    min-height: 260px;
    background-color: #111;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
    transition: transform 0.2s ease-in-out;
}

.server-card:hover {
    transform: scale(1.02);
}

/* Blurred Background Overlay */
.server-card::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image: var(--bg-image);
    background-size: cover;
    background-position: center;
    filter: brightness(1.1) blur(2px);
    z-index: 1;
}

.server-card::after {
    content: "";
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.4);
    z-index: 2;
}

/* Card Content */
.server-card-content {
    position: relative;
    z-index: 3;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 1.5rem;
    text-align: center;
}

@media (max-width: 767px) {
    .server-card-content {
        padding: 1rem;
    }
}

/* Title & Meta */
.server-card-content h5 {
    font-size: 1.25rem;
    font-weight: bold;
    margin-bottom: 0.25rem;
}

.meta,
.server-card-content .meta {
    font-size: 0.875rem;
    color: #e9ecef;
}

.server-card .meta {
    color: #f8f9fa;
    font-weight: 500;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);
}

.server-card .meta span {
    color: #f1f1f1;
}

/* Icons */
.server-card-icon {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    object-fit: cover;
    margin-bottom: 1rem;
}

.server-card-game-icon {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 8px;
    box-shadow: 0 0 6px rgba(0, 0, 0, 0.4);
}

/* Game Header */
.server-card-game-header {
    font-size: 1.1rem;
    font-weight: 600;
    color: #f8f9fa;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
}

.server-card-content .game-header span {
    text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.7);
}

/* Footer */
.server-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
}

/* Wrapper */
.server-card-wrapper {
    display: flex;
}

/* Hidden Info */
.server-info-hidden {
    font-style: italic;
    color: #bbb;
}

.hidden {
    display: none !important;
}

/* Tag Filters */
#tagFilters .btn {
    border-radius: 20px;
    padding: 0.25rem 0.75rem;
    font-size: 0.85rem;
    transition: all 0.2s ease-in-out;
}

#tagFilters .btn.active {
    background-color: var(--bs-success);
    color: #fff;
    border-color: var(--bs-success);
    font-weight: 500;
}

/* Inline Badges */
.server-card .badge {
    font-size: 0.75rem;
    padding: 0.4em 0.6em;
    border-radius: 999px;
    background-color: #6c757d;
}

/* Official Badge */
.badge-official {
    background: linear-gradient(135deg, #f9d923, #ffbd00);
    color: #3a2f0b;
    font-weight: 700;
    border: 1px solid #f5c400;
    padding: 0.35rem 0.75rem;
    font-size: 0.75rem;
    border-radius: 30px;
    box-shadow: 0 0 6px rgba(255, 217, 0, 0.5);
    text-transform: uppercase;
    font-family: 'Cinzel', serif;
    letter-spacing: 0.05em;
}

.badge-official::before {
    content: "\f521";
    font-family: "Font Awesome 6 Free";
    font-weight: 900;
    margin-right: 0.35em;
}

/* Official Banner */
.server-official-banner {
    z-index: 4;
    font-weight: 600;
    font-size: 0.8rem;
    text-align: center;
    padding: 0.4rem 1rem;
}

/* Region Label */
.server-region-label {
    color: #f0f0f0;
    font-size: 0.875rem;
    font-weight: 500;
    letter-spacing: 0.25px;
    display: inline-flex;
    align-items: center;
    gap: 0.4em;
    opacity: 0.85;
}

.server-region-label img {
    vertical-align: middle;
    border-radius: 2px;
}

/* IP Reveal Section */
#ipRevealSection {
    font-size: 0.9rem;
    background-color: #f8f9fa;
}
/* Full background modal card */
.server-card-modal {
    position: relative;
    background-color: #111;
    height: 100%;
    display: flex;
    flex-direction: column;
    border-radius: 1rem;
    overflow: hidden;
}

/* Background image */
.server-card-bg {
    position: absolute;
    inset: 0;
    background-image: var(--modal-bg-image, url('/assets/media/banners/default.jpg'));
    background-size: cover;
    background-position: center center; /* Ensures full centering */
    z-index: 1;
}


/* Dark overlay */
.server-card-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to bottom, rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.75));
    z-index: 2;
}


/* Modal body sits on top of bg/overlay */
.server-card-modal-body {
    position: relative;
    z-index: 3;
    padding: 2rem;
}

/* Join button style */
.btn-join {
    background: linear-gradient(135deg, #5cb85c, #4cae4c);
    color: #fff;
    font-weight: 600;
    transition: all 0.2s ease-in-out;
    box-shadow: 0 0 8px rgba(76, 174, 76, 0.4);
    border: none;
}

.btn-join:hover {
    background: linear-gradient(135deg, #4cae4c, #3e8e41);
    box-shadow: 0 0 12px rgba(76, 174, 76, 0.6);
}

/* Fade-in animation */
.fade-in {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.98); }
    to { opacity: 1; transform: scale(1); }
}
.server-card-modal-body h3,
.server-card-modal-body h5,
.server-card-modal-body div,
.server-card-modal-body button,
.server-card-modal-body a {
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.9);
}
.server-filter-card {
    background: linear-gradient(to bottom right, #1e1e1e, #2c2c2c);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 1rem;
    box-shadow: 0 0 16px rgba(0, 0, 0, 0.3);
}

.server-filter-card .form-label {
    font-weight: 600;
    font-size: 0.9rem;
    letter-spacing: 0.5px;
    margin-bottom: 0.35rem;
    color: #dee2e6;
}

.server-filter-card select.form-select {
    background-color: #181818;
    color: #fff;
    border: 1px solid #444;
    font-size: 0.85rem;
}

.server-filter-card select.form-select:focus {
    border-color: #ffc107;
    box-shadow: 0 0 0 0.15rem rgba(255, 193, 7, 0.3);
}

/* Tag button styling (will be inserted dynamically) */
#tagFilters .btn {
    border-radius: 20px;
    padding: 0.25rem 0.75rem;
    font-size: 0.75rem;
    background-color: #2b2b2b;
    color: #ccc;
    border: 1px solid #444;
    transition: all 0.2s ease-in-out;
}

#tagFilters .btn:hover {
    background-color: #444;
    color: #fff;
}

#tagFilters .btn.active {
    background-color: #198754;
    color: #fff;
    font-weight: 500;
    border-color: #198754;
}
/* Filter Panel Card */
.server-filter-card {
    background: linear-gradient(145deg, #1c1c1c, #292929);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 1rem;
    box-shadow:
        inset 0 0 12px rgba(255, 255, 255, 0.02),
        0 0 18px rgba(0, 0, 0, 0.4);
    padding: 1.25rem 1.5rem;
}

/* Form Labels */
.server-filter-card .form-label {
    font-weight: 600;
    font-size: 0.9rem;
    letter-spacing: 0.5px;
    margin-bottom: 0.25rem;
    color: #f0f0f0;
}

/* Selects */
.server-filter-card select.form-select {
    background-color: #111;
    color: #f8f9fa;
    border: 1px solid #444;
    font-size: 0.85rem;
    box-shadow: inset 0 0 4px rgba(255, 255, 255, 0.05);
    transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.server-filter-card select.form-select:focus {
    border-color: #ffc107;
    box-shadow: 0 0 0 0.15rem rgba(255, 193, 7, 0.35);
}

/* Tag Filter Buttons */
#tagFilters .btn {
    border-radius: 30px;
    padding: 0.25rem 0.85rem;
    font-size: 0.75rem;
    font-weight: 500;
    background-color: #252525;
    color: #ccc;
    border: 1px solid #444;
    transition: all 0.2s ease-in-out;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 0 4px rgba(0,0,0,0.3);
}

#tagFilters .btn:hover {
    background-color: #3a3a3a;
    color: #fff;
}

#tagFilters .btn.active {
    background: linear-gradient(135deg, #198754, #157347);
    color: #fff;
    border-color: #198754;
    box-shadow: 0 0 6px rgba(25, 135, 84, 0.5);
}

</style>
<!-- Join Server Confirmation Modal -->
<div class="modal fade" id="joinServerModal" tabindex="-1" aria-labelledby="joinServerModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content server-card-modal text-light border-0 rounded-4 shadow-lg overflow-hidden">

      <!-- Full Background Image -->
      <div class="server-card-bg"></div>
      <div class="server-card-overlay"></div>

      <!-- Modal Content Layer -->
      <div class="server-card-modal-body d-flex flex-column justify-content-between text-center h-100">

        <!-- Top Message -->
        <div class="p-4">
          <h5 class="text-uppercase fw-bold mb-3">
            <i class="fa-solid fa-plug-circle-bolt me-2"></i> Ready to Join?
          </h5>
          <h3 id="modalServerName" class="fw-bold text-white mb-1"></h3>
          <div id="modalGameName" class=" small fst-italic mb-3"></div>

          <!-- Reveal Connection Info Button -->
          <button class="btn btn-outline-warning btn-sm mb-3" type="button" id="revealIpBtn">
            <i class="fa-solid fa-eye me-1"></i> Show Connection Info
          </button>

          <!-- Connection Info -->
          <div id="ipRevealSection"
               class="d-none fade-in text-start mx-auto bg-black bg-opacity-50 border border-warning rounded-3 p-3"
               style="max-width: 420px;">
            <p class="mb-2">
              <strong class="text-warning">IP:</strong>
              <span id="modalServerIp" class="text-light">192.168.x.x</span>
            </p>
            <p class="mb-0">
              <strong class="text-warning">Password:</strong>
              <span id="modalServerPassword" class="text-light">hunter2</span>
            </p>
          </div>
        </div>

        <!-- Footer Buttons -->
        <div class="modal-footer border-0 justify-content-center pb-4">
          <button type="button" class="btn btn-outline-light px-4" data-bs-dismiss="modal">
            <i class="fa-solid fa-xmark me-1"></i> Cancel
          </button>
          <a id="confirmJoinBtn" href="#" class="btn btn-join px-4">
            <i class="fa-brands fa-steam me-1"></i> Join via Steam
          </a>
        </div>

      </div>

    </div>
  </div>
</div>







<main class="container pt-3 bg-body" style="margin-bottom: 56px;">
    <div class="row">
        <div class="col-12 col-xl-9">

            <?php
            $activePageName = "Community Servers";
            require("php-components/base-page-breadcrumbs.php");
            ?>
            <div class="card shadow-sm mb-4 server-filter-card">
                <div class="card-body">
                <h5 class="text-light fw-bold mb-3">
  <i class="fa-solid fa-filter me-2"></i> Filter Servers
</h5>
                    <div class="row g-3 align-items-end">

                    <!-- Game Filter -->
                    <div class="col-md-4">
                        <label for="gameFilter" class="form-label text-light">Game</label>
                        <select id="gameFilter" class="form-select form-select-sm">
                        <option value="">All Games</option>
                        <?php
                        $gameNames = [];
                        foreach ($servers as $s) {
                            $game = $s->game->name;
                            if (!in_array($game, $gameNames)) {
                                $gameNames[] = $game;
                                echo '<option value="' . htmlspecialchars($game) . '">' . htmlspecialchars($game) . '</option>';
                            }
                        }
                        ?>
                        </select>
                    </div>

                    <!-- Join Method -->
                    <div class="col-md-4">
                        <label for="joinMethodFilter" class="form-label text-light">Join Method</label>
                        <select id="joinMethodFilter" class="form-select form-select-sm">
                        <option value="">All Methods</option>
                        <option value="steam">Steam</option>
                        <option value="kickback">Kickback Kingdom Launcher</option>
                        <option value="manual">Manual</option>
                        </select>
                    </div>

                    <!-- Sort -->
                    <div class="col-md-4">
                        <label for="sortServers" class="form-label text-light">Sort By</label>
                        <select id="sortServers" class="form-select form-select-sm">
                        <option value="default">Default</option>
                        <option value="online">Online First</option>
                        <option value="players">Active Players</option>
                        <option value="name">A-Z Name</option>
                        </select>
                    </div>

                    <!-- Tags -->
                    <div class="col-md-12 mt-2">
                        <label class="form-label text-light mb-1">Filter by Tags</label>
                        <div class="d-flex flex-wrap gap-2" id="tagFilters"></div>
                    </div>

                    </div>
                </div>
                </div>

                

            <div class="row g-4" id="kingdom-realms-list">
                <?php foreach ($servers as $server): ?>
                    <div class="col-12 col-md-6 col-lg-4 server-card-wrapper server-entry"
                        data-tags="<?= htmlspecialchars(json_encode($server->tags)) ?>"
                        data-join-method="<?= htmlspecialchars($server->joinMethod) ?>"
                        data-online="<?= $server->isOnline ? '1' : '0' ?>"
                        data-players="<?= $server->currentPlayers ?>"
                        data-game="<?= htmlspecialchars($server->game->name) ?>">

                            
                        <div class="server-card" style="--bg-image: url('<?= htmlspecialchars($server->icon?->getFullPath() ?? '/assets/media/banners/default.jpg') ?>');">
                        
                            <?php if ($server->isOfficial): ?>

                                <div class="server-official-banner bg-ranked-1">
                                    👑 Official Server of Kickback Kingdom
                                </div>
                            <?php endif; ?>


                            <div class="server-card-content text-center">
                                
                            




                                <?php if (!empty($server->region)): ?>
                                    <div class="d-flex justify-content-center align-items-center gap-2 mb-2">
                                        <span class="badge rounded-pill bg-<?= $server->isOnline ? 'success' : 'danger' ?>">
                                            ● <?= $server->isOnline ? 'Online' : 'Offline' ?>
                                        </span>

                                        <span class="server-region-label text-light small d-flex align-items-center gap-1">
                                            <img src="https://flagcdn.com/16x12/<?= getRegionFlagCode($server->region) ?>.png"
                                                width="16" height="12" alt="<?= htmlspecialchars($server->region) ?>"
                                                style="object-fit: contain;">
                                            <?= htmlspecialchars($server->region) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>





                                <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
                                    <img src="<?= htmlspecialchars($server->game->icon?->getFullPath() ?? '/assets/media/icons/default.png') ?>"
                                        alt="<?= htmlspecialchars($server->game->name) ?> Icon"
                                        style="width: 28px; height: 28px; object-fit: cover; border-radius: 6px; box-shadow: 0 0 4px rgba(0,0,0,0.5);">
                                    <h5 class="mb-0 text-light text-shadow-sm fw-bold"><?= htmlspecialchars($server->name) ?></h5>
                                </div>
                                <div class="meta mb-2 small" style="color: #f8f9fa; text-shadow: 1px 1px 2px rgba(0,0,0,0.7);">
  <strong><?= htmlspecialchars($server->game->name) ?></strong>
  —
  Recent players <strong><?= $server->currentPlayers ?></strong> / Max <?= $server->maxPlayers ?>
</div>






                                <?php if (!empty($server->tags)): ?>
                                    <p class="mt-2 mb-3 d-flex flex-wrap justify-content-center gap-1">
                                        <?php foreach ($server->tags as $tag): ?>
                                            <span class="badge bg-primary"><?= htmlspecialchars($tag) ?></span>
                                        <?php endforeach; ?>
                                    </p>
                                <?php endif; ?>



                                <div class="server-card-footer mt-3">
                                    <a href="<?= $server->gameUrl() ?>" class="btn btn-outline-light btn-sm">View Page</a>

                                    <?php if (Session::isLoggedIn()): ?>
                                        <?php if ($server->joinMethod !== 'manual'): ?>
                                            <button
                                                class="btn btn-sm bg-ranked-1"
                                                data-bs-toggle="modal"
                                                data-bs-target="#joinServerModal"
                                                onclick="showJoinModal(
                                                    '<?= htmlspecialchars($server->ip . ':' . $server->port) ?>',
                                                    '<?= htmlspecialchars($server->name) ?>',
                                                    '<?= htmlspecialchars($server->password) ?>',
                                                    '<?= htmlspecialchars($server->game->name) ?>',
                                                    '<?= htmlspecialchars($server->bannerMobile?->getFullPath() ?? '/assets/media/banners/default.jpg') ?>'
                                                )">
                                                Join Server
                                            </button>



                                            <?php else: ?>
                                                <button class="btn btn-outline-light btn-sm"
                                                        onclick="copyToClipboard('ip-<?= $server->crand ?>')">
                                                    Copy IP
                                                </button>
                                                <input type="text" id="ip-<?= $server->crand ?>" value="<?= htmlspecialchars($server->ip . ':' . $server->port) ?>" readonly hidden>
                                            <?php endif; ?>

                                    <?php else: ?>
                                        <a href="/login.php?redirect=servers.php" class="btn btn-sm bg-ranked-1">Join Server</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>



        </div>

        <?php require("php-components/base-page-discord.php"); ?>
    </div>
    <?php require("php-components/base-page-footer.php"); ?>
</main>

<?php require("php-components/base-page-javascript.php"); ?>
<script>
function showJoinModal(ip, name, password = '', game = '', banner = '') {
  const nameEl = document.getElementById('modalServerName');
  const ipEl = document.getElementById('modalServerIp');
  const pwEl = document.getElementById('modalServerPassword');
  const revealBtn = document.getElementById('revealIpBtn');
  const revealSection = document.getElementById('ipRevealSection');
  const joinBtn = document.getElementById('confirmJoinBtn');
  const gameEl = document.getElementById('modalGameName');

  document.querySelector('.server-card-bg').style.setProperty('--modal-bg-image', `url('${banner || '/assets/media/banners/default.jpg'}')`);

  nameEl.textContent = name;
  ipEl.textContent = ip;
  pwEl.textContent = password || 'None';
  gameEl.textContent = game;
  joinBtn.href = 'steam://connect/' + ip;

  // Reset
  revealSection.classList.add('d-none');
  revealBtn.innerHTML = `<i class="fa-solid fa-eye me-1"></i> Show Connection Info`;

  revealBtn.onclick = () => {
    const isHidden = revealSection.classList.contains('d-none');
    revealSection.classList.toggle('d-none', !isHidden);
    revealBtn.innerHTML = isHidden
      ? `<i class="fa-solid fa-eye-slash me-1"></i> Hide Connection Info`
      : `<i class="fa-solid fa-eye me-1"></i> Show Connection Info`;
  };
}



document.addEventListener('DOMContentLoaded', () => {
    const entries = Array.from(document.querySelectorAll('.server-card-wrapper'));
    const tagFilters = document.getElementById('tagFilters');
    const methodFilter = document.getElementById('joinMethodFilter');
    const gameFilter = document.getElementById('gameFilter');
    const sortSelect = document.getElementById('sortServers');
    const serverList = document.getElementById('kingdom-realms-list');

    // Collect unique tags from all entries
    const uniqueTags = new Set();
    entries.forEach(entry => {
        try {
            const tags = JSON.parse(entry.dataset.tags || '[]');
            tags.forEach(tag => uniqueTags.add(tag));
        } catch (e) {
            console.warn("Invalid tag JSON:", entry.dataset.tags);
        }
    });

    // Build tag filter buttons
    uniqueTags.forEach(tag => {
        const btn = document.createElement('button');
        btn.className = 'btn btn-outline-secondary btn-sm';
        btn.textContent = tag;
        btn.dataset.tag = tag;
        btn.addEventListener('click', () => {
            btn.classList.toggle('active');
            applyFilters();
        });
        tagFilters.appendChild(btn);
    });

    // Event listeners
    methodFilter?.addEventListener('change', applyFilters);
    gameFilter?.addEventListener('change', applyFilters);
    sortSelect?.addEventListener('change', applyFilters);

    function applyFilters() {
        const activeTags = Array.from(tagFilters.querySelectorAll('.btn.active')).map(b => b.dataset.tag);
        const selectedMethod = methodFilter?.value || '';
        const selectedGame = gameFilter?.value || '';
        const sortBy = sortSelect?.value || 'default';

        // Sort entries
        const sortedEntries = [...entries].sort((a, b) => {
            const getAttr = (el, attr) => el.dataset[attr] || '';
            const parseNum = (el, attr) => parseInt(el.dataset[attr] || '0');

            switch (sortBy) {
                case 'players': return parseNum(b, 'players') - parseNum(a, 'players');
                case 'name':    return getAttr(a, 'game').localeCompare(getAttr(b, 'game'));
                case 'online':  return (getAttr(b, 'online') === '1') - (getAttr(a, 'online') === '1');
                default:        return 0;
            }
        });

        // Filter and show/hide
        sortedEntries.forEach(entry => {
            const entryTags = JSON.parse(entry.dataset.tags || '[]');
            const methodMatch = !selectedMethod || entry.dataset.joinMethod === selectedMethod;
            const gameMatch = !selectedGame || entry.dataset.game === selectedGame;
            const tagsMatch = activeTags.length === 0 || activeTags.every(tag => entryTags.includes(tag));

            entry.classList.toggle('hidden', !(methodMatch && gameMatch && tagsMatch));
        });

        // Reorder DOM
        sortedEntries.forEach(entry => {
            if (entry.parentNode === serverList) {
                serverList.appendChild(entry);
            }
        });
    }

    applyFilters(); // Initial call
});
</script>


</body>
</html>
