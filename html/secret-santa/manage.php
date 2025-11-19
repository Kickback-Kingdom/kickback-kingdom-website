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
        .owner-event-card {
            border: 1px solid var(--bs-border-color-translucent);
            border-radius: 0.75rem;
            transition: border-color 120ms ease, background-color 120ms ease;
            background-color: var(--bs-body-bg);
        }

        .owner-event-card:hover,
        .owner-event-card:focus-visible {
            border-color: var(--bs-primary);
            background-color: var(--bs-primary-bg-subtle);
            outline: none;
        }

        .owner-event-card.active {
            border-color: var(--bs-primary);
            background-color: var(--bs-primary-bg-subtle);
        }
    </style>
    <main class="container pt-4" style="margin-bottom: 56px;">
        <div class="row">
            <div class="col-12 col-xl-9">
                <?php
                $activePageName = "Secret Santa Owner Dashboard";
                require("../php-components/base-page-breadcrumbs.php");
                ?>
                <div class="mb-4">
                    <h1 class="h4 mb-1">Secret Santa management</h1>
                    <p class="text-muted mb-0">Pick an event, adjust the roster, and send out assignments without extra clutter.</p>
                </div>
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                            <div>
                                <h2 class="h5 mb-0">Your events</h2>
                                <small class="text-muted">Click one to load details below.</small>
                            </div>
                            <small class="text-muted">Invite tokens are listed for quick sharing.</small>
                        </div>
                        <div id="ownerEventsStatus" class="small text-muted mb-2">Loading your events...</div>
                        <div id="ownerEventsList" class="list-group list-group-flush d-flex flex-column gap-2"></div>
                    </div>
                </div>

                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <h2 class="h5 mb-2">Selected event</h2>
                        <p class="text-muted small mb-3">Pick an event from the list. Details update instantly when you switch.</p>
                        <div class="row g-3">
                            <div class="col-12 col-lg-8">
                                <div class="p-3 border rounded-3 h-100">
                                    <div class="text-uppercase small text-muted">Event name</div>
                                    <div id="selectedEventName" class="fw-semibold">No event selected yet.</div>
                                    <div id="selectedEventDescription" class="text-muted mb-0"></div>
                                </div>
                            </div>
                            <div class="col-6 col-lg-4">
                                <div class="p-3 border rounded-3 h-100">
                                    <div class="text-uppercase small text-muted">Invite token</div>
                                    <div id="selectedInviteToken" class="fw-semibold">—</div>
                                    <div class="text-muted small">Share with teammates.</div>
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
                                <div class="p-3 border rounded-3 h-100">
                                    <div class="text-uppercase small text-muted">Participation</div>
                                    <div id="selectedEventCounts" class="fw-semibold">—</div>
                                    <div class="text-muted small mb-0">Counts refresh when you manage the lists below.</div>
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
                item.className = 'list-group-item list-group-item-action owner-event-card p-3 text-start';
                item.innerHTML = `
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                        <div>
                            <div class="fw-semibold">${evt.name}</div>
                            <div class="text-muted small">Invite token: ${evt.invite_token || '—'}</div>
                        </div>
                        <div class="text-muted small d-flex flex-wrap gap-3">
                            <span>${evt.participant_count ?? 0} participant${(evt.participant_count ?? 0) === 1 ? '' : 's'}</span>
                            <span>Signups: ${evt.signup_deadline || '—'}</span>
                            <span>Gifts: ${evt.gift_deadline || '—'}</span>
                        </div>
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
