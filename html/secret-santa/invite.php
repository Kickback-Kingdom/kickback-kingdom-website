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
    require("../php-components/ad-carousel.php");
    ?>

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
                                            <div class="small text-uppercase text-muted mb-0">Step 1</div>
                                            <h2 class="h5 mb-0">Validate your invite</h2>
                                        </div>
                                    </div>
                                    <form class="row g-2" id="inviteLookupForm">
                                        <div class="col-12">
                                            <label class="form-label small mb-1" for="inviteTokenInput">Invite token</label>
                                            <input class="form-control" id="inviteTokenInput" value="<?php echo htmlspecialchars($inviteToken); ?>" placeholder="e.g. 4fa31b9c8d7e8c52" required>
                                        </div>
                                        <div class="col-12">
                                            <button class="btn btn-success w-100" type="submit">Check my spot</button>
                                        </div>
                                    </form>
                                    <div id="inviteStatus" class="small text-muted mt-2"></div>
                                    <div class="border rounded-3 p-3 mt-3 countdown-card" id="countdownCard" style="display: none;">
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="rounded-circle bg-primary-subtle text-primary-emphasis d-inline-flex align-items-center justify-content-center me-2" style="width: 36px; height: 36px;">
                                                <i class="fa-solid fa-hourglass-half"></i>
                                            </span>
                                            <div>
                                                <div class="small text-uppercase text-muted mb-0">Countdown</div>
                                                <strong id="countdownLabel">Until gift exchange</strong>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between text-center">
                                            <div class="flex-fill">
                                                <div class="display-6 fw-bold text-primary count-number" id="countDays">--</div>
                                                <div class="small text-muted">Days</div>
                                            </div>
                                            <div class="flex-fill">
                                                <div class="display-6 fw-bold text-primary count-number" id="countHours">--</div>
                                                <div class="small text-muted">Hours</div>
                                            </div>
                                            <div class="flex-fill">
                                                <div class="display-6 fw-bold text-primary count-number" id="countMinutes">--</div>
                                                <div class="small text-muted">Minutes</div>
                                            </div>
                                            <div class="flex-fill">
                                                <div class="display-6 fw-bold text-primary count-number" id="countSeconds">--</div>
                                                <div class="small text-muted">Seconds</div>
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
                                        <h2 class="h5 mb-0">Your details</h2>
                                        <small class="text-muted">Share where to send your assignment.</small>
                                    </div>
                                </div>
                                <form id="joinForm" class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label" for="participantName">Display name</label>
                                        <input class="form-control" id="participantName" value="<?php echo htmlspecialchars($defaultDisplayName); ?>" placeholder="Ayla Starling" required>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label" for="participantEmail">Email</label>
                                        <input class="form-control" type="email" id="participantEmail" value="<?php echo htmlspecialchars($defaultEmail); ?>" placeholder="ayla@example.com" required>
                                    </div>
                                    <input type="hidden" id="participantExclusionCtime">
                                    <input type="hidden" id="participantExclusionCrand">
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
        const inviteLookupForm = document.getElementById('inviteLookupForm');
        const inviteTokenInput = document.getElementById('inviteTokenInput');
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
        const exclusionBuilderCard = document.getElementById('exclusionBuilderCard');
        const exclusionBuilder = document.getElementById('exclusionBuilder');
        const exclusionBuilderStatus = document.getElementById('exclusionBuilderStatus');
        const newExclusionCtime = document.getElementById('newExclusionCtime');
        const newExclusionCrand = document.getElementById('newExclusionCrand');
        const countdownCard = document.getElementById('countdownCard');
        const countdownLabel = document.getElementById('countdownLabel');
        const countDays = document.getElementById('countDays');
        const countHours = document.getElementById('countHours');
        const countMinutes = document.getElementById('countMinutes');
        const countSeconds = document.getElementById('countSeconds');
        const countdownNote = document.getElementById('countdownNote');
        const countNumbers = document.querySelectorAll('.count-number');
        let countdownInterval = null;
        let currentEvent = null;
        let currentExclusionGroup = { ctime: '', crand: '' };

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

        function setCountdown(targetDate, label, note) {
            if (countdownInterval) {
                clearInterval(countdownInterval);
            }

            if (!targetDate) {
                countdownCard.style.display = 'none';
                return;
            }

            countdownCard.style.display = 'block';
            countdownLabel.textContent = label;
            countdownNote.textContent = note || '';

            const updateCountdown = () => {
                const now = new Date();
                const diff = targetDate - now;

                if (diff <= 0) {
                    countDays.textContent = '00';
                    countHours.textContent = '00';
                    countMinutes.textContent = '00';
                    countSeconds.textContent = '00';
                    countdownNote.textContent = 'Countdown finished—check with your host for the latest details.';
                    return;
                }

                const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                const hours = Math.floor((diff / (1000 * 60 * 60)) % 24);
                const minutes = Math.floor((diff / (1000 * 60)) % 60);
                const seconds = Math.floor((diff / 1000) % 60);

                countDays.textContent = String(days).padStart(2, '0');
                countHours.textContent = String(hours).padStart(2, '0');
                countMinutes.textContent = String(minutes).padStart(2, '0');
                countSeconds.textContent = String(seconds).padStart(2, '0');

                countNumbers.forEach(el => {
                    el.classList.remove('pulse');
                    void el.offsetWidth;
                    el.classList.add('pulse');
                });
            };

            updateCountdown();
            countdownInterval = setInterval(updateCountdown, 1000);
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

            const now = new Date();
            const targetDate = signup > now ? signup : gift;
            const label = signup > now ? 'Until signups close' : 'Until gift exchange';
            const note = signup > now ? 'Join before the lock to get paired.' : 'If you are already in, check your match!';
            setCountdown(targetDate, label, note);
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
        }

        async function validateInvite(token) {
            inviteStatus.textContent = 'Checking invite...';
            eventDetailsCard.style.display = 'none';
            joinRows.style.display = 'none';
            exclusionBuilderCard.style.display = 'none';
            countdownCard.style.display = 'none';
            currentEvent = null;
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

        inviteLookupForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const token = inviteTokenInput.value.trim();
            if (!token) return;
            validateInvite(token);
        });

        if (inviteTokenInput.value) {
            validateInvite(inviteTokenInput.value);
        }

        joinForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!currentEvent) return;
            joinStatus.textContent = 'Submitting...';
            try {
                const resp = await postForm('/api/v1/secret-santa/join-event.php', {
                    invite_token: currentEvent.invite_token,
                    display_name: document.getElementById('participantName').value,
                    email: document.getElementById('participantEmail').value,
                    ...(participantExclusionCtime.value ? { exclusion_group_ctime: participantExclusionCtime.value } : {}),
                    ...(participantExclusionCrand.value ? { exclusion_group_crand: participantExclusionCrand.value } : {})
                });
                joinStatus.textContent = resp.message || '';
                if (resp.success && resp.data && resp.data.event) {
                    inviteStatus.textContent = 'You are in!';
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
                }
            } catch (err) {
                console.error(err);
                exclusionBuilderStatus.textContent = 'Could not save exclusion group.';
            }
        });
    </script>
</body>

</html>
