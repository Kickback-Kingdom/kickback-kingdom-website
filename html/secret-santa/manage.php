<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("../php-components/base-page-pull-active-account-info.php");

$pageTitle = "Secret Santa Owner Dashboard";
$pageDesc = "Manage Secret Santa signups, exclusion groups, and assignments.";

$prefillInvite = $_GET['invite_token'] ?? '';
$prefillCtime = $_GET['event_ctime'] ?? '';
$prefillCrand = $_GET['event_crand'] ?? '';
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
    <main class="container pt-3 bg-body" style="margin-bottom: 56px;">
        <div class="row">
            <div class="col-12 col-xl-9">
                <?php
                $activePageName = "Secret Santa Owner Dashboard";
                require("../php-components/base-page-breadcrumbs.php");
                ?>
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="rounded-circle bg-primary-subtle text-primary-emphasis d-inline-flex align-items-center justify-content-center me-2" style="width: 48px; height: 48px;">
                                <i class="fa-solid fa-sleigh fa-lg"></i>
                            </div>
                            <div>
                                <h1 class="h4 mb-0">Your Secret Santa events</h1>
                                <small class="text-muted">Select an event you own to load its details below.</small>
                            </div>
                        </div>
                        <div id="ownerEventsStatus" class="small text-muted mb-2">Loading your events...</div>
                        <div id="ownerEventsList" class="list-group list-group-flush"></div>
                    </div>
                </div>

                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-info-subtle text-info-emphasis d-inline-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                <i class="fa-solid fa-clipboard-list"></i>
                            </div>
                            <div>
                                <h2 class="h5 mb-0">Selected event</h2>
                                <small class="text-muted">Fields below update when you choose an event.</small>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label" for="eventCtime">Event ctime</label>
                                <input class="form-control" id="eventCtime" value="<?php echo htmlspecialchars($prefillCtime); ?>" placeholder="20241130125959999">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label" for="eventCrand">Event crand</label>
                                <input class="form-control" id="eventCrand" value="<?php echo htmlspecialchars($prefillCrand); ?>" placeholder="123456789">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label" for="eventInviteToken">Invite token</label>
                                <input class="form-control" id="eventInviteToken" value="<?php echo htmlspecialchars($prefillInvite); ?>" placeholder="8fd0b1cc1ca223ff">
                                <div class="form-text">Share with participants for joining.</div>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-lg-6">
                                <div class="text-uppercase small text-muted">Event name</div>
                                <div id="selectedEventName" class="fw-semibold">No event selected yet.</div>
                                <div id="selectedEventDescription" class="text-muted"></div>
                            </div>
                            <div class="col-6 col-lg-3">
                                <div class="text-uppercase small text-muted">Signups close</div>
                                <div id="selectedSignupDeadline" class="fw-semibold text-nowrap">—</div>
                            </div>
                            <div class="col-6 col-lg-3">
                                <div class="text-uppercase small text-muted">Gift deadline</div>
                                <div id="selectedGiftDeadline" class="fw-semibold text-nowrap">—</div>
                            </div>
                            <div class="col-12 col-lg-6">
                                <div class="text-uppercase small text-muted">Invite token</div>
                                <div id="selectedInviteToken" class="fw-semibold">—</div>
                            </div>
                            <div class="col-12 col-lg-6">
                                <div class="text-uppercase small text-muted">Participation</div>
                                <div id="selectedEventCounts" class="fw-semibold">—</div>
                            </div>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-12 col-lg-6">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <div class="fw-semibold">Participants</div>
                                    <span id="participantsCount" class="badge text-bg-light"></span>
                                </div>
                                <div id="participantsList" class="small text-muted">Select an event to view who has joined.</div>
                            </div>
                            <div class="col-12 col-lg-6">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <div class="fw-semibold">Exclusion groups</div>
                                    <span id="exclusionCount" class="badge text-bg-light"></span>
                                </div>
                                <div id="exclusionList" class="small text-muted">Select an event to view exclusion groups.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-secondary-subtle text-secondary-emphasis d-inline-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                <i class="fa-solid fa-people-arrows"></i>
                            </div>
                            <div>
                                <h2 class="h5 mb-0">Exclusion groups</h2>
                                <small class="text-muted">Keep people in the same household or team from gifting each other.</small>
                            </div>
                        </div>

                        <form id="exclusionGroupForm" class="row g-2 align-items-end">
                            <div class="col-12 col-lg-5">
                                <label class="form-label" for="exclusionName">Group name</label>
                                <input class="form-control" id="exclusionName" placeholder="QA Team" required>
                            </div>
                            <div class="col-6 col-lg-3">
                                <label class="form-label" for="exclusionCtime">Existing ctime (optional)</label>
                                <input class="form-control" id="exclusionCtime" placeholder="20241130125959999">
                            </div>
                            <div class="col-6 col-lg-2">
                                <label class="form-label" for="exclusionCrand">Existing crand</label>
                                <input class="form-control" id="exclusionCrand" placeholder="1234">
                            </div>
                            <div class="col-12 col-lg-2 d-grid">
                                <button class="btn btn-secondary" type="submit">Save group</button>
                            </div>
                            <div class="col-12">
                                <div id="exclusionStatus" class="small text-muted"></div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-success-subtle text-success-emphasis d-inline-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                <i class="fa-solid fa-envelope-open-text"></i>
                            </div>
                            <div>
                                <h2 class="h5 mb-0">Assignments</h2>
                                <small class="text-muted">Generate pairs after signups close and send them out.</small>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <button class="btn btn-primary" id="generatePairsBtn" type="button">Generate pairs</button>
                            <button class="btn btn-outline-primary" id="emailAssignmentsBtn" type="button">Email assignments</button>
                            <div id="assignmentStatus" class="small text-muted align-self-center"></div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle" id="assignmentTable">
                                <thead>
                                    <tr>
                                        <th>Giver</th>
                                        <th>Receiver</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="table-light">
                                        <td colspan="2" class="text-center text-muted">Assignments will appear here after generation.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-warning-subtle text-warning-emphasis d-inline-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                <i class="fa-solid fa-user-pen"></i>
                            </div>
                            <div>
                                <h2 class="h5 mb-0">Participation adjustments</h2>
                                <small class="text-muted">Re-submit a participant using the invite token if they need help.</small>
                            </div>
                        </div>
                        <form id="ownerJoinForm" class="row g-2">
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="ownerDisplayName">Display name</label>
                                <input class="form-control" id="ownerDisplayName" placeholder="Robin the Red" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="ownerEmail">Email</label>
                                <input class="form-control" type="email" id="ownerEmail" placeholder="robin@example.com" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="ownerExclusionCtime">Exclusion group ctime (optional)</label>
                                <input class="form-control" id="ownerExclusionCtime" placeholder="20241130125959999">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="ownerExclusionCrand">Exclusion group crand (optional)</label>
                                <input class="form-control" id="ownerExclusionCrand" placeholder="1234">
                            </div>
                            <div class="col-12">
                                <div class="d-flex align-items-center gap-2">
                                    <button class="btn btn-outline-secondary" type="submit">Submit join</button>
                                    <div id="ownerJoinStatus" class="small text-muted"></div>
                                </div>
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
        const ownerEventsList = document.getElementById('ownerEventsList');
        const ownerEventsStatus = document.getElementById('ownerEventsStatus');
        const eventCtimeInput = document.getElementById('eventCtime');
        const eventCrandInput = document.getElementById('eventCrand');
        const eventInviteInput = document.getElementById('eventInviteToken');
        const selectedEventName = document.getElementById('selectedEventName');
        const selectedEventDescription = document.getElementById('selectedEventDescription');
        const selectedSignupDeadline = document.getElementById('selectedSignupDeadline');
        const selectedGiftDeadline = document.getElementById('selectedGiftDeadline');
        const selectedInviteToken = document.getElementById('selectedInviteToken');
        const selectedEventCounts = document.getElementById('selectedEventCounts');
        const participantsList = document.getElementById('participantsList');
        const participantsCount = document.getElementById('participantsCount');
        const exclusionList = document.getElementById('exclusionList');
        const exclusionCount = document.getElementById('exclusionCount');
        let activeEventKey = null;

        const exclusionForm = document.getElementById('exclusionGroupForm');
        const exclusionStatus = document.getElementById('exclusionStatus');
        const generatePairsBtn = document.getElementById('generatePairsBtn');
        const emailAssignmentsBtn = document.getElementById('emailAssignmentsBtn');
        const assignmentTable = document.getElementById('assignmentTable').querySelector('tbody');
        const assignmentStatus = document.getElementById('assignmentStatus');
        const ownerJoinForm = document.getElementById('ownerJoinForm');
        const ownerJoinStatus = document.getElementById('ownerJoinStatus');

        function getEventFields() {
            return {
                ctime: eventCtimeInput.value.trim(),
                crand: eventCrandInput.value.trim(),
                invite: eventInviteInput.value.trim()
            };
        }

        function setEventFields(evt) {
            eventCtimeInput.value = evt.ctime ?? '';
            eventCrandInput.value = evt.crand ?? '';
            eventInviteInput.value = evt.invite_token ?? '';
        }

        function setEventSummary(evt) {
            selectedEventName.textContent = evt.name || 'No event selected yet.';
            selectedEventDescription.textContent = evt.description || '';
            selectedSignupDeadline.textContent = evt.signup_deadline || '—';
            selectedGiftDeadline.textContent = evt.gift_deadline || '—';
            selectedInviteToken.textContent = evt.invite_token || '—';
            selectedEventCounts.textContent = `${evt.participant_count ?? 0} participants • ${evt.exclusion_group_count ?? 0} exclusion groups`;
            participantsList.textContent = 'Loading participants...';
            exclusionList.textContent = 'Loading exclusion groups...';
            participantsCount.textContent = '';
            exclusionCount.textContent = '';
        }

        function renderParticipants(participants) {
            if (!participants || !participants.length) {
                participantsList.textContent = 'No one has joined this event yet.';
                participantsCount.textContent = '0';
                return;
            }

            const list = document.createElement('ul');
            list.className = 'list-group list-group-flush small';

            participants.forEach((person) => {
                const item = document.createElement('li');
                item.className = 'list-group-item px-0';
                const group = person.exclusion_group_name ? ` • ${person.exclusion_group_name}` : '';
                item.textContent = `${person.display_name} (${person.email})${group}`;
                list.appendChild(item);
            });

            participantsList.innerHTML = '';
            participantsList.appendChild(list);
            participantsCount.textContent = participants.length;
        }

        function renderExclusionGroups(groups) {
            if (!groups || !groups.length) {
                exclusionList.textContent = 'No exclusion groups yet.';
                exclusionCount.textContent = '0';
                return;
            }

            const list = document.createElement('ul');
            list.className = 'list-group list-group-flush small';

            groups.forEach((group) => {
                const item = document.createElement('li');
                item.className = 'list-group-item px-0';
                item.textContent = `${group.group_name} (${group.ctime}:${group.crand})`;
                list.appendChild(item);
            });

            exclusionList.innerHTML = '';
            exclusionList.appendChild(list);
            exclusionCount.textContent = groups.length;
        }

        function highlightSelected(listItem) {
            ownerEventsList.querySelectorAll('.list-group-item').forEach((el) => {
                el.classList.toggle('active', el === listItem);
            });
        }

        async function loadEventDetails(evt, listItem) {
            activeEventKey = `${evt.ctime}:${evt.crand}`;
            if (listItem) {
                highlightSelected(listItem);
            }

            setEventFields(evt);
            setEventSummary(evt);

            try {
                const resp = await fetch(`/api/v1/secret-santa/validate-invite.php?invite_token=${encodeURIComponent(evt.invite_token)}`, { credentials: 'include' });
                const data = await resp.json();
                if (!data.success) {
                    participantsList.textContent = data.message || 'Unable to load participants.';
                    exclusionList.textContent = data.message || 'Unable to load exclusion groups.';
                    return;
                }

                const eventData = data.data || {};
                renderParticipants(eventData.participants || []);
                renderExclusionGroups(eventData.exclusion_groups || []);
                selectedEventCounts.textContent = `${(eventData.participants || []).length} participants • ${(eventData.exclusion_groups || []).length} exclusion groups`;
            } catch (err) {
                console.error(err);
                participantsList.textContent = 'Unable to load participants.';
                exclusionList.textContent = 'Unable to load exclusion groups.';
            }
        }

        function renderEventList(events) {
            ownerEventsList.innerHTML = '';

            if (!events || !events.length) {
                ownerEventsStatus.textContent = 'No Secret Santa events yet. Create one to get started!';
                return;
            }

            ownerEventsStatus.textContent = 'Click an event to load its data below.';

            events.forEach((evt, idx) => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-start';
                item.innerHTML = `
                    <div class="me-2">
                        <div class="fw-semibold">${evt.name}</div>
                        <div class="text-muted small">Invite: ${evt.invite_token}</div>
                        <div class="text-muted small">Signups: ${evt.signup_deadline} · Gifts: ${evt.gift_deadline}</div>
                    </div>
                    <span class="badge text-bg-secondary align-self-center">${evt.participant_count ?? 0} joined</span>
                `;

                item.addEventListener('click', () => loadEventDetails(evt, item));
                ownerEventsList.appendChild(item);

                if (idx === 0 && !activeEventKey) {
                    loadEventDetails(evt, item);
                }
            });
        }

        async function fetchOwnerEvents() {
            ownerEventsStatus.textContent = 'Loading your events...';
            try {
                const resp = await fetch('/api/v1/secret-santa/list-owner-events.php', { credentials: 'include' });
                const data = await resp.json();
                if (!data.success) {
                    ownerEventsStatus.textContent = data.message || 'Unable to load events.';
                    return;
                }

                renderEventList(data.data || []);
            } catch (err) {
                console.error(err);
                ownerEventsStatus.textContent = 'Unable to load events right now.';
            }
        }

        fetchOwnerEvents();

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

        function renderAssignments(pairs) {
            assignmentTable.innerHTML = '';
            if (!pairs || !pairs.length) {
                assignmentTable.innerHTML = '<tr class="table-light"><td colspan="2" class="text-center text-muted">No assignments returned yet.</td></tr>';
                return;
            }
            pairs.forEach((pair) => {
                const row = document.createElement('tr');
                const giver = `${pair.giver.display_name} (${pair.giver.email})`;
                const receiver = `${pair.receiver.display_name} (${pair.receiver.email})`;
                row.innerHTML = `<td>${giver}</td><td>${receiver}</td>`;
                assignmentTable.appendChild(row);
            });
        }

        exclusionForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            exclusionStatus.textContent = 'Saving group...';
            const evt = getEventFields();
            try {
                const resp = await postForm('/api/v1/secret-santa/exclusion-group.php', {
                    event_ctime: evt.ctime,
                    event_crand: evt.crand,
                    name: document.getElementById('exclusionName').value,
                    exclusion_group_ctime: document.getElementById('exclusionCtime').value,
                    exclusion_group_crand: document.getElementById('exclusionCrand').value
                });
                exclusionStatus.textContent = resp.message || '';
                if (resp.success && resp.data) {
                    document.getElementById('exclusionCtime').value = resp.data.ctime ?? '';
                    document.getElementById('exclusionCrand').value = resp.data.crand ?? '';
                }
            } catch (err) {
                console.error(err);
                exclusionStatus.textContent = 'Unable to save exclusion group.';
            }
        });

        generatePairsBtn.addEventListener('click', async () => {
            assignmentStatus.textContent = 'Generating pairs...';
            const evt = getEventFields();
            try {
                const resp = await postForm('/api/v1/secret-santa/generate-pairs.php', {
                    event_ctime: evt.ctime,
                    event_crand: evt.crand
                });
                assignmentStatus.textContent = resp.message || '';
                if (resp.success) {
                    renderAssignments(resp.data || []);
                }
            } catch (err) {
                console.error(err);
                assignmentStatus.textContent = 'Failed to generate pairs.';
            }
        });

        emailAssignmentsBtn.addEventListener('click', async () => {
            assignmentStatus.textContent = 'Sending assignment emails...';
            const evt = getEventFields();
            try {
                const resp = await postForm('/api/v1/secret-santa/email-assignments.php', {
                    event_ctime: evt.ctime,
                    event_crand: evt.crand
                });
                assignmentStatus.textContent = resp.message || '';
            } catch (err) {
                console.error(err);
                assignmentStatus.textContent = 'Failed to send assignment emails.';
            }
        });

        ownerJoinForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            ownerJoinStatus.textContent = 'Submitting...';
            const evt = getEventFields();
            try {
                const resp = await postForm('/api/v1/secret-santa/join-event.php', {
                    invite_token: evt.invite,
                    display_name: document.getElementById('ownerDisplayName').value,
                    email: document.getElementById('ownerEmail').value,
                    exclusion_group_ctime: document.getElementById('ownerExclusionCtime').value,
                    exclusion_group_crand: document.getElementById('ownerExclusionCrand').value
                });
                ownerJoinStatus.textContent = resp.message || '';
            } catch (err) {
                console.error(err);
                ownerJoinStatus.textContent = 'Join request failed.';
            }
        });
    </script>
</body>

</html>
