<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("../php-components/base-page-pull-active-account-info.php");

$inviteToken = $_GET['invite_token'] ?? '';
$kickbackAccount = $session->success ? $session->data : null;
$defaultDisplayName = $kickbackAccount ? trim(($kickbackAccount->firstName ?? '') . ' ' . ($kickbackAccount->lastName ?? '')) : '';
$defaultEmail = $kickbackAccount->email ?? '';
$pageTitle = "Join Secret Santa";
$pageDesc = "Join a Kickback Kingdom Secret Santa event.";
?>
<!DOCTYPE html>
<html lang="en">

<?php require("../php-components/base-page-head.php"); ?>

<body class="bg-body-secondary container p-0">
    <?php
    require("../php-components/base-page-components.php");
    ?>

    <!--TOP BANNER-->
    <div class="d-none d-md-block w-100 ratio" style="--bs-aspect-ratio: 26%; margin-top: 56px">

        <img src="/assets/media/events/1768.png" class="" />

    </div>
    <div class="d-block d-md-none w-100 ratio" style="margin-top: 56px; --bs-aspect-ratio: 46.3%;">

        <img src="/assets/media/events/1769.png" />

    </div>
    <style>
        .invite-hero {
            position: relative;
            background: radial-gradient(circle at 10% 20%, rgba(111, 66, 193, 0.3) 0, transparent 30%),
                radial-gradient(circle at 80% 0, rgba(13, 110, 253, 0.35) 0, transparent 32%),
                linear-gradient(135deg, #0a1a2f 0%, #1f2f55 50%, #29132a 100%);
            color: #f8f9fa;
        }

        .invite-hero::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.12), rgba(255, 255, 255, 0.02));
            pointer-events: none;
        }

        .invite-hero .hero-grid {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: 1fr;
            grid-template-areas:
                "content"
                "card";
            gap: 1.5rem;
            align-items: stretch;
        }

        .invite-hero .hero-content {
            grid-area: content;
        }

        .invite-hero .event-timing {
            grid-area: card;
        }

        .invite-hero .event-timing .hero-card {
            width: 100%;
        }

        .invite-hero .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.16);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 700;
            font-size: 0.75rem;
        }

        .invite-hero .hero-title {
            font-size: clamp(2.25rem, 3vw + 1.5rem, 3rem);
            line-height: 1.1;
        }

        .token-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.4rem 0.75rem;
            border-radius: 0.75rem;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            font-weight: 600;
        }

        .token-code {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 0.65rem;
            padding: 0.65rem 0.9rem;
            color: #fff;
            font-family: "SFMono-Regular", Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        }

        .hero-card {
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(241, 243, 246, 0.96));
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 1rem 2.5rem rgba(9, 14, 30, 0.25);
        }

        .countdown-card {
            background: transparent;
        }

        .countdown-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }

        @media (min-width: 768px) {
            .countdown-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .countdown-block {
            border: 1px solid var(--bs-border-color-translucent);
            border-radius: 1rem;
            padding: 1rem 1.25rem;
            background: #fff;
            height: 100%;
        }

        .count-chip {
            min-width: 90px;
            padding: 0.65rem 0.8rem;
            background: #0d6efd;
            color: #fff;
            border-radius: 0.9rem;
            text-align: center;
            box-shadow: 0 0.35rem 1rem rgba(13, 110, 253, 0.18);
        }

        .count-chip small {
            display: block;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            font-weight: 600;
            opacity: 0.9;
        }

        .count-number {
            transition: transform 0.2s ease;
            line-height: 1;
        }

        .count-number.pulse {
            animation: pulse 0.6s ease;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }

            100% {
                transform: scale(1);
            }
        }
    </style>

    <main class="container pt-3 bg-body" style="margin-bottom: 56px;">
        <div class="row">
            <div class="col-12 col-xl-9">
                <?php
                $activePageName = "Join Secret Santa";
                require("../php-components/base-page-breadcrumbs.php");
                ?>

                <!-- HERO / INVITE LOOKUP -->
                <section class="invite-hero rounded-4 mb-4 shadow-lg overflow-hidden border-0">
                    <div class="hero-grid p-4 p-md-5">
                        <div class="d-flex flex-column gap-3 gap-lg-4 hero-content">
                            <div class="eyebrow text-light text-opacity-85">
                                <i class="fa-solid fa-sleigh"></i>
                                Secret Santa Invitation
                            </div>
                            <div class="d-flex flex-column gap-2">
                                <h1 class="hero-title fw-bold mb-0">Claim your spot in the gift exchange</h1>
                                <p class="lead mb-0 text-light text-opacity-85">
                                    Load the invite, see the deadlines in real time, and get ready for reveal day. Your host’s token keeps
                                    the exchange private and coordinated.
                                </p>
                            </div>
                            <div class="d-flex flex-wrap align-items-center gap-2 gap-md-3">
                                <span class="token-chip">
                                    <i class="fa-solid fa-lock"></i>
                                    Invite token
                                </span>
                                <code class="token-code" id="inviteTokenDisplay"><?php echo htmlspecialchars($inviteToken ?: 'Not provided'); ?></code>
                            </div>
                            <div id="inviteStatus" class="small text-light text-opacity-85"></div>
                        </div>
                        <div class="event-timing">
                            <div class="card hero-card rounded-4">
                                <div class="card-body p-4 p-md-4 d-flex flex-column gap-3">
                                    <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                                        <div>
                                            <div class="small text-uppercase text-muted mb-1">Current event</div>
                                            <h2 class="h4 mb-1" id="heroEventTitle">Secret Santa exchange</h2>
                                            <div class="text-muted" id="heroEventSubtitle">Use your invite link to load the event details.</div>
                                        </div>
                                        <span class="badge bg-primary-subtle text-primary-emphasis align-self-start">Live countdowns</span>
                                    </div>
                                    <div class="countdown-card" id="countdownCard" style="display: none;">
                                        <div class="countdown-grid">
                                            <div class="countdown-block d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3" id="signupCountdown" style="display:none;">
                                                <div class="d-flex align-items-center gap-2 text-start">
                                                    <span class="rounded-circle bg-info-subtle text-info-emphasis d-inline-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                                        <i class="fa-solid fa-user-clock"></i>
                                                    </span>
                                                    <div>
                                                        <div class="small text-uppercase text-muted">Until signups close</div>
                                                        <div class="fw-semibold text-secondary">Lock in before names are drawn.</div>
                                                    </div>
                                                </div>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <div class="count-chip">
                                                        <div class="display-6 fw-bold count-number" id="countSignupDays">--</div>
                                                        <small>Days</small>
                                                    </div>
                                                    <div class="count-chip">
                                                        <div class="display-6 fw-bold count-number" id="countSignupHours">--</div>
                                                        <small>Hours</small>
                                                    </div>
                                                    <div class="count-chip">
                                                        <div class="display-6 fw-bold count-number" id="countSignupMinutes">--</div>
                                                        <small>Minutes</small>
                                                    </div>
                                                    <div class="count-chip">
                                                        <div class="display-6 fw-bold count-number" id="countSignupSeconds">--</div>
                                                        <small>Seconds</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="countdown-block d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3" id="giftCountdown" style="display:none;">
                                                <div class="d-flex align-items-center gap-2 text-start">
                                                    <span class="rounded-circle bg-warning-subtle text-warning-emphasis d-inline-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                                        <i class="fa-solid fa-gift"></i>
                                                    </span>
                                                    <div>
                                                        <div class="small text-uppercase text-muted">Until gift exchange</div>
                                                        <div class="fw-semibold text-secondary">Count down to reveal day.</div>
                                                    </div>
                                                </div>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <div class="count-chip">
                                                        <div class="display-6 fw-bold count-number" id="countGiftDays">--</div>
                                                        <small>Days</small>
                                                    </div>
                                                    <div class="count-chip">
                                                        <div class="display-6 fw-bold count-number" id="countGiftHours">--</div>
                                                        <small>Hours</small>
                                                    </div>
                                                    <div class="count-chip">
                                                        <div class="display-6 fw-bold count-number" id="countGiftMinutes">--</div>
                                                        <small>Minutes</small>
                                                    </div>
                                                    <div class="count-chip">
                                                        <div class="display-6 fw-bold count-number" id="countGiftSeconds">--</div>
                                                        <small>Seconds</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="small text-muted mt-3 mb-0" id="countdownNote"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- EVENT DETAILS -->
                <div class="card shadow-sm border-0 mb-4" id="eventDetailsCard" style="display:none;">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-info-subtle text-info-emphasis d-inline-flex align-items-center justify-content-center me-2" style="width: 44px; height: 44px;">
                                <i class="fa-solid fa-calendar-check"></i>
                            </div>
                            <div>
                                <div class="small text-uppercase text-muted mb-0">Event unlocked</div>
                                <h2 class="h5 mb-0" id="eventName"></h2>
                                <small class="text-muted" id="eventDeadlines"></small>
                            </div>
                        </div>
                        <p id="eventDescription" class="mb-0 text-secondary"></p>
                    </div>
                </div>

                <!-- JOIN + EXCLUSIONS -->
                <div class="row mb-4" id="joinRows" style="display:none;">
                    <div class="col-12">
                        <div class="card shadow-sm border-0" id="joinFormCard">
                            <div class="card-body p-4 d-flex flex-column gap-3">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-primary-subtle text-primary-emphasis d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="fa-solid fa-user-plus"></i>
                                        </div>
                                        <div>
                                            <h2 class="h5 mb-0">Join this exchange</h2>
                                            <small class="text-muted">Confirm your details and hop in.</small>
                                        </div>
                                    </div>
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis">Takes 10 seconds</span>
                                </div>

                                <div class="p-3 bg-body-secondary rounded-3">
                                    <div class="small text-uppercase text-muted mb-2">You're joining as</div>
                                    <div class="row g-3 align-items-center">
                                        <div class="col-12 col-md-6">
                                            <div class="small text-muted">Display name</div>
                                            <div class="fw-semibold" id="participantDisplayNameText">
                                                <?php echo htmlspecialchars($defaultDisplayName) ?: 'Secret Santa adventurer'; ?>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <div class="small text-muted">Email</div>
                                            <div class="fw-semibold" id="participantEmailText">
                                                <?php echo htmlspecialchars($defaultEmail) ?: 'Update your account email to join'; ?>
                                            </div>
                                            <div class="form-text mb-0">We'll use this email to confirm your signup.</div>
                                        </div>
                                    </div>
                                </div>

                                <form id="joinForm" class="row g-3 align-items-end">
                                    <input type="hidden" id="participantExclusionCtime">
                                    <input type="hidden" id="participantExclusionCrand">

                                    <div class="col-12 col-md-8">
                                        <label class="form-label mb-1" for="exclusionSelect">Exclusion group (optional)</label>
                                        <select class="form-select" id="exclusionSelect">
                                            <option value="">No exclusion group</option>
                                        </select>
                                        <div class="form-text">Pick who should never draw each other. Couples or roommates usually share a group.</div>
                                    </div>

                                    <div class="col-12 col-md-4 d-grid gap-2">
                                        <button class="btn btn-success" type="submit">Join event</button>
                                        <div id="joinStatus" class="small text-muted"></div>
                                    </div>
                                </form>

                                <div class="p-3 bg-body-secondary rounded-3">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <div class="rounded-circle bg-secondary-subtle text-secondary-emphasis d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                            <i class="fa-solid fa-people-group"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold mb-0">Need a new exclusion group?</div>
                                            <small class="text-muted">Add it, then pick it from the list above.</small>
                                        </div>
                                    </div>
                                    <form id="exclusionBuilder" class="row g-2 align-items-end">
                                        <div class="col-12 col-md-8">
                                            <label class="form-label" for="newExclusionName">Group name</label>
                                            <input class="form-control" id="newExclusionName" placeholder="Roommates, partners, work team">
                                        </div>
                                        <input type="hidden" id="newExclusionCtime">
                                        <input type="hidden" id="newExclusionCrand">
                                        <div class="col-12 col-md-4 d-grid">
                                            <label class="form-label opacity-0">Add group</label>
                                            <button class="btn btn-outline-secondary" type="submit">Add exclusion group</button>
                                        </div>
                                        <div class="col-12">
                                            <div id="exclusionBuilderStatus" class="small text-muted"></div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mb-4" id="participantListCard" style="display:none;">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-success-subtle text-success-emphasis d-inline-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                <i class="fa-solid fa-list-check"></i>
                            </div>
                            <div>
                                <h2 class="h6 mb-0">Who's already in</h2>
                                <small class="text-muted">Participants and their exclusion groups.</small>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0" id="participantTable">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">Name</th>
                                        <th scope="col">Email</th>
                                        <th scope="col">Exclusion group</th>
                                    </tr>
                                </thead>
                                <tbody id="participantTableBody">
                                    <tr class="table-light">
                                        <td colspan="3" class="text-center text-muted">No one has signed up yet.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- RULES / FUN COPY -->
                <section class="mb-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-2">
                                <div class="rounded-circle bg-warning-subtle text-warning-emphasis d-inline-flex align-items-center justify-content-center me-2" style="width: 42px; height: 42px;">
                                    <i class="fa-solid fa-scroll"></i>
                                </div>
                                <div>
                                    <div class="small text-uppercase text-muted mb-0">House rules</div>
                                    <h2 class="h5 mb-0">Make the exchange fair and festive</h2>
                                </div>
                            </div>
                            <div class="row g-4 mt-1">
                                <div class="col-md-4">
                                    <h3 class="h6 mb-1">Pairing magic</h3>
                                    <ul class="list-unstyled small text-secondary mb-0">
                                        <li class="d-flex align-items-start mb-1">
                                            <i class="fa-solid fa-check text-success me-2 mt-1"></i>
                                            <span>No self-pairing—we only match you with someone new.</span>
                                        </li>
                                        <li class="d-flex align-items-start mb-1">
                                            <i class="fa-solid fa-check text-success me-2 mt-1"></i>
                                            <span>Exclusion groups keep partners, roommates, or teams apart.</span>
                                        </li>
                                        <li class="d-flex align-items-start">
                                            <i class="fa-solid fa-check text-success me-2 mt-1"></i>
                                            <span>Hosts can reroll pairings if plans change.</span>
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-md-4">
                                    <h3 class="h6 mb-1">Timeline checkpoints</h3>
                                    <ul class="list-unstyled small text-secondary mb-0">
                                        <li class="d-flex align-items-start mb-1">
                                            <i class="fa-solid fa-check text-success me-2 mt-1"></i>
                                            <span>Signup closes before names are drawn.</span>
                                        </li>
                                        <li class="d-flex align-items-start mb-1">
                                            <i class="fa-solid fa-check text-success me-2 mt-1"></i>
                                            <span>Gift exchange day gets a live countdown right here.</span>
                                        </li>
                                        <li class="d-flex align-items-start">
                                            <i class="fa-solid fa-check text-success me-2 mt-1"></i>
                                            <span>Hosts can reopen signups if new friends appear.</span>
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-md-4">
                                    <h3 class="h6 mb-1">Budget + vibe</h3>
                                    <ul class="list-unstyled small text-secondary mb-0">
                                        <li class="d-flex align-items-start mb-1">
                                            <i class="fa-solid fa-check text-success me-2 mt-1"></i>
                                            <span>Stick to the posted budget so everyone feels comfy.</span>
                                        </li>
                                        <li class="d-flex align-items-start mb-1">
                                            <i class="fa-solid fa-check text-success me-2 mt-1"></i>
                                            <span>Use the description for any theme, wishlist, or swap rules.</span>
                                        </li>
                                        <li class="d-flex align-items-start">
                                            <i class="fa-solid fa-check text-success me-2 mt-1"></i>
                                            <span>Have fun! The Kingdom keeps the admin tidy.</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
            <?php require("../php-components/base-page-discord.php"); ?>
        </div>
        <?php require("../php-components/base-page-footer.php"); ?>
    </main>

    <?php require("../php-components/base-page-javascript.php"); ?>
    <script>
        const inviteTokenFromUrl = <?php echo json_encode($inviteToken); ?>;
        const accountDisplayName = <?php echo json_encode($defaultDisplayName); ?>;
        const accountEmail = <?php echo json_encode($defaultEmail); ?>;
        const accountCrand = <?php echo json_encode($kickbackAccount ? $kickbackAccount->crand : null); ?>;
        const inviteStatus = document.getElementById('inviteStatus');
        const heroEventTitle = document.getElementById('heroEventTitle');
        const heroEventSubtitle = document.getElementById('heroEventSubtitle');
        const eventDetailsCard = document.getElementById('eventDetailsCard');
        const eventNameEl = document.getElementById('eventName');
        const eventDeadlinesEl = document.getElementById('eventDeadlines');
        const eventDescEl = document.getElementById('eventDescription');
        const joinRows = document.getElementById('joinRows');
        const joinForm = document.getElementById('joinForm');
        const joinStatus = document.getElementById('joinStatus');
        const participantExclusionCtime = document.getElementById('participantExclusionCtime');
        const participantExclusionCrand = document.getElementById('participantExclusionCrand');
        const exclusionSelect = document.getElementById('exclusionSelect');
        const exclusionBuilderCard = document.getElementById('exclusionBuilderCard');
        const exclusionBuilder = document.getElementById('exclusionBuilder');
        const exclusionBuilderStatus = document.getElementById('exclusionBuilderStatus');
        const newExclusionCtime = document.getElementById('newExclusionCtime');
        const newExclusionCrand = document.getElementById('newExclusionCrand');
        const participantListCard = document.getElementById('participantListCard');
        const participantTableBody = document.getElementById('participantTableBody');
        const countdownCard = document.getElementById('countdownCard');
        const signupCountdown = document.getElementById('signupCountdown');
        const giftCountdown = document.getElementById('giftCountdown');
        const countSignupDays = document.getElementById('countSignupDays');
        const countSignupHours = document.getElementById('countSignupHours');
        const countSignupMinutes = document.getElementById('countSignupMinutes');
        const countSignupSeconds = document.getElementById('countSignupSeconds');
        const countGiftDays = document.getElementById('countGiftDays');
        const countGiftHours = document.getElementById('countGiftHours');
        const countGiftMinutes = document.getElementById('countGiftMinutes');
        const countGiftSeconds = document.getElementById('countGiftSeconds');
        const countdownNote = document.getElementById('countdownNote');
        const countNumbers = document.querySelectorAll('.count-number');
        let countdownInterval = null;
        let currentEvent = null;
        let currentExclusionGroup = { ctime: '', crand: '' };
        let exclusionGroups = [];
        let participants = [];

        async function getJson(url) {
            const resp = await fetch(url, { credentials: 'include' });
            return resp.json();
        }

        async function postForm(url, params) {
            const formData = new FormData();
            Object.entries(params).forEach(([k, v]) => {
                if (v !== null && v !== undefined) {
                    formData.append(k, v);
                }
            });
            const resp = await fetch(url, { method: 'POST', credentials: 'include', body: formData });
            return resp.json();
        }

        function setCountdowns(signupDate, giftDate) {
            if (countdownInterval) {
                clearInterval(countdownInterval);
            }

            if (!signupDate && !giftDate) {
                countdownCard.style.display = 'none';
                return;
            }

            countdownCard.style.display = 'block';
            countdownNote.textContent = '';

            const updateBlock = (targetDate, elements) => {
                if (!targetDate) {
                    elements.container.style.display = 'none';
                    return false;
                }

                const now = new Date();
                const diff = targetDate - now;

                elements.container.style.display = 'flex';

                if (diff <= 0) {
                    elements.days.textContent = '00';
                    elements.hours.textContent = '00';
                    elements.minutes.textContent = '00';
                    elements.seconds.textContent = '00';
                    return true;
                }

                const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                const hours = Math.floor((diff / (1000 * 60 * 60)) % 24);
                const minutes = Math.floor((diff / (1000 * 60)) % 60);
                const seconds = Math.floor((diff / 1000) % 60);

                elements.days.textContent = String(days).padStart(2, '0');
                elements.hours.textContent = String(hours).padStart(2, '0');
                elements.minutes.textContent = String(minutes).padStart(2, '0');
                elements.seconds.textContent = String(seconds).padStart(2, '0');
                return false;
            };

            const updateCountdown = () => {
                const signupEnded = !signupDate ? false : updateBlock(signupDate, {
                    container: signupCountdown,
                    days: countSignupDays,
                    hours: countSignupHours,
                    minutes: countSignupMinutes,
                    seconds: countSignupSeconds
                });

                const giftEnded = !giftDate ? false : updateBlock(giftDate, {
                    container: giftCountdown,
                    days: countGiftDays,
                    hours: countGiftHours,
                    minutes: countGiftMinutes,
                    seconds: countGiftSeconds
                });

                countNumbers.forEach(el => {
                    el.classList.remove('pulse');
                    void el.offsetWidth;
                    el.classList.add('pulse');
                });

                if (signupEnded && giftEnded) {
                    countdownNote.textContent = 'Countdown finished—check with your host for the latest details.';
                } else if (signupEnded && giftDate) {
                    countdownNote.textContent = 'Signups are closed. Gift exchange countdown continues below.';
                } else {
                    countdownNote.textContent = '';
                }
            };

            updateCountdown();
            countdownInterval = setInterval(updateCountdown, 1000);
        }

        function renderExclusionOptions(groups) {
            exclusionSelect.innerHTML = '<option value="">No exclusion group</option>';
            groups.forEach(group => {
                const option = document.createElement('option');
                option.value = `${group.ctime}|${group.crand}`;
                option.textContent = group.group_name;
                exclusionSelect.appendChild(option);
            });
        }

        function getExclusionName(ctime, crand) {
            if (!ctime || !crand) return '';
            const match = exclusionGroups.find(group => group.ctime === ctime && String(group.crand) === String(crand));
            return match ? match.group_name : '';
        }

        function renderParticipants() {
            participantTableBody.innerHTML = '';

            if (!participants.length) {
                participantTableBody.innerHTML = '<tr class="table-light"><td colspan="3" class="text-center text-muted">No one has signed up yet.</td></tr>';
                participantListCard.style.display = 'block';
                return;
            }

            participants.forEach(person => {
                const row = document.createElement('tr');
                const exclusionName = person.exclusion_group_name || getExclusionName(person.exclusion_group_ctime, person.exclusion_group_crand);
                row.innerHTML = `
                    <td>${person.display_name || 'Unknown adventurer'}</td>
                    <td>${person.email || ''}</td>
                    <td>${exclusionName || 'None'}</td>
                `;
                participantTableBody.appendChild(row);
            });

            participantListCard.style.display = 'block';
        }

        function isCurrentUserParticipant() {
            const emailLower = (accountEmail || '').trim().toLowerCase();

            return participants.some(participant => {
                const participantEmail = (participant.email || '').trim().toLowerCase();
                const participantAccountId = participant.account_id ?? participant.account_crand ?? null;

                if (accountCrand !== null && participantAccountId !== null && String(participantAccountId) === String(accountCrand)) {
                    return true;
                }

                return !!emailLower && !!participantEmail && participantEmail === emailLower;
            });
        }

        function updateJoinVisibility(alreadyJoinedMessage = 'You are already registered for this exchange.') {
            if (isCurrentUserParticipant()) {
                joinRows.style.display = 'none';
                inviteStatus.textContent = alreadyJoinedMessage;
            } else {
                joinRows.style.display = '';
            }
        }

        function renderEvent(event) {
            eventDetailsCard.style.display = 'block';
            eventNameEl.textContent = event.name;
            const signup = new Date(event.signup_deadline + 'Z');
            const gift = new Date(event.gift_deadline + 'Z');
            eventDeadlinesEl.textContent = `Signup closes ${signup.toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' })} • Gifts due ${gift.toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' })}`;
            heroEventTitle.textContent = event.name;
            heroEventSubtitle.textContent = `Signup closes ${signup.toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' })} • Gifts due ${gift.toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' })}`;
            eventDescEl.textContent = event.description || 'No description provided.';
            joinRows.style.display = '';
            exclusionBuilderCard.style.display = 'block';
            setExclusionGroup('', '');
            exclusionGroups = event.exclusion_groups || [];
            renderExclusionOptions(exclusionGroups);
            participants = event.participants || [];
            renderParticipants();
            updateJoinVisibility();

            const now = new Date();
            const signupDate = signup > now ? signup : null;
            const giftDate = gift;
            setCountdowns(signupDate, giftDate);
        }

        function setExclusionGroup(ctime, crand) {
            currentExclusionGroup = {
                ctime: ctime ?? '',
                crand: crand ?? ''
            };
            participantExclusionCtime.value = currentExclusionGroup.ctime;
            participantExclusionCrand.value = currentExclusionGroup.crand;
            newExclusionCtime.value = currentExclusionGroup.ctime;
            newExclusionCrand.value = currentExclusionGroup.crand;
            const matchValue = currentExclusionGroup.ctime && currentExclusionGroup.crand
                ? `${currentExclusionGroup.ctime}|${currentExclusionGroup.crand}`
                : '';
            exclusionSelect.value = matchValue;
        }

        async function validateInvite(token) {
            inviteStatus.textContent = 'Checking invite...';
            eventDetailsCard.style.display = 'none';
            joinRows.style.display = 'none';
            exclusionBuilderCard.style.display = 'none';
            countdownCard.style.display = 'none';
            currentEvent = null;
            exclusionGroups = [];
            participants = [];
            participantListCard.style.display = 'none';
            try {
                const resp = await getJson(`/api/v1/secret-santa/validate-invite.php?invite_token=${encodeURIComponent(token)}`);
                inviteStatus.textContent = resp.message || '';
                if (resp.success) {
                    currentEvent = resp.data;
                    renderEvent(resp.data);
                }
            } catch (err) {
                console.error(err);
                inviteStatus.textContent = 'Unable to validate invite token right now.';
            }
        }

        if (inviteTokenFromUrl) {
            validateInvite(inviteTokenFromUrl);
        } else {
            inviteStatus.textContent = 'No invite token detected. Please use your invite link.';
        }

        joinForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!currentEvent) return;
            joinStatus.textContent = 'Submitting...';
            try {
                const displayName = (accountDisplayName || '').trim() || 'Secret Santa adventurer';
                const email = (accountEmail || '').trim();

                if (!email) {
                    joinStatus.textContent = 'Missing account email. Please update your profile and try again.';
                    return;
                }

                const resp = await postForm('/api/v1/secret-santa/join-event.php', {
                    invite_token: currentEvent.invite_token,
                    display_name: displayName,
                    email: email,
                    ...(participantExclusionCtime.value ? { exclusion_group_ctime: participantExclusionCtime.value } : {}),
                    ...(participantExclusionCrand.value ? { exclusion_group_crand: participantExclusionCrand.value } : {})
                });
                joinStatus.textContent = resp.message || '';
                if (resp.success && resp.data && resp.data.event) {
                    inviteStatus.textContent = 'You are in!';
                    const addedParticipant = resp.data.participant || {
                        display_name: displayName,
                        email: email,
                        exclusion_group_ctime: participantExclusionCtime.value,
                        exclusion_group_crand: participantExclusionCrand.value
                    };
                    addedParticipant.account_id = resp.data.participant?.account_id ?? accountCrand ?? null;
                    addedParticipant.exclusion_group_name = getExclusionName(addedParticipant.exclusion_group_ctime, addedParticipant.exclusion_group_crand);
                    participants.push(addedParticipant);
                    renderParticipants();
                    updateJoinVisibility('You are in!');
                }
            } catch (err) {
                console.error(err);
                joinStatus.textContent = 'Unable to join right now.';
            }
        });

        exclusionBuilder.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!currentEvent) return;
            exclusionBuilderStatus.textContent = 'Saving...';
            try {
                const payload = {
                    event_ctime: currentEvent.ctime,
                    event_crand: currentEvent.crand,
                    name: document.getElementById('newExclusionName').value
                };

                if (newExclusionCtime.value) {
                    payload.exclusion_group_ctime = newExclusionCtime.value;
                }

                if (newExclusionCrand.value) {
                    payload.exclusion_group_crand = newExclusionCrand.value;
                }

                const resp = await postForm('/api/v1/secret-santa/exclusion-group.php', payload);
                exclusionBuilderStatus.textContent = resp.message || '';
                if (resp.success && resp.data) {
                    setExclusionGroup(resp.data.ctime, resp.data.crand);
                    exclusionGroups.push({
                        ctime: resp.data.ctime,
                        crand: resp.data.crand,
                        name: document.getElementById('newExclusionName').value
                    });
                    renderExclusionOptions(exclusionGroups);
                    setExclusionGroup(resp.data.ctime, resp.data.crand);
                    renderParticipants();
                }
            } catch (err) {
                console.error(err);
                exclusionBuilderStatus.textContent = 'Could not save exclusion group.';
            }
        });

        exclusionSelect.addEventListener('change', (e) => {
            if (!e.target.value) {
                setExclusionGroup('', '');
                return;
            }
            const [ctime, crand] = e.target.value.split('|');
            setExclusionGroup(ctime, crand);
        });
    </script>
</body>

</html>
