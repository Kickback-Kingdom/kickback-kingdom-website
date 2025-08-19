<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

use Kickback\Services\Session;
use Kickback\Common\Version;
use Kickback\Backend\Controllers\FlavorTextController;

if (!Session::isLoggedIn()) {
    header("Location: ".Version::urlBetaPrefix()."/login.php?redirect=" . urlencode("account-settings.php"));
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
                /* ---------- Layout polish ---------- */
                .providers-card { border: 0; overflow: hidden; }
                .providers-card .card-header {
                background: linear-gradient(135deg, rgba(108,117,125,.12), rgba(33,37,41,.06));
                border-bottom: 0;
                }
                .providers-card .card-header h5 { margin: 0; display:flex; align-items:center; gap:.5rem; }

                /* Provider container */
                .provider {
                border: 1px solid var(--bs-border-color);
                border-radius: .9rem;
                padding: 1rem;
                background: var(--bs-body-bg);
                }
                .provider + .provider { margin-top: 1rem; }

                /* Header becomes a neat grid: icon/label • hint • actions */
                .provider-head {
                display: grid;
                grid-template-columns: auto 1fr auto;
                gap: .75rem 1rem;
                align-items: center;
                }
                @media (max-width: 768px) {
                .provider-head { grid-template-columns: 1fr; }
                }

                /* Pill for provider label */
                .provider-pill {
                display:inline-flex; align-items:center; gap:.5rem;
                padding:.35rem .65rem; border-radius:999px;
                font-weight:600; font-size:.9rem; border:1px solid transparent;
                white-space: nowrap;
                }

                /* Header hint text */
                .provider-hint { font-size:.9rem; color: var(--bs-secondary-color); }

                /* Actions area keeps buttons aligned right on desktop */
                .provider-actions { display:flex; gap:.5rem; justify-self:end; }

                /* ---------- Brand tints ---------- */
                :root{
                --discord: #5865F2;
                --steam:   #00ADEE;
                }
                .provider--discord .provider-pill{ background: color-mix(in srgb, var(--discord) 12%, transparent); border-color: color-mix(in srgb, var(--discord) 25%, transparent); }
                .provider--discord .link-cta{ background: var(--discord); border-color: var(--discord); }

                .provider--steam .provider-pill{ background: linear-gradient(135deg, rgba(23,26,33,.18), color-mix(in srgb, var(--steam) 10%, transparent)); border-color: color-mix(in srgb, var(--steam) 25%, transparent); }
                .provider--steam .link-cta{ background: var(--steam); border-color: var(--steam); }

                .link-cta:hover{ filter: brightness(1.05); }
                .unlink-cta{ border-color:#dc3545; }

                /* ---------- Unified callout component ---------- */
                .provider-callout{
                display:flex; align-items:flex-start; gap:.6rem;
                padding:.75rem .9rem; border-radius:.75rem; border:1px solid;
                background: var(--callout-bg); border-color: var(--callout-brd);
                }
                .provider--discord .provider-callout{ --callout-bg: linear-gradient(90deg, color-mix(in srgb, var(--discord) 12%, transparent), rgba(32,34,37,.05)); --callout-brd: color-mix(in srgb, var(--discord) 25%, transparent); }
                .provider--steam   .provider-callout{ --callout-bg: linear-gradient(90deg, color-mix(in srgb, var(--steam)   12%, transparent), rgba(32,34,37,.05)); --callout-brd: color-mix(in srgb, var(--steam)   25%, transparent); }

                /* ---------- Linked meta (username + flavor) ---------- */
                .linked-meta{ margin-top:.6rem; }
                .linked-identity{
                display:inline-flex; align-items:center; gap:.5rem;
                padding:.35rem .65rem; border-radius:.6rem;
                border:1px solid var(--bs-border-color); background: var(--bs-body-bg);
                font-weight:600; font-size:.95rem; max-width:100%;
                }
                .linked-identity .username-text{
                display:inline-block; max-width:32ch; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
                }
                .linked-flavor{ margin-top:.3rem; font-size:.9rem; color: var(--bs-secondary-color); line-height:1.35; max-width:70ch; }

                .provider--discord .linked-identity{ background: color-mix(in srgb, var(--discord) 8%, transparent);  border-color: color-mix(in srgb, var(--discord) 25%, transparent); }
                .provider--steam   .linked-identity{ background: color-mix(in srgb, var(--steam)   8%, transparent);  border-color: color-mix(in srgb, var(--steam)   25%, transparent); }

                /* ---------- Perk chips (less noisy) ---------- */
                .perk-list{ display:flex; flex-wrap:wrap; gap:.5rem; }
                .perk-chip{
                display:inline-flex; align-items:center; gap:.35rem; padding:.28rem .6rem;
                border-radius:999px; font-size:.8rem;
                background: var(--bs-light); border:1px solid color-mix(in srgb, var(--bs-border-color) 75%, transparent);
                opacity:.95;
                }
                /* ---------- Flavor card (brand-tinted) ---------- */
                .flavor-card{
                display:flex; align-items:flex-start; gap:.6rem;
                padding:.75rem .9rem; border-radius:.75rem; border:1px solid;
                background: var(--flavor-bg); border-color: var(--flavor-brd);
                box-shadow: inset 0 1px 0 rgba(255,255,255,.25);
                }
                .flavor-card__icon{
                display:inline-flex; align-items:center; justify-content:center;
                width:1.75rem; height:1.75rem; border-radius:.5rem;
                background: var(--flavor-ico-bg); border:1px solid var(--flavor-brd);
                flex:0 0 1.75rem;
                }
                .flavor-card__body{ min-width:0; }
                .flavor-card__eyebrow{
                font-size:.75rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase;
                color: var(--bs-secondary-color); margin-bottom:.15rem;
                }
                .flavor-card__text{
                font-size:.95rem; line-height:1.45; color: var(--bs-body-color);
                max-width: 70ch; word-wrap: break-word; hyphens: auto;
                }

                /* Brand tints */
                .provider--discord .flavor-card{
                --flavor-bg: linear-gradient(90deg, color-mix(in srgb, var(--discord) 12%, transparent), rgba(32,34,37,.05));
                --flavor-brd: color-mix(in srgb, var(--discord) 25%, transparent);
                --flavor-ico-bg: color-mix(in srgb, var(--discord) 10%, transparent);
                }
                .provider--steam .flavor-card{
                --flavor-bg: linear-gradient(90deg, color-mix(in srgb, var(--steam) 12%, transparent), rgba(32,34,37,.05));
                --flavor-brd: color-mix(in srgb, var(--steam) 25%, transparent);
                --flavor-ico-bg: color-mix(in srgb, var(--steam) 10%, transparent);
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
                        <div class="provider-head">
                            <div class="provider-pill">
                                <i class="fa-brands fa-discord"></i> Discord
                            </div>

                            <div class="provider-actions">
                                <?php if (!$account->isDiscordLinked()) { ?>
                                <button type="button" class="btn bg-ranked-1" id="btnLinkDiscord">
                                    <i class="fa-solid fa-plug me-1"></i> Link Discord
                                </button>
                                <?php } else { ?>
                                <span class="badge bg-ranked-1 d-flex align-items-center"><i class="fa-solid fa-link me-1"></i> Linked</span>
                                <button type="button" class="btn btn-outline-danger btn-sm unlink-cta d-flex align-items-center gap-2"
                                        id="btnUnlinkDiscord" data-bs-toggle="tooltip" title="Removes your Discord connection">
                                    <i class="fa-solid fa-link-slash"></i><span>Unlink</span>
                                </button>
                                <?php } ?>
                            </div>
                        </div>


                        <?php if ($account->isDiscordLinked()) { ?>
                        <div class="linked-meta">
                            <div class="flavor-card mt-2" role="note" aria-label="Discord flavor text">
                                <div class="flavor-card__icon">
                                    <i class="fa-brands fa-discord"></i>
                                </div>
                                <div class="flavor-card__body">
                                    <div class="flavor-card__eyebrow"><span class="username-text text-truncate"><?= htmlspecialchars($account->discordUsername); ?> - Linked Account</span></div>
                                    <div class="flavor-card__text">
                                    <?= nl2br(htmlspecialchars(\Kickback\Backend\Controllers\FlavorTextController::getLinkedAccountFlavorText($account->username))); ?>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="provider-callout mt-3">
                            <i class="fa-solid fa-scroll mt-1"></i>
                            <div class="small">Your bond is sealed in the royal ledger. Those who linked before you whisper of a curious reward—guard it well.</div>
                        </div>
                        <?php } else { ?>
                        <div class="provider-callout mt-3">
                            <i class="fa-solid fa-badge-check mt-1"></i>
                            <div class="small">
                            <strong>Get Discord Verified</strong> — Connect your Discord to verify account status, join special events, and receive secret drops.
                            </div>
                        </div>

                        <?php } ?>

                        <div class="perk-list mt-3 mb-0 small text-muted">
                            <span class="perk-chip"><i class="fa-solid fa-shield"></i> Verified Discord Role</span>
                            <span class="perk-chip"><i class="fa-solid fa-calendar-day"></i> Event pings</span>
                            <span class="perk-chip"><i class="fa-solid fa-dragon"></i> Special Quests</span>
                            <span class="perk-chip"><i class="fa-solid fa-gift"></i> Secret drops</span>
                        </div>
                    </section>

                    <!-- Steam Provider -->
                    <section class="provider provider--steam mt-4 d-none">
                        <div class="provider-head">
                            <div class="provider-pill">
                                <i class="fa-brands fa-steam"></i> Steam
                            </div>


                            <div class="provider-actions">
                                <?php if (!$account->isSteamLinked()) { ?>
                                <button type="button" class="btn bg-ranked-1" id="btnLinkSteam">
                                    <i class="fa-solid fa-plug me-1"></i> Link Steam
                                </button>
                                <?php } else { ?>
                                <span class="badge bg-ranked-1 d-flex align-items-center"><i class="fa-solid fa-link me-1"></i> Linked</span>
                                <button type="button" class="btn btn-outline-danger btn-sm unlink-cta d-flex align-items-center gap-2"
                                        id="btnUnlinkSteam" data-bs-toggle="tooltip" title="Removes your Steam connection">
                                    <i class="fa-solid fa-link-slash"></i><span>Unlink</span>
                                </button>
                                <?php } ?>
                            </div>
                        </div>


                        <?php if ($account->isSteamLinked()) { ?>
                        <div class="linked-meta">
                            <div class="flavor-card mt-2" role="note" aria-label="Steam flavor text">
                                <div class="flavor-card__icon">
                                    <i class="fa-brands fa-steam"></i>
                                </div>
                                <div class="flavor-card__body">
                                    <div class="flavor-card__eyebrow"><span class="username-text text-truncate"><?= htmlspecialchars($account->steamUsername); ?></span> - Linked Account</div>
                                    <div class="flavor-card__text">
                                        <?= nl2br(htmlspecialchars(\Kickback\Backend\Controllers\FlavorTextController::getLinkedAccountFlavorText($account->username))); ?>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="provider-callout mt-3">
                            <i class="fa-solid fa-scroll mt-1"></i>
                            <div class="small">Your Steam banner now flies in the Kingdom. Watch the town board for your exclusive draws.</div>
                        </div>
                        <?php } else { ?>
                        <div class="provider-callout mt-3">
                            <i class="fa-solid fa-ticket mt-1"></i>
                            <div class="small">
                                <strong>Steam Raffles Await</strong> — Link your Steam account to enter exclusive raffles, showcase your game library, earn surprise rewards, and make it easier for friends to find you.
                            </div>

                        </div>

                        <?php } ?>

                        <div class="perk-list mt-3 mb-0 small text-muted">
                            <span class="perk-chip"><i class="fa-solid fa-ticket"></i> Entry to Steam raffles</span>
                            <span class="perk-chip"><i class="fa-solid fa-gamepad"></i> Share your game library</span>
                            <span class="perk-chip"><i class="fa-solid fa-bolt"></i> Quick-join Kickback servers</span>
                        </div>
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
            fetch('<?= Version::urlBetaPrefix(); ?>/api/v1/discord/link-start.php', { credentials: 'same-origin' })
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
            fetch('<?= Version::urlBetaPrefix(); ?>/api/v1/steam/link-start.php', { credentials: 'same-origin' })
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
            fetch('<?= Version::urlBetaPrefix(); ?>/api/v1/discord/unlink.php', { method: 'POST', credentials: 'same-origin' })
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
            fetch('<?= Version::urlBetaPrefix(); ?>/api/v1/steam/unlink.php', { method: 'POST', credentials: 'same-origin' })
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
