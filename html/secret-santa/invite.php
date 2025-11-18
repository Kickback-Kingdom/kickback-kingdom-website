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
        .count-number {
            transition: transform 0.2s ease;
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
                <section class="position-relative overflow-hidden rounded-4 mb-4 shadow-lg border-0 text-light"
                    style="
                        background:
                            radial-gradient(circle at 15% 10%, rgba(255,255,255,0.18), transparent 35%),
                            radial-gradient(circle at 85% 15%, rgba(255,193,7,0.25), transparent 40%),
                            linear-gradient(135deg, #0d6efd 0%, #6f42c1 45%, #842029 100%);
                    ">
                    <div class="row g-0 align-items-center p-4 p-md-5">
                        <div class="col-lg-7">
                            <div class="d-inline-flex align-items-center mb-2 small text-light text-opacity-75">
                                <span class="badge rounded-pill bg-light text-dark me-2">
                                    <i class="fa-solid fa-sleigh me-1"></i> Invite only
                                </span>
                                <span>Bring your code, join the merriment.</span>
                            </div>
                            <h1 class="display-5 fw-bold mb-3">Join your Secret Santa adventure</h1>
                            <p class="lead mb-4 text-light text-opacity-90">
                                Enter the invite token from your host, preview the rules, and watch the countdown to gift day.
                                Everything you need to hop in is right here.
                            </p>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="d-flex align-items-center gap-2 text-light text-opacity-85">
                                    <i class="fa-solid fa-shield-heart"></i>
                                    <span class="small">Private tokens keep your exchange safe.</span>
                                </div>
                                <div class="d-flex align-items-center gap-2 text-light text-opacity-85">
                                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                                    <span class="small">Pairing rules and budget at a glance.</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-5 mt-4 mt-lg-0">
                            <div class="card bg-white bg-opacity-95 border-0 shadow-lg rounded-3 text-start">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <span class="rounded-circle bg-success-subtle text-success-emphasis d-inline-flex align-items-center justify-content-center me-2" style="width: 44px; height: 44px;">
                                            <i class="fa-solid fa-gifts"></i>
                                        </span>
                                        <div>
                                            <div class="small text-uppercase text-muted mb-0">Invite confirmed</div>
                                            <h2 class="h5 mb-0">You're ready to join</h2>
                                        </div>
                                    </div>
                                    <p class="small text-muted mb-3">
                                        You already followed a valid invite link. We'll load the event details automatically and let you hop in.
                                    </p>
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <span class="badge bg-success-subtle text-success-emphasis">Invite token locked</span>
                                        <code class="text-muted" id="inviteTokenDisplay"><?php echo htmlspecialchars($inviteToken ?: 'Not provided'); ?></code>
                                    </div>
                                    <div id="inviteStatus" class="small text-muted mb-3"></div>
                                    <div class="border rounded-3 p-3 mt-3 countdown-card" id="countdownCard" style="display: none;">
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="rounded-circle bg-primary-subtle text-primary-emphasis d-inline-flex align-items-center justify-content-center me-2" style="width: 36px; height: 36px;">
                                                <i class="fa-solid fa-hourglass-half"></i>
                                            </span>
                                            <div>
                                                <div class="small text-uppercase text-muted mb-0">Countdowns</div>
                                                <strong>Key dates for this exchange</strong>
                                            </div>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <div class="d-flex justify-content-between text-center border rounded-3 p-3 h-100" id="signupCountdown" style="display:none;">
                                                    <div class="flex-fill">
                                                        <div class="small text-uppercase text-muted">Until signups close</div>
                                                        <div class="display-6 fw-bold text-primary count-number" id="countSignupDays">--</div>
                                                        <div class="small text-muted">Days</div>
                                                    </div>
                                                    <div class="flex-fill">
                                                        <div class="small text-uppercase text-muted">&nbsp;</div>
                                                        <div class="display-6 fw-bold text-primary count-number" id="countSignupHours">--</div>
                                                        <div class="small text-muted">Hours</div>
                                                    </div>
                                                    <div class="flex-fill">
                                                        <div class="small text-uppercase text-muted">&nbsp;</div>
                                                        <div class="display-6 fw-bold text-primary count-number" id="countSignupMinutes">--</div>
                                                        <div class="small text-muted">Minutes</div>
                                                    </div>
                                                    <div class="flex-fill">
                                                        <div class="small text-uppercase text-muted">&nbsp;</div>
                                                        <div class="display-6 fw-bold text-primary count-number" id="countSignupSeconds">--</div>
                                                        <div class="small text-muted">Seconds</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="d-flex justify-content-between text-center border rounded-3 p-3 h-100" id="giftCountdown" style="display:none;">
                                                    <div class="flex-fill">
                                                        <div class="small text-uppercase text-muted">Until gift exchange</div>
                                                        <div class="display-6 fw-bold text-primary count-number" id="countGiftDays">--</div>
                                                        <div class="small text-muted">Days</div>
                                                    </div>
                                                    <div class="flex-fill">
                                                        <div class="small text-uppercase text-muted">&nbsp;</div>
                                                        <div class="display-6 fw-bold text-primary count-number" id="countGiftHours">--</div>
                                                        <div class="small text-muted">Hours</div>
                                                    </div>
                                                    <div class="flex-fill">
                                                        <div class="small text-uppercase text-muted">&nbsp;</div>
                                                        <div class="display-6 fw-bold text-primary count-number" id="countGiftMinutes">--</div>
                                                        <div class="small text-muted">Minutes</div>
                                                    </div>
                                                    <div class="flex-fill">
                                                        <div class="small text-uppercase text-muted">&nbsp;</div>
                                                        <div class="display-6 fw-bold text-primary count-number" id="countGiftSeconds">--</div>
                                                        <div class="small text-muted">Seconds</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="small text-muted mt-2 mb-0" id="countdownNote"></p>
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
                <div class="row g-4 mb-4" id="joinRows" style="display:none;">
                    <div class="col-lg-7">
                        <div class="card shadow-sm border-0 h-100" id="joinFormCard">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="rounded-circle bg-primary-subtle text-primary-emphasis d-inline-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                        <i class="fa-solid fa-user-plus"></i>
                                    </div>
                                    <div>
                                        <h2 class="h5 mb-0">Join this exchange</h2>
                                        <small class="text-muted">We'll use the account tied to your invite link.</small>
                                    </div>
                                </div>
                                <form id="joinForm" class="row g-3">
                                    <div class="col-12">
                                        <div class="p-3 bg-body-secondary rounded-3">
                                            <div class="small text-uppercase text-muted mb-2">Joining as</div>
                                            <div class="row g-3">
                                                <div class="col-12 col-md-6">
                                                    <div class="form-label mb-1">Display name</div>
                                                    <div class="fw-semibold" id="participantDisplayNameText">
                                                        <?php echo htmlspecialchars($defaultDisplayName) ?: 'Secret Santa adventurer'; ?>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-md-6">
                                                    <div class="form-label mb-1">Email</div>
                                                    <div class="fw-semibold" id="participantEmailText">
                                                        <?php echo htmlspecialchars($defaultEmail) ?: 'Update your account email to join'; ?>
                                                    </div>
                                                    <div class="form-text">We'll use this email to confirm your signup.</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" id="participantExclusionCtime">
                                    <input type="hidden" id="participantExclusionCrand">
                                    <div class="col-12">
                                        <label class="form-label" for="exclusionSelect">Exclusion group</label>
                                        <select class="form-select" id="exclusionSelect">
                                            <option value="">No exclusion group</option>
                                        </select>
                                        <div class="form-text">Optional: keep yourself out of a specific pairing pool.</div>
                                    </div>
                                    <div class="col-12">
                                        <div class="d-flex align-items-center gap-2">
                                            <button class="btn btn-success" type="submit">Join event</button>
                                            <div id="joinStatus" class="small text-muted"></div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="card shadow-sm border-0 h-100" id="exclusionBuilderCard">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="rounded-circle bg-secondary-subtle text-secondary-emphasis d-inline-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                        <i class="fa-solid fa-people-group"></i>
                                    </div>
                                    <div>
                                        <h2 class="h6 mb-0">Request an exclusion group</h2>
                                        <small class="text-muted">Keep couples, roommates, or teams apart.</small>
                                    </div>
                                </div>
                                <form id="exclusionBuilder" class="row g-2">
                                    <div class="col-12 col-lg-8">
                                        <label class="form-label" for="newExclusionName">Group name</label>
                                        <input class="form-control" id="newExclusionName" placeholder="Roommates">
                                    </div>
                                    <input type="hidden" id="newExclusionCtime">
                                    <input type="hidden" id="newExclusionCrand">
                                    <div class="col-12 col-lg-4 d-grid">
                                        <label class="form-label opacity-0">Save</label>
                                        <button class="btn btn-outline-secondary" type="submit">Save group</button>
                                    </div>
                                    <div class="col-12">
                                        <div id="exclusionBuilderStatus" class="small text-muted"></div>
                                    </div>
                                </form>
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
        const inviteStatus = document.getElementById('inviteStatus');
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

        function renderEvent(event) {
            eventDetailsCard.style.display = 'block';
            eventNameEl.textContent = event.name;
            const signup = new Date(event.signup_deadline + 'Z');
            const gift = new Date(event.gift_deadline + 'Z');
            eventDeadlinesEl.textContent = `Signup closes ${signup.toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' })} • Gifts due ${gift.toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' })}`;
            eventDescEl.textContent = event.description || 'No description provided.';
            joinRows.style.display = '';
            exclusionBuilderCard.style.display = 'block';
            setExclusionGroup('', '');
            exclusionGroups = event.exclusion_groups || [];
            renderExclusionOptions(exclusionGroups);
            participants = event.participants || [];
            renderParticipants();

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
                    addedParticipant.exclusion_group_name = getExclusionName(addedParticipant.exclusion_group_ctime, addedParticipant.exclusion_group_crand);
                    participants.push(addedParticipant);
                    renderParticipants();
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
