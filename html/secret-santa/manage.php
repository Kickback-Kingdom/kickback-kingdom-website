<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("../php-components/base-page-pull-active-account-info.php");

$pageTitle = "Secret Santa Owner Dashboard";
$pageDesc = "Manage Secret Santa signups, exclusion groups, and assignments.";

$prefillInvite = $_GET['invite_token'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<?php require("../php-components/base-page-head.php"); ?>

<body class="bg-body-secondary container p-0">
    <?php
    require("../php-components/base-page-components.php");
    ?>
    <style>
        .event-hero {
            position: relative;
            background: radial-gradient(circle at 20% 20%, rgba(13, 110, 253, 0.16), rgba(13, 202, 240, 0.08)),
                linear-gradient(135deg, #f8fbff, #ffffff);
            border: 1px solid rgba(13, 110, 253, 0.14);
            border-radius: 1.25rem;
            overflow: hidden;
            box-shadow: 0 1.25rem 2.5rem rgba(13, 110, 253, 0.08);
        }

        .event-hero::after {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 80% 0%, rgba(32, 201, 151, 0.08), transparent 45%);
            pointer-events: none;
        }

        .event-hero .icon-circle {
            width: 72px;
            height: 72px;
            box-shadow: 0 0.75rem 1.5rem rgba(13, 110, 253, 0.25);
        }

        .event-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.45rem 0.85rem;
            border-radius: 999px;
            background: #f8f9fb;
            border: 1px solid var(--bs-border-color-translucent);
            font-weight: 600;
            color: var(--bs-body-color);
        }

        .owner-event-card {
            border: 1px solid var(--bs-border-color-translucent);
            border-radius: 1rem;
            transition: transform 140ms ease, box-shadow 140ms ease, border-color 140ms ease;
            background: linear-gradient(180deg, #ffffff, #f8fafc);
            box-shadow: 0 0.35rem 1rem rgba(0, 0, 0, 0.04);
        }

        .owner-event-card:hover,
        .owner-event-card:focus-visible {
            transform: translateY(-3px);
            box-shadow: 0 0.75rem 1.5rem rgba(13, 110, 253, 0.14);
            border-color: var(--bs-primary);
            outline: none;
        }

        .owner-event-card.active {
            border-color: var(--bs-primary);
            box-shadow: 0 0.85rem 1.7rem rgba(13, 110, 253, 0.16);
            background: linear-gradient(180deg, rgba(13, 110, 253, 0.05), #ffffff);
        }

        .event-meta > span {
            border-radius: 999px;
        }
    </style>
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
                <div class="card shadow-sm border-0 mb-3 event-hero">
                    <div class="card-body d-lg-flex align-items-center justify-content-between gap-4 position-relative">
                        <div class="d-flex align-items-start gap-3 flex-grow-1">
                            <div class="rounded-circle bg-primary text-light d-inline-flex align-items-center justify-content-center icon-circle flex-shrink-0">
                                <i class="fa-solid fa-gifts fa-lg"></i>
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                                    <h1 class="h4 mb-0">Secret Santa host control room</h1>
                                    <span class="badge text-bg-primary-subtle text-primary-emphasis">Plan · Protect · Share</span>
                                </div>
                                <p class="mb-3 text-muted">Guide every event from signups to gift day with clearer lists, quicker edits, and confident messaging.</p>
                                <div class="d-flex flex-wrap gap-2 text-muted">
                                    <span class="event-pill"><i class="fa-solid fa-list-check text-primary"></i>Track signups</span>
                                    <span class="event-pill"><i class="fa-solid fa-people-arrows text-success"></i>Lock exclusions</span>
                                    <span class="event-pill"><i class="fa-solid fa-paper-plane text-info"></i>Send assignments</span>
                                </div>
                            </div>
                        </div>
                        <div class="text-end small text-muted d-none d-lg-block">
                            <div class="fw-semibold text-primary text-uppercase">Quick start</div>
                            <div>Pick an event below to sync participants, exclusions, and assignments in one view.</div>
                        </div>
                    </div>
                </div>
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-primary-subtle text-primary-emphasis d-inline-flex align-items-center justify-content-center me-2" style="width: 48px; height: 48px;">
                                    <i class="fa-solid fa-sleigh fa-lg"></i>
                                </div>
                                <div>
                                    <h2 class="h5 mb-0">Your Secret Santa events</h2>
                                    <small class="text-muted">Choose an event to open participant, exclusion, and assignment tools.</small>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="badge text-bg-primary-subtle text-primary-emphasis">Step 1 · Choose an event</span>
                                <span class="badge text-bg-light">Host dashboard</span>
                            </div>
                        </div>
                        <div class="alert alert-primary-subtle text-primary-emphasis small d-flex align-items-center gap-2" role="status">
                            <i class="fa-solid fa-wand-magic-sparkles"></i>
                            <span>We load the first event automatically or any invite token included in your link.</span>
                        </div>
                        <div id="ownerEventsStatus" class="small text-muted mb-2">Loading your events...</div>
                        <div id="ownerEventsList" class="list-group list-group-flush d-flex flex-column gap-2"></div>
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
                                <small class="text-muted">Details below update when you choose an event.</small>
                            </div>
                        </div>
                        <p class="text-muted small mb-3">Use the list above to pick an event. Your selection keeps the participation, exclusion, and assignment tools in sync.</p>
                        <div class="row g-3">
                            <div class="col-12 col-lg-8">
                                <div class="p-3 bg-light rounded-3 h-100">
                                    <div class="text-uppercase small text-muted">Event name</div>
                                    <div id="selectedEventName" class="fw-semibold">No event selected yet.</div>
                                    <div id="selectedEventDescription" class="text-muted mb-0"></div>
                                </div>
                            </div>
                            <div class="col-6 col-lg-4">
                                <div class="p-3 border rounded-3 h-100 bg-body-tertiary">
                                    <div class="text-uppercase small text-muted">Invite token</div>
                                    <div id="selectedInviteToken" class="fw-semibold">—</div>
                                    <div class="text-muted small">Share with teammates to let them join.</div>
                                </div>
                            </div>
                            <div class="col-6 col-lg-3">
                                <div class="p-3 border rounded-3 h-100">
                                    <div class="text-uppercase small text-muted">Signups close</div>
                                    <div id="selectedSignupDeadline" class="fw-semibold text-nowrap">—</div>
                                </div>
                            </div>
                            <div class="col-6 col-lg-3">
                                <div class="p-3 border rounded-3 h-100">
                                    <div class="text-uppercase small text-muted">Gift deadline</div>
                                    <div id="selectedGiftDeadline" class="fw-semibold text-nowrap">—</div>
                                </div>
                            </div>
                            <div class="col-12 col-lg-6">
                                <div class="p-3 bg-success-subtle rounded-3 h-100">
                                    <div class="text-uppercase small text-success-emphasis">Participation</div>
                                    <div id="selectedEventCounts" class="fw-semibold">—</div>
                                    <div class="text-muted small mb-0">Counts update automatically when you manage participants or exclusion groups below.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-light text-dark d-inline-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                <i class="fa-solid fa-people-group"></i>
                            </div>
                            <div>
                                <h2 class="h5 mb-0">Participants</h2>
                                <small class="text-muted">See everyone who joined and remove them if needed.</small>
                            </div>
                        </div>
                        <p class="text-muted small mb-2">Need to make a quick change? Use the Kick button to remove a name before drawing pairs.</p>
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="fw-semibold">Current list</div>
                            <span id="participantsCount" class="badge text-bg-light"></span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle" id="participantsTable">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Exclusion group</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="table-light">
                                        <td colspan="4" class="text-center text-muted">Select an event to view participants.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div id="participantsStatus" class="small text-muted mt-2"></div>
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
                        <p class="text-muted small">Add as many groups as you need—Kickback Kingdom will honor them when creating pairs.</p>

                        <form id="exclusionGroupForm" class="row g-2 align-items-end">
                            <div class="col-12 col-lg-5">
                                <label class="form-label" for="exclusionName">Group name</label>
                                <input class="form-control" id="exclusionName" placeholder="QA Team" required>
                            </div>
                            <div class="col-12 col-lg-2 d-grid">
                                <button class="btn btn-secondary" type="submit">Save group</button>
                            </div>
                            <div class="col-12">
                                <div id="exclusionStatus" class="small text-muted"></div>
                            </div>
                        </form>
                        <div class="border-top pt-3 mt-3">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <div class="fw-semibold">Current groups</div>
                                <span id="exclusionCount" class="badge text-bg-light"></span>
                            </div>
                            <div id="exclusionList" class="small text-muted">Select an event to view exclusion groups.</div>
                        </div>
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
                        <p class="text-muted small mb-2">Preview the pairs before you email everyone. We only send messages once per click.</p>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <button class="btn btn-primary" id="generatePairsBtn" type="button">Generate pairs</button>
                            <button class="btn btn-outline-primary" id="emailAssignmentsBtn" type="button">Email assignments</button>
                            <div id="assignmentStatus" class="small text-muted align-self-center"></div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle" id="assignmentTable">
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

            </div>
            <?php require("../php-components/base-page-discord.php"); ?>
        </div>
        <?php require("../php-components/base-page-footer.php"); ?>
    </main>

    <?php require("../php-components/base-page-javascript.php"); ?>
    <script>
        const ownerEventsList = document.getElementById('ownerEventsList');
        const ownerEventsStatus = document.getElementById('ownerEventsStatus');
        const selectedEventName = document.getElementById('selectedEventName');
        const selectedEventDescription = document.getElementById('selectedEventDescription');
        const selectedSignupDeadline = document.getElementById('selectedSignupDeadline');
        const selectedGiftDeadline = document.getElementById('selectedGiftDeadline');
        const selectedInviteToken = document.getElementById('selectedInviteToken');
        const selectedEventCounts = document.getElementById('selectedEventCounts');
        const participantsTableBody = document.querySelector('#participantsTable tbody');
        const participantsCount = document.getElementById('participantsCount');
        const participantsStatus = document.getElementById('participantsStatus');
        const exclusionList = document.getElementById('exclusionList');
        const exclusionCount = document.getElementById('exclusionCount');
        let activeEventData = null;
        const prefillInvite = '<?php echo htmlspecialchars($prefillInvite); ?>';

        const exclusionForm = document.getElementById('exclusionGroupForm');
        const exclusionStatus = document.getElementById('exclusionStatus');
        const generatePairsBtn = document.getElementById('generatePairsBtn');
        const emailAssignmentsBtn = document.getElementById('emailAssignmentsBtn');
        const assignmentTable = document.getElementById('assignmentTable').querySelector('tbody');
        const assignmentStatus = document.getElementById('assignmentStatus');

        function setActiveEvent(evt) {
            activeEventData = evt ? { ...evt } : null;
        }

        function resetParticipantsTable(message) {
            participantsTableBody.innerHTML = `<tr class="table-light"><td colspan="4" class="text-center text-muted">${message}</td></tr>`;
            participantsCount.textContent = '';
            participantsStatus.textContent = '';
        }

        function setEventSummary(evt) {
            selectedEventName.textContent = evt.name || 'No event selected yet.';
            selectedEventDescription.textContent = evt.description || '';
            selectedSignupDeadline.textContent = evt.signup_deadline || '—';
            selectedGiftDeadline.textContent = evt.gift_deadline || '—';
            selectedInviteToken.textContent = evt.invite_token || '—';
            selectedEventCounts.textContent = `${evt.participant_count ?? 0} participants • ${evt.exclusion_group_count ?? 0} exclusion groups`;
            resetParticipantsTable('Loading participants...');
            exclusionList.textContent = 'Loading exclusion groups...';
            exclusionCount.textContent = '';
        }

        function updateSelectedCounts(participants, groups) {
            selectedEventCounts.textContent = `${participants.length} participants • ${groups.length} exclusion groups`;
            participantsCount.textContent = participants.length;
            exclusionCount.textContent = groups.length;
        }

        async function kickParticipant(participant, button) {
            if (!activeEventData) {
                participantsStatus.textContent = 'Select an event first.';
                return;
            }

            const confirmed = confirm(`Remove ${participant.display_name} from this event?`);
            if (!confirmed) {
                return;
            }

            participantsStatus.textContent = `Removing ${participant.display_name}...`;
            button.disabled = true;

            try {
                const resp = await postForm('/api/v1/secret-santa/remove-participant.php', {
                    event_ctime: activeEventData.ctime,
                    event_crand: activeEventData.crand,
                    participant_ctime: participant.ctime,
                    participant_crand: participant.crand
                });
                participantsStatus.textContent = resp.message || '';
                if (resp.success) {
                    loadEventDetails(activeEventData);
                }
            } catch (err) {
                console.error(err);
                participantsStatus.textContent = 'Unable to remove participant right now.';
            } finally {
                button.disabled = false;
            }
        }

        function renderParticipants(participants) {
            participantsStatus.textContent = '';
            participantsTableBody.innerHTML = '';

            if (!participants || !participants.length) {
                resetParticipantsTable('No one has joined this event yet.');
                participantsCount.textContent = '0';
                return;
            }

            participants.forEach((person) => {
                const row = document.createElement('tr');

                const nameCell = document.createElement('td');
                nameCell.textContent = person.display_name;

                const emailCell = document.createElement('td');
                emailCell.className = 'text-muted small';
                emailCell.textContent = person.email;

                const groupCell = document.createElement('td');
                groupCell.textContent = person.exclusion_group_name || '—';

                const actionCell = document.createElement('td');
                actionCell.className = 'text-end';
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'btn btn-outline-danger btn-sm';
                removeBtn.textContent = 'Kick';
                removeBtn.addEventListener('click', () => kickParticipant(person, removeBtn));
                actionCell.appendChild(removeBtn);

                row.appendChild(nameCell);
                row.appendChild(emailCell);
                row.appendChild(groupCell);
                row.appendChild(actionCell);

                participantsTableBody.appendChild(row);
            });

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
                item.textContent = `${group.group_name}`;
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
            setActiveEvent(evt);
            if (listItem) {
                highlightSelected(listItem);
            }

            setEventSummary(evt);

            try {
                const resp = await fetch(`/api/v1/secret-santa/validate-invite.php?invite_token=${encodeURIComponent(evt.invite_token)}`, { credentials: 'include' });
                const data = await resp.json();
                if (!data.success) {
                    resetParticipantsTable(data.message || 'Unable to load participants.');
                    exclusionList.textContent = data.message || 'Unable to load exclusion groups.';
                    return;
                }

                const eventData = data.data || {};
                const participants = eventData.participants || [];
                const groups = eventData.exclusion_groups || [];
                activeEventData = { ...evt, participant_count: participants.length, exclusion_group_count: groups.length };
                renderParticipants(participants);
                renderExclusionGroups(groups);
                updateSelectedCounts(participants, groups);
            } catch (err) {
                console.error(err);
                resetParticipantsTable('Unable to load participants.');
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

            let autoSelected = false;

            events.forEach((evt, idx) => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'list-group-item list-group-item-action owner-event-card p-0 text-start';
                item.innerHTML = `
                    <div class="p-3 d-flex flex-column gap-2 w-100">
                        <div class="d-flex align-items-start gap-3 flex-wrap">
                            <div class="rounded-circle bg-primary-subtle text-primary-emphasis d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 46px; height: 46px;">
                                <i class="fa-solid fa-gift"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <div class="fw-semibold">${evt.name}</div>
                                        <span class="badge text-bg-primary-subtle text-primary-emphasis">${evt.participant_count ?? 0} joined</span>
                                    </div>
                                    <span class="text-primary fw-semibold small d-flex align-items-center gap-1">
                                        <i class="fa-solid fa-arrow-turn-up"></i>
                                        Open tools
                                    </span>
                                </div>
                                <div class="text-muted small d-flex flex-wrap gap-2 mt-2 event-meta">
                                    <span class="event-pill"><i class="fa-solid fa-ticket"></i>${evt.invite_token}</span>
                                    <span class="event-pill"><i class="fa-solid fa-calendar-check"></i>Signups: ${evt.signup_deadline}</span>
                                    <span class="event-pill"><i class="fa-solid fa-calendar-day"></i>Gifts: ${evt.gift_deadline}</span>
                                </div>
                            </div>
                        </div>
                        <div class="text-muted small">Participants, exclusions, and assignments will sync after you select this event.</div>
                    </div>
                `;

                item.addEventListener('click', () => loadEventDetails(evt, item));
                ownerEventsList.appendChild(item);

                const shouldPrefill = prefillInvite && evt.invite_token === prefillInvite;
                if (!autoSelected && (shouldPrefill || (!prefillInvite && idx === 0))) {
                    autoSelected = true;
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
            if (!activeEventData) {
                exclusionStatus.textContent = 'Select an event first.';
                return;
            }
            try {
                const resp = await postForm('/api/v1/secret-santa/exclusion-group.php', {
                    event_ctime: activeEventData.ctime,
                    event_crand: activeEventData.crand,
                    name: document.getElementById('exclusionName').value
                });
                exclusionStatus.textContent = resp.message || '';
                if (resp.success) {
                    loadEventDetails(activeEventData);
                }
            } catch (err) {
                console.error(err);
                exclusionStatus.textContent = 'Unable to save exclusion group.';
            }
        });

        generatePairsBtn.addEventListener('click', async () => {
            assignmentStatus.textContent = 'Generating pairs...';
            if (!activeEventData) {
                assignmentStatus.textContent = 'Select an event first.';
                return;
            }
            try {
                const resp = await postForm('/api/v1/secret-santa/generate-pairs.php', {
                    event_ctime: activeEventData.ctime,
                    event_crand: activeEventData.crand
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
            if (!activeEventData) {
                assignmentStatus.textContent = 'Select an event first.';
                return;
            }
            try {
                const resp = await postForm('/api/v1/secret-santa/email-assignments.php', {
                    event_ctime: activeEventData.ctime,
                    event_crand: activeEventData.crand
                });
                assignmentStatus.textContent = resp.message || '';
            } catch (err) {
                console.error(err);
                assignmentStatus.textContent = 'Failed to send assignment emails.';
            }
        });
    </script>
</body>

</html>
