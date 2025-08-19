<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

use Kickback\Services\Session;
use Kickback\Backend\Controllers\FlavorTextController;

if (!Session::isLoggedIn()) {
    header("Location: login.php?redirect=" . urlencode("account-settings.php"));
    exit();
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

    <!-- Discord Unlink Confirmation Modal -->
    <div class="modal fade" id="unlinkDiscordModal" tabindex="-1" aria-labelledby="unlinkDiscordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="unlinkDiscordModalLabel">Unlink Discord</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to unlink your Discord account?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmUnlinkDiscord">Unlink</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Steam Unlink Confirmation Modal -->
    <div class="modal fade" id="unlinkSteamModal" tabindex="-1" aria-labelledby="unlinkSteamModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="unlinkSteamModalLabel">Unlink Steam</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to unlink your Steam account?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmUnlinkSteam">Unlink</button>
                </div>
            </div>
        </div>
    </div>

    <main class="container pt-3 bg-body" style="margin-bottom: 56px;">
        <div class="row">
            <div class="col-12 col-xl-9">
                <?php
                $activePageName = "Account Settings";
                require("php-components/base-page-breadcrumbs.php");
                ?>

                <style>
                /* --- Third-Party Linking polished look (scoped) --- */
                .providers-card { border: 0; overflow: hidden; }
                .providers-card .card-header {
                    background: linear-gradient(135deg, rgba(108,117,125,.12), rgba(33,37,41,.06));
                    border-bottom: 0;
                }
                .providers-card .card-header h5 { margin: 0; display:flex; align-items:center; gap:.5rem; }

                .provider {
                    border: 1px solid var(--bs-border-color);
                    border-radius: .85rem;
                    padding: .9rem;
                }
                .provider + .provider { margin-top: 1rem; }

                .provider-head { display:flex; align-items:center; gap:.6rem; font-weight:600; }
                .provider-pill {
                    display:inline-flex; align-items:center; gap:.5rem;
                    padding:.35rem .65rem; border-radius:999px;
                    font-weight:600; font-size:.9rem; border:1px solid transparent;
                }

                /* Discord styling */
                .provider--discord .provider-pill {
                    background: rgba(88,101,242,.12);
                    border-color: rgba(88,101,242,.25);
                }
                .link-cta { background:#5865F2; border-color:#5865F2; }
                .link-cta:hover { filter:brightness(1.05); }
                .unlink-cta { border-color:#dc3545; }

                .reward-callout {
                    background: linear-gradient(90deg, rgba(88,101,242,.12), rgba(32,34,37,.05));
                    border: 1px solid rgba(88,101,242,.25);
                    border-radius: .75rem; padding: .75rem .9rem;
                }

                .perk-chip {
                    display:inline-flex; align-items:center; gap:.4rem;
                    border-radius:999px; padding:.25rem .6rem;
                    background: var(--bs-light); border:1px dashed var(--bs-secondary-color);
                    font-size:.8rem; opacity:.9;
                }

                /* Steam styling */
                .provider--steam .provider-pill {
                    /* Steam brand vibes: deep slate + cyan accent */
                    background: linear-gradient(135deg, rgba(23,26,33,.18), rgba(0,173,238,.10));
                    border-color: rgba(0,173,238,.25);
                }
                .steam-hint {
                    font-size:.875rem; color: var(--bs-secondary-color);
                }
                </style>

                <div class="card mb-4 providers-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                    <i class="fa-solid fa-circle-nodes"></i>
                    Third-Party Linking
                    </h5>
                </div>

                <div class="card-body">

                    <!-- Discord Provider -->
                    <section class="provider provider--discord">
                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                        <div class="d-flex align-items-start gap-3">
                        <div class="provider-pill">
                            <i class="fa-brands fa-discord"></i> Discord
                        </div>
                        <div class="small text-muted">
                            Connect your Discord to verify account status, join events, and receive secret drops.
                        </div>
                        </div>

                        <div class="d-flex gap-2">
                        <?php if (!$account->isDiscordLinked()) { ?>
                            <button type="button" class="btn btn-primary link-cta" id="btnLinkDiscord">
                            <i class="fa-solid fa-plug me-1"></i> Link Discord
                            </button>
                        <?php } else { ?>
                            <span class="badge text-bg-success d-flex align-items-center">
                            <i class="fa-solid fa-link me-1"></i> Linked
                            </span>
                            <button
                            type="button"
                            class="btn btn-outline-danger btn-sm unlink-cta d-flex align-items-center gap-2"
                            id="btnUnlinkDiscord"
                            data-bs-toggle="tooltip"
                            title="Removes your Discord connection"
                            >
                            <i class="fa-solid fa-link-slash"></i>
                            <span>Unlink</span>
                            </button>
                        <?php } ?>
                        </div>
                    </div>

                    <?php if ($account->isDiscordLinked()) { ?>
                        <div class="mt-2">
                        <div class="fw-semibold"><?= htmlspecialchars($account->discordUsername); ?></div>
                        <div class="small text-muted">
                            <?= htmlspecialchars(\Kickback\Backend\Controllers\FlavorTextController::getDiscordLinkFlavorText($account->username)); ?>
                        </div>
                        </div>
                        <div class="reward-callout mt-3 d-flex align-items-start gap-2">
                        <i class="fa-solid fa-scroll mt-1"></i>
                        <div class="small">Your bond is sealed in the royal ledger. Those who linked before you whisper of a curious reward—guard it well.</div>
                        </div>
                    <?php } else { ?>
                        <div class="reward-callout mt-3 d-flex align-items-start gap-2">
                        <i class="fa-solid fa-treasure-chest mt-1"></i>
                        <div class="small">
                            <strong>Mystery Reward Unlocked</strong> — Link your Discord to receive a hidden boon from the Kingdom. What is it? Only those who bind their sigil discover the truth.
                        </div>
                        </div>

                        <ul class="mt-3 mb-0 small text-muted d-flex flex-wrap gap-2 list-unstyled">
                        <li class="perk-chip"><i class="fa-solid fa-shield"></i> Verified Discord Role</li>
                        <li class="perk-chip"><i class="fa-solid fa-calendar-day"></i> Event pings</li>
                        <li class="perk-chip"><i class="fa-solid fa-gift"></i> Secret drops</li>
                        </ul>
                    <?php } ?>
                    </section>

                    <!-- Steam Provider -->
                    <section class="provider provider--steam mt-4">
                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                        <div class="d-flex align-items-start gap-3">
                        <div class="provider-pill">
                            <i class="fa-brands fa-steam"></i> Steam
                        </div>
                        <div class="steam-hint">
                            Add your Steam to show game links and let friends find you faster.
                        </div>
                        </div>

                        <div class="d-flex gap-2">
                        <?php if (!$account->isSteamLinked()) { ?>
                            <button type="button" class="btn btn-primary link-cta" id="btnLinkSteam">
                            <i class="fa-solid fa-plug me-1"></i> Link Steam
                            </button>
                        <?php } else { ?>
                            <span class="badge text-bg-success d-flex align-items-center">
                            <i class="fa-solid fa-link me-1"></i> Linked
                            </span>
                            <button
                            type="button"
                            class="btn btn-outline-danger btn-sm unlink-cta d-flex align-items-center gap-2"
                            id="btnUnlinkSteam"
                            data-bs-toggle="tooltip"
                            title="Removes your Steam connection"
                            >
                            <i class="fa-solid fa-link-slash"></i>
                            <span>Unlink</span>
                            </button>
                        <?php } ?>
                        </div>
                    </div>

                    <?php if ($account->isSteamLinked()) { ?>
                        <div class="mt-2">
                        <div class="fw-semibold"><?= htmlspecialchars($account->steamUsername); ?></div>
                        <div class="small text-muted">Steam account linked.</div>
                        </div>
                    <?php } ?>
                    </section>

                    <div id="steamStatus" class="alert d-none mt-3" role="alert"></div>
                    <div id="discordStatus" class="alert d-none mt-3" role="alert"></div>
                </div>
                </div>

                <script>
                // Bootstrap tooltips (if not globally enabled)
                document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el){ new bootstrap.Tooltip(el); });
                </script>




            </div>
            <?php require("php-components/base-page-discord.php"); ?>
        </div>
        <?php require("php-components/base-page-footer.php"); ?>
    </main>

    <?php require("php-components/base-page-javascript.php"); ?>
    <script>
        
document.addEventListener('DOMContentLoaded', () => {
        const params = new URLSearchParams(window.location.search);
        const discordError = params.get('discord_error');
        if (discordError) {
            const statusDiv = $('#discordStatus');
            statusDiv.removeClass('d-none alert-success').addClass('alert-danger').text(discordError);
            ShowPopError(discordError, 'Discord');
            params.delete('discord_error');
            const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
            history.replaceState({}, '', newUrl);
        }

        const steamError = params.get('steam_error');
        if (steamError) {
            const statusDiv = $('#steamStatus');
            statusDiv.removeClass('d-none alert-success').addClass('alert-danger').text(steamError);
            ShowPopError(steamError, 'Steam');
            params.delete('steam_error');
            const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
            history.replaceState({}, '', newUrl);
        }

        $('#btnLinkDiscord').on('click', function () {
            const statusDiv = $('#discordStatus');
            fetch('/api/v1/discord/link-start.php', { credentials: 'same-origin' })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data && data.data.url) {
                        window.location = data.data.url;
                    } else {
                        const msg = data.message || 'Failed to link Discord';
                        statusDiv.removeClass('d-none alert-success').addClass('alert-danger').text(msg);
                        ShowPopError(msg, 'Discord');
                    }
                })
                .catch(() => {
                    const msg = 'Failed to link Discord';
                    statusDiv.removeClass('d-none alert-success').addClass('alert-danger').text(msg);
                    ShowPopError(msg, 'Discord');
                });
        });

        $('#btnLinkSteam').on('click', function () {
            const statusDiv = $('#steamStatus');
            fetch('/api/v1/steam/link-start.php', { credentials: 'same-origin' })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data && data.data.url) {
                        window.location = data.data.url;
                    } else {
                        const msg = data.message || 'Failed to link Steam';
                        statusDiv.removeClass('d-none alert-success').addClass('alert-danger').text(msg);
                        ShowPopError(msg, 'Steam');
                    }
                })
                .catch(() => {
                    const msg = 'Failed to link Steam';
                    statusDiv.removeClass('d-none alert-success').addClass('alert-danger').text(msg);
                    ShowPopError(msg, 'Steam');
                });
        });

        $('#btnUnlinkDiscord').on('click', function () {
            $('#unlinkDiscordModal').modal('show');
        });

        $('#btnUnlinkSteam').on('click', function () {
            $('#unlinkSteamModal').modal('show');
        });

        $('#confirmUnlinkDiscord').on('click', function () {
            fetch('/api/v1/discord/unlink.php', { method: 'POST', credentials: 'same-origin' })
                .then(response => response.json())
                .then(data => {
                    const statusDiv = $('#discordStatus');
                    if (data.success) {
                        statusDiv.removeClass('d-none alert-danger').addClass('alert-success').text(data.message);
                        ShowPopSuccess(data.message, 'Discord');
                        setTimeout(function () { location.reload(); }, 1000);
                    } else {
                        statusDiv.removeClass('d-none alert-success').addClass('alert-danger').text(data.message);
                        ShowPopError(data.message, 'Discord');
                    }
                })
                .catch(() => {
                    ShowPopError('Failed to unlink Discord', 'Discord');
                })
                .finally(() => {
                    $('#unlinkDiscordModal').modal('hide');
                });
        });

        $('#confirmUnlinkSteam').on('click', function () {
            fetch('/api/v1/steam/unlink.php', { method: 'POST', credentials: 'same-origin' })
                .then(response => response.json())
                .then(data => {
                    const statusDiv = $('#steamStatus');
                    if (data.success) {
                        statusDiv.removeClass('d-none alert-danger').addClass('alert-success').text(data.message);
                        ShowPopSuccess(data.message, 'Steam');
                        setTimeout(function () { location.reload(); }, 1000);
                    } else {
                        statusDiv.removeClass('d-none alert-success').addClass('alert-danger').text(data.message);
                        ShowPopError(data.message, 'Steam');
                    }
                })
                .catch(() => {
                    ShowPopError('Failed to unlink Steam', 'Steam');
                })
                .finally(() => {
                    $('#unlinkSteamModal').modal('hide');
                });
        });
    });
    </script>
</body>
</html>
