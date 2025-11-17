<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/../Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("../php-components/base-page-pull-active-account-info.php");

$inviteToken = $_GET['invite_token'] ?? '';
$pageTitle = "Join Secret Santa";
$pageDesc = "Join a Kickback Kingdom Secret Santa event.";
?>
<!DOCTYPE html>
<html lang="en">

<?php require("../php-components/base-page-head.php"); ?>

<body class="bg-body-secondary container p-0">
    <?php require("../php-components/base-page-components.php"); ?>
    <main class="container pt-3 bg-body" style="margin-bottom: 56px;">
        <div class="row">
            <div class="col-12 col-xl-9">
                <?php
                $activePageName = "Join Secret Santa";
                require("../php-components/base-page-breadcrumbs.php");
                ?>

                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="rounded-circle bg-success-subtle text-success-emphasis d-inline-flex align-items-center justify-content-center me-2" style="width: 48px; height: 48px;">
                                <i class="fa-solid fa-gifts fa-lg"></i>
                            </div>
                            <div>
                                <h1 class="h4 mb-0">Join Secret Santa</h1>
                                <small class="text-muted">Validate your invite token, then reserve your spot.</small>
                            </div>
                        </div>
                        <form class="row g-2 align-items-end" id="inviteLookupForm">
                            <div class="col-12 col-md-8">
                                <label class="form-label" for="inviteTokenInput">Invite token</label>
                                <input class="form-control" id="inviteTokenInput" value="<?php echo htmlspecialchars($inviteToken); ?>" placeholder="e.g. 4fa31b9c8d7e8c52" required>
                            </div>
                            <div class="col-12 col-md-4 d-grid">
                                <button class="btn btn-success" type="submit">Validate invite</button>
                            </div>
                        </form>
                        <div id="inviteStatus" class="small text-muted mt-2"></div>
                    </div>
                </div>

                <div class="card shadow-sm mb-3" id="eventDetailsCard" style="display:none;">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="rounded-circle bg-info-subtle text-info-emphasis d-inline-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                <i class="fa-solid fa-calendar-check"></i>
                            </div>
                            <div>
                                <h2 class="h5 mb-0" id="eventName"></h2>
                                <small class="text-muted" id="eventDeadlines"></small>
                            </div>
                        </div>
                        <p id="eventDescription" class="mb-0 text-secondary"></p>
                    </div>
                </div>

                <div class="card shadow-sm mb-3" id="joinFormCard" style="display:none;">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-primary-subtle text-primary-emphasis d-inline-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                <i class="fa-solid fa-user-plus"></i>
                            </div>
                            <div>
                                <h2 class="h5 mb-0">Your details</h2>
                                <small class="text-muted">Share where to send your assignment.</small>
                            </div>
                        </div>
                        <form id="joinForm" class="row g-2">
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="participantName">Display name</label>
                                <input class="form-control" id="participantName" placeholder="Ayla Starling" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="participantEmail">Email</label>
                                <input class="form-control" type="email" id="participantEmail" placeholder="ayla@example.com" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="participantExclusionCtime">Exclusion group ctime (optional)</label>
                                <input class="form-control" id="participantExclusionCtime" placeholder="20241130125959999">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="participantExclusionCrand">Exclusion group crand (optional)</label>
                                <input class="form-control" id="participantExclusionCrand" placeholder="1234">
                                <div class="form-text">Ask the organizer if you should join a group.</div>
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

                <div class="card shadow-sm" id="exclusionBuilderCard" style="display:none;">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-secondary-subtle text-secondary-emphasis d-inline-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                <i class="fa-solid fa-people-group"></i>
                            </div>
                            <div>
                                <h2 class="h5 mb-0">Request an exclusion group</h2>
                                <small class="text-muted">Organizers can create or rename groups from here.</small>
                            </div>
                        </div>
                        <form id="exclusionBuilder" class="row g-2">
                            <div class="col-12 col-lg-5">
                                <label class="form-label" for="newExclusionName">Group name</label>
                                <input class="form-control" id="newExclusionName" placeholder="Roommates">
                            </div>
                            <div class="col-6 col-lg-3">
                                <label class="form-label" for="newExclusionCtime">Existing ctime (optional)</label>
                                <input class="form-control" id="newExclusionCtime" placeholder="20241130125959999">
                            </div>
                            <div class="col-6 col-lg-2">
                                <label class="form-label" for="newExclusionCrand">Existing crand</label>
                                <input class="form-control" id="newExclusionCrand" placeholder="1234">
                            </div>
                            <div class="col-12 col-lg-2 d-grid">
                                <button class="btn btn-outline-secondary" type="submit">Save group</button>
                            </div>
                            <div class="col-12">
                                <div id="exclusionBuilderStatus" class="small text-muted"></div>
                            </div>
                        </form>
                    </div>
                </div>
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
        const joinFormCard = document.getElementById('joinFormCard');
        const joinForm = document.getElementById('joinForm');
        const joinStatus = document.getElementById('joinStatus');
        const exclusionBuilderCard = document.getElementById('exclusionBuilderCard');
        const exclusionBuilder = document.getElementById('exclusionBuilder');
        const exclusionBuilderStatus = document.getElementById('exclusionBuilderStatus');
        let currentEvent = null;

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

        function renderEvent(event) {
            eventDetailsCard.style.display = 'block';
            eventNameEl.textContent = event.name;
            const signup = new Date(event.signup_deadline + 'Z');
            const gift = new Date(event.gift_deadline + 'Z');
            eventDeadlinesEl.textContent = `Signup closes ${signup.toUTCString()} • Gifts due ${gift.toUTCString()}`;
            eventDescEl.textContent = event.description || 'No description provided.';
            joinFormCard.style.display = 'block';
            exclusionBuilderCard.style.display = 'block';
        }

        async function validateInvite(token) {
            inviteStatus.textContent = 'Checking invite...';
            eventDetailsCard.style.display = 'none';
            joinFormCard.style.display = 'none';
            exclusionBuilderCard.style.display = 'none';
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
                    exclusion_group_ctime: document.getElementById('participantExclusionCtime').value,
                    exclusion_group_crand: document.getElementById('participantExclusionCrand').value
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
                const resp = await postForm('/api/v1/secret-santa/exclusion-group.php', {
                    event_ctime: currentEvent.ctime,
                    event_crand: currentEvent.crand,
                    name: document.getElementById('newExclusionName').value,
                    exclusion_group_ctime: document.getElementById('newExclusionCtime').value,
                    exclusion_group_crand: document.getElementById('newExclusionCrand').value
                });
                exclusionBuilderStatus.textContent = resp.message || '';
                if (resp.success && resp.data) {
                    document.getElementById('participantExclusionCtime').value = resp.data.ctime ?? '';
                    document.getElementById('participantExclusionCrand').value = resp.data.crand ?? '';
                }
            } catch (err) {
                console.error(err);
                exclusionBuilderStatus.textContent = 'Could not save exclusion group.';
            }
        });
    </script>
</body>

</html>
